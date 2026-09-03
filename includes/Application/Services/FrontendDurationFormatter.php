<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}

/** Canonical localized duration formatting for customer-facing views. */
final class FrontendDurationFormatter
{
    public static function format(int $minutes, ?string $locale = null): string
    {
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60);
        $remaining_minutes = $minutes % 60;

        if ($hours > 0 && $remaining_minutes > 0) {
            return sprintf(sltr_t('%dh %dmin', 'frontend', $locale), $hours, $remaining_minutes);
        }

        if ($hours > 0) {
            return sprintf(sltr_t('%dh', 'frontend', $locale), $hours);
        }

        return sprintf(sltr_t('%dmin', 'frontend', $locale), $remaining_minutes);
    }
}
