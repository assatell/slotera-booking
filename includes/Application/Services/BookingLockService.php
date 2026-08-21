<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Coordinates booking writes for a single bookable slot.
 *
 * Uses an atomic WordPress option lock as the hard gate and then also attempts
 * a MySQL named lock when available. The option lock is intentionally the
 * primary guard because GET_LOCK() can be unavailable or unreliable on some
 * managed, proxied or clustered MySQL hosts.
 */
final class BookingLockService
{
    private const LOCK_PREFIX = 'sltr_booking_';
    private const OPTION_PREFIX = 'sltr_lock_';
    // The lease must cover the slowest supported date-range booking (up to 365
    // inventory days), provider initialization and a congested database. A
    // thirty-second lease could expire while the original owner was alive.
    private const STALE_SECONDS = 900;

    private int $timeout_seconds;

    /** @var array<string,string> */
    private array $owners = [];

    /** @var array<string,bool> */
    private array $mysql_locks = [];

    public function __construct(int $timeout_seconds = 10)
    {
        $this->timeout_seconds = max(1, $timeout_seconds);
    }

    /**
     * @return true|WP_Error
     */
    public function acquire(int $package_id, string $date, string $start_time, string $end_time, int $resource_id = 0, int $staff_id = 0)
    {
        $lock_name = $this->build_lock_name($package_id, $date, $start_time, $end_time, $resource_id, $staff_id);
        $option_lock = $this->acquire_option_lock($lock_name);

        if (is_wp_error($option_lock)) {
            return $option_lock;
        }

        // Best-effort secondary lock. Failure is not fatal because the option
        // lock above is the authoritative cross-request guard.
        if ($this->acquire_mysql_lock($lock_name)) {
            $this->mysql_locks[$lock_name] = true;
        }

        return true;
    }

    public function release(int $package_id, string $date, string $start_time, string $end_time, int $resource_id = 0, int $staff_id = 0): bool
    {
        $lock_name = $this->build_lock_name($package_id, $date, $start_time, $end_time, $resource_id, $staff_id);
        $released = true;

        if (!empty($this->mysql_locks[$lock_name])) {
            $released = $this->release_mysql_lock($lock_name) && $released;
            unset($this->mysql_locks[$lock_name]);
        }

        $released = $this->release_option_lock($lock_name) && $released;

        return $released;
    }


    /**
     * Serializes simple-mode limited-capacity bookings for a package.
     *
     * Simple bookings do not have a real date/time slot, but limited capacity is
     * counted across all active bookings for the package. Use a stable package-
     * level lock instead of a synthetic current-day slot lock so concurrent
     * requests cannot oversell the package capacity.
     *
     * @return true|WP_Error
     */
    public function acquire_simple_capacity(int $package_id)
    {
        return $this->acquire(max(0, $package_id), 'simple-capacity', '00:00:00', '00:00:00', 0, 0);
    }

    public function release_simple_capacity(int $package_id): bool
    {
        return $this->release(max(0, $package_id), 'simple-capacity', '00:00:00', '00:00:00', 0, 0);
    }


    /**
     * Serializes date-range inventory writes for every night in the requested
     * range. Locking only the check-in date lets overlapping ranges with
     * different starts pass concurrently, so each inventory day gets its own
     * stable lock key.
     *
     * @return true|WP_Error
     */
    public function acquire_date_range_inventory(int $package_id, int $resource_id, string $start_date, string $end_date)
    {
        $locks = $this->date_range_lock_slots($package_id, $resource_id, $start_date, $end_date);
        $acquired = [];

        foreach ($locks as $slot) {
            $result = $this->acquire($package_id, $slot, '00:00:00', '23:59:59', $resource_id, 0);
            if (is_wp_error($result)) {
                foreach (array_reverse($acquired) as $acquired_slot) {
                    $this->release($package_id, $acquired_slot, '00:00:00', '23:59:59', $resource_id, 0);
                }
                return $result;
            }
            $acquired[] = $slot;
        }

        return true;
    }

    public function release_date_range_inventory(int $package_id, int $resource_id, string $start_date, string $end_date): bool
    {
        $released = true;
        foreach (array_reverse($this->date_range_lock_slots($package_id, $resource_id, $start_date, $end_date)) as $slot) {
            $released = $this->release($package_id, $slot, '00:00:00', '23:59:59', $resource_id, 0) && $released;
        }
        return $released;
    }

