<?php

declare(strict_types=1);

namespace Slotera\Application\Security;

if (!defined('ABSPATH')) { exit; }

final class SecretStore
{
    private const PREFIX = 'sltr_secret:v3:';
    private const V2_PREFIX = 'sltr_secret:v2:';
    private const LEGACY_PREFIX = 'sltr_secret:v1:';

    public static function sensitive_keys(): array
    {
        return [
            'smtp_password',
            'payment_stripe_secret_key',
            'payment_stripe_test_secret_key',
            'payment_stripe_live_secret_key',
            'payment_stripe_webhook_secret',
            'payment_paypal_client_secret',
            'payment_paypal_sandbox_client_secret',
            'payment_paypal_live_client_secret',
            'payment_mollie_api_key',
            'payment_mollie_test_api_key',
            'payment_mollie_live_api_key',
            'payment_razorpay_key_secret',
            'payment_razorpay_webhook_secret',
            'payment_alipay_private_key',
            'payment_adyen_api_key',
            'payment_adyen_webhook_hmac_key',
            'payment_square_access_token',
            'payment_square_webhook_signature_key',
            'security_public_rest_booking_api_key',
            'security_public_rest_booking_hmac_secret',
            'security_turnstile_secret_key',
            'security_recaptcha_secret_key',
            'social_login_google_client_secret',
            'social_login_facebook_client_secret',
            'social_login_apple_private_key',
            'license_key',
        ];
    }

    public static function is_sensitive_key(string $key): bool
    {
        return in_array($key, self::sensitive_keys(), true);
    }

    public static function is_encrypted($value): bool
    {
        return is_string($value)
            && (
                strpos($value, self::PREFIX) === 0
                || strpos($value, self::V2_PREFIX) === 0
                || strpos($value, self::LEGACY_PREFIX) === 0
            );
    }
    public static function is_current_encrypted($value): bool
    {
        return is_string($value) && strpos($value, self::PREFIX) === 0;
    }

    public static function encryption_available(): bool
    {
        return function_exists('sodium_crypto_secretbox')
            && function_exists('sodium_crypto_secretbox_open')
            && function_exists('random_bytes')
            && function_exists('wp_salt')
            && (string) wp_salt('auth') !== '';
    }

    public static function encrypt_string(string $plain): string
    {
        if ($plain === '' || self::is_encrypted($plain)) {
            return $plain;
        }

        if (!self::encryption_available()) {
            return '';
        }

        try {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        } catch (\Throwable $e) {
            return '';
        }

        $key = self::key();
        if ($key === '') {
            return '';
        }

        $ciphertext = sodium_crypto_secretbox($plain, $nonce, $key);
        if (!is_string($ciphertext) || $ciphertext === '') {
            return '';
        }

        return self::PREFIX . base64_encode($nonce . $ciphertext);
    }

    public static function decrypt_string(string $stored): string
    {
        if (!self::is_encrypted($stored)) {
            return $stored;
        }

        if (strpos($stored, self::LEGACY_PREFIX) === 0) {
            return self::decrypt_legacy_string($stored);
        }

        if (strpos($stored, self::V2_PREFIX) === 0) {
            return self::decrypt_v2_string($stored);
        }

        if (!self::encryption_available()) {
            return '';
        }

        return self::decrypt_secretbox_string($stored, self::PREFIX, self::key());
    }

    public static function decrypt_settings(array $settings): array
    {
        foreach (self::sensitive_keys() as $key) {
            if (array_key_exists($key, $settings) && is_string($settings[$key])) {
                $settings[$key] = self::decrypt_string($settings[$key]);
            }
        }
        return $settings;
    }

    public static function encrypt_settings(array $settings): array
    {
        foreach (self::sensitive_keys() as $key) {
            if (array_key_exists($key, $settings) && is_string($settings[$key]) && $settings[$key] !== '') {
                $settings[$key] = self::encrypt_string($settings[$key]);
            }
        }
        return $settings;
    }

    public static function mask($value): string
    {
        $value = (string) $value;
        if ($value === '') { return ''; }
        return '••••••••';
    }

    private static function decrypt_v2_string(string $stored): string
    {
        if (!function_exists('sodium_crypto_secretbox_open')) {
            return '';
        }

        return self::decrypt_secretbox_string($stored, self::V2_PREFIX, self::legacy_key());
    }

    private static function decrypt_secretbox_string(string $stored, string $prefix, string $key): string
    {
        if ($key === '') {
            return '';
        }

        $payload = base64_decode(substr($stored, strlen($prefix)), true);
        if (!is_string($payload) || strlen($payload) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return '';
        }

        $nonce = substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

        return is_string($plain) ? $plain : '';
    }

    private static function decrypt_legacy_string(string $stored): string
    {
        if (!function_exists('openssl_decrypt')) {
            return '';
        }

        $payload = base64_decode(substr($stored, strlen(self::LEGACY_PREFIX)), true);
        if (!is_string($payload) || strlen($payload) <= 48) {
            return '';
        }

        $iv = substr($payload, 0, 16);
        $mac = substr($payload, 16, 32);
        $ciphertext = substr($payload, 48);
        $key = self::legacy_key();

        $expected = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        if (!hash_equals($expected, $mac)) {
            return '';
        }

        $plain = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return is_string($plain) ? $plain : '';
    }

    private static function key(): string
    {
        if (!function_exists('wp_salt')) {
            return '';
        }

        $material = (string) wp_salt('auth');
        if ($material === '') {
            return '';
        }

        return hash('sha256', 'slotera-secret-store-v3|' . $material, true);
    }

    /**
     * Historical v1/v2 key derivation.
     *
     * The fixed fallback is retained only so installations that previously
     * encrypted data with it can still decrypt and rotate those values.
     * New encryption never uses this key.
     */
    private static function legacy_key(): string
    {
        $material = '';

        foreach ([
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT',
        ] as $constant) {
            if (defined($constant)) {
                $material .= (string) constant($constant);
            }
        }

        if ($material === '') {
            $material = defined('DB_PASSWORD')
                ? (string) DB_PASSWORD
                : 'slotera-secret-store-fallback';
        }

        return hash('sha256', $material, true);
    }
}