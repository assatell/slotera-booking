<?php

declare(strict_types=1);

namespace Slotera\Frontend\Controllers;

use Slotera\Application\Services\BookingLifecycleService;
use Slotera\Application\Services\BookingService;
use Slotera\Application\Services\BookingSpamProtectionService;
use Slotera\Application\Services\PaymentService;
use Slotera\Application\Services\StripeGatewayService;
use Slotera\Application\Services\PayPalGatewayService;
use Slotera\Application\Services\MollieGatewayService;
use Slotera\Application\Services\PublicBookingActionSecurityService;
use Slotera\Application\Services\MarketingConsentService;
use Slotera\Application\Services\PublicBookingRequestNormalizer;
use Slotera\Domain\Availability\AvailabilityService;
use Slotera\Infrastructure\Http\ClientIpResolver;
use Slotera\Infrastructure\Http\RateLimiter;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) { exit; }

final class RestApiController
{
    private const NAMESPACE = 'slotera/v1';
    private const AVAILABILITY_LOOKAHEAD_DAYS = 366;
    private const AVAILABILITY_LOOKAHEAD_MONTHS = 12;
    private const WEBHOOK_MAX_PAYLOAD_BYTES = 524288;

    private AvailabilityService $availability;
    private BookingSpamProtectionService $spam;
    private BookingService $bookings;
    private BookingLifecycleService $lifecycle;
    private PaymentService $payments;
    private PublicBookingActionSecurityService $public_actions;
    /** @var array<int,true> */
    private array $validated_rest_booking_requests = [];

    public function __construct(?AvailabilityService $availability = null, ?BookingSpamProtectionService $spam = null, ?BookingService $bookings = null, ?BookingLifecycleService $lifecycle = null, ?PaymentService $payments = null, ?PublicBookingActionSecurityService $public_actions = null)
    {
        $this->availability = $availability ?? new AvailabilityService();
        $this->spam = $spam ?? new BookingSpamProtectionService();
        $this->bookings = $bookings ?? new BookingService();
        $this->lifecycle = $lifecycle ?? new BookingLifecycleService();
        $this->payments = $payments ?? new PaymentService();
        $this->public_actions = $public_actions ?? new PublicBookingActionSecurityService();
    }

    public function register(): void { add_action('rest_api_init', [$this, 'register_routes']); }

    public function register_routes(): void
    {
        register_rest_route(self::NAMESPACE, '/availability', ['methods' => 'GET,POST', 'callback' => [$this, 'availability'], 'permission_callback' => [$this, 'can_check_availability']]);
        if ($this->is_public_rest_booking_route_enabled()) {
            register_rest_route(self::NAMESPACE, '/bookings', ['methods' => 'POST', 'callback' => [$this, 'create_booking'], 'permission_callback' => [$this, 'can_create_public_booking']]);
        }
        register_rest_route(self::NAMESPACE, '/bookings/(?P<id>\d+)/cancel', ['methods' => 'POST', 'callback' => [$this, 'cancel_booking_admin'], 'permission_callback' => [$this, 'can_manage_bookings']]);
        register_rest_route(self::NAMESPACE, '/bookings/cancel', ['methods' => 'POST', 'callback' => [$this, 'cancel_booking_by_token'], 'permission_callback' => [$this, 'can_cancel_booking_by_token']]);
        register_rest_route(self::NAMESPACE, '/bookings/reschedule', ['methods' => 'POST', 'callback' => [$this, 'reschedule_booking_by_token'], 'permission_callback' => [$this, 'can_reschedule_booking_by_token']]);
        register_rest_route(self::NAMESPACE, '/payments/stripe/webhook', ['methods' => 'POST', 'callback' => [$this, 'stripe_webhook'], 'permission_callback' => '__return_true']);
        register_rest_route(self::NAMESPACE, '/payments/paypal/return', ['methods' => 'GET', 'callback' => [$this, 'paypal_return'], 'permission_callback' => '__return_true']);
        register_rest_route(self::NAMESPACE, '/payments/paypal/webhook', ['methods' => 'POST', 'callback' => [$this, 'paypal_webhook'], 'permission_callback' => '__return_true']);
        register_rest_route(self::NAMESPACE, '/payments/mollie/webhook', ['methods' => 'POST', 'callback' => [$this, 'mollie_webhook'], 'permission_callback' => '__return_true']);
    }

