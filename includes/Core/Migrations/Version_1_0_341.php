<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_341 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $locations = Database::locations_table();
        $package_locations = Database::package_locations_table();

        dbDelta("CREATE TABLE {$locations} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            description LONGTEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) {$charset};");

        dbDelta("CREATE TABLE {$package_locations} (
            package_id BIGINT UNSIGNED NOT NULL,
            location_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (package_id, location_id),
            KEY location_id (location_id),
            KEY package_id (package_id)
        ) {$charset};");
    }
}
