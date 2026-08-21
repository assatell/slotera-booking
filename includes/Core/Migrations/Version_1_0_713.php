<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

/** Normalize legacy campaign CTA defaults so runtime email localization can apply. */
final class Version_1_0_713 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;
        $table = Database::marketing_campaigns_table();
        $changed = $wpdb->query("UPDATE {$table} SET cta_label = '' WHERE TRIM(cta_label) IN ('Book now', 'Book again')");
        update_option('sltr_marketing_campaign_cta_migration_10713', [
            'completed_at' => current_time('mysql'),
            'changed' => max(0, (int) $changed),
        ], false);
    }
}
