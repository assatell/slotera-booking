<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Database;
use Slotera\Infrastructure\Repositories\CategoryRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class PageBindingService
{
    public function sync_package_page(int $package_id): int
    {
        $package = (new PackageRepository())->get_by_id($package_id);
        if (!$package) {
            return 0;
        }

        $page_id = (int) ($package['page_id'] ?? 0);
        if (empty($package['solo_page_enabled'])) {
            $this->disable_package_page($page_id);
            return 0;
        }

        $post_data = [
            'post_title' => sanitize_text_field((string) ($package['title'] ?? '')),
            'post_name' => sanitize_title((string) ($package['slug'] ?? $package['title'] ?? 'package')),
            'post_content' => '[slotera_booking package_id="' . $package_id . '"]',
            'post_status' => !empty($package['is_active']) ? 'publish' : 'draft',
            'post_type' => 'page',
        ];

        $page_id = $this->upsert_page($page_id, $post_data);
        if ($page_id > 0) {
            update_post_meta($page_id, '_sltr_created_by_plugin', '1');
            update_post_meta($page_id, '_sltr_page_role', 'package:' . $package_id);
        }
        $this->set_page_id(Database::packages_table(), $package_id, $page_id);

        return $page_id;
    }

    public function sync_category_page(int $category_id): int
    {
        $category = (new CategoryRepository())->get_by_id($category_id);
        if (!$category) {
            return 0;
        }

        $page_id = (int) ($category['page_id'] ?? 0);
        $post_data = [
            'post_title' => sanitize_text_field((string) ($category['name'] ?? '')),
            'post_name' => sanitize_title((string) ($category['slug'] ?? $category['name'] ?? 'category')),
            'post_content' => '[slotera_category category_id="' . $category_id . '"]',
            'post_status' => !empty($category['is_active']) ? 'publish' : 'draft',
            'post_type' => 'page',
        ];

        $page_id = $this->upsert_page($page_id, $post_data);
        if ($page_id > 0) {
            update_post_meta($page_id, '_sltr_created_by_plugin', '1');
            update_post_meta($page_id, '_sltr_page_role', 'category:' . $category_id);
        }
        $this->set_page_id(Database::categories_table(), $category_id, $page_id);

        return $page_id;
    }

    public function disable_package_page(int $page_id): void
    {
        if ($page_id <= 0) {
            return;
        }

        $post = get_post($page_id);
        if ($post && $post->post_type === 'page' && $post->post_status !== 'trash') {
            wp_update_post([
                'ID' => $page_id,
                'post_status' => 'draft',
            ]);
        }

        $this->remove_page_from_navigation_menus($page_id);
    }

    public function trash_package_page(int $package_id): void
    {
        $package = (new PackageRepository())->get_by_id($package_id);
        if ($package) {
            $this->trash_page((int) ($package['page_id'] ?? 0));
        }
    }

    public function trash_category_page(int $category_id): void
    {
        $category = (new CategoryRepository())->get_by_id($category_id);
        if ($category) {
            $this->trash_page((int) ($category['page_id'] ?? 0));
        }
    }

    private function upsert_page(int $page_id, array $post_data): int
    {
        if ($page_id > 0) {
            $post = get_post($page_id);
            if ($post && $post->post_type === 'page' && $post->post_status !== 'trash') {
                $post_data['ID'] = $page_id;
                $updated = wp_update_post(wp_slash($post_data), true);
                return is_wp_error($updated) ? 0 : (int) $updated;
            }
        }

        $created = wp_insert_post(wp_slash($post_data), true);
        return is_wp_error($created) ? 0 : (int) $created;
    }

    private function trash_page(int $page_id): void
    {
        if ($page_id > 0) {
            $post = get_post($page_id);
            if ($post && $post->post_type === 'page' && $post->post_status !== 'trash') {
                wp_trash_post($page_id);
            }
        }
    }

    private function remove_page_from_navigation_menus(int $page_id): void
    {
        $menu_items = get_posts([
            'post_type' => 'nav_menu_item',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_key' => '_menu_item_object_id',
            'meta_value' => (string) $page_id,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        foreach ((array) $menu_items as $menu_item_id) {
            wp_delete_post((int) $menu_item_id, true);
        }
    }

    private function set_page_id(string $table, int $object_id, int $page_id): void
    {
        if ($page_id <= 0) {
            return;
        }

        global $wpdb;
        $wpdb->update($table, ['page_id' => $page_id], ['id' => $object_id], ['%d'], ['%d']);
    }
}
