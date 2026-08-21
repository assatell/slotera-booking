<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\BookingHistoryRepository;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Infrastructure\Repositories\PaymentTransactionRepository;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

/**
 * Payment status facade.
 *
 * Handles offline payment methods and delegates online checkout creation to
 * configured gateway services such as Stripe Checkout.
 */
final class PaymentService
{
    private BookingRepository $bookings;
    private BookingHistoryRepository $history;
    private SettingsRepository $settings;
    private NotificationService $notifications;
    private PaymentTransactionRepository $transactions;

    public function __construct($manager = null, ?BookingRepository $bookings = null, ?BookingHistoryRepository $history = null, ?SettingsRepository $settings = null, ?NotificationService $notifications = null)
    {
        // Retained as the first argument for backward-compatible construction.
        unset($manager);
        $this->bookings = $bookings ?: new BookingRepository();
        $this->history = $history ?: new BookingHistoryRepository();
        $this->settings = $settings ?: new SettingsRepository();
        $this->notifications = $notifications ?: new NotificationService();
        $this->transactions = new PaymentTransactionRepository();
    }

    public function requires_payment(array $package, string $requested_mode): bool
    {
        $amount = $this->amount_for_mode($package, $requested_mode);
        return $amount > 0;
    }

    public function amount_for_mode(array $package, string $requested_mode): float
    {
        $total_amount = max(0, (float) ($package['total_amount'] ?? $package['price'] ?? 0));
        $decision = (new PaymentPolicyService())->choose_option($package, $requested_mode, $total_amount);
        return is_wp_error($decision) ? 0.0 : round((float) ($decision['amount_due_now'] ?? 0), 2);
    }

    public function validate_checkout_payment_requirement(array $package, string $requested_mode, string $gateway_id)
    {
        $gateway_validation = $this->validate_gateway_for_checkout($gateway_id);
        if (is_wp_error($gateway_validation)) { return $gateway_validation; }

        $total_amount = max(0, (float) ($package['total_amount'] ?? $package['price'] ?? 0));
        $decision = (new PaymentPolicyService())->choose_option($package, $requested_mode, $total_amount);
        if (is_wp_error($decision)) { return $decision; }

        $requires_online_payment = !empty($decision['requires_online_payment']);
        $is_online = in_array(sanitize_key($gateway_id), ['stripe', 'apple_pay', 'google_pay', 'paypal', 'mollie'], true);

        if ($requires_online_payment && !$is_online) {
            return new WP_Error('sltr_online_payment_method_required', __('Please choose an online payment method for this payment option.', 'slotera-booking'));
        }

        return true;
    }

    public function validate_gateway_for_checkout(string $gateway_id, ?array $booking = null)
    {
        $gateway_id = sanitize_key($gateway_id);
        if ($gateway_id === '') {
            return new WP_Error('sltr_payment_method_required', __('Please choose a payment method.', 'slotera-booking'));
        }

        if (!(new PaymentMethodService($this->settings))->method_exists($gateway_id)) {
            return new WP_Error('sltr_payment_method_unavailable', __('Selected payment method is not available.', 'slotera-booking'));
        }

        return true;
    }