    public function availability(WP_REST_Request $request): WP_REST_Response
    {
        $package_id = absint($request->get_param('package_id'));
        if ($package_id <= 0) { return $this->error('sltr_invalid_package', sltr_t('Invalid package.'), 400); }

        $date = sanitize_text_field((string) $request->get_param('date'));
        $resource_id = absint($request->get_param('resource_id'));
        $staff_id = absint($request->get_param('staff_id'));
        if ($date !== '') {
            if (!$this->is_valid_date($date)) { return $this->error('sltr_invalid_date', sltr_t('Invalid date.'), 400); }
            if (!$this->is_availability_date_in_range($date)) {
                return $this->error('sltr_availability_date_out_of_range', sltr_t('Availability can only be requested for dates within the public booking window.'), 400);
            }
            return rest_ensure_response([
                'package_id' => $package_id,
                'date' => $date,
                'resource_id' => $resource_id,
                'staff_id' => $staff_id,
                'slots' => $this->availability->get_available_slots_for_package_date($package_id, $date, $resource_id, $staff_id),
            ]);
        }

        $year = absint($request->get_param('year'));
        $month = absint($request->get_param('month'));
        if ($year < 2000 || $month < 1 || $month > 12) { return $this->error('sltr_invalid_month', sltr_t('Provide either date or valid year/month.'), 400); }
        if (!$this->is_availability_month_in_range($year, $month)) {
            return $this->error('sltr_availability_month_out_of_range', sltr_t('Availability can only be requested for months within the public booking window.'), 400);
        }

        return rest_ensure_response(['package_id' => $package_id, 'year' => $year, 'month' => $month, 'dates' => $this->availability->get_available_dates_for_package_month($package_id, $year, $month)]);
    }

    private function is_public_rest_booking_route_enabled(): bool
    {
        if (!\sltr_feature_enabled('public_rest_booking')) {
            return false;
        }

        $settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
        return !empty($settings['security_public_rest_booking_enabled'])
            && \sltr_public_rest_booking_security_reviewed();
    }

    public function create_booking(WP_REST_Request $request): WP_REST_Response
    {
        if ((string) (new SettingsRepository())->get('booking_availability_status', 'available') === 'paused') {
            return $this->error('sltr_booking_paused', __('Booking is temporarily unavailable. Please try again later.', 'slotera-booking'), 503);
        }
        $data = $this->booking_data_from_request($request);
        $security_check = $this->validate_rest_booking_security($request, $data);
        if (is_wp_error($security_check)) { return $this->from_wp_error($security_check, 403); }

        $result = $this->bookings->create_booking($data);
        if (is_wp_error($result)) { return $this->from_wp_error($result, 400); }

        $booking_id = is_array($result) ? (int) ($result['booking_id'] ?? 0) : (int) $result;
        $payment_result = is_array($result) ? ($result['payment_result'] ?? null) : null;
        if (!empty($data['marketing_consent'])) {
            (new MarketingConsentService())->grant((string) ($data['customer_email'] ?? ''), 'public_rest_booking');
        }
        return rest_ensure_response([
            'booking_id' => $booking_id,
            'booking' => is_array($result) ? ($result['booking'] ?? null) : null,
            'payment' => $payment_result,
            'redirect_url' => $this->payments->get_booking_redirect_url($booking_id, is_array($payment_result) ? $payment_result : null),
        ]);
    }


