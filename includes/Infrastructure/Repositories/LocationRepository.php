<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;
use Slotera\Core\HtmlSanitizer;

if (!defined('ABSPATH')) {
    exit;
}

final class LocationRepository
{
    public function get_all(): array
    {
        global $wpdb;
        $table = Database::locations_table();
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY sort_order ASC, name ASC", ARRAY_A) ?: [];
    }

    public function get_active(): array
    {
        global $wpdb;
        $table = Database::locations_table();
        return $wpdb->get_results("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY sort_order ASC, name ASC", ARRAY_A) ?: [];
    }

    public function get_by_id(int $id): ?array
    {
        global $wpdb;
        $table = Database::locations_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = Database::locations_table();
        $now = current_time('mysql');
        $name = sanitize_text_field((string) ($data['name'] ?? ''));
        $slug = sanitize_title((string) ($data['slug'] ?? $name));
        if ($slug === '') { $slug = 'location'; }
        if ($this->slug_exists($slug)) { return 0; }

        $inserted = $wpdb->insert($table, [
            'name' => $name,
            'slug' => $slug,
            'description' => HtmlSanitizer::public_content((string) ($data['description'] ?? '')),
            'intro_content' => HtmlSanitizer::public_content((string) ($data['intro_content'] ?? ($data['description'] ?? ''))),
            'faq_json' => $this->encode_faq((array) ($data['faq'] ?? [])),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%s','%s','%s','%s','%s','%d','%d','%s','%s']);

        $id = $inserted ? (int) $wpdb->insert_id : 0;
        if ($id > 0) {
            do_action('sltr_data_changed', 'location_created', ['location_id' => $id]);
        }
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = Database::locations_table();
        $current = $this->get_by_id($id);
        if (!$current) {
            return false;
        }

        $update = [
            'name' => sanitize_text_field((string) ($data['name'] ?? $current['name'])),
            'description' => HtmlSanitizer::public_content((string) ($data['description'] ?? $current['description'])),
            'intro_content' => HtmlSanitizer::public_content((string) ($data['intro_content'] ?? ($current['intro_content'] ?? $current['description'] ?? ''))),
            'faq_json' => $this->encode_faq((array) ($data['faq'] ?? $this->decode_faq((string) ($current['faq_json'] ?? '')))),
            'sort_order' => (int) ($data['sort_order'] ?? $current['sort_order']),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'updated_at' => current_time('mysql'),
        ];
        $current_slug = sanitize_title((string) ($current['slug'] ?? ''));
        if ($current_slug === '') {
            $requested_slug = sanitize_title((string) ($data['slug'] ?? ''));
            if ($requested_slug === '' || $this->slug_exists($requested_slug, $id)) { return false; }
            $update['slug'] = $requested_slug;
        }

        $updated = $wpdb->update($table, $update, ['id' => $id]) !== false;
        if ($updated) {
            do_action('sltr_data_changed', 'location_updated', ['location_id' => $id]);
        }
        return $updated;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        $locations = Database::locations_table();
        $relations = Database::package_locations_table();
        $wpdb->delete($relations, ['location_id' => $id], ['%d']);
        $deleted = $wpdb->delete($locations, ['id' => $id], ['%d']) !== false;
        if ($deleted) {
            do_action('sltr_data_changed', 'location_deleted', ['location_id' => $id]);
        }
        return $deleted;
    }

    public function get_for_package(int $package_id): array
    {
        global $wpdb;
        $locations = Database::locations_table();
        $relations = Database::package_locations_table();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.* FROM {$locations} l INNER JOIN {$relations} pl ON pl.location_id = l.id WHERE pl.package_id = %d ORDER BY l.sort_order ASC, l.name ASC",
            $package_id
        ), ARRAY_A) ?: [];
    }



