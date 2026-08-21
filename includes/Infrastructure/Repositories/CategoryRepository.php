<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;
use Slotera\Core\HtmlSanitizer;

if (!defined('ABSPATH')) {
    exit;
}

final class CategoryRepository
{
    public function get_all(): array
    {
        global $wpdb;
        $table = Database::categories_table();
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY sort_order ASC, name ASC", ARRAY_A) ?: [];
    }

    public function get_active(): array
    {
        global $wpdb;
        $table = Database::categories_table();
        return $wpdb->get_results("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY sort_order ASC, name ASC", ARRAY_A) ?: [];
    }

    public function get_by_id(int $id): ?array
    {
        global $wpdb;
        $table = Database::categories_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = Database::categories_table();
        $now = current_time('mysql');
        $name = sanitize_text_field((string) ($data['name'] ?? ''));
        $slug = sanitize_title((string) ($data['slug'] ?? $name));
        if ($slug === '') { $slug = 'category'; }
        if ($this->slug_exists($slug)) { return 0; }

        $inserted = $wpdb->insert(
            $table,
            [
                'name' => $name,
                'slug' => $slug,
                'description' => HtmlSanitizer::public_content((string) ($data['description'] ?? '')),
                'seo_title' => sanitize_text_field((string) ($data['seo_title'] ?? '')),
                'seo_site_title_position' => $this->sanitize_title_position((string) ($data['seo_site_title_position'] ?? 'right')),
                'seo_description' => sanitize_textarea_field((string) ($data['seo_description'] ?? '')),
                'seo_og_title' => sanitize_text_field((string) ($data['seo_og_title'] ?? '')),
                'seo_og_description' => sanitize_textarea_field((string) ($data['seo_og_description'] ?? '')),
                'seo_og_image' => esc_url_raw((string) ($data['seo_og_image'] ?? '')),
                'seo_canonical' => esc_url_raw((string) ($data['seo_canonical'] ?? '')),
                'seo_redirect_301' => esc_url_raw((string) ($data['seo_redirect_301'] ?? '')),
                'seo_robots' => $this->sanitize_robots((string) ($data['seo_robots'] ?? 'index,follow')),
                'seo_i18n_json' => $this->sanitize_seo_i18n_json((string) ($data['seo_i18n_json'] ?? '')),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => !empty($data['is_active']) ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        $id = $inserted ? (int) $wpdb->insert_id : 0;
        if ($id > 0) {
            do_action('sltr_data_changed', 'category_created', ['category_id' => $id]);
        }
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = Database::categories_table();
        $current = $this->get_by_id($id);

        if (!$current) {
            return false;
        }

        $update = [
            'name' => sanitize_text_field((string) ($data['name'] ?? $current['name'])),
            'description' => HtmlSanitizer::public_content((string) ($data['description'] ?? $current['description'])),
            'seo_title' => sanitize_text_field((string) ($data['seo_title'] ?? ($current['seo_title'] ?? ''))),
            'seo_site_title_position' => $this->sanitize_title_position((string) ($data['seo_site_title_position'] ?? ($current['seo_site_title_position'] ?? 'right'))),
            'seo_description' => sanitize_textarea_field((string) ($data['seo_description'] ?? ($current['seo_description'] ?? ''))),
            'seo_og_title' => sanitize_text_field((string) ($data['seo_og_title'] ?? ($current['seo_og_title'] ?? ''))),
            'seo_og_description' => sanitize_textarea_field((string) ($data['seo_og_description'] ?? ($current['seo_og_description'] ?? ''))),
            'seo_og_image' => esc_url_raw((string) ($data['seo_og_image'] ?? ($current['seo_og_image'] ?? ''))),
            'seo_canonical' => esc_url_raw((string) ($data['seo_canonical'] ?? ($current['seo_canonical'] ?? ''))),
                'seo_redirect_301' => esc_url_raw((string) ($data['seo_redirect_301'] ?? ($current['seo_redirect_301'] ?? ''))),
            'seo_robots' => $this->sanitize_robots((string) ($data['seo_robots'] ?? ($current['seo_robots'] ?? 'index,follow'))),
            'seo_i18n_json' => $this->sanitize_seo_i18n_json((string) ($data['seo_i18n_json'] ?? ($current['seo_i18n_json'] ?? ''))),
            'sort_order' => (int) ($data['sort_order'] ?? $current['sort_order']),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'updated_at' => current_time('mysql'),
        ];

        $old_slug = sanitize_title((string) ($current['slug'] ?? ''));
        $current_slug = sanitize_title((string) ($current['slug'] ?? ''));
        if ($current_slug === '') {
            $requested_slug = sanitize_title((string) ($data['slug'] ?? ''));
            if ($requested_slug === '' || $this->slug_exists($requested_slug, $id)) { return false; }
            $update['slug'] = $requested_slug;
        }
        $new_slug = sanitize_title((string) ($update['slug'] ?? $old_slug));

        $updated = $wpdb->update($table, $update, ['id' => $id]) !== false;
        if ($updated) {
            do_action('sltr_data_changed', 'category_updated', [
                'category_id' => $id,
                'old_slug' => $old_slug,
                'new_slug' => $new_slug,
                'page_id' => (int) ($current['page_id'] ?? 0),
            ]);
        }
        return $updated;
    }

    public function delete(int $id): bool
    {
        global $wpdb;

        $categories_table = Database::categories_table();
        $packages_table = Database::packages_table();

        $wpdb->update($packages_table, ['category_id' => 0], ['category_id' => $id], ['%d'], ['%d']);
        $current = $this->get_by_id($id);
        $result = $wpdb->delete($categories_table, ['id' => $id], ['%d']);

        $deleted = $result !== false;
        if ($deleted) {
            do_action('sltr_data_changed', 'category_deleted', ['category_id' => $id, 'post_ids' => !empty($current['page_id']) ? [absint($current['page_id'])] : []]);
        }
        return $deleted;
    }

    private function sanitize_robots(string $robots): string
    {
        return in_array($robots, ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'], true) ? $robots : 'index,follow';
    }


    private function sanitize_seo_i18n_json(string $json): string
    {
        $decoded = json_decode(wp_unslash($json), true);
        if (!is_array($decoded)) { return ''; }
        $clean = [];
        foreach ($decoded as $locale => $fields) {
            $locale = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $locale);
            if ($locale === '' || !is_array($fields)) { continue; }
            $entry = [
                'seo_title' => sanitize_text_field((string) ($fields['seo_title'] ?? '')),
                'seo_description' => sanitize_textarea_field((string) ($fields['seo_description'] ?? '')),
                'seo_og_title' => sanitize_text_field((string) ($fields['seo_og_title'] ?? '')),
                'seo_og_description' => sanitize_textarea_field((string) ($fields['seo_og_description'] ?? '')),
                'seo_og_image' => esc_url_raw((string) ($fields['seo_og_image'] ?? '')),
                'seo_canonical' => esc_url_raw((string) ($fields['seo_canonical'] ?? '')),
            ];
            $entry = array_filter($entry, static fn($value) => $value !== '');
            if (!empty($entry)) { $clean[$locale] = $entry; }
        }
        return empty($clean) ? '' : (string) wp_json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function count_linked_packages(int $category_id): int
    {
        global $wpdb;
        $packages = Database::packages_table();
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$packages} WHERE category_id = %d",
            $category_id
        ));
    }

    public function slug_exists(string $slug, int $exclude_id = 0): bool
    {
        global $wpdb;
        $table = Database::categories_table();
        if ($exclude_id > 0) {
            return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s AND id <> %d LIMIT 1", $slug, $exclude_id));
        }
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s LIMIT 1", $slug));
    }

    private function sanitize_title_position(string $position): string
    {
        return in_array($position, ['left', 'right'], true) ? $position : 'right';
    }

}