    public function stripe_webhook(WP_REST_Request $request): WP_REST_Response
    {
        $logger = new \Slotera\Application\Services\ActivityLogService();
        $payload = (string) $request->get_body();
        $payload_bytes = strlen($payload);
        if ($payload_bytes === 0 || $payload_bytes > self::WEBHOOK_MAX_PAYLOAD_BYTES) {
            $this->log_rejected_webhook_sample('stripe', 'stripe_webhook_invalid_payload', 'Stripe webhook rejected before verification.', [
                'reason' => $payload_bytes === 0 ? 'empty_payload' : 'payload_too_large',
                'payload_bytes' => $payload_bytes,
            ]);
            return $this->error('sltr_stripe_invalid_payload', 'Invalid Stripe webhook payload.', 400);
        }

        $signature = sanitize_text_field((string) ($request->get_header('stripe-signature') ?: ''));
        if (!$this->stripe_signature_header_shape_is_valid($signature)) {
            $this->log_rejected_webhook_sample('stripe', 'stripe_webhook_verify_error', 'Stripe webhook rejected before signature verification.', [
                'reason' => 'signature_header_missing_or_malformed',
                'payload_bytes' => $payload_bytes,
            ]);
            return $this->error('sltr_stripe_signature_invalid', 'Stripe signature format is invalid.', 400);
        }

        $event = json_decode($payload, true);
        $event_id = is_array($event) ? sanitize_text_field((string) ($event['id'] ?? '')) : '';
        $event_type = is_array($event) ? sanitize_text_field((string) ($event['type'] ?? '')) : '';
        if (!is_array($event) || $event_id === '' || $event_type === '' || !isset($event['data']['object']) || !is_array($event['data']['object'])) {
            $this->log_rejected_webhook_sample('stripe', 'stripe_webhook_invalid_payload', 'Stripe webhook payload failed structural validation.', [
                'reason' => !is_array($event) ? 'invalid_json' : 'missing_required_event_fields',
                'payload_bytes' => $payload_bytes,
            ]);
            return $this->error('sltr_stripe_invalid_payload', 'Invalid Stripe webhook payload.', 400);
        }

        $logger->payment(0, 'stripe_webhook_received', 'stripe', 'info', 'Stripe webhook received.', [
            'event_id_present' => true,
            'event_type' => $event_type,
            'signature_present' => true,
            'payload_bytes' => $payload_bytes,
        ]);

        $stripe = new StripeGatewayService();
        $verified = $stripe->verify_webhook_signature($payload, $signature);
        if (is_wp_error($verified)) {
            $logger->payment(0, 'stripe_webhook_verify_error', 'stripe', 'error', 'Stripe webhook signature verification failed.', [
                'event_id' => $event_id,
                'event_type' => $event_type,
                'error_code' => $verified->get_error_code(),
            ], $verified->get_error_message());
            return $this->from_wp_error($verified, 400);
        }

        $logger->payment(0, 'stripe_webhook_verified', 'stripe', 'success', 'Stripe webhook signature verified.', [
            'event_id' => $event_id,
            'event_type' => $event_type,
        ]);

        $result = $stripe->handle_webhook_event($event);
        if (is_wp_error($result)) {
            $logger->payment(0, 'stripe_webhook_process_error', 'stripe', 'error', 'Stripe webhook processing failed.', [
                'event_id' => $event_id,
                'event_type' => $event_type,
                'error_code' => $result->get_error_code(),
            ], $result->get_error_message());
            return $this->from_wp_error($result, 400);
        }

        $logger->payment(0, 'stripe_webhook_processed', 'stripe', 'success', 'Stripe webhook processed.', [
            'event_id' => $event_id,
            'event_type' => $event_type,
        ]);
        return rest_ensure_response(['received' => true]);
    }

    public function paypal_return(WP_REST_Request $request): WP_REST_Response
    {
        $order_id = sanitize_text_field((string) ($request->get_param('token') ?: $request->get_param('order_id')));
        $booking_id = absint($request->get_param('booking_id'));
        $paypal = new PayPalGatewayService();
        $result = $paypal->capture_order($order_id, $booking_id);
        if (is_wp_error($result)) {
            return $this->from_wp_error($result, 400);
        }

        $redirect = (new PaymentService())->get_booking_redirect_url($booking_id, null);
        if ($redirect !== '') {
            $result_data = $result instanceof \Slotera\Application\Services\PaymentResult ? $result->to_array() : [];
            $redirect_state = sanitize_key((string) ($result_data['status'] ?? '')) === 'processing' ? 'processing' : 'success';
            $redirect = add_query_arg('sltr_payment', $redirect_state, $redirect);
            wp_safe_redirect($redirect);
            exit;
        }
        return rest_ensure_response(['success' => true, 'booking_id' => $booking_id]);
    }

