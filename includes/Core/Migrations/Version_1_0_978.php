<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;
use Slotera\Core\Migrator;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_978 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;

        $packages = Database::packages_table();
        $settings = get_option('sltr_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $legacy_ratio = isset($settings['tooltip_size_ratio']) ? (float) $settings['tooltip_size_ratio'] : 1.15;
        $legacy_ratio = max(0.8, min(2.0, $legacy_ratio));
        $legacy_text_size = isset($settings['tooltip_text_size']) ? (int) $settings['tooltip_text_size'] : 13;
        $legacy_text_size = max(10, min(24, $legacy_text_size));

        Migrator::maybe_add_column($packages, 'tooltip_size_ratio', "DECIMAL(4,2) NOT NULL DEFAULT 1.15 AFTER info_tooltip");
        Migrator::maybe_add_column($packages, 'tooltip_text_size', "INT UNSIGNED NOT NULL DEFAULT 13 AFTER tooltip_size_ratio");

        // Preserve the previously configured global appearance values for all
        // existing packages during the one-time move to package-level settings.
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$packages} SET tooltip_size_ratio=%f, tooltip_text_size=%d",
                $legacy_ratio,
                $legacy_text_size
            )
        );
    }
}
