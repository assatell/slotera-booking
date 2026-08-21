<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Application\Services\EmailTemplateRegistry;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

/** Repair localized email settings polluted by historical Russian stock copy. */
final class Version_1_0_699 implements MigrationInterface
{
    public static function apply(): void
    {
        $settings = get_option(SettingsRepository::OPTION_NAME, []);
        if (!is_array($settings)) {
            return;
        }

        $changed = false;
        foreach (EmailTemplateRegistry::scenarios() as $scenario_key => $scenario) {
            foreach ([
                'subject' => 'default_subject',
                'body' => 'default_body',
                'html_body' => 'default_html_body',
            ] as $suffix => $field) {
                if (!array_key_exists($field, $scenario)) {
                    continue;
                }

                $setting_key = 'email_template_' . $scenario_key . '_' . $suffix;
                $stored_value = array_key_exists($setting_key, $settings) ? (string) $settings[$setting_key] : null;
                if (!EmailTemplateRegistry::is_repairable_stock_value($scenario_key, $field, $stored_value)) {
                    continue;
                }

                $localized_value = (string) $scenario[$field];
                if (($settings[$setting_key] ?? null) !== $localized_value) {
                    $settings[$setting_key] = $localized_value;
                    $changed = true;
                }
            }
        }

        if ($changed) {
            update_option(SettingsRepository::OPTION_NAME, $settings, false);
        }

        update_option('sltr_email_template_data_migration_1099', [
            'completed_at' => current_time('mysql'),
            'changed' => $changed ? 1 : 0,
        ], false);
    }
}
