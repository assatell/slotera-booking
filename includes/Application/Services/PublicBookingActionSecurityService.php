<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Http\ClientIpResolver;
use Slotera\Infrastructure\Http\RateLimiter;

if (!defined('ABSPATH')) { exit; }

final class PublicBookingActionSecurityService
{
    public const ACTION_CANCEL = 'cancel';
    public const ACTION_RESCHEDULE = 'reschedule';

    public function nonce_action(string $action, string $token): string
    {
        $action = $this->normalize_action($action);
        $token = sanitize_text_field($token);
        return 'sltr_' . $action . '_booking_' . $token;
    }

    public function create_nonce(string $action, string $token): string
    {
        return wp_create_nonce($this->nonce_action($action, $token));
    }

    public function nonce_field(string $action, string $token): string
    {
        return wp_nonce_field($this->nonce_action($action, $token), '_wpnonce', true, false);
    }

    public function verify_nonce(string $nonce, string $action, string $token): bool
    {
        $nonce = sanitize_text_field($nonce);
        if ($nonce === '' || $token === '') { return false; }
        return (bool) wp_verify_nonce($nonce, $this->nonce_action($action, $token));
    }


    /**
     * Customer action tokens are intentionally short-lived security credentials.
     * Default: 180 days after booking creation, and never after the booked slot starts.
     * The TTL can be changed with the `slotera_public_booking_token_ttl_days` filter.
     */
    public function is_token_expired(array $booking): bool
    {
        $created_at = (string) ($booking['created_at'] ?? '');
        $ttl_days = (int) apply_filters('slotera_public_booking_token_ttl_days', 180, $booking);
        $ttl_days = max(1, min(3650, $ttl_days));

        try {
            if ($created_at !== '') {
                $created = new \DateTimeImmutable($created_at, wp_timezone());
                if ((new \DateTimeImmutable('now', wp_timezone())) > $created->modify('+' . $ttl_days . ' days')) {
                    return true;
                }
            }

            $is_booking_request = sanitize_key((string) ($booking['pricing_mode'] ?? '')) === 'simple';
            $mode = sanitize_key((string) ($booking['booking_mode'] ?? $booking['pricing_mode'] ?? ''));
            $date = (string) ($booking['booking_date'] ?? '');
            $start = (string) ($booking['start_time'] ?? '');
            $end_date = (string) ($booking['end_date'] ?? '');
            $end_time = (string) ($booking['end_time'] ?? '');

            // Booking Request has no scheduled slot. Its customer links expire only by TTL,
            // not when the technical placeholder date/time is reached.
            if (!$is_booking_request && $date !== '' && $start !== '') {
                $is_scheduled_event = $this->is_scheduled_event_booking($booking);
                // Event cancellation links use the normal token TTL. Tying them to the
                // event clock made valid links fail during testing and prevents businesses
                // from handling same-day cancellation according to their own policy.
                if (!$is_scheduled_event) {
                    $slot_expiry = new \DateTimeImmutable($date . ' ' . $start, wp_timezone());
                    if ((new \DateTimeImmutable('now', wp_timezone())) > $slot_expiry) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            return true;
        }

        return false;
    }


    private function is_scheduled_event_booking(array $booking): bool
    {
        $package_id = absint($booking['package_id'] ?? 0);
        $event_id = absint($booking['resource_id'] ?? 0);
        if ($package_id <= 0 || $event_id <= 0 || !class_exists('Slotera\Infrastructure\Repositories\PackageRepository') || !class_exists('Slotera\Application\Services\DateRangeInventoryService')) {
            return false;
        }
        try {
            $package = (new \Slotera\Infrastructure\Repositories\PackageRepository())->get_by_id($package_id);
            return is_array($package)
                && is_array((new \Slotera\Application\Services\DateRangeInventoryService())->find_scheduled_event($package, $event_id));
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function enforce_rate_limit(string $action, string $token): ?\WP_Error
    {
        $action = $this->normalize_action($action);
        $ip = ClientIpResolver::get_client_ip();
        $ua = $this->user_agent();
        $identity = implode('|', [$action, $ip !== '' ? $ip : 'no-ip', substr(hash('sha256', $token), 0, 16), substr(hash('sha256', $ua), 0, 12)]);

        $window = (int) apply_filters('slotera_public_booking_token_rate_limit_window_seconds', 15 * MINUTE_IN_SECONDS, $action);
        $limit = (int) apply_filters('slotera_public_booking_token_rate_limit_attempts', 8, $action);
        $window = max(60, min(DAY_IN_SECONDS, $window));
        $limit = max(1, min(100, $limit));

        $attempts = RateLimiter::increment('public_booking_token_' . $action, $identity, $window);
        if ($attempts > $limit) {
            return new \WP_Error('sltr_token_rate_limited', __('Too many booking link attempts. Please try again later.', 'slotera-booking'), ['status' => 429]);
        }

        return null;
    }

    public function audit_context(): array
    {
        $ip = ClientIpResolver::get_client_ip();
        $ua = $this->user_agent();
        $salt = wp_salt('auth');

        return [
            'ip_hash' => $ip !== '' ? hash_hmac('sha256', $ip, $salt) : '',
            'user_agent_hash' => $ua !== '' ? hash_hmac('sha256', $ua, $salt) : '',
        ];
    }

    private function user_agent(): string
    {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
        $ua = sanitize_text_field($ua);
        return substr($ua, 0, 500);
    }

    public function cancel_url(string $token): string
    {
        return $this->action_url(self::ACTION_CANCEL, $token);
    }

    public function reschedule_url(string $token): string
    {
        return $this->action_url(self::ACTION_RESCHEDULE, $token);
    }

    public function action_url(string $action, string $token): string
    {
        $token = sanitize_text_field($token);
        if ($token === '') { return ''; }
        $query_action = $this->normalize_action($action) === self::ACTION_RESCHEDULE ? 'reschedule_booking' : 'cancel_booking';
        return add_query_arg(['sltr_action' => $query_action, 'sltr_token' => $token], home_url('/'));
    }

    private function normalize_action(string $action): string
    {
        return $action === self::ACTION_RESCHEDULE ? self::ACTION_RESCHEDULE : self::ACTION_CANCEL;
    }
}
