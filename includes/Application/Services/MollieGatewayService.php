<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\PaymentTransactionRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Infrastructure\Http\ClientIpResolver;
use Slotera\Infrastructure\Http\RateLimiter;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

/**
 * Mollie gateway for EU checkout methods (iDEAL, Bancontact, SEPA, cards, Klarna, etc.).
 *
 * Slotera uses Mollie's hosted checkout so individual EU methods are managed in the
 * merchant's Mollie dashboard instead of being separate Slotera gateway stubs.
 */
final class MollieGatewayService
{
    private SettingsRepository $settings;
    private BookingRepository $bookings;
    private PaymentTransactionRepository $transactions;

    public function __construct(?SettingsRepository $settings = null, ?BookingRepository $bookings = null, ?PaymentTransactionRepository $transactions = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
        $this->bookings = $bookings ?: new BookingRepository();
        $this->transactions = $transactions ?: new PaymentTransactionRepository();
    }

    public function is_test_mode(): bool
    {
        return !$this->is_live($this->settings->all());
    }

    public function create_payment(int $booking_id, array $context = [])
    {
        $booking = $this->bookings->get_by_id($booking_id);
        if (!$booking) { return new WP_Error('sltr_booking_not_found', __('Booking not found.', 'slotera-booking')); }

        $settings = $this->settings->all();
        $api_key = $this->api_key($settings);
        if ($api_key === '') { return new WP_Error('sltr_mollie_api_key_missing', __('Mollie API key is missing.', 'slotera-booking')); }

        $currency = CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
        $amount = (float) ($booking['amount_due_now'] ?? $booking['total_amount'] ?? 0);
        if ($amount <= 0) { return new WP_Error('sltr_mollie_amount_invalid', __('Payment amount must be greater than zero.', 'slotera-booking')); }

        $redirect_url = $this->redirect_url($booking_id, $booking);
        if ($redirect_url === '') { return new WP_Error('sltr_mollie_redirect_missing', __('Payment return page is not configured.', 'slotera-booking')); }

        $body = [
            'amount' => [
                'currency' => $currency,
                'value' => number_format($amount, 2, '.', ''),
            ],
            'description' => sprintf(__('Booking #%d', 'slotera-booking'), $booking_id),
            'redirectUrl' => $redirect_url,
            'webhookUrl' => rest_url('slotera/v1/payments/mollie/webhook'),
            'metadata' => [
                'booking_id' => $booking_id,
                'payment_mode' => sanitize_key((string) ($context['payment_mode'] ?? '')),
            ],
            'locale' => $this->mollie_locale(),
        ];

        $method = sanitize_key((string) ($settings['payment_mollie_method'] ?? ''));
        if ($method !== '' && $method !== 'all') { $body['method'] = $method; }

        $response = $this->request('POST', '/v2/payments', $body);
        if (is_wp_error($response)) { return $response; }

        $payment_id = sanitize_text_field((string) ($response['id'] ?? ''));
        $checkout_url = esc_url_raw((string) ($response['_links']['checkout']['href'] ?? ''));
        if ($payment_id === '' || $checkout_url === '') {
            return new WP_Error('sltr_mollie_payment_incomplete', __('Mollie did not return a checkout URL.', 'slotera-booking'));
        }

        $this->bookings->update($booking_id, [
            'payment_gateway' => 'mollie',
            'external_payment_id' => $payment_id,
            'payment_redirect_url' => $checkout_url,
            'payment_status' => 'pending',
        ]);

        $this->transactions->upsert_by_external_id([
            'booking_id' => $booking_id,
            'customer_email' => (string) ($booking['customer_email'] ?? ''),
            'gateway' => 'mollie',
            'transaction_type' => 'payment',
            'status' => 'pending',
            'amount' => $amount,
            'currency' => $currency,
            'external_id' => $payment_id,
            'mode' => $this->is_live($settings) ? 'live' : 'test',
            'description' => 'Mollie payment created.',
            'metadata' => ['payment_mode' => $context['payment_mode'] ?? '', 'checkout_url' => $checkout_url, 'method' => $method ?: 'all'],
        ]);

        return new PaymentResult([
            'status' => 'pending',
            'gateway' => 'mollie',
            'external_id' => $payment_id,
            'redirect_url' => $checkout_url,
            'checkout_url' => $checkout_url,
            'payment_mode' => sanitize_key((string) ($context['payment_mode'] ?? '')),
            'offline' => false,
            'test_mode' => !$this->is_live($settings),
        ]);
    }

