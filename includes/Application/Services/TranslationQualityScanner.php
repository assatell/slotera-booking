<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Application\TranslationQuality\ScanIssue;
use Slotera\Application\TranslationQuality\ScanResult;
use Slotera\Application\TranslationQuality\ScoreCalculator;
use Slotera\Application\TranslationQuality\SeverityResolver;
use Slotera\Application\TranslationQuality\PlaceholderAnalyzer;
use Slotera\Application\TranslationQuality\HtmlAnalyzer;
use Slotera\Application\TranslationQuality\TranslationTextClassifier;

if (!defined('ABSPATH')) { exit; }

final class TranslationQualityScanner
{
    public const VERSION = '3.3.12';

    private SeverityResolver $severityResolver;
    private ScoreCalculator $scoreCalculator;
    private PlaceholderAnalyzer $placeholderAnalyzer;
    private HtmlAnalyzer $htmlAnalyzer;
    private TranslationTextClassifier $textClassifier;
    private TranslationQualityLintService $qualityLintService;
    private TranslationUxLinguisticService $uxLinguisticService;
    private TranslationFreezeService $freezeService;

    public function __construct(
        ?SeverityResolver $severityResolver = null,
        ?ScoreCalculator $scoreCalculator = null,
        ?PlaceholderAnalyzer $placeholderAnalyzer = null,
        ?HtmlAnalyzer $htmlAnalyzer = null,
        ?TranslationTextClassifier $textClassifier = null,
        ?TranslationQualityLintService $qualityLintService = null,
        ?TranslationUxLinguisticService $uxLinguisticService = null,
        ?TranslationFreezeService $freezeService = null
    ) {
        $this->severityResolver = $severityResolver ?? new SeverityResolver();
        $this->scoreCalculator = $scoreCalculator ?? new ScoreCalculator();
        $this->placeholderAnalyzer = $placeholderAnalyzer ?? new PlaceholderAnalyzer();
        $this->htmlAnalyzer = $htmlAnalyzer ?? new HtmlAnalyzer();
        $this->textClassifier = $textClassifier ?? new TranslationTextClassifier();
        $this->qualityLintService = $qualityLintService ?? new TranslationQualityLintService();
        $this->uxLinguisticService = $uxLinguisticService ?? new TranslationUxLinguisticService();
        $this->freezeService = $freezeService ?? new TranslationFreezeService();
    }

    public function scan(): array
    {
        $translationService = new TranslationService();

        return [
            'frontend_ui' => $this->scan_registry_group('frontend', 'frontend_ui', $translationService->locale_for_group('frontend')),
            'email_settings' => $this->scan_registry_group('emails', 'email_settings', $translationService->locale_for_group('emails')),
            'email_templates' => $this->scan_templates($translationService->locale_for_group('emails')),
        ];
    }

    private function scan_registry_group(string $group, string $section, string $activeLocale): array
    {
        $strings = TranslationRegistry::strings_for_group($group);
        $languages = TranslationRegistry::languages_for_group($group);
        unset($languages['en_US']);
        $localeReports = [];
        $allIssues = [];

        foreach (array_keys($languages) as $locale) {
            $issues = [];
            $localeVocabulary = $this->registry_vocabulary($strings, (string) $locale);
            foreach ($strings as $key => $meta) {
                $source = trim((string)($meta['default'] ?? ''));
                $translation = trim((string)($meta[$locale] ?? ''));
                if ($translation === '') {
                    $issues[] = $this->issue($locale, $section, (string)$key, 'missing', 'Missing translation', $source, '');
                    continue;
                }
                if ($translation === $source && $source !== '' && !$this->textClassifier->is_accepted_same_as_source($translation, (string)$locale) && !$this->is_allowed_same_as_english((string)$locale, $section, (string)$key)) {
                    $issues[] = $this->issue($locale, $section, (string)$key, 'same_as_english', 'Same as English', $source, $translation);
                }
                $placeholder = $this->placeholderAnalyzer->analyze($source, $translation);
                if (!$placeholder['valid']) {
                    $issues[] = $this->issue($locale, $section, (string)$key, 'placeholder_errors', (string)$placeholder['message'], $source, $translation, $placeholder);
                }
                foreach ($this->technical_text_issues($locale, $section, (string)$key, $source, $translation) as $technicalIssue) {
                    $issues[] = $technicalIssue;
                }
                $mixed = $this->textClassifier->mixed_evidence($source, $translation, (string)$locale);
                if ($mixed['mixed']) {
                    $issues[] = $this->issue($locale, $section, (string)$key, 'mixed_candidates', 'Copied English phrase detected', $source, $translation, $mixed);
                }
                $qualityLint = $this->qualityLintService->analyze($source, $this->strip_runtime_tokens($translation), (string)$locale);
                if (!$qualityLint['valid']) {
                    $issues[] = $this->issue($locale, $section, (string)$key, 'quality_lint', (string)$qualityLint['message'], $source, $translation, $qualityLint);
                }
                $uxLinguistic = $this->uxLinguisticService->analyze($source, $translation, (string)$locale, $localeVocabulary);
                if (!$uxLinguistic['valid']) {
                    $issues[] = $this->issue($locale, $section, (string)$key, 'ux_linguistic', (string)$uxLinguistic['message'], $source, $translation, $uxLinguistic);
                }
            }
            foreach ($this->duplicate_translation_issues((string)$locale, $section, $strings) as $duplicateIssue) {
                $issues[] = $duplicateIssue;
            }
            foreach ($this->duplicate_english_msgid_issues((string)$locale, $section, $strings) as $contextIssue) {
                $issues[] = $contextIssue;
            }
            foreach ($this->completeness_issues((string)$locale, $section, $strings) as $matrixIssue) {
                $issues[] = $matrixIssue;
            }
            $freeze = $this->freezeService->verify($group, (string)$locale);
            if (!($freeze['valid'] ?? true)) {
                $issues[] = $this->issue((string)$locale, $section, '__locale_freeze__', 'freeze_drift', (string)($freeze['message'] ?? 'Frozen locale changed.'), '', '', $freeze);
            }
            $localeReports[$locale] = $this->build_locale_report($section, (string)$locale, count($strings), $issues);
            $allIssues = array_merge($allIssues, $issues);
        }

        $normalizedActiveLocale = $this->registry_locale($activeLocale);
        return $this->build_section_result($section, count($strings), count($languages), $localeReports, $allIssues, [$normalizedActiveLocale]);
    }

