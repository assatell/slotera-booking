<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

final class TranslationQualityReportService
{
    private TranslationQualityScanner $scanner;

    public function __construct(?TranslationQualityScanner $scanner = null)
    {
        $this->scanner = $scanner ?? new TranslationQualityScanner();
    }

    public function report(): array
    {
        return $this->scanner->scan();
    }

    public function checks(?array $report = null): array
    {
        $report = $report ?? $this->report();
        $labels = [
            'frontend_ui' => 'Frontend UI',
            'email_settings' => 'Email Settings',
            'email_templates' => 'Email Templates',
        ];
        $checks = [];

        foreach ($labels as $key => $label) {
            $section = (array) ($report[$key] ?? []);
            $issues = (array) ($section['issues'] ?? []);
            $critical = (int) ($section['critical'] ?? ($section['severity']['critical'] ?? 0));
            $warnings = (int) ($section['warnings'] ?? ($section['severity']['warning'] ?? 0));
            $notices = (int) ($section['notices'] ?? ($section['severity']['notice'] ?? 0));
            $scannerStatus = (string) ($section['status'] ?? 'in_progress');
            $status = $scannerStatus === 'blocked'
                ? 'critical'
                : (in_array($scannerStatus, ['needs_attention', 'in_progress'], true) ? 'warning' : 'ok');

            $recommendation = '';
            if ($critical > 0) {
                $recommendation = 'Expand details below and fix critical placeholder or HTML issues first.';
            } elseif ($warnings > 0) {
                $recommendation = 'Expand details below and review English fallbacks, mixed-language candidates, and translation quality warnings.';
            } elseif ($notices > 0) {
                $recommendation = 'Continue translating the missing strings shown as notices.';
            }

            $checks[] = [
                'label' => $label,
                'status' => $status,
                'detail' => sprintf(
                    'active scope: completion=%s%%; quality=%s%%; readiness=%s%%; status=%s; %d items, %d active locales; critical=%d; warnings=%d; notices=%d; missing=%d; same_as_english=%d; mixed_candidates=%d; quality_lint=%d; ux_linguistic=%d; freeze_drift=%d; placeholder_errors=%d; html_errors=%d; catalog backlog=%d issues across %d locales',
                    (string) ($section['translation_completion'] ?? 100),
                    (string) ($section['quality'] ?? 100),
                    (string) ($section['release_readiness'] ?? 100),
                    ucfirst(str_replace('_', ' ', $scannerStatus)),
                    (int) ($section['items'] ?? 0),
                    (int) ($section['locales'] ?? 0),
                    $critical,
                    $warnings,
                    $notices,
                    (int) ($issues['missing'] ?? 0),
                    (int) ($issues['same_as_english'] ?? 0),
                    (int) ($issues['mixed_candidates'] ?? 0),
                    (int) ($issues['quality_lint'] ?? 0),
                    (int) ($issues['ux_linguistic'] ?? 0),
                    (int) ($issues['freeze_drift'] ?? 0),
                    (int) ($issues['placeholder_errors'] ?? 0),
                    (int) ($issues['html_errors'] ?? 0),
                    (int) ($section['catalog']['backlog_issues'] ?? 0),
                    (int) ($section['catalog']['locales'] ?? ($section['locales'] ?? 0))
                ),
                'recommendation' => $recommendation,
            ];
        }

        return $checks;
    }
}
