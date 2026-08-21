<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

/**
 * Production booking analytics without external trackers.
 *
 * The first analytics layer intentionally reads from Slotera's own booking data so
 * it is privacy-friendly and works even when GA/Meta pixels are not configured.
 */
final class AnalyticsService
{
    /** @return array<string,mixed> */
    public function report(string $period = '30'): array
    {
        global $wpdb;

        $period = in_array($period, ['7', '30', '90', '365', 'all'], true) ? $period : '30';
        $where = '1=1';
        $params = [];
        if ($period !== 'all') {
            $where = 'created_at >= %s';
            $params[] = gmdate('Y-m-d H:i:s', time() - ((int) $period * DAY_IN_SECONDS));
        }

        $bookings = Database::bookings_table();
        $packages = Database::packages_table();
        $visitor_events = Database::visitor_events_table();

        $sql_where = $params ? $wpdb->prepare($where, $params) : $where;
        $booking_sql_where = $period === 'all' ? '1=1' : $wpdb->prepare('b.created_at >= %s', $params[0] ?? gmdate('Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS));

        $revenue_amount = "COALESCE(NULLIF(gross_amount,0), total_amount, 0)";
        $active_statuses = "('pending','pending_payment','confirmed','completed')";
        $confirmed_statuses = "('confirmed','completed')";

        $totals = $wpdb->get_row("SELECT
                COUNT(*) AS total_bookings,
                SUM(CASE WHEN status IN {$active_statuses} THEN 1 ELSE 0 END) AS booking_count,
                SUM(CASE WHEN status IN {$confirmed_statuses} THEN 1 ELSE 0 END) AS successful_bookings,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled_bookings,
                SUM(CASE WHEN payment_status IN ('paid','partially_paid','partial') THEN 1 ELSE 0 END) AS paid_bookings,
                COALESCE(SUM(CASE WHEN status IN {$active_statuses} THEN {$revenue_amount} ELSE 0 END),0) AS expected_revenue,
                COALESCE(SUM(CASE WHEN status IN {$confirmed_statuses} THEN {$revenue_amount} ELSE 0 END),0) AS confirmed_revenue,
                COALESCE(SUM(CASE WHEN payment_status IN ('paid','partially_paid','partial') THEN paid_amount ELSE 0 END),0) AS paid_revenue,
                COALESCE(AVG(CASE WHEN status IN {$active_statuses} THEN {$revenue_amount} ELSE NULL END),0) AS average_booking_value
            FROM {$bookings}
            WHERE {$sql_where}", ARRAY_A) ?: [];

        $services = $wpdb->get_results("SELECT
                b.package_id,
                COALESCE(NULLIF(p.title,''), NULLIF(CONCAT('Package #', NULLIF(b.package_id,0)), 'Package #'), 'Unknown service') AS title,
                COUNT(*) AS bookings,
                SUM(CASE WHEN b.status IN ('confirmed','completed') THEN 1 ELSE 0 END) AS successful,
                COALESCE(SUM(CASE WHEN b.status IN ('confirmed','completed') THEN COALESCE(NULLIF(b.gross_amount,0), b.total_amount, 0) ELSE 0 END),0) AS revenue
            FROM {$bookings} b
            LEFT JOIN {$packages} p ON p.id=b.package_id
            WHERE {$booking_sql_where}
            GROUP BY b.package_id, p.title
            ORDER BY successful DESC, bookings DESC
            LIMIT 10", ARRAY_A) ?: [];

        $sources = $wpdb->get_results("SELECT
                COALESCE(NULLIF(source,''),'unknown') AS source,
                COUNT(*) AS bookings,
                SUM(CASE WHEN status IN ('confirmed','completed') THEN 1 ELSE 0 END) AS successful,
                COALESCE(SUM(CASE WHEN status IN ('confirmed','completed') THEN COALESCE(NULLIF(gross_amount,0), total_amount, 0) ELSE 0 END),0) AS revenue
            FROM {$bookings}
            WHERE {$sql_where}
            GROUP BY source
            ORDER BY successful DESC, bookings DESC
            LIMIT 10", ARRAY_A) ?: [];

        $daily = $wpdb->get_results("SELECT
                DATE(created_at) AS day,
                COUNT(*) AS bookings,
                SUM(CASE WHEN status IN ('confirmed','completed') THEN 1 ELSE 0 END) AS successful,
                COALESCE(SUM(CASE WHEN status IN ('confirmed','completed') THEN COALESCE(NULLIF(gross_amount,0), total_amount, 0) ELSE 0 END),0) AS revenue
            FROM {$bookings}
            WHERE {$sql_where}
            GROUP BY DATE(created_at)
            ORDER BY day DESC
            LIMIT 30", ARRAY_A) ?: [];

        $weekday = $wpdb->get_results("SELECT
                DAYOFWEEK(booking_date) AS weekday,
                COUNT(*) AS bookings,
                SUM(CASE WHEN status IN ('confirmed','completed') THEN 1 ELSE 0 END) AS successful
            FROM {$bookings}
            WHERE {$sql_where}
            GROUP BY DAYOFWEEK(booking_date)
            ORDER BY weekday ASC", ARRAY_A) ?: [];


        $visitor_page_where = $period === 'all' ? '1=1' : $wpdb->prepare('created_at >= %s', $params[0] ?? gmdate('Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS));
        $page_views = $wpdb->get_results("SELECT
                page_url,
                COALESCE(NULLIF(page_title,''), page_url) AS page_title,
                page_type,
                package_id,
                COUNT(*) AS views,
                COALESCE(AVG(duration_seconds),0) AS avg_duration_seconds,
                SUM(CASE WHEN booking_created=1 THEN 1 ELSE 0 END) AS bookings,
                SUM(CASE WHEN bounced=1 THEN 1 ELSE 0 END) AS bounces,
                SUM(CASE WHEN exited=1 THEN 1 ELSE 0 END) AS exits
            FROM {$visitor_events}
            WHERE {$visitor_page_where}
            GROUP BY page_url, page_title, page_type, package_id
            ORDER BY views DESC, avg_duration_seconds DESC
            LIMIT 10", ARRAY_A) ?: [];

        $short_views = $wpdb->get_results("SELECT
                page_url,
                COALESCE(NULLIF(page_title,''), page_url) AS page_title,
                page_type,
                COUNT(*) AS views,
                COALESCE(AVG(duration_seconds),0) AS avg_duration_seconds,
                SUM(CASE WHEN bounced=1 THEN 1 ELSE 0 END) AS bounces
            FROM {$visitor_events}
            WHERE {$visitor_page_where}
            GROUP BY page_url, page_title, page_type
            HAVING views >= 1
            ORDER BY avg_duration_seconds ASC, bounces DESC, views DESC
            LIMIT 10", ARRAY_A) ?: [];

        $cities = $wpdb->get_results("SELECT
                COALESCE(NULLIF(city,''),'Unknown city') AS city,
                COALESCE(NULLIF(country,''),'') AS country,
                COUNT(*) AS visits,
                COALESCE(AVG(duration_seconds),0) AS avg_duration_seconds,
                SUM(CASE WHEN booking_created=1 THEN 1 ELSE 0 END) AS bookings
            FROM {$visitor_events}
            WHERE {$visitor_page_where}
            GROUP BY city, country
            ORDER BY visits DESC, bookings DESC
            LIMIT 10", ARRAY_A) ?: [];

        $visitor_totals = $wpdb->get_row("SELECT
                COUNT(*) AS page_views,
                COUNT(DISTINCT NULLIF(session_hash,'')) AS sessions,
                COALESCE(AVG(duration_seconds),0) AS avg_duration_seconds,
                SUM(CASE WHEN bounced=1 THEN 1 ELSE 0 END) AS bounces,
                SUM(CASE WHEN exited=1 THEN 1 ELSE 0 END) AS exits,
                SUM(CASE WHEN page_type='service' AND booking_created=0 THEN 1 ELSE 0 END) AS service_views_without_booking
            FROM {$visitor_events}
            WHERE {$visitor_page_where}", ARRAY_A) ?: [];

        $total = max(0, (int) ($totals['total_bookings'] ?? 0));
        $booking_count = max(0, (int) ($totals['booking_count'] ?? 0));
        $successful = max(0, (int) ($totals['successful_bookings'] ?? 0));

        return [
            'period' => $period,
            'totals' => [
                'total_bookings' => $total,
                'booking_count' => $booking_count,
                'successful_bookings' => $successful,
                'cancelled_bookings' => max(0, (int) ($totals['cancelled_bookings'] ?? 0)),
                'paid_bookings' => max(0, (int) ($totals['paid_bookings'] ?? 0)),
                'expected_revenue' => (float) ($totals['expected_revenue'] ?? 0),
                'confirmed_revenue' => (float) ($totals['confirmed_revenue'] ?? 0),
                'paid_revenue' => (float) ($totals['paid_revenue'] ?? 0),
                'average_booking_value' => (float) ($totals['average_booking_value'] ?? 0),
                'average_order_value' => (float) ($totals['average_booking_value'] ?? 0),
                'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0.0,
            ],
            'funnel' => $this->funnel($total, $successful),
            'services' => $services,
            'sources' => $sources,
            'daily' => array_reverse($daily),
            'weekday' => $this->normalize_weekdays($weekday),
            'visitor_insights' => [
                'totals' => [
                    'page_views' => max(0, (int) ($visitor_totals['page_views'] ?? 0)),
                    'sessions' => max(0, (int) ($visitor_totals['sessions'] ?? 0)),
                    'average_time_on_page' => round((float) ($visitor_totals['avg_duration_seconds'] ?? 0), 1),
                    'service_views_without_booking' => max(0, (int) ($visitor_totals['service_views_without_booking'] ?? 0)),
                    'bounce_rate' => ((int) ($visitor_totals['page_views'] ?? 0)) > 0 ? round(((int) ($visitor_totals['bounces'] ?? 0) / (int) $visitor_totals['page_views']) * 100, 1) : 0.0,
                    'exit_signals' => max(0, (int) ($visitor_totals['exits'] ?? 0)),
                ],
                'top_pages' => $page_views,
                'short_pages' => $short_views,
                'cities' => $cities,
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function funnel(int $total, int $successful): array
    {
        return [
            ['label' => __('Booking attempts', 'slotera-booking'), 'value' => $total, 'rate' => 100.0],
            ['label' => __('Successful bookings', 'slotera-booking'), 'value' => $successful, 'rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0.0],
        ];
    }

    /** @param array<int,array<string,mixed>> $rows @return array<int,array<string,mixed>> */
    private function normalize_weekdays(array $rows): array
    {
        $names = [1 => __('Sunday', 'slotera-booking'), 2 => __('Monday', 'slotera-booking'), 3 => __('Tuesday', 'slotera-booking'), 4 => __('Wednesday', 'slotera-booking'), 5 => __('Thursday', 'slotera-booking'), 6 => __('Friday', 'slotera-booking'), 7 => __('Saturday', 'slotera-booking')];
        $by_day = [];
        foreach ($rows as $row) {
            $by_day[(int) ($row['weekday'] ?? 0)] = $row;
        }
        $out = [];
        foreach ($names as $day => $name) {
            $row = $by_day[$day] ?? [];
            $out[] = ['weekday' => $day, 'label' => $name, 'bookings' => (int) ($row['bookings'] ?? 0), 'successful' => (int) ($row['successful'] ?? 0)];
        }
        return $out;
    }
}