    private function scan_templates(string $activeLocale): array
    {
        $section = 'email_templates';
        $base = EmailTemplateRegistry::qa_base_scenarios();
        $locales = EmailTemplateRegistry::qa_locales();
        $normalizedActiveLocale = $this->normalize_locale($activeLocale);
        if ($normalizedActiveLocale !== '' && $normalizedActiveLocale !== 'en_US' && !in_array($normalizedActiveLocale, $locales, true)) {
            $locales[] = $normalizedActiveLocale;
        }
        $localeReports = [];
        $allIssues = [];
        $sourceCount = 0;
        foreach ($base as $fields) {
            foreach (['title','description','default_subject','default_body','default_html_body'] as $field) {
                if (array_key_exists($field, $fields)) { $sourceCount++; }
            }
        }

        foreach ($locales as $locale) {
            $localized = EmailTemplateRegistry::qa_translation_fields_for_locale($locale);
            $issues = [];
            $localeVocabulary = $this->template_vocabulary($localized);
            foreach ($base as $scenario => $fields) {
                foreach (['title','description','default_subject','default_body','default_html_body'] as $field) {
                    if (!array_key_exists($field, $fields)) { continue; }
                    $source = trim((string)$fields[$field]);
                    $translation = trim((string)($localized[$scenario][$field] ?? ''));
                    $key = $scenario . '.' . $field;
                    if ($translation === '') {
                        $issues[] = $this->issue($locale, $section, $key, 'missing', 'Missing required field', $source, '');
                        continue;
                    }
                    if ($translation === $source && !$this->textClassifier->is_accepted_same_as_source($translation, (string)$locale)) {
                        $issues[] = $this->issue($locale, $section, $key, 'same_as_english', 'Same as English', $source, $translation);
                    }
                    $placeholder = $this->placeholderAnalyzer->analyze($source, $translation);
                    if (!$placeholder['valid']) {
                        $issues[] = $this->issue($locale, $section, $key, 'placeholder_errors', (string)$placeholder['message'], $source, $translation, $placeholder);
                    }
                    foreach ($this->technical_text_issues($locale, $section, $key, $source, $translation) as $technicalIssue) {
                        $issues[] = $technicalIssue;
                    }
                    if ($field === 'default_html_body') {
                        $html = $this->htmlAnalyzer->analyze($source, $translation);
                        if (!$html['valid']) {
                            $issues[] = $this->issue($locale, $section, $key, 'html_errors', implode(' ', $html['errors']), $source, $translation, $html);
                        }
                    }
                    $mixed = $this->textClassifier->mixed_evidence($source, $translation, (string)$locale);
                    if ($mixed['mixed']) {
                        $issues[] = $this->issue($locale, $section, $key, 'mixed_candidates', 'Copied English phrase detected', $source, $translation, $mixed);
                    }
                    $qualityLint = $this->qualityLintService->analyze($source, $this->strip_runtime_tokens($translation), (string)$locale);
                    if (!$qualityLint['valid']) {
                        $issues[] = $this->issue($locale, $section, $key, 'quality_lint', (string)$qualityLint['message'], $source, $translation, $qualityLint);
                    }
                    $uxLinguistic = $this->uxLinguisticService->analyze($source, $translation, (string)$locale, $localeVocabulary);
                    if (!$uxLinguistic['valid']) {
                        $issues[] = $this->issue($locale, $section, $key, 'ux_linguistic', (string)$uxLinguistic['message'], $source, $translation, $uxLinguistic);
                    }
                }
            }
            foreach ($this->template_duplicate_translation_issues((string)$locale, $section, $base, $localized) as $duplicateIssue) {
                $issues[] = $duplicateIssue;
            }
            $contextFlat = [];
            foreach ($base as $scenario => $fields) {
                foreach (['title','description','default_subject','default_body','default_html_body'] as $field) {
                    if (!array_key_exists($field, $fields)) { continue; }
                    $contextFlat[$scenario . '.' . $field] = ['default' => $fields[$field], (string)$locale => $localized[$scenario][$field] ?? ''];
                }
            }
            foreach ($this->duplicate_english_msgid_issues((string)$locale, $section, $contextFlat) as $contextIssue) {
                $issues[] = $contextIssue;
            }
            $freeze = $this->freezeService->verify('email_templates', (string)$locale);
            if (!($freeze['valid'] ?? true)) {
                $issues[] = $this->issue((string)$locale, $section, '__locale_freeze__', 'freeze_drift', (string)($freeze['message'] ?? 'Frozen locale changed.'), '', '', $freeze);
            }
            $localeReports[$locale] = $this->build_locale_report($section, (string)$locale, $sourceCount, $issues);
            $allIssues = array_merge($allIssues, $issues);
        }

        return $this->build_section_result($section, $sourceCount, count($locales), $localeReports, $allIssues, [$normalizedActiveLocale]);
    }


