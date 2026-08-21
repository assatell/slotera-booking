<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

final class Version_1_0_916 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;
        $table = Database::packages_table();
        $wpdb->query("ALTER TABLE {$table} MODIFY title_font_size INT UNSIGNED NOT NULL DEFAULT 24");
        $wpdb->query("ALTER TABLE {$table} MODIFY description_font_size INT UNSIGNED NOT NULL DEFAULT 18");
    }
}
