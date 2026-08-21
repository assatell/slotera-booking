<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;
use Slotera\Core\Migrator;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_1001 implements MigrationInterface
{
    public static function apply(): void
    {
        $bookings = Database::bookings_table();
        Migrator::maybe_add_column($bookings, 'pricing_adjustment_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER package_discount_amount");
        Migrator::maybe_add_column($bookings, 'pricing_adjustment_label', "VARCHAR(255) NOT NULL DEFAULT '' AFTER pricing_adjustment_amount");
    }
}
