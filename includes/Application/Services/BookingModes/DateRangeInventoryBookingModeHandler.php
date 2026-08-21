<?php

declare(strict_types=1);

namespace Slotera\Application\Services\BookingModes;

use Slotera\Application\Services\BookingLifecycleService;
use Slotera\Application\Services\DateRangeInventoryService;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class DateRangeInventoryBookingModeHandler extends AbstractBookingModeHandler
{
    public function create(array $package, array $customer, array $data)
    {
        $inventory = new DateRangeInventoryService($this->context->booking_repository);
        $package_id = (int) ($package['id'] ?? 0);
        $start_date = sanitize_text_field((string) ($data['booking_date'] ?? ''));
        $end_date = sanitize_text_field((string) ($data['end_date'] ?? ''));
        $resource_id = absint($data['resource_id'] ?? 0);
        $extra_ids = array_map('absint', (array) ($data['extra_ids'] ?? []));
        $payment_choice = sanitize_key((string) ($data['payment_choice'] ?? ''));
        $payment_method = sanitize_key((string) ($data['payment_method'] ?? ''));
        $is_scheduled = $inventory->is_admin_scheduled($package);
        $scheduled_event = null;
        if ($is_scheduled) {
            $scheduled_event = $inventory->find_scheduled_event($package, $resource_id);
            if (!$scheduled_event) { return new WP_Error('sltr_event_not_found', __('Selected event is not available.', 'slotera-booking')); }
            $start_date = (string) $scheduled_event['start_date'];
            $end_date = $inventory->exclusive_end_date_for_event($scheduled_event);
        }

        $range_validation = $is_scheduled ? true : $inventory->validate_range($package, $start_date, $end_date);
        if (is_wp_error($range_validation)) { return $range_validation; }
        if ($is_scheduled) {
            if (!$inventory->scheduled_event_available($package, $resource_id)) {
                return new WP_Error('sltr_event_full', __('Selected event is no longer available.', 'slotera-booking'));
            }
            $quote = $inventory->scheduled_event_quote($package, $resource_id, $extra_ids);
        } else {
            if (!$inventory->unit_capacity_available($package, $resource_id, $start_date, $end_date)) {
                return new WP_Error('sltr_unit_unavailable', __('Selected room is no longer available for these dates.', 'slotera-booking'));
            }
            $quote = $inventory->quote($package, $resource_id, $start_date, $end_date, $extra_ids);
        }
        if (is_wp_error($quote)) { return $quote; }
        $quote = $this->context->pricing_adjustments->apply_to_quote($package, $quote, ['start_date' => $start_date, 'end_date' => $end_date]);

        $coupon_id = 0;
        $coupon_code = sanitize_text_field((string) ($data['coupon_code'] ?? ''));
        $coupon_discount_amount = 0.0;
        if ($is_scheduled && $scheduled_event && empty($scheduled_event['allow_coupons'])) {
            $coupon_code = '';
        } elseif ($coupon_code !== '') {
            $coupon_package = array_merge($package, [
                'price' => (float) ($quote['total_amount'] ?? 0),
                'discount_type' => 'none',
                'discount_value' => 0,
            ]);
            $coupon_result = $this->context->coupons->validate_and_calculate($coupon_code, $coupon_package, (string) ($customer['customer_email'] ?? ''));
            if (is_wp_error($coupon_result)) { return $coupon_result; }
            if (is_array($coupon_result) && !empty($coupon_result['valid'])) {
                $coupon = $coupon_result['coupon'] ?? [];
                $coupon_id = (int) ($coupon['id'] ?? 0);
                $coupon_code = (string) ($coupon['code'] ?? $coupon_code);
                $coupon_discount_amount = (float) ($coupon_result['discount_amount'] ?? 0);
                $quote['total_amount'] = (float) ($coupon_result['final_amount'] ?? ($quote['total_amount'] ?? 0));
                $quote['coupon_discount_amount'] = $coupon_discount_amount;
            }
        }

        $payment_package = $package;
        if ($is_scheduled && is_array($scheduled_event)) {
            $payment_package['payment_policy'] = (string) ($scheduled_event['payment_policy'] ?? 'booking_only');
            $payment_package['deposit_type'] = (string) ($scheduled_event['deposit_type'] ?? 'percent');
            $payment_package['deposit_value'] = (float) ($scheduled_event['deposit_value'] ?? 30);
            $payment_package['mode_configs_json'] = '';
        }
        $payment_decision = $this->context->payment_policy_service->choose_option($payment_package, $payment_choice, (float) ($quote['total_amount'] ?? 0), [
            'booking_mode' => 'date_range_inventory',
            'package_id' => $package_id,
            'unit_id' => $resource_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);
        if (is_wp_error($payment_decision)) { return $payment_decision; }
        $requires_payment = !empty($payment_decision['requires_online_payment']);
        $requested_payment_mode = (string) ($payment_decision['payment_mode'] ?? 'none');
        if ($requires_payment) {
            $gateway_validation = $this->context->payment_service->validate_gateway_for_checkout($payment_method);
            if (is_wp_error($gateway_validation)) { return $gateway_validation; }
        } else {
            $payment_method = '';
        }

        $amount_to_collect = (float) ($payment_decision['amount_due_now'] ?? 0);
        $use_online_hold = $requires_payment && $this->uses_online_payment_hold($payment_method);
        $status = $use_online_hold ? BookingLifecycleService::STATUS_PENDING_PAYMENT : (string) ($payment_decision['booking_status'] ?? BookingLifecycleService::STATUS_CONFIRMED);
        if ($requires_payment && !$use_online_hold) { $status = BookingLifecycleService::STATUS_CONFIRMED; }
        $payment_status = ($requires_payment && !$use_online_hold) ? 'pending' : (string) ($payment_decision['payment_status'] ?? 'unpaid');

        $start = $is_scheduled && $scheduled_event && !empty($scheduled_event['use_time']) ? $this->normalize_time((string) ($scheduled_event['start_time'] ?? '')) : $this->normalize_time((string) ($package['checkin_time'] ?? '15:00:00'));
        $end = $is_scheduled && $scheduled_event && !empty($scheduled_event['use_time']) ? $this->normalize_time((string) ($scheduled_event['end_time'] ?? '')) : $this->normalize_time((string) ($package['checkout_time'] ?? '11:00:00'));
        if ($start === '') { $start = '00:00:00'; }
        if ($end === '') { $end = '23:59:59'; }

        $lock = $this->context->booking_lock_service->acquire_date_range_inventory($package_id, $resource_id, $start_date, $end_date);
        if (is_wp_error($lock)) { return $lock; }
        try {
            if ($is_scheduled) {
                if (!$inventory->scheduled_event_available($package, $resource_id)) {
                    return new WP_Error('sltr_event_full', __('Selected event is no longer available.', 'slotera-booking'));
                }
            } elseif (!$inventory->unit_capacity_available($package, $resource_id, $start_date, $end_date)) {
                return new WP_Error('sltr_unit_unavailable', __('Selected room is no longer available for these dates.', 'slotera-booking'));
            }
            $base_booking_data = array_merge($customer, [
                'user_id' => (int) ($data['user_id'] ?? 0),
                'package_id' => $package_id,
                'resource_id' => $resource_id,
                'staff_id' => 0,
                'booking_date' => $start_date,
                'end_date' => $end_date,
                'start_time' => $start,
                'end_time' => $end,
                'status' => $status,
                'payment_status' => $payment_status,
                'payment_gateway' => $payment_method,
                'total_amount' => $amount_to_collect,
                'gross_amount' => (float) ($payment_decision['total_amount'] ?? ($quote['total_amount'] ?? 0)),
                'amount_due_now' => $amount_to_collect,
                'paid_amount' => 0,
                'remaining_amount' => (float) ($payment_decision['remaining_amount'] ?? 0),
                'deposit_amount' => (float) ($payment_decision['deposit_amount'] ?? ($quote['deposit_amount'] ?? 0)),
                'extras_amount' => (float) ($quote['extras_amount'] ?? 0),
                'pricing_adjustment_amount' => (float) ($quote['dynamic_adjustment_amount'] ?? 0),
                'pricing_adjustment_label' => (string) ($quote['dynamic_label'] ?? ''),
                'tax_amount' => (float) ($quote['tax_amount'] ?? 0),
                'selected_extras' => (array) ($quote['selected_extras'] ?? []),
                'pricing_mode' => sanitize_key((string) ($quote['price_unit'] ?? 'fixed')),
                'payment_choice' => (string) ($payment_decision['choice'] ?? 'booking_only'),
                'payment_policy_snapshot' => (array) ($payment_decision['snapshot'] ?? []),
                'base_amount' => (float) ($quote['original_base_amount'] ?? $quote['base_amount'] ?? 0),
                'package_discount_amount' => max(0, (float) ($quote['original_base_amount'] ?? 0) - (float) ($quote['base_amount'] ?? 0)),
                'coupon_id' => $coupon_id,
                'coupon_code' => $coupon_code,
                'coupon_discount_amount' => $coupon_discount_amount,
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
            $this->context->booking_lock_service->release_date_range_inventory($package_id, $resource_id, $start_date, $end_date);
        }

        $booking = $this->context->booking_repository->get_by_id($booking_id);
        $this->context->lifecycle->record_history($booking_id, 'date_range_booking_created', null, $booking, 'Date range booking created.');
        $payment_result = null;
        if ($requires_payment) {
            $before_payment = $this->context->booking_repository->get_by_id($booking_id);
            try {
                $payment_result = $this->context->payment_service->create_for_booking($booking_id, $payment_method, ['source' => 'frontend', 'payment_mode' => $requested_payment_mode, 'defer_notifications' => true]);
            } catch (\Throwable $e) {
                $payment_result = new WP_Error('sltr_payment_initialization_failed', __('Payment could not be started. Please try again or choose another payment method.', 'slotera-booking'));
                $payment_result->add_data(['booking_id' => $booking_id, 'gateway' => $payment_method, 'error' => $e->getMessage()]);
            }
            if (is_wp_error($payment_result)) {
                $this->context->booking_repository->update($booking_id, [
                    'status' => BookingLifecycleService::STATUS_CANCELLED,
                    'payment_status' => 'failed',
                    'payment_gateway' => $payment_method,
                ]);
                $after_payment_failure = $this->context->booking_repository->get_by_id($booking_id);
                $this->context->lifecycle->record_history(
                    $booking_id,
                    'payment_initialization_failed',
                    $before_payment,
                    $after_payment_failure,
                    'Payment initialization failed; pending booking was cancelled to release the slot.',
                    ['gateway' => $payment_method, 'error_code' => $payment_result->get_error_code(), 'error_message' => $payment_result->get_error_message()]
                );
                return $payment_result;
            }
            $booking = $this->context->booking_repository->get_by_id($booking_id);
        }
        $this->notify_after_successful_creation($booking_id, $booking, $package);
        return ['booking_id' => $booking_id, 'payment_result' => is_object($payment_result) && method_exists($payment_result, 'to_array') ? $payment_result->to_array() : null, 'booking' => $booking, 'quote' => $quote];
    }
}
