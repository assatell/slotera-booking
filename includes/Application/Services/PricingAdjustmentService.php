<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

/**
 * Applies package-level dynamic pricing and tax/VAT rules.
 * Values are stored inside the active mode config so each booking block remains isolated.
 */
final class PricingAdjustmentService
{
    public function active_config(array $package): array
    {
        $mode = sanitize_key((string) ($package['booking_mode'] ?? 'simple'));
        if ($mode === 'flexible') { $mode = 'flex'; }
        $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        $config = is_array($configs) && isset($configs[$mode]) && is_array($configs[$mode]) ? $configs[$mode] : [];
        foreach ([
            'dynamic_pricing_enabled', 'dynamic_weekend_percent', 'dynamic_season_start',
            'dynamic_season_end', 'dynamic_season_percent', 'tax_enabled', 'tax_label',
            'tax_rate', 'tax_mode',
        ] as $key) {
            if (array_key_exists($key, $package)) {
                $config[$key] = $package[$key];
            }
        }
        return $config;
    }

    public function apply_dynamic(array $package, float $amount, array $context = []): array
    {
        $amount = round(max(0, $amount), 2);
        $config = $this->active_config($package);
        if (empty($config['dynamic_pricing_enabled'])) {
            return ['base_amount' => $amount, 'dynamic_adjustment_amount' => 0.0, 'dynamic_amount' => $amount, 'applied_rules' => [], 'dynamic_label' => ''];
        }
        $rules = [];
        $adjusted = $amount;
        $date = $this->context_date($context);

        $weekend_percent = max(0, min(100, abs((float) ($config['dynamic_weekend_percent'] ?? 0))));
        if ($weekend_percent > 0.0 && $date !== '' && $this->is_weekend($date)) {
            // Dynamic pricing is customer-facing offer/discount logic, not a surcharge.
            $delta = -round($adjusted * $weekend_percent / 100, 2);
            $adjusted += $delta;
            $rules[] = ['type' => 'weekend', 'label' => function_exists('sltr__') ? \sltr__('frontend.weekend_offer') : __('Weekend offer', 'slotera-booking'), 'percent' => $weekend_percent, 'amount' => $delta];
        }

        $season_percent = max(0, min(100, abs((float) ($config['dynamic_season_percent'] ?? 0))));
        $season_start = sanitize_text_field((string) ($config['dynamic_season_start'] ?? ''));
        $season_end = sanitize_text_field((string) ($config['dynamic_season_end'] ?? ''));
        if ($season_percent > 0.0 && $date !== '' && $this->date_in_range($date, $season_start, $season_end)) {
            $delta = -round($adjusted * $season_percent / 100, 2);
            $adjusted += $delta;
            $rules[] = ['type' => 'season', 'label' => function_exists('sltr__') ? \sltr__('frontend.seasonal_offer') : __('Seasonal offer', 'slotera-booking'), 'percent' => $season_percent, 'amount' => $delta, 'start' => $season_start, 'end' => $season_end];
        }

        $adjusted = round(max(0, $adjusted), 2);
        return [
            'base_amount' => $amount,
            'dynamic_adjustment_amount' => round($adjusted - $amount, 2),
            'dynamic_amount' => $adjusted,
            'applied_rules' => $rules,
            'dynamic_label' => $this->offer_label($rules),
        ];
    }

    public function apply_tax(array $package, float $amount): array
    {
        $amount = round(max(0, $amount), 2);
        $config = $this->active_config($package);
        if (empty($config['tax_enabled'])) {
            $settings = (new SettingsRepository())->all();
            if (empty($settings['payment_tax_enabled'])) {
                return ['net_amount' => $amount, 'tax_amount' => 0.0, 'total_amount' => $amount, 'tax_label' => '', 'tax_mode' => 'none', 'tax_rate' => 0.0];
            }
            $config['tax_enabled'] = 1;
            $config['tax_label'] = (string) ($settings['payment_tax_label'] ?? 'VAT');
            $config['tax_rate'] = (float) ($settings['payment_tax_rate'] ?? 0);
            $config['tax_mode'] = (string) ($settings['payment_tax_mode'] ?? 'exclusive');
        }
        $rate = max(0, (float) ($config['tax_rate'] ?? 0));
        $mode = sanitize_key((string) ($config['tax_mode'] ?? 'exclusive'));
        if (!in_array($mode, ['exclusive', 'inclusive'], true)) { $mode = 'exclusive'; }
        $label = sanitize_text_field((string) ($config['tax_label'] ?? 'VAT'));
        if ($rate <= 0) {
            return ['net_amount' => $amount, 'tax_amount' => 0.0, 'total_amount' => $amount, 'tax_label' => $label, 'tax_mode' => $mode, 'tax_rate' => 0.0];
        }
        if ($mode === 'inclusive') {
            $tax = round($amount - ($amount / (1 + $rate / 100)), 2);
            return ['net_amount' => round($amount - $tax, 2), 'tax_amount' => $tax, 'total_amount' => $amount, 'tax_label' => $label, 'tax_mode' => $mode, 'tax_rate' => $rate];
        }
        $tax = round($amount * $rate / 100, 2);
        return ['net_amount' => $amount, 'tax_amount' => $tax, 'total_amount' => round($amount + $tax, 2), 'tax_label' => $label, 'tax_mode' => $mode, 'tax_rate' => $rate];
    }

