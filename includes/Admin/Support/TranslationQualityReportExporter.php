<?php

declare(strict_types=1);

namespace Slotera\Admin\Support;

if (!defined('ABSPATH')) { exit; }

final class TranslationQualityReportExporter
{
    public function output(string $format, array $report): void
    {
        nocache_headers();

        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename=slotera-translation-report.json');
            echo wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=slotera-translation-report.csv');
        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Unable to create the translation report export.', 'slotera-booking'));
        }

        fputcsv($output, ['Area', 'Locale', 'Key', 'Type', 'Severity', 'Issue', 'Source', 'Value', 'Rule', 'Confidence', 'Suggestion']);
        foreach ($report as $area => $section) {
            foreach ((array) ($section['details'] ?? []) as $detail) {
                fputcsv($output, [
                    $area,
                    $detail['locale'] ?? '',
                    $detail['key'] ?? '',
                    $detail['type'] ?? '',
                    $detail['severity'] ?? '',
                    $detail['issue'] ?? '',
                    $detail['source'] ?? '',
                    $detail['value'] ?? '',
                    $detail['context']['rule'] ?? '',
                    $detail['context']['confidence'] ?? '',
                    $detail['context']['suggestion'] ?? '',
                ]);
            }
        }
        fclose($output);
        exit;
    }
}
