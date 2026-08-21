<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Minimal setup that is required on a truly fresh installation after the
 * current schema has been created. Historical upgrade/backfill migrations are
 * intentionally not replayed because there is no legacy data to transform.
 */
final class FreshInstallSetup
{
    public static function run(): void
    {
        // Fresh sites receive all canonical Slotera shortcode pages immediately.
        LegacyMigrations::migrate_to_10141();
    }
}
