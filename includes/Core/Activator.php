<?php

declare(strict_types=1);

namespace Slotera\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class Activator
{
    public static function activate(): void
    {
        try {
            self::assert_environment_supported();
            update_option('sltr_version', SLTR_VERSION);
            update_option('sltr_build_id', defined('SLTR_BUILD_ID') ? SLTR_BUILD_ID : SLTR_VERSION, false);
            delete_option('sltr_last_activation_error');
            Capabilities::install();
            Migrator::migrate();
        } catch (\Throwable $e) {
            EnvironmentCheck::deactivate_and_die([
                'Slotera Booking could not finish activation safely: ' . $e->getMessage(),
                'This usually means different server settings, database permissions, or mixed old plugin files. Delete the plugin folder and reinstall the ZIP if the problem persists.'
            ]);
        }

        foreach ([
            'Slotera\\Application\\Services\\LicenseService',
            'Slotera\\Application\\Services\\EmailReminderService',
            'Slotera\\Application\\Services\\MarketingEmailService',
            'Slotera\\Application\\Services\\MarketingAutomationService',
            'Slotera\\Application\\Services\\PrivacyService',
        ] as $class) {
            if (class_exists($class)) {
                $class::activate();
            }
        }

        if (class_exists('Slotera\\Application\\Services\\SitemapService')) {
            (new \Slotera\Application\Services\SitemapService())->register_rewrite();
            flush_rewrite_rules();
        }
    }

    private static function assert_environment_supported(): void
    {
        $errors = EnvironmentCheck::activation_errors();
        if ($errors !== []) {
            EnvironmentCheck::deactivate_and_die($errors);
        }
    }
}
