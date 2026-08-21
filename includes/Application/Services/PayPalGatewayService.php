<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Application\Security\SecretStore;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Infrastructure\Repositories\PaymentTransactionRepository;
use Slotera\Infrastructure\Repositories\WebhookEventRepository;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class PayPalGatewayService
{
    public const RECONCILE_CRON_HOOK = 'sltr_paypal_reconcile_processing';
    public const RECONCILE_CRON_SCHEDULE = 'sltr_every_fifteen_minutes';
    public const RECONCILE_STATE_OPTION = 'sltr_paypal_reconcile_state';
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


    public function register_hooks(): void
    {
        add_filter('cron_schedules', [$this, 'add_cron_schedule']);
        add_action(self::RECONCILE_CRON_HOOK, [$this, 'reconcile_processing_payments']);
    }

    public function add_cron_schedule(array $schedules): array
    {
        $schedules[self::RECONCILE_CRON_SCHEDULE] = ['interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Every fifteen minutes'];
        return $schedules;
    }

    public function reconcile_processing_payments(): array
    {
        $started_at = current_time('mysql', true);
        $rows = $this->transactions->search(['gateway' => 'paypal', 'status' => 'processing'], 100, 0);
        $summary = ['checked' => 0, 'completed' => 0, 'failed' => 0, 'pending' => 0, 'errors' => 0, 'started_at' => $started_at, 'finished_at' => '', 'results' => []];
        $cutoff = time() - 5 * MINUTE_IN_SECONDS;
        foreach ($rows as $row) {
            $updated = strtotime((string) ($row['updated_at'] ?? $row['created_at'] ?? '')) ?: 0;
            if ($updated > $cutoff) { continue; }
            $capture_id = sanitize_text_field((string) ($row['external_parent_id'] ?? ''));
            $order_id = sanitize_text_field((string) ($row['external_id'] ?? ''));
            $booking_id = absint($row['booking_id'] ?? 0);
            if ($capture_id === '' || $order_id === '' || $booking_id <= 0) { continue; }
            $summary['checked']++;
            $result_detail = ['booking_id' => $booking_id, 'capture_id' => $capture_id, 'order_id' => $order_id, 'status' => '', 'reason' => '', 'outcome' => ''];
            $capture = $this->get_capture($capture_id);
            if (is_wp_error($capture)) {
                $summary['errors']++;
                $result_detail['outcome'] = 'error';
                $result_detail['reason'] = sanitize_text_field($capture->get_error_message());
                $summary['results'][] = $result_detail;
                continue;
            }
            $status = strtoupper(sanitize_text_field((string) ($capture['status'] ?? '')));
            $reason = strtoupper(sanitize_text_field((string) (($capture['status_details']['reason'] ?? $capture['pending_reason'] ?? ''))));
            $result_detail['status'] = $status;
            $result_detail['reason'] = $reason;
            if ($status === 'COMPLETED') {
                $result = $this->record_capture_resource($capture, $booking_id, $order_id, 'paypal_reconciliation', $capture);
                if (is_wp_error($result) || $result === false) { $summary['errors']++; $result_detail['outcome'] = 'error'; } else { $summary['completed']++; $result_detail['outcome'] = 'completed'; }
            } elseif (in_array($status, ['DENIED', 'DECLINED', 'FAILED', 'REVERSED'], true)) {
                $booking = $this->bookings->get_by_id($booking_id) ?: [];
                $this->transactions->upsert_by_external_id([
                    'booking_id' => $booking_id, 'customer_email' => (string) ($booking['customer_email'] ?? ''),
                    'gateway' => 'paypal', 'transaction_type' => 'payment', 'status' => 'failed',
                    'amount' => (float) ($row['amount'] ?? 0), 'currency' => (string) ($row['currency'] ?? 'EUR'),
                    'external_id' => $order_id, 'external_parent_id' => $capture_id,
                    'mode' => (string) ($row['mode'] ?? ($this->is_test_mode() ? 'sandbox' : 'live')),
                    'description' => 'PayPal payment did not complete.',
                    'metadata' => ['source' => 'paypal_reconciliation', 'capture_id' => $capture_id, 'capture_status' => $status, 'pending_reason' => $reason],
                ]);
                (new PaymentService())->mark_failed($booking_id, 'paypal_reconciliation', 'PayPal payment did not complete (' . $status . ').');
                $summary['failed']++;
                $result_detail['outcome'] = 'failed';
            } else {
                $summary['pending']++;
                $result_detail['outcome'] = 'pending';
            }
            $summary['results'][] = $result_detail;
        }
        $summary['results'] = array_slice($summary['results'], 0, 20);
        $summary['finished_at'] = current_time('mysql', true);
        update_option(self::RECONCILE_STATE_OPTION, $summary, false);
        return $summary;
    }

    public function reconciliation_state(): array
    {
        $state = get_option(self::RECONCILE_STATE_OPTION, []);
        return is_array($state) ? $state : [];
    }

    private function get_capture(string $capture_id)
    {
        $settings = $this->settings->all();
        $access_token = $this->access_token($settings);
        if (is_wp_error($access_token)) { return $access_token; }
        $response = wp_remote_get($this->api_base($settings) . '/v2/payments/captures/' . rawurlencode($capture_id), [
            'timeout' => 25, 'headers' => ['Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json'],
        ]);
        if (is_wp_error($response)) { return $response; }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            return new WP_Error('sltr_paypal_capture_lookup_failed', 'PayPal capture status could not be retrieved.', ['status' => $code]);
        }
        return $data;
    }

    public function is_configured(): bool
    {
        $settings = $this->settings->all();
        return $this->client_id($settings) !== '' && $this->client_secret($settings) !== '';
    }

    public function is_test_mode(): bool
    {
        $settings = $this->settings->all();
        return sanitize_key((string) ($settings['payment_paypal_mode'] ?? 'sandbox')) !== 'live';
    }

    public function create_order(int $booking_id, array $context = [])
    {
        $booking = $this->bookings->get_by_id($booking_id);
        if (!$booking) {
            return new WP_Error('sltr_booking_not_found', __('Booking not found.', 'slotera-booking'));
        }

        $settings = $this->settings->all();
        $access_token = $this->access_token($settings);
        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $amount = round((float) ($booking['amount_due_now'] ?? $booking['total_amount'] ?? 0), 2);
        if ($amount <= 0) {
            return new WP_Error('sltr_paypal_invalid_amount', __('PayPal payment amount must be greater than zero.', 'slotera-booking'));
        }

        $currency = CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
        $package = $this->packages->get_by_id((int) ($booking['package_id'] ?? 0));
        $description = (string) ($package['title'] ?? $package['name'] ?? __('Booking', 'slotera-booking'));
        if ($description === '') { $description = __('Booking', 'slotera-booking'); }
        if (sanitize_key((string) ($booking['payment_choice'] ?? ($context['payment_mode'] ?? ''))) === 'deposit_payment' || sanitize_key((string) ($context['payment_mode'] ?? '')) === 'prepay') {
            $description .= ' - ' . __('Deposit', 'slotera-booking');
        }

        $return_url = add_query_arg([
            'booking_id' => $booking_id,
        ], rest_url('slotera/v1/payments/paypal/return'));
        $cancel_url = add_query_arg([
            'sltr_payment' => 'failed',
            'booking_id' => $booking_id,
        ], $this->return_base_url($booking));

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'booking_' . $booking_id,
                'custom_id' => (string) $booking_id,
                'description' => substr($description, 0, 120),
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'brand_name' => substr((string) get_bloginfo('name'), 0, 127),
                'user_action' => 'PAY_NOW',
                'return_url' => $return_url,
                'cancel_url' => $cancel_url,
            ],
        ];

        $response = wp_remote_post($this->api_base($settings) . '/v2/checkout/orders', [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'PayPal-Request-Id' => 'slotera-order-' . $booking_id . '-' . md5((string) microtime(true)),
            ],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            $message = is_array($data) && isset($data['message']) ? (string) $data['message'] : __('PayPal order could not be created.', 'slotera-booking');
            return new WP_Error('sltr_paypal_order_failed', $message, ['status' => $code, 'response' => $data]);
        }

        $order_id = sanitize_text_field((string) ($data['id'] ?? ''));
        $approve_url = '';
        foreach ((array) ($data['links'] ?? []) as $link) {
            if (is_array($link) && (string) ($link['rel'] ?? '') === 'approve') {
                $approve_url = esc_url_raw((string) ($link['href'] ?? ''));
                break;
            }
        }
        if ($order_id === '' || $approve_url === '') {
            return new WP_Error('sltr_paypal_order_incomplete', __('PayPal did not return an approval URL.', 'slotera-booking'));
        }


        $this->bookings->update($booking_id, [
            'payment_gateway' => 'paypal',
            'external_payment_id' => $order_id,
            'payment_redirect_url' => $approve_url,
            'payment_status' => 'pending',
        ]);

        $this->transactions->upsert_by_external_id([
            'booking_id' => $booking_id,
            'customer_email' => (string) ($booking['customer_email'] ?? ''),
            'gateway' => 'paypal',
            'transaction_type' => 'payment',
            'status' => 'pending',
            'amount' => $amount,
            'currency' => $currency,
            'external_id' => $order_id,
            'mode' => $this->is_live($settings) ? 'live' : 'sandbox',
            'description' => 'PayPal order created.',
            'metadata' => ['payment_mode' => $context['payment_mode'] ?? '', 'approve_url' => $approve_url],
        ]);

        return new PaymentResult([
            'status' => 'pending',
            'gateway' => 'paypal',
            'external_id' => $order_id,
            'redirect_url' => $approve_url,
            'checkout_url' => $approve_url,
            'payment_mode' => sanitize_key((string) ($context['payment_mode'] ?? '')),
            'offline' => false,
            'test_mode' => !$this->is_live($settings),
        ]);
    }

    public function capture_order(string $order_id, int $booking_id = 0)
    {
        $order_id = sanitize_text_field($order_id);
        if ($order_id === '') { return new WP_Error('sltr_paypal_order_missing', __('PayPal order id is missing.', 'slotera-booking')); }

        $booking = $this->bookings->get_by_external_payment_id($order_id);
        if (!$booking || sanitize_key((string) ($booking['payment_gateway'] ?? '')) !== 'paypal') {
            return new WP_Error('sltr_paypal_order_not_linked', __('PayPal order is not linked to a pending booking.', 'slotera-booking'));
        }
        $linked_booking_id = absint($booking['id'] ?? 0);
        if ($linked_booking_id <= 0 || ($booking_id > 0 && $booking_id !== $linked_booking_id)) {
            return new WP_Error('sltr_paypal_booking_mismatch', __('PayPal order does not belong to this booking.', 'slotera-booking'));
        }
        $settings = $this->settings->all();
        $access_token = $this->access_token($settings);
        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $response = wp_remote_post($this->api_base($settings) . '/v2/checkout/orders/' . rawurlencode($order_id) . '/capture', [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
                'PayPal-Request-Id' => 'slotera-capture-' . md5($order_id),
            ],
            'body' => '{}',
        ]);

        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            $message = is_array($data) && isset($data['message']) ? (string) $data['message'] : __('PayPal order could not be captured.', 'slotera-booking');
            return new WP_Error('sltr_paypal_capture_failed', $message, ['status' => $code, 'response' => $data]);
        }

        $result = $this->record_captured_order($data, $linked_booking_id, 'paypal_return');
        if (is_wp_error($result)) {
            return $result;
        }
        return $result;
    }

    /**
     * Read-only PayPal app/webhook registration diagnostics for the active mode.
     * Results are cached briefly because DiagnosticsService evaluates groups more than once per page load.
     */
    public function webhook_registration_health(bool $force = false): array
    {
        $settings = $this->settings->all();
        $mode = $this->is_live($settings) ? 'live' : 'sandbox';
        $webhook_id = sanitize_text_field((string) ($settings['payment_paypal_webhook_id'] ?? ''));
        $expected_url = esc_url_raw(rest_url('slotera/v1/payments/paypal/webhook'));
        $client_id = $this->client_id($settings);
        $cache_key = 'sltr_pp_wh_' . substr(hash('sha256', $mode . '|' . $client_id . '|' . $webhook_id . '|' . $expected_url), 0, 24);
        if (!$force) {
            $cached = get_transient($cache_key);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $health = [
            'mode' => $mode,
            'configured' => $client_id !== '' && $this->client_secret($settings) !== '' && $webhook_id !== '',
            'webhook_id' => $webhook_id,
            'expected_url' => $expected_url,
            'api_authenticated' => false,
            'webhooks_count' => null,
            'webhook_found' => false,
            'actual_url' => '',
            'url_matches' => false,
            'subscriptions' => [],
            'error_code' => '',
            'error_message' => '',
        ];
        if (!$health['configured']) {
            $health['error_code'] = 'not_configured';
            $health['error_message'] = 'PayPal credentials or webhook ID are missing for the active mode.';
            return $health;
        }

        $access_token = $this->access_token($settings);
        if (is_wp_error($access_token)) {
            $health['error_code'] = sanitize_key((string) $access_token->get_error_code());
            $health['error_message'] = sanitize_text_field($access_token->get_error_message());
            set_transient($cache_key, $health, 5 * MINUTE_IN_SECONDS);
            return $health;
        }
        $health['api_authenticated'] = true;

        $list = $this->paypal_diagnostic_get($settings, $access_token, '/v1/notifications/webhooks');
        if (is_wp_error($list)) {
            $health['error_code'] = sanitize_key((string) $list->get_error_code());
            $health['error_message'] = sanitize_text_field($list->get_error_message());
            set_transient($cache_key, $health, 5 * MINUTE_IN_SECONDS);
            return $health;
        }
        $webhooks = is_array($list['webhooks'] ?? null) ? $list['webhooks'] : [];
        $health['webhooks_count'] = count($webhooks);
        foreach ($webhooks as $webhook) {
            if (!is_array($webhook) || sanitize_text_field((string) ($webhook['id'] ?? '')) !== $webhook_id) {
                continue;
            }
            $health['webhook_found'] = true;
            $health['actual_url'] = esc_url_raw((string) ($webhook['url'] ?? ''));
            $health['url_matches'] = untrailingslashit($health['actual_url']) === untrailingslashit($expected_url);
            break;
        }

        if ($health['webhook_found']) {
            $subscriptions = $this->paypal_diagnostic_get($settings, $access_token, '/v1/notifications/webhooks/' . rawurlencode($webhook_id) . '/event-types');
            if (!is_wp_error($subscriptions)) {
                foreach ((array) ($subscriptions['event_types'] ?? []) as $event_type) {
                    if (!is_array($event_type)) { continue; }
                    $name = sanitize_text_field((string) ($event_type['name'] ?? ''));
                    if ($name === '') { continue; }
                    $health['subscriptions'][$name] = strtoupper(sanitize_text_field((string) ($event_type['status'] ?? 'ENABLED')));
                }
            } else {
                $health['error_code'] = sanitize_key((string) $subscriptions->get_error_code());
                $health['error_message'] = sanitize_text_field($subscriptions->get_error_message());
            }
        }

        set_transient($cache_key, $health, 5 * MINUTE_IN_SECONDS);
        return $health;
    }

    private function paypal_diagnostic_get(array $settings, string $access_token, string $path)
    {
        $response = wp_remote_get($this->api_base($settings) . $path, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept' => 'application/json',
            ],
        ]);
        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            $message = is_array($data) && isset($data['message']) ? sanitize_text_field((string) $data['message']) : 'PayPal diagnostics request failed.';
            return new WP_Error('sltr_paypal_diagnostics_api_error', $message, ['status' => $code]);
        }
        return $data;
    }

    public function verify_webhook_event(array $headers, array $event)
    {
        $settings = $this->settings->all();
        $webhook_id = sanitize_text_field((string) ($settings['payment_paypal_webhook_id'] ?? ''));
        if ($webhook_id === '') {
            return new WP_Error('sltr_paypal_webhook_id_missing', 'PayPal webhook id is missing.');
        }
        $access_token = $this->access_token($settings);
        if (is_wp_error($access_token)) { return $access_token; }

        $body = [
            'auth_algo' => (string) ($headers['paypal-auth-algo'] ?? ''),
            'cert_url' => (string) ($headers['paypal-cert-url'] ?? ''),
            'transmission_id' => (string) ($headers['paypal-transmission-id'] ?? ''),
            'transmission_sig' => (string) ($headers['paypal-transmission-sig'] ?? ''),
            'transmission_time' => (string) ($headers['paypal-transmission-time'] ?? ''),
            'webhook_id' => $webhook_id,
            'webhook_event' => $event,
        ];

        $response = wp_remote_post($this->api_base($settings) . '/v1/notifications/verify-webhook-signature', [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ]);
        if (is_wp_error($response)) {
            (new ActivityLogService())->payment(0, 'paypal_webhook_verify_transport_error', 'paypal', 'error', 'PayPal webhook verification request failed.', [], $response->get_error_message());
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        (new ActivityLogService())->payment(0, 'paypal_webhook_verify_response', 'paypal', ($code >= 200 && $code < 300) ? 'info' : 'error', 'PayPal webhook verification response received.', [
            'http_status' => $code,
            'verification_status' => is_array($data) ? sanitize_key((string) ($data['verification_status'] ?? '')) : '',
            'paypal_debug_id' => is_array($data) ? sanitize_text_field((string) ($data['debug_id'] ?? '')) : '',
        ]);
        if ($code < 200 || $code >= 300 || !is_array($data) || (string) ($data['verification_status'] ?? '') !== 'SUCCESS') {
            return new WP_Error('sltr_paypal_webhook_verification_failed', 'PayPal webhook signature verification failed.', ['status' => $code, 'response' => $data]);
        }
        return true;
    }

    public function handle_webhook_event(array $event)
    {
        $type = sanitize_text_field((string) ($event['event_type'] ?? ''));
        $resource = $event['resource'] ?? [];
        if (!is_array($resource)) { return new WP_Error('sltr_paypal_invalid_event', 'Invalid PayPal event resource.'); }

        if ($type === 'PAYMENT.CAPTURE.PENDING') {
            $order_id = sanitize_text_field((string) ($resource['supplementary_data']['related_ids']['order_id'] ?? ''));
            $booking_id = $this->booking_id_from_resource($resource, $order_id);
            if ($booking_id <= 0) { return true; }
            return $this->record_capture_resource($resource, $booking_id, $order_id, 'paypal_webhook', $event);
        }

        if ($type === 'PAYMENT.CAPTURE.COMPLETED') {
            $order_id = sanitize_text_field((string) ($resource['supplementary_data']['related_ids']['order_id'] ?? ''));
            $booking_id = $this->booking_id_from_resource($resource, $order_id);
            (new ActivityLogService())->payment($booking_id, 'paypal_webhook_booking_match', 'paypal', $booking_id > 0 ? 'info' : 'warning', 'PayPal webhook booking match evaluated.', [
                'event_id' => sanitize_text_field((string) ($event['id'] ?? '')),
                'event_type' => $type,
                'order_id_present' => $order_id !== '',
                'booking_matched' => $booking_id > 0,
            ]);
            if ($booking_id <= 0) {
                (new ActivityLogService())->payment(0, 'paypal_webhook_ignored', 'paypal', 'warning', 'PayPal webhook ignored because no booking matched the event.', [
                    'event_id' => sanitize_text_field((string) ($event['id'] ?? '')),
                    'event_type' => $type,
                    'reason' => 'booking_not_found',
                ]);
                return true;
            }
            $claim = $this->webhook_events->claim('paypal', (string) ($event['id'] ?? ''), $event, $booking_id);
            if (is_wp_error($claim)) { return $claim; }
            if ($claim === 0) {
                (new ActivityLogService())->payment($booking_id, 'paypal_webhook_duplicate', 'paypal', 'info', 'Duplicate PayPal webhook event ignored.', [
                    'event_id' => sanitize_text_field((string) ($event['id'] ?? '')),
                    'event_type' => $type,
                ]);
                return true;
            }
            $result = $this->record_capture_resource($resource, $booking_id, $order_id, 'paypal_webhook', $event);
            $finished = $this->finish_webhook_event($claim, $result);
            if (!is_wp_error($finished) && $finished !== false) {
                (new ActivityLogService())->payment($booking_id, 'paypal_webhook_capture_processed', 'paypal', 'success', 'PayPal capture webhook applied to booking.', [
                    'event_id' => sanitize_text_field((string) ($event['id'] ?? '')),
                    'event_type' => $type,
                    'capture_id' => sanitize_text_field((string) ($resource['id'] ?? '')),
                    'order_id' => $order_id,
                ]);
            }
            return $finished;
        }

        if (in_array($type, ['PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED', 'PAYMENT.CAPTURE.REVERSED', 'CHECKOUT.ORDER.VOIDED'], true)) {
            $order_id = sanitize_text_field((string) ($resource['supplementary_data']['related_ids']['order_id'] ?? ($resource['id'] ?? '')));
            $booking_id = $this->booking_id_from_resource($resource, $order_id);
            if ($booking_id <= 0) { return true; }
            $claim = $this->webhook_events->claim('paypal', (string) ($event['id'] ?? ''), $event, $booking_id);
            if (is_wp_error($claim)) { return $claim; }
            if ($claim === 0) { return true; }
            $booking = $this->bookings->get_by_id($booking_id) ?: [];
            $this->transactions->upsert_by_external_id([
                'booking_id' => $booking_id,
                'customer_email' => (string) ($booking['customer_email'] ?? ''),
                'gateway' => 'paypal',
                'transaction_type' => 'payment',
                'status' => 'failed',
                'amount' => (float) ($booking['amount_due_now'] ?? $booking['total_amount'] ?? 0),
                'currency' => CurrencyService::normalize((string) ($this->settings->all()['payment_currency'] ?? 'EUR')),
                'external_id' => $order_id,
                'mode' => !empty($resource['seller_protection']) ? 'live' : ($this->is_test_mode() ? 'sandbox' : 'live'),
                'description' => 'PayPal payment failed or was voided.',
                'metadata' => ['event_id' => $event['id'] ?? '', 'event_type' => $type],
            ]);
            $result = (new PaymentService())->mark_failed($booking_id, 'paypal_webhook', 'PayPal payment failed or was voided.');
            return $this->finish_webhook_event($claim, $result);
        }

        (new ActivityLogService())->payment(0, 'paypal_webhook_ignored', 'paypal', 'info', 'PayPal webhook event type is not handled by Slotera.', [
            'event_id' => sanitize_text_field((string) ($event['id'] ?? '')),
            'event_type' => $type,
            'reason' => 'unsupported_event_type',
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

    public function create_refund(int $booking_id, float $amount, string $capture_id, string $reason = 'requested_by_customer')
    {
        $settings = $this->settings->all();
        $access_token = $this->access_token($settings);
        if (is_wp_error($access_token)) { return $access_token; }

        $capture_id = sanitize_text_field($capture_id);
        if ($capture_id === '') {
            return new WP_Error('sltr_paypal_capture_missing', __('PayPal capture id is missing.', 'slotera-booking'));
        }
        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) {
            return new WP_Error('sltr_paypal_refund_amount_invalid', __('Refund amount must be greater than zero.', 'slotera-booking'));
        }

        $currency = CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
        $response = wp_remote_post($this->api_base($settings) . '/v2/payments/captures/' . rawurlencode($capture_id) . '/refund', [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'PayPal-Request-Id' => 'slotera-refund-' . $booking_id . '-' . md5($capture_id . '|' . $amount . '|' . $reason),
            ],
            'body' => wp_json_encode([
                'amount' => ['value' => number_format($amount, 2, '.', ''), 'currency_code' => $currency],
                'note_to_payer' => sanitize_text_field($reason),
            ]),
        ]);
        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            $message = is_array($data) && isset($data['message']) ? (string) $data['message'] : __('PayPal refund could not be created.', 'slotera-booking');
            return new WP_Error('sltr_paypal_refund_failed', $message, ['status' => $code, 'response' => $data]);
        }
        return $data;
    }

    private function record_captured_order(array $order, int $booking_id, string $source)
    {
        $purchase_unit = $order['purchase_units'][0] ?? [];
        $capture = $purchase_unit['payments']['captures'][0] ?? [];
        if (!is_array($purchase_unit) || !is_array($capture)) {
            return new WP_Error('sltr_paypal_capture_missing', __('PayPal did not return capture details.', 'slotera-booking'));
        }
        $paypal_booking_id = absint($purchase_unit['custom_id'] ?? 0);
        if ($booking_id <= 0) { $booking_id = $paypal_booking_id; }
        if ($booking_id <= 0 || ($paypal_booking_id > 0 && $paypal_booking_id !== $booking_id)) {
            return new WP_Error('sltr_paypal_booking_mismatch', __('PayPal order booking reference does not match.', 'slotera-booking'));
        }

        $order_id = sanitize_text_field((string) ($order['id'] ?? ''));
        $booking = $this->bookings->get_by_id($booking_id);
        if (!$booking || $order_id === '' || !hash_equals((string) ($booking['external_payment_id'] ?? ''), $order_id)) {
            return new WP_Error('sltr_paypal_order_mismatch', __('PayPal order does not match the stored booking payment.', 'slotera-booking'));
        }

        $captured_amount = round((float) ($capture['amount']['value'] ?? -1), 2);
        $expected_amount = round((float) ($booking['amount_due_now'] ?? $booking['total_amount'] ?? 0), 2);
        $captured_currency_raw = strtoupper(sanitize_text_field((string) ($capture['amount']['currency_code'] ?? '')));
        $captured_currency = CurrencyService::normalize($captured_currency_raw);
        $expected_currency = CurrencyService::normalize((string) ($this->settings->all()['payment_currency'] ?? 'EUR'));
        if ($captured_amount <= 0 || abs($captured_amount - $expected_amount) > 0.001 || $captured_currency_raw === '' || $captured_currency !== $expected_currency) {
            return new WP_Error('sltr_paypal_amount_mismatch', __('PayPal captured amount or currency does not match the booking.', 'slotera-booking'));
        }

        return $this->record_capture_resource($capture, $booking_id, $order_id, $source, $order);
    }

    private function record_capture_resource(array $capture, int $booking_id, string $order_id, string $source, array $raw = [])
    {
        $booking = $this->bookings->get_by_id($booking_id);
        if (!$booking) {
            return new WP_Error('sltr_paypal_booking_missing', __('Booking linked to the PayPal payment was not found.', 'slotera-booking'));
        }
        $stored_order_id = sanitize_text_field((string) ($booking['external_payment_id'] ?? ''));
        if ($order_id === '' || $stored_order_id === '' || !hash_equals($stored_order_id, $order_id)) {
            return new WP_Error('sltr_paypal_order_mismatch', __('PayPal order does not match the stored booking payment.', 'slotera-booking'));
        }

        $amount = round((float) ($capture['amount']['value'] ?? -1), 2);
        $expected_amount = round((float) ($booking['amount_due_now'] ?? $booking['total_amount'] ?? 0), 2);
        $currency_raw = strtoupper(sanitize_text_field((string) ($capture['amount']['currency_code'] ?? '')));
        $currency = CurrencyService::normalize($currency_raw);
        $expected_currency = CurrencyService::normalize((string) ($this->settings->all()['payment_currency'] ?? 'EUR'));
        if ($amount <= 0 || abs($amount - $expected_amount) > 0.001 || $currency_raw === '' || $currency !== $expected_currency) {
            return new WP_Error('sltr_paypal_amount_mismatch', __('PayPal captured amount or currency does not match the booking.', 'slotera-booking'));
        }
        $capture_id = sanitize_text_field((string) ($capture['id'] ?? ''));
        if ($capture_id === '') {
            return new WP_Error('sltr_paypal_capture_missing', __('PayPal capture id is missing.', 'slotera-booking'));
        }
        $capture_status = strtoupper(sanitize_text_field((string) ($capture['status'] ?? '')));
        $pending_reason = strtoupper(sanitize_text_field((string) ($capture['status_details']['reason'] ?? '')));
        if ($capture_status === 'PENDING') {
            $this->transactions->upsert_by_external_id([
                'booking_id' => $booking_id,
                'customer_email' => (string) ($booking['customer_email'] ?? ''),
                'gateway' => 'paypal',
                'transaction_type' => 'payment',
                'status' => 'processing',
                'amount' => $amount,
                'currency' => $currency,
                'external_id' => $order_id,
                'external_parent_id' => $capture_id,
                'mode' => $this->is_test_mode() ? 'sandbox' : 'live',
                'description' => 'PayPal payment is processing.',
                'metadata' => ['source' => $source, 'capture_id' => $capture_id, 'capture_status' => $capture_status, 'pending_reason' => $pending_reason, 'raw_id' => $raw['id'] ?? ''],
            ]);
            $marked = (new PaymentService())->mark_processing($booking_id, $source, 'PayPal is processing the payment' . ($pending_reason !== '' ? ' (' . $pending_reason . ').' : '.'));
            if (is_wp_error($marked) || $marked === false) { return $marked; }
            return new PaymentResult([
                'status' => 'processing',
                'gateway' => 'paypal',
                'external_id' => $order_id,
                'capture_id' => $capture_id,
                'capture_status' => $capture_status,
                'pending_reason' => $pending_reason,
            ]);
        }
        if ($capture_status !== 'COMPLETED') {
            return new WP_Error('sltr_paypal_capture_not_completed', __('PayPal capture has not completed.', 'slotera-booking'), ['capture_status' => $capture_status, 'reason' => $pending_reason]);
        }

        $this->transactions->upsert_by_external_id([
            'booking_id' => $booking_id,
            'customer_email' => (string) ($booking['customer_email'] ?? ''),
            'gateway' => 'paypal',
            'transaction_type' => 'payment',
            'status' => 'paid',
            'amount' => $amount,
            'currency' => $currency,
            'external_id' => $order_id,
            'external_parent_id' => $capture_id,
            'mode' => $this->is_test_mode() ? 'sandbox' : 'live',
            'description' => 'PayPal payment captured.',
            'metadata' => ['source' => $source, 'capture_id' => $capture_id, 'raw_id' => $raw['id'] ?? ''],
        ]);

        return (new PaymentService())->mark_paid($booking_id, $source);
    }

    private function booking_id_from_resource(array $resource, string $order_id = ''): int
    {
        $booking_id = absint($resource['custom_id'] ?? $resource['invoice_id'] ?? 0);
        if ($booking_id > 0) { return $booking_id; }
        if ($order_id !== '') {
            $transaction = $this->transactions->get_by_external_id('paypal', $order_id);
            if ($transaction) { return absint($transaction['booking_id'] ?? 0); }
        }
        return 0;
    }

    private function access_token(array $settings)
    {
        $client_id = $this->client_id($settings);
        $client_secret = $this->client_secret($settings);
        if ($client_id === '' || $client_secret === '') {
            return new WP_Error('sltr_paypal_not_configured', __('PayPal client ID or secret is missing.', 'slotera-booking'));
        }

        $mode = $this->is_live($settings) ? 'live' : 'sandbox';
        $cache_key = 'sltr_pp_at_' . substr(hash('sha256', $mode . '|' . $client_id), 0, 24);
        $cached = get_transient($cache_key);
        if (is_string($cached) && $cached !== '') {
            $token = SecretStore::decrypt_string($cached);
            if ($token !== '') {
                return $token;
            }
            delete_transient($cache_key);
        }

        $response = wp_remote_post($this->api_base($settings) . '/v1/oauth2/token', [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => ['grant_type' => 'client_credentials'],
        ]);
        if (is_wp_error($response)) {
            return $response;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($data) || empty($data['access_token'])) {
            $message = is_array($data) && isset($data['error_description']) ? (string) $data['error_description'] : __('PayPal access token could not be created.', 'slotera-booking');
            return new WP_Error('sltr_paypal_access_token_failed', $message, ['status' => $code, 'response' => $data]);
        }

        $token = sanitize_text_field((string) $data['access_token']);
        $expires_in = max(60, (int) ($data['expires_in'] ?? 300));
        $ttl = max(30, min(8 * HOUR_IN_SECONDS, $expires_in - 60));
        $encrypted = SecretStore::encrypt_string($token);
        if ($encrypted !== '') {
            set_transient($cache_key, $encrypted, $ttl);
        }
        return $token;
    }

    private function client_id(array $settings): string
    {
        return sanitize_text_field((string) ($this->is_live($settings) ? ($settings['payment_paypal_live_client_id'] ?? '') : ($settings['payment_paypal_sandbox_client_id'] ?? '')));
    }

    private function client_secret(array $settings): string
    {
        return sanitize_text_field((string) ($this->is_live($settings) ? ($settings['payment_paypal_live_client_secret'] ?? '') : ($settings['payment_paypal_sandbox_client_secret'] ?? '')));
    }

    private function api_base(array $settings): string
    {
        return $this->is_live($settings) ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    private function is_live(array $settings): bool
    {
        return sanitize_key((string) ($settings['payment_paypal_mode'] ?? 'sandbox')) === 'live';
    }

    private function return_base_url(array $booking): string
    {
        $url = $this->settings->get_page_url('thank_you');
        if ($url === '') { $url = home_url('/'); }
        $access_code = (new BookingAccessTokenService())->issue_code($booking);
        if ($access_code !== '') { $url = add_query_arg(['booking_id' => (int) ($booking['id'] ?? 0), 'sltr_access_code' => $access_code], $url); }
        return esc_url_raw($url);
    }
}
