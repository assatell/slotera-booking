<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_332 implements MigrationInterface
{
    public static function apply(): void
    {
        self::add_seo_columns(Database::packages_table());
        self::add_seo_columns(Database::categories_table());
    }

    private static function add_seo_columns(string $table): void
    {
        global $wpdb;

        $columns = [
            'seo_title' => "ALTER TABLE {$table} ADD COLUMN seo_title VARCHAR(255) NOT NULL DEFAULT '' AFTER description",
            'seo_description' => "ALTER TABLE {$table} ADD COLUMN seo_description TEXT NULL AFTER seo_title",
            'seo_og_title' => "ALTER TABLE {$table} ADD COLUMN seo_og_title VARCHAR(255) NOT NULL DEFAULT '' AFTER seo_description",
            'seo_og_description' => "ALTER TABLE {$table} ADD COLUMN seo_og_description TEXT NULL AFTER seo_og_title",
            'seo_og_image' => "ALTER TABLE {$table} ADD COLUMN seo_og_image VARCHAR(500) NOT NULL DEFAULT '' AFTER seo_og_description",
            'seo_canonical' => "ALTER TABLE {$table} ADD COLUMN seo_canonical VARCHAR(500) NOT NULL DEFAULT '' AFTER seo_og_image",
            'seo_robots' => "ALTER TABLE {$table} ADD COLUMN seo_robots VARCHAR(30) NOT NULL DEFAULT 'index,follow' AFTER seo_canonical",
        ];

        foreach ($columns as $column => $sql) {
            $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
            if (!$exists) {
                $wpdb->query($sql);
            }
        }
    }
}
