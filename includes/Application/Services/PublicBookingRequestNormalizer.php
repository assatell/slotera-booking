<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/**
 * Canonical normalization for public booking payloads.
 *
 * AJAX and REST must feed the same domain keys into BookingService so pricing,
 * availability, coupons, extras and payment policy do not depend on transport.
 */
final class PublicBookingRequestNormalizer
{
    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public static function normalize(array $payload, string $source): array
    {
        return [
            'package_id' => absint(self::value($payload, ['package_id'])),
            'customer_name' => sanitize_text_field(self::string_value($payload, ['name', 'customer_name'])),
            'customer_email' => sanitize_email(self::string_value($payload, ['email', 'customer_email'])),
            'customer_phone' => sanitize_text_field(self::string_value($payload, ['phone', 'customer_phone'])),
            'city' => sanitize_text_field(self::string_value($payload, ['city'])),
            'state' => sanitize_text_field(self::string_value($payload, ['state'])),
            'address' => sanitize_text_field(self::string_value($payload, ['address'])),
            'company' => sanitize_text_field(self::string_value($payload, ['company'])),
            'notes' => sanitize_text_field(self::string_value($payload, ['notes'])),
            'booking_date' => sanitize_text_field(self::string_value($payload, ['date', 'booking_date'])),
            'end_date' => sanitize_text_field(self::string_value($payload, ['end_date'])),
            'start_time' => sanitize_text_field(self::string_value($payload, ['start', 'start_time'])),
            'end_time' => sanitize_text_field(self::string_value($payload, ['end', 'end_time'])),
            'resource_id' => absint(self::value($payload, ['resource_id'])),
            'staff_id' => absint(self::value($payload, ['staff_id'])),
            'source' => sanitize_key($source),
            'company_website' => sanitize_text_field(self::string_value($payload, ['company_website'])),
            'form_started_at' => absint(self::value($payload, ['form_started_at'])),
            'cf_turnstile_response' => sanitize_text_field(self::string_value($payload, ['cf_turnstile_response'])),
            'g_recaptcha_response' => sanitize_text_field(self::string_value($payload, ['g_recaptcha_response'])),
            'payment_method' => sanitize_key(self::string_value($payload, ['payment_method'])),
            'payment_mode' => sanitize_key(self::string_value($payload, ['payment_mode'], 'none')) ?: 'none',
            'payment_choice' => sanitize_key(self::string_value($payload, ['payment_choice'])),
            'extra_ids' => self::normalize_extra_ids(self::value($payload, ['extra_ids'], [])),
            'coupon_code' => sanitize_text_field(self::string_value($payload, ['coupon_code'])),
            'marketing_consent' => absint(self::value($payload, ['marketing_consent'])) === 1 ? 1 : 0,
        ];
    }

    /** @param array<string,mixed> $payload @param list<string> $keys */
    private static function value(array $payload, array $keys, $default = '')
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) { continue; }
            $value = $payload[$key];
            if ($value === null || $value === '') { continue; }
            return $value;
        }
        return $default;
    }

    /** @param array<string,mixed> $payload @param list<string> $keys */
    private static function string_value(array $payload, array $keys, string $default = ''): string
    {
        $value = self::value($payload, $keys, $default);
        return is_scalar($value) ? (string) $value : $default;
    }

    /** @return list<int> */
    private static function normalize_extra_ids($value): array
    {
        if (is_string($value) || is_numeric($value)) {
            $value = preg_split('/[,\s]+/', trim((string) $value)) ?: [];
        }
        if (!is_array($value)) { return []; }

        $ids = [];
        foreach ($value as $item) {
            if (is_array($item) || is_object($item)) { continue; }
            $id = absint($item);
            if ($id > 0) { $ids[$id] = $id; }
        }
        return array_values($ids);
    }
}
