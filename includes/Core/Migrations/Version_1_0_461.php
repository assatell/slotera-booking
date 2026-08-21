<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;
use Slotera\Core\Migrator;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_461 implements MigrationInterface
{
    public static function apply(): void
    {
        Migrator::maybe_add_column(Database::packages_table(), 'seo_redirect_301', "VARCHAR(500) NOT NULL DEFAULT '' AFTER seo_canonical");
        Migrator::maybe_add_column(Database::categories_table(), 'seo_redirect_301', "VARCHAR(500) NOT NULL DEFAULT '' AFTER seo_canonical");
    }
}