    private function registry_locale(string $locale): string
    {
        $locale = $this->normalize_locale($locale);
        $aliases = [
            'et_EE' => 'et',
            'fi_FI' => 'fi',
            'el_GR' => 'el',
            'hr_HR' => 'hr',
            'lv_LV' => 'lv',
            'nb_NO' => 'no_NO',
        ];
        return $aliases[$locale] ?? $locale;
    }

    private function strip_runtime_tokens(string $value): string
    {
        return preg_replace('~\{[A-Za-z0-9_.:-]+\}|https?://\S+|\b\S+@\S+\b~u', ' ', $value) ?? $value;
    }

    private function build_locale_report(string $section, string $locale, int $sourceCount, array $issueObjects): array
    {
        $counts = $this->issue_counts($issueObjects);
        $severity = $this->severity_counts($issueObjects);
        $translated = max(0, $sourceCount - $counts['missing']);
        $invalidKeys = [];
        foreach ($issueObjects as $issue) {
            if ($issue->severity() === SeverityResolver::CRITICAL || in_array($issue->type(), ['same_as_english', 'mixed_candidates', 'quality_lint', 'ux_linguistic'], true)) {
                $data = $issue->to_array();
                $invalidKeys[(string)$data['key']] = true;
            }
        }
        $valid = max(0, $translated - count($invalidKeys));
        $scores = $this->scoreCalculator->calculate($sourceCount, $translated, $valid, $severity['critical']);
        $status = $this->scoreCalculator->status($scores, $severity['critical'], $severity['warning']);
        $details = array_map(static fn(ScanIssue $issue): array => $issue->to_array(), $issueObjects);
        $this->sort_details($details);

        return [
            'locale' => $locale,
            'section' => $section,
            'totals' => ['source_strings'=>$sourceCount,'translated_strings'=>$translated,'valid_strings'=>$valid],
            'scores' => $scores,
            'issues' => $counts,
            'severity' => $severity,
            'status' => $status,
            'details' => $details,
            'coverage' => $scores['completion'],
            'quality' => $scores['quality'],
            'coverage_status' => $scores['completion'] >= 99.9 ? 'Complete' : ($scores['completion'] >= 95 ? 'Nearly complete' : 'In progress'),
        ];
    }

    private function build_section_result(string $section, int $sourcePerLocale, int $locales, array $localeReports, array $allIssues, array $activeLocales): array
    {
        $activeLocales = array_values(array_unique(array_filter($activeLocales, static fn(string $locale): bool => $locale !== '' && $locale !== 'en_US')));
        $activeReports = array_intersect_key($localeReports, array_fill_keys($activeLocales, true));

        // English requires no translation. When no non-English locale is active,
        // report a clean active scope while retaining the complete catalog backlog.
        $activeSource = $sourcePerLocale * count($activeReports);
        $translated = 0;
        $valid = 0;
        foreach ($activeReports as $report) {
            $translated += (int)($report['totals']['translated_strings'] ?? 0);
            $valid += (int)($report['totals']['valid_strings'] ?? 0);
        }

        $activeIssues = array_values(array_filter($allIssues, static function (ScanIssue $issue) use ($activeLocales): bool {
            $data = $issue->to_array();
            return in_array((string)($data['locale'] ?? ''), $activeLocales, true);
        }));
        $counts = $this->issue_counts($activeIssues);
        $severity = $this->severity_counts($activeIssues);
        $scores = $this->scoreCalculator->calculate($activeSource, $translated, $valid, $severity['critical']);
        $status = $this->scoreCalculator->status($scores, $severity['critical'], $severity['warning']);

        $details = array_map(static fn(ScanIssue $issue): array => $issue->to_array(), $allIssues);
        $activeDetails = array_map(static fn(ScanIssue $issue): array => $issue->to_array(), $activeIssues);
        $this->sort_details($details);
        $this->sort_details($activeDetails);

        $result = (new ScanResult($section, $activeSource, $sourcePerLocale, $translated, $valid, count($activeReports), $scores, $counts, $severity, $status, $details, $localeReports))->to_array();
        $result['active_locales'] = array_keys($activeReports);
        $result['active_details'] = $activeDetails;
        $result['catalog'] = [
            'locales' => $locales,
            'issues' => $this->issue_counts($allIssues),
            'severity' => $this->severity_counts($allIssues),
            'backlog_issues' => max(0, count($allIssues) - count($activeIssues)),
        ];

        return $result;
    }

