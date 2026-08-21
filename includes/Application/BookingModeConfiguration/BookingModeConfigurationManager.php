<?php

declare(strict_types=1);

namespace Slotera\Application\BookingModeConfiguration;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\BusinessValidator;
use Slotera\Infrastructure\Repositories\PackageRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class BookingModeConfigurationManager
{
    public const MODES = ['simple', 'fixed', 'flex', 'date_range_inventory'];

    private RequestValidator $request;
    private PackageRepository $packages;

    /** @var array<string,BookingModeHandlerInterface> */
    private array $handlers = [];

    public function __construct(RequestValidator $request, PackageRepository $packages)
    {
        $this->request = $request;
        $this->packages = $packages;
        foreach ([
            new SimpleBookingModeHandler(),
            new FixedBookingModeHandler(),
            new FlexBookingModeHandler(),
            new DateRangeInventoryBookingModeHandler(),
        ] as $handler) {
            $this->handlers[$handler->mode()] = $handler;
        }
    }

    public function normalize_mode(string $mode): string
    {
        $mode = sanitize_key($mode);
        if ($mode === 'flexible') {
            $mode = 'flex';
        }
        return isset($this->handlers[$mode]) ? $mode : 'simple';
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function collect_mode_configs(string $active_mode): array
    {
        $active_mode = $this->normalize_mode($active_mode);
        $posted_raw = $this->request->post_raw_array('mode_config');
        $raw = $posted_raw;
        $compact = $this->compact_mode_config_from_post();
        if (!empty($compact)) {
            $raw = array_replace_recursive($raw, $compact);
            foreach (self::MODES as $mode) {
                if (!isset($posted_raw[$mode]) || !is_array($posted_raw[$mode])) {
                    continue;
                }
                foreach (['low_availability_notice_enabled', 'low_availability_notice_enabled_checked', 'low_availability_threshold', 'confirm_immediately'] as $key) {
                    if (array_key_exists($key, $posted_raw[$mode])) {
                        if (!isset($raw[$mode]) || !is_array($raw[$mode])) {
                            $raw[$mode] = [];
                        }
                        $raw[$mode][$key] = $posted_raw[$mode][$key];
                    }
                }
            }
        }

        if (empty($raw)) {
            $existing_id = $this->request->post_int('id');
            if ($existing_id > 0) {
                $existing = $this->packages->get_by_id($existing_id);
                $existing_configs = json_decode((string) ($existing['mode_configs_json'] ?? ''), true);
                if (is_array($existing_configs) && !empty($existing_configs)) {
                    $raw = $existing_configs;
                }
            }
        }

        $configs = [];
        foreach ($this->handlers as $mode => $handler) {
            $source = isset($raw[$mode]) && is_array($raw[$mode]) ? $raw[$mode] : [];
            $configs[$mode] = $handler->sanitize_config($this->sanitize_common_config($mode, $source), $source);
        }

        $posted_confirm_immediately = $this->posted_confirm_immediately_simple();
        $posted_simple_price_mode = $this->posted_simple_price_mode();
        if ($posted_confirm_immediately !== null) {
            $configs['simple']['confirm_immediately'] = $posted_confirm_immediately ? 1 : 0;
        }
        if ($posted_simple_price_mode !== null) {
            $configs['simple']['price_mode'] = $posted_simple_price_mode;
        }

        if (empty($raw[$active_mode])) {
            $configs[$active_mode] = array_merge($configs[$active_mode], [
                'duration_minutes' => BusinessValidator::duration_minutes($this->post_duration_minutes('duration', 60), 60, 1, 1440),
                'slot_step' => BusinessValidator::duration_minutes($this->post_duration_minutes('slot_step', 60), 60, 1, 1440),
                'max_bookings_per_slot' => BusinessValidator::capacity($this->request->post_int('max_bookings_per_slot', 1)),
                'price' => BusinessValidator::money($this->request->post_float('price')),
                'discount_type' => $this->request->post_key('discount_type', 'none'),
                'discount_value' => BusinessValidator::money($this->request->post_float('discount_value')),
                'payment_policy' => $this->request->post_key('payment_policy', 'booking_only'),
                'deposit_type' => $this->request->post_key('deposit_type', 'percent'),
                'deposit_value' => BusinessValidator::money($this->request->post_float('deposit_value')),
                'unpaid_booking_status' => $this->request->post_key('unpaid_booking_status', 'confirmed'),
            ]);
        }

        if ($posted_confirm_immediately !== null) {
            $configs['simple']['confirm_immediately'] = $posted_confirm_immediately ? 1 : 0;
        }
        if ($posted_simple_price_mode !== null) {
            $configs['simple']['price_mode'] = $posted_simple_price_mode;
        }

        return $configs;
    }

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    public function package_fields_for_mode(string $mode, array $config): array
    {
        $mode = $this->normalize_mode($mode);
        return $this->handlers[$mode]->package_fields($config);
    }

    public function posted_simple_price_mode(): ?string
    {
        $value = null;
        if ($this->request->post_has('sltr_simple_price_mode')) {
            $value = $this->request->post_raw('sltr_simple_price_mode');
        }
        if ($value === null) {
            $raw = $this->request->post_raw_array('mode_config');
            if (isset($raw['simple']) && is_array($raw['simple']) && array_key_exists('price_mode', $raw['simple'])) {
                $value = $raw['simple']['price_mode'];
            }
        }
        if ($value === null) {
            $compact = $this->compact_mode_config_from_post();
            if (isset($compact['simple']) && is_array($compact['simple']) && array_key_exists('price_mode', $compact['simple'])) {
                $value = $compact['simple']['price_mode'];
            }
        }
        if (is_array($value)) {
            $value = end($value);
        }
        $value = sanitize_key((string) $value);
        return in_array($value, ['fixed', 'from', 'request'], true) ? $value : null;
    }

    public function posted_confirm_immediately_simple(): ?bool
    {
        foreach (['sltr_confirm_immediately_simple', 'confirm_immediately_simple'] as $name) {
            if ($this->request->post_has($name)) {
                return $this->request->post_truthy($name);
            }
        }
        $raw = $this->request->post_raw_array('mode_config');
        if (isset($raw['simple']) && is_array($raw['simple']) && array_key_exists('confirm_immediately', $raw['simple'])) {
            $value = $raw['simple']['confirm_immediately'];
            if (is_array($value)) {
                $value = end($value);
            }
            return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
        }
        $compact = $this->compact_mode_config_from_post();
        if (isset($compact['simple']) && is_array($compact['simple']) && array_key_exists('confirm_immediately', $compact['simple'])) {
            $value = $compact['simple']['confirm_immediately'];
            if (is_array($value)) {
                $value = end($value);
            }
            return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
        }
        return null;
    }

    private function sanitize_common_config(string $mode, array $source): array
    {
        $duration = $this->duration_from_array($source, 'duration_minutes', 60);
        $full_day_booking = $mode === 'fixed' && !empty($source['full_day_booking']);
        if ($full_day_booking) {
            $duration = 1440;
        }
        $slot_step = $this->duration_from_array($source, 'slot_step', $duration);
        $discount_type = sanitize_key((string) ($source['discount_type'] ?? 'none'));
        if (!in_array($discount_type, ['none', 'percent', 'fixed'], true)) {
            $discount_type = 'none';
        }
        $deposit_type = sanitize_key((string) ($source['deposit_type'] ?? 'percent'));
        if (!in_array($deposit_type, ['percent', 'fixed'], true)) {
            $deposit_type = 'percent';
        }
        $show_duration = $this->bool_from_source($source, 'show_duration');
        foreach (['show_duration_' . $mode, 'show_duration_frontend_' . $mode] as $flat_show_duration_key) {
            if (!$show_duration && $this->request->post_has($flat_show_duration_key)) {
                $show_duration = $this->request->post_truthy($flat_show_duration_key);
            }
        }
        $tax_mode = sanitize_key((string) ($source['tax_mode'] ?? 'exclusive'));
        if (!in_array($tax_mode, ['exclusive', 'inclusive'], true)) {
            $tax_mode = 'exclusive';
        }
        $unpaid_status = sanitize_key((string) ($source['unpaid_booking_status'] ?? 'confirmed'));
        if (!in_array($unpaid_status, ['confirmed'], true)) {
            $unpaid_status = 'confirmed';
        }

        return [
            'duration_minutes' => BusinessValidator::duration_minutes($duration, 60, 1, 1440),
            'full_day_booking' => $full_day_booking ? 1 : 0,
            'slot_step' => BusinessValidator::duration_minutes($full_day_booking ? 60 : $slot_step, 60, 1, 1440),
            'max_bookings_per_slot' => BusinessValidator::capacity($source['max_bookings_per_slot'] ?? 1),
            'show_duration' => $show_duration ? 1 : 0,
            'price' => BusinessValidator::money($source['price'] ?? 0),
            'discount_type' => $discount_type,
            'discount_value' => BusinessValidator::money($source['discount_value'] ?? 0),
            'campaign_note' => sanitize_text_field((string) ($source['campaign_note'] ?? '')),
            'booking_button_text' => sanitize_text_field((string) ($source['booking_button_text'] ?? '')),
            'dynamic_pricing_enabled' => !empty($source['dynamic_pricing_enabled']) ? 1 : 0,
            'dynamic_weekend_percent' => BusinessValidator::percent($source['dynamic_weekend_percent'] ?? 0),
            'dynamic_season_start' => BusinessValidator::date($source['dynamic_season_start'] ?? ''),
            'dynamic_season_end' => BusinessValidator::date($source['dynamic_season_end'] ?? ''),
            'dynamic_season_percent' => BusinessValidator::percent($source['dynamic_season_percent'] ?? 0),
            'tax_enabled' => !empty($source['tax_enabled']) ? 1 : 0,
            'tax_label' => sanitize_text_field((string) ($source['tax_label'] ?? 'VAT')),
            'tax_rate' => BusinessValidator::percent($source['tax_rate'] ?? 0),
            'tax_mode' => $tax_mode,
            'payment_policy' => !empty($source['hide_payment_methods']) ? 'booking_only' : $this->payment_policy_from_options($source),
            'hide_payment_methods' => $this->bool_from_source($source, 'hide_payment_methods') ? 1 : 0,
            'hide_price_on_frontend' => $this->bool_from_source($source, 'hide_price_on_frontend') ? 1 : 0,
            'deposit_type' => $deposit_type,
            'deposit_value' => BusinessValidator::money($source['deposit_value'] ?? 30),
            'unpaid_booking_status' => $unpaid_status,
            'low_availability_notice_enabled' => $this->bool_from_source($source, array_key_exists('low_availability_notice_enabled_checked', $source) ? 'low_availability_notice_enabled_checked' : 'low_availability_notice_enabled') ? 1 : 0,
            'low_availability_threshold' => max(1, min(99, absint($source['low_availability_threshold'] ?? 5))),
        ];
    }

    private function compact_mode_config_from_post(): array
    {
        $decoded = $this->request->post_json_array('sltr_package_compact_state', 'text');
        if (empty($decoded)) {
            return [];
        }
        $clean = [];
        foreach (self::MODES as $mode) {
            if (isset($decoded[$mode]) && is_array($decoded[$mode])) {
                $clean[$mode] = $this->sanitize_compact_value($decoded[$mode]);
            }
        }
        return $clean;
    }

    private function sanitize_compact_value($value)
    {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $child) {
                $clean[is_int($key) ? $key : sanitize_key((string) $key)] = $this->sanitize_compact_value($child);
            }
            return $clean;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        return sanitize_text_field((string) $value);
    }

    private function bool_from_source(array $source, string $key): bool
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
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function payment_policy_from_options(array $source): string
    {
        if (isset($source['payment_options']) && is_array($source['payment_options'])) {
            $options = array_values(array_unique(array_filter(array_map('sanitize_key', $source['payment_options']))));
            $has_booking = in_array('booking_only', $options, true) || in_array('pay_on_arrival', $options, true) || in_array('pay_on_arrival_manual', $options, true) || in_array('manual', $options, true);
            $has_full = in_array('full_payment', $options, true) || in_array('payment', $options, true) || in_array('full', $options, true);
            $has_deposit = in_array('deposit_payment', $options, true) || in_array('prepay', $options, true) || in_array('deposit', $options, true);
            if ($has_booking && $has_full && $has_deposit) { return 'all_options'; }
            if ($has_booking && $has_full) { return 'booking_or_full'; }
            if ($has_booking && $has_deposit) { return 'booking_or_deposit'; }
            if ($has_full && $has_deposit) { return 'full_or_deposit'; }
            if ($has_full) { return 'full_payment'; }
            if ($has_deposit) { return 'deposit_payment'; }
            return 'booking_only';
        }
        $payment_policy = sanitize_key((string) ($source['payment_policy'] ?? 'booking_only'));
        if ($payment_policy === '__from_options') { return 'booking_only'; }
        return in_array($payment_policy, ['booking_only', 'full_payment', 'deposit_payment', 'full_or_deposit', 'booking_or_full', 'booking_or_deposit', 'all_options'], true) ? $payment_policy : 'booking_only';
    }

    private function duration_from_array(array $source, string $key, int $default = 0): int
    {
        if (isset($source[$key . '_hours']) || isset($source[$key . '_mins'])) {
            return BusinessValidator::duration_from_hours_minutes($source[$key . '_hours'] ?? 0, $source[$key . '_mins'] ?? 0, $default, 0, 1440);
        }
        return BusinessValidator::duration_minutes($source[$key] ?? $default, $default, 0, 1440);
    }

    private function post_duration_minutes(string $base, int $default = 0): int
    {
        return $this->request->post_duration_minutes(
            $base,
            $base === 'duration' ? 'duration_minutes' : $base,
            $default,
            0,
            1440
        );
    }

    private function valid_date(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
    }
}
