<?php

declare(strict_types=1);

namespace Slotera\Frontend\Controllers;

use Slotera\Application\Services\AccountMagicLinkService;
use Slotera\Application\Services\BookingLifecycleService;
use Slotera\Application\Services\PdfInvoiceService;
use Slotera\Application\Services\SocialLoginService;
use Slotera\Application\Services\TranslationService;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use WP_Error;
use Slotera\Infrastructure\Http\ClientIpResolver;
use Slotera\Infrastructure\Http\RateLimiter;
use Slotera\Core\Events;

if (!defined('ABSPATH')) { exit; }

final class AccountController
{
    private AccountMagicLinkService $accounts;
    private BookingLifecycleService $lifecycle;

    public function __construct(?AccountMagicLinkService $accounts = null, ?BookingLifecycleService $lifecycle = null)
    {
        $this->accounts = $accounts ?: new AccountMagicLinkService();
        $this->lifecycle = $lifecycle ?: new BookingLifecycleService();
    }

    public function register(): void
    {
        add_action('template_redirect', [$this, 'maybe_consume_magic_link']);
        add_action('template_redirect', [$this, 'maybe_handle_frontend_social_login']);
        add_action('admin_post_nopriv_sltr_request_magic_link', [$this, 'request_magic_link']);
        add_action('admin_post_sltr_request_magic_link', [$this, 'request_magic_link']);
        add_action('admin_post_nopriv_sltr_consume_magic_link', [$this, 'consume_magic_link']);
        add_action('admin_post_sltr_consume_magic_link', [$this, 'consume_magic_link']);
        add_action('admin_post_nopriv_sltr_account_logout', [$this, 'logout']);
        add_action('admin_post_sltr_account_logout', [$this, 'logout']);
        add_action('admin_post_nopriv_sltr_account_cancel_booking', [$this, 'cancel_booking']);
        add_action('admin_post_sltr_account_cancel_booking', [$this, 'cancel_booking']);
        add_action('admin_post_nopriv_sltr_account_reschedule_booking', [$this, 'reschedule_booking']);
        add_action('admin_post_sltr_account_reschedule_booking', [$this, 'reschedule_booking']);
        add_action('admin_post_nopriv_sltr_account_invoice_pdf', [$this, 'download_invoice']);
        add_action('admin_post_sltr_account_invoice_pdf', [$this, 'download_invoice']);
    }


    public function maybe_handle_frontend_social_login(): void
    {
        $route = $this->current_social_login_route();
        if ($route) {
            $_GET['provider'] = $route['provider'];
            if ($route['step'] === 'start') {
                $this->social_login_start();
            }
            if ($route['step'] === 'callback') {
                $this->social_login_callback();
            }
        }

        // Backward-compatible frontend query fallback only. Do not use wp-admin/admin-post.php for social login.
        if (!empty($_GET['sltr_social_login_start'])) {
            $this->social_login_start();
        }

        if (!empty($_GET['sltr_social_login_callback'])) {
            $this->social_login_callback();
        }
    }

