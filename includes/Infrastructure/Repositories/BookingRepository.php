<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class BookingRepository
{
    /** @return string[] */
    private function active_statuses(): array
    {
        return function_exists('sltr_active_booking_statuses') ? \sltr_active_booking_statuses() : ['confirmed'];
    }

    /**
     * @return array{0:string,1:string[]}
     */
    private function active_statuses_sql(): array
    {
        $statuses = array_values(array_filter(array_map('sanitize_key', $this->active_statuses())));
        if ($statuses === []) {
            $statuses = ['confirmed'];
        }
        return [implode(',', array_fill(0, count($statuses), '%s')), $statuses];
    }


    /**
     * Explicit allowlist for booking updates. Unknown keys are ignored before SQL is built.
     *
     * @var array<string,string>
     */
    private const UPDATE_FORMATS = [
        'user_id' => '%d',
        'package_id' => '%d',
        'resource_id' => '%d',
        'staff_id' => '%d',
        'customer_name' => '%s',
        'customer_email' => '%s',
        'customer_phone' => '%s',
        'booking_locale' => '%s',
        'city' => '%s',
        'state' => '%s',
        'address' => '%s',
        'company' => '%s',
        'booking_date' => '%s',
        'end_date' => '%s',
        'start_time' => '%s',
        'end_time' => '%s',
        'status' => '%s',
        'payment_status' => '%s',
        'payment_gateway' => '%s',
        'external_payment_id' => '%s',
        'payment_redirect_url' => '%s',
        'paid_at' => '%s',
        'refunded_at' => '%s',
        'total_amount' => '%f',
        'gross_amount' => '%f',
        'amount_due_now' => '%f',
        'paid_amount' => '%f',
        'remaining_amount' => '%f',
        'deposit_amount' => '%f',
        'extras_amount' => '%f',
        'selected_extras_json' => '%s',
        'pricing_mode' => '%s',
        'payment_choice' => '%s',
        'payment_policy_snapshot_json' => '%s',
        'base_amount' => '%f',
        'package_discount_amount' => '%f',
        'pricing_adjustment_amount' => '%f',
        'pricing_adjustment_label' => '%s',
        'coupon_id' => '%d',
        'coupon_code' => '%s',
        'coupon_discount_amount' => '%f',
        'tax_amount' => '%f',
        'coupon_usage_recorded' => '%d',
        'notes' => '%s',
        'source' => '%s',
        'cancellation_token' => '%s',
        'reschedule_token' => '%s',
        'active_slot_hash' => '%s',
        'cancelled_at' => '%s',
        'completed_at' => '%s',
        'updated_at' => '%s',
    ];

    public function get_by_id(int $id): ?array
    {
        global $wpdb;
        $table = Database::bookings_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id), ARRAY_A);
        return $row ?: null;
    }

    public function get_by_cancellation_token(string $token): ?array
    {
        global $wpdb;
        $table = Database::bookings_table();
        $token = sanitize_text_field($token);
        if ($token === '') { return null; }
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE cancellation_token=%s LIMIT 1", $token), ARRAY_A);
        return $row ?: null;
    }


    public function get_by_external_payment_id(string $external_id): ?array
    {
        global $wpdb;
        $table = Database::bookings_table();
        $external_id = sanitize_text_field($external_id);
        if ($external_id === '') { return null; }
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE external_payment_id=%s LIMIT 1", $external_id), ARRAY_A);
        return $row ?: null;
    }

    public function get_by_reschedule_token(string $token): ?array
    {
        global $wpdb;
        $table = Database::bookings_table();
        $token = sanitize_text_field($token);
        if ($token === '') { return null; }
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE reschedule_token=%s LIMIT 1", $token), ARRAY_A);
        return $row ?: null;
    }

    public function token_exists(string $column, string $token): bool
    {
        global $wpdb;
        $table = Database::bookings_table();
        $column = $column === 'reschedule_token' ? 'reschedule_token' : 'cancellation_token';
        $token = sanitize_text_field($token);
        if ($token === '') { return false; }
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE {$column}=%s LIMIT 1", $token));
    }

    public function last_error_was_duplicate_token(): bool
    {
        global $wpdb;
        $error = strtolower((string) $wpdb->last_error);
        return $error !== ''
            && strpos($error, 'duplicate') !== false
            && (strpos($error, 'cancellation_token') !== false || strpos($error, 'reschedule_token') !== false || strpos($error, 'token_unique') !== false);
    }


    public function last_error_was_duplicate_active_slot(): bool
    {
        global $wpdb;
        $error = strtolower((string) $wpdb->last_error);
        return $error !== ''
            && strpos($error, 'duplicate') !== false
            && strpos($error, 'active_slot_hash_unique') !== false;
    }

    private function active_slot_hash_from_data(array $data): ?string
    {
        $status = sanitize_key((string) ($data['status'] ?? 'confirmed'));
        if (!in_array($status, $this->active_statuses(), true)) {
            return null;
        }

        $package_id = (int) ($data['package_id'] ?? 0);
        if ($package_id <= 0 || !$this->package_is_single_capacity($package_id)) {
            return null;
        }

        // Scheduled Events enforce capacity through Event inventory. Their package-level
        // max_bookings_per_slot value must not create a one-booking unique slot lock.
        if (!empty($data['resource_id']) && $this->package_uses_scheduled_events($package_id)) {
            return null;
        }

        $date = sanitize_text_field((string) ($data['booking_date'] ?? ''));
        $end_date = sanitize_text_field((string) ($data['end_date'] ?? ''));
        $start = sanitize_text_field((string) ($data['start_time'] ?? ''));
        $end = sanitize_text_field((string) ($data['end_time'] ?? ''));
        if ($date === '' || $start === '' || $end === '') {
            return null;
        }

        return hash('sha256', implode('|', [
            $package_id,
            $date,
            $end_date,
            $start,
            $end,
            (int) ($data['resource_id'] ?? 0),
            (int) ($data['staff_id'] ?? 0),
        ]));
    }

    private function package_is_single_capacity(int $package_id): bool
    {
        global $wpdb;
        $table = Database::packages_table();
        $max = (int) $wpdb->get_var($wpdb->prepare("SELECT max_bookings_per_slot FROM {$table} WHERE id=%d LIMIT 1", $package_id));
        return $max <= 1;
    }

    private function package_uses_scheduled_events(int $package_id): bool
    {
        global $wpdb;
        $table = Database::packages_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT booking_mode, mode_configs_json FROM {$table} WHERE id=%d LIMIT 1", $package_id), ARRAY_A);
        if (!is_array($row) || sanitize_key((string) ($row['booking_mode'] ?? '')) !== 'date_range_inventory') {
            return false;
        }
        $configs = json_decode((string) ($row['mode_configs_json'] ?? ''), true);
        return is_array($configs)
            && sanitize_key((string) ($configs['date_range_inventory']['date_flow'] ?? '')) === 'admin_scheduled';
    }

    public function get_for_date(int $package_id, string $date, int $resource_id = 0, int $staff_id = 0): array
    {
        global $wpdb;
        $table = Database::bookings_table();
        [$status_placeholders, $status_args] = $this->active_statuses_sql();
        $where = "package_id=%d AND booking_date=%s AND status IN ({$status_placeholders})";
        $args = array_merge([$package_id, $date], $status_args);
        if ($resource_id > 0) { $where .= " AND resource_id=%d"; $args[] = $resource_id; }
        if ($staff_id > 0) { $where .= " AND staff_id=%d"; $args[] = $staff_id; }
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE {$where} ORDER BY start_time ASC", $args),
            ARRAY_A
        ) ?: [];
    }


    public function get_for_time_range(int $package_id, string $start_date, string $end_date, int $resource_id = 0, int $staff_id = 0): array
    {
        global $wpdb;
        $table = Database::bookings_table();
        [$status_placeholders, $status_args] = $this->active_statuses_sql();
        $where = "package_id=%d AND booking_date <= %s AND COALESCE(NULLIF(end_date,''), booking_date) >= %s AND status IN ({$status_placeholders})";
        $args = array_merge([$package_id, $end_date, $start_date], $status_args);
        if ($resource_id > 0) { $where .= " AND resource_id=%d"; $args[] = $resource_id; }
        if ($staff_id > 0) { $where .= " AND staff_id=%d"; $args[] = $staff_id; }
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE {$where} ORDER BY booking_date ASC, start_time ASC", $args),
            ARRAY_A
        ) ?: [];
    }

    public function get_overlapping_date_range(int $package_id, int $resource_id, string $start_date, string $end_date): array
    {
        global $wpdb;
        $table = Database::bookings_table();
        if ($package_id <= 0 || $resource_id <= 0 || $start_date === '' || $end_date === '') { return []; }
        [$status_placeholders, $status_args] = $this->active_statuses_sql();
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE package_id=%d AND resource_id=%d AND status IN ({$status_placeholders}) AND booking_date < %s AND COALESCE(end_date, booking_date) > %s ORDER BY booking_date ASC",
                array_merge([$package_id, $resource_id], $status_args, [$end_date, $start_date])
            ),
            ARRAY_A
        ) ?: [];
    }



    public function get_by_customer_email(string $email, int $limit = 50, int $offset = 0): array
    {
        global $wpdb;
        $table = Database::bookings_table();
        $email = sanitize_email($email);
        if ($email === '') { return []; }
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE customer_email=%s ORDER BY booking_date DESC,start_time DESC,created_at DESC LIMIT %d OFFSET %d",
                $email,
                $limit,
                $offset
            ),
            ARRAY_A
        ) ?: [];
    }

    public function customer_email_exists(string $email): bool
    {
        global $wpdb;
        $table = Database::bookings_table();
        $email = sanitize_email($email);
        if ($email === '') { return false; }
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE customer_email=%s LIMIT 1", $email));
    }
    public function get_upcoming(int $limit = 10, int $offset = 0): array
    {
        global $wpdb;
        $table = Database::bookings_table();
        [$status_placeholders, $status_args] = $this->active_statuses_sql();
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE booking_date >= %s AND status IN ({$status_placeholders}) ORDER BY booking_date ASC,start_time ASC LIMIT %d OFFSET %d", array_merge([current_time('Y-m-d')], $status_args, [$limit, $offset])),
            ARRAY_A
        ) ?: [];
    }


    private function booking_locale(array $data): string
    {
        $locale = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($data['booking_locale'] ?? '')) ?: '';
        if ($locale === '' && class_exists('Slotera\Application\Services\TranslationService')) {
            $locale = (new \Slotera\Application\Services\TranslationService())->locale_for_group('frontend');
        }
        if ($locale === '') {
            $locale = function_exists('get_locale') ? (string) get_locale() : 'en_US';
        }
        return substr(str_replace('-', '_', $locale), 0, 20);
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = Database::bookings_table();
        $now = current_time('mysql');

        $ok = $wpdb->insert($table, [
            'user_id' => (int) ($data['user_id'] ?? 0),
            'package_id' => (int) $data['package_id'],
            'resource_id' => (int) ($data['resource_id'] ?? 0),
            'staff_id' => (int) ($data['staff_id'] ?? 0),
            'customer_name' => sanitize_text_field((string) ($data['customer_name'] ?? '')),
            'customer_email' => sanitize_email((string) ($data['customer_email'] ?? '')),
            'customer_phone' => sanitize_text_field((string) ($data['customer_phone'] ?? '')),
            'booking_locale' => $this->booking_locale($data),
            'city' => sanitize_text_field((string) ($data['city'] ?? '')),
            'state' => sanitize_text_field((string) ($data['state'] ?? '')),
            'address' => sanitize_text_field((string) ($data['address'] ?? '')),
            'company' => sanitize_text_field((string) ($data['company'] ?? '')),
            'booking_date' => sanitize_text_field((string) ($data['booking_date'] ?? '')),
            'end_date' => sanitize_text_field((string) ($data['end_date'] ?? '')),
            'start_time' => sanitize_text_field((string) ($data['start_time'] ?? '')),
            'end_time' => sanitize_text_field((string) ($data['end_time'] ?? '')),
            'status' => sanitize_key((string) ($data['status'] ?? 'confirmed')),
            'payment_status' => sanitize_key((string) ($data['payment_status'] ?? 'unpaid')),
            'payment_gateway' => sanitize_key((string) ($data['payment_gateway'] ?? '')),
            'external_payment_id' => sanitize_text_field((string) ($data['external_payment_id'] ?? '')),
            'payment_redirect_url' => esc_url_raw((string) ($data['payment_redirect_url'] ?? '')),
            'total_amount' => (float) ($data['total_amount'] ?? 0),
            'gross_amount' => (float) ($data['gross_amount'] ?? ($data['total_amount'] ?? 0)),
            'amount_due_now' => (float) ($data['amount_due_now'] ?? ($data['total_amount'] ?? 0)),
            'paid_amount' => (float) ($data['paid_amount'] ?? 0),
            'remaining_amount' => (float) ($data['remaining_amount'] ?? 0),
            'deposit_amount' => (float) ($data['deposit_amount'] ?? 0),
            'extras_amount' => (float) ($data['extras_amount'] ?? 0),
            'selected_extras_json' => wp_json_encode($data['selected_extras'] ?? json_decode((string) ($data['selected_extras_json'] ?? '[]'), true) ?: []),
            'pricing_mode' => sanitize_key((string) ($data['pricing_mode'] ?? 'fixed')),
            'payment_choice' => sanitize_key((string) ($data['payment_choice'] ?? '')),
            'payment_policy_snapshot_json' => wp_json_encode($data['payment_policy_snapshot'] ?? json_decode((string) ($data['payment_policy_snapshot_json'] ?? '[]'), true) ?: []),
            'base_amount' => (float) ($data['base_amount'] ?? 0),
            'package_discount_amount' => (float) ($data['package_discount_amount'] ?? 0),
            'pricing_adjustment_amount' => (float) ($data['pricing_adjustment_amount'] ?? 0),
            'pricing_adjustment_label' => sanitize_text_field((string) ($data['pricing_adjustment_label'] ?? '')),
            'coupon_id' => (int) ($data['coupon_id'] ?? 0),
            'coupon_code' => sanitize_text_field((string) ($data['coupon_code'] ?? '')),
            'coupon_discount_amount' => (float) ($data['coupon_discount_amount'] ?? 0),
            'tax_amount' => (float) ($data['tax_amount'] ?? 0),
            'coupon_usage_recorded' => !empty($data['coupon_usage_recorded']) ? 1 : 0,
            'notes' => wp_kses_post((string) ($data['notes'] ?? '')),
            'source' => sanitize_key((string) ($data['source'] ?? 'frontend')),
            'cancellation_token' => sanitize_text_field((string) ($data['cancellation_token'] ?? '')),
            'reschedule_token' => sanitize_text_field((string) ($data['reschedule_token'] ?? '')),
            'active_slot_hash' => $this->active_slot_hash_from_data($data),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = $ok ? (int) $wpdb->insert_id : 0;
        if ($id > 0) {
            do_action('sltr_data_changed', 'booking_created', ['booking_id' => $id, 'package_id' => (int) ($data['package_id'] ?? 0)]);
        }
        return $id;
    }


    public function count_by_package_id(int $package_id): int
    {
        global $wpdb;
        $table = Database::bookings_table();
        if ($package_id <= 0) { return 0; }
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE package_id=%d", $package_id));
    }

    public function count_active_by_package_id(int $package_id): int
    {
        global $wpdb;
        $table = Database::bookings_table();
        if ($package_id <= 0) { return 0; }
        [$status_placeholders, $status_args] = $this->active_statuses_sql();
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE package_id=%d AND status IN ({$status_placeholders})", array_merge([$package_id], $status_args)));
    }

    public function count_coupon_usage_by_email(int $coupon_id, string $email): int
    {
        global $wpdb;
        $table = Database::bookings_table();
        $email = sanitize_email($email);
        if ($coupon_id <= 0 || $email === '') { return 0; }
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE coupon_id=%d AND customer_email=%s AND coupon_usage_recorded=1", $coupon_id, $email));
    }

    public function mark_coupon_usage_recorded(int $id): bool
    {
        global $wpdb;
        $table = Database::bookings_table();
        if ($id <= 0) { return false; }
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET coupon_usage_recorded=1, updated_at=%s WHERE id=%d AND coupon_id > 0 AND coupon_usage_recorded=0",
            current_time('mysql'),
            $id
        ));
        return $updated === 1;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = Database::bookings_table();
        $current = $this->get_by_id($id);
        if (!$current) { return false; }

        $data = $this->normalize_update_data($data);
        if ($data === []) { return false; }

        $merged = array_merge($current, $data);
        $data['active_slot_hash'] = $this->active_slot_hash_from_data($merged);
        $data['updated_at'] = current_time('mysql');

        $formats = $this->formats_for_update_data($data);
        $updated = $wpdb->update($table, $data, ['id' => $id], $formats, ['%d']) !== false;
        if ($updated) {
            do_action('sltr_data_changed', 'booking_updated', ['booking_id' => $id, 'package_id' => (int) ($merged['package_id'] ?? $current['package_id'] ?? 0)]);
        }
        return $updated;
    }

    /**
     * Keep repository updates constrained to known columns and normalize values by type.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function normalize_update_data(array $data): array
    {
        $allowed = [];
        foreach ($data as $column => $value) {
            if (!array_key_exists($column, self::UPDATE_FORMATS) || $column === 'updated_at' || $column === 'active_slot_hash') {
                continue;
            }

            if ($value === null) {
                $allowed[$column] = null;
                continue;
            }

            switch ($column) {
                case 'user_id':
                case 'package_id':
                case 'resource_id':
                case 'staff_id':
                case 'coupon_id':
                    $allowed[$column] = max(0, (int) $value);
                    break;

                case 'coupon_usage_recorded':
                    $allowed[$column] = !empty($value) ? 1 : 0;
                    break;

                case 'total_amount':
                case 'gross_amount':
                case 'amount_due_now':
                case 'paid_amount':
                case 'remaining_amount':
                case 'deposit_amount':
                case 'extras_amount':
                case 'base_amount':
                case 'pricing_adjustment_amount':
            case 'package_discount_amount':
                case 'coupon_discount_amount':
                case 'tax_amount':
                    $allowed[$column] = (float) $value;
                    break;

                case 'customer_email':
                    $allowed[$column] = sanitize_email((string) $value);
                    break;

                case 'status':
                case 'payment_status':
                case 'payment_gateway':
                case 'pricing_mode':
                case 'payment_choice':
                case 'source':
                    $allowed[$column] = sanitize_key((string) $value);
                    break;

                case 'payment_redirect_url':
                    $allowed[$column] = esc_url_raw((string) $value);
                    break;

                case 'selected_extras_json':
                case 'payment_policy_snapshot_json':
                    $allowed[$column] = is_array($value) ? wp_json_encode($value) : (string) $value;
                    break;

                case 'notes':
                    $allowed[$column] = wp_kses_post((string) $value);
                    break;

                default:
                    $allowed[$column] = sanitize_text_field((string) $value);
                    break;
            }
        }

        return $allowed;
    }

    /**
     * @param array<string,mixed> $data
     * @return string[]
     */
    private function formats_for_update_data(array $data): array
    {
        $formats = [];
        foreach (array_keys($data) as $column) {
            $formats[] = self::UPDATE_FORMATS[$column];
        }
        return $formats;
    }

    public function update_status(int $id, string $status): bool
    {
        $data = ['status' => sanitize_key($status)];
        if ($status === 'cancelled') { $data['cancelled_at'] = current_time('mysql'); }
        if ($status === 'completed') { $data['completed_at'] = current_time('mysql'); }
        return $this->update($id, $data);
    }

    public function update_payment_status(int $id, string $status): bool
    {
        $data = ['payment_status' => sanitize_key($status)];
        if ($status === 'paid') { $data['paid_at'] = current_time('mysql'); }
        if ($status === 'refunded') { $data['refunded_at'] = current_time('mysql'); }
        return $this->update($id, $data);
    }

    /**
     * Updates payment fields only while the stored status still matches the
     * status read by the caller. This prevents concurrent webhook deliveries
     * from both executing the same payment transition side effects.
     */
    public function compare_and_set_payment_status(int $id, string $expected_status, array $data): bool
    {
        global $wpdb;
        $table = Database::bookings_table();
        $current = $this->get_by_id($id);
        if (!$current) { return false; }

        $data = $this->normalize_update_data($data);
        if (!isset($data['payment_status'])) { return false; }
        $data['updated_at'] = current_time('mysql');

        $updated = $wpdb->update(
            $table,
            $data,
            ['id' => $id, 'payment_status' => sanitize_key($expected_status)],
            $this->formats_for_update_data($data),
            ['%d', '%s']
        );
        if ($updated === 1) {
            do_action('sltr_data_changed', 'booking_updated', [
                'booking_id' => $id,
                'package_id' => (int) ($current['package_id'] ?? 0),
            ]);
            return true;
        }
        return false;
    }

    public function update_payment_meta(int $id, string $gateway, string $external_id = ''): bool
    {
        return $this->update($id, ['payment_gateway' => sanitize_key($gateway), 'external_payment_id' => sanitize_text_field($external_id)]);
    }

    public function cancel(int $id): bool { return $this->update_status($id, 'cancelled'); }

    /**
     * Consumes a public cancellation token and cancels its booking in one SQL
     * statement. Only the request that changes one row may run side effects.
     */
    public function cancel_by_token_atomically(int $id, string $token, array $allowed_statuses): bool
    {
        global $wpdb;
        $table = Database::bookings_table();
        $token = sanitize_text_field($token);
        $statuses = array_values(array_unique(array_filter(array_map('sanitize_key', $allowed_statuses))));
        if ($id <= 0 || $token === '' || $statuses === []) { return false; }

        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
        $now = current_time('mysql');
        $params = array_merge([$now, $now, $id, $token], $statuses);
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET status='cancelled',cancellation_token=NULL,active_slot_hash=NULL,cancelled_at=%s,updated_at=%s WHERE id=%d AND cancellation_token=%s AND status IN ({$placeholders})",
            $params
        ));

        if ($updated === 1) {
            $current = $this->get_by_id($id);
            do_action('sltr_data_changed', 'booking_updated', [
                'booking_id' => $id,
                'package_id' => (int) ($current['package_id'] ?? 0),
            ]);
            return true;
        }
        return false;
    }

    public function complete(int $id): bool { return $this->update_status($id, 'completed'); }

    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        global $wpdb;
        $table = Database::bookings_table();
        [$where, $params] = $this->where($filters);
        $params[] = $limit;
        $params[] = $offset;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} {$where} ORDER BY booking_date DESC,start_time DESC LIMIT %d OFFSET %d", $params), ARRAY_A) ?: [];
    }

    public function count_search(array $filters = []): int
    {
        global $wpdb;
        $table = Database::bookings_table();
        [$where, $params] = $this->where($filters);
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table/where fragments are generated internally; values are prepared when present.
        return !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($sql, $params)) : (int) $wpdb->get_var($sql);
    }



    public function find_stale_pending_payments(int $older_than_minutes = 60, int $limit = 50): array
    {
        global $wpdb;
        $table = Database::bookings_table();
        $older_than_minutes = max(1, $older_than_minutes);
        $limit = max(1, min(500, $limit));
        $cutoff = date('Y-m-d H:i:s', current_time('timestamp') - ($older_than_minutes * MINUTE_IN_SECONDS));
        if (!(function_exists('sltr_feature_enabled') && \sltr_feature_enabled('payments'))) { return []; }
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE status='pending_payment' AND payment_status IN ('pending','unpaid') AND updated_at < %s ORDER BY updated_at ASC LIMIT %d",
                $cutoff,
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    public function count_stale_pending_payments(int $older_than_minutes = 60): int
    {
        global $wpdb;
        $table = Database::bookings_table();
        $older_than_minutes = max(1, $older_than_minutes);
        $cutoff = date('Y-m-d H:i:s', current_time('timestamp') - ($older_than_minutes * MINUTE_IN_SECONDS));
        if (!(function_exists('sltr_feature_enabled') && \sltr_feature_enabled('payments'))) { return 0; }
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status='pending_payment' AND payment_status IN ('pending','unpaid') AND updated_at < %s", $cutoff));
    }

    public function find_failed_pending_payments(int $limit = 50): array
    {
        global $wpdb;
        $table = Database::bookings_table();
        $limit = max(1, min(500, $limit));
        if (!(function_exists('sltr_feature_enabled') && \sltr_feature_enabled('payments'))) { return []; }
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE status='pending_payment' AND payment_status='failed' ORDER BY updated_at ASC LIMIT %d",
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }


    private function where(array $filters): array
    {
        global $wpdb;
        $where = [];
        $params = [];
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = "(customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s OR city LIKE %s OR state LIKE %s OR address LIKE %s OR company LIKE %s OR booking_date LIKE %s)";
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
        }
        foreach (['status', 'payment_status'] as $key) {
            $value = sanitize_key((string) ($filters[$key] ?? ''));
            if ($value !== '' && $value !== 'all') { $where[] = "{$key}=%s"; $params[] = $value; }
        }
        foreach (['date_from' => 'booking_date >= %s', 'date_to' => 'booking_date <= %s'] as $key => $condition) {
            $value = sanitize_text_field((string) ($filters[$key] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) { $where[] = $condition; $params[] = $value; }
        }
        if (!empty($filters['upcoming'])) { $where[] = 'booking_date >= %s'; $params[] = current_time('Y-m-d'); }
        return [empty($where) ? '' : 'WHERE ' . implode(' AND ', $where), $params];
    }
}
