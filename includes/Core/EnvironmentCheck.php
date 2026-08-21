<?php

declare(strict_types=1);

namespace Slotera\Core;

if (!defined('ABSPATH')) { exit; }

final class EnvironmentCheck
{
    /** @return string[] */
    public static function activation_errors(): array
    {
        $errors = self::blocking_errors();
        $errors = array_merge($errors, self::database_errors());
        $errors = array_merge($errors, self::integrity_errors());

        return array_values(array_unique(array_filter($errors)));
    }

    /** @return string[] */
    public static function runtime_warnings(): array
    {
        $warnings = [];
        if (!extension_loaded('mbstring')) {
            $warnings[] = 'PHP extension mbstring is missing. Slotera will use fallbacks, but multilingual search and translations may be less accurate.';
        }
        if (!extension_loaded('curl')) {
            $warnings[] = 'PHP extension cURL is missing. WordPress HTTP fallback may work, but payment providers and remote API calls can be unreliable.';
        }
        if (!extension_loaded('openssl')) {
            $warnings[] = 'PHP extension OpenSSL is missing. Payment signatures, Apple/Google/Alipay integrations and social login signing may not work.';
        }
        if (!function_exists('sodium_crypto_secretbox')) {
            $warnings[] = 'PHP extension Sodium is missing. Sensitive Slotera settings cannot be encrypted or saved until Sodium is enabled.';
        }
        if (!extension_loaded('intl')) {
            $warnings[] = 'PHP extension intl is missing. Locale-aware formatting may be limited on this server.';
        }

        $memory_limit = self::memory_limit_bytes();
        if ($memory_limit > 0 && $memory_limit < 64 * 1024 * 1024) {
            $warnings[] = 'PHP memory_limit is below 64M. Large exports, migrations or booking calendars may fail on busy sites.';
        }

        if (defined('WP_CONTENT_DIR') && !is_writable(WP_CONTENT_DIR)) {
            $warnings[] = 'wp-content is not writable. WordPress may be unable to write debug logs, cache files or plugin-generated files.';
        }

        return $warnings;
    }

    /** @return string[] */
    public static function blocking_errors(): array
    {
        $minimum_php = defined('SLTR_MINIMUM_PHP_VERSION') ? SLTR_MINIMUM_PHP_VERSION : '8.0';
        $minimum_wp = defined('SLTR_MINIMUM_WP_VERSION') ? SLTR_MINIMUM_WP_VERSION : '6.0';
        $current_wp = function_exists('get_bloginfo') ? (string) get_bloginfo('version') : '0.0';
        $errors = [];

        if (version_compare(PHP_VERSION, $minimum_php, '<')) {
            $errors[] = sprintf('Slotera Booking requires PHP %s or newer. Current PHP version is %s.', $minimum_php, PHP_VERSION);
        }
        if (version_compare($current_wp, $minimum_wp, '<')) {
            $errors[] = sprintf('Slotera Booking requires WordPress %s or newer. Current WordPress version is %s.', $minimum_wp, $current_wp);
        }
        if (!extension_loaded('json') || !function_exists('json_decode')) {
            $errors[] = 'Required PHP extension json is missing.';
        }
        if (!function_exists('mysqli_connect') && !class_exists('mysqli')) {
            $errors[] = 'Required PHP MySQLi extension is missing. WordPress and Slotera need MySQL database access.';
        }

        return $errors;
    }

    /** @return string[] */
    public static function database_errors(): array
    {
        global $wpdb;
        $errors = [];
        if (!isset($wpdb) || !is_object($wpdb)) {
            return ['WordPress database object is not available.'];
        }

        $probe = $wpdb->get_var('SELECT 1');
        if ((string) $probe !== '1') {
            $errors[] = 'Database connection check failed. Please verify database credentials and permissions.';
        }

        if (empty($wpdb->prefix) || !preg_match('/^[A-Za-z0-9_]+$/', (string) $wpdb->prefix)) {
            $errors[] = 'WordPress database table prefix looks invalid for Slotera table creation.';
        }

        return $errors;
    }

    /** @return string[] */
    public static function integrity_errors(): array
    {
        $errors = [];
        $expected = defined('SLTR_VERSION') ? SLTR_VERSION : '';
        $build = defined('SLTR_BUILD_VERSION') ? SLTR_BUILD_VERSION : '';
        $build_id = defined('SLTR_BUILD_ID') ? SLTR_BUILD_ID : '';
        if ($build === '' || $build_id === '' || $expected === '' || $build !== $expected || $build_id !== $expected) {
            $errors[] = 'Slotera plugin files look mixed or incomplete. Delete the old plugin folder completely and install the ZIP again.';
        }

        $required_files = [
            'includes/autoload.php',
            'includes/helpers.php',
            'includes/Core/Activator.php',
            'includes/Core/Migrator.php',
            'includes/Core/Migrations/LegacyMigrations.php',
            'includes/Core/Migrations/MigrationRegistry.php',
            'includes/Core/DatabaseSchemaInstaller.php',
        ];
        foreach ($required_files as $relative) {
            if (!defined('SLTR_PLUGIN_DIR') || !is_readable(SLTR_PLUGIN_DIR . $relative)) {
                $errors[] = 'Required Slotera file is missing or unreadable: ' . $relative;
            }
        }

        if (class_exists('Slotera\\Core\\Migrations\\LegacyMigrations')) {
            $legacy = 'Slotera\\Core\\Migrations\\LegacyMigrations';
            foreach (['migrate_to_1046', 'queue_active_slot_hash_backfill', 'is_active_slot_hash_backfill_complete'] as $method) {
                if (!method_exists($legacy, $method)) {
                    $errors[] = 'Slotera migration files are out of sync. Missing method: ' . $method . '. Reinstall the ZIP into a clean plugin folder.';
                }
            }
        }

        return $errors;
    }

    public static function admin_notice(): void
    {
        if (!current_user_can('activate_plugins')) { return; }
        $errors = self::blocking_errors();
        $warnings = self::runtime_warnings();
        $activation_error = (string) get_option('sltr_last_activation_error', '');
        if ($activation_error !== '') { $errors[] = $activation_error; }
        if ($errors === [] && $warnings === []) { return; }

        echo '<div class="notice notice-' . ($errors === [] ? 'warning' : 'error') . '"><p><strong>' . esc_html__('Slotera Booking environment check', 'slotera-booking') . '</strong></p><ul>';
        foreach (array_merge($errors, $warnings) as $message) {
            echo '<li>' . esc_html($message) . '</li>';
        }
        echo '</ul></div>';
    }

    public static function deactivate_and_die(array $errors): void
    {
        if (function_exists('deactivate_plugins') && defined('SLTR_PLUGIN_BASENAME')) {
            deactivate_plugins(SLTR_PLUGIN_BASENAME);
        }
        update_option('sltr_last_activation_error', implode(' ', $errors), false);
        if (function_exists('wp_die')) {
            wp_die(esc_html(implode(' ', $errors)), esc_html__('Slotera Booking activation failed', 'slotera-booking'), ['back_link' => true]);
        }
        throw new \RuntimeException(implode(' ', $errors));
    }

    private static function memory_limit_bytes(): int
    {
        $value = trim((string) ini_get('memory_limit'));
        if ($value === '' || $value === '-1') { return -1; }
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;
        if ($unit === 'g') { $number *= 1024 * 1024 * 1024; }
        elseif ($unit === 'm') { $number *= 1024 * 1024; }
        elseif ($unit === 'k') { $number *= 1024; }
        return (int) $number;
    }
}