    /** @param array<string,array<string,mixed>> $strings @return array<string,int> */
    private function registry_vocabulary(array $strings, string $locale): array
    {
        $values = [];
        foreach ($strings as $meta) {
            $value = trim((string) ($meta[$locale] ?? ''));
            if ($value !== '') { $values[] = $value; }
        }
        return $this->build_vocabulary($values);
    }

    /** @param array<string,array<string,mixed>> $localized @return array<string,int> */
    private function template_vocabulary(array $localized): array
    {
        $values = [];
        foreach ($localized as $fields) {
            foreach ($fields as $value) {
                if (is_string($value) && trim($value) !== '') { $values[] = $value; }
            }
        }
        return $this->build_vocabulary($values);
    }

    /** @param array<int,string> $values @return array<string,int> */
    private function build_vocabulary(array $values): array
    {
        $vocabulary = [];
        foreach ($values as $value) {
            $plain = preg_replace([
                '~https?://\S+|\b\S+@\S+\b~u',
                '/(?<!%)%(?:\d+\$)?[-+0 #\']*(?:\d+|\*)?(?:\.(?:\d+|\*))?[bcdeEfFgGosuxX]/',
                '/\{\{\s*[a-zA-Z0-9_.-]+\s*\}\}/',
                '/(?<!\{)\{\s*[a-zA-Z0-9_.-]+\s*\}(?!\})/',
            ], ' ', strip_tags($value)) ?? $value;
            preg_match_all('/\p{L}[\p{L}\p{M}\'-]*/u', $plain, $matches);
            foreach (($matches[0] ?? []) as $word) {
                $word = function_exists('mb_strtolower') ? mb_strtolower((string) $word, 'UTF-8') : strtolower((string) $word);
                $vocabulary[$word] = ($vocabulary[$word] ?? 0) + 1;
            }
        }
        return $vocabulary;
    }

    /** @return array<int,ScanIssue> */
    private function technical_text_issues(string $locale, string $section, string $key, string $source, string $translation): array
    {
        $issues = [];
        $sourceTypes = $this->printf_type_signature($source);
        $translationTypes = $this->printf_type_signature($translation);
        if ($sourceTypes !== $translationTypes) {
            $issues[] = $this->issue($locale, $section, $key, 'placeholder_type_errors', 'Printf placeholder types differ.', $source, $translation, ['expected_types'=>$sourceTypes,'found_types'=>$translationTypes]);
        }
        $sourceEntities = $this->entity_signature($source);
        $translationEntities = $this->entity_signature($translation);
        if ($sourceEntities !== $translationEntities) {
            $issues[] = $this->issue($locale, $section, $key, 'html_entity_errors', 'HTML entity signature differs.', $source, $translation, ['expected'=>$sourceEntities,'found'=>$translationEntities]);
        }
        $unicode = $this->unicode_whitespace_findings($translation);
        if ($unicode['unicode'] !== []) {
            $issues[] = $this->issue($locale, $section, $key, 'unicode_errors', 'Invalid or unsafe Unicode marker detected.', $source, $translation, $unicode);
        }
        if ($unicode['whitespace'] !== []) {
            $issues[] = $this->issue($locale, $section, $key, 'whitespace_errors', 'Non-normalized whitespace detected.', $source, $translation, $unicode);
        }
        return $issues;
    }

    /** @return array<string,int> */
    private function printf_type_signature(string $text): array
    {
        preg_match_all('/(?<!%)%(?:(\d+)\$)?[-+0 #\']*(?:\d+|\*)?(?:\.(?:\d+|\*))?([bcdeEfFgGosuxX])/', $text, $matches, PREG_SET_ORDER);
        $types=[];
        foreach ($matches as $match) {
            $position=(string)($match[1] ?? '');
            $type=strtolower((string)($match[2] ?? ''));
            $family=in_array($type,['d','u','x','o','b'],true)?'integer':(in_array($type,['e','f','g'],true)?'float':($type==='s'?'string':$type));
            $token=($position!==''?$position.'$':'*').':'.$family;
            $types[$token]=($types[$token]??0)+1;
        }
        ksort($types);
        return $types;
    }