    public function handle_webhook_payment_id(string $payment_id)
    {
        $payment_id = sanitize_text_field($payment_id);
        if ($payment_id === '') { return new WP_Error('sltr_mollie_payment_id_missing', 'Mollie payment id is missing.'); }

        $transaction = $this->transactions->get_by_external_id('mollie', $payment_id);
        if (!$transaction) {
            return new WP_Error('sltr_mollie_payment_unknown', 'Mollie payment is not linked to a local transaction.');
        }
        $local_status = sanitize_key((string) ($transaction['status'] ?? ''));
        if (in_array($local_status, ['paid', 'failed', 'refunded'], true)) { return true; }
        if ($local_status !== 'pending') {
            return new WP_Error('sltr_mollie_payment_not_pending', 'Mollie payment is not pending.');
        }

        $booking_id = absint($transaction['booking_id'] ?? 0);
        $local_booking = $booking_id > 0 ? $this->bookings->get_by_id($booking_id) : null;
        if (!$local_booking || sanitize_key((string) ($local_booking['payment_gateway'] ?? '')) !== 'mollie' || !hash_equals((string) ($local_booking['external_payment_id'] ?? ''), $payment_id)) {
            return new WP_Error('sltr_mollie_booking_mismatch', 'Mollie payment does not match the local booking.');
        }

        $identity = ClientIpResolver::get_client_ip();
        if (RateLimiter::increment('mollie_webhook', $identity !== '' ? $identity : 'unknown', 60) > 30) {
            return new WP_Error('sltr_mollie_webhook_rate_limited', 'Too many Mollie webhook requests.', ['status' => 429]);
        }

        $lock_key = '_sltr_mollie_webhook_lock_' . hash('sha256', $payment_id);
        if (!add_option($lock_key, time(), '', false)) {
            $locked_at = (int) get_option($lock_key, 0);
            if ($locked_at <= 0 || (time() - $locked_at) <= 120) { return true; }
            delete_option($lock_key);
            if (!add_option($lock_key, time(), '', false)) { return true; }
        }
        try {
            $payment = $this->get_payment($payment_id);
        } finally {
            delete_option($lock_key);
        }
        if (is_wp_error($payment)) { return $payment; }

        $provider_booking_id = absint($payment['metadata']['booking_id'] ?? 0);
        if ($provider_booking_id <= 0 || $provider_booking_id !== $booking_id) {
            return new WP_Error('sltr_mollie_booking_mismatch', 'Mollie payment metadata does not match the local booking.');
        }

        $booking = $this->bookings->get_by_id($booking_id) ?: [];
        $status = sanitize_key((string) ($payment['status'] ?? ''));
        $amount_value = (float) ($payment['amount']['value'] ?? ($booking['amount_due_now'] ?? $booking['total_amount'] ?? 0));
        $currency = CurrencyService::normalize((string) ($payment['amount']['currency'] ?? ($this->settings->all()['payment_currency'] ?? 'EUR')));
        $method = sanitize_key((string) ($payment['method'] ?? ''));

        $mapped = in_array($status, ['paid', 'authorized'], true) ? 'paid' : (in_array($status, ['failed', 'expired', 'canceled'], true) ? 'failed' : 'pending');
        $this->transactions->upsert_by_external_id([
            'booking_id' => $booking_id,
            'customer_email' => (string) ($booking['customer_email'] ?? ''),
            'gateway' => 'mollie',
            'transaction_type' => 'payment',
            'status' => $mapped,
            'amount' => $amount_value,
            'currency' => $currency,
            'external_id' => $payment_id,
            'mode' => !empty($payment['testmode']) ? 'test' : 'live',
            'description' => 'Mollie payment webhook: ' . $status,
            'metadata' => ['mollie_status' => $status, 'method' => $method],
        ]);

        if ($mapped === 'paid') { return (new PaymentService())->mark_paid($booking_id, 'mollie_webhook'); }
        if ($mapped === 'failed') { return (new PaymentService())->mark_failed($booking_id, 'mollie_webhook', 'Mollie payment failed, expired or was canceled.'); }
        return true;
    }

