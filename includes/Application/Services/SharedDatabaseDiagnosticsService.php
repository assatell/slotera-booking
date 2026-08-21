<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class SharedDatabaseDiagnosticsService
{
    /** @return array<string,mixed> */
    public function report(): array
    {
        global $wpdb;
        $tables = [
            'packages' => Database::packages_table(),
            'events' => Database::events_table(),
            'bookings' => Database::bookings_table(),
            'coupons' => Database::coupons_table(),
            'marketing_campaigns' => Database::marketing_campaigns_table(),
            'marketing_logs' => Database::marketing_logs_table(),
            'rate_limits' => Database::rate_limits_table(),
            'email_queue' => Database::email_queue_table(),
            'activity_log' => Database::activity_log_table(),
        ];
        $checks = [];
        foreach ($tables as $key => $table) {
            $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
            $count = null;
            if ($exists) {
                $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            }
            $checks[$key] = ['table' => $table, 'exists' => $exists, 'count' => $count];
        }
        return [
            'active' => Database::uses_shared_tables(),
            'shared_prefix' => defined('SLTR_SHARED_TABLE_PREFIX') ? (string) constant('SLTR_SHARED_TABLE_PREFIX') : '',
            'wp_prefix' => (string) $wpdb->prefix,
            'database_name' => (string) DB_NAME,
            'config_line' => "define('SLTR_SHARED_TABLE_PREFIX', 'sltr_');",
            'config_anchor' => "/* That's all, stop editing! Happy publishing. */",
            'wp_config_path' => defined('ABSPATH') ? dirname((string) ABSPATH) . '/wp-config.php' : '',
            'wp_config_writable' => defined('ABSPATH') ? is_writable(dirname((string) ABSPATH) . '/wp-config.php') : false,
            'tables' => $checks,
        ];
    }
}
