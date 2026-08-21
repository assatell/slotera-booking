<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_1019 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;
        $table = Database::packages_table();
        $exists = $wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE %s', 'solo_media_json'));
        if (!$exists) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN solo_media_json LONGTEXT NULL AFTER booking_card_image_focus");
        }
    }
}
