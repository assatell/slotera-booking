<?php

declare(strict_types=1);

namespace Slotera\Application\Services\BookingModes;

use Slotera\Application\Services\BookingLifecycleService;
use Slotera\Application\Services\DateRangeInventoryService;
use Slotera\Application\Services\SimpleBookingQuoteService;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class SimpleBookingModeHandler extends AbstractBookingModeHandler
{
    public function create(array $package, array $customer, array $data)
    {
        $package_id = (int) ($package['id'] ?? 0);
        $capacity_type = sanitize_key((string) ($package['price_unit'] ?? 'fixed'));
        $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        $simple_config = is_array($configs) && isset($configs['simple']) && is_array($configs['simple']) ? $configs['simple'] : [];
        $capacity_type = sanitize_key((string) ($simple_config['capacity_type'] ?? 'unlimited'));
        if ($capacity_type === 'limited') {
            $capacity_total = max(1, (int) ($simple_config['capacity_total'] ?? 1));
            if ($this->context->booking_repository->count_active_by_package_id($package_id) >= $capacity_total) {
                return new WP_Error('sltr_simple_capacity_full', __('This package is no longer available.', 'slotera-booking'));
            }
        }

        $quote = (new SimpleBookingQuoteService($this->context->pricing_adjustments, $this->context->coupons))->quote(
            $package,
            (array) ($data['extra_ids'] ?? []),
            (string) ($data['coupon_code'] ?? ''),
            (string) ($customer['customer_email'] ?? '')
        );
        if (is_wp_error($quote)) { return $quote; }
        $pricing_base = (float) ($quote['package_base_amount'] ?? 0);
        $package_discount_amount = (float) ($quote['package_discount_amount'] ?? 0);
        $coupon_id = (int) ($quote['coupon_id'] ?? 0);
        $coupon_code = (string) ($quote['coupon_code'] ?? '');
        $coupon_discount_amount = (float) ($quote['coupon_discount_amount'] ?? 0);
        $tax_amount = (float) ($quote['tax_amount'] ?? 0);
        $final_amount = (float) ($quote['final_amount'] ?? 0);
        $dynamic_pricing = [
            'dynamic_adjustment_amount' => (float) ($quote['dynamic_adjustment_amount'] ?? 0),
            'dynamic_label' => (string) ($quote['dynamic_label'] ?? ''),
        ];

        $payment_choice = sanitize_key((string) ($data['payment_choice'] ?? ($data['payment_mode'] ?? '')));
        $gateway_id = sanitize_key((string) ($data['payment_method'] ?? ''));
        $payment_decision = $this->context->payment_policy_service->choose_option(array_merge($package, ['final_price' => $final_amount, 'total_amount' => $final_amount]), $payment_choice, $final_amount, [
            'booking_mode' => 'simple',
            'package_id' => $package_id,
        ]);
        if (is_wp_error($payment_decision)) { return $payment_decision; }
        $requires_payment = !empty($payment_decision['requires_online_payment']);
        $requested_payment_mode = (string) ($payment_decision['payment_mode'] ?? 'none');
        if ($requires_payment) {
            $gateway_validation = $this->context->payment_service->validate_gateway_for_checkout($gateway_id);
            if (is_wp_error($gateway_validation)) { return $gateway_validation; }
        } else {
            $gateway_id = '';
        }
        $use_online_hold = $requires_payment && $this->uses_online_payment_hold($gateway_id);
        $status = $use_online_hold ? BookingLifecycleService::STATUS_PENDING_PAYMENT : (string) ($payment_decision['booking_status'] ?? BookingLifecycleService::STATUS_CONFIRMED);
        if ($requires_payment && !$use_online_hold) { $status = BookingLifecycleService::STATUS_CONFIRMED; }
        $payment_status = ($requires_payment && !$use_online_hold) ? 'pending' : (string) ($payment_decision['payment_status'] ?? 'unpaid');
        // The database schema requires date/time values, but Booking Request has no scheduled slot.
        // Store a neutral time sentinel and keep the creation date only for ordering/reporting.
        $booking_date = current_time('Y-m-d');
        $start = '00:00:00';
        $end = '00:00:00';

        $capacity_lock_acquired = false;
        if ($capacity_type === 'limited') {
            $lock = $this->context->booking_lock_service->acquire_simple_capacity($package_id);
            if (is_wp_error($lock)) { return $lock; }
            $capacity_lock_acquired = true;
        }

        try {
            if ($capacity_type === 'limited' && $this->context->booking_repository->count_active_by_package_id($package_id) >= $capacity_total) {
                return new WP_Error('sltr_simple_capacity_full', __('This package is no longer available.', 'slotera-booking'));
            }

        $base_booking_data = array_merge($customer, [
            'user_id' => (int) ($data['user_id'] ?? 0),
            'package_id' => $package_id,
            'resource_id' => 0,
            'staff_id' => 0,
            'booking_date' => $booking_date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
            'payment_status' => $payment_status,
            'payment_gateway' => $gateway_id,
            'total_amount' => (float) ($payment_decision['total_amount'] ?? $final_amount),
            'gross_amount' => (float) ($payment_decision['total_amount'] ?? $final_amount),
            'amount_due_now' => (float) ($payment_decision['amount_due_now'] ?? $final_amount),
            'paid_amount' => 0,
            'remaining_amount' => (float) ($payment_decision['remaining_amount'] ?? 0),
            'deposit_amount' => (float) ($payment_decision['deposit_amount'] ?? 0),
            'extras_amount' => (float) ($quote['extras_amount'] ?? 0),
            'selected_extras' => (array) ($quote['selected_extras'] ?? []),
            'payment_choice' => (string) ($payment_decision['choice'] ?? ''),
            'payment_policy_snapshot' => (array) ($payment_decision['snapshot'] ?? []),
            'base_amount' => $pricing_base,
            'package_discount_amount' => $package_discount_amount,
            'coupon_id' => $coupon_id,
            'coupon_code' => $coupon_code,
            'coupon_discount_amount' => $coupon_discount_amount,
            'pricing_adjustment_amount' => (float) ($dynamic_pricing['dynamic_adjustment_amount'] ?? 0),
                'pricing_adjustment_label' => (string) ($dynamic_pricing['dynamic_label'] ?? ''),
            'tax_amount' => $tax_amount,
            'pricing_mode' => 'simple',
            'source' => sanitize_key((string) ($data['source'] ?? 'frontend')),
        ]);

        $booking_id = 0;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $booking_id = $this->context->booking_repository->create(array_merge($base_booking_data, [
                'cancellation_token' => $this->context->lifecycle->generate_unique_token('cancellation_token', $this->context->booking_repository),
                'reschedule_token' => $this->context->lifecycle->generate_unique_token('reschedule_token', $this->context->booking_repository),
            ]));
            if ($booking_id > 0) { break; }
            if (!$this->context->booking_repository->last_error_was_duplicate_token()) { break; }
        }
        if ($booking_id <= 0) { return new WP_Error('sltr_booking_create_failed', __('Booking could not be created.', 'slotera-booking')); }
        } finally {
            if ($capacity_lock_acquired) {
                $this->context->booking_lock_service->release_simple_capacity($package_id);
            }
        }

        $booking = $this->context->booking_repository->get_by_id($booking_id);
        $this->context->lifecycle->record_history($booking_id, 'simple_booking_created', null, $booking, 'Booking Request created.');
        // Coupon usage is recorded only after a verified paid/partial payment transition.
        // Pending online-payment holds must not consume a coupon before payment succeeds.
        $payment_result = null;
        if ($requires_payment) {
            $before_payment = $this->context->booking_repository->get_by_id($booking_id);
            try {
                $payment_result = $this->context->payment_service->create_for_booking($booking_id, $gateway_id, ['source' => 'frontend', 'payment_mode' => $requested_payment_mode, 'defer_notifications' => true]);
            } catch (\Throwable $e) {
                $payment_result = new WP_Error('sltr_payment_initialization_failed', __('Payment could not be started. Please try again or choose another payment method.', 'slotera-booking'));
                $payment_result->add_data(['booking_id' => $booking_id, 'gateway' => $gateway_id, 'error' => $e->getMessage()]);
            }
            if (is_wp_error($payment_result)) {
                $this->context->booking_repository->update($booking_id, [
                    'status' => BookingLifecycleService::STATUS_CANCELLED,
                    'payment_status' => 'failed',
                    'payment_gateway' => $gateway_id,
                ]);
                $after_payment_failure = $this->context->booking_repository->get_by_id($booking_id);
                $this->context->lifecycle->record_history(
                    $booking_id,
                    'payment_initialization_failed',
                    $before_payment,
                    $after_payment_failure,
                    'Payment initialization failed; pending booking was cancelled to release the slot.',
                    ['gateway' => $gateway_id, 'error_code' => $payment_result->get_error_code(), 'error_message' => $payment_result->get_error_message()]
                );
                return $payment_result;
            }
            $booking = $this->context->booking_repository->get_by_id($booking_id);
        }
        $this->notify_after_successful_creation($booking_id, $booking, $package);
        return ['booking_id' => $booking_id, 'payment_result' => is_object($payment_result) && method_exists($payment_result, 'to_array') ? $payment_result->to_array() : null, 'booking' => $booking];
    }
}
