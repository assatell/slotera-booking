<?php
declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/**
 * Production marketing suppression helper.
 *
 * Marketing emails are different from transactional booking emails: every campaign
 * recipient must be able to opt out and future campaign queueing must respect it.
 * The suppression record is intentionally stored in a per-email autoload=no option so
 * it works without a schema migration on existing installs and does not affect normal
 * page loads.
 */
final class MarketingOptOutService
{
    private const ACTION = 'sltr_marketing_unsubscribe';

    public function register_hooks(): void
    {
        add_action('template_redirect', [$this, 'maybe_handle_unsubscribe'], 1);
    }

    public function unsubscribe_url(string $email): string
    {
        $email = $this->normalize_email($email);
        if ($email === '') {
            return home_url('/');
        }
        return add_query_arg([
            'sltr_action' => self::ACTION,
            'email' => rawurlencode($email),
            'token' => $this->token_for_email($email),
        ], home_url('/'));
    }

    public function is_unsubscribed(string $email): bool
    {
        $email = $this->normalize_email($email);
        if ($email === '') { return true; }
        return get_option($this->option_key($email), '') !== '';
    }

    public function record(string $email): array
    {
        $email = $this->normalize_email($email);
        if ($email === '') { return []; }
        $record = get_option($this->option_key($email), []);
        return is_array($record) ? $record : [];
    }

    public function delete_record(string $email): bool
    {
        $email = $this->normalize_email($email);
        if ($email === '') { return false; }
        return delete_option($this->option_key($email));
    }

    public function unsubscribe(string $email): bool
    {
        $email = $this->normalize_email($email);
        if ($email === '') { return false; }
        (new MarketingConsentService())->revoke($email);
        return $this->suppress($email, 'unsubscribe_link');
    }

    /**
     * Persist the minimum data needed to prevent future marketing contact.
     * The raw email is deliberately not stored in the record: the keyed option
     * name is sufficient for exact suppression checks while minimizing retained PII.
     */
    public function suppress(string $email, string $source = 'privacy_erasure'): bool
    {
        $email = $this->normalize_email($email);
        if ($email === '') { return false; }
        $record = [
            'unsubscribed_at' => current_time('mysql'),
            'source' => sanitize_key($source),
        ];
        if ($source !== 'privacy_erasure') {
            $record['ip_hash'] = isset($_SERVER['REMOTE_ADDR']) ? hash('sha256', sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) . wp_salt('auth')) : '';
            $record['user_agent_hash'] = isset($_SERVER['HTTP_USER_AGENT']) ? hash('sha256', sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) . wp_salt('auth')) : '';
        }
        return update_option($this->option_key($email), $record, false);
    }

    public function maybe_handle_unsubscribe(): void
    {
        if ((string) ($_GET['sltr_action'] ?? '') !== self::ACTION) { return; }
        $email = $this->normalize_email(rawurldecode((string) ($_GET['email'] ?? '')));
        $token = sanitize_text_field(wp_unslash((string) ($_GET['token'] ?? '')));
        $ok = $email !== '' && hash_equals($this->token_for_email($email), $token);
        if ($ok) { $this->unsubscribe($email); }

        status_header($ok ? 200 : 400);
        nocache_headers();
        get_header();
        echo '<main class="sltr-marketing-unsubscribe" style="max-width:720px;margin:40px auto;padding:24px;">';
        if ($ok) {
            echo '<h1>' . esc_html__('You have been unsubscribed', 'slotera-booking') . '</h1>';
            echo '<p>' . esc_html__('You will no longer receive marketing emails from this site. Booking confirmations and important service emails may still be sent.', 'slotera-booking') . '</p>';
        } else {
            echo '<h1>' . esc_html__('Unsubscribe link is invalid', 'slotera-booking') . '</h1>';
            echo '<p>' . esc_html__('The unsubscribe link is missing required data or has been changed.', 'slotera-booking') . '</p>';
        }
        echo '</main>';
        get_footer();
        exit;
    }

    private function normalize_email(string $email): string
    {
        $email = strtolower(trim(sanitize_email($email)));
        return is_email($email) ? $email : '';
    }

    private function token_for_email(string $email): string
    {
        return hash_hmac('sha256', strtolower($email), wp_salt('auth') . '|slotera-marketing-optout-v1');
    }

    private function option_key(string $email): string
    {
        return 'sltr_marketing_optout_' . hash('sha256', strtolower($email));
    }
}
