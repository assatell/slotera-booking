<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\ActivityLogRepository;
use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class DashboardService
{
    private BookingRepository $b;
    private ActivityLogRepository $log;

    public function __construct(?BookingRepository $b = null, ?ActivityLogRepository $log = null)
    {
        $this->b = $b ?: new BookingRepository();
        $this->log = $log ?: new ActivityLogRepository();
    }

    /** @return string[] */
    private function active_statuses(): array
    {
        return function_exists('sltr_active_booking_statuses') ? \sltr_active_booking_statuses() : ['confirmed'];
    }

    /** @return array{0:string,1:string[]} */
    private function active_statuses_sql(): array
    {
        $statuses = array_values(array_filter(array_map('sanitize_key', $this->active_statuses())));
        if ($statuses === []) {
            $statuses = ['confirmed'];
        }

        return [implode(',', array_fill(0, count($statuses), '%s')), $statuses];
    }

    public function get_dashboard_data(int $limit = 10): array
    {
        return PerformanceProfiler::time('admin.dashboard.get_dashboard_data', function () use ($limit): array {
            return [
            'overview' => $this->overview(),
            'dashboard_sections' => $this->dashboard_sections($limit),
            'upcoming_bookings' => $this->b->get_upcoming($limit),
            'recent_activity' => $this->log->recent($limit),
            'requires_attention' => $this->log->get_errors($limit),
            'payments_snapshot' => [
                'paid' => $this->count_payment('paid'),
                'pending' => $this->count_payment('pending'),
                'unpaid' => $this->count_payment('unpaid'),
                'partial' => $this->count_payment('partial'),
                'failed' => $this->count_payment('failed'),
                'refunded' => $this->count_payment('refunded'),
            ],
            'payment_log' => $this->log->get_by_events(['payment_pending','payment_pending','payment_paid','payment_completed','payment_failed','payment_partial','payment_unpaid'], $limit),
            'cancellation_log' => $this->log->get_by_events(['booking_cancelled'], $limit),
            'email_log' => $this->log->get_by_events(['email_sent','email_failed'], $limit),
            ];
        });
    }

    private function dashboard_sections(int $limit): array
    {
        return [
            'today_bookings' => [
                'title' => sltr__('admin.dashboard.today_bookings'),
                'empty' => sltr__('admin.dashboard.no_matching_bookings'),
                'rows' => $this->bookings_for('today_bookings', $limit),
            ],
            'upcoming_bookings' => [
                'title' => sltr__('admin.dashboard.upcoming_bookings'),
                'empty' => sltr__('admin.dashboard.no_upcoming_bookings'),
                'rows' => $this->bookings_for('upcoming_bookings', $limit),
            ],
            'pending_payments' => [
                'title' => sltr__('admin.dashboard.pending_payments'),
                'empty' => sltr__('admin.dashboard.no_matching_bookings'),
                'rows' => $this->bookings_for('pending_payments', $limit),
            ],
            'cancelled_bookings' => [
                'title' => sltr__('admin.dashboard.cancelled'),
                'empty' => sltr__('admin.dashboard.no_matching_bookings'),
                'rows' => $this->bookings_for('cancelled_bookings', $limit),
            ],
            'paid_revenue' => [
                'title' => sltr__('admin.dashboard.revenue'),
                'empty' => sltr__('admin.dashboard.no_matching_bookings'),
                'type' => 'revenue',
                'total' => $this->revenue(),
                'rows' => $this->bookings_for('paid_revenue', $limit),
            ],
        ];
    }

    private function bookings_for(string $section, int $limit): array
    {
        global $wpdb;
        $table = Database::bookings_table();
        $limit = max(1, min(100, $limit));
        $today = current_time('Y-m-d');

        switch ($section) {
            case 'today_bookings':
                $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE booking_date=%s ORDER BY start_time ASC, id DESC LIMIT %d", $today, $limit);
                break;
            case 'pending_payments':
                $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE payment_status IN ('unpaid','pending','processing','failed') ORDER BY booking_date ASC, start_time ASC, id DESC LIMIT %d", $limit);
                break;
            case 'cancelled_bookings':
                $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE status=%s ORDER BY COALESCE(cancelled_at, updated_at, created_at) DESC, id DESC LIMIT %d", 'cancelled', $limit);
                break;
            case 'paid_revenue':
                $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE payment_status IN ('paid','partial') AND status <> %s ORDER BY COALESCE(paid_at, updated_at, created_at) DESC, id DESC LIMIT %d", 'cancelled', $limit);
                break;
            case 'upcoming_bookings':
            default:
                [$status_placeholders, $status_args] = $this->active_statuses_sql();
                $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE booking_date >= %s AND status IN ({$status_placeholders}) ORDER BY booking_date ASC, start_time ASC LIMIT %d", array_merge([$today], $status_args, [$limit]));
                break;
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    private function overview(): array
    {
        return [
            'today_bookings' => $this->count_today(),
            'upcoming_bookings' => count($this->b->get_upcoming(999, 0)),
            'pending_payments' => $this->count_payments(['unpaid','pending','processing','failed']),
            'cancelled_bookings' => $this->count_status('cancelled'),
            'paid_revenue' => $this->revenue(),
        ];
    }

    private function count_today(): int
    {
        global $wpdb;
        $t = Database::bookings_table();
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE booking_date=%s", current_time('Y-m-d')));
    }

    private function count_status(string $s): int
    {
        global $wpdb;
        $t = Database::bookings_table();
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE status=%s", $s));
    }

    private function count_payment(string $s): int
    {
        global $wpdb;
        $t = Database::bookings_table();
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE payment_status=%s", $s));
    }

    private function count_payments(array $statuses): int
    {
        global $wpdb;
        $t = Database::bookings_table();
        $statuses = array_values(array_filter(array_map('sanitize_key', $statuses)));
        if (empty($statuses)) { return 0; }
        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE payment_status IN ({$placeholders})", $statuses));
    }

    private function revenue(): float
    {
        global $wpdb;
        $t = Database::bookings_table();
        return (float) $wpdb->get_var("SELECT COALESCE(SUM(CASE WHEN paid_amount > 0 THEN paid_amount ELSE total_amount END),0) FROM {$t} WHERE payment_status IN ('paid','partial') AND status <> 'cancelled'");
    }
}
