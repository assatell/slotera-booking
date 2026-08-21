<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

/** Persist After booking automation settings and migrate legacy CTA defaults. */
final class Version_1_0_712 implements MigrationInterface
{
    public static function apply(): void
    {
        $settings = get_option(SettingsRepository::OPTION_NAME, []);
        if (!is_array($settings)) { $settings = []; }
        $changed = false;
        foreach (['comeback_automation_cta_label', 'after_booking_automation_cta_label'] as $key) {
            if (isset($settings[$key]) && in_array(trim((string) $settings[$key]), ['Book now', 'Book again'], true)) {
                $settings[$key] = '';
                $changed = true;
            }
        }
        if ($changed) { update_option(SettingsRepository::OPTION_NAME, $settings, false); }
        update_option('sltr_after_booking_settings_migration_10712', ['completed_at' => current_time('mysql'), 'changed' => $changed ? 1 : 0], false);
    }
}
