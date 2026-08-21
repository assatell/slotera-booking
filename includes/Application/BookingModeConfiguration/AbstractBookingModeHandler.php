<?php

declare(strict_types=1);

namespace Slotera\Application\BookingModeConfiguration;

use Slotera\Application\Services\BusinessValidator;

if (!defined('ABSPATH')) {
    exit;
}

abstract class AbstractBookingModeHandler implements BookingModeHandlerInterface
{
    protected function bool_from_source(array $source, string $key): bool
    {
        if (!array_key_exists($key, $source)) {
            return false;
        }
        $value = $source[$key];
        if (is_bool($value)) {
            return $value;
        }
        if (is_array($value)) {
            $value = end($value);
        }
        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    protected function sanitize_price_unit(string $value): string
    {
        $value = sanitize_key($value);
        return in_array($value, ['fixed', 'per_day', 'per_night', 'per_hour'], true) ? $value : 'fixed';
    }

    protected function extra_services_json_from_source(array $source): string
    {
        if (!isset($source['extra_services']) || !is_array($source['extra_services'])) {
            return isset($source['extra_services_json']) ? (string) $source['extra_services_json'] : '';
        }

        $clean = [];
        $next_id = 1;
        foreach ($source['extra_services'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = max(1, absint($item['id'] ?? 0));
            if ($id <= 0) {
                $id = $next_id;
            }
            $next_id = max($next_id, $id + 1);
            $active = $this->bool_from_source($item, 'active') ? 1 : 0;
            $name = sanitize_text_field((string) ($item['name'] ?? ''));
            if ($name === '' && $active === 0) {
                $name = sprintf('Extra service %d', $id);
            }
            if ($name === '') {
                continue;
            }
            $type = sanitize_key((string) ($item['price_type'] ?? 'once'));
            if (!in_array($type, ['once', 'per_day', 'per_night', 'per_hour', 'per_guest'], true)) {
                $type = 'once';
            }
            $clean[] = [
                'id' => $id,
                'name' => $name,
                'description' => sanitize_text_field((string) ($item['description'] ?? '')),
                'price' => BusinessValidator::money($item['price'] ?? 0),
                'price_type' => $type,
                'active' => $active,
            ];
        }

        return wp_json_encode(array_values($clean));
    }
}
