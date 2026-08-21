<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Database;
use Slotera\Infrastructure\Repositories\LocationRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class LocalRouteService
{
    /** @var array<string,mixed>|null */
    private static $context = null;

    public function register_hooks(): void
    {
        add_action('template_redirect', [$this, 'detect'], 0);
        add_action('template_redirect', [$this, 'maybe_render'], 20);
    }

    public static function current_context(): ?array
    {
        return self::$context;
    }

    public static function is_current_route(): bool
    {
        return self::resolve_current_request() !== null;
    }

    public static function city_url(array $location): string
    {
        $location_slug = sanitize_title((string) ($location['slug'] ?? ''));
        return $location_slug !== '' ? home_url(user_trailingslashit($location_slug)) : '';
    }

    public static function url(array $location, array $package): string
    {
        $location_slug = sanitize_title((string) ($location['slug'] ?? ''));
        $package_slug = sanitize_title((string) ($package['slug'] ?? ''));
        if ($location_slug === '' || $package_slug === '') {
            return '';
        }
        return home_url(user_trailingslashit($location_slug . '/' . $package_slug));
    }

    public function detect(): void
    {
        $context = self::resolve_current_request();
        if (!$context) {
            return;
        }

        self::$context = $context;

        global $wp_query;
        if ($wp_query) {
            $wp_query->is_404 = false;
            $wp_query->is_page = true;
            $wp_query->is_singular = true;
            $wp_query->is_archive = false;
        }
        status_header(200);
    }

    public function maybe_render(): void
    {
        if (!self::$context) {
            return;
        }

        if ((string) (self::$context['type'] ?? 'package') === 'city') {
            $this->render_city_landing((array) self::$context['location']);
            return;
        }

        $package = (array) self::$context['package'];
        $location = (array) self::$context['location'];
        $relation = (array) (self::$context['relation'] ?? []);
        $location_repo = new LocationRepository();
        $local_intro = $location_repo->resolve_local_intro($location, $relation, $package);
        $local_faq = $location_repo->resolve_local_faq($location, $relation);

        global $post;
        $page_id = (int) ($package['page_id'] ?? 0);
        $post = $page_id > 0 ? get_post($page_id) : null;
        if (!$post) {
            $post = (object) [
                'ID' => 0,
                'post_title' => (string) ($package['title'] ?? ''),
                'post_content' => '[slotera_booking package_id="' . (int) ($package['id'] ?? 0) . '"]',
                'post_status' => 'publish',
                'post_type' => 'page',
            ];
        }

        get_header();
        echo '<main id="primary" class="site-main sltr-local-package-page" data-sltr-location-id="' . esc_attr((string) ($location['id'] ?? 0)) . '">';
        (new BreadcrumbService())->render_local($location, $package);
        if (trim(wp_strip_all_tags($local_intro)) !== '') {
            echo '<section class="sltr-local-intro"><div class="sltr-container">' . wp_kses_post(wpautop($local_intro)) . '</div></section>';
        }
        include SLTR_PLUGIN_DIR . 'includes/Frontend/Views/package-detail.php';
        $this->render_faq_block($local_faq);
        echo '</main>';
        get_footer();
        exit;
    }

    private function render_city_landing(array $location): void
    {
        $location_repo = new LocationRepository();
        $packages = $location_repo->get_packages_for_location((int) ($location['id'] ?? 0));
        $intro = $location_repo->resolve_local_intro($location, null, []);
        $faq = $location_repo->resolve_local_faq($location, null);
        $location_name = trim(wp_strip_all_tags((string) ($location['name'] ?? '')));
        if ($location_name === '') {
            $location_name = __('Location', 'slotera-booking');
        }

        global $post;
        $post = (object) [
            'ID' => 0,
            'post_title' => $location_name,
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'page',
        ];

        get_header();
        echo '<main id="primary" class="site-main sltr-city-landing-page" data-sltr-location-id="' . esc_attr((string) ($location['id'] ?? 0)) . '">';
        (new BreadcrumbService())->render_city($location);
        echo '<section class="sltr-city-hero"><div class="sltr-container">';
        echo '<p class="sltr-city-kicker">' . esc_html__('Local services', 'slotera-booking') . '</p>';
        echo '<h1>' . esc_html(sprintf(__('Services in %s', 'slotera-booking'), $location_name)) . '</h1>';
        if (trim(wp_strip_all_tags($intro)) !== '') {
            echo '<div class="sltr-city-intro">' . wp_kses_post(wpautop($intro)) . '</div>';
        }
        echo '</div></section>';

        echo '<section class="sltr-city-packages"><div class="sltr-container">';
        echo '<h2>' . esc_html(sprintf(__('Available packages in %s', 'slotera-booking'), $location_name)) . '</h2>';
        if (!empty($packages)) {
            $this->render_city_package_cards($packages, $location);
        } else {
            echo '<p class="sltr-empty-state">' . esc_html__('No packages are currently available for this location.', 'slotera-booking') . '</p>';
        }
        echo '</div></section>';

        $this->render_faq_block($faq);
        echo '</main>';
        get_footer();
        exit;
    }

    private function render_city_package_cards(array $packages, array $location): void
    {
        $settings = (new SettingsRepository())->all();
        $currency = CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
        $currency_position = CurrencyService::normalize_position((string) ($settings['payment_currency_position'] ?? 'right'));
        echo '<div class="sltr-city-package-grid">';
        foreach ($packages as $package) {
            $title = trim(wp_strip_all_tags((string) ($package['title'] ?? '')));
            $description = trim(wp_strip_all_tags((string) ($package['description'] ?? '')));
            $url = self::url($location, $package);
            $price = max(0, (float) ($package['price'] ?? 0));
            echo '<article class="sltr-city-package-card">';
            echo '<h3>' . esc_html($title !== '' ? $title : __('Package', 'slotera-booking')) . '</h3>';
            if ($description !== '') {
                echo '<p>' . esc_html(wp_trim_words($description, 24, '…')) . '</p>';
            }
            if ($price > 0) {
                echo '<p class="sltr-city-package-price"><strong>' . esc_html(CurrencyService::format($price, $currency, $currency_position)) . '</strong></p>';
            }
            if ($url !== '') {
                echo '<a class="sltr-button sltr-city-package-link" href="' . esc_url($url) . '">' . esc_html__('View package', 'slotera-booking') . '</a>';
            }
            echo '</article>';
        }
        echo '</div>';
    }

    private function render_faq_block(array $faq): void
    {
        if (empty($faq)) {
            return;
        }
        echo '<section class="sltr-local-faq"><div class="sltr-container"><h2>' . esc_html__('Frequently asked questions', 'slotera-booking') . '</h2>';
        foreach ($faq as $item) {
            $question = sanitize_text_field((string) ($item['question'] ?? ''));
            $answer = wp_kses_post((string) ($item['answer'] ?? ''));
            if ($question === '' || trim(wp_strip_all_tags($answer)) === '') { continue; }
            echo '<details class="sltr-local-faq-item"><summary>' . esc_html($question) . '</summary><div>' . wp_kses_post(wpautop($answer)) . '</div></details>';
        }
        echo '</div></section>';
    }

    private static function resolve_current_request(): ?array
    {
        if (is_admin()) {
            return null;
        }

        $path = (string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $path = trim(rawurldecode($path), '/');
        if ($path === '') {
            return null;
        }

        $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
        if ($home_path !== '' && strpos($path, $home_path . '/') === 0) {
            $path = trim(substr($path, strlen($home_path)), '/');
        }

        $parts = array_values(array_filter(explode('/', $path), static function ($part): bool {
            return $part !== '';
        }));

        if (count($parts) === 1) {
            $location_slug = sanitize_title($parts[0]);
            return $location_slug !== '' ? self::find_city_context($location_slug) : null;
        }

        if (count($parts) !== 2) {
            return null;
        }

        $location_slug = sanitize_title($parts[0]);
        $package_slug = sanitize_title($parts[1]);
        if ($location_slug === '' || $package_slug === '') {
            return null;
        }

        return self::find_context($location_slug, $package_slug);
    }

    private static function find_city_context(string $location_slug): ?array
    {
        global $wpdb;
        $locations = Database::locations_table();
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$locations} WHERE slug = %s AND is_active = 1 LIMIT 1",
            $location_slug
        ), ARRAY_A);
        if (!$row) {
            return null;
        }
        $location = (new LocationRepository())->get_by_id((int) $row['id']);
        if (!$location) {
            return null;
        }
        return [
            'type' => 'city',
            'location' => $location,
            'url' => self::city_url($location),
        ];
    }

    private static function find_context(string $location_slug, string $package_slug): ?array
    {
        global $wpdb;
        $locations = Database::locations_table();
        $packages = Database::packages_table();
        $relations = Database::package_locations_table();

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT l.id AS sltr_location_row_id, p.id AS sltr_package_row_id
             FROM {$locations} l
             INNER JOIN {$relations} pl ON pl.location_id = l.id
             INNER JOIN {$packages} p ON p.id = pl.package_id
             WHERE l.slug = %s AND p.slug = %s AND l.is_active = 1 AND p.is_active = 1
             LIMIT 1",
            $location_slug,
            $package_slug
        ), ARRAY_A);

        if (!$row) {
            return null;
        }

        $location = (new LocationRepository())->get_by_id((int) $row['sltr_location_row_id']);
        $package = (new PackageRepository())->get_by_id((int) $row['sltr_package_row_id']);
        if (!$location || !$package) {
            return null;
        }

        return [
            'type' => 'package',
            'location' => $location,
            'package' => $package,
            'relation' => (new LocationRepository())->get_relation((int) $package['id'], (int) $location['id']),
            'url' => self::url($location, $package),
        ];
    }
}