    public function create_for_booking(int $booking_id, string $gateway_id, array $context = [])
    {
        $gateway_id = sanitize_key($gateway_id);
        $validation = $this->validate_gateway_for_checkout($gateway_id);
        if (is_wp_error($validation)) { return $validation; }

        $booking = $this->bookings->get_by_id($booking_id);
        if (!$booking) {
            return new WP_Error('sltr_booking_not_found', __('Booking not found.', 'slotera-booking'));
        }

        if (in_array($gateway_id, ['stripe', 'apple_pay', 'google_pay'], true)) {
            if ($gateway_id === 'apple_pay') { $context['wallet'] = 'apple_pay'; }
            if ($gateway_id === 'google_pay') { $context['wallet'] = 'google_pay'; }
            $context['selected_payment_method'] = $gateway_id;
            return (new StripeGatewayService($this->settings, $this->bookings))->create_checkout_session($booking_id, $context);
        }
        if ($gateway_id === 'paypal') {
            return (new PayPalGatewayService($this->settings, $this->bookings))->create_order($booking_id, $context);
        }
        if ($gateway_id === 'mollie') {
            return (new MollieGatewayService($this->settings, $this->bookings, $this->transactions))->create_payment($booking_id, $context);
        }

        $external_id = 'sltr_' . $booking_id . '_' . wp_generate_password(8, false, false);
        $this->bookings->update_payment_meta($booking_id, $gateway_id, $external_id);
        $this->bookings->update_payment_status($booking_id, 'pending');
        $settings = $this->settings->all();
        $this->transactions->create([
            'booking_id' => $booking_id,
            'customer_email' => (string) ($booking['customer_email'] ?? ''),
            'gateway' => $gateway_id,
            'transaction_type' => 'payment',
            'status' => 'pending',
            'amount' => (float) ($booking['amount_due_now'] ?? $booking['total_amount'] ?? 0),
            'currency' => CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR')),
            'external_id' => $external_id,
            'mode' => 'manual',
            'description' => 'Offline payment method selected by customer.',
            'metadata' => ['payment_mode' => $context['payment_mode'] ?? ''],
        ]);

        $after = $this->bookings->get_by_id($booking_id);
        (new InvoiceService($this->bookings))->sync_for_booking($booking_id);
        if (empty($context['defer_notifications'])) {
            $this->notifications->payment_pending($booking_id, $after ?: $booking, null);
        }

        $this->history->create([
            'booking_id' => $booking_id,
            'event' => 'payment_pending',
            'old_status' => $booking['status'] ?? null,
            'new_status' => $after['status'] ?? null,
            'old_payment_status' => $booking['payment_status'] ?? null,
            'new_payment_status' => $after['payment_status'] ?? null,
            'message' => 'Offline payment method selected by customer.',
        ]);

        return new PaymentResult([
            'status' => 'pending',
            'gateway' => $gateway_id,
            'external_id' => $external_id,
            'payment_mode' => sanitize_key((string) ($context['payment_mode'] ?? '')),
            'offline' => true,
        ]);
    }

    public function mark_paid(int $booking_id, string $source = 'admin')
    {
        return $this->set_payment_status($booking_id, 'paid', 'payment_marked_paid', $source === 'stripe_webhook' ? 'Payment confirmed by Stripe webhook.' : 'Payment marked paid by admin.', $source);
    }

    public function mark_unpaid(int $booking_id)
    {
        return $this->set_payment_status($booking_id, 'unpaid', 'payment_marked_unpaid', 'Payment marked unpaid by admin.', 'admin');
    }

    public function mark_failed(int $booking_id, string $source = 'gateway', string $message = 'Payment failed.')
    {
        return $this->set_payment_status($booking_id, 'failed', 'payment_failed', $message, $source);
    }

    public function mark_processing(int $booking_id, string $source = 'paypal_return', string $message = 'PayPal is processing the payment.')
    {
        return $this->set_payment_status($booking_id, 'processing', 'payment_processing', $message, $source);
    }


