<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;
use Slotera\Core\HtmlSanitizer;

if (!defined('ABSPATH')) {
    exit;
}

final class EventRepository
{
    public function get_all(int $limit = 200): array
    {
        global $wpdb;
        $table = Database::events_table();
        $limit = max(1, min(500, $limit));
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY event_date DESC, start_time DESC LIMIT %d", $limit), ARRAY_A) ?: [];
    }

    public function get_first_for_package(int $package_id): ?array
    {
        global $wpdb;
        $table = Database::events_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE package_id = %d ORDER BY id ASC LIMIT 1", $package_id), ARRAY_A);
        return $row ?: null;
    }

    public function get_for_package(int $package_id, int $limit = 200): array
    {
        global $wpdb;
        $table = Database::events_table();
        $limit = max(1, min(500, $limit));
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE package_id = %d ORDER BY event_date ASC, start_time ASC LIMIT %d", $package_id, $limit), ARRAY_A) ?: [];
    }

    public function get_active_upcoming(int $package_id = 0): array
    {
        global $wpdb;
        $table = Database::events_table();
        $today = current_time('Y-m-d');
        if ($package_id > 0) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE is_active = 1 AND status = 'scheduled' AND package_id = %d AND COALESCE(NULLIF(end_date, ''), event_date) >= %s ORDER BY event_date ASC, start_time ASC", $package_id, $today), ARRAY_A) ?: [];
        }
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE is_active = 1 AND status = 'scheduled' AND COALESCE(NULLIF(end_date, ''), event_date) >= %s ORDER BY event_date ASC, start_time ASC", $today), ARRAY_A) ?: [];
    }

    public function get_by_id(int $id): ?array
    {
        global $wpdb;
        $table = Database::events_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = Database::events_table();
        $now = current_time('mysql');
        $title = sanitize_text_field((string) ($data['title'] ?? ''));
        $slug = $this->unique_slug(sanitize_title((string) ($data['slug'] ?? $title)));

        $inserted = $wpdb->insert(
            $table,
            $this->sanitize_row(array_merge($data, [
                'title' => $title,
                'slug' => $slug,
                'created_at' => $now,
                'updated_at' => $now,
            ])),
            $this->formats()
        );

        $id = $inserted ? (int) $wpdb->insert_id : 0;
        if ($id > 0) {
            do_action('sltr_internal_event_created', $id, $this->get_by_id($id));
            do_action('sltr_data_changed', 'event_created', ['event_id' => $id, 'package_id' => absint($data['package_id'] ?? 0)]);
        }

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = Database::events_table();
        $current = $this->get_by_id($id);
        if (!$current) {
            return false;
        }

        $row = $this->sanitize_row(array_merge($current, $data, ['updated_at' => current_time('mysql')]));
        unset($row['created_at']);
        $formats = $this->formats_without_created_at();
        $updated = $wpdb->update($table, $row, ['id' => $id], $formats, ['%d']) !== false;
        if ($updated) {
            do_action('sltr_internal_event_updated', $id, $this->get_by_id($id), $current);
            do_action('sltr_data_changed', 'event_updated', ['event_id' => $id, 'package_id' => absint($row['package_id'] ?? 0)]);
        }
        return $updated;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        $current = $this->get_by_id($id);
        if (!$current) {
            return false;
        }
        $deleted = $wpdb->delete(Database::events_table(), ['id' => $id], ['%d']) !== false;
        if ($deleted) {
            do_action('sltr_internal_event_deleted', $id, $current);
            do_action('sltr_data_changed', 'event_deleted', ['event_id' => $id, 'package_id' => absint($current['package_id'] ?? 0)]);
        }
        return $deleted;
    }

    private function sanitize_row(array $data): array
    {
        $status = sanitize_key((string) ($data['status'] ?? 'scheduled'));
        if (!in_array($status, ['scheduled', 'draft', 'cancelled', 'completed'], true)) {
            $status = 'scheduled';
        }

        $date = sanitize_text_field((string) ($data['event_date'] ?? current_time('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = current_time('Y-m-d');
        }

        $end_date = sanitize_text_field((string) ($data['end_date'] ?? $date));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) || $end_date < $date) { $end_date = $date; }
        $use_time = !empty($data['use_time']) ? 1 : 0;
        $start = $this->sanitize_time((string) ($data['start_time'] ?? '09:00:00'));
        $end = $this->sanitize_time((string) ($data['end_time'] ?? '10:00:00'));
        $discount_type = sanitize_key((string) ($data['discount_type'] ?? 'none'));
        if (!in_array($discount_type, ['none','percent','fixed'], true)) { $discount_type = 'none'; }
        $discount_value = max(0, (float) ($data['discount_value'] ?? 0));
        if ($discount_type === 'percent') { $discount_value = min(100, $discount_value); }

        $payment_policy = sanitize_key((string) ($data['payment_policy'] ?? 'booking_only'));
        if (!in_array($payment_policy, ['booking_only','deposit_payment','full_payment','full_or_deposit','booking_or_full','booking_or_deposit','all_options'], true)) { $payment_policy = 'booking_only'; }
        $deposit_type = sanitize_key((string) ($data['deposit_type'] ?? 'percent'));
        if (!in_array($deposit_type, ['percent','fixed'], true)) { $deposit_type = 'percent'; }
        $deposit_value = max(0, (float) ($data['deposit_value'] ?? 30));
        if ($deposit_type === 'percent') { $deposit_value = min(100, $deposit_value); }

        $price = $data['price_override'] ?? null;
        $price = ($price === '' || $price === null) ? null : max(0, (float) $price);

        return [
            'package_id' => absint($data['package_id'] ?? 0),
            'title' => sanitize_text_field((string) ($data['title'] ?? '')),
            'slug' => sanitize_title((string) ($data['slug'] ?? 'event')),
            'description' => '',
            'event_date' => $date,
            'end_date' => $end_date,
            'use_time' => $use_time,
            'start_time' => $start,
            'end_time' => $end,
            'timezone' => sanitize_text_field((string) ($data['timezone'] ?? wp_timezone_string())),
            'capacity' => max(1, absint($data['capacity'] ?? 1)),
            'booked_count' => max(0, absint($data['booked_count'] ?? 0)),
            'price_override' => $price,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'allow_coupons' => !empty($data['allow_coupons']) ? 1 : 0,
            'payment_policy' => $payment_policy,
            'deposit_type' => $deposit_type,
            'deposit_value' => $deposit_value,
            'location' => sanitize_text_field((string) ($data['location'] ?? '')),
            'status' => $status,
            'reminder_profile' => sanitize_key((string) ($data['reminder_profile'] ?? 'default')),
            'automation_profile' => sanitize_key((string) ($data['automation_profile'] ?? 'default')),
            'meta_json' => is_array($data['meta_json'] ?? null) ? wp_json_encode($data['meta_json']) : (string) ($data['meta_json'] ?? ''),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_at' => (string) ($data['created_at'] ?? current_time('mysql')),
            'updated_at' => (string) ($data['updated_at'] ?? current_time('mysql')),
        ];
    }

    private function formats(): array
    {
        return ['%d','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d','%d','%f','%s','%f','%d','%s','%s','%f','%s','%s','%s','%s','%s','%d','%s','%s'];
    }

    private function formats_without_created_at(): array
    {
        return ['%d','%s','%s','%s','%s','%s','%d','%s','%s','%s','%d','%d','%f','%s','%f','%d','%s','%s','%f','%s','%s','%s','%s','%s','%d','%s'];
    }

    private function sanitize_time(string $time): string
    {
        $time = sanitize_text_field($time);
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        return '09:00:00';
    }

    private function unique_slug(string $slug): string
    {
        global $wpdb;
        $table = Database::events_table();
        if ($slug === '') {
            $slug = 'event';
        }
        $base = $slug;
        $i = 2;
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s LIMIT 1", $slug))) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
