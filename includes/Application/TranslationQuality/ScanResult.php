<?php

declare(strict_types=1);

namespace Slotera\Application\TranslationQuality;

if (!defined('ABSPATH')) { exit; }

final class ScanResult
{
    public const SCHEMA_VERSION = '2.2.0';

    private string $section;
    private int $sourceStrings;
    private int $items;
    private int $translatedStrings;
    private int $validStrings;
    private int $locales;
    private array $scores;
    private array $issues;
    private array $severity;
    private string $status;
    private array $details;
    private array $localeReports;

    public function __construct(string $section, int $sourceStrings, int $items, int $translatedStrings, int $validStrings, int $locales, array $scores, array $issues, array $severity, string $status, array $details, array $localeReports)
    {
        $this->section = $section;
        $this->sourceStrings = $sourceStrings;
        $this->items = $items;
        $this->translatedStrings = $translatedStrings;
        $this->validStrings = $validStrings;
        $this->locales = $locales;
        $this->scores = $scores;
        $this->issues = $issues;
        $this->severity = $severity;
        $this->status = $status;
        $this->details = $details;
        $this->localeReports = $localeReports;
    }

    public function to_array(): array
    {
        $critical = (int)($this->severity['critical'] ?? 0);
        $warnings = (int)($this->severity['warning'] ?? 0);
        $completion = (float)($this->scores['completion'] ?? 100.0);
        $quality = (float)($this->scores['quality'] ?? 100.0);

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'section' => $this->section,
            'totals' => [
                'source_strings' => $this->sourceStrings,
                'translated_strings' => $this->translatedStrings,
                'valid_strings' => $this->validStrings,
                'locales' => $this->locales,
            ],
            'scores' => $this->scores,
            'issues' => $this->issues,
            'severity' => $this->severity,
            'status' => $this->status,
            'details' => $this->details,
            'locale_reports' => $this->localeReports,

            // Backward-compatible v1 diagnostics fields.
            'items' => $this->items,
            'locales' => $this->locales,
            'coverage' => $completion,
            'coverage_status' => $completion >= 99.9 ? 'Complete' : ($completion >= 95.0 ? 'Nearly complete' : 'In progress'),
            'quality_status' => $critical > 0 ? 'Critical' : ($warnings > 0 ? 'Warning' : 'OK'),
            'critical' => $critical,
            'warnings' => $warnings,
            'notices' => (int)($this->severity['notice'] ?? 0),
            'translation_completion' => $completion,
            'quality' => $quality,
            'release_readiness' => (float)($this->scores['readiness'] ?? 100.0),
        ];
    }
}