    public function paypal_webhook(WP_REST_Request $request): WP_REST_Response
    {
        $logger = new \Slotera\Application\Services\ActivityLogService();
        $payload = (string) $request->get_body();
        $payload_bytes = strlen($payload);
        if ($payload_bytes === 0 || $payload_bytes > self::WEBHOOK_MAX_PAYLOAD_BYTES) {
            $this->log_rejected_webhook_sample('paypal', 'paypal_webhook_invalid_payload', 'PayPal webhook rejected before verification.', [
                'reason' => $payload_bytes === 0 ? 'empty_payload' : 'payload_too_large',
                'payload_bytes' => $payload_bytes,
            ]);
            return $this->error('sltr_paypal_invalid_payload', 'Invalid PayPal webhook payload.', 400);
        }

        $headers = [];
        foreach (['paypal-auth-algo','paypal-cert-url','paypal-transmission-id','paypal-transmission-sig','paypal-transmission-time'] as $header) {
            $headers[$header] = sanitize_text_field((string) ($request->get_header($header) ?: ''));
        }
        if (!$this->paypal_webhook_headers_shape_is_valid($headers)) {
            $this->log_rejected_webhook_sample('paypal', 'paypal_webhook_verify_error', 'PayPal webhook rejected before remote verification.', [
                'reason' => 'required_headers_missing_or_malformed',
                'payload_bytes' => $payload_bytes,
            ]);
            return $this->error('sltr_paypal_webhook_headers_invalid', 'PayPal webhook headers are invalid.', 400);
        }

        $event = json_decode($payload, true);
        $event_id = is_array($event) ? sanitize_text_field((string) ($event['id'] ?? '')) : '';
        $event_type = is_array($event) ? sanitize_text_field((string) ($event['event_type'] ?? '')) : '';
        if (!is_array($event) || $event_id === '' || $event_type === '' || !isset($event['resource']) || !is_array($event['resource'])) {
            $this->log_rejected_webhook_sample('paypal', 'paypal_webhook_invalid_payload', 'PayPal webhook payload failed structural validation.', [
                'reason' => !is_array($event) ? 'invalid_json' : 'missing_required_event_fields',
                'payload_bytes' => $payload_bytes,
            ]);
            return $this->error('sltr_paypal_invalid_payload', 'Invalid PayPal webhook payload.', 400);
        }

        $logger->payment(0, 'paypal_webhook_received', 'paypal', 'info', 'PayPal webhook received.', [
            'event_id_present' => true,
            'event_type' => $event_type,
            'payload_bytes' => $payload_bytes,
        ]);

        $paypal = new PayPalGatewayService();
        $verified = $paypal->verify_webhook_event($headers, $event);
        if (is_wp_error($verified)) {
            $logger->payment(0, 'paypal_webhook_verify_error', 'paypal', 'error', 'PayPal webhook signature verification failed.', [
                'event_id' => $event_id,
                'event_type' => $event_type,
                'error_code' => $verified->get_error_code(),
            ], $verified->get_error_message());
            return $this->from_wp_error($verified, 400);
        }
        $logger->payment(0, 'paypal_webhook_verified', 'paypal', 'success', 'PayPal webhook signature verified.', [
            'event_id' => $event_id,
            'event_type' => $event_type,
        ]);

        $result = $paypal->handle_webhook_event($event);
        if (is_wp_error($result)) {
            $logger->payment(0, 'paypal_webhook_process_error', 'paypal', 'error', 'PayPal webhook processing failed.', [
                'event_id' => $event_id,
                'event_type' => $event_type,
                'error_code' => $result->get_error_code(),
            ], $result->get_error_message());
            return $this->from_wp_error($result, 400);
        }
        $logger->payment(0, 'paypal_webhook_processed', 'paypal', 'success', 'PayPal webhook processed.', [
            'event_id' => $event_id,
            'event_type' => $event_type,
        ]);
        return rest_ensure_response(['received' => true]);
    }


    private function stripe_signature_header_shape_is_valid(string $signature): bool
    {
        if ($signature === '' || strlen($signature) > 4096) {
            return false;
        }
        return preg_match('/(?:^|,)\s*t=\d{8,12}(?:,|$)/', $signature) === 1
            && preg_match('/(?:^|,)\s*v1=[a-f0-9]{64}(?:,|$)/i', $signature) === 1;
    }

