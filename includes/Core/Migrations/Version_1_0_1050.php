<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

if (!defined('ABSPATH')) { exit; }

/**
 * Re-run the strict activity-log privacy completion check for sites that had
 * already advanced beyond the original RC67.3 migration.
 */
final class Version_1_0_1050 implements MigrationInterface
{
    public static function apply(): void
    {
        Version_1_0_1043::apply();
    }

    public static function is_complete(): bool
    {
        return Version_1_0_1043::is_complete();
    }
}
