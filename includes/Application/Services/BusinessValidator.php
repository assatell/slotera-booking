<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Central business-range validation for admin-saved values.
 *
 * Keep these ranges intentionally conservative: invalid values are normalized
 * to safe defaults instead of being written to package/event/settings records.
 */
final class BusinessValidator
{
    public static function date($value, string $default = '', bool $allow_empty = true): string
    {
        $value = sanitize_text_field(trim((string) $value));
        if ($value === '') {
            return $allow_empty ? '' : $default;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return $default;
        }

        return $value;
    }

    public static function date_or_today($value): string
    {
        $today = current_time('Y-m-d');
        return self::date($value, $today, false);
    }

    public static function time($value, string $default = '00:00'): string
    {
        $value = sanitize_text_field(trim((string) $value));
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $matches) !== 1) {
            return $default;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];
        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return $default;
        }

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public static function money($value, float $default = 0.0, float $min = 0.0, float $max = 1000000.0): float
    {
        $raw = is_string($value) ? str_replace(',', '.', trim($value)) : $value;
        if (!is_numeric($raw)) {
            return $default;
        }

        $amount = round((float) $raw, 2);
        return max($min, min($max, $amount));
    }

    public static function percent($value, float $default = 0.0): float
    {
        return self::money($value, $default, 0.0, 100.0);
    }

    public static function duration_minutes($value, int $default = 0, int $min = 0, int $max = 1440): int
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, absint($value)));
    }

    public static function duration_from_hours_minutes($hours, $minutes, int $default = 0, int $min = 0, int $max = 1440): int
    {
        $total = (absint($hours) * 60) + min(59, absint($minutes));
        return self::duration_minutes($total, $default, $min, $max);
    }

    public static function capacity($value, int $default = 1, int $min = 1, int $max = 999): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, absint($value)));
    }
}
