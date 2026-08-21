<?php
declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class MarketingLogRepository
{
    public function create(array $data): int
    {
        global $wpdb;
        $table = Database::marketing_logs_table();
        $now = current_time('mysql');
        $ok = $wpdb->insert($table, [
            'campaign_id' => absint($data['campaign_id'] ?? 0),
            'customer_email' => sanitize_email((string) ($data['customer_email'] ?? '')),
            'customer_name' => sanitize_text_field((string) ($data['customer_name'] ?? '')),
            'status' => sanitize_key((string) ($data['status'] ?? 'pending')),
            'subject' => sanitize_text_field((string) ($data['subject'] ?? '')),
            'error_message' => sanitize_textarea_field((string) ($data['error_message'] ?? '')),
            'sent_at' => !empty($data['sent_at']) ? sanitize_text_field((string) $data['sent_at']) : null,
            'attempts' => absint($data['attempts'] ?? 0),
            'max_attempts' => max(1, absint($data['max_attempts'] ?? 3)),
            'last_try' => !empty($data['last_try']) ? sanitize_text_field((string) $data['last_try']) : null,
            'payload_json' => wp_json_encode($data['payload'] ?? []),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $ok ? (int) $wpdb->insert_id : 0;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $allowed = [];
        foreach (['status', 'subject', 'error_message', 'sent_at', 'attempts', 'max_attempts', 'last_try', 'payload_json'] as $key) {
            if (array_key_exists($key, $data)) { $allowed[$key] = $data[$key]; }
        }
        if (empty($allowed)) { return false; }
        $allowed['updated_at'] = current_time('mysql');
        return $wpdb->update(Database::marketing_logs_table(), $allowed, ['id' => $id]) !== false;
    }

    public function delete_for_campaign(int $campaign_id): int
    {
        global $wpdb;
        if ($campaign_id <= 0) { return 0; }
        $deleted = $wpdb->delete(Database::marketing_logs_table(), ['campaign_id' => $campaign_id], ['%d']);
        return is_numeric($deleted) ? (int) $deleted : 0;
    }

    public function campaign_source(int $campaign_id, string $campaign_name = ''): string
    {
        global $wpdb;
        if ($campaign_id > 0) {
            $campaigns = Database::marketing_campaigns_table();
            $source = sanitize_key((string) $wpdb->get_var($wpdb->prepare("SELECT source FROM {$campaigns} WHERE id=%d LIMIT 1", $campaign_id)));
            if ($source === 'coupon_bound') { return 'coupon'; }
            if (in_array($source, ['automation', 'coupon'], true)) { return $source; }
        }
        return $this->legacy_is_automation_campaign($campaign_id, $campaign_name) ? 'automation' : 'coupon';
    }

    public function get_for_campaign(int $campaign_id, int $limit = 200): array
    {
        global $wpdb;
        $table = Database::marketing_logs_table();
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE campaign_id=%d ORDER BY created_at DESC,id DESC LIMIT %d", $campaign_id, max(1, min(500, $limit))), ARRAY_A) ?: [];
    }

    public function exists_for_email(int $campaign_id, string $email): bool
    {
        global $wpdb;
        $table = Database::marketing_logs_table();
        $email = sanitize_email($email);
        if ($campaign_id <= 0 || $email === '') { return false; }
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE campaign_id=%d AND customer_email=%s LIMIT 1", $campaign_id, $email));
    }


    public function exists_recent_automation_for_email(string $automation_key, string $email, int $days): bool
    {
        global $wpdb;
        $table = Database::marketing_logs_table();
        $email = sanitize_email($email);
        $automation_key = sanitize_key($automation_key);
        $days = max(1, $days);
        if ($email === '' || $automation_key === '') { return false; }
        $since = wp_date('Y-m-d H:i:s', current_time('timestamp') - $days * DAY_IN_SECONDS);
        $like = '%"automation":"' . $wpdb->esc_like($automation_key) . '"%';
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE customer_email=%s AND status IN ('pending','sending','sent') AND created_at >= %s AND payload_json LIKE %s LIMIT 1",
            $email,
            $since,
            $like
        ));
    }

    public function is_automation_campaign(int $campaign_id, string $campaign_name = ''): bool
    {
        return $this->campaign_source($campaign_id, $campaign_name) === 'automation';
    }

    private function legacy_is_automation_campaign(int $campaign_id, string $campaign_name = ''): bool
    {
        global $wpdb;
        if ($campaign_id <= 0) { return false; }
        $table = Database::marketing_logs_table();
        $like = '%"automation":"%';
        $found = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE campaign_id=%d AND payload_json LIKE %s LIMIT 1",
            $campaign_id,
            $like
        ));
        if ($found) { return true; }

        $name = trim($campaign_name);
        if ($name === '') { return false; }
        $prefixes = [
            'Come back automation —',
            'After booking automation —',
            __('Come back automation —', 'slotera-booking'),
            __('After booking automation —', 'slotera-booking'),
        ];
        foreach (array_unique($prefixes) as $prefix) {
            if ($prefix !== '' && strpos($name, $prefix) === 0) { return true; }
        }
        return false;
    }

    public function counts_for_campaign(int $campaign_id): array
    {
        global $wpdb;
        $table = Database::marketing_logs_table();
        $rows = $wpdb->get_results($wpdb->prepare("SELECT status, COUNT(*) AS total FROM {$table} WHERE campaign_id=%d GROUP BY status", $campaign_id), ARRAY_A) ?: [];
        $out = ['pending' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($rows as $row) { $out[(string) $row['status']] = (int) $row['total']; }
        return $out;
    }

    public function get_processable(int $limit): array
    {
        global $wpdb;
        $logs = Database::marketing_logs_table();
        $campaigns = Database::marketing_campaigns_table();
        $limit = max(1, min(50, $limit));
        $sql = "SELECT l.* FROM {$logs} l INNER JOIN {$campaigns} c ON c.id=l.campaign_id WHERE c.status IN ('queued','sending') AND l.status IN ('pending','failed') AND l.attempts < l.max_attempts ORDER BY l.created_at ASC,l.id ASC LIMIT %d";
        return $wpdb->get_results($wpdb->prepare($sql, $limit), ARRAY_A) ?: [];
    }

    public function get_processable_for_campaign(int $campaign_id, int $limit): array
    {
        global $wpdb;
        $logs = Database::marketing_logs_table();
        $campaigns = Database::marketing_campaigns_table();
        $limit = max(1, min(50, $limit));
        return $wpdb->get_results($wpdb->prepare("SELECT l.* FROM {$logs} l INNER JOIN {$campaigns} c ON c.id=l.campaign_id WHERE l.campaign_id=%d AND c.status IN ('queued','sending') AND l.status IN ('pending','failed') AND l.attempts < l.max_attempts ORDER BY l.created_at ASC,l.id ASC LIMIT %d", $campaign_id, $limit), ARRAY_A) ?: [];
    }

    public function mark_sending(int $id): bool
    {
        return $this->update($id, ['status' => 'sending', 'last_try' => current_time('mysql')]);
    }

    public function mark_sent(int $id): bool
    {
        return $this->update($id, ['status' => 'sent', 'sent_at' => current_time('mysql'), 'error_message' => '']);
    }

    public function mark_failed(int $id, string $message, int $attempts, int $max_attempts): bool
    {
        return $this->update($id, [
            'status' => $attempts >= $max_attempts ? 'failed' : 'pending',
            'attempts' => $attempts,
            'max_attempts' => $max_attempts,
            'last_try' => current_time('mysql'),
            'error_message' => $message,
        ]);
    }

    public function has_pending_or_retryable(int $campaign_id): bool
    {
        global $wpdb;
        $table = Database::marketing_logs_table();
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE campaign_id=%d AND status IN ('pending','sending') LIMIT 1", $campaign_id));
    }

    public function last_activity_for_campaign(int $campaign_id): string
    {
        global $wpdb;
        $table = Database::marketing_logs_table();
        return (string) $wpdb->get_var($wpdb->prepare("SELECT MAX(updated_at) FROM {$table} WHERE campaign_id=%d", $campaign_id));
    }

    public function retry_failed_for_campaign(int $campaign_id): int
    {
        global $wpdb;
        $table = Database::marketing_logs_table();
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET status='pending', error_message='', updated_at=%s WHERE campaign_id=%d AND status='failed'",
            current_time('mysql'),
            $campaign_id
        ));
        return is_numeric($updated) ? (int) $updated : 0;
    }

    public function stats_for_campaign(int $campaign_id): array
    {
        $counts = $this->counts_for_campaign($campaign_id);
        $total = array_sum($counts);
        $done = (int) ($counts['sent'] ?? 0) + (int) ($counts['failed'] ?? 0) + (int) ($counts['skipped'] ?? 0);
        $percent = $total > 0 ? (int) floor(($done / $total) * 100) : 0;
        return array_merge($counts, [
            'total' => $total,
            'done' => $done,
            'percent' => max(0, min(100, $percent)),
            'last_activity' => $this->last_activity_for_campaign($campaign_id),
        ]);
    }
}
