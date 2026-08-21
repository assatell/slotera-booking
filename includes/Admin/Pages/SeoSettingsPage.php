<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Core\Capabilities;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class SeoSettingsPage
{
    public function render(bool $embedded_in_settings = false): void
    {
        if (!current_user_can(Capabilities::MANAGE_SETTINGS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        $sltr_embedded_in_settings = $embedded_in_settings;
        $settings = (new SettingsRepository())->all();
        $seo_service = new \Slotera\Application\Services\SEOService();
        $detected_seo_plugins = $seo_service->detected_external_plugins();
        $robots_service = new \Slotera\Application\Services\RobotsTxtService();
        $robots_file_exists = $robots_service->exists();
        $robots_file_path = $robots_service->path();
        $robots_file_writable = $robots_service->is_writable_target();
        $robots_current_content = $robots_service->current_content();
        $robots_default_content = $robots_service->default_content();
        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'settings';
        if ($tab === 'wp-pages' || $tab === 'slotera-pages') {
            $tab = 'individual';
        }
        if (!in_array($tab, ['settings', 'individual', 'templates', 'warnings'], true)) {
            $tab = 'settings';
        }



        global $wpdb;
        $packages_table = \Slotera\Core\Database::packages_table();
        $categories_table = \Slotera\Core\Database::categories_table();
        $locations_table = \Slotera\Core\Database::locations_table();
        $package_locations_table = \Slotera\Core\Database::package_locations_table();
        $packages = $wpdb->get_results("SELECT * FROM {$packages_table} ORDER BY title ASC LIMIT 200", ARRAY_A) ?: [];
        $categories = $wpdb->get_results("SELECT * FROM {$categories_table} ORDER BY name ASC LIMIT 200", ARRAY_A) ?: [];

        $slotera_page_ids = [];
        foreach (array_merge($packages, $categories) as $item) {
            $page_id = (int) ($item['page_id'] ?? 0);
            if ($page_id > 0) {
                $slotera_page_ids[$page_id] = true;
            }
        }

        $other_pages_search = isset($_GET['sltr_other_pages_search']) ? sanitize_text_field((string) wp_unslash($_GET['sltr_other_pages_search'])) : '';
        $other_pages_status = isset($_GET['sltr_other_pages_status']) ? sanitize_key((string) wp_unslash($_GET['sltr_other_pages_status'])) : 'all';
        $allowed_other_pages_statuses = ['all', 'publish', 'draft', 'pending', 'private'];
        if (!in_array($other_pages_status, $allowed_other_pages_statuses, true)) {
            $other_pages_status = 'all';
        }
        $other_pages_per_page = 20;
        $other_pages_page = isset($_GET['sltr_other_pages_page']) ? max(1, (int) $_GET['sltr_other_pages_page']) : 1;
        $other_pages_query_args = [
            'post_type' => 'page',
            'post_status' => $other_pages_status === 'all' ? ['publish', 'draft', 'pending', 'private'] : [$other_pages_status],
            'posts_per_page' => $other_pages_per_page,
            'paged' => $other_pages_page,
            'orderby' => 'title',
            'order' => 'ASC',
            'post__not_in' => array_map('intval', array_keys($slotera_page_ids)),
        ];
        if ($other_pages_search !== '') {
            $other_pages_query_args['s'] = $other_pages_search;
        }
        $other_pages_query = new \WP_Query($other_pages_query_args);
        $pages = array_values(array_filter((array) $other_pages_query->posts, static function ($page): bool {
            $content = (string) $page->post_content;
            return !preg_match('/\[(slotera_booking|slotera_category)\b/i', $content);
        }));
        $other_pages_total_pages = max(1, (int) $other_pages_query->max_num_pages);
        $other_pages_total_items = max(0, (int) $other_pages_query->found_posts);
        wp_reset_postdata();
        $locations = $wpdb->get_results("SELECT * FROM {$locations_table} ORDER BY sort_order ASC, name ASC LIMIT 200", ARRAY_A) ?: [];
        $package_location_relations = $wpdb->get_results("SELECT pl.*, p.title AS package_title, p.slug AS package_slug, l.name AS location_name, l.slug AS location_slug FROM {$package_locations_table} pl INNER JOIN {$packages_table} p ON p.id = pl.package_id INNER JOIN {$locations_table} l ON l.id = pl.location_id ORDER BY p.title ASC, l.sort_order ASC, l.name ASC LIMIT 500", ARRAY_A) ?: [];

        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/seo-settings.php';
    }

    public function redirectLegacy(): void
    {
        if (!current_user_can(Capabilities::MANAGE_SETTINGS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        $args = ['page' => 'slotera-settings', 'section' => 'seo'];
        foreach (['tab', 'sltr_focus', 'sltr_other_pages_search', 'sltr_other_pages_status', 'sltr_other_pages_page', 'sltr_message'] as $key) {
            if (isset($_GET[$key])) {
                $args[$key] = sanitize_text_field((string) wp_unslash($_GET[$key]));
            }
        }

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

}
