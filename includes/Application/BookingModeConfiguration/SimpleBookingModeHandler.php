<?php

declare(strict_types=1);

namespace Slotera\Application\BookingModeConfiguration;

use Slotera\Application\Services\BusinessValidator;

if (!defined('ABSPATH')) {
    exit;
}

final class SimpleBookingModeHandler extends AbstractBookingModeHandler
{
    public function mode(): string
    {
        return 'simple';
    }

    public function sanitize_config(array $common_config, array $source): array
    {
        $price_mode = sanitize_key((string) ($source['price_mode'] ?? 'fixed'));
        if (!in_array($price_mode, ['fixed', 'from', 'request'], true)) {
            $price_mode = 'fixed';
        }
        $capacity_type = sanitize_key((string) ($source['capacity_type'] ?? 'unlimited'));
        if (!in_array($capacity_type, ['unlimited', 'limited'], true)) {
            $capacity_type = 'unlimited';
        }

        return array_merge($common_config, [
            'price_mode' => $price_mode,
            'capacity_type' => $capacity_type,
            'capacity_total' => BusinessValidator::capacity($source['capacity_total'] ?? 1),
            'confirm_immediately' => $this->bool_from_source($source, 'confirm_immediately') ? 1 : 0,
            'included_services' => sanitize_textarea_field((string) ($source['included_services'] ?? '')),
            'extra_services_json' => $this->extra_services_json_from_source($source),
        ]);
    }

    public function package_fields(array $config): array
    {
        return [
            'show_duration_frontend' => 0,
            'max_bookings_per_slot' => (string) ($config['capacity_type'] ?? 'unlimited') === 'unlimited' ? 999999 : max(1, (int) ($config['capacity_total'] ?? 1)),
            'price_unit' => (string) ($config['price_mode'] ?? 'fixed'),
        ];
    }
}