    /** @return array<string,int> */
    private function entity_signature(string $text): array
    {
        preg_match_all('/&(?:[a-zA-Z][a-zA-Z0-9]+|#\d+|#x[0-9a-fA-F]+);/', $text, $matches);
        $entities=[];
        foreach (($matches[0]??[]) as $entity) {$entity=strtolower((string)$entity);$entities[$entity]=($entities[$entity]??0)+1;}
        ksort($entities); return $entities;
    }

    /** @return array{unicode:array<int,string>,whitespace:array<int,string>} */
    private function unicode_whitespace_findings(string $text): array
    {
        $unicode=[];$whitespace=[];
        if (!preg_match('//u',$text)) {$unicode[]='invalid_utf8';}
        if (str_contains($text,"\xEF\xBB\xBF")) {$unicode[]='bom';}
        foreach (["\u{200B}"=>'zero_width_space',"\u{200C}"=>'zero_width_non_joiner',"\u{200D}"=>'zero_width_joiner',"\u{2060}"=>'word_joiner',"\u{FFFD}"=>'replacement_character'] as $char=>$label) {if(str_contains($text,$char)){$unicode[]=$label;}}
        if (str_contains($text,"\xC2\xA0")) {$whitespace[]='nbsp';}
        if (str_contains($text,"\t")) {$whitespace[]='tab';}
        if (str_contains($text,"\r\n")) {$whitespace[]='crlf';}
        if ($text!==trim($text)) {$whitespace[]='leading_or_trailing';}
        return ['unicode'=>array_values(array_unique($unicode)),'whitespace'=>array_values(array_unique($whitespace))];
    }

    /** @param array<string,array<string,mixed>> $strings @return array<int,ScanIssue> */
    private function duplicate_translation_issues(string $locale, string $section, array $strings): array
    {
        $groups=[];
        foreach($strings as $key=>$meta){$source=trim((string)($meta['default']??''));$value=trim((string)($meta[$locale]??''));if($value===''||$source===''){continue;}$norm=function_exists('mb_strtolower')?mb_strtolower(strip_tags($value),'UTF-8'):strtolower(strip_tags($value));$groups[$norm][]=[$key,$source,$value];}
        $issues=[];
        foreach($groups as $rows){if(count($rows)<3){continue;}$sources=array_unique(array_map(static fn(array $r):string=>$r[1],$rows));if(count($sources)<3){continue;}$keys=array_map(static fn(array $r):string=>(string)$r[0],$rows);sort($keys);if($this->is_allowed_duplicate_translation($locale,$section,$keys)){continue;}$issues[]=$this->issue($locale,$section,implode(', ',array_slice($keys,0,3)),'duplicate_translation','One translation is reused for several distinct source strings.',implode(' | ',array_slice($sources,0,3)),(string)$rows[0][2],['keys'=>$keys,'count'=>count($rows)]);}
        return $issues;
    }


    /** Duplicate English source strings require context review before translation reuse.
     *  This is informational and intentionally never auto-applies an existing translation.
     *  @param array<string,array<string,mixed>> $strings @return array<int,ScanIssue>
     */
    private function duplicate_english_msgid_issues(string $locale, string $section, array $strings): array
    {
        $groups = [];
        foreach ($strings as $key => $meta) {
            $source = trim(strip_tags((string) ($meta['default'] ?? '')));
            if ($source === '') { continue; }
            $norm = function_exists('mb_strtolower') ? mb_strtolower($source, 'UTF-8') : strtolower($source);
            $groups[$norm][] = [(string) $key, $source, trim((string) ($meta[$locale] ?? ''))];
        }
        $issues = [];
        foreach ($groups as $rows) {
            if (count($rows) < 2) { continue; }
            $keys = array_map(static fn(array $row): string => $row[0], $rows);
            sort($keys);
            $translations = array_values(array_unique(array_filter(array_map(static fn(array $row): string => $row[2], $rows), static fn(string $value): bool => $value !== '')));
            if ($this->is_acknowledged_duplicate_english_msgid($locale, $section, $keys)) { continue; }
            $issues[] = $this->issue(
                $locale,
                $section,
                implode(', ', array_slice($keys, 0, 3)),
                'duplicate_english_msgid',
                'Duplicate English msgid detected. Automatic translation reuse is disabled. Review each occurrence in its UI context.',
                (string) $rows[0][1],
                implode(' | ', array_slice($translations, 0, 3)),
                ['keys' => $keys, 'count' => count($rows), 'suggestion' => 'Review each key in context; translate independently even when the English msgid is identical.']
            );
        }
        return $issues;
    }


