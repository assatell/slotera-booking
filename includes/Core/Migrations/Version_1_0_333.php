<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_333 implements MigrationInterface
{
    public static function apply(): void
    {
        self::add_position_column(Database::packages_table());
        self::add_position_column(Database::categories_table());
    }

    private static function add_position_column(string $table): void
    {
        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", 'seo_site_title_position'));
        if (!$exists) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN seo_site_title_position VARCHAR(10) NOT NULL DEFAULT 'right' AFTER seo_title");
        }
    }
}
