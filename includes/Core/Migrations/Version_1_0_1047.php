<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_1047 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;

        $table = Database::bookings_table();
        $wpdb->query(
            "UPDATE {$table}
             SET end_date = NULL
             WHERE end_date IS NOT NULL
               AND end_date < '1000-01-01'"
        );
    }

    public static function is_complete(): bool
    {
        global $wpdb;

        $table = Database::bookings_table();
        $remaining = $wpdb->get_var(
            "SELECT id
             FROM {$table}
             WHERE end_date IS NOT NULL
               AND end_date < '1000-01-01'
             ORDER BY end_date ASC
             LIMIT 1"
        );

        return $remaining === null;
    }
}
