<?php
declare(strict_types=1);
namespace Slotera\Application\Services;
use Slotera\Application\Security\SecretStore;
if (!defined('ABSPATH')) { exit; }

final class LicenseService
{
    public const OPTION_NAME = 'sltr_license';
    public const CRON_HOOK = 'sltr_refresh_license';
    public const FULL_TRIAL_DAYS = 30;
    public const GRACE_DAYS = 0;

    public static function activate(): void
    {
        (new self())->ensure_initialized();
        if (!wp_next_scheduled(self::CRON_HOOK)) { wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK); }
    }
    public static function deactivate(): void { wp_clear_scheduled_hook(self::CRON_HOOK); }
    public function register_hooks(): void
    {
        add_action(self::CRON_HOOK, [$this, 'refresh']);
        if (!wp_next_scheduled(self::CRON_HOOK)) { wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK); }
    }
    public function ensure_initialized(): void
    {
        $stored = get_option(self::OPTION_NAME, []);
        if (!is_array($stored)) { $stored = []; }
        $defaults = [
            'license_key' => '', 'licensed_domain' => '', 'license_status' => 'unverified',
            'license_plan' => '', 'license_id' => '', 'license_activated_at' => '',
            'license_expires_at' => '', 'trial_started_at' => '', 'license_last_checked_at' => '',
            'license_last_check_result' => 'not_checked', 'certificate_envelope' => [], 'certificate_issued_at' => '',
        ];
        $next = array_merge($defaults, $stored);
        if ($next !== $stored) { update_option(self::OPTION_NAME, $next, false); }
    }
    public function data(): array
    {
        $this->ensure_initialized();
        $data = get_option(self::OPTION_NAME, []);
        if (!is_array($data)) { return []; }
        if (is_string($data['license_key'] ?? null) && $data['license_key'] !== '') {
            $stored_key = $data['license_key'];
            if (SecretStore::encryption_available() && !SecretStore::is_current_encrypted($stored_key)) {
                $plain_key = SecretStore::decrypt_string($stored_key);
                $rotated_key = SecretStore::encrypt_string($plain_key);
                if ($plain_key !== '' && SecretStore::is_current_encrypted($rotated_key)) {
                    $stored = $data;
                    $stored['license_key'] = $rotated_key;
                    update_option(self::OPTION_NAME, $stored, false);
                    $stored_key = $rotated_key;
                }
            }
            $data['license_key'] = SecretStore::decrypt_string($stored_key);
        }
        return $data;
    }
    public function status(): array
    {
        $data = $this->data();
        // Server-authoritative state: local time alone never converts an active/trial
        // signed certificate into an expired state. A signed expired/revoked response
        // is required, so a licensing outage cannot disable customer functionality.
        $state = (string) ($data['license_status'] ?? 'unverified');
        $expires = (string) ($data['license_expires_at'] ?? '');
        $allowed = in_array($state, ['active', 'trial', 'grace'], true);
        return [
            'state' => $state, 'label' => $this->state_label($state),
            'days_left' => $expires !== '' && strtotime($expires) !== false ? max(0, (int) ceil((strtotime($expires) - time()) / DAY_IN_SECONDS)) : null,
            'trial_started_at' => (string) ($data['trial_started_at'] ?? ''),
            'trial_ends_at' => $state === 'trial' ? $expires : '', 'grace_ends_at' => '',
            'license_expires_at' => $expires,
            'marketing_allowed' => $allowed, 'automation_allowed' => $allowed,
            'advanced_marketing_allowed' => $allowed, 'unique_coupons_allowed' => $allowed,
            'queue_settings_allowed' => $allowed, 'marketing_batch_limit' => $allowed ? 50 : 0,
        ];
    }
    public function activate_license(string $key): bool
    {
        $key = strtoupper(trim(sanitize_text_field($key)));
        if (!preg_match('/^SLTR-[A-F0-9]{8}-[A-F0-9]{8}-[A-F0-9]{8}$/', $key) || !SecretStore::encryption_available()) { return false; }
        return $this->request_and_store('activate', $key);
    }
    public function start_trial(): bool
    {
        $data = $this->data();
        if ((string) ($data['license_key'] ?? '') !== '' || (string) ($data['license_status'] ?? '') !== 'unverified') { return false; }
        return $this->request_and_store('start_trial', '');
    }
    public function refresh(): bool
    {
        $data = $this->data();
        $key = (string) ($data['license_key'] ?? '');
        if ($key === '') { return false; }
        return $this->request_and_store('refresh', $key);
    }
    public function deactivate_license(): void
    {
        $data = $this->data();
        foreach (['license_key','licensed_domain','license_plan','license_id','license_activated_at','license_expires_at','trial_started_at'] as $field) { $data[$field] = ''; }
        $data['license_status'] = 'unverified';
        $data['license_last_check_result'] = 'local_key_removed';
        $data['certificate_envelope'] = [];
        $data['certificate_issued_at'] = '';
        $this->store($data);
    }
    public function prepared_license_fields(): array
    {
        $data = $this->data();
        return [
            'license_key' => SecretStore::mask((string) ($data['license_key'] ?? '')),
            'licensed_domain' => (string) ($data['licensed_domain'] ?? ''),
            'license_status' => (string) ($data['license_status'] ?? 'unverified'),
            'license_plan' => (string) ($data['license_plan'] ?? ''),
            'license_expires_at' => (string) ($data['license_expires_at'] ?? ''),
            'license_last_checked_at' => (string) ($data['license_last_checked_at'] ?? ''),
            'license_last_check_result' => (string) ($data['license_last_check_result'] ?? 'not_checked'),
        ];
    }
    public function trial_message(): string { return sprintf(__('License status: %s.', 'slotera-booking'), (string) $this->status()['label']); }
    public function has_built_in_license(): bool { return false; }
    public function check_license_locally(): void { $this->refresh(); }
    public function can_use_marketing(): bool { return !empty($this->status()['marketing_allowed']); }
    public function can_use_automations(): bool { return !empty($this->status()['automation_allowed']); }
    public function can_use_advanced_marketing(): bool { return !empty($this->status()['advanced_marketing_allowed']); }
    public function can_use_unique_coupons(): bool { return !empty($this->status()['unique_coupons_allowed']); }
    public function can_manage_queue_settings(): bool { return !empty($this->status()['queue_settings_allowed']); }
    public function marketing_batch_limit(): int { return (int) $this->status()['marketing_batch_limit']; }
    public function is_grace_limited(): bool { return $this->status()['state'] === 'grace'; }

    private function request_and_store(string $operation, string $key): bool
    {
        $body = ['schema' => 'slotera-license-request/v1', 'plugin' => 'slotera-booking', 'operation' => $operation, 'site_url' => home_url('/')];
        if ($key !== '') { $body['license_key'] = $key; }
        $response = wp_safe_remote_post(sltr_license_api_url(), [
            'timeout' => 15, 'redirection' => 0, 'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode($body), 'data_format' => 'body',
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $this->record_failed_check(is_wp_error($response) ? 'server_unavailable' : 'request_rejected');
            return false;
        }
        $envelope = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($envelope)) { $this->record_failed_check('invalid_response'); return false; }
        $data = $this->data();
        $payload = (new LicenseCertificateVerifier())->verify($envelope, $this->current_domain(), (string) ($data['certificate_issued_at'] ?? ''));
        if ($payload === null) { $this->record_failed_check('signature_rejected'); return false; }
        $previousIssued = (string) ($data['certificate_issued_at'] ?? '');
        $previousEnvelope = $data['certificate_envelope'] ?? [];
        if ($previousIssued !== '' && hash_equals($previousIssued, (string) $payload['issued_at'])
            && is_array($previousEnvelope) && $previousEnvelope !== $envelope) {
            $this->record_failed_check('replay_rejected');
            return false;
        }
        if ($key !== '') {
            $encrypted = SecretStore::encrypt_string($key);
            if (!SecretStore::is_current_encrypted($encrypted)) { return false; }
            $data['license_key'] = $encrypted;
        }
        $data['licensed_domain'] = (string) $payload['licensed_root'];
        $data['license_status'] = (string) $payload['state'];
        $data['license_plan'] = (string) ($payload['plan'] ?? '');
        $data['license_id'] = (string) ($payload['license_id'] ?? '');
        $data['license_expires_at'] = (string) ($payload['expires_at'] ?? '');
        $data['trial_started_at'] = (string) ($payload['trial_started_at'] ?? '');
        if ($operation === 'activate') { $data['license_activated_at'] = gmdate('c'); }
        $data['license_last_checked_at'] = gmdate('c');
        $data['license_last_check_result'] = 'verified';
        $data['certificate_envelope'] = $envelope;
        $data['certificate_issued_at'] = (string) $payload['issued_at'];
        $this->store($data);
        return true;
    }
    private function record_failed_check(string $result): void
    {
        $data = $this->data();
        $data['license_last_checked_at'] = gmdate('c');
        $data['license_last_check_result'] = $result;
        // Fail open: retain the last valid signed certificate and state indefinitely.
        $this->store($data);
    }
    private function store(array $data): void
    {
        $key = $data['license_key'] ?? '';
        if (is_string($key) && $key !== '' && !SecretStore::is_encrypted($key)) {
            $encrypted = SecretStore::encrypt_string($key);
            if (SecretStore::is_current_encrypted($encrypted)) {
                $data['license_key'] = $encrypted;
            } else {
                $current = get_option(self::OPTION_NAME, []);
                $data['license_key'] = is_array($current) && is_string($current['license_key'] ?? null)
                    ? $current['license_key']
                    : '';
            }
        }
        update_option(self::OPTION_NAME, $data, false);
    }
    private function current_domain(): string { $host = wp_parse_url(home_url('/'), PHP_URL_HOST); return is_string($host) ? strtolower($host) : ''; }
    private function state_label(string $state): string
    {
        $labels = ['active' => __('Active', 'slotera-booking'), 'trial' => __('Trial', 'slotera-booking'), 'grace' => __('Grace period', 'slotera-booking'), 'expired' => __('Expired', 'slotera-booking'), 'revoked' => __('Revoked', 'slotera-booking'), 'unverified' => __('Not activated', 'slotera-booking')];
        return $labels[$state] ?? $labels['unverified'];
    }
}
