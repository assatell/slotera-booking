<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

/** Normalize saved price-summary sections so the heading is conditional too. */
final class Version_1_0_711 implements MigrationInterface
{
    public static function apply(): void
    {
        $settings = get_option(SettingsRepository::OPTION_NAME, []);
        if (!is_array($settings)) {
            return;
        }

        $changed = false;
        foreach ($settings as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }
            if (strpos($key, 'email_template_') !== 0 || !preg_match('/_(?:body|html_body)$/', $key)) {
                continue;
            }
            if (strpos($value, '{price_summary}') === false) {
                continue;
            }

            $normalized = self::normalize_price_summary_section($value);
            if ($normalized !== $value) {
                $settings[$key] = $normalized;
                $changed = true;
            }
        }

        if ($changed) {
            update_option(SettingsRepository::OPTION_NAME, $settings, false);
        }

        update_option('sltr_conditional_email_price_summary_migration_10711', [
            'completed_at' => current_time('mysql'),
            'changed' => $changed ? 1 : 0,
        ], false);
    }

    private static function normalize_price_summary_section(string $value): string
    {
        $value = preg_replace(
            '/(^|\R)([^\r\n<>]{1,160}:)[ \t]*\R[ \t]*\{price_summary\}/u',
            '$1$2 {price_summary}',
            $value
        ) ?? $value;

        $value = preg_replace_callback(
            '/<(p|div|h[1-6])\b([^>]*)>(.*?)\s*:\s*<\/\1>\s*<(p|div)\b([^>]*)>\s*\{price_summary\}\s*<\/\4>/isu',
            static function (array $match): string {
                return '<' . $match[1] . $match[2] . '>' . $match[3] . ': {price_summary}</' . $match[1] . '>';
            },
            $value
        ) ?? $value;

        return $value;
    }
}
