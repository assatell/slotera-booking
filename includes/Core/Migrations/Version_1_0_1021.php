<?php
declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_1021 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;
        $table = Database::packages_table();
        $columns = [
            'solo_contact_image_id' => "BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER solo_media_json",
            'solo_contact_map' => "TEXT NULL AFTER solo_contact_image_id",
            'solo_contact_details_json' => "LONGTEXT NULL AFTER solo_contact_map",
        ];
        foreach ($columns as $name => $definition) {
            $exists = $wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE %s', $name));
            if (!$exists) { $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$name} {$definition}"); }
        }
    }
}
