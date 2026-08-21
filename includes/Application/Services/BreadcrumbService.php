<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class BreadcrumbService
{
    private static bool $rendered = false;

    public function is_enabled(): bool
    {
        $settings = (new SettingsRepository())->all();
        return !empty($settings['seo_breadcrumbs_enabled']);
    }

    public function should_show(string $context): bool
    {
        if (!$this->is_enabled()) {
            return false;
        }
        $settings = (new SettingsRepository())->all();
        $map = [
            'package' => 'seo_breadcrumbs_show_packages',
            'category' => 'seo_breadcrumbs_show_categories',
            'local' => 'seo_breadcrumbs_show_local',
            'city' => 'seo_breadcrumbs_show_local',
        ];
        $key = $map[$context] ?? '';
        return $key !== '' && !empty($settings[$key]);
    }

    public function render_package(array $package): void
    {
        if (!$this->should_show('package') || self::$rendered) {
            return;
        }
        $title = trim(wp_strip_all_tags((string) ($package['title'] ?? '')));
        if ($title === '') {
            $title = __('Package', 'slotera-booking');
        }
        $items = [
            ['label' => __('Home', 'slotera-booking'), 'url' => home_url('/')],
            ['label' => $title, 'url' => ''],
        ];
        $this->render($items);
    }

    public function render_category(array $category): void
    {
        if (!$this->should_show('category') || self::$rendered) {
            return;
        }
        $name = trim(wp_strip_all_tags((string) ($category['name'] ?? '')));
        if ($name === '') {
            $name = __('Category', 'slotera-booking');
        }
        $items = [
            ['label' => __('Home', 'slotera-booking'), 'url' => home_url('/')],
            ['label' => $name, 'url' => ''],
        ];
        $this->render($items);
    }

    public function render_local(array $location, array $package): void
    {
        if (!$this->should_show('local') || self::$rendered) {
            return;
        }
        $location_name = trim(wp_strip_all_tags((string) ($location['name'] ?? '')));
        $package_title = trim(wp_strip_all_tags((string) ($package['title'] ?? '')));
        if ($location_name === '') {
            $location_name = __('Location', 'slotera-booking');
        }
        if ($package_title === '') {
            $package_title = __('Package', 'slotera-booking');
        }
        $location_slug = sanitize_title((string) ($location['slug'] ?? ''));
        $location_url = $location_slug !== '' ? home_url(user_trailingslashit($location_slug)) : home_url('/');
        $items = [
            ['label' => __('Home', 'slotera-booking'), 'url' => home_url('/')],
            ['label' => $location_name, 'url' => $location_url],
            ['label' => $package_title, 'url' => ''],
        ];
        $this->render($items);
    }



    public function render_city(array $location): void
    {
        if (!$this->should_show('city') || self::$rendered) {
            return;
        }
        $location_name = trim(wp_strip_all_tags((string) ($location['name'] ?? '')));
        if ($location_name === '') {
            $location_name = __('Location', 'slotera-booking');
        }
        $items = [
            ['label' => __('Home', 'slotera-booking'), 'url' => home_url('/')],
            ['label' => $location_name, 'url' => ''],
        ];
        $this->render($items);
    }

    /**
     * @param array<int,array{label:string,url:string}> $items
     */
    private function render(array $items): void
    {
        self::$rendered = true;
        echo '<nav class="sltr-breadcrumbs" aria-label="' . esc_attr__('Breadcrumbs', 'slotera-booking') . '"><div class="sltr-container">';
        echo '<ol class="sltr-breadcrumbs-list">';
        foreach ($items as $index => $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $url = (string) ($item['url'] ?? '');
            if ($label === '') { continue; }
            if ($url !== '' && $index < count($items) - 1) {
                echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
            } else {
                echo '<li aria-current="page">' . esc_html($label) . '</li>';
            }
        }
        echo '</ol></div></nav>';
    }
}
