<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class EmailQueueRepository
{
    public function enqueue(array $data, bool $unique = true): int
    {
        global $wpdb;
        $table = Database::email_queue_table();
        $booking_id = absint($data['booking_id'] ?? 0);
        $scenario = sanitize_key((string) ($data['scenario'] ?? ''));
        $recipient_type = sanitize_key((string) ($data['recipient_type'] ?? 'customer'));
        $recipient_email = sanitize_email((string) ($data['recipient_email'] ?? ''));
        $send_at = sanitize_text_field((string) ($data['send_at'] ?? current_time('mysql')));

        if ($scenario === '' || $recipient_email === '' || !is_email($recipient_email)) {
            return 0;
        }

        if ($unique && $booking_id > 0) {
            $existing = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE booking_id=%d AND scenario=%s AND recipient_type=%s AND recipient_email=%s AND status IN ('pending','processing','sent') LIMIT 1",
                $booking_id,
                $scenario,
                $recipient_type,
                $recipient_email
            ));
            if ($existing > 0) {
                return $existing;
            }
        }

        $now = current_time('mysql');
        $ok = $wpdb->insert($table, [
            'booking_id' => $booking_id,
            'scenario' => $scenario,
            'recipient_type' => $recipient_type,
            'recipient_email' => $recipient_email,
            'subject' => sanitize_text_field((string) ($data['subject'] ?? '')),
            'body' => wp_kses_post((string) ($data['body'] ?? '')),
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => max(1, min(10, absint($data['max_attempts'] ?? 3))),
            'send_at' => $send_at,
            'last_error' => '',
            'payload_json' => wp_json_encode($data['payload'] ?? []),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $ok ? (int) $wpdb->insert_id : 0;
    }

    public function get_due(int $limit = 20): array
    {
        global $wpdb;
        $table = Database::email_queue_table();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status='pending' AND send_at <= %s ORDER BY send_at ASC,id ASC LIMIT %d",
            current_time('mysql'),
            max(1, min(100, $limit))
        ), ARRAY_A) ?: [];
    }

    public function get_by_id(int $id): ?array
    {
        global $wpdb;
        $table = Database::email_queue_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id), ARRAY_A);
        return $row ?: null;
    }

    public function mark_processing(int $id): bool
    {
        return $this->update($id, ['status' => 'processing']);
    }

    public function mark_sent(int $id): bool
    {
        return $this->update($id, [
            'status' => 'sent',
            'sent_at' => current_time('mysql'),
            'last_error' => '',
        ]);
    }

    public function mark_failed(int $id, string $error, int $attempts, int $max_attempts): bool
    {
        $status = $attempts >= $max_attempts ? 'failed' : 'pending';
        $data = [
            'status' => $status,
            'attempts' => $attempts,
            'last_error' => sanitize_textarea_field($error),
        ];

        if ($status === 'pending') {
            $delay_minutes = min(120, max(5, $attempts * 10));
            $data['send_at'] = wp_date('Y-m-d H:i:s', current_time('timestamp') + ($delay_minutes * MINUTE_IN_SECONDS));
        }

        return $this->update($id, $data);
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = Database::email_queue_table();
        $data['updated_at'] = current_time('mysql');
        return $wpdb->update($table, $data, ['id' => $id]) !== false;
    }
}
