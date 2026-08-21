<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;
use Slotera\Core\Migrator;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_439 implements MigrationInterface
{
    public static function apply(): void
    {
        $packages = Database::packages_table();
        Migrator::maybe_add_column($packages, 'right_block_title_font_family', "VARCHAR(120) NOT NULL DEFAULT '' AFTER right_block_text");
        Migrator::maybe_add_column($packages, 'right_block_title_font_size', "INT UNSIGNED NOT NULL DEFAULT 28 AFTER right_block_title_font_family");
        Migrator::maybe_add_column($packages, 'right_block_text_font_family', "VARCHAR(120) NOT NULL DEFAULT '' AFTER right_block_title_font_size");
        Migrator::maybe_add_column($packages, 'right_block_text_font_size', "INT UNSIGNED NOT NULL DEFAULT 18 AFTER right_block_text_font_family");
    }
}
