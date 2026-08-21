<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Application\Security\SecretStore;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_407 implements MigrationInterface
{
    public static function apply(): void
    {
        $settings = get_option(SettingsRepository::OPTION_NAME, []);
        if (!is_array($settings)) {
            return;
        }
        update_option(SettingsRepository::OPTION_NAME, SecretStore::encrypt_settings($settings), false);
    }
}
