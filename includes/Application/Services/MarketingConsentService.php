<?php
declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/** Stores explicit, auditable marketing consent without exposing an email in option names. */
final class MarketingConsentService
{
    private const POLICY_VERSION = 'slotera-marketing-consent-v1';

    public function grant(string $email, string $source = 'booking_form'): bool
    {
        $email = $this->normalize_email($email);
        if ($email === '') { return false; }
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : '';
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) : '';
        return update_option($this->option_key($email), [
            'email' => $email,
            'granted_at' => current_time('mysql'),
            'source' => sanitize_key($source),
            'policy_version' => self::POLICY_VERSION,
            'ip_hash' => $ip !== '' ? hash_hmac('sha256', $ip, wp_salt('auth')) : '',
            'user_agent_hash' => $agent !== '' ? hash_hmac('sha256', $agent, wp_salt('auth')) : '',
        ], false);
    }

    public function has_consent(string $email): bool
    {
        $email = $this->normalize_email($email);
        if ($email === '') { return false; }
        $record = get_option($this->option_key($email), []);
        return is_array($record)
            && !empty($record['granted_at'])
            && hash_equals(self::POLICY_VERSION, (string) ($record['policy_version'] ?? ''));
    }

    public function record(string $email): array
    {
        $email = $this->normalize_email($email);
        if ($email === '') { return []; }
        $record = get_option($this->option_key($email), []);
        return is_array($record) ? $record : [];
    }

    public function revoke(string $email): bool
    {
        $email = $this->normalize_email($email);
        return $email !== '' && delete_option($this->option_key($email));
    }

    private function normalize_email(string $email): string
    {
        $email = strtolower(trim(sanitize_email($email)));
        return is_email($email) ? $email : '';
    }

    private function option_key(string $email): string
    {
        return 'sltr_marketing_consent_' . hash_hmac('sha256', strtolower($email), wp_salt('auth'));
    }
}
