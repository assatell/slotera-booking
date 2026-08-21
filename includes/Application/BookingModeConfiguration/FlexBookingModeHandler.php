<?php

declare(strict_types=1);

namespace Slotera\Application\BookingModeConfiguration;

if (!defined('ABSPATH')) {
    exit;
}

final class FlexBookingModeHandler extends FixedBookingModeHandler
{
    public function mode(): string
    {
        return 'flex';
    }

    public function sanitize_config(array $common_config, array $source): array
    {
        $common_config['display_start_time_only'] = !empty($source['display_start_time_only']) ? 1 : 0;
        return $common_config;
    }
}
