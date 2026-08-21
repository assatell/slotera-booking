<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Database;
use Slotera\Application\Services\MarketingOptOutService;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class PrivacyService
{
    public const CRON_HOOK = 'sltr_privacy_retention_cleanup';
    private const RETENTION_BATCH_SIZE = 1000;
    private const RETENTION_MAX_BATCHES = 20;

    private SettingsRepository $settings;

    public function __construct(?SettingsRepository $settings = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
    }

    public static function activate(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public function register_hooks(): void
    {
        add_action(self::CRON_HOOK, [$this, 'run_retention_cleanup']);
        add_filter('wp_privacy_personal_data_exporters', [$this, 'register_exporter']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'register_eraser']);
    }

    public function register_exporter(array $exporters): array
    {
        $exporters['slotera-booking'] = [
            'exporter_friendly_name' => __('Slotera Booking', 'slotera-booking'),
            'callback' => [$this, 'export_personal_data'],
        ];
        return $exporters;
    }

    public function register_eraser(array $erasers): array
    {
        $erasers['slotera-booking'] = [
            'eraser_friendly_name' => __('Slotera Booking', 'slotera-booking'),
            'callback' => [$this, 'erase_personal_data'],
        ];
        return $erasers;
    }

    public function export_personal_data(string $email_address, int $page = 1): array
    {
        global $wpdb;

        $email = sanitize_email($email_address);
        if ($email === '') {
            return ['data' => [], 'done' => true];
        }

        $limit = 50;
        $offset = max(0, ($page - 1) * $limit);
        $bookings_table = Database::bookings_table();
        $bookings = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$bookings_table} WHERE customer_email = %s ORDER BY id ASC LIMIT %d OFFSET %d", $email, $limit, $offset),
            ARRAY_A
        );
        $transactions_table = Database::payment_transactions_table();
        $transactions = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$transactions_table} WHERE customer_email = %s ORDER BY id ASC LIMIT %d OFFSET %d", $email, $limit, $offset),
            ARRAY_A
        );
        $invoices_table = Database::payment_invoices_table();
        $invoices = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$invoices_table} WHERE customer_email = %s ORDER BY id ASC LIMIT %d OFFSET %d", $email, $limit, $offset),
            ARRAY_A
        );

        $data = [];
        $opt_out = (new MarketingOptOutService())->record($email);
        if (!empty($opt_out)) {
            $data[] = [
                'group_id' => 'slotera-marketing',
                'group_label' => __('Slotera marketing', 'slotera-booking'),
                'item_id' => 'slotera-marketing-optout-' . md5($email),
                'data' => [
                    ['name' => __('Email', 'slotera-booking'), 'value' => $email],
                    ['name' => __('Marketing opt-out', 'slotera-booking'), 'value' => __('Yes', 'slotera-booking')],
                    ['name' => __('Unsubscribed at', 'slotera-booking'), 'value' => (string) ($opt_out['unsubscribed_at'] ?? '')],
                ],
            ];
        }

        $consent = (new MarketingConsentService())->record($email);
        if (!empty($consent)) {
            $data[] = [
                'group_id' => 'slotera-marketing',
                'group_label' => __('Slotera marketing', 'slotera-booking'),
                'item_id' => 'slotera-marketing-consent-' . md5($email),
                'data' => [
                    ['name' => __('Email', 'slotera-booking'), 'value' => $email],
                    ['name' => __('Marketing consent', 'slotera-booking'), 'value' => __('Yes', 'slotera-booking')],
                    ['name' => __('Granted at', 'slotera-booking'), 'value' => (string) ($consent['granted_at'] ?? '')],
                    ['name' => __('Consent source', 'slotera-booking'), 'value' => (string) ($consent['source'] ?? '')],
                    ['name' => __('Policy version', 'slotera-booking'), 'value' => (string) ($consent['policy_version'] ?? '')],
                ],
            ];
        }

        foreach ((array) $bookings as $booking) {
            $booking_id = (int) ($booking['id'] ?? 0);
            $item = [
                'group_id' => 'slotera-bookings',
                'group_label' => __('Slotera bookings', 'slotera-booking'),
                'item_id' => 'slotera-booking-' . $booking_id,
                'data' => [
                    ['name' => __('Booking ID', 'slotera-booking'), 'value' => (string) $booking_id],
                    ['name' => __('Customer name', 'slotera-booking'), 'value' => (string) ($booking['customer_name'] ?? '')],
                    ['name' => __('Email', 'slotera-booking'), 'value' => (string) ($booking['customer_email'] ?? '')],
                    ['name' => __('Phone', 'slotera-booking'), 'value' => (string) ($booking['customer_phone'] ?? '')],
                    ['name' => __('Company', 'slotera-booking'), 'value' => (string) ($booking['company'] ?? '')],
                    ['name' => __('Address', 'slotera-booking'), 'value' => trim((string) ($booking['address'] ?? '') . ' ' . (string) ($booking['city'] ?? '') . ' ' . (string) ($booking['state'] ?? ''))],
                    ['name' => __('Booking date', 'slotera-booking'), 'value' => (string) ($booking['booking_date'] ?? '')],
                    ['name' => __('End date', 'slotera-booking'), 'value' => (string) ($booking['end_date'] ?? '')],
                    ['name' => __('Start time', 'slotera-booking'), 'value' => (string) ($booking['start_time'] ?? '')],
                    ['name' => __('End time', 'slotera-booking'), 'value' => (string) ($booking['end_time'] ?? '')],
                    ['name' => __('Booking status', 'slotera-booking'), 'value' => (string) ($booking['status'] ?? '')],
                    ['name' => __('Payment status', 'slotera-booking'), 'value' => (string) ($booking['payment_status'] ?? '')],
                    ['name' => __('Total amount', 'slotera-booking'), 'value' => (string) ($booking['total_amount'] ?? '')],
                    ['name' => __('Notes', 'slotera-booking'), 'value' => (string) ($booking['notes'] ?? '')],
                    ['name' => __('Created at', 'slotera-booking'), 'value' => (string) ($booking['created_at'] ?? '')],
                ],
            ];
            $data[] = $item;
        }

        foreach ((array) $transactions as $transaction) {
            $transaction_id = (int) ($transaction['id'] ?? 0);
            $data[] = [
                'group_id' => 'slotera-payment-transactions',
                'group_label' => __('Slotera payment transactions', 'slotera-booking'),
                'item_id' => 'slotera-payment-transaction-' . $transaction_id,
                'data' => [
                    ['name' => __('Transaction ID', 'slotera-booking'), 'value' => (string) $transaction_id],
                    ['name' => __('Booking ID', 'slotera-booking'), 'value' => (string) ($transaction['booking_id'] ?? '')],
                    ['name' => __('Email', 'slotera-booking'), 'value' => (string) ($transaction['customer_email'] ?? '')],
                    ['name' => __('Gateway', 'slotera-booking'), 'value' => (string) ($transaction['gateway'] ?? '')],
                    ['name' => __('Status', 'slotera-booking'), 'value' => (string) ($transaction['status'] ?? '')],
                    ['name' => __('Amount', 'slotera-booking'), 'value' => (string) ($transaction['amount'] ?? '')],
                    ['name' => __('Currency', 'slotera-booking'), 'value' => (string) ($transaction['currency'] ?? '')],
                    ['name' => __('Description', 'slotera-booking'), 'value' => (string) ($transaction['description'] ?? '')],
                    ['name' => __('Error message', 'slotera-booking'), 'value' => (string) ($transaction['error_message'] ?? '')],
                    ['name' => __('Metadata', 'slotera-booking'), 'value' => (string) ($transaction['metadata_json'] ?? '')],
                    ['name' => __('Created at', 'slotera-booking'), 'value' => (string) ($transaction['created_at'] ?? '')],
                ],
            ];
        }

        foreach ((array) $invoices as $invoice) {
            $invoice_id = (int) ($invoice['id'] ?? 0);
            $data[] = [
                'group_id' => 'slotera-payment-invoices',
                'group_label' => __('Slotera payment invoices', 'slotera-booking'),
                'item_id' => 'slotera-payment-invoice-' . $invoice_id,
                'data' => [
                    ['name' => __('Invoice ID', 'slotera-booking'), 'value' => (string) $invoice_id],
                    ['name' => __('Invoice number', 'slotera-booking'), 'value' => (string) ($invoice['invoice_number'] ?? '')],
                    ['name' => __('Booking ID', 'slotera-booking'), 'value' => (string) ($invoice['booking_id'] ?? '')],
                    ['name' => __('Email', 'slotera-booking'), 'value' => (string) ($invoice['customer_email'] ?? '')],
                    ['name' => __('Customer name', 'slotera-booking'), 'value' => (string) ($invoice['customer_name'] ?? '')],
                    ['name' => __('Status', 'slotera-booking'), 'value' => (string) ($invoice['status'] ?? '')],
                    ['name' => __('Total amount', 'slotera-booking'), 'value' => (string) ($invoice['total'] ?? '')],
                    ['name' => __('Paid', 'slotera-booking'), 'value' => (string) ($invoice['paid'] ?? '')],
                    ['name' => __('Remaining', 'slotera-booking'), 'value' => (string) ($invoice['remaining'] ?? '')],
                    ['name' => __('Notes', 'slotera-booking'), 'value' => (string) ($invoice['notes'] ?? '')],
                    ['name' => __('Metadata', 'slotera-booking'), 'value' => (string) ($invoice['metadata_json'] ?? '')],
                    ['name' => __('Created at', 'slotera-booking'), 'value' => (string) ($invoice['created_at'] ?? '')],
                ],
            ];
        }

        return [
            'data' => $data,
            'done' => count((array) $bookings) < $limit
                && count((array) $transactions) < $limit
                && count((array) $invoices) < $limit,
        ];
    }

    public function erase_personal_data(string $email_address, int $page = 1): array
    {
        global $wpdb;

        $email = sanitize_email($email_address);
        if ($email === '') {
            return ['items_removed' => false, 'items_retained' => false, 'messages' => [], 'done' => true];
        }

        $bookings_table = Database::bookings_table();
        $booking_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$bookings_table} WHERE customer_email = %s ORDER BY id ASC LIMIT 50", $email));
        $items_removed = false;
        $items_retained = false;
        $messages = [];

        foreach ((array) $booking_ids as $booking_id) {
            $id = (int) $booking_id;
            if ($id <= 0) {
                continue;
            }
            $wpdb->update($bookings_table, [
                'user_id' => 0,
                'customer_name' => __('Deleted customer', 'slotera-booking'),
                'customer_email' => 'deleted+' . $id . '@example.invalid',
                'customer_phone' => null,
                'city' => null,
                'state' => null,
                'address' => null,
                'company' => null,
                'notes' => '',
                'cancellation_token' => null,
                'reschedule_token' => null,
                'updated_at' => current_time('mysql'),
            ], ['id' => $id]);
            $items_retained = true;
        }

        $email_queue_table = Database::email_queue_table();
        $deleted_queue = $wpdb->delete($email_queue_table, ['recipient_email' => $email]);
        if ($deleted_queue !== false && $deleted_queue > 0) {
            $items_removed = true;
        }

        $marketing_logs_table = Database::marketing_logs_table();
        $deleted_marketing = $wpdb->delete($marketing_logs_table, ['customer_email' => $email]);
        if ($deleted_marketing !== false && $deleted_marketing > 0) {
            $items_removed = true;
        }

        $consent_service = new MarketingConsentService();
        if ($consent_service->has_consent($email) && $consent_service->revoke($email)) {
            $items_removed = true;
        }

        // A privacy erasure must never remove a do-not-contact preference. Keep
        // a minimal hashed suppression marker so erased contacts cannot become
        // marketable again without a fresh, explicit consent flow.
        $opt_out_service = new MarketingOptOutService();
        $opt_out_service->suppress($email, 'privacy_erasure');
        if ($opt_out_service->is_unsubscribed($email)) {
            $items_retained = true;
        }

        $transactions_table = Database::payment_transactions_table();
        $transaction_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$transactions_table} WHERE customer_email = %s ORDER BY id ASC LIMIT 500",
            $email
        ));
        foreach ((array) $transaction_ids as $transaction_id) {
            $id = (int) $transaction_id;
            if ($id <= 0) { continue; }
            $updated = $wpdb->update($transactions_table, [
                'customer_email' => 'deleted+transaction-' . $id . '@example.invalid',
                'description' => '',
                'error_message' => '',
                'metadata_json' => '',
                'updated_at' => current_time('mysql'),
            ], ['id' => $id]);
            if ($updated !== false) { $items_retained = true; }
        }

        $invoices_table = Database::payment_invoices_table();
        $invoice_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$invoices_table} WHERE customer_email = %s ORDER BY id ASC LIMIT 500",
            $email
        ));
        foreach ((array) $invoice_ids as $invoice_id) {
            $id = (int) $invoice_id;
            if ($id <= 0) { continue; }
            $updated = $wpdb->update($invoices_table, [
                'customer_email' => 'deleted+invoice-' . $id . '@example.invalid',
                'customer_name' => __('Deleted customer', 'slotera-booking'),
                'notes' => '',
                'metadata_json' => '',
                'updated_at' => current_time('mysql'),
            ], ['id' => $id]);
            if ($updated !== false) { $items_retained = true; }
        }

        if ($items_retained) {
            $messages[] = __('Slotera booking and financial records were anonymized where possible and retained for operational/accounting integrity. A minimal marketing suppression marker was retained to respect the customer\'s do-not-contact preference.', 'slotera-booking');
        }

        return [
            'items_removed' => $items_removed,
            'items_retained' => $items_retained,
            'messages' => $messages,
            'done' => count((array) $booking_ids) < 50
                && count((array) $transaction_ids) < 500
                && count((array) $invoice_ids) < 500,
        ];
    }

    public function run_retention_cleanup(): array
    {
        if (!CronResilienceService::acquire(self::CRON_HOOK, 30 * MINUTE_IN_SECONDS)) { return ['skipped' => 'locked']; }
        try {
        global $wpdb;

        $settings = $this->settings->all();
        $stats = ['bookings_anonymized' => 0, 'visitor_events_deleted' => 0, 'activity_logs_deleted' => 0, 'booking_history_deleted' => 0, 'email_queue_deleted' => 0, 'marketing_logs_deleted' => 0, 'webhook_events_deleted' => 0, 'outgoing_webhook_deliveries_deleted' => 0];

        $booking_days = max(0, (int) ($settings['privacy_anonymize_completed_bookings_days'] ?? 0));
        if ($booking_days > 0) {
            $cutoff = gmdate('Y-m-d H:i:s', time() - ($booking_days * DAY_IN_SECONDS));
            $table = Database::bookings_table();
            $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE customer_email NOT LIKE %s AND status IN ('completed','cancelled') AND updated_at < %s LIMIT 500", 'deleted+%@example.invalid', $cutoff));
            foreach ((array) $ids as $id_raw) {
                $id = (int) $id_raw;
                if ($id <= 0) { continue; }
                $updated = $wpdb->update($table, [
                    'user_id' => 0,
                    'customer_name' => __('Deleted customer', 'slotera-booking'),
                    'customer_email' => 'deleted+' . $id . '@example.invalid',
                    'customer_phone' => null,
                    'city' => null,
                    'state' => null,
                    'address' => null,
                    'company' => null,
                    'notes' => '',
                    'cancellation_token' => null,
                    'reschedule_token' => null,
                    'updated_at' => current_time('mysql'),
                ], ['id' => $id]);
                if ($updated !== false) { $stats['bookings_anonymized']++; }
            }
        }

        $stats['visitor_events_deleted'] = $this->delete_older_than(Database::visitor_events_table(), 'created_at', max(1, (int) ($settings['privacy_visitor_analytics_retention_days'] ?? 90)));
        $stats['activity_logs_deleted'] = $this->delete_older_than(Database::activity_log_table(), 'created_at', (int) ($settings['privacy_activity_log_retention_days'] ?? 365));
        $stats['booking_history_deleted'] = $this->delete_older_than(Database::booking_history_table(), 'created_at', (int) ($settings['privacy_booking_history_retention_days'] ?? 1095));
        $stats['email_queue_deleted'] = $this->delete_older_than(Database::email_queue_table(), 'updated_at', (int) ($settings['privacy_email_queue_retention_days'] ?? 90));
        $stats['marketing_logs_deleted'] = $this->delete_older_than(Database::marketing_logs_table(), 'updated_at', (int) ($settings['privacy_marketing_log_retention_days'] ?? 180));
        $stats['webhook_events_deleted'] = $this->delete_older_than(Database::webhook_events_table(), 'updated_at', (int) ($settings['privacy_webhook_event_retention_days'] ?? 180));
        $stats['outgoing_webhook_deliveries_deleted'] = $this->delete_older_than(Database::outgoing_webhook_deliveries_table(), 'updated_at', (int) ($settings['privacy_outgoing_webhook_retention_days'] ?? 180));

        update_option('sltr_privacy_last_cleanup', ['ran_at' => current_time('mysql'), 'stats' => $stats], false);

        CronResilienceService::success(self::CRON_HOOK, $stats);
        return $stats;
        } catch (\Throwable $e) {
            CronResilienceService::failure(self::CRON_HOOK, $e);
            throw $e;
        }
    }

    private function delete_older_than(string $table, string $column, int $days): int
    {
        global $wpdb;
        if ($days <= 0) {
            return 0;
        }
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        $total = 0;
        for ($batch = 0; $batch < self::RETENTION_MAX_BATCHES; $batch++) {
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table} WHERE {$column} < %s LIMIT %d",
                $cutoff,
                self::RETENTION_BATCH_SIZE
            ));
            if (!is_int($deleted)) {
                throw new \RuntimeException('Slotera retention cleanup database delete failed.');
            }
            $total += max(0, $deleted);
            if ($deleted < self::RETENTION_BATCH_SIZE) {
                break;
            }
        }
        return $total;
    }
}