    /**
     * @return string[]
     */
    private function date_range_lock_slots(int $package_id, int $resource_id, string $start_date, string $end_date): array
    {
        try {
            $cursor = new \DateTimeImmutable($start_date, wp_timezone());
            $end = new \DateTimeImmutable($end_date, wp_timezone());
        } catch (\Throwable $e) {
            return ['date-range-invalid:' . sha1($start_date . '|' . $end_date)];
        }

        if ($end <= $cursor) {
            return ['date-range-invalid:' . sha1($start_date . '|' . $end_date)];
        }

        $slots = [];
        while ($cursor < $end) {
            $slots[] = 'date-range:' . $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }

        return $slots ?: ['date-range-empty:' . sha1($start_date . '|' . $end_date)];
    }

    public function build_lock_name(int $package_id, string $date, string $start_time, string $end_time, int $resource_id = 0, int $staff_id = 0): string
    {
        $date = sanitize_text_field($date);
        $start_time = sanitize_text_field($start_time);
        $end_time = sanitize_text_field($end_time);

        /**
         * Fixed and flexible bookings must serialize by bookable inventory day,
         * not by the exact requested interval. Exact interval locks allow two
         * concurrent requests such as 10:00-11:00 and 10:30-11:30 to acquire
         * different lock names, both pass the availability re-check, and then
         * create overlapping active bookings.
         *
         * A package/date/resource/staff lock keeps the critical section broad
         * enough for every overlapping interval on that inventory day while
         * still allowing independent packages, dates, resources and staff to be
         * booked in parallel. Availability is still re-checked after the lock is
         * acquired, so max_bookings_per_slot and flexible-duration rules remain
         * enforced by the domain service rather than by the lock key itself.
         */
        $scope = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? 'inventory-day' : 'system-scope';

        $raw = implode('|', [
            'scope:' . $scope,
            'package:' . max(0, $package_id),
            'date:' . $date,
            'resource:' . max(0, $resource_id),
            'staff:' . max(0, $staff_id),
        ]);

        return self::LOCK_PREFIX . sha1($raw);
    }

    /**
     * @return true|WP_Error
     */
    private function acquire_option_lock(string $lock_name)
    {
        $option_name = $this->option_name($lock_name);
        $owner = $this->make_owner();
        $deadline = microtime(true) + $this->timeout_seconds;

        do {
            $now = time();
            $payload = [
                'owner' => $owner,
                'expires_at' => $now + self::STALE_SECONDS,
                'created_at' => $now,
            ];

            if (add_option($option_name, wp_json_encode($payload), '', 'no')) {
                $this->owners[$lock_name] = $owner;
                return true;
            }

            $existing_raw = get_option($option_name, '');
            $existing = $this->decode_lock_payload($existing_raw);
            if ($existing && !empty($existing['expires_at']) && (int) $existing['expires_at'] < $now) {
                // Delete only the exact stale value we inspected. Another
                // request may have replaced it between get_option() and here.
                if ($this->delete_option_if_value_matches($option_name, $existing_raw)) {
                    continue;
                }
            }

            usleep(200000);
        } while (microtime(true) < $deadline);

        return new WP_Error(
            'sltr_booking_lock_failed',
            __('This time slot is being booked right now. Please try again in a moment.', 'slotera-booking')
        );
    }

    private function release_option_lock(string $lock_name): bool
    {
        $option_name = $this->option_name($lock_name);
        $owner = $this->owners[$lock_name] ?? '';

        if ($owner === '') {
            return true;
        }

        $existing_raw = get_option($option_name, '');
        $existing = $this->decode_lock_payload($existing_raw);
        if (!$existing || ($existing['owner'] ?? '') !== $owner) {
            unset($this->owners[$lock_name]);
            return false;
        }

        unset($this->owners[$lock_name]);
        return $this->delete_option_if_value_matches($option_name, $existing_raw);
    }

    /**
     * Compare-and-delete for a non-autoloaded WordPress option lock.
     */
    private function delete_option_if_value_matches(string $option_name, $expected_value): bool
    {
        global $wpdb;
        if (!is_string($expected_value) || $expected_value === '') { return false; }

        $deleted = $wpdb->delete(
            $wpdb->options,
            ['option_name' => $option_name, 'option_value' => $expected_value],
            ['%s', '%s']
        );

        // Direct SQL bypasses delete_option(), so invalidate this request's
        // option cache even when a concurrent replacement made the CAS lose.
        wp_cache_delete($option_name, 'options');
        return $deleted === 1;
    }

    private function acquire_mysql_lock(string $lock_name): bool
    {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare('SELECT GET_LOCK(%s, %d)', $lock_name, $this->timeout_seconds)
        );

        return (string) $result === '1';
    }

    private function release_mysql_lock(string $lock_name): bool
    {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));

        return (string) $result === '1';
    }

    private function option_name(string $lock_name): string
    {
        return self::OPTION_PREFIX . sha1($lock_name);
    }

    private function make_owner(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            return wp_generate_password(32, false, false);
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private function decode_lock_payload($payload): ?array
    {
        if (!is_string($payload) || $payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : null;
    }
}