    private function paypal_webhook_headers_shape_is_valid(array $headers): bool
    {
        foreach (['paypal-auth-algo','paypal-cert-url','paypal-transmission-id','paypal-transmission-sig','paypal-transmission-time'] as $key) {
            if (trim((string) ($headers[$key] ?? '')) === '') {
                return false;
            }
        }
        if (strlen((string) $headers['paypal-auth-algo']) > 64
            || strlen((string) $headers['paypal-transmission-id']) > 160
            || strlen((string) $headers['paypal-transmission-sig']) > 4096
            || strlen((string) $headers['paypal-transmission-time']) > 64) {
            return false;
        }
        $cert_url = (string) $headers['paypal-cert-url'];
        $parts = wp_parse_url($cert_url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (($parts['scheme'] ?? '') !== 'https' || $host === '' || !($host === 'paypal.com' || str_ends_with($host, '.paypal.com'))) {
            return false;
        }
        return strtotime((string) $headers['paypal-transmission-time']) !== false;
    }

    /**
     * Invalid pre-verification traffic can be attacker-controlled. Keep a
     * bounded diagnostic sample without turning every rejected request into a
     * permanent activity-log row. The rate-limit counter is operational state,
     * not an allow/deny gate, so legitimate provider retries are never blocked.
     */
    private function log_rejected_webhook_sample(string $gateway, string $event, string $description, array $context = []): void
    {
        $identity = ClientIpResolver::get_client_ip();
        $count = RateLimiter::increment('webhook_reject_log_' . sanitize_key($gateway), $identity !== '' ? $identity : 'unknown', 300);
        if ($count <= 3 || ($count % 25) === 0) {
            $context['sample_count'] = $count;
            (new \Slotera\Application\Services\ActivityLogService())->payment(0, $event, $gateway, 'warning', $description, $context);
        }
    }


    public function mollie_webhook(WP_REST_Request $request): WP_REST_Response
    {
        $payment_id = sanitize_text_field((string) ($request->get_param('id') ?: $request->get_param('payment_id')));
        if ($payment_id === '') {
            $raw_body = (string) $request->get_body();
            parse_str($raw_body, $parsed);
            $payment_id = sanitize_text_field((string) ($parsed['id'] ?? $parsed['payment_id'] ?? ''));
        }
        $mollie = new MollieGatewayService();
        $result = $mollie->handle_webhook_payment_id($payment_id);
        if (is_wp_error($result)) { return $this->from_wp_error($result, 400); }
        return rest_ensure_response(['received' => true]);
    }

    public function cancel_booking_admin(WP_REST_Request $request): WP_REST_Response
    {
        $booking_id = absint($request['id'] ?? 0);
        $result = $this->bookings->cancel_booking($booking_id);
        if (is_wp_error($result)) { return $this->from_wp_error($result, 400); }
        return rest_ensure_response(['success' => (bool) $result, 'booking_id' => $booking_id]);
    }

    public function cancel_booking_by_token(WP_REST_Request $request): WP_REST_Response
    {
        $token = sanitize_text_field((string) $request->get_param('token'));
        if ($token === '') { return $this->error('sltr_missing_token', sltr_t('Missing token.'), 400); }
        $nonce_check = $this->validate_public_token_action_nonce($request, $token, 'cancel');
        if (is_wp_error($nonce_check)) { return $this->from_wp_error($nonce_check, 403); }
        $result = $this->lifecycle->cancel_by_token($token);
        if (is_wp_error($result)) { return $this->from_wp_error($result, 400); }
        return rest_ensure_response(['success' => (bool) $result, 'status' => 'cancelled']);
    }

    public function reschedule_booking_by_token(WP_REST_Request $request): WP_REST_Response
    {
        $token = sanitize_text_field((string) $request->get_param('token'));
        $date = sanitize_text_field((string) $request->get_param('date'));
        $start = sanitize_text_field((string) $request->get_param('start'));
        $end = sanitize_text_field((string) $request->get_param('end'));
        if ($token === '' || $date === '' || $start === '' || $end === '') { return $this->error('sltr_missing_reschedule_data', sltr_t('Missing reschedule data.'), 400); }
        $nonce_check = $this->validate_public_token_action_nonce($request, $token, 'reschedule');
        if (is_wp_error($nonce_check)) { return $this->from_wp_error($nonce_check, 403); }
        $result = $this->lifecycle->reschedule_by_token($token, $date, $start, $end);
        if (is_wp_error($result)) { return $this->from_wp_error($result, 400); }
        return rest_ensure_response(['success' => true, 'booking' => $result]);
    }

    public function can_check_availability(WP_REST_Request $request)
    {
        if (current_user_can(\Slotera\Core\Capabilities::MANAGE_BOOKINGS)) {
            return true;
        }

        $settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
        $identity = $this->rate_limit_identity();

        if ($this->is_trusted_ip($identity, (string) ($settings['security_trusted_ips'] ?? ''))) {
            return true;
        }

        $rate_limit = $this->enforce_availability_rate_limit($settings);
        if (is_wp_error($rate_limit)) {
            return $rate_limit;
        }

        if ($this->has_valid_rest_nonce($request)) {
            return true;
        }

        /**
         * Do not expose raw slot/date availability through unauthenticated REST.
         * The public booking UI uses nonce-protected admin-ajax requests, while REST
         * availability remains available to admins, same-site REST nonce requests and
         * explicitly trusted server-to-server IPs only. Anonymous calls are still
         * rate-limited before denial to make package/date enumeration expensive.
         */
        return new WP_Error(
            'sltr_rest_availability_nonce_required',
            sltr_t('Availability REST requests require a valid same-site REST nonce.'),
            ['status' => 403]
        );
    }


    public function can_cancel_booking_by_token(WP_REST_Request $request)
    {
        $token = sanitize_text_field((string) $request->get_param('token'));
        return $this->validate_public_token_action_nonce($request, $token, 'cancel');
    }

    public function can_reschedule_booking_by_token(WP_REST_Request $request)
    {
        $token = sanitize_text_field((string) $request->get_param('token'));
        return $this->validate_public_token_action_nonce($request, $token, 'reschedule');
    }

    public function can_create_public_booking(WP_REST_Request $request)
    {
        if (current_user_can(\Slotera\Core\Capabilities::MANAGE_BOOKINGS)) {
            return true;
        }

        $settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
        if (empty($settings['security_public_rest_booking_enabled'])) {
            return new WP_Error('sltr_rest_booking_disabled', sltr_t('Public REST booking is disabled. Use the site booking form or enable public REST booking in Slotera security settings.'), ['status' => 403]);
        }

        if (empty($settings['security_public_rest_booking_security_reviewed'])) {
            return new WP_Error('sltr_rest_booking_security_review_required', sltr_t('Public REST booking requires an explicit security review before it can accept booking creation requests.'), ['status' => 403]);
        }

        $auth = $this->validate_rest_booking_auth($request, $settings);
        if ($auth === true) {
            $this->validated_rest_booking_requests[spl_object_id($request)] = true;
        }
        return $auth;
    }

    private function validate_public_token_action_nonce(WP_REST_Request $request, string $token, string $action)
    {
        if ($token === '') {
            return new WP_Error('sltr_missing_token', sltr_t('Missing token.'), ['status' => 400]);
        }

        $nonce = (string) (
            $request->get_param('_wpnonce')
            ?: $request->get_param('nonce')
            ?: $request->get_header('x_sltr_nonce')
            ?: $request->get_header('x_wp_nonce')
        );
        $nonce = sanitize_text_field($nonce);
        $action = $action === 'reschedule' ? PublicBookingActionSecurityService::ACTION_RESCHEDULE : PublicBookingActionSecurityService::ACTION_CANCEL;

        $limited = $this->public_actions->enforce_rate_limit($action, $token);
        if (is_wp_error($limited)) { return $limited; }

        if (!$this->public_actions->verify_nonce($nonce, $action, $token)) {
            return new WP_Error('sltr_invalid_action_nonce', sltr_t('Invalid or expired booking action confirmation. Please open the booking link again and confirm the action from the site.'), ['status' => 403]);
        }

        return true;
    }

    private function validate_rest_booking_security(WP_REST_Request $request, array $data)
    {
        $is_privileged_request = current_user_can(\Slotera\Core\Capabilities::MANAGE_BOOKINGS);

        if (!$is_privileged_request) {
            $settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
            if (empty($settings['security_public_rest_booking_enabled'])) {
                return new WP_Error('sltr_rest_booking_disabled', sltr_t('Public REST booking is disabled. Use the site booking form or enable public REST booking in Slotera security settings.'), ['status' => 403]);
            }

            if (empty($settings['security_public_rest_booking_security_reviewed'])) {
                return new WP_Error('sltr_rest_booking_security_review_required', sltr_t('Public REST booking requires an explicit security review before it can accept booking creation requests.'), ['status' => 403]);
            }

            $request_id = spl_object_id($request);
            if (empty($this->validated_rest_booking_requests[$request_id])) {
                $auth_check = $this->validate_rest_booking_auth($request, $settings);
                if (is_wp_error($auth_check)) { return $auth_check; }
                $this->validated_rest_booking_requests[$request_id] = true;
            }

            $rate_limit = $this->enforce_rest_rate_limit($data);
            if (is_wp_error($rate_limit)) { return $rate_limit; }
        }

        return $this->spam->validate_frontend_submission($data);
    }

    private function has_valid_rest_nonce(WP_REST_Request $request): bool
    {
        $nonce = (string) ($request->get_header('x_wp_nonce') ?: $request->get_param('_wpnonce'));
        return $nonce !== '' && (bool) wp_verify_nonce($nonce, 'wp_rest');
    }

    private function validate_rest_booking_auth(WP_REST_Request $request, array $settings)
    {
        $auth_rate_limit = $this->enforce_rest_auth_rate_limit();
        if (is_wp_error($auth_rate_limit)) { return $auth_rate_limit; }

        $mode = sanitize_key((string) ($settings['security_public_rest_booking_auth_mode'] ?? 'site_form'));

        if ($mode === 'hmac') {
            return $this->validate_rest_booking_hmac($request, $settings);
        }

        if (!$this->has_valid_rest_nonce($request)) {
            return new WP_Error(
                'sltr_rest_nonce_required',
                sltr_t('Invalid or missing REST nonce. Public REST booking is in site-form mode; submit bookings from the site booking form or switch to HMAC mode for headless integrations.'),
                ['status' => 403]
            );
        }

        return true;
    }

    private function validate_rest_booking_hmac(WP_REST_Request $request, array $settings)
    {
        $expected_key = trim((string) ($settings['security_public_rest_booking_api_key'] ?? ''));
        $secret = (string) ($settings['security_public_rest_booking_hmac_secret'] ?? '');

        if ($expected_key === '' || $secret === '') {
            return new WP_Error('sltr_rest_hmac_not_configured', sltr_t('Public REST booking HMAC credentials are not configured.'), ['status' => 403]);
        }

        $provided_key = trim((string) $request->get_header('x_slotera_api_key'));
        $timestamp = trim((string) $request->get_header('x_slotera_timestamp'));
        $signature = trim((string) $request->get_header('x_slotera_signature'));
        $nonce = sanitize_text_field((string) $request->get_header('x_slotera_nonce'));

        if ($provided_key === '' || $timestamp === '' || $signature === '' || $nonce === '') {
            return new WP_Error('sltr_rest_hmac_required', sltr_t('Missing API key, timestamp, nonce or HMAC signature for public REST booking.'), ['status' => 403]);
        }

        if (!hash_equals($expected_key, $provided_key)) {
            return new WP_Error('sltr_rest_hmac_invalid_key', sltr_t('Invalid public REST booking API key.'), ['status' => 403]);
        }

        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) {
            return new WP_Error('sltr_rest_hmac_stale', sltr_t('Public REST booking HMAC timestamp is invalid or expired.'), ['status' => 403]);
        }

        $route = (string) $request->get_route();
        $body = (string) $request->get_body();
        $payload = strtoupper((string) $request->get_method()) . "\n" . $route . "\n" . $timestamp . "\n" . $nonce . "\n" . $body;
        $expected_signature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals(strtolower($expected_signature), strtolower($signature))) {
            return new WP_Error('sltr_rest_hmac_invalid_signature', sltr_t('Invalid public REST booking HMAC signature.'), ['status' => 403]);
        }