    private function is_allowed_same_as_english(string $locale, string $section, string $key): bool
    {
        $allowlist = [
            'fr_FR' => [
                'frontend_ui' => ['frontend.contact_message'],
            ],
            'ro_RO' => [
                'frontend_ui' => ['frontend.site', 'frontend.total', 'frontend.popular'],
                'email_settings' => ['emails.total'],
            ],
            'no_NO' => [
                'frontend_ui' => ['frontend.booking.label.status', 'frontend.status'],
                'email_settings' => ['emails.scenario', 'emails.send_test'],
            ],
            'mt_MT' => [
                'frontend_ui' => ['frontend.booking.label.status', 'frontend.status', 'frontend.vat', 'frontend.total'],
                'email_settings' => ['emails.booking.label.status', 'emails.emails', 'emails.settings.emails'],
            ],
        ];
        return in_array($key, $allowlist[$locale][$section] ?? [], true);
    }

    /** @param array<int,string> $keys */
    private function is_acknowledged_duplicate_english_msgid(string $locale, string $section, array $keys): bool
    {
        $acknowledged = [
            'ro_RO' => [
                'frontend_ui' => [
                    ['frontend.booking.label.status', 'frontend.status'],
                    ['frontend.booking.label.payment', 'frontend.payment'],
                    ['frontend.payment.status.failed', 'frontend.payment_failed'],
                    ['frontend.pay_on_arrival', 'frontend.payment.status.pay_on_arrival'],
                ],
            ],
            'bg_BG' => [
                'email_settings' => [
                    ['emails.booking.status.confirmed', 'emails.confirmed'],
                    ['emails.booking.status.cancelled', 'emails.cancelled'],
                    ['emails.booking.status.completed', 'emails.completed'],
                    ['emails.payment.status.unpaid', 'emails.unpaid'],
                    ['emails.awaiting_payment', 'emails.payment.status.pending'],
                    ['emails.paid', 'emails.payment.status.paid'],
                    ['emails.partially_paid', 'emails.payment.status.partial'],
                    ['emails.payment.status.refunded', 'emails.refunded'],
                    ['emails.payment.status.failed', 'emails.payment_failed'],
                    ['emails.pay_on_arrival', 'emails.payment.status.pay_on_arrival'],
                    ['emails.test.test_email_sent', 'emails.test_email_sent'],
                    ['emails.template', 'emails.templates.template'],
                    ['emails.send_test_email', 'emails.test.send_test_email'],
                    ['emails.emails', 'emails.settings.emails'],
                    ['emails.admin_notification_email', 'emails.settings.admin_notification_email'],
                    ['emails.recipient', 'emails.settings.recipient'],
                ],
            ],
            'el_GR' => [
                'frontend_ui' => [
                    ['frontend.booking.label.status', 'frontend.status'],
                    ['frontend.booking.label.payment', 'frontend.payment'],
                    ['frontend.payment.status.failed', 'frontend.payment_failed'],
                    ['frontend.pay_on_arrival', 'frontend.payment.status.pay_on_arrival'],
                ],
            ],
            'ga_IE' => [
                'frontend_ui' => [
                    ['frontend.booking.label.status', 'frontend.status'],
                    ['frontend.booking.label.payment', 'frontend.payment'],
                    ['frontend.payment.status.failed', 'frontend.payment_failed'],
                    ['frontend.pay_on_arrival', 'frontend.payment.status.pay_on_arrival'],
                ],
            ],
            'mt_MT' => [
                'frontend_ui' => [
                    ['frontend.booking.label.status', 'frontend.status'],
                    ['frontend.booking.label.payment', 'frontend.payment'],
                    ['frontend.payment.status.failed', 'frontend.payment_failed'],
                    ['frontend.pay_on_arrival', 'frontend.payment.status.pay_on_arrival'],
                ],
                'email_settings' => [
                    ['emails.booking.status.confirmed', 'emails.confirmed'],
                    ['emails.booking.status.cancelled', 'emails.cancelled'],
                    ['emails.booking.status.completed', 'emails.completed'],
                    ['emails.payment.status.unpaid', 'emails.unpaid'],
                    ['emails.awaiting_payment', 'emails.payment.status.pending'],
                    ['emails.paid', 'emails.payment.status.paid'],
                    ['emails.partially_paid', 'emails.payment.status.partial'],
                    ['emails.payment.status.refunded', 'emails.refunded'],
                    ['emails.payment.status.failed', 'emails.payment_failed'],
                    ['emails.pay_on_arrival', 'emails.payment.status.pay_on_arrival'],
                    ['emails.test.test_email_sent', 'emails.test_email_sent'],
                    ['emails.template', 'emails.templates.template'],
                    ['emails.send_test_email', 'emails.test.send_test_email'],
                    ['emails.emails', 'emails.settings.emails'],
                    ['emails.admin_notification_email', 'emails.settings.admin_notification_email'],
                    ['emails.recipient', 'emails.settings.recipient'],
                ],
            ],
            'is_IS' => [
                'frontend_ui' => [
                    ['frontend.booking.label.status', 'frontend.status'],
                    ['frontend.booking.label.payment', 'frontend.payment'],
                    ['frontend.payment.status.failed', 'frontend.payment_failed'],
                    ['frontend.pay_on_arrival', 'frontend.payment.status.pay_on_arrival'],
                ],
            ],
            'no_NO' => [
                'frontend_ui' => [
                    ['frontend.booking.label.status', 'frontend.status'],
                    ['frontend.booking.label.payment', 'frontend.payment'],
                    ['frontend.payment.status.failed', 'frontend.payment_failed'],
                    ['frontend.pay_on_arrival', 'frontend.payment.status.pay_on_arrival'],
                ],
                'email_settings' => [
                    ['emails.booking.status.confirmed', 'emails.confirmed'],
                    ['emails.booking.status.cancelled', 'emails.cancelled'],
                    ['emails.booking.status.completed', 'emails.completed'],
                    ['emails.payment.status.unpaid', 'emails.unpaid'],
                    ['emails.awaiting_payment', 'emails.payment.status.pending'],
                    ['emails.paid', 'emails.payment.status.paid'],
                    ['emails.partially_paid', 'emails.payment.status.partial'],
                    ['emails.payment.status.refunded', 'emails.refunded'],
                    ['emails.payment.status.failed', 'emails.payment_failed'],
                    ['emails.pay_on_arrival', 'emails.payment.status.pay_on_arrival'],
                    ['emails.test.test_email_sent', 'emails.test_email_sent'],
                    ['emails.template', 'emails.templates.template'],
                    ['emails.send_test_email', 'emails.test.send_test_email'],
                    ['emails.emails', 'emails.settings.emails'],
                    ['emails.admin_notification_email', 'emails.settings.admin_notification_email'],
                    ['emails.recipient', 'emails.settings.recipient'],
                ],
            ],
            'hu_HU' => [
                'frontend_ui' => [
                    ['frontend.booking.label.status', 'frontend.status'],
                    ['frontend.booking.label.payment', 'frontend.payment'],
                    ['frontend.payment.status.failed', 'frontend.payment_failed'],
                    ['frontend.pay_on_arrival', 'frontend.payment.status.pay_on_arrival'],
                ],
                'email_settings' => [
                    ['emails.booking.status.confirmed', 'emails.confirmed'],
                    ['emails.booking.status.cancelled', 'emails.cancelled'],
                    ['emails.booking.status.completed', 'emails.completed'],
                    ['emails.payment.status.unpaid', 'emails.unpaid'],
                    ['emails.awaiting_payment', 'emails.payment.status.pending'],
                    ['emails.paid', 'emails.payment.status.paid'],
                    ['emails.partially_paid', 'emails.payment.status.partial'],
                    ['emails.payment.status.refunded', 'emails.refunded'],
                    ['emails.payment.status.failed', 'emails.payment_failed'],
                    ['emails.pay_on_arrival', 'emails.payment.status.pay_on_arrival'],
                    ['emails.test.test_email_sent', 'emails.test_email_sent'],
                    ['emails.template', 'emails.templates.template'],
                    ['emails.send_test_email', 'emails.test.send_test_email'],
                    ['emails.emails', 'emails.settings.emails'],
                    ['emails.admin_notification_email', 'emails.settings.admin_notification_email'],
                    ['emails.recipient', 'emails.settings.recipient'],
                ],
            ],
        ];
        sort($keys);
        foreach ($acknowledged[$locale][$section] ?? [] as $accepted) {
            sort($accepted);
            if ($keys === $accepted) { return true; }
        }
        return false;
    }