    public function refund_transaction(int $transaction_id, float $amount = 0.0, string $reason = 'requested_by_customer')
    {
        $transaction = $this->transactions->get($transaction_id);
        if (!$transaction) {
            return new WP_Error('sltr_transaction_not_found', __('Payment transaction not found.', 'slotera-booking'));
        }

        $booking_id = absint($transaction['booking_id'] ?? 0);
        $booking = $booking_id > 0 ? $this->bookings->get_by_id($booking_id) : null;
        if (!$booking) {
            return new WP_Error('sltr_booking_not_found', __('Booking not found.', 'slotera-booking'));
        }

        $gateway = sanitize_key((string) ($transaction['gateway'] ?? ''));
        $parent_id = sanitize_text_field((string) (($transaction['external_parent_id'] ?? '') ?: ($transaction['external_id'] ?? '')));
        $transaction_amount = (float) ($transaction['amount'] ?? 0);
        $already_refunded = $parent_id !== '' ? $this->transactions->refunded_amount_for_parent($gateway, $parent_id) : 0.0;
        $refundable = round(max(0, $transaction_amount - $already_refunded), 2);
        $amount = $amount > 0 ? round(min($amount, $refundable), 2) : $refundable;

        if ($amount <= 0) {
            return new WP_Error('sltr_refund_amount_invalid', __('There is no refundable amount left for this transaction.', 'slotera-booking'));
        }

        $refund_id = 'sltr_refund_' . $transaction_id . '_' . wp_generate_password(8, false, false);
        $mode = sanitize_key((string) ($transaction['mode'] ?? 'manual'));
        $description = __('Refund recorded in Slotera.', 'slotera-booking');
        $metadata = ['source_transaction_id' => $transaction_id, 'reason' => sanitize_key($reason), 'local_only' => true];

        if ($gateway === 'stripe' && $parent_id !== '') {
            $stripe_result = (new StripeGatewayService($this->settings, $this->bookings))->create_refund($booking_id, $amount, $parent_id, $reason);
            if (is_wp_error($stripe_result)) {
                $this->transactions->create([
                    'booking_id' => $booking_id,
                    'customer_email' => (string) ($booking['customer_email'] ?? ''),
                    'gateway' => 'stripe',
                    'transaction_type' => 'refund',
                    'status' => 'failed',
                    'amount' => $amount,
                    'currency' => (string) ($transaction['currency'] ?? CurrencyService::normalize((string) ($this->settings->all()['payment_currency'] ?? 'EUR'))),
                    'external_id' => '',
                    'external_parent_id' => $parent_id,
                    'mode' => $mode,
                    'description' => 'Stripe refund failed.',
                    'error_message' => $stripe_result->get_error_message(),
                    'metadata' => ['source_transaction_id' => $transaction_id, 'reason' => sanitize_key($reason)],
                ]);
                return $stripe_result;
            }
            if (is_array($stripe_result)) {
                $refund_id = sanitize_text_field((string) ($stripe_result['id'] ?? $refund_id));
                $description = __('Stripe refund created.', 'slotera-booking');
                $metadata = ['source_transaction_id' => $transaction_id, 'reason' => sanitize_key($reason), 'stripe_status' => sanitize_key((string) ($stripe_result['status'] ?? ''))];
            }
        }

        if ($gateway === 'mollie' && $parent_id !== '') {
            $mollie_result = (new MollieGatewayService($this->settings, $this->bookings, $this->transactions))->create_refund($booking_id, $amount, $parent_id, $reason);
            if (is_wp_error($mollie_result)) {
                $this->transactions->create([
                    'booking_id' => $booking_id,
                    'customer_email' => (string) ($booking['customer_email'] ?? ''),
                    'gateway' => 'mollie',
                    'transaction_type' => 'refund',
                    'status' => 'failed',
                    'amount' => $amount,
                    'currency' => (string) ($transaction['currency'] ?? CurrencyService::normalize((string) ($this->settings->all()['payment_currency'] ?? 'EUR'))),
                    'external_id' => '',
                    'external_parent_id' => $parent_id,
                    'mode' => $mode,
                    'description' => 'Mollie refund failed.',
                    'error_message' => $mollie_result->get_error_message(),
                    'metadata' => ['source_transaction_id' => $transaction_id, 'reason' => sanitize_key($reason)],
                ]);
                return $mollie_result;
            }
            if (is_array($mollie_result)) {
                $refund_id = sanitize_text_field((string) ($mollie_result['id'] ?? $refund_id));
                $description = __('Mollie refund created.', 'slotera-booking');
                $metadata = ['source_transaction_id' => $transaction_id, 'reason' => sanitize_key($reason), 'mollie_status' => sanitize_key((string) ($mollie_result['status'] ?? ''))];
            }
        }

        if ($gateway === 'paypal' && $parent_id !== '') {
            $paypal_result = (new PayPalGatewayService($this->settings, $this->bookings))->create_refund($booking_id, $amount, $parent_id, $reason);
            if (is_wp_error($paypal_result)) {
                $this->transactions->create([
                    'booking_id' => $booking_id,
                    'customer_email' => (string) ($booking['customer_email'] ?? ''),
                    'gateway' => 'paypal',
                    'transaction_type' => 'refund',
                    'status' => 'failed',
                    'amount' => $amount,
                    'currency' => (string) ($transaction['currency'] ?? CurrencyService::normalize((string) ($this->settings->all()['payment_currency'] ?? 'EUR'))),
                    'external_id' => '',
                    'external_parent_id' => $parent_id,
                    'mode' => $mode,
                    'description' => 'PayPal refund failed.',
                    'error_message' => $paypal_result->get_error_message(),
                    'metadata' => ['source_transaction_id' => $transaction_id, 'reason' => sanitize_key($reason)],
                ]);
                return $paypal_result;
            }
            if (is_array($paypal_result)) {
                $refund_id = sanitize_text_field((string) ($paypal_result['id'] ?? $refund_id));
                $description = __('PayPal refund created.', 'slotera-booking');
                $metadata = ['source_transaction_id' => $transaction_id, 'reason' => sanitize_key($reason), 'paypal_status' => sanitize_key((string) ($paypal_result['status'] ?? ''))];
            }
        }

        $this->transactions->create([
            'booking_id' => $booking_id,
            'customer_email' => (string) ($booking['customer_email'] ?? ''),
            'gateway' => $gateway !== '' ? $gateway : 'manual',
            'transaction_type' => 'refund',
            'status' => 'refunded',
            'amount' => $amount,
            'currency' => (string) ($transaction['currency'] ?? CurrencyService::normalize((string) ($this->settings->all()['payment_currency'] ?? 'EUR'))),
            'external_id' => $refund_id,
            'external_parent_id' => $parent_id,
            'mode' => $mode,
            'description' => $description,
            'metadata' => $metadata,
        ]);

        $paid_before = (float) ($booking['paid_amount'] ?? $transaction_amount);
        $gross_amount = (float) ($booking['gross_amount'] ?? ($booking['total_amount'] ?? 0));
        $paid_after = round(max(0, $paid_before - $amount), 2);
        $remaining_after = round(max(0, $gross_amount - $paid_after), 2);
        $new_payment_status = $paid_after <= 0.009 ? 'refunded' : 'partially_refunded';

        $this->bookings->update($booking_id, [
            'payment_status' => $new_payment_status,
            'paid_amount' => $paid_after,
            'remaining_amount' => $remaining_after,
            'refunded_at' => current_time('mysql'),
        ]);
        $after = $this->bookings->get_by_id($booking_id);
        (new InvoiceService($this->bookings))->sync_for_booking($booking_id);

        $this->history->create([
            'booking_id' => $booking_id,
            'event' => $new_payment_status === 'refunded' ? 'payment_refunded' : 'payment_partially_refunded',
            'old_status' => $booking['status'] ?? null,
            'new_status' => $after['status'] ?? null,
            'old_payment_status' => $booking['payment_status'] ?? null,
            'new_payment_status' => $after['payment_status'] ?? null,
            'message' => sprintf('Refund recorded: %.2f %s.', $amount, (string) ($transaction['currency'] ?? '')),
        ]);

        return true;
    }

