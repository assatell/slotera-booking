<?php
declare(strict_types=1);
namespace Slotera\Core;
if (!defined('ABSPATH')) { exit; }

final class Database {
    /**
     * SQL identifier safety policy:
     * Values must always be passed via $wpdb->prepare() / insert() / update() / delete().
     * Table identifiers cannot be placeholders in WordPress, so Database::*_table()
     * is the single approved source for interpolated Slotera table names. The private
     * table() factory validates every generated name with SafeSqlIdentifier before it
     * can be used in raw SQL. Do not concatenate request/user input into SQL identifiers.
     */
    /**
     * Returns the physical Slotera table name.
     *
     * Default mode keeps the historical WordPress-prefixed tables, e.g. wp_sltr_bookings.
     * Shared-table mode is enabled by defining SLTR_SHARED_TABLE_PREFIX in wp-config.php.
     * This is intended for two WordPress installs in the SAME MySQL database with different
     * WordPress $table_prefix values, while Slotera uses one common set of tables.
     *
     * Example:
     *   define('SLTR_SHARED_TABLE_PREFIX', 'sltr_');
     *
     * Important: keep both WordPress installs on the same DB_NAME. Cross-database table names
     * are intentionally not used here because WordPress wpdb helper methods quote table names
     * in a way that is unsafe for db.table identifiers.
     */
    private static function table(string $suffix): string {
        global $wpdb;

        $shared_prefix = defined('SLTR_SHARED_TABLE_PREFIX') ? (string) constant('SLTR_SHARED_TABLE_PREFIX') : '';
        if ($shared_prefix !== '') {
            $shared_prefix = preg_replace('/[^A-Za-z0-9_]/', '', $shared_prefix) ?: 'sltr_';
            if (substr($shared_prefix, -1) !== '_') {
                $shared_prefix .= '_';
            }
            return SafeSqlIdentifier::table($shared_prefix . $suffix);
        }

        return SafeSqlIdentifier::table($wpdb->prefix . 'sltr_' . $suffix);
    }

    public static function packages_table(): string { return self::table('packages'); }
    public static function events_table(): string { return self::table('events'); }
    public static function categories_table(): string { return self::table('categories'); }
    public static function locations_table(): string { return self::table('locations'); }
    public static function package_locations_table(): string { return self::table('package_locations'); }
    public static function working_hours_table(): string { return self::table('working_hours'); }
    public static function bookings_table(): string { return self::table('bookings'); }
    public static function activity_log_table(): string { return self::table('activity_log'); }
    public static function booking_history_table(): string { return self::table('booking_history'); }
    public static function webhook_events_table(): string { return self::table('webhook_events'); }
    public static function email_queue_table(): string { return self::table('email_queue'); }
    public static function outgoing_webhook_endpoints_table(): string { return self::table('outgoing_webhook_endpoints'); }
    public static function outgoing_webhook_deliveries_table(): string { return self::table('outgoing_webhook_deliveries'); }
    public static function coupons_table(): string { return self::table('coupons'); }
    public static function marketing_campaigns_table(): string { return self::table('marketing_campaigns'); }
    public static function marketing_logs_table(): string { return self::table('marketing_logs'); }
    public static function rate_limits_table(): string { return self::table('rate_limits'); }
    public static function rest_hmac_nonces_table(): string { return self::table('rest_hmac_nonces'); }
    public static function visitor_events_table(): string { return self::table('visitor_events'); }
    public static function payment_transactions_table(): string { return self::table('payment_transactions'); }
    public static function payment_invoices_table(): string { return self::table('payment_invoices'); }

    public static function uses_shared_tables(): bool {
        return defined('SLTR_SHARED_TABLE_PREFIX') && (string) constant('SLTR_SHARED_TABLE_PREFIX') !== '';
    }

    public static function create_tables(): void
    {
        DatabaseSchemaInstaller::create_tables();
    }
}
