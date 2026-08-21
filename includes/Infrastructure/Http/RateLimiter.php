<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Http;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class RateLimiter
{
    /**
     * Atomically increments a fixed-window counter stored in a dedicated table.
     * Returns the incremented attempt count for the current window.
     *
     * Keeping counters out of wp_options prevents option-table growth under
     * abusive traffic while preserving atomic increments under concurrency.
     */
    public static function increment(string $namespace, string $identity, int $window_seconds): int
    {
        global $wpdb;

        $namespace = substr(sanitize_key($namespace), 0, 64);
        if ($namespace === '') {
            $namespace = 'default';
        }

        $identity_hash = md5($identity !== '' ? $identity : 'unknown');
        $window_seconds = max(1, $window_seconds);
        $now = time();
        $bucket = (int) floor($now / $window_seconds);
        $expires_at = $now + $window_seconds + 60;
        $updated_at = current_time('mysql', true);
        $table = Database::rate_limits_table();

        $write_result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (namespace, identity_hash, bucket, attempts, expires_at, updated_at)
             VALUES (%s, %s, %d, 1, %d, %s)
             ON DUPLICATE KEY UPDATE attempts = attempts + 1, expires_at = VALUES(expires_at), updated_at = VALUES(updated_at)",
            $namespace,
            $identity_hash,
            $bucket,
            $expires_at,
            $updated_at
        ));

        if ($write_result === false) {
            return self::fail_closed('increment', (string) ($wpdb->last_error ?? ''));
        }

        $attempts = $wpdb->get_var($wpdb->prepare(
            "SELECT attempts FROM {$table} WHERE namespace = %s AND identity_hash = %s AND bucket = %d LIMIT 1",
            $namespace,
            $identity_hash,
            $bucket
        ));

        if ($attempts === null || !is_numeric($attempts)) {
            return self::fail_closed('read', (string) ($wpdb->last_error ?? ''));
        }

        self::cleanup($namespace);

        return max(1, (int) $attempts);
    }


    /**
     * Fail closed when the database-backed counter cannot be updated or read.
     * Existing callers treat a value above their configured limit as blocked.
     */
    private static function fail_closed(string $operation, string $database_error): int
    {
        static $logged = false;

        if (!$logged && function_exists('error_log')) {
            $message = 'Slotera rate limiter database failure during ' . $operation . '; request blocked.';
            if ($database_error !== '') {
                $message .= ' Error: ' . $database_error;
            }
            error_log($message);
            $logged = true;
        }

        return PHP_INT_MAX;
    }

    private static function cleanup(string $namespace): void
    {
        global $wpdb;

        $last_cleanup_option = '_sltr_rl_last_cleanup_' . sanitize_key($namespace);
        $last_cleanup = (int) get_option($last_cleanup_option, 0);
        $now = time();
        if (($now - $last_cleanup) < 300) {
            return;
        }

        update_option($last_cleanup_option, $now, false);

        $table = Database::rate_limits_table();
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE expires_at < %d LIMIT 1000",
            $now
        ));

        self::cleanup_legacy_options($namespace, $now);
    }

    private static function cleanup_legacy_options(string $namespace, int $now): void
    {
        global $wpdb;

        $options_table = $wpdb->options;
        $timeout_like = $wpdb->esc_like('_sltr_rl_timeout_' . sanitize_key($namespace) . '_') . '%';
        $expired_timeouts = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$options_table} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d LIMIT 500",
            $timeout_like,
            $now
        ));

        foreach ((array) $expired_timeouts as $timeout_name) {
            $counter_name = str_replace('_sltr_rl_timeout_', '_sltr_rl_', (string) $timeout_name);
            delete_option((string) $timeout_name);
            delete_option($counter_name);
        }
    }
}
