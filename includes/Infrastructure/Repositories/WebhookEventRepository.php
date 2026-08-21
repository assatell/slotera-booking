<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Application\Security\DataRedactor;
use Slotera\Core\Database;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

/**
 * Atomically claims incoming gateway events before payment side effects run.
 */
final class WebhookEventRepository
{
    /**
     * @return int|WP_Error Database id when claimed, zero for an already claimed event.
     */
    public function claim(string $gateway, string $event_id, array $payload, int $booking_id = 0)
    {
        global $wpdb;

        $table = Database::webhook_events_table();
        $gateway = sanitize_key($gateway);
        $event_id = sanitize_text_field($event_id);
        $payload_json = wp_json_encode(DataRedactor::payload($payload));
        if (!is_string($payload_json)) { $payload_json = '{}'; }

        $fingerprint = hash('sha256', $gateway . '|' . $event_id . '|' . $payload_json);
        $stored_event_id = substr($gateway . ':' . ($event_id !== '' ? $event_id : 'sha256-' . $fingerprint), 0, 191);
        $now = current_time('mysql');

        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} (gateway,event_id,fingerprint,booking_id,status,payload_json,error_message,processed_at,created_at,updated_at) VALUES (%s,%s,%s,%d,'processing',%s,'',NULL,%s,%s)",
            $gateway,
            $stored_event_id,
            $fingerprint,
            absint($booking_id),
            $payload_json,
            $now,
            $now
        ));

        if ($inserted === false) {
            return new WP_Error('sltr_webhook_claim_failed', 'Webhook event could not be claimed.');
        }
        if ($inserted === 1) {
            return (int) $wpdb->insert_id;
        }

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id,status,updated_at FROM {$table} WHERE event_id=%s OR fingerprint=%s ORDER BY id ASC LIMIT 1",
            $stored_event_id,
            $fingerprint
        ), ARRAY_A);
        if (!$existing) {
            return new WP_Error('sltr_webhook_claim_missing', 'Existing webhook event could not be loaded.');
        }

        // Failed attempts may be retried. A stale processing claim may be
        // reclaimed after five minutes if the original request was interrupted.
        $cutoff = date('Y-m-d H:i:s', current_time('timestamp') - 300);
        $reclaimed = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET status='processing',booking_id=%d,payload_json=%s,error_message='',updated_at=%s WHERE id=%d AND (status IN ('received','failed') OR (status='processing' AND updated_at<%s))",
            absint($booking_id),
            $payload_json,
            $now,
            absint($existing['id'] ?? 0),
            $cutoff
        ));
        if ($reclaimed === false) {
            return new WP_Error('sltr_webhook_reclaim_failed', 'Webhook event could not be reclaimed.');
        }

        return $reclaimed === 1 ? absint($existing['id'] ?? 0) : 0;
    }

    public function mark_processed(int $id): bool
    {
        global $wpdb;
        $now = current_time('mysql');
        return $wpdb->update(Database::webhook_events_table(), [
            'status' => 'processed',
            'error_message' => '',
            'processed_at' => $now,
            'updated_at' => $now,
        ], ['id' => $id], ['%s','%s','%s','%s'], ['%d']) !== false;
    }

    public function mark_failed(int $id, string $message): bool
    {
        global $wpdb;
        return $wpdb->update(Database::webhook_events_table(), [
            'status' => 'failed',
            'error_message' => sanitize_textarea_field(DataRedactor::text($message)),
            'updated_at' => current_time('mysql'),
        ], ['id' => $id], ['%s','%s','%s'], ['%d']) !== false;
    }
}
