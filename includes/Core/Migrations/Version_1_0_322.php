<?php
declare(strict_types=1);

namespace Slotera\Core\Migrations;

if (!defined('ABSPATH')) { exit; }

/**
 * v1.0.322 adds automated release-check tooling outside the production ZIP.
 *
 * No runtime schema or data changes are required for this release.
 */
final class Version_1_0_322 implements MigrationInterface
{
    public static function apply(): void
    {
        // No-op migration marker for release-check tooling alignment.
    }
}
