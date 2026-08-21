<?php
declare(strict_types=1);

namespace Slotera\Core;

if (!defined('ABSPATH')) { exit; }

/**
 * Batched maintenance task for rebuilding active booking slot hashes.
 */
final class ActiveSlotHashBackfill
{
    private const OPTION = 'sltr_active_slot_hash_backfill';
    private const BATCH_SIZE = 250;

    public static function register_hooks(): void
    {
        add_action('sltr_active_slot_hash_backfill_batch', [self::class, 'run_batch']);
        add_action('admin_notices', [self::class, 'print_admin_notice']);
        add_action('wp_ajax_sltr_active_slot_hash_backfill_batch', [self::class, 'ajax_run_batch']);
    }

    public static function queue(string $source): void
    {
        $state = (array) get_option(self::OPTION, []);
        if (($state['status'] ?? '') === 'complete') {
            return;
        }

        $pending = self::count_pending_rows();
        if ($pending <= 0) {
            $state = array_merge([
                'status' => 'complete',
                'cursor' => 0,
                'processed' => absint($state['processed'] ?? 0),
                'pending' => 0,
                'source' => $source,
                'started_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ], $state);
            $state['status'] = 'complete';
            $state['pending'] = 0;
            $state['updated_at'] = current_time('mysql');
            update_option(self::OPTION, $state, false);
            wp_clear_scheduled_hook('sltr_active_slot_hash_backfill_batch');
            return;
        }

        $state = array_merge([
            'status' => 'queued',
            'cursor' => 0,
            'processed' => 0,
            'pending' => $pending,
            'source' => $source,
            'started_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ], $state);
        $state['pending'] = $pending;
        update_option(self::OPTION, $state, false);
        if (!wp_next_scheduled('sltr_active_slot_hash_backfill_batch')) {
            wp_schedule_single_event(time() + 60, 'sltr_active_slot_hash_backfill_batch');
        }
    }

    public static function run_batch(): void
    {
        global $wpdb;
        $state = (array) get_option(self::OPTION, []);
        if (($state['status'] ?? '') === 'complete') {
            return;
        }

        $bookings = Database::bookings_table();
        $packages = Database::packages_table();
        $cursor = absint($state['cursor'] ?? 0);
        $limit = (int) apply_filters('sltr_active_slot_hash_backfill_batch_size', self::BATCH_SIZE);
        $limit = max(25, min(1000, $limit));

        $rows = $wpdb->get_results($wpdb->prepare("SELECT b.id, b.package_id, b.resource_id, b.staff_id, b.booking_date, b.end_date, b.start_time, b.end_time
            FROM {$bookings} b
            INNER JOIN {$packages} p ON p.id = b.package_id
            WHERE b.id > %d
              AND b.status IN ('confirmed')
              AND COALESCE(p.max_bookings_per_slot, 1) <= 1
            ORDER BY b.id ASC
            LIMIT %d", $cursor, $limit), ARRAY_A);

        if (!$rows) {
            Migrator::maybe_add_index(Database::bookings_table(), 'active_slot_hash_unique', 'UNIQUE KEY active_slot_hash_unique (active_slot_hash)');
            $state['status'] = 'complete';
            $state['pending'] = 0;
            $state['updated_at'] = current_time('mysql');
            update_option(self::OPTION, $state, false);
            wp_clear_scheduled_hook('sltr_active_slot_hash_backfill_batch');
            return;
        }

        foreach ((array) $rows as $row) {
            $id = absint($row['id'] ?? 0);
            if ($id <= 0) { continue; }
            $hash = self::hash_from_row($row);
            $existing_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$bookings} WHERE active_slot_hash=%s AND id<>%d LIMIT 1", $hash, $id));
            if ($existing_id) {
                $wpdb->update($bookings, ['active_slot_hash' => null], ['id' => $id], ['%s'], ['%d']);
            } else {
                $wpdb->update($bookings, ['active_slot_hash' => $hash], ['id' => $id], ['%s'], ['%d']);
            }
            $cursor = max($cursor, $id);
            $state['processed'] = absint($state['processed'] ?? 0) + 1;
        }

        $state['status'] = 'running';
        $state['cursor'] = $cursor;
        $state['pending'] = self::count_pending_rows($cursor);
        $state['updated_at'] = current_time('mysql');
        update_option(self::OPTION, $state, false);

        if (!wp_next_scheduled('sltr_active_slot_hash_backfill_batch')) {
            wp_schedule_single_event(time() + 60, 'sltr_active_slot_hash_backfill_batch');
        }
    }

    public static function print_admin_notice(): void
    {
        if (!current_user_can(Capabilities::MANAGE_TOOLS)) { return; }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id = $screen ? (string) $screen->id : '';
        if ($screen_id !== '' && strpos($screen_id, 'slotera') === false && $screen_id !== 'plugins') { return; }
        $state = (array) get_option(self::OPTION, []);
        if (!$state || ($state['status'] ?? '') === 'complete') { return; }
        $pending = isset($state['pending']) ? absint($state['pending']) : self::count_pending_rows(absint($state['cursor'] ?? 0));
        if ($pending <= 0 && absint($state['processed'] ?? 0) === 0) {
            $state['status'] = 'complete';
            $state['pending'] = 0;
            $state['updated_at'] = current_time('mysql');
            update_option(self::OPTION, $state, false);
            wp_clear_scheduled_hook('sltr_active_slot_hash_backfill_batch');
            return;
        }
        $nonce = wp_create_nonce('sltr_active_slot_hash_backfill_batch');
        $processed = absint($state['processed'] ?? 0);
        echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Slotera Booking database upgrade is running.', 'slotera-booking') . '</strong> ' . esc_html__('Booking slot hashes are being rebuilt in small batches to avoid locking large tables.', 'slotera-booking') . ' ' . esc_html(sprintf(__('Processed rows: %d.', 'slotera-booking'), $processed)) . '</p><p><button type="button" class="button" id="sltr-run-hash-backfill" data-nonce="' . esc_attr($nonce) . '">' . esc_html__('Run next batch now', 'slotera-booking') . '</button></p><script>jQuery(function($){$(document).on(\'click\',\'#sltr-run-hash-backfill\',function(){var b=$(this);b.prop(\'disabled\',true);$.post(ajaxurl,{action:\'sltr_active_slot_hash_backfill_batch\',_ajax_nonce:b.data(\'nonce\')}).always(function(){window.location.reload();});});});</script></div>';
    }

    public static function ajax_run_batch(): void
    {
        if (!current_user_can(Capabilities::MANAGE_TOOLS)) { wp_send_json_error(['message' => 'forbidden'], 403); }
        check_ajax_referer('sltr_active_slot_hash_backfill_batch');
        self::run_batch();
        wp_send_json_success((array) get_option(self::OPTION, []));
    }

    private static function count_pending_rows(int $cursor = 0): int
    {
        global $wpdb;

        $bookings = Database::bookings_table();
        $packages = Database::packages_table();

        $bookings_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $bookings));
        $packages_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $packages));
        if ($bookings_exists !== $bookings || $packages_exists !== $packages) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*)
            FROM {$bookings} b
            INNER JOIN {$packages} p ON p.id = b.package_id
            WHERE b.id > %d
              AND b.status IN ('confirmed')
              AND COALESCE(p.max_bookings_per_slot, 1) <= 1
              AND (b.active_slot_hash IS NULL OR b.active_slot_hash = '')", $cursor));
    }

    private static function hash_from_row(array $row): string
    {
        return hash('sha256', implode('|', [
            (int) ($row['package_id'] ?? 0),
            (string) ($row['booking_date'] ?? ''),
            (string) ($row['end_date'] ?? ''),
            (string) ($row['start_time'] ?? ''),
            (string) ($row['end_time'] ?? ''),
            (int) ($row['resource_id'] ?? 0),
            (int) ($row['staff_id'] ?? 0),
        ]));
    }
}
