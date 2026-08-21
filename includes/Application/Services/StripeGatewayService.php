<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Infrastructure\Repositories\PaymentTransactionRepository;
use Slotera\Infrastructure\Repositories\WebhookEventRepository;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class StripeGatewayService
{
    private SettingsRepository $settings;
    private BookingRepository $bookings;
    private PackageRepository $packages;
    private PaymentTransactionRepository $transactions;
    private WebhookEventRepository $webhook_events;

    public function __construct(?SettingsRepository $settings = null, ?BookingRepository $bookings = null, ?PackageRepository $packages = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
        $this->bookings = $bookings ?: new BookingRepository();
        $this->packages = $packages ?: new PackageRepository();
        $this->transactions = new PaymentTransactionRepository();
        $this->webhook_events = new WebhookEventRepository();
    }

    public function is_configured(): bool
    {
        $settings = $this->settings->all();
        return $this->secret_key($settings) !== '';
    }

    public function is_test_mode(): bool
    {
        $settings = $this->settings->all();
        return sanitize_key((string) ($settings['payment_stripe_mode'] ?? 'test')) !== 'live';
    }

    public function publishable_key(): string
    {
        $settings = $this->settings->all();
        return sanitize_text_field((string) ($this->is_live($settings) ? ($settings['payment_stripe_live_publishable_key'] ?? '') : ($settings['payment_stripe_test_publishable_key'] ?? '')));
    }

    public function create_checkout_session(int $booking_id, array $context = [])
    {
        $booking = $this->bookings->get_by_id($booking_id);
        if (!$booking) {
            return new WP_Error('sltr_booking_not_found', __('Booking not found.', 'slotera-booking'));
        }

        $settings = $this->settings->all();
        $secret_key = $this->secret_key($settings);
        if ($secret_key === '') {
            return new WP_Error('sltr_stripe_not_configured', __('Stripe secret key is missing.', 'slotera-booking'));
        }

        $amount = (float) ($booking['amount_due_now'] ?? $booking['total_amount'] ?? 0);
        if ($amount <= 0) {
            return new WP_Error('sltr_stripe_invalid_amount', __('Stripe payment amount must be greater than zero.', 'slotera-booking'));
        }

        $currency = strtolower(CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR')));
        $zero_decimal = in_array(strtoupper($currency), ['BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'], true);
        $unit_amount = $zero_decimal ? (int) round($amount) : (int) round($amount * 100);

        $package = $this->packages->get_by_id((int) ($booking['package_id'] ?? 0));
        $product_name = (string) ($package['title'] ?? $package['name'] ?? __('Booking', 'slotera-booking'));
        if ($product_name === '') { $product_name = __('Booking', 'slotera-booking'); }
        $payment_choice = sanitize_key((string) ($booking['payment_choice'] ?? ($context['payment_mode'] ?? '')));
        if ($payment_choice === 'deposit_payment' || sanitize_key((string) ($context['payment_mode'] ?? '')) === 'prepay') {
            $product_name .= ' - ' . __('Deposit', 'slotera-booking');
        }

        $success_url = add_query_arg([
            'sltr_payment' => 'success',
            'booking_id' => $booking_id,
            'session_id' => '{CHECKOUT_SESSION_ID}',
        ], $this->return_base_url($booking));
        $cancel_url = add_query_arg([
            'sltr_payment' => 'failed',
            'booking_id' => $booking_id,
        ], $this->return_base_url($booking));

        $body = [
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'client_reference_id' => (string) $booking_id,
            'customer_email' => sanitize_email((string) ($booking['customer_email'] ?? '')),
            'payment_intent_data[metadata][slotera_booking_id]' => (string) $booking_id,
            'metadata[slotera_booking_id]' => (string) $booking_id,
            'metadata[slotera_payment_mode]' => sanitize_key((string) ($context['payment_mode'] ?? ($booking['payment_choice'] ?? ''))),
            'metadata[slotera_payment_choice]' => sanitize_key((string) ($booking['payment_choice'] ?? '')),
            'metadata[slotera_remaining_amount]' => (string) round((float) ($booking['remaining_amount'] ?? 0), 2),
            'metadata[slotera_selected_payment_method]' => sanitize_key((string) ($context['selected_payment_method'] ?? 'stripe')),
            'metadata[slotera_wallet]' => sanitize_key((string) ($context['wallet'] ?? '')),
            'payment_method_types[0]' => 'card',
            'line_items[0][quantity]' => '1',
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => (string) $unit_amount,
            'line_items[0][price_data][product_data][name]' => $product_name,
        ];

        $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => $body,
        ]);

        if (is_wp_error($response)) { return $response; }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            $message = is_array($data) && isset($data['error']['message']) ? (string) $data['error']['message'] : __('Stripe checkout session could not be created.', 'slotera-booking');
            return new WP_Error('sltr_stripe_session_failed', $message, ['status' => $code, 'response' => $data]);
        }

        $session_id = sanitize_text_field((string) ($data['id'] ?? ''));
        $checkout_url = esc_url_raw((string) ($data['url'] ?? ''));
        if ($session_id === '' || $checkout_url === '') {
            return new WP_Error('sltr_stripe_session_incomplete', __('Stripe did not return a checkout URL.', 'slotera-booking'));
        }

        $this->bookings->update($booking_id, [
            'payment_gateway' => 'stripe',
            'external_payment_id' => $session_id,
            'payment_redirect_url' => $checkout_url,
            'payment_status' => 'pending',
        ]);

        $this->transactions->upsert_by_external_id([
            'booking_id' => $booking_id,
            'customer_email' => (string) ($booking['customer_email'] ?? ''),
            'gateway' => 'stripe',
            'transaction_type' => 'payment',
            'status' => 'pending',
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'external_id' => $session_id,
            'mode' => $this->is_live($settings) ? 'live' : 'test',
            'description' => !empty($context['wallet']) ? ('Stripe Checkout session created for ' . sanitize_key((string) $context['wallet']) . '.') : 'Stripe Checkout session created.',
            'metadata' => ['payment_mode' => $context['payment_mode'] ?? '', 'checkout_url' => $checkout_url, 'selected_payment_method' => sanitize_key((string) ($context['selected_payment_method'] ?? 'stripe')), 'wallet' => sanitize_key((string) ($context['wallet'] ?? ''))],
        ]);

        return new PaymentResult([
            'status' => 'pending',
            'gateway' => 'stripe',
            'external_id' => $session_id,
            'redirect_url' => $checkout_url,
            'checkout_url' => $checkout_url,
            'payment_mode' => sanitize_key((string) ($context['payment_mode'] ?? '')),
            'offline' => false,
            'test_mode' => !$this->is_live($settings),
        ]);
    }

    public function verify_webhook_signature(string $payload, string $signature_header)
    {
        $settings = $this->settings->all();
        $secret = sanitize_text_field((string) ($settings['payment_stripe_webhook_secret'] ?? ''));
        if ($secret === '') {
            return new WP_Error('sltr_stripe_webhook_secret_missing', 'Stripe webhook secret is missing.');
        }
        if ($signature_header === '') {
            return new WP_Error('sltr_stripe_signature_missing', 'Stripe signature is missing.');
        }

        $timestamp = '';
        $signatures = [];
        foreach (explode(',', $signature_header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') { $timestamp = $value; }
            if ($key === 'v1') { $signatures[] = $value; }
        }
        if ($timestamp === '' || $signatures === []) {
            return new WP_Error('sltr_stripe_signature_invalid', 'Stripe signature format is invalid.');
        }
        if (abs(time() - (int) $timestamp) > 300) {
            return new WP_Error('sltr_stripe_signature_expired', 'Stripe signature timestamp is outside the allowed tolerance.');
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) { return true; }
        }
        return new WP_Error('sltr_stripe_signature_mismatch', 'Stripe signature verification failed.');
    }

    public function handle_webhook_event(array $event)
    {
        $type = sanitize_text_field((string) ($event['type'] ?? ''));
        $object = $event['data']['object'] ?? [];
        if (!is_array($object)) { return new WP_Error('sltr_stripe_invalid_event', 'Invalid Stripe event object.'); }
        $logger = new ActivityLogService();
        $event_id = sanitize_text_field((string) ($event['id'] ?? ''));

        if ($type === 'checkout.session.completed' || $type === 'checkout.session.async_payment_succeeded') {
            $booking_id = (int) ($object['metadata']['slotera_booking_id'] ?? $object['client_reference_id'] ?? 0);
            $logger->payment($booking_id, 'stripe_webhook_booking_match', 'stripe', $booking_id > 0 ? 'info' : 'warning', 'Stripe webhook booking match evaluated.', [
                'event_id' => $event_id,
                'event_type' => $type,
                'booking_id' => $booking_id,
                'session_id_present' => sanitize_text_field((string) ($object['id'] ?? '')) !== '',
            ]);
            if ($booking_id <= 0) { return new WP_Error('sltr_stripe_booking_missing', 'Booking id is missing in Stripe event.'); }
            $claim = $this->webhook_events->claim('stripe', $event_id, $event, $booking_id);
            if (is_wp_error($claim)) { return $claim; }
            if ($claim === 0) {
                $logger->payment($booking_id, 'stripe_webhook_duplicate', 'stripe', 'info', 'Duplicate Stripe webhook event ignored.', [
                    'event_id' => $event_id,
                    'event_type' => $type,
                ]);
                return true;
            }

            $validated = $this->validate_checkout_session($object, $booking_id);
            if (is_wp_error($validated)) {
                return $this->finish_webhook_event($claim, $validated);
            }
            $session_id = $validated['session_id'];
            $booking = $validated['booking'];
            $amount = $validated['amount'];
            $currency = $validated['currency'];

            // Checkout completes before delayed payment methods settle. Stripe
            // sends async_payment_succeeded later; only a session explicitly
            // reported as paid may settle on checkout.session.completed.
            if ($type === 'checkout.session.completed' && sanitize_key((string) ($object['payment_status'] ?? '')) !== 'paid') {
                $this->transactions->upsert_by_external_id([
                    'booking_id' => $booking_id,
                    'customer_email' => (string) ($booking['customer_email'] ?? ($object['customer_details']['email'] ?? '')),
                    'gateway' => 'stripe',
                    'transaction_type' => 'payment',
                    'status' => 'pending',
                    'amount' => $amount,
                    'currency' => $currency,
                    'external_id' => $session_id,
                    'external_parent_id' => sanitize_text_field((string) ($object['payment_intent'] ?? '')),
                    'mode' => !empty($object['livemode']) ? 'live' : 'test',
                    'description' => 'Stripe Checkout completed; asynchronous payment is pending.',
                    'metadata' => ['event_id' => $event['id'] ?? '', 'event_type' => $type, 'payment_status' => $object['payment_status'] ?? ''],
                ]);
                $this->webhook_events->mark_processed($claim);
                return true;
            }

            $this->transactions->upsert_by_external_id([
                'booking_id' => $booking_id,
                'customer_email' => (string) ($booking['customer_email'] ?? ($object['customer_details']['email'] ?? '')),
                'gateway' => 'stripe',
                'transaction_type' => 'payment',
                'status' => 'paid',
                'amount' => $amount,
                'currency' => $currency,
                'external_id' => $session_id,
                'external_parent_id' => sanitize_text_field((string) ($object['payment_intent'] ?? '')),
                'mode' => !empty($object['livemode']) ? 'live' : 'test',
                'description' => 'Stripe Checkout payment completed.',
                'metadata' => ['event_id' => $event['id'] ?? '', 'event_type' => $type],
            ]);
            $result = (new PaymentService())->mark_paid($booking_id, 'stripe_webhook');
            if (!is_wp_error($result) && $result !== false) {
                $logger->payment($booking_id, 'stripe_webhook_payment_applied', 'stripe', 'success', 'Stripe payment webhook applied to booking.', [
                    'event_id' => $event_id,
                    'event_type' => $type,
                    'payment_status' => sanitize_key((string) ($object['payment_status'] ?? '')),
                ]);
            }
            return $this->finish_webhook_event($claim, $result);
        }

        if ($type === 'checkout.session.expired' || $type === 'checkout.session.async_payment_failed') {
            $booking_id = (int) ($object['metadata']['slotera_booking_id'] ?? $object['client_reference_id'] ?? 0);
            if ($booking_id <= 0) { return true; }
            $logger->payment($booking_id, 'stripe_webhook_booking_match', 'stripe', 'info', 'Stripe webhook booking match evaluated.', [
                'event_id' => $event_id,
                'event_type' => $type,
                'booking_id' => $booking_id,
                'session_id_present' => sanitize_text_field((string) ($object['id'] ?? '')) !== '',
            ]);
            $claim = $this->webhook_events->claim('stripe', $event_id, $event, $booking_id);
            if (is_wp_error($claim)) { return $claim; }
            if ($claim === 0) {
                $logger->payment($booking_id, 'stripe_webhook_duplicate', 'stripe', 'info', 'Duplicate Stripe webhook event ignored.', [
                    'event_id' => $event_id,
                    'event_type' => $type,
                ]);
                return true;
            }
            $session_id = sanitize_text_field((string) ($object['id'] ?? ''));
            $booking = $this->bookings->get_by_id($booking_id) ?: [];
            $this->transactions->upsert_by_external_id([
                'booking_id' => $booking_id,
                'customer_email' => (string) ($booking['customer_email'] ?? ''),
                'gateway' => 'stripe',
                'transaction_type' => 'payment',
                'status' => 'failed',
                'amount' => (float) ($booking['amount_due_now'] ?? $booking['total_amount'] ?? 0),
                'currency' => strtoupper(sanitize_key((string) ($object['currency'] ?? 'EUR'))),
                'external_id' => $session_id,
                'mode' => !empty($object['livemode']) ? 'live' : 'test',
                'description' => 'Stripe Checkout payment failed or expired.',
                'metadata' => ['event_id' => $event['id'] ?? '', 'event_type' => $type],
            ]);
            $result = (new PaymentService())->mark_failed($booking_id, 'stripe_webhook', 'Stripe Checkout payment failed or expired.');
            if (!is_wp_error($result) && $result !== false) {
                $logger->payment($booking_id, 'stripe_webhook_failure_applied', 'stripe', 'success', 'Stripe failed/expired webhook applied to booking.', [
                    'event_id' => $event_id,
                    'event_type' => $type,
                ]);
            }
            return $this->finish_webhook_event($claim, $result);
        }

        $logger->payment(0, 'stripe_webhook_ignored', 'stripe', 'info', 'Stripe webhook event type is not handled by Slotera.', [
            'event_id' => $event_id,
            'event_type' => $type,
        ]);
        return true;
    }

    private function finish_webhook_event(int $claim_id, $result)
    {
        if (is_wp_error($result) || $result === false) {
            $message = is_wp_error($result) ? $result->get_error_message() : 'Payment state could not be updated.';
            $this->webhook_events->mark_failed($claim_id, $message);
            return $result;
        }
        $this->webhook_events->mark_processed($claim_id);
        return $result;
    }

    /**
     * @return array{booking:array,session_id:string,amount:float,currency:string}|WP_Error
     */
    private function validate_checkout_session(array $session, int $booking_id)
    {
        $booking = $this->bookings->get_by_id($booking_id);
        if (!$booking) {
            return new WP_Error('sltr_stripe_booking_missing', 'Booking linked to the Stripe payment was not found.');
        }

        $metadata_booking_id = absint($session['metadata']['slotera_booking_id'] ?? 0);
        $reference_booking_id = absint($session['client_reference_id'] ?? 0);
        if (($metadata_booking_id > 0 && $metadata_booking_id !== $booking_id)
            || ($reference_booking_id > 0 && $reference_booking_id !== $booking_id)) {
            return new WP_Error('sltr_stripe_booking_mismatch', 'Stripe Checkout booking reference does not match.');
        }

        $session_id = sanitize_text_field((string) ($session['id'] ?? ''));
        $stored_session_id = sanitize_text_field((string) ($booking['external_payment_id'] ?? ''));
        if ($session_id === '' || $stored_session_id === '' || !hash_equals($stored_session_id, $session_id)) {
            return new WP_Error('sltr_stripe_session_mismatch', 'Stripe Checkout session does not match the stored booking payment.');
        }
        if (sanitize_key((string) ($booking['payment_gateway'] ?? '')) !== 'stripe') {
            return new WP_Error('sltr_stripe_gateway_mismatch', 'Booking is not assigned to the Stripe gateway.');
        }

        $currency_raw = strtoupper(sanitize_text_field((string) ($session['currency'] ?? '')));
        $currency = CurrencyService::normalize($currency_raw);
        $expected_currency = CurrencyService::normalize((string) ($this->settings->all()['payment_currency'] ?? 'EUR'));
        if ($currency_raw === '' || $currency !== $expected_currency) {
            return new WP_Error('sltr_stripe_currency_mismatch', 'Stripe Checkout currency does not match the booking.');
        }
        if (!isset($session['amount_total']) || !is_numeric($session['amount_total'])) {
            return new WP_Error('sltr_stripe_amount_missing', 'Stripe Checkout amount is missing.');
        }

        $minor_amount = (float) $session['amount_total'];
        $amount = $this->is_zero_decimal_currency($currency) ? $minor_amount : $minor_amount / 100;
        $amount = round($amount, 2);
        $expected_amount = round((float) ($booking['amount_due_now'] ?? $booking['total_amount'] ?? 0), 2);
        if ($amount <= 0 || abs($amount - $expected_amount) > 0.001) {
            return new WP_Error('sltr_stripe_amount_mismatch', 'Stripe Checkout amount does not match the booking.');
        }

        return ['booking' => $booking, 'session_id' => $session_id, 'amount' => $amount, 'currency' => $currency];
    }

    private function is_zero_decimal_currency(string $currency): bool
    {
        return in_array(strtoupper($currency), ['BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'], true);
    }


    public function create_refund(int $booking_id, float $amount, string $payment_intent, string $reason = 'requested_by_customer')
    {
        $settings = $this->settings->all();
        $secret_key = $this->secret_key($settings);
        if ($secret_key === '') {
            return new WP_Error('sltr_stripe_not_configured', __('Stripe secret key is missing.', 'slotera-booking'));
        }

        $payment_intent = sanitize_text_field($payment_intent);
        if ($payment_intent === '') {
            return new WP_Error('sltr_stripe_payment_intent_missing', __('Stripe payment intent is missing.', 'slotera-booking'));
        }

        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) {
            return new WP_Error('sltr_stripe_refund_amount_invalid', __('Refund amount must be greater than zero.', 'slotera-booking'));
        }

        $currency = strtolower(CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR')));
        $zero_decimal = in_array(strtoupper($currency), ['BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'], true);
        $stripe_amount = $zero_decimal ? (int) round($amount) : (int) round($amount * 100);
        $reason = sanitize_key($reason);
        if (!in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'], true)) {
            $reason = 'requested_by_customer';
        }

        $response = wp_remote_post('https://api.stripe.com/v1/refunds', [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Idempotency-Key' => 'slotera-refund-' . $booking_id . '-' . md5($payment_intent . '|' . $amount . '|' . $reason),
            ],
            'body' => [
                'payment_intent' => $payment_intent,
                'amount' => (string) $stripe_amount,
                'reason' => $reason,
                'metadata[slotera_booking_id]' => (string) $booking_id,
            ],
        ]);

        if (is_wp_error($response)) { return $response; }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            $message = is_array($data) && isset($data['error']['message']) ? (string) $data['error']['message'] : __('Stripe refund could not be created.', 'slotera-booking');
            return new WP_Error('sltr_stripe_refund_failed', $message, ['status' => $code, 'response' => $data]);
        }

        return $data;
    }

    private function secret_key(array $settings): string
    {
        return sanitize_text_field((string) ($this->is_live($settings) ? ($settings['payment_stripe_live_secret_key'] ?? '') : ($settings['payment_stripe_test_secret_key'] ?? '')));
    }

    private function is_live(array $settings): bool
    {
        return sanitize_key((string) ($settings['payment_stripe_mode'] ?? 'test')) === 'live';
    }

    private function return_base_url(array $booking): string
    {
        $url = $this->settings->get_page_url('thank_you');
        if ($url === '') { $url = home_url('/'); }
        $access_code = (new BookingAccessTokenService())->issue_code($booking);
        if ($access_code !== '') {
            $url = add_query_arg(['booking_id' => (int) ($booking['id'] ?? 0), 'sltr_access_code' => $access_code], $url);
        }
        return esc_url_raw($url);
    }
}
