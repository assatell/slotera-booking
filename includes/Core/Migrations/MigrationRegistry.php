<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;


if (!defined('ABSPATH')) {
    exit;
}

/**
 * Versioned migration registry for Slotera database/data updates.
 *
 * Keep the migration order append-only. Each entry represents the first plugin
 * version that requires the migration to have been applied. No-op releases are
 * intentionally listed to document upgrade history without adding conditional
 * branches to the main Migrator.
 *
 * @internal
 */
final class MigrationRegistry
{
    /**
     * @return array<string, callable|null>
     */
    public static function all(): array
    {
        return [
            '0.2.0' => [LegacyMigrations::class, 'migrate_to_020'],
            '0.4.0' => [LegacyMigrations::class, 'migrate_to_040'],
            '0.9.2' => [LegacyMigrations::class, 'migrate_to_092'],
            '1.0.16' => [LegacyMigrations::class, 'migrate_to_1016'],
            '1.0.17' => [LegacyMigrations::class, 'migrate_to_1017'],
            '1.0.19' => [LegacyMigrations::class, 'migrate_to_1019'],
            '1.0.20' => [LegacyMigrations::class, 'migrate_to_1020'],
            '1.0.22' => [LegacyMigrations::class, 'migrate_to_1022'],
            '1.0.24' => [LegacyMigrations::class, 'migrate_to_1024'],
            '1.0.28' => [LegacyMigrations::class, 'migrate_to_1028'],
            '1.0.35' => [LegacyMigrations::class, 'migrate_to_1035'],
            '1.0.46' => [LegacyMigrations::class, 'migrate_to_1046'],
            '1.0.48' => [LegacyMigrations::class, 'migrate_to_1048'],
            '1.0.49' => [LegacyMigrations::class, 'migrate_to_1049'],
            '1.0.50' => [LegacyMigrations::class, 'migrate_to_1050'],
            '1.0.51' => [LegacyMigrations::class, 'migrate_to_1051'],
            '1.0.53' => [LegacyMigrations::class, 'migrate_to_1053'],
            '1.0.56' => [LegacyMigrations::class, 'migrate_to_1056'],
            '1.0.60' => [LegacyMigrations::class, 'migrate_to_1060'],
            '1.0.63' => [LegacyMigrations::class, 'migrate_to_1063'],
            '1.0.65' => [LegacyMigrations::class, 'migrate_to_1065'],
            '1.0.66' => [LegacyMigrations::class, 'migrate_to_1066'],
            '1.0.67' => [LegacyMigrations::class, 'migrate_to_1067'],
            '1.0.69' => [LegacyMigrations::class, 'migrate_to_1069'],
            '1.0.86' => [LegacyMigrations::class, 'migrate_to_1086'],
            '1.0.92' => [LegacyMigrations::class, 'migrate_to_1092'],
            '1.0.93' => [LegacyMigrations::class, 'migrate_to_1093'],
            '1.0.94' => [LegacyMigrations::class, 'migrate_to_1094'],
            '1.0.96' => [LegacyMigrations::class, 'migrate_to_1096'],
            '1.0.101' => [LegacyMigrations::class, 'migrate_to_10101'],
            '1.0.120' => [LegacyMigrations::class, 'migrate_to_10120'],
            '1.0.123' => [LegacyMigrations::class, 'migrate_to_10123'],
            '1.0.124' => [LegacyMigrations::class, 'migrate_to_10124'],
            '1.0.128' => [LegacyMigrations::class, 'migrate_to_10128'],
            '1.0.129' => [LegacyMigrations::class, 'migrate_to_10129'],
            '1.0.137' => [LegacyMigrations::class, 'migrate_to_10137'],
            '1.0.141' => [LegacyMigrations::class, 'migrate_to_10141'],
            '1.0.142' => null,
            '1.0.143' => null,
            '1.0.144' => null,
            '1.0.167' => [LegacyMigrations::class, 'migrate_to_10167'],
            '1.0.177' => [LegacyMigrations::class, 'migrate_to_10177'],
            '1.0.227' => [LegacyMigrations::class, 'migrate_to_10227'],
            '1.0.228' => [LegacyMigrations::class, 'migrate_to_10228'],
            '1.0.229' => [LegacyMigrations::class, 'migrate_to_10229'],
            '1.0.230' => [LegacyMigrations::class, 'migrate_to_10230'],
            '1.0.231' => [LegacyMigrations::class, 'migrate_to_10231'],
            '1.0.232' => [LegacyMigrations::class, 'migrate_to_10232'],
            '1.0.233' => [LegacyMigrations::class, 'migrate_to_10233'],
            '1.0.234' => [LegacyMigrations::class, 'migrate_to_10234'],
            '1.0.235' => null,
            '1.0.238' => null,
            '1.0.239' => null,
            '1.0.300' => null,
            '1.0.301' => null,
            '1.0.302' => null,
            '1.0.303' => null,
            '1.0.314' => null,
            '1.0.315' => null,
            '1.0.316' => null,
            '1.0.317' => null,
            '1.0.318' => null,
            '1.0.319' => null,
            '1.0.320' => null,
            '1.0.321' => Version_1_0_321::class,
            '1.0.322' => Version_1_0_322::class,
            '1.0.323' => null,
            '1.0.332' => Version_1_0_332::class,
            '1.0.333' => Version_1_0_333::class,
            '1.0.334' => null,
            '1.0.335' => null,
            '1.0.336' => null,
            '1.0.337' => null,
            '1.0.338' => null,
            '1.0.340' => null,
            '1.0.341' => Version_1_0_341::class,
            '1.0.342' => null,
            '1.0.343' => Version_1_0_343::class,
            '1.0.344' => null,
            '1.0.345' => null,
            '1.0.346' => null,
            '1.0.347' => null,
            '1.0.348' => Version_1_0_348::class,
            '1.0.349' => null,
            '1.0.350' => null,
            '1.0.351' => null,
            '1.0.352' => null,
            '1.0.353' => null,
            '1.0.354' => null,
            '1.0.355' => null,
            '1.0.356' => null,
            '1.0.357' => null,
            '1.0.358' => null,
            '1.0.359' => null,
            '1.0.360' => Version_1_0_360::class,
            '1.0.361' => Version_1_0_361::class,
            '1.0.362' => null,
            '1.0.363' => null,
            '1.0.364' => Version_1_0_364::class,
            '1.0.365' => Version_1_0_365::class,
            '1.0.366' => Version_1_0_366::class,
            '1.0.367' => null,
            '1.0.368' => null,
            '1.0.369' => null,
            '1.0.370' => null,
            '1.0.371' => null,
            '1.0.372' => null,
            '1.0.373' => null,
            '1.0.374' => null,
            '1.0.376' => null,
            '1.0.377' => null,
            '1.0.378' => null,
            '1.0.379' => null,
            '1.0.380' => null,
            '1.0.381' => null,
            '1.0.382' => null,
            '1.0.383' => null,
            '1.0.384' => null,
            '1.0.385' => null,
            '1.0.386' => null,
            '1.0.387' => null,
            '1.0.388' => null,
            '1.0.389' => null,
            '1.0.390' => null,
            '1.0.391' => null,
            '1.0.392' => null,
            '1.0.393' => null,
            '1.0.394' => null,
            '1.0.395' => null,
            '1.0.396' => null,
            '1.0.397' => null,
            '1.0.398' => null,
            '1.0.400' => null,
            '1.0.401' => null,
            '1.0.402' => null,
            '1.0.403' => null,
            '1.0.404' => null,
            '1.0.405' => null,
            '1.0.406' => null,
            '1.0.407' => Version_1_0_407::class,
            '1.0.408' => Version_1_0_408::class,
            '1.0.409' => null,
            '1.0.410' => null,
            '1.0.411' => null,
            '1.0.412' => null,
            '1.0.413' => null,
            '1.0.414' => null,
            '1.0.415' => null,
            '1.0.416' => null,
            '1.0.417' => null,
            '1.0.418' => null,
            '1.0.419' => null,
            '1.0.420' => null,
            '1.0.421' => null,
            '1.0.422' => null,
            '1.0.423' => null,
            '1.0.424' => null,
            '1.0.425' => null,
            '1.0.426' => null,
            '1.0.427' => null,
            '1.0.428' => null,
            '1.0.429' => null,
            '1.0.430' => null,
            '1.0.431' => null,
            '1.0.432' => null,
            '1.0.433' => Version_1_0_433::class,
            '1.0.434' => null,
            '1.0.435' => null,
            '1.0.436' => null,
            '1.0.437' => null,
            '1.0.438' => null,
            '1.0.439' => Version_1_0_439::class,
            '1.0.440' => null,
            '1.0.441' => null,
            '1.0.442' => null,
            '1.0.443' => null,
            '1.0.444' => null,
            '1.0.445' => null,
            '1.0.446' => null,
            '1.0.447' => null,
            '1.0.448' => null,
            '1.0.449' => null,
            '1.0.457' => null,
            '1.0.458' => null,
            '1.0.461' => Version_1_0_461::class,
            '1.0.505' => null,
            '1.0.506' => null,
            '1.0.507' => null,
            '1.0.508' => null,
            '1.0.509' => null,
            '1.0.699' => Version_1_0_699::class,
            '1.0.701' => null,
            '1.0.702' => null,
            '1.0.703' => null,
            '1.0.704' => null,
            '1.0.706' => null,
            '1.0.707' => null,
            '1.0.708' => null,
            '1.0.709' => null,
            '1.0.710' => null,
            '1.0.711' => Version_1_0_711::class,
            '1.0.712' => Version_1_0_712::class,
            '1.0.713' => Version_1_0_713::class,
            '1.0.856' => Version_1_0_856::class,
            '1.0.916' => Version_1_0_916::class,
            '1.0.963' => Version_1_0_963::class,
            '1.0.964' => Version_1_0_964::class,
            '1.0.978' => Version_1_0_978::class,
            '1.0.1001' => Version_1_0_1001::class,
            '1.0.1019' => Version_1_0_1019::class,
            '1.0.1021' => Version_1_0_1021::class,
            '1.0.1038' => Version_1_0_1038::class,
            // Superseded by the bounded/resumable RC67.3 privacy repair.
            '1.0.1039' => null,
            '1.0.1040' => null,
            '1.0.1041' => null,
            '1.0.1042' => null,
            '1.0.1043' => Version_1_0_1043::class,
            '1.0.1047' => Version_1_0_1047::class,
            '1.0.714' => null,
        ];
    }

    /**
     * @return bool True only when every required migration is complete.
     */
    public static function run(string $current_version): bool
    {
        foreach (self::all() as $target_version => $migration) {
            if (version_compare($current_version, $target_version, '>=')) {
                continue;
            }

            if ($migration === null) {
                continue;
            }

            if (is_string($migration) && is_subclass_of($migration, MigrationInterface::class)) {
                $migration::apply();
                if (method_exists($migration, 'is_complete') && !$migration::is_complete()) {
                    return false;
                }
                continue;
            }

            if (is_callable($migration)) {
                call_user_func($migration);
            }
        }

        return true;
    }
}
