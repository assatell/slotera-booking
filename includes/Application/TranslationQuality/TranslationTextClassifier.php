<?php

declare(strict_types=1);

namespace Slotera\Application\TranslationQuality;

if (!defined('ABSPATH')) { exit; }

final class TranslationTextClassifier
{
    private const INVARIANT_WORDS = [
        'email','url','sms','api','seo','html','css','php','wordpress','slotera','google','apple','paypal','stripe','smtp','ics','wp-cron','webhook','tls','ssl',
        'calendar','woocommerce','javascript','json','csv','pdf','xml','http','https','oauth','uuid','iban','vat','id','ip','gps','qr','utc','gmt',
    ];

    private const LOCALE_SAME_AS_SOURCE_ALLOWLIST = [
        'da' => ['status'],
        'da_DK' => ['status'],
        'nl' => ['site','start','status'],
        'nl_NL' => ['site','start','status'],
    ];

    private const COMMON_SHARED_WORDS = [
        'admin','booking','calendar','client','code','date','email','form','google','html','id','login','menu','name','online','payment','paypal','phone','plugin','price','slotera','status','stripe','system','test','time','url','user','wordpress',
    ];

    public function is_accepted_same_as_source(string $value, string $locale): bool
    {
        if ($this->is_invariant($value) || $this->is_printf_time_unit_format($value)) {
            return true;
        }

        $locale = str_replace('-', '_', trim($locale));
        $language = substr($locale, 0, 2);
        $plain = $this->lower($this->plain($value));
        $plain = trim($plain, " \t\n\r\0\x0B:;,.!?()[]{}");

        return in_array($plain, self::LOCALE_SAME_AS_SOURCE_ALLOWLIST[$locale] ?? [], true)
            || in_array($plain, self::LOCALE_SAME_AS_SOURCE_ALLOWLIST[$language] ?? [], true);
    }

    public function is_invariant(string $value): bool
    {
        $plain = $this->plain($value);
        if ($plain === '' || $this->length($plain) <= 2) { return true; }
        if ($this->is_url_email_or_identifier($plain)) { return true; }
        if ($this->contains_no_letters($this->remove_placeholders($plain))) { return true; }

        $words = $this->words($plain);
        if ($words === []) { return true; }
        foreach ($words as $word) {
            if (!in_array($word, self::INVARIANT_WORDS, true)) {
                return false;
            }
        }
        return true;
    }

    public function mixed_evidence(string $source, string $translation, string $locale): array
    {
        if (in_array($locale, ['en_US','en'], true) || trim($translation) === trim($source)) {
            return ['mixed'=>false, 'matched_phrase'=>'', 'matched_words'=>0, 'source_ratio'=>0.0];
        }

        $sourceWords = $this->meaningful_words($source);
        $translationWords = $this->meaningful_words($translation);
        $sourceMeaningfulCount = count(array_filter($sourceWords));
        $translationMeaningfulCount = count(array_filter($translationWords));
        if ($sourceMeaningfulCount < 4 || $translationMeaningfulCount < 4) {
            return ['mixed'=>false, 'matched_phrase'=>'', 'matched_words'=>0, 'source_ratio'=>0.0];
        }

        [$phrase, $length] = $this->longest_common_contiguous_phrase($sourceWords, $translationWords);
        $sourceRatio = $sourceMeaningfulCount > 0 ? $length / $sourceMeaningfulCount : 0.0;
        $translationRatio = $translationMeaningfulCount > 0 ? $length / $translationMeaningfulCount : 0.0;

        // Three-word fragments are noisy in Latin-script languages. Require either a
        // longer copied phrase, or a three-word phrase that dominates both strings.
        $mixed = $length >= 4 || ($length >= 3 && $sourceRatio >= 0.60 && $translationRatio >= 0.45);

        return [
            'mixed' => $mixed,
            'matched_phrase' => implode(' ', $phrase),
            'matched_words' => $length,
            'source_ratio' => round($sourceRatio, 3),
            'translation_ratio' => round($translationRatio, 3),
        ];
    }

