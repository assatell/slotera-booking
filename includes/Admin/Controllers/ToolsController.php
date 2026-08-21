<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\DateRangeInventoryService;
use Slotera\Application\Services\CronResilienceService;
use Slotera\Domain\Availability\AvailabilityService;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class ToolsController
{
    private RequestValidator $request;

    private const SAFE_IMPORT_MAX_BYTES = 2097152;
    private const SAFE_IMPORT_MAX_ROWS = 1000;
    private const SAFE_IMPORT_MAX_COLUMNS = 24;
    private const SAFE_IMPORT_MAX_FIELD_BYTES = 10000;
    private const SAFE_IMPORT_MAX_LINE_BYTES = 65536;

    private const BOOKING_EXPORT_COLUMNS = [
        'id', 'package_id', 'resource_id', 'staff_id',
        'customer_name', 'customer_email', 'customer_phone', 'booking_locale',
        'city', 'state', 'address', 'company',
        'booking_date', 'end_date', 'start_time', 'end_time',
        'status', 'payment_status', 'payment_gateway',
        'total_amount', 'gross_amount', 'amount_due_now', 'paid_amount',
        'remaining_amount', 'deposit_amount', 'extras_amount',
        'selected_extras_json', 'pricing_mode', 'payment_choice',
        'base_amount', 'package_discount_amount', 'pricing_adjustment_amount',
        'pricing_adjustment_label', 'coupon_code', 'coupon_discount_amount',
        'tax_amount', 'notes', 'source',
        'paid_at', 'refunded_at', 'cancelled_at', 'completed_at',
        'created_at', 'updated_at',
    ];

    /** @return string[] */
    private function active_statuses(): array
    {
        return function_exists('sltr_active_booking_statuses') ? \sltr_active_booking_statuses() : ['confirmed'];
    }

    /** @return array{0:string,1:string[]} */
    private function active_statuses_sql(): array
    {
        $statuses = array_values(array_filter(array_map('sanitize_key', $this->active_statuses())));
        if ($statuses === []) { $statuses = ['confirmed']; }
        return [implode(',', array_fill(0, count($statuses), '%s')), $statuses];
    }

    public function __construct(?RequestValidator $request = null)
    {
        $this->request = $request ?: new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_tools_rebuild_hashes', [$this, 'rebuild_hashes']);
        add_action('admin_post_sltr_tools_export_bookings', [$this, 'export_bookings']);
        add_action('admin_post_sltr_tools_import_bookings', [$this, 'import_bookings']);
        add_action('admin_post_sltr_tools_import_bookings_commit', [$this, 'import_bookings_commit']);
        add_action('admin_post_sltr_tools_save_debug', [$this, 'save_debug']);
        add_action('admin_post_sltr_tools_save_profiling', [$this, 'save_profiling']);
        add_action('admin_post_sltr_tools_prune_logs', [$this, 'prune_logs']);
        add_action('admin_post_sltr_tools_preview_due_cron', [$this, 'preview_due_cron']);
        add_action('admin_post_sltr_tools_run_due_cron', [$this, 'run_due_cron']);
    }

    public function rebuild_hashes(): void
    {
        $this->guard('sltr_tools_rebuild_hashes');
        global $wpdb;
        $limit = max(25, min(2000, absint(wp_unslash((string) ($_POST['limit'] ?? 500)))));
        $bookings = Database::bookings_table();
        $packages = Database::packages_table();
        [$status_placeholders, $status_args] = $this->active_statuses_sql();
        $rows = $wpdb->get_results($wpdb->prepare("SELECT b.id, b.package_id, b.resource_id, b.staff_id, b.booking_date, b.end_date, b.start_time, b.end_time, b.status
            FROM {$bookings} b
            INNER JOIN {$packages} p ON p.id = b.package_id
            WHERE b.status IN ({$status_placeholders})
              AND COALESCE(p.max_bookings_per_slot, 1) <= 1
            ORDER BY b.id ASC
            LIMIT %d", array_merge($status_args, [$limit])), ARRAY_A) ?: [];
        $updated = 0;
        $cleared = 0;
        foreach ($rows as $row) {
            $id = absint($row['id'] ?? 0);
            if ($id <= 0) { continue; }
            $hash = hash('sha256', implode('|', [
                (int) ($row['package_id'] ?? 0),
                (string) ($row['booking_date'] ?? ''),
                (string) ($row['end_date'] ?? ''),
                (string) ($row['start_time'] ?? ''),
                (string) ($row['end_time'] ?? ''),
                (int) ($row['resource_id'] ?? 0),
                (int) ($row['staff_id'] ?? 0),
            ]));
            $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$bookings} WHERE active_slot_hash=%s AND id<>%d LIMIT 1", $hash, $id));
            if ($existing) {
                $wpdb->update($bookings, ['active_slot_hash' => null], ['id' => $id], ['%s'], ['%d']);
                $cleared++;
            } else {
                $wpdb->update($bookings, ['active_slot_hash' => $hash], ['id' => $id], ['%s'], ['%d']);
                $updated++;
            }
        }
        set_transient('sltr_tools_result_' . get_current_user_id(), [
            'type' => 'rebuild',
            'status' => 'ok',
            'message' => 'Active slot hash rebuild batch finished.',
            'checked' => count($rows),
            'updated' => $updated,
            'duplicates_cleared' => $cleared,
        ], 120);
        $this->redirect('rebuilt');
    }

    public function prune_logs(): void
    {
        $this->guard('sltr_tools_prune_logs');
        global $wpdb;
        $days = max(7, min(3650, absint(wp_unslash((string) ($_POST['days'] ?? 90)))));
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        $activity = $wpdb->query($wpdb->prepare("DELETE FROM " . Database::activity_log_table() . " WHERE created_at < %s", $cutoff));
        $webhooks = $wpdb->query($wpdb->prepare("DELETE FROM " . Database::outgoing_webhook_deliveries_table() . " WHERE created_at < %s AND status IN ('success','failed_permanent')", $cutoff));
        set_transient('sltr_tools_result_' . get_current_user_id(), [
            'type' => 'maintenance',
            'status' => 'ok',
            'message' => 'Old logs pruned.',
            'older_than_days' => $days,
            'activity_deleted' => max(0, (int) $activity),
            'webhook_deliveries_deleted' => max(0, (int) $webhooks),
        ], 120);
        $this->redirect('pruned');
    }


    public function preview_due_cron(): void
    {
        $this->guard('sltr_tools_preview_due_cron');
        $preview = CronResilienceService::due_preview();
        set_transient('sltr_tools_result_' . get_current_user_id(), [
            'type' => 'cron_dry_run_preview',
            'status' => $preview === [] ? 'ok' : 'warning',
            'message' => $preview === [] ? 'No due Slotera cron jobs found.' : 'Dry-run preview finished. Review the due jobs before executing them.',
            'jobs' => $preview,
            'due_count' => count($preview),
        ], 5 * MINUTE_IN_SECONDS);
        $this->redirect('cron_preview');
    }

    public function run_due_cron(): void
    {
        $this->guard('sltr_tools_run_due_cron');
        $ran = [];
        $skipped = [];
        foreach (CronResilienceService::due_preview() as $job) {
            $hook = (string) ($job['hook'] ?? '');
            $label = (string) ($job['label'] ?? $hook);
            if ($hook === '') { continue; }
            if (!empty($job['locked'])) {
                $skipped[] = $label . ' (locked)';
                continue;
            }
            do_action($hook);
            $ran[] = $label;
        }
        set_transient('sltr_tools_result_' . get_current_user_id(), [
            'type' => 'cron_resilience',
            'status' => 'ok',
            'message' => $ran === [] ? 'No due Slotera cron jobs were executed.' : 'Due Slotera cron jobs executed.',
            'ran' => $ran,
            'skipped' => $skipped,
        ], 120);
        $this->redirect('cron_run');
    }

    public function save_debug(): void
    {
        $this->guard('sltr_tools_save_debug');
        update_option('sltr_debug_mode', !empty(wp_unslash((string) ($_POST['sltr_debug_mode'] ?? ''))) ? 1 : 0, false);
        $this->redirect('debug_saved');
    }

    public function save_profiling(): void
    {
        $this->guard('sltr_tools_save_profiling');
        update_option('sltr_profiling_mode', !empty(wp_unslash((string) ($_POST['sltr_profiling_mode'] ?? ''))) ? 1 : 0, false);
        $this->redirect('profiling_saved');
    }

    public function export_bookings(): void
    {
        $this->guard('sltr_tools_export_bookings');
        global $wpdb;
        $table = Database::bookings_table();
        $status = sanitize_key(wp_unslash((string) ($_POST['status'] ?? '')));
        $from = sanitize_text_field(wp_unslash((string) ($_POST['from'] ?? '')));
        $to = sanitize_text_field(wp_unslash((string) ($_POST['to'] ?? '')));
        $where = '1=1';
        $args = [];
        if ($status !== '') { $where .= ' AND status=%s'; $args[] = $status; }
        if ($from !== '') { $where .= ' AND booking_date >= %s'; $args[] = $from; }
        if ($to !== '') { $where .= ' AND booking_date <= %s'; $args[] = $to; }
        $columns = $this->booking_columns();
        $select = implode(', ', array_map(static fn(string $column): string => '`' . $column . '`', $columns));
        $sql = "SELECT {$select} FROM {$table} WHERE {$where} ORDER BY id ASC";
        $rows = $args ? $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);
        if (!is_array($rows)) { $rows = []; }
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=slotera-bookings-' . gmdate('Ymd-His') . '.csv');
        $out = fopen('php://output', 'w');
        if ($out) {
            fputcsv($out, $columns);
            foreach ($rows as $row) {
                $line = [];
                foreach ($columns as $col) { $line[] = $this->csv_export_value((string) ($row[$col] ?? '')); }
                fputcsv($out, $line);
            }
            fclose($out);
        }
        exit;
    }

    public function import_bookings(): void
    {
        $this->guard('sltr_tools_import_bookings');
        $file = $_FILES['bookings_csv'] ?? null;
        $upload_error = $this->validate_csv_upload(is_array($file) ? $file : []);
        if ($upload_error !== '') {
            set_transient('sltr_tools_result_' . get_current_user_id(), ['type' => 'safe_import', 'status' => 'error', 'message' => $upload_error], 120);
            $this->redirect('import_error');
        }

        $parsed = $this->parse_safe_import_csv((string) $file['tmp_name']);
        if (!empty($parsed['fatal'])) {
            set_transient('sltr_tools_result_' . get_current_user_id(), ['type' => 'safe_import', 'status' => 'error', 'message' => $parsed['fatal']], 120);
            $this->redirect('import_error');
        }

        $valid_rows = $parsed['valid_rows'] ?? [];
        $import_key = '';
        if (!empty($valid_rows)) {
            $import_key = wp_generate_password(24, false, false);
            set_transient('sltr_safe_import_' . get_current_user_id() . '_' . $import_key, $valid_rows, 30 * MINUTE_IN_SECONDS);
        }

        set_transient('sltr_tools_result_' . get_current_user_id(), [
            'type' => 'safe_import_preview',
            'status' => !empty($valid_rows) ? (!empty($parsed['rejected_rows']) ? 'warning' : 'ok') : 'error',
            'message' => !empty($valid_rows)
                ? __('Safe import validation finished. Review the result and import valid rows only.', 'slotera-booking')
                : __('Safe import validation finished. No rows can be imported.', 'slotera-booking'),
            'valid_rows' => count($valid_rows),
            'rejected_rows' => count($parsed['rejected_rows'] ?? []),
            'import_key' => $import_key,
            'errors' => array_slice($parsed['rejected_rows'] ?? [], 0, 25),
        ], 10 * MINUTE_IN_SECONDS);
        $this->redirect('import_preview');
    }

    public function import_bookings_commit(): void
    {
        $this->guard('sltr_tools_import_bookings_commit');
        $key = sanitize_text_field(wp_unslash((string) ($_POST['import_key'] ?? '')));
        if ($key === '') {
            set_transient('sltr_tools_result_' . get_current_user_id(), ['type' => 'safe_import', 'status' => 'error', 'message' => __('Import session expired. Please upload and validate the CSV again.', 'slotera-booking')], 120);
            $this->redirect('import_expired');
        }
        $transient_key = 'sltr_safe_import_' . get_current_user_id() . '_' . $key;
        $rows = get_transient($transient_key);
        delete_transient($transient_key);
        if (!is_array($rows) || empty($rows)) {
            set_transient('sltr_tools_result_' . get_current_user_id(), ['type' => 'safe_import', 'status' => 'error', 'message' => __('Import session expired. Please upload and validate the CSV again.', 'slotera-booking')], 120);
            $this->redirect('import_expired');
        }

        $lock_key = 'sltr_safe_import_commit_lock_' . get_current_user_id();
        if (get_transient($lock_key)) {
            set_transient('sltr_tools_result_' . get_current_user_id(), ['type' => 'safe_import', 'status' => 'error', 'message' => __('Another CSV import is already running. Please wait and try again.', 'slotera-booking')], 120);
            $this->redirect('import_locked');
        }
        set_transient($lock_key, 1, 5 * MINUTE_IN_SECONDS);

        global $wpdb;
        $repo = new BookingRepository();
        $imported = 0;
        $errors = [];
        $events = [];

        try {
            $wpdb->query('START TRANSACTION');
            foreach ($rows as $row) {
                if (!is_array($row)) { continue; }
                $row_number = (int) ($row['_row_number'] ?? 0);
                $second_pass = $this->validate_safe_import_row($row, $row_number, true);
                if (!empty($second_pass['error'])) {
                    $errors[] = ['row' => $row_number, 'reason' => (string) $second_pass['error']];
                    continue;
                }
                unset($row['_row_number']);
                $booking_id = $repo->create($row);
                if ($booking_id > 0) {
                    $imported++;
                    $events[] = [
                        'event_name' => 'booking_imported',
                        'booking_id' => $booking_id,
                        'source' => 'safe_csv_import',
                        'occurred_at' => current_time('mysql'),
                    ];
                } else {
                    $errors[] = ['row' => $row_number, 'reason' => __('Database insert failed.', 'slotera-booking')];
                }
            }

            if (!empty($errors)) {
                $wpdb->query('ROLLBACK');
                $imported = 0;
                $events = [];
            } else {
                $wpdb->query('COMMIT');
            }
        } catch (\Throwable $e) {
            $wpdb->query('ROLLBACK');
            $imported = 0;
            $events = [];
            $errors[] = ['row' => 0, 'reason' => __('Import failed and was rolled back.', 'slotera-booking')];
        } finally {
            delete_transient($lock_key);
        }

        foreach ($events as $event) {
            do_action('slotera_event', 'booking_imported', $event);
        }

        set_transient('sltr_tools_result_' . get_current_user_id(), [
            'type' => 'safe_import',
            'status' => empty($errors) ? 'ok' : 'error',
            'message' => empty($errors)
                ? __('Safe CSV import finished.', 'slotera-booking')
                : __('Safe CSV import was aborted. No rows were imported because validation failed during the final pass.', 'slotera-booking'),
            'imported' => $imported,
            'rolled_back' => !empty($errors) ? 1 : 0,
            'rejected_on_second_pass' => count($errors),
            'errors' => array_slice($errors, 0, 25),
        ], 120);
        $this->redirect('imported');
    }

    private function parse_safe_import_csv(string $tmp): array
    {
        $handle = fopen($tmp, 'rb');
        if (!$handle) {
            return ['fatal' => __('Could not read uploaded CSV.', 'slotera-booking')];
        }
        $headers = fgetcsv($handle, self::SAFE_IMPORT_MAX_LINE_BYTES, ',', '"');
        if (!is_array($headers) || empty($headers)) {
            fclose($handle);
            return ['fatal' => __('CSV header row is missing.', 'slotera-booking')];
        }
        if (count($headers) > self::SAFE_IMPORT_MAX_COLUMNS) {
            fclose($handle);
            return ['fatal' => __('CSV contains too many columns.', 'slotera-booking')];
        }
        $headers = array_map(static fn($h): string => sanitize_key((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)), $headers);
        $header_error = $this->validate_import_headers($headers);
        if ($header_error !== '') {
            fclose($handle);
            return ['fatal' => $header_error];
        }
        $valid = [];
        $rejected = [];
        $row_number = 1;
        while (($row = fgetcsv($handle, self::SAFE_IMPORT_MAX_LINE_BYTES, ',', '"')) !== false) {
            $row_number++;
            if ($row_number > self::SAFE_IMPORT_MAX_ROWS + 1) {
                $rejected[] = ['row' => $row_number, 'reason' => sprintf(__('Import limit is %d rows per upload.', 'slotera-booking'), self::SAFE_IMPORT_MAX_ROWS)];
                break;
            }
            if (count($row) === 1 && trim((string) ($row[0] ?? '')) === '') {
                continue;
            }
            if (count($row) > count($headers)) {
                $rejected[] = ['row' => $row_number, 'reason' => __('CSV row has more fields than the header row.', 'slotera-booking')];
                continue;
            }
            $data = [];
            foreach ($headers as $i => $header) {
                if ($header === '') { continue; }
                $value = isset($row[$i]) ? (string) $row[$i] : '';
                if (strlen($value) > self::SAFE_IMPORT_MAX_FIELD_BYTES) {
                    $rejected[] = ['row' => $row_number, 'reason' => __('CSV field is too large.', 'slotera-booking')];
                    continue 2;
                }
                if (strpos($value, "\0") !== false) {
                    $rejected[] = ['row' => $row_number, 'reason' => __('CSV field contains invalid binary data.', 'slotera-booking')];
                    continue 2;
                }
                $data[$header] = sanitize_text_field($this->csv_import_value($value));
            }
            $validated = $this->validate_safe_import_row($data, $row_number, false);
            if (!empty($validated['error'])) {
                $rejected[] = ['row' => $row_number, 'reason' => (string) $validated['error']];
                continue;
            }
            $valid[] = $validated['data'];
        }
        fclose($handle);
        return ['valid_rows' => $valid, 'rejected_rows' => $rejected];
    }

    private function validate_safe_import_row(array $row, int $row_number, bool $second_pass): array
    {
        global $wpdb;
        $package_id = absint($row['package_id'] ?? 0);
        if ($package_id <= 0) { return ['error' => __('Package not found.', 'slotera-booking')]; }
        $packages = new PackageRepository();
        $package = $packages->get_by_id($package_id);
        if (!$package || (int) ($package['is_active'] ?? 0) !== 1) { return ['error' => __('Package not found or inactive.', 'slotera-booking')]; }

        $email = sanitize_email((string) ($row['customer_email'] ?? ''));
        if ($email === '' || !is_email($email)) { return ['error' => __('Invalid email.', 'slotera-booking')]; }

        $name = sanitize_text_field((string) ($row['customer_name'] ?? ''));
        if ($name === '') { return ['error' => __('Customer name is required.', 'slotera-booking')]; }

        $booking_date = sanitize_text_field((string) ($row['booking_date'] ?? ''));
        if (!$this->safe_import_valid_date($booking_date)) { return ['error' => __('Invalid booking date.', 'slotera-booking')]; }
        if ($booking_date < current_time('Y-m-d')) { return ['error' => __('Past dates cannot be imported by the safe importer.', 'slotera-booking')]; }

        $mode = sanitize_key((string) ($package['booking_mode'] ?? 'fixed'));
        $resource_id = absint($row['resource_id'] ?? 0);
        $staff_id = absint($row['staff_id'] ?? 0);
        $end_date = sanitize_text_field((string) ($row['end_date'] ?? ''));
        $start_time = $this->safe_import_normalize_time((string) ($row['start_time'] ?? '00:00:00'));
        $end_time = $this->safe_import_normalize_time((string) ($row['end_time'] ?? '00:00:00'));
        if ($start_time === '' || $end_time === '') { return ['error' => __('Invalid time format.', 'slotera-booking')]; }

        if (!empty($row['status']) && !in_array(sanitize_key((string) $row['status']), $this->active_statuses(), true)) {
            return ['error' => __('Booking status cannot be imported by the safe importer.', 'slotera-booking')];
        }
        if (!empty($row['payment_status']) && !in_array(sanitize_key((string) $row['payment_status']), ['unpaid', 'pending'], true)) {
            return ['error' => __('Payment status cannot be imported. Paid/refunded/failed states must come from payments or manual admin confirmation.', 'slotera-booking')];
        }
        if (!empty($row['paid_at']) || !empty($row['paid_amount']) || !empty($row['external_payment_id'])) {
            return ['error' => __('Payment fields cannot be imported by the safe importer.', 'slotera-booking')];
        }

        if ($mode === 'date_range_inventory') {
            if (!$this->safe_import_valid_date($end_date) || $end_date <= $booking_date) { return ['error' => __('Invalid date range.', 'slotera-booking')]; }
            if ($resource_id <= 0) { return ['error' => __('Room/unit is required for date range inventory imports.', 'slotera-booking')]; }
            $date_range = new DateRangeInventoryService();
            $validation = $date_range->validate_range($package, $booking_date, $end_date);
            if (is_wp_error($validation)) { return ['error' => $validation->get_error_message()]; }
            if (!$date_range->unit_capacity_available($package, $resource_id, $booking_date, $end_date)) { return ['error' => __('Slot is unavailable.', 'slotera-booking')]; }
        } elseif ($mode === 'simple') {
            $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
            $simple = is_array($configs) && isset($configs['simple']) && is_array($configs['simple']) ? $configs['simple'] : [];
            if (sanitize_key((string) ($simple['capacity_type'] ?? 'unlimited')) === 'limited') {
                $capacity = max(1, (int) ($simple['capacity_total'] ?? 1));
                [$status_placeholders, $status_args] = $this->active_statuses_sql();
                $active_count = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . Database::bookings_table() . " WHERE package_id=%d AND status IN ({$status_placeholders})", array_merge([$package_id], $status_args)));
                if ($active_count >= $capacity) { return ['error' => __('Package capacity is full.', 'slotera-booking')]; }
            }
        } else {
            if ($start_time === '00:00:00' && $end_time === '00:00:00') { return ['error' => __('Start and end time are required.', 'slotera-booking')]; }
            $availability = new AvailabilityService();
            if (!$availability->slot_is_available($package_id, $booking_date, $start_time, $end_time, $resource_id, $staff_id)) {
                return ['error' => __('Slot is unavailable.', 'slotera-booking')];
            }
        }

        if ($this->safe_import_duplicate_exists($package_id, $email, $booking_date, $end_date, $start_time, $end_time, $resource_id, $staff_id)) {
            return ['error' => __('Duplicate booking.', 'slotera-booking')];
        }

        $total = isset($row['total_amount']) && is_numeric($row['total_amount']) ? max(0, (float) $row['total_amount']) : max(0, (float) ($package['price'] ?? 0));
        $data = [
            '_row_number' => $row_number,
            'user_id' => 0,
            'package_id' => $package_id,
            'resource_id' => $resource_id,
            'staff_id' => $staff_id,
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => sanitize_text_field((string) ($row['customer_phone'] ?? '')),
            'city' => sanitize_text_field((string) ($row['city'] ?? '')),
            'state' => sanitize_text_field((string) ($row['state'] ?? '')),
            'address' => sanitize_text_field((string) ($row['address'] ?? '')),
            'company' => sanitize_text_field((string) ($row['company'] ?? '')),
            'booking_date' => $booking_date,
            'end_date' => $mode === 'date_range_inventory' ? $end_date : '',
            'start_time' => $start_time,
            'end_time' => $end_time,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'payment_gateway' => '',
            'external_payment_id' => '',
            'total_amount' => $total,
            'gross_amount' => $total,
            'amount_due_now' => 0,
            'paid_amount' => 0,
            'remaining_amount' => $total,
            'deposit_amount' => 0,
            'extras_amount' => 0,
            'selected_extras' => [],
            'pricing_mode' => sanitize_key((string) ($row['pricing_mode'] ?? 'fixed')),
            'payment_choice' => '',
            'payment_policy_snapshot' => ['source' => 'safe_csv_import', 'note' => 'Payment state intentionally not imported.'],
            'base_amount' => $total,
            'package_discount_amount' => 0,
            'coupon_id' => 0,
            'coupon_code' => '',
            'coupon_discount_amount' => 0,
            'tax_amount' => 0,
            'coupon_usage_recorded' => 0,
            'notes' => sanitize_textarea_field((string) ($row['notes'] ?? '')),
            'source' => 'csv_import',
            'cancellation_token' => wp_generate_password(32, false, false),
            'reschedule_token' => wp_generate_password(32, false, false),
        ];
        return ['data' => $data];
    }


    private function validate_csv_upload(array $file): string
    {
        $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_OK;
        if ($error !== UPLOAD_ERR_OK) {
            return __('CSV upload failed. Please try again.', 'slotera-booking');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        $name = (string) ($file['name'] ?? '');
        $size = isset($file['size']) ? (int) $file['size'] : 0;
        if ($tmp === '' || !is_uploaded_file($tmp) || !is_readable($tmp)) {
            return __('No CSV file uploaded.', 'slotera-booking');
        }
        $actual_size = (int) filesize($tmp);
        if ($size <= 0 || $actual_size <= 0 || $size > self::SAFE_IMPORT_MAX_BYTES || $actual_size > self::SAFE_IMPORT_MAX_BYTES) {
            return sprintf(__('CSV file must be smaller than %d MB.', 'slotera-booking'), 2);
        }
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            return __('Only .csv files are allowed.', 'slotera-booking');
        }

        $allowed_mimes = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
            'application/octet-stream',
        ];
        $detected_mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected_mime = (string) finfo_file($finfo, $tmp);
                finfo_close($finfo);
            }
        }
        if ($detected_mime !== '' && !in_array($detected_mime, $allowed_mimes, true)) {
            return __('Uploaded file is not a valid CSV file.', 'slotera-booking');
        }

        $check = function_exists('wp_check_filetype_and_ext') ? wp_check_filetype_and_ext($tmp, $name, ['csv' => 'text/csv']) : ['ext' => 'csv'];
        if (is_array($check) && isset($check['ext']) && $check['ext'] !== false && $check['ext'] !== 'csv') {
            return __('Uploaded file is not a valid CSV file.', 'slotera-booking');
        }
        $sample = file_get_contents($tmp, false, null, 0, 4096);
        if ($sample === false || strpos($sample, "\0") !== false) {
            return __('Uploaded CSV appears to be invalid.', 'slotera-booking');
        }
        if (preg_match('/^[\s\xEF\xBB\xBF]*(<\?php|<html|<!doctype|<script|<svg)/i', $sample) === 1) {
            return __('Uploaded CSV appears to be invalid.', 'slotera-booking');
        }
        return '';
    }

    private function validate_import_headers(array $headers): string
    {
        $allowed = array_fill_keys([
            'package_id', 'resource_id', 'staff_id', 'customer_name', 'customer_email', 'customer_phone',
            'city', 'state', 'address', 'company', 'booking_date', 'end_date', 'start_time', 'end_time',
            'status', 'payment_status', 'paid_at', 'paid_amount', 'external_payment_id', 'total_amount',
            'pricing_mode', 'notes',
        ], true);
        $seen = [];
        foreach ($headers as $header) {
            if ($header === '') { continue; }
            if (isset($seen[$header])) {
                return __('CSV contains duplicate column headers.', 'slotera-booking');
            }
            $seen[$header] = true;
            if (!isset($allowed[$header])) {
                return sprintf(__('CSV contains an unsupported column: %s', 'slotera-booking'), $header);
            }
        }
        foreach (['package_id', 'customer_name', 'customer_email', 'booking_date'] as $required) {
            if (!isset($seen[$required])) {
                return sprintf(__('CSV is missing required column: %s', 'slotera-booking'), $required);
            }
        }
        return '';
    }

    private function csv_export_value(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        // Prevent CSV formula injection in Excel, LibreOffice and Google Sheets.
        // Formula payloads can be hidden behind spaces, tabs, newlines, control bytes
        // or a UTF-8 BOM, so detection uses a normalized copy while preserving output.
        if ($this->csv_starts_with_formula_payload($value)) {
            return "'" . $value;
        }

        return $value;
    }

    private function csv_import_value(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        if ($this->csv_starts_with_formula_payload($value)) {
            return '';
        }
        return $value;
    }

    private function csv_starts_with_formula_payload(string $value): bool
    {
        $normalized = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        if (!is_string($normalized)) { $normalized = $value; }

        // Strip ASCII whitespace/control prefix for detection only. This catches
        // payloads such as "\t=CMD()", "\n+SUM()" and "  @HYPERLINK(...)".
        $normalized = preg_replace('/^[\x00-\x20\x7F]+/', '', $normalized);
        if (!is_string($normalized) || $normalized === '') { return false; }

        return preg_match('/^[=+\-@]/', $normalized) === 1;
    }

    private function safe_import_duplicate_exists(int $package_id, string $email, string $date, string $end_date, string $start, string $end, int $resource_id, int $staff_id): bool
    {
        global $wpdb;
        $table = Database::bookings_table();
        [$status_placeholders, $status_args] = $this->active_statuses_sql();
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE package_id=%d AND customer_email=%s AND booking_date=%s AND COALESCE(end_date,'')=%s AND start_time=%s AND end_time=%s AND resource_id=%d AND staff_id=%d AND status IN ({$status_placeholders}) LIMIT 1",
            array_merge([$package_id, $email, $date, $end_date, $start, $end, $resource_id, $staff_id], $status_args)
        ));
    }

    private function safe_import_valid_date(string $date): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) { return false; }
        try {
            $dt = new \DateTimeImmutable($date, wp_timezone());
            return $dt->format('Y-m-d') === $date;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function safe_import_normalize_time(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^\d{1,2}:\d{2}$/', $time) === 1) { $time .= ':00'; }
        if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d:[0-5]\d$/', $time) !== 1) { return ''; }
        [$h, $m, $s] = array_map('intval', explode(':', $time));
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    private function booking_columns(): array
    {
        // Public/admin exports are an intentional data interface. Keep this an
        // explicit allowlist so future schema additions (tokens, hashes, redirect
        // URLs or payment internals) cannot silently leak into CSV files.
        return self::BOOKING_EXPORT_COLUMNS;
    }

    private function guard(string $nonce): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_TOOLS);
        $this->request->verify_admin_nonce($nonce);
    }

    private function redirect(string $message): void
    {
        wp_safe_redirect(add_query_arg(['page' => 'slotera-diagnostics', 'tab' => 'tools', 'sltr_message' => $message], admin_url('admin.php')));
        exit;
    }
}