    public function create_refund(int $booking_id, float $amount, string $payment_id, string $reason = 'requested_by_customer')
    {
        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) { return new WP_Error('sltr_mollie_refund_amount_invalid', __('Refund amount must be greater than zero.', 'slotera-booking')); }
        $settings = $this->settings->all();
        $currency = CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
        $payment_id = sanitize_text_field($payment_id);
        if ($payment_id === '') { return new WP_Error('sltr_mollie_payment_id_missing', __('Mollie payment id is missing.', 'slotera-booking')); }

        $body = [
            'amount' => ['currency' => $currency, 'value' => number_format($amount, 2, '.', '')],
            'description' => substr(sprintf(__('Refund for booking #%d', 'slotera-booking'), $booking_id), 0, 140),
            'metadata' => ['booking_id' => $booking_id, 'reason' => sanitize_key($reason)],
        ];
        $response = $this->request('POST', '/v2/payments/' . rawurlencode($payment_id) . '/refunds', $body);
        if (is_wp_error($response)) { return $response; }
        return $response;
    }

    private function get_payment(string $payment_id)
    {
        return $this->request('GET', '/v2/payments/' . rawurlencode($payment_id));
    }

    private function request(string $method, string $path, ?array $body = null)
    {
        $settings = $this->settings->all();
        $api_key = $this->api_key($settings);
        if ($api_key === '') { return new WP_Error('sltr_mollie_api_key_missing', __('Mollie API key is missing.', 'slotera-booking')); }

        $args = [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'method' => strtoupper($method),
        ];
        if ($body !== null) { $args['body'] = wp_json_encode($body); }
        $response = wp_remote_request('https://api.mollie.com' . $path, $args);
        if (is_wp_error($response)) { return $response; }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            $message = is_array($data) && isset($data['detail']) ? (string) $data['detail'] : __('Mollie API request failed.', 'slotera-booking');
            return new WP_Error('sltr_mollie_api_failed', $message, ['status' => $code, 'response' => $data]);
        }
        return $data;
    }

    private function api_key(array $settings): string
    {
        $mode = sanitize_key((string) ($settings['payment_mollie_mode'] ?? 'test'));
        if ($mode === 'live') { return trim((string) ($settings['payment_mollie_live_api_key'] ?? $settings['payment_mollie_api_key'] ?? '')); }
        return trim((string) ($settings['payment_mollie_test_api_key'] ?? $settings['payment_mollie_api_key'] ?? ''));
    }

    private function is_live(array $settings): bool
    {
        return sanitize_key((string) ($settings['payment_mollie_mode'] ?? 'test')) === 'live';
    }

    private function redirect_url(int $booking_id, array $booking): string
    {
        $url = $this->settings->get_page_url('thank_you');
        if ($url === '') { return ''; }
        $access_code = (new BookingAccessTokenService())->issue_code($booking);
        if ($access_code === '') { return ''; }
        return esc_url_raw(add_query_arg([
            'booking_id' => $booking_id,
            'sltr_access_code' => $access_code,
            'sltr_payment' => 'pending',
        ], $url));
    }

    private function mollie_locale(): string
    {
        $locale = determine_locale();
        return preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale) ? $locale : 'en_US';
    }
}
