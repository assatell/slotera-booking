<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;
use Slotera\Application\Security\DataRedactor;

if (!defined('ABSPATH')) { exit; }

final class PaymentInvoiceRepository
{
    public function create(array $data): int
    {
        global $wpdb;
        $table = Database::payment_invoices_table();
        $now = current_time('mysql');
        $number = sanitize_text_field((string) ($data['invoice_number'] ?? ''));
        if ($number === '') { $number = $this->next_number(); }
        $row = $this->normalize($data);
        $row['invoice_number'] = $number;
        $row['created_at'] = $now;
        $row['updated_at'] = $now;
        $ok = $wpdb->insert($table, $row, [
            '%s','%d','%s','%s','%s','%s','%f','%s','%f','%f','%f','%f','%f','%s','%s','%s','%s','%s','%s'
        ]);
        return $ok ? (int) $wpdb->insert_id : 0;
    }

    public function upsert_for_booking(int $booking_id, array $data): int
    {
        $existing = $this->get_by_booking_id($booking_id);
        if (!$existing) {
            $data['booking_id'] = $booking_id;
            return $this->create($data);
        }
        $this->update((int) $existing['id'], $data);
        return (int) $existing['id'];
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = Database::payment_invoices_table();
        $row = $this->normalize($data, false);
        if ($row === []) { $row = []; }
        $row['updated_at'] = current_time('mysql');
        $formats = [];
        foreach (array_keys($row) as $key) {
            $formats[] = in_array($key, ['booking_id'], true) ? '%d' : (in_array($key, ['subtotal','tax_rate','tax_amount','total','paid','remaining'], true) ? '%f' : '%s');
        }
        return $wpdb->update($table, $row, ['id' => $id], $formats, ['%d']) !== false;
    }

    public function get(int $id): ?array
    {
        global $wpdb;
        $table = Database::payment_invoices_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id), ARRAY_A);
        return $row ?: null;
    }

    public function get_by_booking_id(int $booking_id): ?array
    {
        global $wpdb;
        $table = Database::payment_invoices_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE booking_id=%d LIMIT 1", $booking_id), ARRAY_A);
        return $row ?: null;
    }

    public function search(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        global $wpdb;
        $table = Database::payment_invoices_table();
        [$where, $params] = $this->where($filters);
        $params[] = max(1, min(200, $limit));
        $params[] = max(0, $offset);
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} {$where} ORDER BY created_at DESC,id DESC LIMIT %d OFFSET %d", $params), ARRAY_A) ?: [];
    }

    public function count(array $filters = []): int
    {
        global $wpdb;
        $table = Database::payment_invoices_table();
        [$where, $params] = $this->where($filters);
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        return $params ? (int) $wpdb->get_var($wpdb->prepare($sql, $params)) : (int) $wpdb->get_var($sql);
    }

    public function totals(): array
    {
        global $wpdb;
        $table = Database::payment_invoices_table();
        return $wpdb->get_results("SELECT status,currency,SUM(total) AS total,SUM(paid) AS paid,SUM(remaining) AS remaining,COUNT(*) AS count FROM {$table} GROUP BY status,currency", ARRAY_A) ?: [];
    }

    private function next_number(): string
    {
        global $wpdb;
        $table = Database::payment_invoices_table();
        $prefix = 'SLTR-' . gmdate('Y') . '-';
        $last = (string) $wpdb->get_var($wpdb->prepare("SELECT invoice_number FROM {$table} WHERE invoice_number LIKE %s ORDER BY id DESC LIMIT 1", $prefix . '%'));
        $n = 1;
        if (preg_match('/-(\d+)$/', $last, $m)) { $n = max(1, (int) $m[1] + 1); }
        return $prefix . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    private function normalize(array $data, bool $include_defaults = true): array
    {
        $allowed = [
            'invoice_number','booking_id','customer_email','customer_name','status','currency','subtotal','tax_label','tax_rate','tax_amount','total','paid','remaining','issue_date','due_date','notes','metadata_json','created_at','updated_at'
        ];
        $row = [];
        foreach ($allowed as $key) {
            if (!$include_defaults && !array_key_exists($key, $data) && $key !== 'metadata_json') { continue; }
            $value = $data[$key] ?? null;
            switch ($key) {
                case 'booking_id': $value = absint($value ?? 0); break;
                case 'customer_email': $value = sanitize_email((string) ($value ?? '')); break;
                case 'invoice_number': case 'customer_name': case 'tax_label': $value = sanitize_text_field((string) ($value ?? '')); break;
                case 'status': $value = sanitize_key((string) ($value ?? 'draft')); break;
                case 'currency': $value = strtoupper(sanitize_key((string) ($value ?? 'EUR'))); break;
                case 'subtotal': case 'tax_rate': case 'tax_amount': case 'total': case 'paid': case 'remaining': $value = round((float) ($value ?? 0), 2); break;
                case 'issue_date': case 'due_date': $value = sanitize_text_field((string) ($value ?? '')); $value = $value !== '' ? $value : null; break;
                case 'notes': $value = sanitize_textarea_field(DataRedactor::text((string) ($value ?? ''))); break;
                case 'metadata_json':
                    if (isset($data['metadata']) && is_array($data['metadata'])) { $value = wp_json_encode(DataRedactor::payload($data['metadata'])); }
                    else { $value = is_string($value) ? $value : ''; }
                    break;
                default: $value = sanitize_text_field((string) ($value ?? ''));
            }
            if (!$include_defaults && $value === null && !array_key_exists($key, $data)) { continue; }
            $row[$key] = $value;
        }
        return $row;
    }

    private function where(array $filters): array
    {
        global $wpdb;
        $where = [];
        $params = [];
        $status = sanitize_key((string) ($filters['status'] ?? ''));
        if ($status !== '' && $status !== 'all') { $where[] = 'status=%s'; $params[] = $status; }
        $booking_id = absint($filters['booking_id'] ?? 0);
        if ($booking_id > 0) { $where[] = 'booking_id=%d'; $params[] = $booking_id; }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = '(invoice_number LIKE %s OR customer_email LIKE %s OR customer_name LIKE %s)';
            array_push($params, $like, $like, $like);
        }
        return [empty($where) ? '' : 'WHERE ' . implode(' AND ', $where), $params];
    }
}