    /** @param array<int,string> $keys */
    private function is_allowed_duplicate_translation(string $locale, string $section, array $keys): bool
    {
        $allowlist = [
            'da_DK' => [
                'email_settings' => [[
                    'emails.send_test',
                    'emails.send_test_email',
                    'emails.test.send_test_email',
                ]],
            ],
            'bg_BG' => [
                'email_settings' => [
                    ['emails.booking.status.confirmed', 'emails.confirmed'],
                    ['emails.booking.status.cancelled', 'emails.cancelled'],
                    ['emails.booking.status.completed', 'emails.completed'],
                    ['emails.payment.status.unpaid', 'emails.unpaid'],
                    ['emails.awaiting_payment', 'emails.payment.status.pending'],
                    ['emails.paid', 'emails.payment.status.paid'],
                    ['emails.partially_paid', 'emails.payment.status.partial'],
                    ['emails.payment.status.refunded', 'emails.refunded'],
                    ['emails.payment.status.failed', 'emails.payment_failed'],
                    ['emails.pay_on_arrival', 'emails.payment.status.pay_on_arrival'],
                    ['emails.test.test_email_sent', 'emails.test_email_sent'],
                    ['emails.template', 'emails.templates.template'],
                    ['emails.send_test_email', 'emails.test.send_test_email'],
                    ['emails.emails', 'emails.settings.emails'],
                    ['emails.admin_notification_email', 'emails.settings.admin_notification_email'],
                    ['emails.recipient', 'emails.settings.recipient'],
                ],
            ],
            'hu_HU' => [
                'frontend_ui' => [[
                    'frontend.places_left',
                    'frontend.spot_left',
                    'frontend.spots_left',
                ]],
            ],
            'sv_SE' => [
                'frontend_ui' => [[
                    'frontend.choose_dates',
                    'frontend.choose_your_date',
                    'frontend.select_date',
                ]],
            ],
        ];
        foreach ($allowlist[$locale][$section] ?? [] as $allowedKeys) {
            sort($allowedKeys);
            if ($keys === $allowedKeys) { return true; }
        }
        return false;
    }

