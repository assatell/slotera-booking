<?php
declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class MarketingCampaignRepository
{
    public function get_all(): array
    {
        global $wpdb;
        $table = Database::marketing_campaigns_table();
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A) ?: [];
    }

    public function get_by_id(int $id): ?array
    {
        global $wpdb;
        $table = Database::marketing_campaigns_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id), ARRAY_A);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = Database::marketing_campaigns_table();
        $now = current_time('mysql');
        $ok = $wpdb->insert($table, array_merge($this->normalize($data), ['created_at' => $now, 'updated_at' => $now]));
        return $ok ? (int) $wpdb->insert_id : 0;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = Database::marketing_campaigns_table();
        $current = $this->get_by_id($id);
        if (!$current) { return false; }
        $normalized = $this->normalize(array_merge($current, $data));
        $normalized['updated_at'] = current_time('mysql');
        return $wpdb->update($table, $normalized, ['id' => $id]) !== false;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        return $wpdb->delete(Database::marketing_campaigns_table(), ['id' => $id], ['%d']) !== false;
    }

    public function update_status(int $id, string $status): bool
    {
        global $wpdb;
        $allowed = ['draft', 'queued', 'sending', 'completed', 'paused', 'stopped', 'sent', 'failed'];
        if (!in_array($status, $allowed, true)) { $status = 'draft'; }
        return $wpdb->update(Database::marketing_campaigns_table(), ['status' => $status, 'updated_at' => current_time('mysql')], ['id' => $id]) !== false;
    }

    public function get_processable_ids(): array
    {
        global $wpdb;
        $table = Database::marketing_campaigns_table();
        $ids = $wpdb->get_col("SELECT id FROM {$table} WHERE status IN ('queued','sending') ORDER BY updated_at ASC,id ASC");
        return array_map('absint', $ids ?: []);
    }

    private function normalize_cta_url_type(string $type): string
    {
        $type = sanitize_key($type);
        return in_array($type, ['booking', 'package', 'custom'], true) ? $type : 'booking';
    }


    private function normalize_csv_keys(string $csv): string
    {
        $parts = array_filter(array_map('sanitize_key', array_map('trim', explode(',', $csv))));
        $parts = array_values(array_unique($parts));
        return implode(',', $parts);
    }

    private function normalize_last_booking_mode(string $mode): string
    {
        $mode = sanitize_key($mode);
        return in_array($mode, ['any', 'within_days', 'older_than_days'], true) ? $mode : 'any';
    }

    private function normalize_coupon_filter(string $filter): string
    {
        $filter = sanitize_key($filter);
        return in_array($filter, ['any', 'used_coupon', 'never_used_coupon', 'used_selected_coupon'], true) ? $filter : 'any';
    }

    private function normalize_source(string $source): string
    {
        $source = sanitize_key($source);
        return in_array($source, ['coupon', 'coupon_bound', 'automation', 'promotion_digest'], true) ? $source : 'coupon';
    }

    private function normalize_automation_type(string $type): string
    {
        $type = sanitize_key($type);
        return in_array($type, ['come_back', 'after_booking'], true) ? $type : '';
    }

    private function normalize(array $data): array
    {
        $audience = sanitize_key((string) ($data['audience_type'] ?? 'all'));
        if (!in_array($audience, ['all', 'package', 'completed', 'inactive_30', 'advanced'], true)) { $audience = 'all'; }
        return [
            'name' => sanitize_text_field((string) ($data['name'] ?? '')),
            'template_key' => sanitize_key((string) ($data['template_key'] ?? '')),
            'subject_override' => sanitize_text_field((string) ($data['subject_override'] ?? '')),
            'audience_type' => $audience,
            'package_id' => absint($data['package_id'] ?? 0),
            'coupon_id' => absint($data['coupon_id'] ?? 0),
            'generate_unique_coupons' => !empty($data['generate_unique_coupons']) ? 1 : 0,
            'cta_enabled' => !empty($data['cta_enabled']) ? 1 : 0,
            'cta_label' => sanitize_text_field((string) ($data['cta_label'] ?? '')),
            'cta_url_type' => $this->normalize_cta_url_type((string) ($data['cta_url_type'] ?? 'booking')),
            'cta_custom_url' => esc_url_raw((string) ($data['cta_custom_url'] ?? '')),
            'audience_statuses' => $this->normalize_csv_keys((string) ($data['audience_statuses'] ?? '')),
            'audience_payment_statuses' => $this->normalize_csv_keys((string) ($data['audience_payment_statuses'] ?? '')),
            'audience_last_booking_mode' => $this->normalize_last_booking_mode((string) ($data['audience_last_booking_mode'] ?? 'any')),
            'audience_last_booking_days' => max(0, absint($data['audience_last_booking_days'] ?? 30)),
            'audience_min_bookings' => max(0, absint($data['audience_min_bookings'] ?? 0)),
            'audience_max_bookings' => max(0, absint($data['audience_max_bookings'] ?? 0)),
            'audience_min_spent' => max(0, (float) ($data['audience_min_spent'] ?? 0)),
            'audience_max_spent' => max(0, (float) ($data['audience_max_spent'] ?? 0)),
            'audience_coupon_filter' => $this->normalize_coupon_filter((string) ($data['audience_coupon_filter'] ?? 'any')),
            'marketing_headline' => sanitize_text_field((string) ($data['marketing_headline'] ?? '')),
            'marketing_message' => wp_kses_post((string) ($data['marketing_message'] ?? '')),
            'marketing_submessage' => wp_kses_post((string) ($data['marketing_submessage'] ?? '')),
            'source' => $this->normalize_source((string) ($data['source'] ?? 'coupon')),
            'automation_type' => $this->normalize_automation_type((string) ($data['automation_type'] ?? '')),
            'status' => sanitize_key((string) ($data['status'] ?? 'draft')) ?: 'draft',
        ];
    }
}
