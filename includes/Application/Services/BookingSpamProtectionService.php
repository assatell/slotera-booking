<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Http\ClientIpResolver;
use Slotera\Infrastructure\Http\RateLimiter;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class BookingSpamProtectionService
{
    private const HONEYPOT_FIELD = 'company_website';
    private const FORM_TIME_FIELD = 'form_started_at';

    private array $settings;

    public function __construct(?SettingsRepository $settings = null)
    {
        $this->settings = ($settings ?? new SettingsRepository())->all();
    }

    public function validate_frontend_submission(array $data, string $context = 'booking')
    {
        $context = $this->normalize_context($context);
        $ip = ClientIpResolver::get_client_ip();

        if ($this->is_trusted_ip($ip)) {
            return true;
        }

        if (!empty($this->settings['security_honeypot_enabled'])) {
            $honeypot = sanitize_text_field((string) ($data[self::HONEYPOT_FIELD] ?? ''));
            if ($honeypot !== '') {
                return new WP_Error('sltr_spam_detected', __('Booking request could not be processed.', 'slotera-booking'));
            }
        }

        $min_seconds = max(0, (int) ($this->settings['security_min_submit_seconds'] ?? 4));
        if ($min_seconds > 0) {
            $started_at = absint($data[self::FORM_TIME_FIELD] ?? 0);
            if ($started_at <= 0 || (time() - $started_at) < $min_seconds) {
                return new WP_Error('sltr_form_submitted_too_quickly', __('Please wait a moment before submitting the booking form.', 'slotera-booking'));
            }
        }

        $captcha_check = $this->validate_captcha($data, $ip, $context);
        if (is_wp_error($captcha_check)) {
            return $captcha_check;
        }

        if (!empty($this->settings['security_rate_limit_ip_enabled']) && $ip !== '') {
            $limit = $this->effective_limit('ip', (int) ($this->settings['security_rate_limit_ip_attempts'] ?? 30), $context);
            if (!$this->increment_and_check('ip_' . md5($ip), $limit, $context)) {
                return new WP_Error('sltr_rate_limited', $this->rate_limit_message($context));
            }
        }

        if (!empty($this->settings['security_rate_limit_email_enabled'])) {
            $email = sanitize_email((string) ($data['customer_email'] ?? ''));
            $limit = $this->effective_limit('email', (int) ($this->settings['security_rate_limit_email_attempts'] ?? 10), $context);
            if ($email !== '' && !$this->increment_and_check('email_' . md5(strtolower($email)), $limit, $context)) {
                return new WP_Error('sltr_rate_limited', $this->rate_limit_message($context));
            }
        }

        return true;
    }

    private function validate_captcha(array $data, string $ip, string $context)
    {
        $provider = sanitize_key((string) ($this->settings['security_captcha_provider'] ?? 'none'));

        if ($provider === 'none') {
            return true;
        }

        if ($provider === 'turnstile') {
            $secret = trim((string) ($this->settings['security_turnstile_secret_key'] ?? ''));
            $token = sanitize_text_field((string) ($data['cf_turnstile_response'] ?? ''));
            return $this->verify_remote_captcha('https://challenges.cloudflare.com/turnstile/v0/siteverify', $secret, $token, $ip);
        }

        if ($provider === 'recaptcha') {
            $secret = trim((string) ($this->settings['security_recaptcha_secret_key'] ?? ''));
            $token = sanitize_text_field((string) ($data['g_recaptcha_response'] ?? ''));
            return $this->verify_remote_captcha('https://www.google.com/recaptcha/api/siteverify', $secret, $token, $ip);
        }

        if ($provider === 'recaptcha_v3') {
            $secret = trim((string) ($this->settings['security_recaptcha_secret_key'] ?? ''));
            $token = sanitize_text_field((string) ($data['g_recaptcha_response'] ?? ''));
            $expected_action = $context === 'contact' ? 'slotera_contact' : 'slotera_booking';
            $threshold = max(0.0, min(1.0, (float) ($this->settings['security_recaptcha_v3_threshold'] ?? 0.5)));
            return $this->verify_recaptcha_v3($secret, $token, $ip, $expected_action, $threshold);
        }

        return true;
    }

    private function verify_remote_captcha(string $url, string $secret, string $token, string $ip)
    {
        if ($secret === '' || $token === '') {
            return new WP_Error('sltr_captcha_required', __('Please complete the security challenge.', 'slotera-booking'));
        }

        $response = wp_remote_post($url, [
            'timeout' => 10,
            'body' => array_filter([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('sltr_captcha_unavailable', __('Security challenge could not be verified. Please try again.', 'slotera-booking'));
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($body) || empty($body['success'])) {
            return new WP_Error('sltr_captcha_failed', __('Security challenge failed. Please try again.', 'slotera-booking'));
        }

        return true;
    }

    private function verify_recaptcha_v3(string $secret, string $token, string $ip, string $expected_action, float $threshold)
    {
        if ($secret === '' || $token === '') {
            return new WP_Error('sltr_captcha_required', __('Please complete the security challenge.', 'slotera-booking'));
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 10,
            'body' => array_filter([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('sltr_captcha_unavailable', __('Security challenge could not be verified. Please try again.', 'slotera-booking'));
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $action = is_array($body) ? sanitize_key((string) ($body['action'] ?? '')) : '';
        $score = is_array($body) && isset($body['score']) ? (float) $body['score'] : -1.0;

        if (!is_array($body) || empty($body['success']) || $action !== $expected_action || $score < $threshold) {
            return new WP_Error('sltr_captcha_failed', __('Security challenge failed. Please try again.', 'slotera-booking'));
        }

        return true;
    }

    private function increment_and_check(string $key, int $limit, string $context): bool
    {
        $window = max(1, (int) ($this->settings['security_rate_limit_window_minutes'] ?? 15)) * MINUTE_IN_SECONDS;
        $namespace = $context === 'contact' ? 'contact_form' : 'booking_create_v2';
        $attempts = RateLimiter::increment($namespace, $key, $window);

        return $attempts <= $limit;
    }

    private function normalize_context(string $context): string
    {
        return $context === 'contact' ? 'contact' : 'booking';
    }

    private function effective_limit(string $identity_type, int $configured_limit, string $context): int
    {
        $configured_limit = max(1, $configured_limit);

        if ($context === 'booking') {
            return $identity_type === 'email' ? max(10, $configured_limit) : max(30, $configured_limit);
        }

        return $configured_limit;
    }

    private function rate_limit_message(string $context): string
    {
        if ($context === 'contact') {
            return __('Too many contact form attempts. Please try again later.', 'slotera-booking');
        }

        return __('Too many booking attempts. Please try again later.', 'slotera-booking');
    }

    private function is_trusted_ip(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }

        $trusted = (string) ($this->settings['security_trusted_ips'] ?? '');
        $lines = preg_split('/\r\n|\r|\n/', $trusted);

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

}
