<?php

declare(strict_types=1);

namespace Slotera\Application\BookingModeConfiguration;

if (!defined('ABSPATH')) {
    exit;
}

class FixedBookingModeHandler extends AbstractBookingModeHandler
{
    public function mode(): string
    {
        return 'fixed';
    }

    public function sanitize_config(array $common_config, array $source): array
    {
        return $common_config;
    }

    public function package_fields(array $config): array
    {
        return [
            'show_duration_frontend' => !empty($config['show_duration']) ? 1 : 0,
            'max_bookings_per_slot' => max(1, (int) ($config['capacity_total'] ?? ($config['max_bookings_per_slot'] ?? 1))),
            'price_unit' => (string) ($config['price_unit'] ?? 'fixed'),
        ];
    }
}
