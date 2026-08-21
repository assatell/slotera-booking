<?php
declare(strict_types=1);

namespace Slotera\Core\Migrations;

if (!defined('ABSPATH')) { exit; }

interface MigrationInterface
{
    public static function apply(): void;
}
