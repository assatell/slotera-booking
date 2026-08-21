<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_366 implements MigrationInterface
{
    public static function apply(): void
    {
        // No database changes. Layout-only hotfix for solo package top content placement.
    }
}