    public function count_linked_packages(int $location_id): int
    {
        global $wpdb;
        $relations = Database::package_locations_table();
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT package_id) FROM {$relations} WHERE location_id = %d",
            $location_id
        ));
    }

    public function get_packages_for_location(int $location_id, int $limit = 500): array
    {
        global $wpdb;
        $packages = Database::packages_table();
        $relations = Database::package_locations_table();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.* FROM {$packages} p INNER JOIN {$relations} pl ON pl.package_id = p.id WHERE pl.location_id = %d AND p.is_active = 1 ORDER BY p.is_popular DESC, p.title ASC LIMIT %d",
            $location_id,
            $limit
        ), ARRAY_A) ?: [];
    }

    public function get_ids_for_package(int $package_id): array
    {
        global $wpdb;
        $relations = Database::package_locations_table();
        $ids = $wpdb->get_col($wpdb->prepare("SELECT location_id FROM {$relations} WHERE package_id = %d ORDER BY location_id ASC", $package_id)) ?: [];
        return array_values(array_map('intval', $ids));
    }

    public function replace_package_locations(int $package_id, array $location_ids, array $local_content = []): void
    {
        global $wpdb;
        $relations = Database::package_locations_table();
        $wpdb->delete($relations, ['package_id' => $package_id], ['%d']);
        $location_ids = array_values(array_unique(array_filter(array_map('absint', $location_ids))));
        if (empty($location_ids)) {
            return;
        }
        $intro_overrides = (array) ($local_content['intro_override'] ?? []);
        $faq_overrides = (array) ($local_content['faq_override'] ?? []);
        $now = current_time('mysql');
        foreach ($location_ids as $location_id) {
            $wpdb->insert($relations, [
                'package_id' => $package_id,
                'location_id' => $location_id,
                'intro_override' => HtmlSanitizer::public_content((string) ($intro_overrides[$location_id] ?? '')),
                'faq_override_json' => $this->encode_faq((array) ($faq_overrides[$location_id] ?? [])),
                'created_at' => $now,
            ], ['%d','%d','%s','%s','%s']);
        }
    }

    public function get_relation(int $package_id, int $location_id): ?array
    {
        global $wpdb;
        $relations = Database::package_locations_table();
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$relations} WHERE package_id = %d AND location_id = %d LIMIT 1",
            $package_id,
            $location_id
        ), ARRAY_A);
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function get_relations_for_package(int $package_id): array
    {
        global $wpdb;
        $relations = Database::package_locations_table();
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$relations} WHERE package_id = %d", $package_id), ARRAY_A) ?: [];
        $mapped = [];
        foreach ($rows as $row) {
            $mapped[(int) ($row['location_id'] ?? 0)] = $row;
        }
        return $mapped;
    }

    public function resolve_local_intro(array $location, ?array $relation, array $package = []): string
    {
        $override = trim((string) ($relation['intro_override'] ?? ''));
        if ($override !== '') {
            return $override;
        }
        $general = trim((string) ($location['intro_content'] ?? ''));
        if ($general !== '') {
            return $general;
        }
        $description = trim((string) ($location['description'] ?? ''));
        if ($description !== '') {
            return $description;
        }
        return $this->fallback_intro($package, $location);
    }

    public function resolve_local_faq(array $location, ?array $relation): array
    {
        $override = $this->decode_faq((string) ($relation['faq_override_json'] ?? ''));
        if (!empty($override)) {
            return $override;
        }
        return $this->decode_faq((string) ($location['faq_json'] ?? ''));
    }

    private function fallback_intro(array $package, array $location): string
    {
        $package_title = sanitize_text_field((string) ($package['title'] ?? ''));
        $location_name = sanitize_text_field((string) ($location['name'] ?? ''));
        if ($package_title !== '' && $location_name !== '') {
            return sprintf(__('We offer %1$s services in %2$s.', 'slotera-booking'), $package_title, $location_name);
        }
        return '';
    }

    public function encode_faq(array $faq): string
    {
        $items = [];
        foreach ($faq as $item) {
            $question = sanitize_text_field((string) ($item['question'] ?? ''));
            $answer = HtmlSanitizer::public_content((string) ($item['answer'] ?? ''));
            if ($question === '' || trim(wp_strip_all_tags($answer)) === '') {
                continue;
            }
            $items[] = ['question' => $question, 'answer' => $answer];
        }
        return wp_json_encode($items) ?: '[]';
    }

    public function decode_faq(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? array_values(array_filter($decoded, static fn($item): bool => is_array($item))) : [];
    }

    public function slug_exists(string $slug, int $exclude_id = 0): bool
    {
        global $wpdb;
        $table = Database::locations_table();
        if ($exclude_id > 0) {
            return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s AND id <> %d LIMIT 1", $slug, $exclude_id));
        }
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s LIMIT 1", $slug));
    }


}