    public function get_booking_redirect_url(int $booking_id, ?array $payment_result = null): string
    {
        if (is_array($payment_result)) {
            $payment_url = esc_url_raw((string) ($payment_result['redirect_url'] ?? $payment_result['checkout_url'] ?? ''));
            if ($payment_url !== '') { return $payment_url; }
        }

        $booking = $this->bookings->get_by_id($booking_id);
        if (!$booking) { return ''; }

        $redirect_url = $this->settings->get_page_url('thank_you');
        if ($redirect_url === '') { return ''; }

        $access_code = (new BookingAccessTokenService())->issue_code($booking);
        if ($access_code === '') { return ''; }

        return esc_url_raw(add_query_arg([
            'booking_id' => $booking_id,
            'sltr_access_code' => $access_code,
        ], $redirect_url));
    }

    private function set_payment_status(int $booking_id, string $status, string $event, string $message, string $source = 'admin')
    {
        $before = $this->bookings->get_by_id($booking_id);
        if (!$before) {
            return new WP_Error('sltr_booking_not_found', __('Booking not found.', 'slotera-booking'));
        }

        $target_status = sanitize_key($status);
        $before_payment_status = sanitize_key((string) ($before['payment_status'] ?? ''));
        $paid_amount = (float) ($before['paid_amount'] ?? 0);
        $gross_amount = (float) ($before['gross_amount'] ?? ($before['total_amount'] ?? 0));
        $due_now = (float) ($before['amount_due_now'] ?? ($before['total_amount'] ?? 0));
        $remaining_amount = (float) ($before['remaining_amount'] ?? max(0, $gross_amount - $paid_amount));
        $is_gateway_confirmation = in_array($source, ['stripe_webhook', 'paypal_webhook', 'paypal_return', 'mollie_webhook', 'gateway', 'stripe', 'paypal', 'mollie'], true);

        $update = ['payment_status' => $target_status];

        if ($target_status === 'paid') {
            // Gateway confirmations settle the current checkout amount. If the
            // booking was created with a deposit choice, this should become a
            // partial/deposit-paid payment instead of incorrectly closing the
            // whole booking balance.
            $collected_now = $is_gateway_confirmation ? max(0, $due_now) : max($gross_amount, $due_now);
            $new_paid_amount = round(max($paid_amount, $collected_now), 2);
            $new_remaining = round(max(0, $gross_amount - $new_paid_amount), 2);
            $target_status = $new_remaining > 0.009 ? 'partial' : 'paid';

            $update['payment_status'] = $target_status;
            $update['paid_amount'] = $new_paid_amount;
            $update['remaining_amount'] = $new_remaining;
            $update['paid_at'] = current_time('mysql');
        } elseif ($target_status === 'unpaid') {
            $update['paid_amount'] = 0;
            $update['remaining_amount'] = $gross_amount > 0 ? $gross_amount : $remaining_amount;
        }

        // A late failure must not regress a payment already confirmed by a gateway.
        if ($target_status === 'failed' && in_array($before_payment_status, ['paid', 'partial'], true)) {
            return true;
        }
        if ($before_payment_status === $target_status) {
            // A duplicate gateway callback is also our recovery path. The first
            // delivery may have persisted the payment state and then stopped before
            // booking confirmation or downstream financial side effects completed.
            if (in_array($target_status, ['paid', 'partial'], true)) {
                return $this->reconcile_successful_payment($booking_id, $before, $target_status, $event, $message, $source, $gross_amount);
            }
            return true;
        }

        $updated = $this->bookings->compare_and_set_payment_status($booking_id, $before_payment_status, $update);
        if (!$updated) {
            // Another concurrent delivery may have completed the payment-state CAS.
            // Re-read and run the same idempotent reconciliation path instead of
            // returning success before lifecycle/financial side effects are safe.
            $current = $this->bookings->get_by_id($booking_id);
            if ($current && sanitize_key((string) ($current['payment_status'] ?? '')) === $target_status) {
                if (in_array($target_status, ['paid', 'partial'], true)) {
                    return $this->reconcile_successful_payment($booking_id, $current, $target_status, $event, $message, $source, $gross_amount);
                }
                return true;
            }
            return false;
        }

        $after = $this->bookings->get_by_id($booking_id);
        if (!$after) { return false; }

        if (in_array($target_status, ['paid', 'partial'], true)) {
            return $this->reconcile_successful_payment($booking_id, $after, $target_status, $event, $message, $source, $gross_amount, $before);
        }

        $settings = $this->settings->all();
        if ($target_status !== 'processing') {
        $this->transactions->create([
            'booking_id' => $booking_id,
            'customer_email' => (string) ($after['customer_email'] ?? $before['customer_email'] ?? ''),
            'gateway' => $source === 'admin' ? 'admin' : sanitize_key($source),
            'transaction_type' => 'status_change',
            'status' => $target_status,
            'amount' => (float) ($after['paid_amount'] ?? $after['amount_due_now'] ?? $after['total_amount'] ?? 0),
            'currency' => CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR')),
            'external_id' => (string) ($after['external_payment_id'] ?? ''),
            'mode' => $source,
            'description' => $message,
            'metadata' => ['old_payment_status' => $before['payment_status'] ?? '', 'new_payment_status' => $target_status],
        ]);
        }
        $this->history->create([
            'booking_id' => $booking_id, 'event' => $event,
            'old_status' => $before['status'] ?? null, 'new_status' => $after['status'] ?? null,
            'old_payment_status' => $before['payment_status'] ?? null, 'new_payment_status' => $after['payment_status'] ?? null,
            'message' => $message,
        ]);
        if ($target_status === 'failed') {
            $this->notifications->payment_failed($booking_id, $after, $source, [], $message);
        }
        return true;
    }

