<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_343 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;

        $locations = Database::locations_table();
        $package_locations = Database::package_locations_table();

        self::maybe_add_column($locations, 'intro_content', 'LONGTEXT NULL');
        self::maybe_add_column($locations, 'faq_json', 'LONGTEXT NULL');
        self::maybe_add_column($package_locations, 'intro_override', 'LONGTEXT NULL');
        self::maybe_add_column($package_locations, 'faq_override_json', 'LONGTEXT NULL');

        // Backfill existing location descriptions as the first general intro so upgrades keep useful content.
        $wpdb->query("UPDATE {$locations} SET intro_content = description WHERE (intro_content IS NULL OR intro_content = '') AND description IS NOT NULL AND description <> ''");
    }

    private static function maybe_add_column(string $table, string $column, string $definition): void
    {
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE %s', $column));
        if (!$exists) {
            $wpdb->query("ALTER TABLE {$table} ADD {$column} {$definition}");
        }
    }
}