    public function finalize_amount(array $package, float $amount, array $context = []): array
    {
        $dynamic = $this->apply_dynamic($package, $amount, $context);
        $tax = $this->apply_tax($package, (float) $dynamic['dynamic_amount']);
        return array_merge($dynamic, $tax, ['final_amount' => (float) $tax['total_amount']]);
    }

    public function apply_to_quote(array $package, array $quote, array $context = []): array
    {
        $base_plus_extras = max(0, (float) ($quote['total_amount'] ?? 0));
        $pricing = $this->finalize_amount($package, $base_plus_extras, $context);
        $quote['pre_tax_amount'] = (float) ($pricing['net_amount'] ?? $base_plus_extras);
        $quote['dynamic_adjustment_amount'] = (float) ($pricing['dynamic_adjustment_amount'] ?? 0);
        $quote['dynamic_label'] = (string) ($pricing['dynamic_label'] ?? '');
        $quote['tax_amount'] = (float) ($pricing['tax_amount'] ?? 0);
        $quote['tax_label'] = (string) ($pricing['tax_label'] ?? '');
        $quote['tax_rate'] = (float) ($pricing['tax_rate'] ?? 0);
        $quote['tax_mode'] = (string) ($pricing['tax_mode'] ?? 'none');
        $quote['total_amount'] = (float) ($pricing['final_amount'] ?? $base_plus_extras);
        return $quote;
    }

    private function offer_label(array $rules): string
    {
        $parts = [];
        foreach ($rules as $rule) {
            $percent = max(0, (float) ($rule['percent'] ?? 0));
            if ($percent <= 0) { continue; }
            $label = trim((string) ($rule['label'] ?? ''));
            if ($label === '') { $label = function_exists('sltr__') ? \sltr__('frontend.special_offer') : __('Special offer', 'slotera-booking'); }
            $parts[] = sprintf('%s -%s%%', $label, rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.'));
        }
        return implode(' · ', $parts);
    }


    /**
     * Re-localize persisted dynamic offer labels for the current frontend locale.
     * Older bookings may contain an English or another-locale label because the
     * display text was historically persisted with the booking.
     */
    public function localize_offer_label(string $label): string
    {
        $label = trim($label);
        if ($label === '') { return ''; }

        $strings = TranslationRegistry::strings_for_group('frontend');
        foreach ([
            'frontend.weekend_offer',
            'frontend.seasonal_offer',
            'frontend.special_offer',
        ] as $key) {
            $meta = is_array($strings[$key] ?? null) ? $strings[$key] : [];
            $localized = function_exists('sltr__') ? \sltr__($key) : (string) ($meta['default'] ?? '');
            if ($localized === '') { continue; }

            $variants = [];
            foreach ($meta as $metaKey => $value) {
                if (in_array($metaKey, ['group','label'], true) || !is_string($value)) { continue; }
                $value = trim($value);
                if ($value !== '') { $variants[$value] = true; }
            }

            $variants = array_keys($variants);
            usort($variants, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
            foreach ($variants as $variant) {
                $label = str_replace($variant, $localized, $label);
            }
        }

        return $label;
    }

    private function context_date(array $context): string
    {
        foreach (['booking_date', 'start_date', 'date'] as $key) {
            $date = sanitize_text_field((string) ($context[$key] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { return $date; }
        }
        return current_time('Y-m-d');
    }

    private function is_weekend(string $date): bool
    {
        try { $dow = (int) (new \DateTimeImmutable($date, wp_timezone()))->format('N'); return $dow >= 6; }
        catch (\Throwable $e) { return false; }
    }

    private function date_in_range(string $date, string $start, string $end): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) { return false; }
        if ($end < $start) { [$start, $end] = [$end, $start]; }
        return $date >= $start && $date <= $end;
    }
}
