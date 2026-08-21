<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\BusinessValidator;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class SecurityController
{
    private SettingsRepository $settings;
    private RequestValidator $request;

    public function __construct(?SettingsRepository $settings = null, ?RequestValidator $request = null)
    {
        $this->settings = $settings ?? new SettingsRepository();
        $this->request = $request ?? new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_security_settings', [$this, 'save_security_settings']);
    }


    private function post_duration_minutes(string $base, int $default = 0): int
    {
        $post = wp_unslash($_POST);
        if (isset($post[$base . '_hours']) || isset($post[$base . '_mins'])) {
            $hours = isset($post[$base . '_hours']) ? absint($post[$base . '_hours']) : 0;
            $mins = isset($post[$base . '_mins']) ? absint($post[$base . '_mins']) : 0;
            return BusinessValidator::duration_from_hours_minutes($hours, $mins, $default, 1, 10080);
        }
        return BusinessValidator::duration_minutes($this->request->post_int($base . '_minutes', $default), $default, 1, 10080);
    }


    private function preserve_or_replace_secret(string $key, array $current): string
    {
        if (!empty($_POST[$key . '_clear'])) {
            return '';
        }
        $posted = isset($_POST[$key]) ? wp_unslash((string) $_POST[$key]) : '';
        $posted = (string) preg_replace('/[\x00\r\n]/', '', $posted);
        if ($posted === '') {
            return (string) ($current[$key] ?? '');
        }
        return $posted;
    }

    public function save_security_settings(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce('sltr_save_security_settings', 'sltr_nonce');

        $current_settings = $this->settings->all();
        $api_key = $this->preserve_or_replace_secret('security_public_rest_booking_api_key', $current_settings);
        $hmac_secret = $this->preserve_or_replace_secret('security_public_rest_booking_hmac_secret', $current_settings);
        $turnstile_secret = $this->preserve_or_replace_secret('security_turnstile_secret_key', $current_settings);
        $recaptcha_secret = $this->preserve_or_replace_secret('security_recaptcha_secret_key', $current_settings);

        $this->settings->update([
            'security_honeypot_enabled' => $this->request->post_bool('security_honeypot_enabled'),
            'security_public_rest_booking_enabled' => $this->request->post_bool('security_public_rest_booking_enabled'),
            'security_public_rest_booking_security_reviewed' => $this->request->post_bool('security_public_rest_booking_security_reviewed'),
            'security_public_rest_booking_auth_mode' => $this->request->post_key('security_public_rest_booking_auth_mode', 'site_form'),
            'security_public_rest_booking_api_key' => $api_key,
            'security_public_rest_booking_hmac_secret' => $hmac_secret,
            'security_min_submit_seconds' => $this->request->post_int('security_min_submit_seconds', 4),
            'security_rate_limit_ip_enabled' => $this->request->post_bool('security_rate_limit_ip_enabled'),
            'security_rate_limit_ip_attempts' => $this->request->post_int('security_rate_limit_ip_attempts', 30),
            'security_availability_rate_limit_enabled' => $this->request->post_bool('security_availability_rate_limit_enabled'),
            'security_availability_rate_limit_attempts' => $this->request->post_int('security_availability_rate_limit_attempts', 120),
            'security_rate_limit_email_enabled' => $this->request->post_bool('security_rate_limit_email_enabled'),
            'security_rate_limit_email_attempts' => $this->request->post_int('security_rate_limit_email_attempts', 10),
            'security_rate_limit_window_minutes' => $this->post_duration_minutes('security_rate_limit_window', 15),
            'security_trusted_ips' => $this->request->post_textarea('security_trusted_ips'),
            'security_trusted_proxies' => $this->request->post_textarea('security_trusted_proxies'),
            'security_captcha_provider' => $this->request->post_key('security_captcha_provider', 'none'),
            'security_turnstile_site_key' => $this->request->post_text('security_turnstile_site_key'),
            'security_turnstile_secret_key' => $turnstile_secret,
            'security_recaptcha_site_key' => $this->request->post_text('security_recaptcha_site_key'),
            'security_recaptcha_secret_key' => $recaptcha_secret,
            'security_recaptcha_v3_threshold' => $this->request->post_text('security_recaptcha_v3_threshold', '0.5'),
        ]);

        wp_safe_redirect(add_query_arg([
            'page' => 'slotera-settings',
            'section' => 'security',
            'security_tab' => 'protection',
            'sltr_message' => 'saved',
        ], admin_url('admin.php')));
        exit;
    }
}