    /** @param array<string,array<string,mixed>> $strings @return array<int,ScanIssue> */
    private function completeness_issues(string $locale, string $section, array $strings): array
    {
        $buckets=['admin'=>['admin','setting','dashboard'],'payments'=>['payment','checkout','coupon'],'booking'=>['booking','appointment','service','calendar'],'magic_links'=>['magic','token','manage_booking'],'ics'=>['ics','calendar_attachment']];
        $issues=[];
        // Registry language selectors use a few short aliases (for example `hr`),
        // while the translation catalogs store those values under canonical keys
        // such as `hr_HR`. Completeness must inspect the same catalog key used by
        // the normal per-string scan or fully translated sections are reported as 0/N.
        $catalogLocale = ['hr' => 'hr_HR'][$locale] ?? $locale;
        foreach($buckets as $name=>$needles){$sourceCount=0;$present=0;foreach($strings as $key=>$meta){$hay=strtolower((string)$key.' '.(string)($meta['default']??''));$match=false;foreach($needles as $needle){if(str_contains($hay,$needle)){$match=true;break;}}if(!$match){continue;}$sourceCount++;if(trim((string)($meta[$catalogLocale]??''))!==''){$present++;}}if($sourceCount>0&&$present!==$sourceCount){$issues[]=$this->issue($locale,$section,'__section_'.$name.'__','section_completeness',sprintf('Section %s incomplete: %d/%d.',$name,$present,$sourceCount),'','',['section'=>$name,'present'=>$present,'expected'=>$sourceCount]);}}
        return $issues;
    }

    /** @return array<int,ScanIssue> */
    private function template_duplicate_translation_issues(string $locale,string $section,array $base,array $localized): array
    {
        $flat=[];foreach($base as $scenario=>$fields){foreach(['title','description','default_subject','default_body','default_html_body'] as $field){if(!array_key_exists($field,$fields)){continue;}$flat[$scenario.'.'.$field]=['default'=>$fields[$field],$locale=>$localized[$scenario][$field]??''];}}
        return $this->duplicate_translation_issues($locale,$section,$flat);
    }

    private function normalize_locale(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));
        $aliases = [
            'ru' => 'ru_RU',
            'et' => 'et_EE',
            'de' => 'de_DE',
            'bg' => 'bg_BG',
            'fi' => 'fi_FI',
            'el' => 'el_GR',
            'hr' => 'hr_HR',
            'lv' => 'lv_LV',
            'nb' => 'no_NO',
            'nb_NO' => 'no_NO',
        ];
        return $aliases[$locale] ?? $locale;
    }

    private function issue(string $locale, string $section, string $key, string $type, string $message, string $source, string $translation, array $context = []): ScanIssue
    {
        return new ScanIssue($locale, $section, $key, $type, $this->severityResolver->for_type($type), $message, $source, $translation, $context);
    }

    private function issue_counts(array $issues): array
    {
        $counts = ['missing'=>0,'same_as_english'=>0,'mixed_candidates'=>0,'quality_lint'=>0,'ux_linguistic'=>0,'placeholder_errors'=>0,'placeholder_type_errors'=>0,'html_errors'=>0,'html_entity_errors'=>0,'unicode_errors'=>0,'whitespace_errors'=>0,'duplicate_translation'=>0,'duplicate_english_msgid'=>0,'section_completeness'=>0,'freeze_drift'=>0];
        foreach ($issues as $issue) { $counts[$issue->type()] = ($counts[$issue->type()] ?? 0) + 1; }
        return $counts;
    }

    private function severity_counts(array $issues): array
    {
        $counts = ['critical'=>0,'warning'=>0,'notice'=>0];
        foreach ($issues as $issue) { $counts[$issue->severity()] = ($counts[$issue->severity()] ?? 0) + 1; }
        return $counts;
    }

    private function sort_details(array &$details): void
    {
        usort($details, static function (array $a, array $b): int {
            $rank = ['critical'=>0,'warning'=>1,'notice'=>2];
            return ($rank[$a['severity'] ?? 'notice'] ?? 9) <=> ($rank[$b['severity'] ?? 'notice'] ?? 9);
        });
    }

}
