<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_365 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;
        $table = Database::packages_table();
        self::add_column_if_missing($wpdb, $table, 'solo_top_content', 'LONGTEXT NULL AFTER seo_i18n_json');
        self::add_column_if_missing($wpdb, $table, 'show_solo_top_content', "TINYINT(1) NOT NULL DEFAULT 0 AFTER solo_top_content");
        self::add_column_if_missing($wpdb, $table, 'show_solo_down_content', "TINYINT(1) NOT NULL DEFAULT 1 AFTER solo_down_content");
    }

    private static function add_column_if_missing(\wpdb $wpdb, string $table, string $column, string $definition): void
    {
        $exists = $wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE %s', $column));
        if (!$exists) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }
}
