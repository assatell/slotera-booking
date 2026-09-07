<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

if (!defined('ABSPATH')) { exit; }

/** Revalidate every activity-log payload against redaction schema v2. */
final class Version_1_0_1053 implements MigrationInterface
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
