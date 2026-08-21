<?php

declare(strict_types=1);

namespace Slotera\Application\TranslationQuality;

if (!defined('ABSPATH')) { exit; }

final class SeverityResolver
{
    public const CRITICAL = 'critical';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';

    public function for_type(string $type): string
    {
        if (in_array($type, ['placeholder_errors', 'placeholder_type_errors', 'html_errors', 'html_entity_errors', 'unicode_errors', 'section_completeness', 'freeze_drift'], true)) {
            return self::CRITICAL;
        }
        if (in_array($type, ['same_as_english', 'mixed_candidates', 'quality_lint', 'ux_linguistic', 'whitespace_errors', 'duplicate_translation'], true)) {
            return self::WARNING;
        }
        return self::NOTICE;
    }
}
