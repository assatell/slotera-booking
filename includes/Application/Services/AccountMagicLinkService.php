<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\ActivityLogRepository;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class AccountMagicLinkService
{
    public const CRON_HOOK = 'sltr_cleanup_magic_link_options';
    private const COOKIE = 'sltr_account_session';
    private const TTL = 14 * DAY_IN_SECONDS;
    private const MAGIC_TTL = 30 * MINUTE_IN_SECONDS;
    private const MAGIC_OPTION_PREFIX = 'sltr_magic_login_';
    private const CONFIRM_OPTION_PREFIX = 'sltr_magic_confirm_';
    private const CONFIRM_COOKIE = 'sltr_magic_confirm';
    private const CONFIRM_TTL = 10 * MINUTE_IN_SECONDS;

    private BookingRepository $bookings;
    private SettingsRepository $settings;
    private ActivityLogRepository $logs;

    public function __construct(?BookingRepository $bookings = null, ?SettingsRepository $settings = null, ?ActivityLogRepository $logs = null)
    {
        $this->bookings = $bookings ?: new BookingRepository();
        $this->settings = $settings ?: new SettingsRepository();
        $this->logs = $logs ?: new ActivityLogRepository();
    }

    public static function activate(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK);
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public function register_hooks(): void
    {
        add_action(self::CRON_HOOK, [$this, 'run_scheduled_cleanup']);
    }

    public function run_scheduled_cleanup(): void
    {
        $this->cleanup_expired_magic_options();
    }

    public function current_email(): string
    {
        if (empty($_COOKIE[self::COOKIE])) { return ''; }
        $payload = $this->decode_session(sanitize_text_field((string) wp_unslash($_COOKIE[self::COOKIE])));
        if (!$payload) { return ''; }
        return sanitize_email((string) ($payload['email'] ?? ''));
    }

    public function is_logged_in(): bool
    {
        return $this->current_email() !== '';
    }

    public function send_magic_link(string $email): bool
    {
        $email = sanitize_email($email);
        if ($email === '' || !is_email($email)) { return false; }

        if (!$this->bookings->customer_email_exists($email)) {
            // Keep the frontend response generic, but do not send account links to unknown emails.
            return true;
        }

        $expires = time() + self::MAGIC_TTL;
        $token = $this->new_magic_token();
        $this->store_magic_token($email, $expires, $token);

        $url = add_query_arg([
            'sltr_magic_login' => '1',
            'sltr_email' => $email,
            'sltr_expires' => (string) $expires,
            'sltr_token' => $token,
        ], $this->account_url());

        $subject = sprintf(\sltr_account_t('magic_link_subject'), get_bloginfo('name'));
        $message = '<p>' . esc_html(\sltr_account_t('magic_link_open_account')) . '</p>'
            . '<p><a href="' . esc_url($url) . '" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#2563eb;color:#ffffff;text-decoration:none;">' . esc_html(\sltr_account_t('open_my_bookings')) . '</a></p>'
            . '<p>' . esc_html(\sltr_account_t('magic_link_ignore')) . '</p>';

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sent = wp_mail($email, $subject, $message, $headers);

        $this->logs->create([
            'object_type' => 'account',
            'object_id' => 0,
            'event' => $sent ? 'magic_link_sent' : 'magic_link_failed',
            'actor_type' => 'customer',
            'actor_id' => 0,
            'status' => $sent ? 'success' : 'error',
            'message' => $sent ? 'Client account magic link sent.' : 'Client account magic link failed.',
            'payload' => ['email' => $email],
        ]);

        return $sent;
    }

    public function consume_magic_link(string $email, int $expires, string $token): bool
    {
        $email = sanitize_email(rawurldecode($email));
        $token = sanitize_text_field($token);
        if (!$this->is_magic_link_valid($email, $expires, $token)) { return false; }
        if (!$this->consume_stored_magic_token($email, $expires, $token)) { return false; }

        return $this->authenticate_email($email);
    }

    /**
     * Exchange URL credentials for a short-lived HttpOnly browser handle.
     * The original email/token disappear from the address bar before any
     * theme/plugin frontend stack is rendered.
     */
    public function begin_magic_confirmation(string $email, int $expires, string $token): bool
    {
        $email = sanitize_email(rawurldecode($email));
        $token = sanitize_text_field($token);
        if (!$this->is_magic_link_valid($email, $expires, $token)) { return false; }

        try {
            $handle = bin2hex(random_bytes(32));
        } catch (\Throwable $e) {
            $handle = wp_generate_password(64, false, false);
        }
        if ($handle === '') { return false; }

        $payload = [
            'email' => $email,
            'expires' => $expires,
            'token' => $token,
            'confirm_expires' => time() + self::CONFIRM_TTL,
        ];
        if (!update_option($this->confirmation_option_key($handle), $payload, false)) { return false; }
        $this->set_confirmation_cookie($handle, time() + self::CONFIRM_TTL);
        $_COOKIE[self::CONFIRM_COOKIE] = $handle;
        return true;
    }

    public function has_pending_magic_confirmation(): bool
    {
        $handle = $this->confirmation_cookie_value();
        if ($handle === '') { return false; }
        $payload = get_option($this->confirmation_option_key($handle), false);
        if (!is_array($payload) || (int) ($payload['confirm_expires'] ?? 0) < time()) {
            delete_option($this->confirmation_option_key($handle));
            $this->clear_confirmation_cookie();
            return false;
        }
        return true;
    }

    /** Returns the authenticated email on success, or an empty string. */
    public function consume_magic_confirmation(): string
    {
        global $wpdb;
        $handle = $this->confirmation_cookie_value();
        if ($handle === '') { return ''; }
        $option_key = $this->confirmation_option_key($handle);
        $payload = get_option($option_key, false);
        $this->clear_confirmation_cookie();
        if (!is_array($payload) || (int) ($payload['confirm_expires'] ?? 0) < time()) {
            delete_option($option_key);
            return '';
        }

        $deleted = $wpdb->delete(
            $wpdb->options,
            ['option_name' => $option_key, 'option_value' => maybe_serialize($payload)],
            ['%s', '%s']
        );
        wp_cache_delete($option_key, 'options');
        if ($deleted !== 1) { return ''; }

        $email = sanitize_email((string) ($payload['email'] ?? ''));
        $expires = (int) ($payload['expires'] ?? 0);
        $token = sanitize_text_field((string) ($payload['token'] ?? ''));
        return $this->consume_magic_link($email, $expires, $token) ? $email : '';
    }

    public function is_magic_link_valid(string $email, int $expires, string $token): bool
    {
        $email = sanitize_email(rawurldecode($email));
        $token = sanitize_text_field($token);
        if ($email === '' || !is_email($email) || $expires < time() || $token === '') { return false; }
        return $this->peek_stored_magic_token($email, $expires, $token);
    }

    public function authenticate_email(string $email): bool
    {
        $email = sanitize_email($email);
        if ($email === '' || !is_email($email)) { return false; }
        if (!$this->bookings->customer_email_exists($email)) { return false; }

        $this->set_session($email);
        return true;
    }

    public function logout(): void
    {
        $this->set_cookie('', time() - HOUR_IN_SECONDS);
        unset($_COOKIE[self::COOKIE]);
    }

    public function account_url(): string
    {
        return $this->page_url('account_page_id', 'slotera_account') ?: $this->page_url('login_page_id', 'slotera_login') ?: home_url('/');
    }

    public function login_url(): string
    {
        return $this->page_url('login_page_id', 'slotera_login') ?: $this->account_url();
    }


    private function page_url(string $setting_key, string $shortcode): string
    {
        $page_id = (int) $this->settings->get($setting_key, 0);
        $url = $this->permalink_for_page_with_shortcode($page_id, $shortcode);
        if ($url !== '') {
            return $url;
        }

        $detected_id = $this->find_page_id_by_shortcode($shortcode);
        $url = $this->permalink_for_page_with_shortcode($detected_id, $shortcode);
        if ($url !== '') {
            return $url;
        }

        return '';
    }

    private function permalink_for_page_with_shortcode(int $page_id, string $shortcode): string
    {
        if ($page_id <= 0) {
            return '';
        }

        $post = get_post($page_id);
        if (!$post || $post->post_type !== 'page' || $post->post_status !== 'publish') {
            return '';
        }

        if (!$this->post_contains_shortcode((string) $post->post_content, $shortcode)) {
            return '';
        }

        $url = get_permalink($page_id);
        return is_string($url) ? $url : '';
    }

    private function find_page_id_by_shortcode(string $shortcode): int
    {
        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => '[' . $shortcode,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        foreach ((array) $pages as $page_id) {
            $post = get_post((int) $page_id);
            if ($post && $this->post_contains_shortcode((string) $post->post_content, $shortcode)) {
                return (int) $page_id;
            }
        }

        return 0;
    }

    private function post_contains_shortcode(string $content, string $shortcode): bool
    {
        return preg_match('/\[' . preg_quote($shortcode, '/') . '\b/i', $content) === 1;
    }

    private function set_session(string $email): void
    {
        $expires = time() + self::TTL;
        $payload = base64_encode(wp_json_encode(['email' => $email, 'expires' => $expires]) ?: '');
        $sig = hash_hmac('sha256', $payload, wp_salt('auth'));
        $this->set_cookie($payload . '.' . $sig, $expires);
        $_COOKIE[self::COOKIE] = $payload . '.' . $sig;
    }

    private function decode_session(string $cookie): ?array
    {
        [$payload, $sig] = array_pad(explode('.', $cookie, 2), 2, '');
        if ($payload === '' || $sig === '' || !hash_equals(hash_hmac('sha256', $payload, wp_salt('auth')), $sig)) { return null; }
        $data = json_decode((string) base64_decode($payload, true), true);
        if (!is_array($data) || (int) ($data['expires'] ?? 0) < time()) { return null; }
        $email = sanitize_email((string) ($data['email'] ?? ''));
        return $email !== '' ? ['email' => $email] : null;
    }

    private function new_magic_token(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (\Throwable $e) {
            return wp_generate_password(64, false, false);
        }
    }

    private function store_magic_token(string $email, int $expires, string $token): void
    {
        // Also clean opportunistically so busy sites do not depend solely on WP-Cron.
        $this->cleanup_expired_magic_options(100);
        $payload = [
            'email_hash' => $this->magic_email_hash($email),
            'expires' => $expires,
            'created_at' => time(),
        ];

        $ttl = max(1, $expires - time());
        set_transient($this->magic_transient_key($email, $expires, $token), $payload, $ttl);

        // Persistent fallback for hosts/object-cache plugins that aggressively
        // drop transients between email generation and the first click. The
        // option name is keyed by HMAC only and contains no raw token/email.
        update_option($this->magic_option_key($email, $expires, $token), $payload, false);
    }

    /**
     * Removes bounded batches of expired or malformed persistent fallbacks.
     * Option names are immutable per token, so delete_option() cannot remove a
     * refreshed token created by another request.
     */
    public function cleanup_expired_magic_options(int $limit = 1000): int
    {
        global $wpdb;
        if (!isset($wpdb->options)) { return 0; }

        $limit = max(1, min(1000, $limit));
        $magic_like = $wpdb->esc_like(self::MAGIC_OPTION_PREFIX) . '%';
        $confirm_like = $wpdb->esc_like(self::CONFIRM_OPTION_PREFIX) . '%';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s ORDER BY option_id ASC LIMIT %d",
                $magic_like,
                $confirm_like,
                $limit
            ),
            ARRAY_A
        );
        if (!is_array($rows)) { return 0; }

        $now = time();
        $deleted = 0;
        foreach ($rows as $row) {
            $option_name = (string) ($row['option_name'] ?? '');
            $is_magic = strpos($option_name, self::MAGIC_OPTION_PREFIX) === 0;
            $is_confirm = strpos($option_name, self::CONFIRM_OPTION_PREFIX) === 0;
            if (!$is_magic && !$is_confirm) { continue; }
            $payload = maybe_unserialize($row['option_value'] ?? null);
            $expires = is_array($payload)
                ? (int) ($is_confirm ? ($payload['confirm_expires'] ?? 0) : ($payload['expires'] ?? 0))
                : 0;
            if ($expires > $now) { continue; }
            if (delete_option($option_name)) { $deleted++; }
        }
        return $deleted;
    }

    private function peek_stored_magic_token(string $email, int $expires, string $token): bool
    {
        $stored = get_transient($this->magic_transient_key($email, $expires, $token));
        if (!is_array($stored)) {
            $stored = get_option($this->magic_option_key($email, $expires, $token), false);
        }
        if (!is_array($stored)) {
            return false;
        }

        $stored_expires = (int) ($stored['expires'] ?? 0);
        $stored_email_hash = (string) ($stored['email_hash'] ?? '');
        if ($stored_expires !== $expires || $stored_expires < time() || $stored_email_hash === '') {
            delete_transient($this->magic_transient_key($email, $expires, $token));
            delete_option($this->magic_option_key($email, $expires, $token));
            return false;
        }

        return hash_equals($stored_email_hash, $this->magic_email_hash($email));
    }

    private function consume_stored_magic_token(string $email, int $expires, string $token): bool
    {
        global $wpdb;
        $key = $this->magic_transient_key($email, $expires, $token);
        $option_key = $this->magic_option_key($email, $expires, $token);
        // The persistent fallback is the authoritative one-time record. Claim
        // it with an exact compare-and-delete so only one concurrent request
        // can observe a successful affected-row count.
        $stored = get_option($option_key, false);
        if (!is_array($stored)) {
            return false;
        }

        $deleted = $wpdb->delete(
            $wpdb->options,
            ['option_name' => $option_key, 'option_value' => maybe_serialize($stored)],
            ['%s', '%s']
        );
        wp_cache_delete($option_key, 'options');
        if ($deleted !== 1) {
            return false;
        }
        delete_transient($key);

        $stored_expires = (int) ($stored['expires'] ?? 0);
        $stored_email_hash = (string) ($stored['email_hash'] ?? '');
        if ($stored_expires !== $expires || $stored_expires < time() || $stored_email_hash === '') {
            return false;
        }

        return hash_equals($stored_email_hash, $this->magic_email_hash($email));
    }

    private function magic_transient_key(string $email, int $expires, string $token): string
    {
        return 'sltr_magic_' . $this->magic_storage_hash($email, $expires, $token);
    }

    private function magic_option_key(string $email, int $expires, string $token): string
    {
        return self::MAGIC_OPTION_PREFIX . $this->magic_storage_hash($email, $expires, $token);
    }

    private function magic_storage_hash(string $email, int $expires, string $token): string
    {
        return hash_hmac('sha256', strtolower($email) . '|' . $expires . '|' . $token, wp_salt('auth'));
    }

    private function magic_email_hash(string $email): string
    {
        return hash_hmac('sha256', strtolower($email), wp_salt('auth'));
    }

    private function confirmation_option_key(string $handle): string
    {
        return self::CONFIRM_OPTION_PREFIX . hash_hmac('sha256', $handle, wp_salt('auth'));
    }

    private function confirmation_cookie_value(): string
    {
        if (empty($_COOKIE[self::CONFIRM_COOKIE])) { return ''; }
        $handle = sanitize_text_field((string) wp_unslash($_COOKIE[self::CONFIRM_COOKIE]));
        return preg_match('/^[A-Za-z0-9]+$/', $handle) ? $handle : '';
    }

    private function set_confirmation_cookie(string $value, int $expires): void
    {
        setcookie(self::CONFIRM_COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            // Keep Slotera account cookies host-only. Inheriting COOKIE_DOMAIN
            // can make the browser reject or withhold this plugin-owned cookie
            // when WordPress is behind a proxy or uses a legacy cookie domain.
            'secure' => is_ssl() || wp_parse_url(home_url(), PHP_URL_SCHEME) === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clear_confirmation_cookie(): void
    {
        $this->set_confirmation_cookie('', time() - HOUR_IN_SECONDS);
        unset($_COOKIE[self::CONFIRM_COOKIE]);
    }

    private function set_cookie(string $value, int $expires): void
    {
        setcookie(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            // Keep Slotera account cookies host-only. Inheriting COOKIE_DOMAIN
            // can make the browser reject or withhold this plugin-owned cookie
            // when WordPress is behind a proxy or uses a legacy cookie domain.
            'secure' => is_ssl() || wp_parse_url(home_url(), PHP_URL_SCHEME) === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
