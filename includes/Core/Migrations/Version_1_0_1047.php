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
               AND CAST(end_date AS CHAR) = '0000-00-00'"
        );
    }

    public static function is_complete(): bool
    {
        global $wpdb;

        $table = Database::bookings_table();
        $remaining = $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$table}
             WHERE end_date IS NOT NULL
               AND CAST(end_date AS CHAR) = '0000-00-00'"
        );

        return $remaining !== null && (int) $remaining === 0;
    }
}
