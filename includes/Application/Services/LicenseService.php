<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Application\Security\SecretStore;

if (!defined('ABSPATH')) { exit; }

final class LicenseService
{
    public const OPTION_NAME = 'sltr_license';
    public const FULL_TRIAL_DAYS = 0;
    public const GRACE_DAYS = 0;
    private const BUILT_IN_LICENSE_ROOT_DOMAINS = ['partytime.ee'];

    public function ensure_initialized(): void
    {
        $data = get_option(self::OPTION_NAME, []);
        if (!is_array($data)) { $data = []; }

        $changed = false;
        if (empty($data['trial_started_at'])) {
            $data['trial_started_at'] = current_time('mysql');
            $data['license_status'] = 'development_placeholder';
            $changed = true;
        }

        $defaults = [
            'license_key' => '',
            'licensed_domain' => $this->current_domain(),
            'license_status' => 'development_placeholder',
            'license_activated_at' => '',
            'license_expires_at' => '',
            'license_last_checked_at' => '',
            'license_last_check_result' => 'not_checked',
        ];

        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $value;
                $changed = true;
            }
        }

        if (empty($data['licensed_domain'])) {
            $data['licensed_domain'] = $this->current_domain();
            $changed = true;
        }

        if ($changed) {
            update_option(self::OPTION_NAME, $data, false);
        }
    }

    public static function activate(): void
    {
        (new self())->ensure_initialized();
    }

    public function data(): array
    {
        $this->ensure_initialized();
        $data = get_option(self::OPTION_NAME, []);
        if (!is_array($data)) { return []; }
        if (!empty($data['license_key']) && is_string($data['license_key'])) {
            $stored_key = $data['license_key'];

            if (SecretStore::encryption_available()) {
                if (!SecretStore::is_current_encrypted($stored_key)) {
                    $plain_key = SecretStore::decrypt_string($stored_key);
                    if ($plain_key !== '') {
                        $rotated_key = SecretStore::encrypt_string($plain_key);
                        if (SecretStore::is_current_encrypted($rotated_key)) {
                            $stored = $data;
                            $stored['license_key'] = $rotated_key;
                            update_option(self::OPTION_NAME, $stored, false);
                            $stored_key = $rotated_key;
                        }
                    }
                }
            }

            $data['license_key'] = SecretStore::decrypt_string($stored_key);
        }
        return $data;
    }

    public function status(): array
    {
        if ($this->has_built_in_license()) {
            return $this->built_in_status();
        }

        $data = $this->data();
        $now = current_time('timestamp');
        $trial_started = $this->timestamp((string) ($data['trial_started_at'] ?? '')) ?: $now;
        return [
            'state' => 'development_placeholder',
            'label' => __('Licensing: development placeholder / enforcement disabled', 'slotera-booking'),
            'days_left' => null,
            'trial_started_at' => $data['trial_started_at'] ?? '',
            'trial_ends_at' => wp_date('Y-m-d H:i:s', $trial_started),
            'grace_ends_at' => wp_date('Y-m-d H:i:s', $trial_started),
            'license_expires_at' => $data['license_expires_at'] ?? '',
            'marketing_allowed' => true,
            'automation_allowed' => true,
            'advanced_marketing_allowed' => true,
            'unique_coupons_allowed' => true,
            'queue_settings_allowed' => true,
            'marketing_batch_limit' => 50,
        ];
    }

    public function can_use_marketing(): bool
    {
        $status = $this->status();
        return !empty($status['marketing_allowed']);
    }

    public function can_use_automations(): bool
    {
        $status = $this->status();
        return !empty($status['automation_allowed']);
    }

    public function can_use_advanced_marketing(): bool
    {
        $status = $this->status();
        return !empty($status['advanced_marketing_allowed']);
    }

    public function can_use_unique_coupons(): bool
    {
        $status = $this->status();
        return !empty($status['unique_coupons_allowed']);
    }

    public function can_manage_queue_settings(): bool
    {
        $status = $this->status();
        return !empty($status['queue_settings_allowed']);
    }

    public function marketing_batch_limit(): int
    {
        $status = $this->status();
        return max(0, (int) ($status['marketing_batch_limit'] ?? 0));
    }

    public function is_grace_limited(): bool
    {
        $status = $this->status();
        return ($status['state'] ?? '') === 'grace';
    }

    public function activate_license(string $key): bool
    {
        if ($this->has_built_in_license()) {
            $this->mark_built_in_license_checked();
            return true;
        }

        $key = sanitize_text_field($key);
        if ($key === '') { return false; }
        $data = $this->data();
        $data['license_key'] = $key;
        $data['licensed_domain'] = $this->current_domain();
        $data['license_status'] = 'development_placeholder';
        $data['license_activated_at'] = current_time('mysql');
        $data['license_expires_at'] = '';
        $data['license_last_checked_at'] = current_time('mysql');
        $data['license_last_check_result'] = 'development_placeholder_key_saved';
        if (SecretStore::encryption_available()) {
            $data['license_key'] = SecretStore::encrypt_string($key);
        }
        update_option(self::OPTION_NAME, $data, false);
        return true;
    }

    public function deactivate_license(): void
    {
        if ($this->has_built_in_license()) {
            $this->mark_built_in_license_checked();
            return;
        }

        $data = $this->data();
        $data['license_key'] = '';
        $data['license_status'] = 'development_placeholder';
        $data['license_expires_at'] = '';
        $data['license_last_checked_at'] = current_time('mysql');
        $data['license_last_check_result'] = 'development_placeholder_key_cleared';
        update_option(self::OPTION_NAME, $data, false);
    }

    public function prepared_license_fields(): array
    {
        if ($this->has_built_in_license()) {
            return [
                'license_key' => 'PARTYTIME-BUILT-IN',
                'licensed_domain' => $this->current_domain(),
                'license_status' => 'built_in_partytime',
                'license_expires_at' => '',
                'license_last_checked_at' => current_time('mysql'),
                'license_last_check_result' => 'built_in_partytime',
            ];
        }

        $data = $this->data();
        return [
            'license_key' => SecretStore::mask((string) ($data['license_key'] ?? '')),
            'licensed_domain' => (string) ($data['licensed_domain'] ?? $this->current_domain()),
            'license_status' => 'development_placeholder',
            'license_expires_at' => (string) ($data['license_expires_at'] ?? ''),
            'license_last_checked_at' => (string) ($data['license_last_checked_at'] ?? ''),
            'license_last_check_result' => (string) ($data['license_last_check_result'] ?? 'not_checked'),
        ];
    }

    public function check_license_locally(): void
    {
        if ($this->has_built_in_license()) {
            $this->mark_built_in_license_checked();
            return;
        }

        $data = $this->data();
        $data['licensed_domain'] = $data['licensed_domain'] ?: $this->current_domain();
        $data['license_last_checked_at'] = current_time('mysql');
        $data['license_last_check_result'] = 'local_prepared_only';
        update_option(self::OPTION_NAME, $data, false);
    }

    public function trial_message(): string
    {
        if ($this->has_built_in_license()) {
            return __('Slotera built-in Partytime license is active.', 'slotera-booking');
        }

        return __('Licensing is a development placeholder; feature enforcement is disabled.', 'slotera-booking');
    }

    public function has_built_in_license(): bool
    {
        $domain = $this->current_domain();
        if ($domain === '') { return false; }

        foreach (self::BUILT_IN_LICENSE_ROOT_DOMAINS as $root_domain) {
            if ($domain === $root_domain || substr($domain, -strlen('.' . $root_domain)) === '.' . $root_domain) {
                return true;
            }
        }

        return false;
    }

    private function built_in_status(): array
    {
        $now = current_time('timestamp');
        return [
            'state' => 'active',
            'label' => __('Built-in Partytime license', 'slotera-booking'),
            'days_left' => null,
            'trial_started_at' => '',
            'trial_ends_at' => wp_date('Y-m-d H:i:s', $now),
            'grace_ends_at' => wp_date('Y-m-d H:i:s', $now),
            'license_expires_at' => '',
            'marketing_allowed' => true,
            'automation_allowed' => true,
            'advanced_marketing_allowed' => true,
            'unique_coupons_allowed' => true,
            'queue_settings_allowed' => true,
            'marketing_batch_limit' => 50,
        ];
    }

    private function mark_built_in_license_checked(): void
    {
        $data = $this->data();
        $data['license_key'] = '';
        $data['licensed_domain'] = $this->current_domain();
        $data['license_status'] = 'built_in_partytime';
        $data['license_activated_at'] = $data['license_activated_at'] ?? '';
        $data['license_expires_at'] = '';
        $data['license_last_checked_at'] = current_time('mysql');
        $data['license_last_check_result'] = 'built_in_partytime';
        update_option(self::OPTION_NAME, $data, false);
    }

    private function current_domain(): string
    {
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        return is_string($host) && $host !== '' ? strtolower($host) : '';
    }

    private function timestamp(string $mysql): int
    {
        if ($mysql === '') { return 0; }
        $time = strtotime($mysql);
        return $time ? (int) $time : 0;
    }
}
