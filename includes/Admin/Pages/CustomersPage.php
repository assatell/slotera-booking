<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class CustomersPage
{
    public function render(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_BOOKINGS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        wp_safe_redirect(admin_url('admin.php?page=slotera-bookings&tab=customers'));
        exit;
    }

    public function render_embedded(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_BOOKINGS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        global $wpdb;
        $table = Database::bookings_table();
        $search = isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '';
        $paged = max(1, absint(wp_unslash((string) ($_GET['paged'] ?? 1))));
        $per_page = 30;
        $offset = ($paged - 1) * $per_page;

        $where = "customer_email <> ''";
        $args = [];
        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= ' AND (customer_email LIKE %s OR customer_name LIKE %s OR customer_phone LIKE %s)';
            $args = [$like, $like, $like];
        }

        $count_sql = "SELECT COUNT(*) FROM (SELECT customer_email FROM {$table} WHERE {$where} GROUP BY customer_email) customers";
        $total = (int) ($args ? $wpdb->get_var($wpdb->prepare($count_sql, $args)) : $wpdb->get_var($count_sql));

        $sql = "SELECT customer_email,
                       MAX(customer_name) AS customer_name,
                       MAX(customer_phone) AS customer_phone,
                       COUNT(*) AS bookings_count,
                       MAX(created_at) AS last_booking_at,
                       SUM(CASE WHEN status IN ('confirmed') THEN 1 ELSE 0 END) AS active_bookings
                FROM {$table}
                WHERE {$where}
                GROUP BY customer_email
                ORDER BY last_booking_at DESC
                LIMIT %d OFFSET %d";
        $query_args = array_merge($args, [$per_page, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($sql, $query_args), ARRAY_A) ?: [];
        $total_pages = max(1, (int) ceil($total / $per_page));

        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/customers.php';
    }
}