    /**
     * Complete/recover all successful-payment side effects idempotently.
     * Returning false is intentional: gateway callers must not acknowledge a
     * delivery while the booking lifecycle is still inconsistent.
     */
    private function reconcile_successful_payment(int $booking_id, array $current, string $target_status, string $event, string $message, string $source, float $gross_amount, ?array $before_transition = null): bool
    {
        if ((string) ($current['status'] ?? '') === BookingLifecycleService::STATUS_PENDING_PAYMENT) {
            if (!$this->bookings->update_status($booking_id, BookingLifecycleService::STATUS_CONFIRMED)) {
                return false;
            }
            $current = $this->bookings->get_by_id($booking_id) ?: [];
            if (!$current || (string) ($current['status'] ?? '') !== BookingLifecycleService::STATUS_CONFIRMED) {
                return false;
            }
        }

        $gateway = $source === 'stripe_webhook' ? 'stripe' : (in_array($source, ['paypal_webhook', 'paypal_return'], true) ? 'paypal' : ($source === 'admin' ? 'admin' : sanitize_key($source)));
        $settings = $this->settings->all();
        $external_id = sanitize_text_field((string) ($current['external_payment_id'] ?? ''));
        if ($external_id === '') {
            $external_id = 'slotera-booking-' . $booking_id . '-' . $target_status;
        }
        $previous = $before_transition ?: $current;
        $description = $target_status === 'partial' ? 'Deposit payment received. Remaining balance is still due.' : $message;

        $transaction_id = $this->transactions->upsert_by_external_id([
            'booking_id' => $booking_id,
            'customer_email' => (string) ($current['customer_email'] ?? ''),
            'gateway' => $gateway,
            'transaction_type' => $target_status === 'partial' ? 'deposit' : 'status_change',
            'status' => $target_status,
            'amount' => (float) ($current['paid_amount'] ?? $current['amount_due_now'] ?? $current['total_amount'] ?? 0),
            'currency' => CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR')),
            'external_id' => $external_id,
            'mode' => $source,
            'description' => $description,
            'metadata' => [
                'old_payment_status' => $previous['payment_status'] ?? '', 'new_payment_status' => $target_status,
                'gross_amount' => $gross_amount, 'paid_amount' => (float) ($current['paid_amount'] ?? 0),
                'remaining_amount' => (float) ($current['remaining_amount'] ?? 0),
            ],
        ]);
        if ($transaction_id <= 0) { return false; }

        $history_event = $target_status === 'partial' ? 'deposit_paid' : $event;
        if ($this->history->count_event($booking_id, $history_event) === 0) {
            if ($this->history->create([
                'booking_id' => $booking_id, 'event' => $history_event,
                'old_status' => $previous['status'] ?? null, 'new_status' => $current['status'] ?? null,
                'old_payment_status' => $previous['payment_status'] ?? null, 'new_payment_status' => $current['payment_status'] ?? null,
                'message' => $description,
            ]) <= 0) { return false; }
        }

        if ((new InvoiceService($this->bookings))->sync_for_booking($booking_id) <= 0) { return false; }
        (new CouponService())->record_usage_for_booking($current);

        // Reserve the notification once. This intentionally favors no duplicate
        // customer emails when providers retry the same successful callback.
        if ($this->history->count_event($booking_id, 'payment_completed_notified') === 0) {
            if ($this->history->create([
                'booking_id' => $booking_id, 'event' => 'payment_completed_notified',
                'old_status' => $current['status'] ?? null, 'new_status' => $current['status'] ?? null,
                'old_payment_status' => $current['payment_status'] ?? null, 'new_payment_status' => $current['payment_status'] ?? null,
                'message' => 'Payment completion notification reserved.',
            ]) <= 0) { return false; }
            $this->notifications->payment_completed($booking_id, $current, '', ['source' => $source, 'payment_status' => $target_status]);
        }

        return true;
    }

}
