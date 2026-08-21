<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use WP_Error;

if (!defined('ABSPATH')) { exit; }

/**
 * Central payment policy resolver for all booking modes.
 *
 * Pricing services should calculate the gross booking total first. This service
 * then decides which payment choices are available, how much is due now, and
 * which initial booking/payment statuses should be used before any gateway is
 * contacted.
 */
final class PaymentPolicyService
{
    public const CHOICE_FULL = 'full_payment';
    public const CHOICE_PREPAY = 'deposit_payment';
    public const CHOICE_PAY_LATER = 'booking_only';

    /** @return array<int,array<string,mixed>> */
    public function get_available_options(array $package, float $total_amount, array $context = []): array
    {
        if (function_exists('sltr_mvp_online_payments_disabled') && \sltr_mvp_online_payments_disabled()) {
            $decision = $this->choose_option($package, self::CHOICE_PAY_LATER, $total_amount, $context);
            return is_wp_error($decision) ? [] : [$decision];
        }

        $policy = $this->policy_from_package($package);
        $options = [];
        foreach ($this->choices_for_policy($policy) as $choice) {
            $decision = $this->choose_option($package, $choice, $total_amount, $context);
            if (!is_wp_error($decision)) {
                $options[] = $decision;
            }
        }
        return $options;
    }

    /** @return array<string,mixed>|WP_Error */
    public function choose_option(array $package, string $choice, float $total_amount, array $context = [])
    {
        $total_amount = round(max(0, $total_amount), 2);
        if (function_exists('sltr_mvp_online_payments_disabled') && \sltr_mvp_online_payments_disabled()) {
            $policy = self::CHOICE_PAY_LATER;
            $choice = self::CHOICE_PAY_LATER;
        } else {
            $policy = $this->policy_from_package($package);
            $choice = $this->normalize_choice($choice, $policy);
        }
        if (!in_array($choice, $this->choices_for_policy($policy), true)) {
            return new WP_Error('sltr_invalid_payment_choice', __('Selected payment option is not available for this package.', 'slotera-booking'));
        }

        $deposit_amount = $choice === self::CHOICE_PREPAY ? $this->deposit_amount($package, $total_amount) : 0.0;
        $due_now = $choice === self::CHOICE_FULL ? $total_amount : ($choice === self::CHOICE_PREPAY ? $deposit_amount : 0.0);
        $remaining = round(max(0, $total_amount - $due_now), 2);
        $requires_online_payment = $due_now > 0;

        $active_config = $this->active_mode_config($package);
        $unpaid_status = sanitize_key((string) ($active_config['unpaid_booking_status'] ?? ($package['unpaid_booking_status'] ?? 'confirmed')));
        if (!in_array($unpaid_status, [BookingLifecycleService::STATUS_CONFIRMED], true)) {
            $unpaid_status = BookingLifecycleService::STATUS_CONFIRMED;
        }

        return [
            'policy' => $policy,
            'choice' => $choice,
            'payment_mode' => $choice === self::CHOICE_PREPAY ? 'prepay' : ($choice === self::CHOICE_FULL ? 'payment' : 'none'),
            'requires_online_payment' => $requires_online_payment,
            'total_amount' => $total_amount,
            'amount_due_now' => round($due_now, 2),
            'deposit_amount' => round($deposit_amount, 2),
            'paid_amount' => 0.0,
            'remaining_amount' => $remaining,
            'booking_status' => $requires_online_payment ? BookingLifecycleService::STATUS_PENDING_PAYMENT : $unpaid_status,
            'payment_status' => $requires_online_payment ? 'unpaid' : 'unpaid',
            'label' => $this->label_for_choice($choice),
            'snapshot' => [
                'policy' => $policy,
                'choice' => $choice,
                'deposit_type' => sanitize_key((string) ($active_config['deposit_type'] ?? ($package['deposit_type'] ?? 'percent'))),
                'deposit_value' => max(0, (float) ($active_config['deposit_value'] ?? ($package['deposit_value'] ?? 0))),
                'total_amount' => $total_amount,
                'amount_due_now' => round($due_now, 2),
                'remaining_amount' => $remaining,
                'unpaid_booking_status' => $unpaid_status,
                'context' => $this->sanitize_context($context),
            ],
        ];
    }

    private function policy_from_package(array $package): string
    {
        $active = $this->active_mode_config($package);
        if (isset($active['payment_options']) && is_array($active['payment_options'])) {
            return $this->policy_from_options($active['payment_options']);
        }
        $raw = (string) ($active['payment_policy'] ?? ($package['payment_policy'] ?? ($package['checkout_mode'] ?? self::CHOICE_PAY_LATER)));
        if ($raw === '__from_options') { $raw = self::CHOICE_PAY_LATER; }
        return $this->normalize_policy($raw);
    }

    private function policy_from_options(array $options): string
    {
        $options = array_values(array_unique(array_filter(array_map('sanitize_key', $options))));
        $has_booking = in_array('booking_only', $options, true) || in_array('pay_on_arrival', $options, true) || in_array('pay_on_arrival_manual', $options, true) || in_array('manual', $options, true) || in_array('unpaid', $options, true);
        $has_full = in_array('full_payment', $options, true) || in_array('payment', $options, true) || in_array('full', $options, true);
        $has_deposit = in_array('deposit_payment', $options, true) || in_array('prepay', $options, true) || in_array('deposit', $options, true);
        if ($has_booking && $has_full && $has_deposit) { return 'all_options'; }
        if ($has_booking && $has_full) { return 'booking_or_full'; }
        if ($has_booking && $has_deposit) { return 'booking_or_deposit'; }
        if ($has_full && $has_deposit) { return 'full_or_deposit'; }
        if ($has_full) { return self::CHOICE_FULL; }
        if ($has_deposit) { return self::CHOICE_PREPAY; }
        return self::CHOICE_PAY_LATER;
    }

