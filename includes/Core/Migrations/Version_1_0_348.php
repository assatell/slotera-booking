<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_348 implements MigrationInterface
{
    public static function apply(): void
    {
        self::maybe_add_column(Database::packages_table(), 'seo_i18n_json', 'LONGTEXT NULL');
        self::maybe_add_column(Database::categories_table(), 'seo_i18n_json', 'LONGTEXT NULL');
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
