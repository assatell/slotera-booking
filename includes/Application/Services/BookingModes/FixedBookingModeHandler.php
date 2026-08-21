<?php

declare(strict_types=1);

namespace Slotera\Application\Services\BookingModes;

use Slotera\Application\Services\BookingLifecycleService;
use Slotera\Application\Services\DateRangeInventoryService;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class FixedBookingModeHandler extends AbstractBookingModeHandler
{
    public function create(array $package, array $customer, array $data)
    {
        $package_id = (int) ($package['id'] ?? 0);
        if ($package_id <= 0) {
            return new WP_Error('sltr_invalid_package', __('Selected package is not available.', 'slotera-booking'));
        }

        $date = sanitize_text_field((string) ($data['booking_date'] ?? ''));
        $start = $this->normalize_time((string) ($data['start_time'] ?? ''));
        $end = $this->normalize_time((string) ($data['end_time'] ?? ''));
        $mode_configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        $full_day_booking = is_array($mode_configs) && !empty($mode_configs['fixed']['full_day_booking']);
        if ($full_day_booking) {
            $start = '00:00:00';
            $end = '00:00:00';
        }
        $requested_end_date = sanitize_text_field((string) ($data['end_date'] ?? ''));
        $full_day_end_date = '';
        $full_day_days = 1;
        if ($full_day_booking && $this->is_valid_date($date)) {
            if (!$this->is_valid_date($requested_end_date) || $requested_end_date <= $date) {
                $requested_end_date = (new \DateTimeImmutable($date, wp_timezone()))->modify('+1 day')->format('Y-m-d');
            }
            $full_day_end_date = $requested_end_date;
            $full_day_days = max(1, (int) (new \DateTimeImmutable($date, wp_timezone()))->diff(new \DateTimeImmutable($full_day_end_date, wp_timezone()))->days);
            $end = $start;
        }
        if (!$this->is_valid_date($date) || $start === '' || $end === '') {
            return new WP_Error('sltr_invalid_booking_data', __('Please complete all required fields.', 'slotera-booking'));
        }
        if ($this->is_past_slot($date, $start)) {
            return new WP_Error('sltr_past_booking_date', __('Bookings cannot be created for past dates or times.', 'slotera-booking'));
        }

        $pricing_base = max(0, (float) ($package['price'] ?? 0)) * ($full_day_booking ? $full_day_days : 1);
        $dynamic_pricing = $this->context->pricing_adjustments->apply_dynamic($package, $pricing_base, ['booking_date' => $date]);
        $package_for_pricing = array_merge($package, ['price' => (float) ($dynamic_pricing['dynamic_amount'] ?? $pricing_base)]);
        $sale_price = $this->context->coupons->package_sale_price($package_for_pricing);
        $package_discount_amount = round(max(0, (float) ($dynamic_pricing['dynamic_amount'] ?? $pricing_base) - $sale_price), 2);
        $coupon_code = sanitize_text_field((string) ($data['coupon_code'] ?? ''));
        $coupon_result = $this->context->coupons->validate_and_calculate($coupon_code, $package_for_pricing, (string) ($customer['customer_email'] ?? ''));
        if (is_wp_error($coupon_result)) {
            return $coupon_result;
        }
        $coupon_id = 0;
        $coupon_discount_amount = 0.0;
        $final_amount = $sale_price;
        if (is_array($coupon_result) && !empty($coupon_result['valid'])) {
            $coupon = $coupon_result['coupon'] ?? [];
            $coupon_id = (int) ($coupon['id'] ?? 0);
            $coupon_code = (string) ($coupon['code'] ?? $coupon_code);
            $coupon_discount_amount = (float) ($coupon_result['discount_amount'] ?? 0);
            $final_amount = (float) ($coupon_result['final_amount'] ?? $sale_price);
        } else {
            $coupon_code = '';
        }
        $tax_result = $this->context->pricing_adjustments->apply_tax($package, $final_amount);
        $tax_amount = (float) ($tax_result['tax_amount'] ?? 0);
        $final_amount = (float) ($tax_result['total_amount'] ?? $final_amount);
        $package_for_payment = array_merge($package, ['final_price' => $final_amount, 'total_amount' => $final_amount]);

        $resource_id = absint($data['resource_id'] ?? 0);
        $staff_id = absint($data['staff_id'] ?? 0);
        $payment_choice = sanitize_key((string) ($data['payment_choice'] ?? ($data['payment_mode'] ?? '')));
        $gateway_id = sanitize_key((string) ($data['payment_method'] ?? ''));
        $payment_decision = $this->context->payment_policy_service->choose_option($package_for_payment, $payment_choice, $final_amount, [
            'booking_mode' => sanitize_key((string) ($package['booking_mode'] ?? 'fixed')),
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
        $status = $use_online_hold
            ? BookingLifecycleService::STATUS_PENDING_PAYMENT
            : (string) ($payment_decision['booking_status'] ?? BookingLifecycleService::STATUS_CONFIRMED);
        if ($requires_payment && !$use_online_hold) {
            $status = BookingLifecycleService::STATUS_CONFIRMED;
        }
        $payment_status = ($requires_payment && !$use_online_hold) ? 'pending' : (string) ($payment_decision['payment_status'] ?? 'unpaid');

        $lock = $full_day_booking
            ? $this->context->booking_lock_service->acquire_date_range_inventory($package_id, $resource_id, $date, $full_day_end_date)
            : $this->context->booking_lock_service->acquire($package_id, $date, $start, $end, $resource_id, $staff_id);
        if (is_wp_error($lock)) {
            return $lock;
        }

        try {
            $available = $full_day_booking
                ? $this->context->availability_service->timed_range_is_available($package_id, $date, $start, $full_day_end_date, $end, $resource_id, $staff_id)
                : $this->context->availability_service->slot_is_available($package_id, $date, $start, $end, $resource_id, $staff_id);
            if (!$available) {
                return new WP_Error('sltr_slot_unavailable', __('Selected time slot is no longer available.', 'slotera-booking'));
            }

            $base_booking_data = array_merge($customer, [
                'user_id' => (int) ($data['user_id'] ?? 0),
                'package_id' => $package_id,
                'resource_id' => $resource_id,
                'staff_id' => $staff_id,
                'booking_date' => $date,
                'end_date' => $full_day_booking ? $full_day_end_date : '',
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

            if ($booking_id <= 0) {
                if ($this->context->booking_repository->last_error_was_duplicate_active_slot()) {
                    return new WP_Error('sltr_slot_unavailable', __('Selected time slot is no longer available.', 'slotera-booking'));
                }
                return new WP_Error('sltr_booking_create_failed', __('Booking could not be created.', 'slotera-booking'));
            }
        } finally {
            if ($full_day_booking) {
                $this->context->booking_lock_service->release_date_range_inventory($package_id, $resource_id, $date, $full_day_end_date);
            } else {
                $this->context->booking_lock_service->release($package_id, $date, $start, $end, $resource_id, $staff_id);
            }
        }

        $booking = $this->context->booking_repository->get_by_id($booking_id);
        $this->context->lifecycle->record_history($booking_id, 'booking_created', null, $booking, 'Booking created.');
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

        return [
            'booking_id' => $booking_id,
            'payment_result' => is_object($payment_result) && method_exists($payment_result, 'to_array') ? $payment_result->to_array() : null,
            'booking' => $booking,
        ];
    }
}
