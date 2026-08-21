<?php

declare(strict_types=1);

namespace Slotera\Application\Services\Translations;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Read-only translation boundary metadata for the staged Admin UI separation.
 *
 * Version 1.0.623 completes the staged separation. Version 1.0.617 removed the editable Admin UI translation group while it
 * establishes explicit protected areas and exposes architecture diagnostics.
 */
final class TranslationBoundaryRegistry
{
    private const MIXED_TRANSLATION_SERVICES = 0;
    private const UNKNOWN_TRANSLATION_GROUPS = 0;

    public function boundaries(): array
    {
        $file = SLTR_PLUGIN_DIR . 'includes/config/translation-boundaries.php';
        $boundaries = is_readable($file) ? require $file : [];

        return is_array($boundaries) ? $boundaries : [];
    }

    public function protected_boundaries(): array
    {
        return array_filter(
            $this->boundaries(),
            static fn(array $boundary): bool => !empty($boundary['protected'])
        );
    }

    public function is_protected(string $boundary): bool
    {
        $boundaries = $this->boundaries();
        return !empty($boundaries[$boundary]['protected']);
    }

    public function diagnostics(): array
    {
        $boundaries = $this->boundaries();
        $required = ['admin', 'frontend', 'email_settings', 'email_templates', 'internal'];
        $ready = count(array_intersect($required, array_keys($boundaries))) === count($required)
            && $this->is_protected('frontend')
            && $this->is_protected('email_settings')
            && $this->is_protected('email_templates');

        return [
            'ready' => $ready,
            'mixed_translation_services' => self::MIXED_TRANSLATION_SERVICES,
            'unknown_translation_groups' => self::UNKNOWN_TRANSLATION_GROUPS,
        ];
    }
}
