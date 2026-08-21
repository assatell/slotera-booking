<?php

declare(strict_types=1);

namespace Slotera\Application\BookingModeConfiguration;

use Slotera\Application\Services\BusinessValidator;

if (!defined('ABSPATH')) {
    exit;
}

final class DateRangeInventoryBookingModeHandler extends AbstractBookingModeHandler
{
    public function mode(): string
    {
        return 'date_range_inventory';
    }

    public function sanitize_config(array $common_config, array $source): array
    {
        return array_merge($common_config, [
            'price_unit' => $this->sanitize_price_unit((string) ($source['price_unit'] ?? 'fixed')),
            'hourly_price' => BusinessValidator::money($source['hourly_price'] ?? 0),
            'checkin_time' => BusinessValidator::time($source['checkin_time'] ?? '15:00', '15:00'),
            'checkout_time' => BusinessValidator::time($source['checkout_time'] ?? '11:00', '11:00'),
            'min_nights' => BusinessValidator::capacity($source['min_nights'] ?? 1, 1, 1, 365),
            'max_nights' => BusinessValidator::capacity($source['max_nights'] ?? 30, 30, 1, 365),
            'inventory_units_json' => $this->inventory_units_json_from_source($source),
            'date_inventory_json' => $this->date_inventory_json_from_source($source),
            'date_flow' => $this->sanitize_date_flow((string) ($source['date_flow'] ?? 'customer_choice')),
            'scheduled_events_json' => wp_json_encode($this->sanitize_scheduled_events((array) ($source['scheduled_events'] ?? []))),
            'included_services' => sanitize_textarea_field((string) ($source['included_services'] ?? '')),
            'extra_services_json' => $this->extra_services_json_from_source($source),
        ]);
    }

    public function package_fields(array $config): array
    {
        return [
            'show_duration_frontend' => 0,
            'max_bookings_per_slot' => $this->max_date_range_capacity($config),
            'price_unit' => (string) ($config['price_unit'] ?? 'fixed'),
        ];
    }

    private function inventory_units_json_from_source(array $source): string
    {
        if (!isset($source['inventory_units']) || !is_array($source['inventory_units'])) {
            return isset($source['inventory_units_json']) ? (string) $source['inventory_units_json'] : '';
        }

        $clean = [];
        $next_id = 1;
        foreach ($source['inventory_units'] as $unit) {
            if (!is_array($unit)) {
                continue;
            }
            $id = max(1, absint($unit['id'] ?? 0));
            if ($id <= 0) {
                $id = $next_id;
            }
            $next_id = max($next_id, $id + 1);
            $active = $this->bool_from_source($unit, array_key_exists('active_checked', $unit) ? 'active_checked' : 'active') ? 1 : 0;
            $name = sanitize_text_field((string) ($unit['name'] ?? ''));
            if ($name === '' && $active === 0) {
                $name = sprintf('Unit %d', $id);
            }
            if ($name === '') {
                continue;
            }
            $clean[] = [
                'id' => $id,
                'name' => $name,
                'description' => sanitize_text_field((string) ($unit['description'] ?? '')),
                'capacity' => BusinessValidator::capacity($unit['capacity'] ?? 1),
                'price' => BusinessValidator::money($unit['price'] ?? 0),
                'hourly_price' => BusinessValidator::money($unit['hourly_price'] ?? 0),
                'active' => $active,
            ];
        }

        return wp_json_encode(array_values($clean));
    }

    private function date_inventory_json_from_source(array $source): string
    {
        if (!isset($source['date_inventory_overrides']) || !is_array($source['date_inventory_overrides'])) {
            return isset($source['date_inventory_json']) ? (string) $source['date_inventory_json'] : '';
        }

        $periods = [];
        foreach ($source['date_inventory_overrides'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $start = BusinessValidator::date($row['start_date'] ?? '');
            $end = BusinessValidator::date($row['end_date'] ?? $start);
            if (!$this->valid_date($start)) {
                continue;
            }
            if (!$this->valid_date($end) || $end < $start) {
                $end = $start;
            }
            $periods[] = [
                'start_date' => $start,
                'end_date' => $end,
                'capacity' => BusinessValidator::capacity($row['capacity'] ?? 0, 0, 0),
                'price' => BusinessValidator::money($row['price'] ?? 0),
                'closed' => !empty($row['closed']) ? 1 : 0,
            ];
        }

        return wp_json_encode(['periods' => array_values($periods)]);
    }

    private function sanitize_date_flow(string $value): string
    {
        $value = sanitize_key($value);
        return in_array($value, ['customer_choice', 'admin_scheduled'], true) ? $value : 'customer_choice';
    }

    private function sanitize_scheduled_events(array $events): array
    {
        $clean = [];
        $next_id = 1;
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }
            $start_date = BusinessValidator::date($event['start_date'] ?? '');
            $end_date = BusinessValidator::date($event['end_date'] ?? '');
            if (!$this->valid_date($start_date) || !$this->valid_date($end_date) || $end_date < $start_date) {
                continue;
            }
            $id = max(1, absint($event['id'] ?? 0));
            if ($id <= 0) {
                $id = $next_id;
            }
            $next_id = max($next_id, $id + 1);
            $use_time = !empty($event['use_time']) ? 1 : 0;
            $clean[] = [
                'id' => $id,
                'title' => sanitize_text_field((string) ($event['title'] ?? '')),
                'start_date' => $start_date,
                'start_time' => $use_time ? BusinessValidator::time($event['start_time'] ?? '00:00', '00:00') : '',
                'end_date' => $end_date,
                'end_time' => $use_time ? BusinessValidator::time($event['end_time'] ?? '23:59', '23:59') : '',
                'use_time' => $use_time,
                'seats' => BusinessValidator::capacity($event['seats'] ?? 1),
                'price' => BusinessValidator::money($event['price'] ?? 0),
            ];
        }

        return array_values($clean);
    }

    private function max_date_range_capacity(array $config): int
    {
        $max = max(1, (int) ($config['max_bookings_per_slot'] ?? 1));
        $events = json_decode((string) ($config['scheduled_events_json'] ?? ''), true);
        if (is_array($events)) {
            foreach ($events as $event) {
                $max = max($max, (int) ($event['seats'] ?? 1));
            }
        }
        $units = json_decode((string) ($config['inventory_units_json'] ?? ''), true);
        if (is_array($units)) {
            foreach ($units as $unit) {
                $max = max($max, (int) ($unit['capacity'] ?? 1));
            }
        }
        return $max;
    }

    private function normalize_time(string $time): string
    {
        $time = trim($time);
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            return '00:00';
        }
        return sprintf('%02d:%02d', max(0, min(23, (int) $matches[1])), max(0, min(59, (int) $matches[2])));
    }

    private function valid_date(string $date): bool
    {
        return BusinessValidator::date($date) !== '';
    }
}
