<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\EventRepository;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class DateRangeInventoryService
{
    private BookingRepository $bookings;

    public function __construct(?BookingRepository $bookings = null)
    {
        $this->bookings = $bookings ?: new BookingRepository();
    }

    public function is_date_range_mode(array $package): bool
    {
        return sanitize_key((string) ($package['booking_mode'] ?? '')) === 'date_range_inventory';
    }



    public function date_flow(array $package): string
    {
        $flow = sanitize_key((string) ($package['date_flow'] ?? ''));
        if ($flow === '') {
            $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
            $flow = is_array($configs) ? sanitize_key((string) ($configs['date_range_inventory']['date_flow'] ?? '')) : '';
        }
        return in_array($flow, ['customer_choice', 'admin_scheduled'], true) ? $flow : 'customer_choice';
    }

    public function is_admin_scheduled(array $package): bool
    {
        return $this->date_flow($package) === 'admin_scheduled';
    }

    public function scheduled_events(array $package): array
    {
        $package_id = (int) ($package['id'] ?? 0);
        if ($package_id > 0) {
            $repository_events = (new EventRepository())->get_active_upcoming($package_id);
            if (!empty($repository_events)) {
                $events = [];
                foreach ($repository_events as $event) {
                    $date = sanitize_text_field((string) ($event['event_date'] ?? ''));
                    if (!$this->is_valid_date($date)) { continue; }
                    $events[] = [
                        'id' => max(1, (int) ($event['id'] ?? 0)),
                        'title' => sanitize_text_field((string) ($event['title'] ?? '')),
                        'start_date' => $date,
                        'start_time' => $this->normalize_time((string) ($event['start_time'] ?? '00:00')),
                        'end_date' => sanitize_text_field((string) ($event['end_date'] ?? $date)),
                        'end_time' => $this->normalize_time((string) ($event['end_time'] ?? '23:59')),
                        'use_time' => !empty($event['use_time']) ? 1 : 0,
                        'seats' => max(1, (int) ($event['capacity'] ?? 1)),
                        'price' => max(0, (float) ($event['price_override'] ?? 0)),
                        'discount_type' => sanitize_key((string) ($event['discount_type'] ?? 'none')),
                        'discount_value' => max(0, (float) ($event['discount_value'] ?? 0)),
                        'allow_coupons' => !empty($event['allow_coupons']) ? 1 : 0,
                        'payment_policy' => sanitize_key((string) ($event['payment_policy'] ?? 'booking_only')),
                        'deposit_type' => sanitize_key((string) ($event['deposit_type'] ?? 'percent')),
                        'deposit_value' => max(0, (float) ($event['deposit_value'] ?? 30)),
                        'location' => sanitize_text_field((string) ($event['location'] ?? '')),
                        'timezone' => sanitize_text_field((string) ($event['timezone'] ?? wp_timezone_string())),
                    ];
                }
                usort($events, static fn($a, $b): int => strcmp($a['start_date'] . $a['start_time'], $b['start_date'] . $b['start_time']));
                return $events;
            }
        }

        $json = (string) ($package['scheduled_events_json'] ?? '');
        if ($json === '') {
            $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
            if (is_array($configs)) { $json = (string) ($configs['date_range_inventory']['scheduled_events_json'] ?? ''); }
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) { return []; }
        $today = (new \DateTimeImmutable('today', wp_timezone()))->format('Y-m-d');
        $events = [];
        foreach ($decoded as $event) {
            if (!is_array($event)) { continue; }
            $start_date = sanitize_text_field((string) ($event['start_date'] ?? ''));
            $end_date = sanitize_text_field((string) ($event['end_date'] ?? ''));
            if (!$this->is_valid_date($start_date) || !$this->is_valid_date($end_date) || $end_date < $start_date || $start_date < $today) { continue; }
            $events[] = [
                'id' => max(1, (int) ($event['id'] ?? 0)),
                'title' => sanitize_text_field((string) ($event['title'] ?? '')),
                'start_date' => $start_date,
                'start_time' => !empty($event['use_time']) ? $this->normalize_time((string) ($event['start_time'] ?? '00:00')) : '',
                'end_date' => $end_date,
                'end_time' => !empty($event['use_time']) ? $this->normalize_time((string) ($event['end_time'] ?? '23:59')) : '',
                'use_time' => !empty($event['use_time']) ? 1 : 0,
                'seats' => max(1, (int) ($event['seats'] ?? 1)),
                'price' => max(0, (float) ($event['price'] ?? ($package['price'] ?? 0))),
            ];
        }
        usort($events, static fn($a, $b): int => strcmp($a['start_date'] . $a['start_time'], $b['start_date'] . $b['start_time']));
        return $events;
    }

    public function scheduled_event_options(array $package): array
    {
        $package_id = (int) ($package['id'] ?? 0);
        $options = [];
        foreach ($this->scheduled_events($package) as $event) {
            $booked = count($this->bookings->get_overlapping_date_range($package_id, (int) $event['id'], $event['start_date'], $this->exclusive_end_date_for_event($event)));
            $left = max(0, (int) $event['seats'] - $booked);
            if ($left <= 0) { continue; }
            $quote = $this->scheduled_event_quote($package, (int) $event['id'], []);
            $event['seats_left'] = $left;
            $event['quote'] = is_wp_error($quote) ? [] : $quote;
            $options[] = $event;
        }
        return $options;
    }

    public function find_scheduled_event(array $package, int $event_id): ?array
    {
        foreach ($this->scheduled_events($package) as $event) {
            if ((int) $event['id'] === $event_id) { return $event; }
        }
        return null;
    }

    public function scheduled_event_available(array $package, int $event_id): bool
    {
        $event = $this->find_scheduled_event($package, $event_id);
        if (!$event) { return false; }
        $booked = count($this->bookings->get_overlapping_date_range((int) ($package['id'] ?? 0), $event_id, $event['start_date'], $this->exclusive_end_date_for_event($event)));
        return $booked < (int) $event['seats'];
    }

    public function scheduled_event_quote(array $package, int $event_id, array $extra_ids)
    {
        $event = $this->find_scheduled_event($package, $event_id);
        if (!$event) { return new WP_Error('sltr_event_not_found', __('Selected event is not available.', 'slotera-booking')); }
        $start_date = (string) $event['start_date'];
        $end_date = (string) $event['end_date'];
        $nights = $this->nights($start_date, $this->exclusive_end_date_for_event($event));
        $days = max(1, $nights + 1);
        $base = max(0, (float) ($event['price'] ?: ($package['price'] ?? 0)));
        $original_base = $base;
        $discount_type = sanitize_key((string) ($event['discount_type'] ?? 'none'));
        $discount_value = max(0, (float) ($event['discount_value'] ?? 0));
        if ($discount_type === 'percent' && $discount_value > 0) {
            $base = round(max(0, $base - ($base * min(100, $discount_value) / 100)), 2);
        } elseif ($discount_type === 'fixed' && $discount_value > 0) {
            $base = round(max(0, $base - $discount_value), 2);
        }
        $selected_extras = [];
        $extras_amount = 0.0;
        foreach ($this->extras($package) as $extra) {
            if (!in_array((int) $extra['id'], array_map('intval', $extra_ids), true)) { continue; }
            $qty = 1;
            if ($extra['price_type'] === 'per_day') { $qty = $days; }
            if ($extra['price_type'] === 'per_night') { $qty = max(1, $nights); }
            $line = round((float) $extra['price'] * $qty, 2);
            $extra['quantity'] = $qty;
            $extra['amount'] = $line;
            $selected_extras[] = $extra;
            $extras_amount += $line;
        }
        $total = round($base + $extras_amount, 2);
        return ['unit' => ['id' => $event_id, 'name' => $event['title'] ?: __('Scheduled event', 'slotera-booking'), 'capacity' => (int) $event['seats'], 'price' => $base], 'event' => $event, 'start_date' => $start_date, 'end_date' => $end_date, 'nights' => $nights, 'days' => $days, 'price_unit' => 'fixed', 'original_base_amount' => round($original_base, 2), 'base_amount' => round($base, 2), 'extras_amount' => round($extras_amount, 2), 'total_amount' => $total, 'deposit_amount' => $this->deposit_amount(array_merge($package, ['payment_policy' => (string) ($event['payment_policy'] ?? 'booking_only'), 'deposit_type' => (string) ($event['deposit_type'] ?? 'percent'), 'deposit_value' => (float) ($event['deposit_value'] ?? 30)]), $total), 'selected_extras' => $selected_extras, 'allow_coupons' => !empty($event['allow_coupons']) ? 1 : 0, 'discount_type' => $discount_type, 'discount_value' => $discount_value];
    }

    public function exclusive_end_date_for_event(array $event): string
    {
        $end_date = (string) ($event['end_date'] ?? '');
        if (!$this->is_valid_date($end_date)) { return $end_date; }
        return (new \DateTimeImmutable($end_date, wp_timezone()))->modify('+1 day')->format('Y-m-d');
    }

    public function units(array $package): array
    {
        $json = (string) ($package['inventory_units_json'] ?? '');
        if ($json === '') {
            $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
            if (is_array($configs)) {
                $json = (string) ($configs['date_range_inventory']['inventory_units_json'] ?? '');
            }
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || empty($decoded)) {
            return [[
                'id' => 1,
                'name' => __('Default unit', 'slotera-booking'),
                'capacity' => max(1, (int) ($package['max_bookings_per_slot'] ?? 1)),
                'price' => max(0, (float) ($package['price'] ?? 0)),
                'hourly_price' => max(0, (float) ($package['hourly_price'] ?? 0)),
            ]];
        }
        return array_values(array_filter(array_map(static function ($unit): array {
            return [
                'id' => max(1, (int) ($unit['id'] ?? 0)),
                'name' => sanitize_text_field((string) ($unit['name'] ?? '')),
                'description' => sanitize_text_field((string) ($unit['description'] ?? '')),
                'capacity' => max(1, (int) ($unit['capacity'] ?? 1)),
                'price' => max(0, (float) ($unit['price'] ?? 0)),
                'hourly_price' => max(0, (float) ($unit['hourly_price'] ?? 0)),
                'active' => array_key_exists('active', $unit) ? (in_array(strtolower(trim((string) (is_array($unit['active']) ? end($unit['active']) : $unit['active']))), ['1', 'true', 'yes', 'on'], true) ? 1 : 0) : 1,
            ];
        }, $decoded), static fn($unit): bool => $unit['id'] > 0 && $unit['name'] !== '' && !empty($unit['active'])));
    }

    public function extras(array $package): array
    {
        $json = (string) ($package['extra_services_json'] ?? '');
        if ($json === '') {
            $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
            if (is_array($configs)) {
                $json = (string) ($configs['date_range_inventory']['extra_services_json'] ?? '');
            }
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) { return []; }
        return array_values(array_filter(array_map(static function ($item): array {
            $type = sanitize_key((string) ($item['price_type'] ?? 'once'));
            if (!in_array($type, ['once', 'per_day', 'per_night', 'per_hour', 'per_guest'], true)) { $type = 'once'; }
            return ['id' => max(1, (int) ($item['id'] ?? 0)), 'name' => sanitize_text_field((string) ($item['name'] ?? '')), 'description' => sanitize_text_field((string) ($item['description'] ?? '')), 'price' => max(0, (float) ($item['price'] ?? 0)), 'price_type' => $type, 'active' => array_key_exists('active', $item) ? (in_array(strtolower(trim((string) (is_array($item['active']) ? end($item['active']) : $item['active']))), ['1', 'true', 'yes', 'on'], true) ? 1 : 0) : 1];
        }, $decoded), static fn($item): bool => $item['id'] > 0 && $item['name'] !== '' && !empty($item['active'])));
    }

    public function availability_for_range(array $package, string $start_date, string $end_date): array
    {
        $validation = $this->validate_range($package, $start_date, $end_date);
        if (is_wp_error($validation)) { return []; }
        $package_id = (int) ($package['id'] ?? 0);
        $units = $this->units($package);
        $available = [];
        foreach ($units as $unit) {
            if ($this->unit_capacity_available($package, (int) $unit['id'], $start_date, $end_date)) {
                $quote = $this->quote($package, (int) $unit['id'], $start_date, $end_date, []);
                $unit['quote'] = is_wp_error($quote) ? [] : $quote;
                $available[] = $unit;
            }
        }
        return $available;
    }

    public function unit_available(int $package_id, int $unit_id, string $start_date, string $end_date, int $exclude_booking_id = 0): bool
    {
        if ($package_id <= 0 || $unit_id <= 0 || !$this->is_valid_date($start_date) || !$this->is_valid_date($end_date) || $end_date <= $start_date) {
            return false;
        }
        foreach ($this->bookings->get_overlapping_date_range($package_id, $unit_id, $start_date, $end_date) as $booking) {
            if ($exclude_booking_id > 0 && (int) ($booking['id'] ?? 0) === $exclude_booking_id) { continue; }
            return false;
        }
        return true;
    }


    public function unit_capacity_available(array $package, int $unit_id, string $start_date, string $end_date, int $exclude_booking_id = 0): bool
    {
        $unit = $this->find_unit($package, $unit_id);
        if (!$unit) { return false; }
        $count = 0;
        foreach ($this->bookings->get_overlapping_date_range((int) ($package['id'] ?? 0), $unit_id, $start_date, $end_date) as $booking) {
            if ($exclude_booking_id > 0 && (int) ($booking['id'] ?? 0) === $exclude_booking_id) { continue; }
            $count++;
        }
        return $count < max(1, (int) ($unit['capacity'] ?? 1));
    }

    public function validate_range(array $package, string $start_date, string $end_date)
    {
        if (!$this->is_valid_date($start_date) || !$this->is_valid_date($end_date)) {
            return new WP_Error('sltr_invalid_date_range', __('Please choose valid check-in and check-out dates.', 'slotera-booking'));
        }
        $today = (new \DateTimeImmutable('today', wp_timezone()))->format('Y-m-d');
        if ($start_date < $today || $end_date <= $start_date) {
            return new WP_Error('sltr_invalid_date_range', __('Please choose a future date range.', 'slotera-booking'));
        }
        $nights = $this->nights($start_date, $end_date);
        $min = max(1, (int) ($package['min_nights'] ?? 1));
        $max = max($min, (int) ($package['max_nights'] ?? 30));
        if ($nights < $min || $nights > $max) {
            return new WP_Error('sltr_invalid_stay_length', sprintf(__('Stay length must be between %1$d and %2$d nights.', 'slotera-booking'), $min, $max));
        }
        return true;
    }

    public function quote(array $package, int $unit_id, string $start_date, string $end_date, array $extra_ids)
    {
        $validation = $this->validate_range($package, $start_date, $end_date);
        if (is_wp_error($validation)) { return $validation; }
        $unit = $this->find_unit($package, $unit_id);
        if (!$unit) { return new WP_Error('sltr_unit_not_found', __('Selected room is not available.', 'slotera-booking')); }
        $nights = $this->nights($start_date, $end_date);
        $days = $nights + 1;
        $price_unit = sanitize_key((string) ($package['price_unit'] ?? 'fixed'));
        $unit_price = (float) ($unit['price'] ?: ($package['price'] ?? 0));
        $hourly_price = (float) ($unit['hourly_price'] ?: ($package['hourly_price'] ?? 0));
        if ($price_unit === 'per_day') { $base = $unit_price * $days; }
        elseif ($price_unit === 'per_night') { $base = $unit_price * $nights; }
        elseif ($price_unit === 'per_hour') { $base = $hourly_price * max(1, (int) ($package['duration_minutes'] ?? 60) / 60) * $days; }
        else { $base = $unit_price; }
        $selected_extras = [];
        $extras_amount = 0.0;
        foreach ($this->extras($package) as $extra) {
            if (!in_array((int) $extra['id'], array_map('intval', $extra_ids), true)) { continue; }
            $qty = 1;
            if ($extra['price_type'] === 'per_day') { $qty = $days; }
            if ($extra['price_type'] === 'per_night') { $qty = $nights; }
            if ($extra['price_type'] === 'per_hour') { $qty = max(1, (int) ($package['duration_minutes'] ?? 60) / 60) * $days; }
            $line = round((float) $extra['price'] * $qty, 2);
            $extra['quantity'] = $qty;
            $extra['amount'] = $line;
            $selected_extras[] = $extra;
            $extras_amount += $line;
        }
        $total = round(max(0, $base) + $extras_amount, 2);
        $deposit = $this->deposit_amount($package, $total);
        return [
            'unit' => $unit,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'nights' => $nights,
            'days' => $days,
            'price_unit' => $price_unit,
            'base_amount' => round(max(0, $base), 2),
            'extras_amount' => round($extras_amount, 2),
            'total_amount' => $total,
            'deposit_amount' => $deposit,
            'selected_extras' => $selected_extras,
            // Ordinary date-range inventory has no scheduled-event discount
            // contract. Package sales/coupons are applied by the booking mode
            // handler, so return explicit, deterministic defaults here.
            'allow_coupons' => 1,
            'discount_type' => 'none',
            'discount_value' => 0.0,
        ];
    }

    public function payment_requirement_for_choice(array $package, string $choice): array
    {
        $policy = sanitize_key((string) ($package['payment_policy'] ?? 'booking_only'));
        if ($policy === 'full_or_deposit') { $policy = in_array($choice, ['full_payment', 'deposit_payment'], true) ? $choice : 'full_payment'; }
        return ['policy' => $policy, 'requires_payment' => in_array($policy, ['full_payment', 'deposit_payment'], true), 'payment_mode' => $policy === 'deposit_payment' ? 'prepay' : ($policy === 'full_payment' ? 'payment' : 'none')];
    }

    private function find_unit(array $package, int $unit_id): ?array
    {
        foreach ($this->units($package) as $unit) { if ((int) $unit['id'] === $unit_id) { return $unit; } }
        return null;
    }

    private function deposit_amount(array $package, float $total): float
    {
        $type = sanitize_key((string) ($package['deposit_type'] ?? 'percent'));
        $value = max(0, (float) ($package['deposit_value'] ?? 0));
        return round($type === 'fixed' ? min($total, $value) : min($total, $total * min(100, $value) / 100), 2);
    }

    private function nights(string $start_date, string $end_date): int
    {
        return max(0, (int) (new \DateTimeImmutable($start_date, wp_timezone()))->diff(new \DateTimeImmutable($end_date, wp_timezone()))->days);
    }

    private function normalize_time(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^\d{1,2}:\d{2}$/', $time) === 1) { $time .= ':00'; }
        if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d:[0-5]\d$/', $time) !== 1) { return ''; }
        [$h, $m, $sec] = array_map('intval', explode(':', $time));
        return sprintf('%02d:%02d:%02d', $h, $m, $sec);
    }

    private function is_valid_date(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
    }
}
