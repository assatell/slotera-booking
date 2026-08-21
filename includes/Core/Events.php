<?php

declare(strict_types=1);

namespace Slotera\Core;

if (!defined('ABSPATH')) { exit; }

final class Events
{
    public const BOOKING_CREATED = 'sltr_booking_created';
    public const BOOKING_CONFIRMED = 'sltr_booking_confirmed';
    public const BOOKING_CANCELLED = 'sltr_booking_cancelled';
    public const BOOKING_COMPLETED = 'sltr_booking_completed';
    public const BOOKING_RESCHEDULED = 'sltr_booking_rescheduled';
    public const BOOKING_PACKAGE_CHANGED = 'sltr_booking_package_changed';

    public const PAYMENT_PENDING = 'sltr_payment_pending';
    public const PAYMENT_PAID = 'sltr_payment_paid';
    public const PAYMENT_COMPLETED = 'sltr_payment_completed';
    public const PAYMENT_FAILED = 'sltr_payment_failed';
    public const PAYMENT_REFUNDED = 'sltr_payment_refunded';

    public const INVOICE_CREATED = 'sltr_invoice_created';
    public const CUSTOMER_LOGIN = 'sltr_customer_login';

    public const EMAIL_SENT = 'sltr_email_sent';
    public const EMAIL_FAILED = 'sltr_email_failed';

    /**
     * Dispatch a Slotera internal event and its public WordPress integration hooks.
     *
     * Backward compatibility:
     * - Existing internal listeners still receive do_action('sltr_*', $payload).
     *
     * Public integration API:
     * - Generic: do_action('slotera_event', $event, $payload)
     * - Per event: do_action('slotera_booking_created', $booking_id, $booking, $package, $payload)
     * - Payload filter: apply_filters('slotera_event_payload', $payload, $event)
     */
    public static function dispatch(string $event, array $payload = []): void
    {
        $event = sanitize_key($event);
        if ($event === '') {
            return;
        }

        $payload = self::normalize_payload($event, $payload);
        $payload = apply_filters('slotera_event_payload', $payload, $event);
        if (!is_array($payload)) {
            $payload = self::normalize_payload($event, []);
        }

        // Internal Slotera listeners. Keep this first so existing behavior is unchanged.
        do_action($event, $payload);

        // Public integration hooks.
        do_action('slotera_event', $event, $payload);
        do_action(self::public_hook_name($event), ...self::public_hook_args($payload));
    }

    private static function normalize_payload(string $event, array $payload): array
    {
        $payload['event'] = $event;
        $payload['event_name'] = self::event_slug($event);
        $payload['occurred_at'] = $payload['occurred_at'] ?? current_time('mysql');
        $payload['site_url'] = $payload['site_url'] ?? home_url('/');

        if (isset($payload['booking_id'])) {
            $payload['booking_id'] = absint($payload['booking_id']);
        }
        if (isset($payload['package_id'])) {
            $payload['package_id'] = absint($payload['package_id']);
        }

        return $payload;
    }

    private static function public_hook_name(string $event): string
    {
        $slug = self::event_slug($event);
        return $slug !== '' ? 'slotera_' . $slug : 'slotera_event_unknown';
    }

    private static function event_slug(string $event): string
    {
        if (strpos($event, 'sltr_') === 0) {
            return substr($event, 5);
        }
        if (strpos($event, 'slotera_') === 0) {
            return substr($event, 8);
        }
        return $event;
    }

    private static function public_hook_args(array $payload): array
    {
        $booking_id = absint($payload['booking_id'] ?? 0);
        $booking = isset($payload['booking']) && is_array($payload['booking']) ? $payload['booking'] : [];
        $package = isset($payload['package']) && is_array($payload['package']) ? $payload['package'] : [];

        return [$booking_id, $booking, $package, $payload];
    }
}