    private function meaningful_words(string $value): array
    {
        $value = $this->remove_placeholders($this->plain($value));
        preg_match_all('/\p{L}[\p{L}\p{M}\'-]*/u', $value, $matches);
        $result = [];
        foreach (($matches[0] ?? []) as $word) {
            $word = $this->lower((string)$word);
            if ($this->length($word) < 3 || in_array($word, self::INVARIANT_WORDS, true) || in_array($word, self::COMMON_SHARED_WORDS, true)) {
                $result[] = null; // Preserve a boundary so ignored words cannot join unrelated fragments.
                continue;
            }
            $result[] = $word;
        }
        return $result;
    }

    private function longest_common_contiguous_phrase(array $left, array $right): array
    {
        $bestLength = 0;
        $bestEnd = 0;
        $previous = array_fill(0, count($right) + 1, 0);
        foreach ($left as $i => $leftWord) {
            $current = array_fill(0, count($right) + 1, 0);
            foreach ($right as $j => $rightWord) {
                if ($leftWord !== null && $leftWord === $rightWord) {
                    $current[$j + 1] = $previous[$j] + 1;
                    if ($current[$j + 1] > $bestLength) {
                        $bestLength = $current[$j + 1];
                        $bestEnd = $i + 1;
                    }
                }
            }
            $previous = $current;
        }
        return [array_slice($left, $bestEnd - $bestLength, $bestLength), $bestLength];
    }

    private function is_printf_time_unit_format(string $value): bool
    {
        $plain = trim($this->plain($value));
        if ($plain === '' || preg_match('/%(?:\d+\$)?d/', $plain) !== 1) {
            return false;
        }

        // Compact duration labels are frequently identical across Latin-script
        // locales. Accept only placeholders, whitespace, punctuation and a
        // conservative set of hour/minute abbreviations.
        $withoutPlaceholders = preg_replace('/%(?:\d+\$)?d/', ' ', $plain) ?? $plain;
        $withoutUnits = preg_replace('/\b(?:h|hr|hrs|min|mins)\.?\b/iu', ' ', $withoutPlaceholders) ?? $withoutPlaceholders;

        return preg_match('/^[\s.,:;()\-+\/]*$/u', $withoutUnits) === 1;
    }

    private function is_url_email_or_identifier(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL) || filter_var($value, FILTER_VALIDATE_EMAIL)) { return true; }
        if (preg_match('/^(?:[a-z][a-z0-9+.-]*:\/\/|www\.)\S+$/i', $value)) { return true; }
        if (preg_match('/^[A-Z0-9_.:\/-]+$/', $value) && preg_match('/[A-Z0-9]/', $value)) { return true; }
        if (preg_match('/^[a-z0-9_.-]+(?:\/[a-z0-9_.-]+)+$/i', $value)) { return true; }
        return false;
    }

    private function remove_placeholders(string $value): string
    {
        return preg_replace([
            '/(?<!%)%(?:\d+\$)?[-+0 #\']*(?:\d+|\*)?(?:\.(?:\d+|\*))?[bcdeEfFgGosuxX]/',
            '/\{\{\s*[a-zA-Z0-9_.-]+\s*\}\}/',
            '/(?<!\{)\{\s*[a-zA-Z0-9_.-]+\s*\}(?!\})/',
            '/\[\s*[a-zA-Z0-9_.-]+\s*\]/',
        ], ' ', $value) ?? $value;
    }

    private function contains_no_letters(string $value): bool
    {
        return preg_match('/\p{L}/u', $value) !== 1;
    }

    private function words(string $value): array
    {
        preg_match_all('/\p{L}[\p{L}\p{M}\'-]*/u', $value, $matches);
        return array_map(fn($word) => $this->lower((string)$word), $matches[0] ?? []);
    }

    private function plain(string $value): string
    {
        $value = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($value) : strip_tags($value);
        return trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
