<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;
use Slotera\Application\Security\DataRedactor;

if (!defined('ABSPATH')) { exit; }

final class PaymentTransactionRepository
{
    public function create(array $data): int
    {
        global $wpdb;
        $table = Database::payment_transactions_table();
        $now = current_time('mysql');
        $metadata = DataRedactor::payload($data['metadata'] ?? []);

        $ok = $wpdb->insert($table, [
            'booking_id' => absint($data['booking_id'] ?? 0),
            'customer_email' => sanitize_email((string) ($data['customer_email'] ?? '')),
            'gateway' => sanitize_key((string) ($data['gateway'] ?? '')),
            'transaction_type' => sanitize_key((string) ($data['transaction_type'] ?? 'payment')),
            'status' => sanitize_key((string) ($data['status'] ?? 'pending')),
            'amount' => (float) ($data['amount'] ?? 0),
            'currency' => strtoupper(sanitize_key((string) ($data['currency'] ?? 'EUR'))),
            'external_id' => sanitize_text_field((string) ($data['external_id'] ?? '')),
            'external_parent_id' => sanitize_text_field((string) ($data['external_parent_id'] ?? '')),
            'mode' => sanitize_key((string) ($data['mode'] ?? 'test')),
            'description' => sanitize_textarea_field(DataRedactor::text((string) ($data['description'] ?? ''))),
            'error_message' => sanitize_textarea_field(DataRedactor::text((string) ($data['error_message'] ?? ''))),
            'metadata_json' => wp_json_encode($metadata),
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%d','%s','%s','%s','%s','%f','%s','%s','%s','%s','%s','%s','%s','%s','%s']);

        return $ok ? (int) $wpdb->insert_id : 0;
    }

    public function upsert_by_external_id(array $data): int
    {
        $gateway = sanitize_key((string) ($data['gateway'] ?? ''));
        $external_id = sanitize_text_field((string) ($data['external_id'] ?? ''));
        if ($gateway === '' || $external_id === '') {
            return $this->create($data);
        }

        $existing = $this->get_by_external_id($gateway, $external_id);
        if (!$existing) {
            return $this->create($data);
        }

        $this->update((int) $existing['id'], $data);
        return (int) $existing['id'];
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = Database::payment_transactions_table();
        $allowed = [
            'booking_id' => '%d', 'customer_email' => '%s', 'gateway' => '%s', 'transaction_type' => '%s',
            'status' => '%s', 'amount' => '%f', 'currency' => '%s', 'external_id' => '%s',
            'external_parent_id' => '%s', 'mode' => '%s', 'description' => '%s', 'error_message' => '%s',
            'metadata_json' => '%s', 'updated_at' => '%s',
        ];
        $row = [];
        $formats = [];
        foreach ($allowed as $key => $format) {
            if (!array_key_exists($key, $data)) { continue; }
            $value = $data[$key];
            if ($key === 'booking_id') { $value = absint($value); }
            elseif ($key === 'customer_email') { $value = sanitize_email((string) $value); }
            elseif (in_array($key, ['gateway','transaction_type','status','mode'], true)) { $value = sanitize_key((string) $value); }
            elseif ($key === 'currency') { $value = strtoupper(sanitize_key((string) $value)); }
            elseif (in_array($key, ['external_id','external_parent_id'], true)) { $value = sanitize_text_field((string) $value); }
            elseif (in_array($key, ['description','error_message'], true)) { $value = sanitize_textarea_field(DataRedactor::text((string) $value)); }
            elseif ($key === 'metadata_json') { $value = (string) $value; }
            $row[$key] = $value;
            $formats[] = $format;
        }
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $row['metadata_json'] = wp_json_encode(DataRedactor::payload($data['metadata']));
            $formats[] = '%s';
        }
        $row['updated_at'] = current_time('mysql');
        $formats[] = '%s';

        return $wpdb->update($table, $row, ['id' => $id], $formats, ['%d']) !== false;
    }


    public function get(int $id): ?array
    {
        global $wpdb;
        $table = Database::payment_transactions_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id), ARRAY_A);
        return $row ?: null;
    }

    public function refunded_amount_for_parent(string $gateway, string $external_parent_id): float
    {
        global $wpdb;
        $table = Database::payment_transactions_table();
        $gateway = sanitize_key($gateway);
        $external_parent_id = sanitize_text_field($external_parent_id);
        if ($gateway === '' || $external_parent_id === '') { return 0.0; }
        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM {$table} WHERE gateway=%s AND transaction_type='refund' AND status='refunded' AND external_parent_id=%s",
            $gateway,
            $external_parent_id
        ));
    }

    public function get_by_external_id(string $gateway, string $external_id): ?array
    {
        global $wpdb;
        $table = Database::payment_transactions_table();
        $gateway = sanitize_key($gateway);
        $external_id = sanitize_text_field($external_id);
        if ($gateway === '' || $external_id === '') { return null; }
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE gateway=%s AND external_id=%s LIMIT 1", $gateway, $external_id), ARRAY_A);
        return $row ?: null;
    }

    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        global $wpdb;
        $table = Database::payment_transactions_table();
        [$where, $params] = $this->where($filters);
        $params[] = max(1, min(200, $limit));
        $params[] = max(0, $offset);
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} {$where} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d", $params), ARRAY_A) ?: [];
    }

    public function count(array $filters = []): int
    {
        global $wpdb;
        $table = Database::payment_transactions_table();
        [$where, $params] = $this->where($filters);
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        return $params ? (int) $wpdb->get_var($wpdb->prepare($sql, $params)) : (int) $wpdb->get_var($sql);
    }

    public function totals(): array
    {
        global $wpdb;
        $table = Database::payment_transactions_table();
        $rows = $wpdb->get_results("SELECT status, currency, SUM(amount) AS total, COUNT(*) AS count FROM {$table} GROUP BY status,currency", ARRAY_A) ?: [];
        return $rows;
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
        $booking_id = absint($filters['booking_id'] ?? 0);
        if ($booking_id > 0) { $where[] = 'booking_id=%d'; $params[] = $booking_id; }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(customer_email LIKE %s OR external_id LIKE %s OR description LIKE %s OR error_message LIKE %s)';
            array_push($params, $like, $like, $like, $like);
        }
        return [empty($where) ? '' : 'WHERE ' . implode(' AND ', $where), $params];
    }
}
