<?php

declare(strict_types=1);

namespace Slotera\Admin\Support;

use Slotera\Infrastructure\Repositories\PackageRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Applies small post-save corrections to package mode_configs_json.
 *
 * Keeping this out of PackageController leaves the controller responsible for
 * request flow only, while this class owns persistence details for mode config
 * patches that must remain backward-compatible with older package forms.
 */
final class PackageModeConfigUpdater
{
    private PackageRepository $packages;

    public function __construct(PackageRepository $packages)
    {
        $this->packages = $packages;
    }

    public function force_simple_confirm_immediately(int $package_id, bool $enabled): void
    {
        $this->update_simple_config($package_id, [
            'confirm_immediately' => $enabled ? 1 : 0,
        ]);
    }

    public function force_simple_price_mode(int $package_id, string $price_mode): void
    {
        $price_mode = sanitize_key($price_mode);
        if (!in_array($price_mode, ['fixed', 'from', 'request'], true)) {
            $price_mode = 'fixed';
        }

        $this->update_simple_config($package_id, [
            'price_mode' => $price_mode,
        ], [
            'price_unit' => $price_mode,
        ]);
    }

    /**
     * @param array<string,mixed> $simple_config_patch
     * @param array<string,mixed> $package_patch
     */
    private function update_simple_config(int $package_id, array $simple_config_patch, array $package_patch = []): void
    {
        global $wpdb;

        $package = $this->packages->get_by_id($package_id);
        $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        if (!is_array($configs)) {
            $configs = [];
        }
        if (!isset($configs['simple']) || !is_array($configs['simple'])) {
            $configs['simple'] = [];
        }

        $configs['simple'] = array_merge($configs['simple'], $simple_config_patch);
        $data = array_merge(['mode_configs_json' => wp_json_encode($configs)], $package_patch);
        $formats = array_map(static fn($value): string => is_int($value) ? '%d' : '%s', $data);

        $wpdb->update(
            \Slotera\Core\Database::packages_table(),
            $data,
            ['id' => $package_id],
            $formats,
            ['%d']
        );
    }
}
