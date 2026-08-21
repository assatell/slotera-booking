<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Application\Security\SecretStore;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_408 implements MigrationInterface
{
    public static function apply(): void
    {
        $settings = get_option(SettingsRepository::OPTION_NAME, []);
        if (!is_array($settings)) { $settings = []; }

        if (!array_key_exists('security_public_rest_booking_auth_mode', $settings)) {
            $settings['security_public_rest_booking_auth_mode'] = 'site_form';
        }
        if (!array_key_exists('security_public_rest_booking_api_key', $settings)) {
            $settings['security_public_rest_booking_api_key'] = '';
        }
        if (!array_key_exists('security_public_rest_booking_hmac_secret', $settings)) {
            $settings['security_public_rest_booking_hmac_secret'] = '';
        }

        update_option(SettingsRepository::OPTION_NAME, SecretStore::encrypt_settings($settings), false);
    }
}