        if (!$this->reserve_rest_hmac_nonce($provided_key, $timestamp, $nonce)) {
            return new WP_Error('sltr_rest_hmac_replay', sltr_t('Duplicate public REST booking HMAC request detected.'), ['status' => 403]);
        }

        return true;
    }


    private function reserve_rest_hmac_nonce(string $api_key, string $timestamp, string $nonce): bool
    {
        global $wpdb;

        $nonce = substr(sanitize_text_field($nonce), 0, 128);
        if ($nonce === '' || !isset($wpdb)) {
            return false;
        }

        $table = \Slotera\Core\Database::rest_hmac_nonces_table();
        $nonce_hash = hash('sha256', $api_key . '|' . $timestamp . '|' . $nonce);
        $api_key_hash = hash('sha256', $api_key);
        $expires_at = time() + 360;
        $created_at = function_exists('current_time') ? current_time('mysql') : gmdate('Y-m-d H:i:s');

        $reserved = $wpdb->insert(
            $table,
            [
                'nonce_hash' => $nonce_hash,
                'api_key_hash' => $api_key_hash,
                'request_timestamp' => (int) $timestamp,
                'expires_at' => $expires_at,
                'created_at' => $created_at,
            ],
            ['%s', '%s', '%d', '%d', '%s']
        );

        if ($reserved === false) {
            return false;
        }

        if (function_exists('wp_rand') && wp_rand(1, 100) === 1) {
            $this->cleanup_rest_hmac_replay_markers();
        }

        return true;
    }

    private function cleanup_rest_hmac_replay_markers(): void
    {
        global $wpdb;

        if (!isset($wpdb)) {
            return;
        }

        $table = \Slotera\Core\Database::rest_hmac_nonces_table();
        $now = time();
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE expires_at < %d LIMIT 100",
            $now
        ));
    }


    private function enforce_rest_auth_rate_limit()
    {
        $settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
        $limit = max(1, (int) ($settings['security_rate_limit_ip_attempts'] ?? 10));
        $window = max(1, (int) ($settings['security_rate_limit_window_minutes'] ?? 15)) * MINUTE_IN_SECONDS;
        $key = 'ip_' . md5($this->rate_limit_identity());
        $attempts = RateLimiter::increment('rest_booking_auth', $key, $window);

        if ($attempts > $limit) {
            return new WP_Error('sltr_rest_auth_rate_limited', sltr_t('Too many public REST booking authentication attempts. Please try again later.'), ['status' => 429]);
        }

        return true;
    }

    private function enforce_rest_rate_limit(array $data)
    {
        $settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
        $limit = max(1, (int) ($settings['security_rate_limit_ip_attempts'] ?? 10));
        $window = max(1, (int) ($settings['security_rate_limit_window_minutes'] ?? 15)) * MINUTE_IN_SECONDS;

        $email = sanitize_email((string) ($data['customer_email'] ?? ''));
        $keys = ['ip_' . md5($this->rate_limit_identity())];
        if ($email !== '') {
            $keys[] = 'email_' . md5(strtolower($email));
        }

        foreach ($keys as $key) {
            $attempts = RateLimiter::increment('rest_booking', $key, $window);
            if ($attempts > $limit) {
                return new WP_Error('sltr_rest_rate_limited', sltr_t('Too many public REST booking attempts. Please try again later.'), ['status' => 429]);
            }
        }

        return true;
    }

    private function enforce_availability_rate_limit(array $settings)
    {
        $limit = max(1, (int) ($settings['security_availability_rate_limit_attempts'] ?? 120));
        $window = max(1, (int) ($settings['security_rate_limit_window_minutes'] ?? 15)) * MINUTE_IN_SECONDS;
        $key = 'ip_' . md5($this->rate_limit_identity());
        $attempts = RateLimiter::increment('rest_availability', $key, $window);

        if ($attempts > $limit) {
            return new WP_Error('sltr_rest_availability_rate_limited', sltr_t('Too many availability requests. Please try again later.'), ['status' => 429]);
        }

        return true;
    }

    private function is_availability_date_in_range(string $date): bool
    {
        try {
            $timezone = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('UTC');
            $today = new \DateTimeImmutable(function_exists('current_time') ? current_time('Y-m-d') : 'today', $timezone);
            $requested = new \DateTimeImmutable($date, $timezone);
            $max = $today->modify('+' . self::AVAILABILITY_LOOKAHEAD_DAYS . ' days');
            return $requested >= $today && $requested <= $max;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function is_availability_month_in_range(int $year, int $month): bool
    {
        try {
            $timezone = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('UTC');
            $today = new \DateTimeImmutable(function_exists('current_time') ? current_time('Y-m-01') : 'first day of this month', $timezone);
            $requested = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), $timezone);
            $max = $today->modify('+' . self::AVAILABILITY_LOOKAHEAD_MONTHS . ' months');
            return $requested >= $today && $requested <= $max;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function rate_limit_identity(): string
    {
        $ip = ClientIpResolver::get_client_ip();
        return $ip !== '' ? $ip : 'unknown';
    }



    private function is_trusted_ip(string $ip, string $trusted_ips): bool
    {
        if ($ip === '' || $ip === 'unknown') {
            return false;
        }

        $lines = preg_split('/\r\n|\r|\n/', $trusted_ips);
        foreach ((array) $lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            if ($line === $ip || $this->ip_in_cidr($ip, $line)) {
                return true;
            }
        }

        return false;
    }

    private function ip_in_cidr(string $ip, string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return false;
        }

        [$subnet, $mask] = array_pad(explode('/', $cidr, 2), 2, '');
        $subnet = trim((string) $subnet);
        $mask = trim((string) $mask);

        if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($subnet, FILTER_VALIDATE_IP) || !is_numeric($mask)) {
            return false;
        }

        $ip_bin = @inet_pton($ip);
        $subnet_bin = @inet_pton($subnet);
        if ($ip_bin === false || $subnet_bin === false || strlen($ip_bin) !== strlen($subnet_bin)) {
            return false;
        }

        $bits = strlen($ip_bin) * 8;
        $mask = (int) $mask;
        if ($mask < 0 || $mask > $bits) {
            return false;
        }

        $full_bytes = intdiv($mask, 8);
        $remaining_bits = $mask % 8;

        if ($full_bytes > 0 && substr($ip_bin, 0, $full_bytes) !== substr($subnet_bin, 0, $full_bytes)) {
            return false;
        }

        if ($remaining_bits === 0) {
            return true;
        }

        $byte_mask = (0xFF << (8 - $remaining_bits)) & 0xFF;
        return (ord($ip_bin[$full_bytes]) & $byte_mask) === (ord($subnet_bin[$full_bytes]) & $byte_mask);
    }

    private function booking_data_from_request(WP_REST_Request $request): array
    {
        return PublicBookingRequestNormalizer::normalize($request->get_params(), 'rest');
    }
    private function is_valid_date(string $date): bool { $dt = \DateTime::createFromFormat('Y-m-d', $date); return $dt && $dt->format('Y-m-d') === $date; }
    private function error(string $code, string $message, int $status): WP_REST_Response { return new WP_REST_Response(['code' => $code, 'message' => $message], $status); }
    private function from_wp_error(WP_Error $error, int $status): WP_REST_Response { return new WP_REST_Response(['code' => $error->get_error_code(), 'message' => $error->get_error_message()], $status); }
}
