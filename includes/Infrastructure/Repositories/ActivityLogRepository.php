<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;
use Slotera\Application\Security\DataRedactor;

if (!defined('ABSPATH')) { exit; }

final class ActivityLogRepository
{
    public function create(array $d): int
    {
        global $wpdb;
        $table = Database::activity_log_table();
        $payload = DataRedactor::payload($d['payload'] ?? []);
        $gateway = sanitize_key((string) ($d['gateway'] ?? ($payload['gateway'] ?? ($payload['payment_gateway'] ?? ''))));
        $error = sanitize_textarea_field(DataRedactor::text((string) ($d['error_message'] ?? ($payload['error'] ?? ''))));

        $ok = $wpdb->insert($table, [
            'object_type' => sanitize_key((string) ($d['object_type'] ?? 'system')),
            'object_id' => (int) ($d['object_id'] ?? 0),
            'event' => sanitize_key((string) ($d['event'] ?? 'unknown')),
            'actor_type' => sanitize_key((string) ($d['actor_type'] ?? 'system')),
            'actor_id' => (int) ($d['actor_id'] ?? 0),
            'status' => sanitize_key((string) ($d['status'] ?? 'info')),
            'gateway' => $gateway,
            'message' => sanitize_text_field(DataRedactor::text((string) ($d['message'] ?? ''))),
            'error_message' => $error,
            'payload_json' => wp_json_encode($payload),
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => current_time('mysql'),
        ]);

        return $ok ? (int) $wpdb->insert_id : 0;
    }

    public function recent(int $limit = 10, int $offset = 0): array
    {
        return $this->search([], $limit, $offset);
    }

    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        global $wpdb;
        $table = Database::activity_log_table();
        [$where, $params] = $this->where($filters);
        $params[] = max(1, min(200, $limit));
        $params[] = max(0, $offset);
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} {$where} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d", $params), ARRAY_A) ?: [];
    }

    public function count(array $filters = []): int
    {
        global $wpdb;
        $table = Database::activity_log_table();
        [$where, $params] = $this->where($filters);
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        return !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($sql, $params)) : (int) $wpdb->get_var($sql);
    }

    public function get_by_object(string $type, int $id): array
    {
        return $this->search(['object_type' => $type, 'object_id' => $id], 100, 0);
    }

    public function get_by_events(array $events, int $limit = 10, int $offset = 0): array
    {
        if (empty($events)) { return []; }
        return $this->search(['events' => $events], $limit, $offset);
    }

    public function get_errors(int $limit = 10): array
    {
        return $this->search(['has_error' => 1], $limit, 0);
    }

    public function gateways(): array
    {
        global $wpdb;
        $table = Database::activity_log_table();
        $rows = $wpdb->get_col("SELECT DISTINCT gateway FROM {$table} WHERE gateway <> '' ORDER BY gateway ASC");
        return array_values(array_filter(array_map('sanitize_key', $rows ?: [])));
    }

    private function where(array $filters): array
    {
        global $wpdb;
        $where = [];
        $params = [];

        $status = sanitize_key((string) ($filters['status'] ?? ''));
        if ($status !== '' && $status !== 'all') { $where[] = 'status=%s'; $params[] = $status; }

        $gateway = sanitize_key((string) ($filters['gateway'] ?? ''));
        if ($gateway !== '' && $gateway !== 'all') { $where[] = 'gateway=%s'; $params[] = $gateway; }

        $object_type = sanitize_key((string) ($filters['object_type'] ?? ''));
        if ($object_type !== '') { $where[] = 'object_type=%s'; $params[] = $object_type; }

        $object_id = absint($filters['object_id'] ?? 0);
        if ($object_id > 0) { $where[] = 'object_id=%d'; $params[] = $object_id; }

        if (!empty($filters['has_error']) || sanitize_key((string) ($filters['error'] ?? '')) === '1') {
            $where[] = "(status='error' OR error_message <> '')";
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(event LIKE %s OR message LIKE %s OR error_message LIKE %s OR payload_json LIKE %s)';
            array_push($params, $like, $like, $like, $like);
        }

        if (!empty($filters['events']) && is_array($filters['events'])) {
            $events = array_values(array_filter(array_map('sanitize_key', $filters['events'])));
            if (!empty($events)) {
                $where[] = 'event IN (' . implode(',', array_fill(0, count($events), '%s')) . ')';
                foreach ($events as $event) { $params[] = $event; }
            }
        }

        return [empty($where) ? '' : 'WHERE ' . implode(' AND ', $where), $params];
    }
}
