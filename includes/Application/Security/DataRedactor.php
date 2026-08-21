<?php

declare(strict_types=1);

namespace Slotera\Application\Security;

if (!defined('ABSPATH')) { exit; }

final class DataRedactor
{
    private const REDACTED = '[redacted]';

    /** @var string[] */
    private const SENSITIVE_KEY_PARTS = [
        'password', 'passwd', 'pwd', 'secret', 'client_secret', 'webhook_secret', 'key_secret',
        'token', 'access_token', 'refresh_token', 'id_token', 'bearer', 'authorization', 'auth',
        'signature', 'sig', 'sign', 'hmac', 'api_key', 'apikey', 'private_key', 'public_key',
        'card', 'card_number', 'cvc', 'cvv', 'iban', 'account_number', 'routing_number',
        'email', 'payer_email', 'customer_email', 'phone', 'customer_phone', 'tel',
        'address', 'line1', 'line2', 'city', 'postal_code', 'postcode', 'zip', 'country',
        'name', 'first_name', 'last_name', 'customer_name', 'full_name',
        'raw_body', 'raw_payload', 'payload_raw', 'body_raw', 'request_body',
    ];

    /** @var string[] */
    private const SENSITIVE_EXACT_KEYS = [
        'http_authorization', 'authorization', 'x_signature', 'x-signature',
        'stripe-signature', 'http_stripe_signature',
        'x-razorpay-signature', 'http_x_razorpay_signature',
        'paypal-transmission-sig', 'http_paypal_transmission_sig',
        'x-square-hmacsha256-signature', 'http_x_square_hmacsha256_signature',
        'set-cookie', 'cookie', 'cookies',
    ];

    public static function payload($value)
    {
        return self::walk($value, 0);
    }

    public static function text(string $value): string
    {
        if ($value === '') { return ''; }
        $value = preg_replace('/(Authorization:\s*(?:Bearer|Basic)\s+)[^\s,;]+/i', '$1' . self::REDACTED, $value) ?? $value;
        $value = preg_replace('/((?:access|refresh|id|client|webhook|api)[_-]?token\s*[=:]\s*)[^\s,;]+/i', '$1' . self::REDACTED, $value) ?? $value;
        $value = preg_replace('/((?:password|secret|signature|api[_-]?key)\s*[=:]\s*)[^\s,;]+/i', '$1' . self::REDACTED, $value) ?? $value;
        $value = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', self::REDACTED, $value) ?? $value;
        return $value;
    }

    private static function walk($value, int $depth)
    {
        if ($depth > 8) { return '[max-depth]'; }

        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $key => $item) {
                $key_string = (string) $key;
                if (self::is_sensitive_key($key_string)) {
                    $redacted[$key] = self::REDACTED;
                    continue;
                }
                $redacted[$key] = self::walk($item, $depth + 1);
            }
            return $redacted;
        }

        if (is_object($value)) {
            return self::walk((array) $value, $depth + 1);
        }

        if (is_string($value)) {
            return self::text($value);
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return '[non-scalar]';
    }

    private static function is_sensitive_key(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' ', '.'], '_', $key));
        if (in_array($normalized, self::SENSITIVE_EXACT_KEYS, true)) { return true; }
        foreach (self::SENSITIVE_KEY_PARTS as $part) {
            if (strpos($normalized, $part) !== false) { return true; }
        }
        return false;
    }
}
