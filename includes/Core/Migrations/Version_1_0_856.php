<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;
use Slotera\Core\Migrator;

final class Version_1_0_856 implements MigrationInterface
{
    public static function apply(): void
    {
        $packages = Database::packages_table();
        Migrator::maybe_add_column($packages, 'title_font_family', "VARCHAR(160) NOT NULL DEFAULT '' AFTER description");
        Migrator::maybe_add_column($packages, 'title_font_size', "INT UNSIGNED NOT NULL DEFAULT 24 AFTER title_font_family");
    }
}
