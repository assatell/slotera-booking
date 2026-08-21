<?php
declare(strict_types=1);

namespace Slotera\Core\Migrations;

if (!defined('ABSPATH')) { exit; }

/**
 * v1.0.321 documents the migration-system refactor.
 *
 * No schema changes are required; future migrations should be added as
 * append-only classes in this namespace and registered in MigrationRegistry.
 */
final class Version_1_0_321 implements MigrationInterface
{
    public static function apply(): void
    {
        // No-op migration marker for the append-only migration class system.
    }
}
