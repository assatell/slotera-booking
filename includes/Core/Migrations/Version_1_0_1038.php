<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_1038 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;

        $table = Database::packages_table();
        $exists = $wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE %s', 'media_fit_mode'));
        if (!$exists) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN media_fit_mode VARCHAR(10) NOT NULL DEFAULT 'cover' AFTER slider_speed");
        }

        LegacyMigrations::ensure_required_shortcode_pages();
    }
}