    /**
     * @return array{provider:string,step:string}|null
     */
    private function current_social_login_route(): ?array
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
        $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);

        if ($home_path !== '/' && $home_path !== '' && str_starts_with($path, rtrim($home_path, '/'))) {
            $path = substr($path, strlen(rtrim($home_path, '/')));
        }

        $path = trim($path, '/');
        if (!preg_match('#^slotera-social-login/(google|facebook|apple)/(start|callback)$#', $path, $matches)) {
            return null;
        }

        return [
            'provider' => sanitize_key($matches[1]),
            'step' => sanitize_key($matches[2]),
        ];
    }

    public function maybe_consume_magic_link(): void
    {
        if (!empty(wp_unslash((string) ($_GET['sltr_magic_login'] ?? '')))) {
            $email = isset($_GET['sltr_email']) ? sanitize_email(rawurldecode((string) wp_unslash($_GET['sltr_email']))) : '';
            $expires = isset($_GET['sltr_expires']) ? absint(wp_unslash((string) $_GET['sltr_expires'])) : 0;
            $token = isset($_GET['sltr_token']) ? sanitize_text_field((string) wp_unslash($_GET['sltr_token'])) : '';

            if (!$this->accounts->begin_magic_confirmation($email, $expires, $token)) {
                wp_safe_redirect(add_query_arg('sltr_account_notice', 'login_failed', $this->accounts->account_url()));
                exit;
            }

            // Remove the email and bearer-equivalent token from the URL before
            // any theme/plugin frontend code, analytics or pixels are rendered.
            wp_safe_redirect($this->accounts->account_url(), 303);
            exit;
        }

        if (!$this->accounts->has_pending_magic_confirmation()) { return; }
        $this->render_magic_link_confirmation();
        exit;
    }

    public function consume_magic_link(): void
    {
        check_admin_referer('sltr_consume_magic_link');
        $email = $this->accounts->consume_magic_confirmation();
        $ok = $email !== '';
        if ($ok) {
            Events::dispatch(Events::CUSTOMER_LOGIN, [
                'customer_email' => $email,
                'source' => 'magic_link',
            ]);
        }
        wp_safe_redirect(add_query_arg('sltr_account_notice', $ok ? 'login_success' : 'login_failed', $this->accounts->account_url()));
        exit;
    }

    private function render_magic_link_confirmation(): void
    {
        status_header(200);
        nocache_headers();
        $translations = new TranslationService();
        $title = $translations->translate_default('Confirm login', 'frontend') ?: __('Confirm login', 'slotera-booking');
        $description = $translations->translate_default('Click the button below to sign in to your booking account. This extra confirmation keeps email security scanners from using your one-time link before you do.', 'frontend') ?: __('Click the button below to sign in to your booking account. This extra confirmation keeps email security scanners from using your one-time link before you do.', 'slotera-booking');
        $button = $translations->translate_default('Sign in', 'frontend') ?: __('Sign in', 'slotera-booking');
        ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($title); ?></title>
    <?php wp_head(); ?>
    <?php include dirname(__DIR__) . '/Views/public-token/_styles.php'; ?>
</head>
<body <?php body_class('sltr-magic-link-confirmation'); ?>>
    <main class="sltr-confirm sltr-account-auth">
        <h1><?php echo esc_html($title); ?></h1>
        <p><?php echo esc_html($description); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_consume_magic_link">
            <?php wp_nonce_field('sltr_consume_magic_link'); ?>
            <button type="submit" class="button"><?php echo esc_html($button); ?></button>
        </form>
    </main>
    <?php wp_footer(); ?>
</body>
</html><?php
    }

    public function request_magic_link(): void
    {
        check_admin_referer('sltr_request_magic_link');
        $email = isset($_POST['email']) ? sanitize_email((string) wp_unslash($_POST['email'])) : '';
        $redirect = wp_get_referer() ?: $this->accounts->login_url();

        if ($email === '' || !is_email($email)) {
            wp_safe_redirect(add_query_arg('sltr_login_notice', 'invalid_email', $redirect));
            exit;
        }

        $key = md5(strtolower($email) . '|' . (ClientIpResolver::get_client_ip() ?: 'unknown'));
        $attempts = RateLimiter::increment('account_magic_link', $key, 15 * MINUTE_IN_SECONDS);
        if ($attempts > 5) {
            wp_safe_redirect(add_query_arg('sltr_login_notice', 'rate_limited', $redirect));
            exit;
        }

        $sent = $this->accounts->send_magic_link($email);
        // Keep customer PII out of browser history, access logs and analytics URLs.
        wp_safe_redirect(add_query_arg(
            'sltr_login_notice',
            $sent ? 'sent' : 'mail_failed',
            $redirect
        ));
        exit;
    }


    public function social_login_start(): void
    {
        $provider = isset($_GET['provider']) ? sanitize_key((string) wp_unslash($_GET['provider'])) : '';
        $service = new SocialLoginService();
        $url = $service->authorization_url($provider);

        if ($url === '') {
            wp_safe_redirect(add_query_arg('sltr_login_notice', 'social_not_configured', $this->accounts->login_url()));
            exit;
        }

        // OAuth authorization URLs point to external providers. Keep wp_redirect(),
        // but only after the service verifies the URL against the fixed provider allowlist.
        if (!$service->is_authorization_url_allowed($provider, $url)) {
            wp_safe_redirect(add_query_arg('sltr_login_notice', 'social_not_configured', $this->accounts->login_url()));
            exit;
        }

        wp_redirect($url, 302, 'Slotera Booking');
        exit;
    }

    public function social_login_callback(): void
    {
        $provider = isset($_GET['provider']) ? sanitize_key((string) wp_unslash($_GET['provider'])) : '';
        $code = isset($_GET['code']) ? sanitize_text_field((string) wp_unslash($_GET['code'])) : '';
        $state = isset($_GET['state']) ? sanitize_text_field((string) wp_unslash($_GET['state'])) : '';
        $error = isset($_GET['error']) ? sanitize_key((string) wp_unslash($_GET['error'])) : '';

        if ($error !== '') {
            $this->record_social_login_diagnostic($provider, 'provider_error_' . $error, ['provider_error' => $error]);
            wp_safe_redirect(add_query_arg([
                'sltr_login_notice' => 'social_cancelled',
                'sltr_social_error' => 'provider_error_' . $error,
            ], $this->accounts->login_url()));
            exit;
        }

        $service = new SocialLoginService();
        $profile = $service->user_from_callback($provider, $code, $state);
        if (is_wp_error($profile)) {
            $reason = sanitize_key($profile->get_error_code() ?: 'sltr_social_login_failed');
            $this->record_social_login_diagnostic($provider, $reason, $profile->get_error_data());
            wp_safe_redirect(add_query_arg([
                'sltr_login_notice' => 'social_failed',
                'sltr_social_error' => $reason,
            ], $this->accounts->login_url()));
            exit;
        }

        if (($profile['email_verified'] ?? false) !== true) {
            $this->record_social_login_diagnostic($provider, 'sltr_social_login_email_unverified');
            wp_safe_redirect(add_query_arg([
                'sltr_login_notice' => 'social_failed',
                'sltr_social_error' => 'sltr_social_login_email_unverified',
            ], $this->accounts->login_url()));
            exit;
        }

        $email = sanitize_email((string) ($profile['email'] ?? ''));
        $ok = $this->accounts->authenticate_email($email);
        if ($ok) {
            Events::dispatch(Events::CUSTOMER_LOGIN, [
                'customer_email' => $email,
                'source' => 'social_' . sanitize_key($provider),
            ]);
            wp_safe_redirect(add_query_arg('sltr_account_notice', 'login_success', $this->accounts->account_url()));
            exit;
        }

        $this->record_social_login_diagnostic($provider, 'sltr_social_login_email_not_found', [
            'email_hash' => $email !== '' ? hash_hmac('sha256', strtolower($email), wp_salt('auth')) : '',
        ]);
        // Never put customer PII in browser history, CDN/WAF logs or Referer.
        wp_safe_redirect(add_query_arg([
            'sltr_login_notice' => 'social_email_not_found',
            'sltr_social_error' => 'sltr_social_login_email_not_found',
        ], $this->accounts->login_url()));
        exit;
    }

    /**
     * Store a short, secret-free diagnostic marker for failed social logins.
     * The frontend may show the reason code, while WP_DEBUG logs include extra
     * non-sensitive context such as HTTP status and provider error strings.
     *
     * @param mixed $data
     */
    private function record_social_login_diagnostic(string $provider, string $reason, $data = null): void
    {
        $provider = sanitize_key($provider ?: 'unknown');
        $reason = sanitize_key($reason ?: 'sltr_social_login_failed');
        $safe = [
            'provider' => $provider,
            'reason' => $reason,
            'time' => time(),
        ];

        if (is_array($data)) {
            foreach (['http_code', 'provider_error', 'provider_error_description', 'redirect_uri', 'wp_error_code', 'wp_error_message'] as $key) {
                if (isset($data[$key]) && is_scalar($data[$key])) {
                    $safe[$key] = sanitize_text_field((string) $data[$key]);
                }
            }
        }

        set_transient('sltr_last_social_login_error', $safe, DAY_IN_SECONDS);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Slotera social login diagnostic: ' . wp_json_encode($safe));
        }
    }

    public function logout(): void
    {
        check_admin_referer('sltr_account_logout');
        $this->accounts->logout();
        wp_safe_redirect(add_query_arg('sltr_account_notice', 'logged_out', $this->accounts->login_url()));
        exit;
    }

    public function cancel_booking(): void
    {
        $token = isset($_POST['token']) ? sanitize_text_field((string) wp_unslash($_POST['token'])) : '';
        if ($token === '' || !wp_verify_nonce((string) wp_unslash((string) ($_POST['_wpnonce'] ?? '')), 'sltr_account_cancel_' . $token)) {
            wp_safe_redirect(add_query_arg('sltr_account_notice', 'action_failed', $this->accounts->account_url()));
            exit;
        }

        $booking = $this->require_account_booking_owner('cancel', $token);
        if (is_wp_error($booking)) {
            wp_safe_redirect(add_query_arg('sltr_account_notice', 'action_failed', $this->accounts->account_url()));
            exit;
        }

        $result = $this->lifecycle->cancel_by_token($token);
        wp_safe_redirect(add_query_arg('sltr_account_notice', is_wp_error($result) ? 'cancel_failed' : 'cancelled', $this->accounts->account_url()));
        exit;
    }


    public function download_invoice(): void
    {
        $booking_id = isset($_GET['booking_id']) ? absint(wp_unslash((string) $_GET['booking_id'])) : 0;
        if ($booking_id <= 0 || !wp_verify_nonce((string) wp_unslash((string) ($_GET['_wpnonce'] ?? '')), 'sltr_account_invoice_pdf_' . $booking_id)) {
            status_header(403);
            wp_die(esc_html(sltr_t('Invalid invoice link.')));
        }

        $email = $this->accounts->current_email();
        if ($email === '') {
            wp_safe_redirect($this->accounts->login_url());
            exit;
        }

        $booking = (new BookingRepository())->get_by_id($booking_id);
        if (!$booking || strtolower(sanitize_email((string) ($booking['customer_email'] ?? ''))) !== strtolower($email)) {
            status_header(403);
            wp_die(esc_html(sltr_t('You do not have access to this invoice.')));
        }

        $service = new PdfInvoiceService();
        if (!$service->is_enabled()) {
            status_header(404);
            wp_die(esc_html(sltr_t('PDF invoices are disabled.')));
        }

        $package = (new PackageRepository())->get_by_id((int) ($booking['package_id'] ?? 0)) ?: [];
        $service->stream($booking, $package);
        exit;
    }

    public function reschedule_booking(): void
    {
        $token = isset($_POST['token']) ? sanitize_text_field((string) wp_unslash($_POST['token'])) : '';
        if ($token === '' || !wp_verify_nonce((string) wp_unslash((string) ($_POST['_wpnonce'] ?? '')), 'sltr_account_reschedule_' . $token)) {
            wp_safe_redirect(add_query_arg('sltr_account_notice', 'action_failed', $this->accounts->account_url()));
            exit;
        }

        $booking = $this->require_account_booking_owner('reschedule', $token);
        if (is_wp_error($booking)) {
            wp_safe_redirect(add_query_arg('sltr_account_notice', 'action_failed', $this->accounts->account_url()));
            exit;
        }

        $package = (new PackageRepository())->get_by_id((int) ($booking['package_id'] ?? 0)) ?: [];
        $booking_mode = sanitize_key((string) ($booking['booking_mode'] ?? ($package['booking_mode'] ?? 'fixed')));
        if ($booking_mode === 'flexible') { $booking_mode = 'flex'; }
        if ($booking_mode !== 'date_range_inventory') {
            wp_safe_redirect(add_query_arg('sltr_account_notice', 'action_failed', $this->accounts->account_url()));
            exit;
        }

        $date = isset($_POST['date']) ? sanitize_text_field((string) wp_unslash($_POST['date'])) : '';
        $start = isset($_POST['start']) ? sanitize_text_field((string) wp_unslash($_POST['start'])) : '';
        $end = isset($_POST['end']) ? sanitize_text_field((string) wp_unslash($_POST['end'])) : '';
        $result = $this->lifecycle->reschedule_by_token($token, $date, $start, $end);
        wp_safe_redirect(add_query_arg('sltr_account_notice', is_wp_error($result) ? 'reschedule_failed' : 'rescheduled', $this->accounts->account_url()));
        exit;
    }

    /**
     * Account self-service actions must be authorized by the current signed-in
     * email in addition to the per-booking token and form nonce.
     *
     * @return array<string,mixed>|WP_Error
     */
    private function require_account_booking_owner(string $action, string $token)
    {
        $email = strtolower(sanitize_email($this->accounts->current_email()));
        if ($email === '') {
            return new WP_Error('sltr_account_login_required', sltr_t('Please sign in to manage this booking.'));
        }

        $repo = new BookingRepository();
        $booking = $action === 'reschedule'
            ? $repo->get_by_reschedule_token($token)
            : $repo->get_by_cancellation_token($token);

        if (!$booking) {
            return new WP_Error('sltr_invalid_token', sltr_t('Invalid or expired booking token.'));
        }

        $owner_email = strtolower(sanitize_email((string) ($booking['customer_email'] ?? '')));
        if ($owner_email === '' || !hash_equals($owner_email, $email)) {
            return new WP_Error('sltr_booking_owner_mismatch', sltr_t('You do not have access to manage this booking.'));
        }

        return $booking;
    }
}