    private function active_mode_config(array $package): array
    {
        $mode = sanitize_key((string) ($package['booking_mode'] ?? 'simple'));
        if ($mode === 'flexible') { $mode = 'flex'; }
        $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        return is_array($configs) && isset($configs[$mode]) && is_array($configs[$mode]) ? $configs[$mode] : [];
    }

    public function normalize_policy(string $policy): string
    {
        $policy = sanitize_key($policy);
        $aliases = [
            'pay_on_arrival' => self::CHOICE_PAY_LATER,
            'pay_on_arrival_manual' => self::CHOICE_PAY_LATER,
            'manual' => self::CHOICE_PAY_LATER,
            'arrival' => self::CHOICE_PAY_LATER,
            'offline' => self::CHOICE_PAY_LATER,
            'unpaid' => self::CHOICE_PAY_LATER,
            'full_only' => self::CHOICE_FULL,
            'prepay_only' => self::CHOICE_PREPAY,
            'prepayment_only' => self::CHOICE_PREPAY,
            'prepay' => self::CHOICE_PREPAY,
            'full_or_prepay' => 'full_or_deposit',
            'full_or_prepay_or_arrival' => 'all_options',
        ];
        $policy = $aliases[$policy] ?? $policy;
        return in_array($policy, [self::CHOICE_PAY_LATER, self::CHOICE_FULL, self::CHOICE_PREPAY, 'full_or_deposit', 'booking_or_full', 'booking_or_deposit', 'all_options'], true)
            ? $policy
            : self::CHOICE_PAY_LATER;
    }

    public function normalize_choice(string $choice, string $policy = ''): string
    {
        $choice = sanitize_key($choice);
        $aliases = ['payment' => self::CHOICE_FULL, 'full' => self::CHOICE_FULL, 'prepay' => self::CHOICE_PREPAY, 'deposit' => self::CHOICE_PREPAY, 'unpaid' => self::CHOICE_PAY_LATER, 'pay_on_arrival' => self::CHOICE_PAY_LATER, 'pay_on_arrival_manual' => self::CHOICE_PAY_LATER, 'manual' => self::CHOICE_PAY_LATER, 'arrival' => self::CHOICE_PAY_LATER, 'offline' => self::CHOICE_PAY_LATER, 'none' => self::CHOICE_PAY_LATER, '' => ''];
        $choice = $aliases[$choice] ?? $choice;
        if ($choice !== '') { return $choice; }
        $choices = $this->choices_for_policy($this->normalize_policy($policy));
        return $choices[0] ?? self::CHOICE_PAY_LATER;
    }

    /** @return array<int,string> */
    public function choices_for_policy(string $policy): array
    {
        $policy = $this->normalize_policy($policy);
        if ($policy === self::CHOICE_FULL) { return [self::CHOICE_FULL]; }
        if ($policy === self::CHOICE_PREPAY) { return [self::CHOICE_PREPAY]; }
        if ($policy === 'full_or_deposit') { return [self::CHOICE_PREPAY, self::CHOICE_FULL]; }
        if ($policy === 'booking_or_full') { return [self::CHOICE_FULL, self::CHOICE_PAY_LATER]; }
        if ($policy === 'booking_or_deposit') { return [self::CHOICE_PREPAY, self::CHOICE_PAY_LATER]; }
        if ($policy === 'all_options') { return [self::CHOICE_PREPAY, self::CHOICE_FULL, self::CHOICE_PAY_LATER]; }
        return [self::CHOICE_PAY_LATER];
    }

    public function amount_for_choice(array $package, string $choice, float $total_amount): float
    {
        $decision = $this->choose_option($package, $choice, $total_amount);
        return is_wp_error($decision) ? 0.0 : (float) ($decision['amount_due_now'] ?? 0);
    }

    private function deposit_amount(array $package, float $total): float
    {
        $active = $this->active_mode_config($package);
        $type = sanitize_key((string) ($active['deposit_type'] ?? ($package['deposit_type'] ?? 'percent')));
        $value = max(0, (float) ($active['deposit_value'] ?? ($package['deposit_value'] ?? 0)));
        if ($total <= 0) { return 0.0; }
        return round($type === 'fixed' ? min($total, $value) : min($total, $total * min(100, $value) / 100), 2);
    }

    private function label_for_choice(string $choice): string
    {
        if ($choice === self::CHOICE_FULL) { return function_exists('sltr_t') ? sltr_t('Pay in full') : __('Pay in full', 'slotera-booking'); }
        if ($choice === self::CHOICE_PREPAY) { return __('Prepay / deposit', 'slotera-booking'); }
        return __('Pay on arrival', 'slotera-booking');
    }

    private function sanitize_context(array $context): array
    {
        $safe = [];
        foreach ($context as $key => $value) {
            $key = sanitize_key((string) $key);
            if ($key === '') { continue; }
            if (is_scalar($value) || $value === null) {
                $safe[$key] = is_string($value) ? sanitize_text_field($value) : $value;
            }
        }
        return $safe;
    }
}
