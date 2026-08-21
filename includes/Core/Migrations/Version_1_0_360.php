<?php
declare(strict_types=1);

namespace Slotera\Core\Migrations;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_360 implements MigrationInterface
{
    public static function apply(): void
    {
        if (class_exists('Slotera\Application\Services\TranslationService')) {
            (new \Slotera\Application\Services\TranslationService())->initialize_context_locales_from_wordpress(true);
        }
    }
}
