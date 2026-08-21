<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_364 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;
        $table = Database::packages_table();
        $column = $wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE %s', 'slider_image_position'));
        if (!$column) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN slider_image_position VARCHAR(10) NOT NULL DEFAULT 'top' AFTER slider_speed");
        }
    }
}
