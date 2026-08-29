<?php
declare(strict_types=1);
namespace Slotera\Core;

use Slotera\Core\Migrations\FreshInstallSetup;
use Slotera\Core\Migrations\LegacyMigrations;
use Slotera\Core\Migrations\MigrationRegistry;

if (!defined('ABSPATH')) { exit; }

final class Migrator {
    public const DB_VERSION_OPTION = 'sltr_db_version';

    public static function migrate(): void {
        $stored_version = get_option(self::DB_VERSION_OPTION, null);
        $old = is_string($stored_version) && $stored_version !== '' ? $stored_version : '0.0.0';
        $fresh_install = self::is_fresh_install($stored_version);

        Database::create_tables();

        if ($fresh_install) {
            FreshInstallSetup::run();
        } else {
            if (!MigrationRegistry::run($old)) {
                return;
            }
        }

        update_option(self::DB_VERSION_OPTION, SLTR_VERSION);
    }

    /**
     * Fast path is deliberately conservative. Missing DB version alone is not
     * enough because a damaged/legacy site or a shared-table installation can
     * still contain real Slotera data. Only an option-less install with none of
     * the core Slotera tables present is treated as new.
     */
    private static function is_fresh_install(mixed $stored_version): bool {
        if ($stored_version !== null && $stored_version !== false && $stored_version !== '') {
            return false;
        }

        foreach ([
            Database::bookings_table(),
            Database::packages_table(),
            Database::categories_table(),
            Database::marketing_campaigns_table(),
        ] as $table) {
            if (self::table_exists($table)) {
                return false;
            }
        }

        return true;
    }

    private static function table_exists(string $table): bool {
        global $wpdb;
        $found = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))
        );
        return is_string($found) && $found === $table;
    }

    public static function register_hooks(): void {
        ActiveSlotHashBackfill::register_hooks();
    }

    public static function run_active_slot_hash_backfill_batch(): void {
        ActiveSlotHashBackfill::run_batch();
    }

    public static function print_active_slot_hash_backfill_notice(): void {
        ActiveSlotHashBackfill::print_admin_notice();
    }

    public static function ajax_run_active_slot_hash_backfill_batch(): void {
        ActiveSlotHashBackfill::ajax_run_batch();
    }

    public static function maybe_add_column(string $table, string $column, string $definition): void {
        LegacyMigrations::maybe_add_column($table, $column, $definition);
    }

    public static function maybe_add_index(string $table, string $name, string $definition): void {
        LegacyMigrations::maybe_add_index($table, $name, $definition);
    }
}
