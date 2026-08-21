<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class SEOTemplateService
{
    /** @return array<string, string> */
    public static function variables(): array
    {
        return [
            '{package_name}' => __('Package name', 'slotera-booking'),
            '{category_name}' => __('Category name', 'slotera-booking'),
            '{location_name}' => __('Location name', 'slotera-booking'),
            '{site_name}' => __('Site name', 'slotera-booking'),
            '{site}' => __('Site name', 'slotera-booking'),
        ];
    }

    /** @param array<string, scalar|null> $context */
    public function render(string $template, array $context): string
    {
        $template = trim(wp_strip_all_tags($template));
        if ($template === '') {
            return '';
        }

        $site = isset($context['site_name']) ? (string) $context['site_name'] : wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $rendered = strtr($template, [
            '{package_name}' => (string) ($context['package_name'] ?? ''),
            '{category_name}' => (string) ($context['category_name'] ?? ''),
            '{location_name}' => (string) ($context['location_name'] ?? ''),
            '{site_name}' => $site,
            '{site}' => $site,
        ]);

        $rendered = preg_replace('/\s+/', ' ', $rendered);
        return is_string($rendered) ? trim($rendered) : '';
    }

    public function setting(string $key): string
    {
        return (string) (new SettingsRepository())->get($key, '');
    }
}
