<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Infrastructure\Repositories\ActivityLogRepository;
use Slotera\Application\Security\DataRedactor;

if (!defined('ABSPATH')) { exit; }

final class LogsPage
{
    public function render(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::VIEW_LOGS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        $this->renderPage(false);
    }

    public function renderEmbedded(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::VIEW_LOGS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }
        $this->renderPage(true);
    }

    public function redirectLegacy(): void
    {
        wp_safe_redirect(add_query_arg(['page' => 'slotera-diagnostics', 'tab' => 'logs'], admin_url('admin.php')));
        exit;
    }

    private function renderPage(bool $embedded): void
    {
        $repo = new ActivityLogRepository();
        $filters = [
            'status' => isset($_GET['status']) ? sanitize_key((string) wp_unslash($_GET['status'])) : 'all',
            'gateway' => isset($_GET['gateway']) ? sanitize_key((string) wp_unslash($_GET['gateway'])) : 'all',
            'error' => isset($_GET['error']) ? sanitize_key((string) wp_unslash($_GET['error'])) : '',
            'search' => isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '',
        ];
        $page = max(1, absint(wp_unslash((string) ($_GET['paged'] ?? 1))));
        $per_page = 25;
        $offset = ($page - 1) * $per_page;
        $logs = $repo->search($filters, $per_page, $offset);
        $total = $repo->count($filters);
        $gateways = array_unique(array_merge(['manual','bank_transfer','stripe','paypal','mollie'], $repo->gateways()));

        if (!$embedded) { echo '<div class="wrap sltr-admin">'; }
        echo '<h2>Logs</h2>';
        $this->render_filters($filters, $gateways);
        $this->render_table($logs);
        $this->render_pagination($page, $per_page, $total, $filters);
        if (!$embedded) { echo '</div>'; }
    }

    private function render_filters(array $filters, array $gateways): void
    {
        echo '<form method="get" style="margin:16px 0;padding:14px;background:#fff;border:1px solid #dcdcde;">';
        echo '<input type="hidden" name="page" value="slotera-diagnostics"><input type="hidden" name="tab" value="logs">';
        echo '<label style="margin-right:12px;">Status <select name="status">';
        foreach (['all'=>'All','info'=>'Info','success'=>'Success','warning'=>'Warning','error'=>'Error'] as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected((string) $filters['status'], $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label>';

        echo '<label style="margin-right:12px;">Gateway <select name="gateway">';
        echo '<option value="all" ' . selected((string) $filters['gateway'], 'all', false) . '>All</option>';
        foreach ($gateways as $gateway) {
            $gateway = sanitize_key((string) $gateway);
            if ($gateway === '') { continue; }
            echo '<option value="' . esc_attr($gateway) . '" ' . selected((string) $filters['gateway'], $gateway, false) . '>' . esc_html($gateway) . '</option>';
        }
        echo '</select></label>';

        echo '<label style="margin-right:12px;"><input type="checkbox" name="error" value="1" ' . checked((string) $filters['error'], '1', false) . '> Errors only</label>';
        echo '<input type="search" name="s" value="' . esc_attr((string) $filters['search']) . '" placeholder="Search event, message, payload" style="min-width:260px;margin-right:8px;">';
        submit_button(__('Filter', 'slotera-booking'), 'secondary', '', false);
        echo ' <a class="button" href="' . esc_url(admin_url('admin.php?page=slotera-diagnostics&tab=logs')) . '">Reset</a>';
        echo '</form>';
    }

    private function render_table(array $logs): void
    {
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th style="width:150px;">Date</th><th style="width:100px;">Status</th><th style="width:150px;">Event</th><th style="width:110px;">Gateway</th><th>Message</th><th style="width:120px;">Object</th><th>Error / Payload</th>';
        echo '</tr></thead><tbody>';
        if (empty($logs)) {
            echo '<tr><td colspan="7">No logs found.</td></tr>';
        }
        foreach ($logs as $log) {
            $payload = json_decode((string) ($log['payload_json'] ?? ''), true);
            $payload = is_array($payload) ? DataRedactor::payload($payload) : [];
            $payload_preview = is_array($payload) && !empty($payload) ? wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '';
            echo '<tr>';
            echo '<td>' . esc_html((string) ($log['created_at'] ?? '')) . '</td>';
            echo '<td>' . $this->status_badge((string) ($log['status'] ?? 'info')) . '</td>';
            echo '<td><code>' . esc_html((string) ($log['event'] ?? '')) . '</code></td>';
            echo '<td>' . (!empty($log['gateway']) ? '<code>' . esc_html((string) $log['gateway']) . '</code>' : '—') . '</td>';
            echo '<td>' . esc_html(DataRedactor::text((string) ($log['message'] ?? ''))) . '</td>';
            echo '<td>' . esc_html((string) ($log['object_type'] ?? '')) . ' #' . esc_html((string) ($log['object_id'] ?? '0')) . '</td>';
            echo '<td>';
            if (!empty($log['error_message'])) { echo '<div style="color:#b91c1c;font-weight:600;">' . esc_html(DataRedactor::text((string) $log['error_message'])) . '</div>'; }
            if ($payload_preview !== '') {
                echo '<details><summary>Payload</summary><pre style="max-width:520px;max-height:220px;overflow:auto;background:#f6f7f7;padding:8px;">' . esc_html($payload_preview) . '</pre></details>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    private function render_pagination(int $page, int $per_page, int $total, array $filters): void
    {
        $pages = (int) ceil($total / $per_page);
        if ($pages <= 1) { return; }
        $base = admin_url('admin.php?page=slotera-diagnostics&tab=logs');
        $args = array_filter([
            'status' => $filters['status'] !== 'all' ? $filters['status'] : null,
            'gateway' => $filters['gateway'] !== 'all' ? $filters['gateway'] : null,
            'error' => $filters['error'] === '1' ? '1' : null,
            's' => $filters['search'] !== '' ? $filters['search'] : null,
        ]);
        echo '<p class="tablenav-pages">';
        echo esc_html(sprintf('%d items', $total)) . ' ';
        if ($page > 1) {
            echo '<a class="button" href="' . esc_url(add_query_arg(array_merge($args, ['paged' => $page - 1]), $base)) . '">‹ Previous</a> ';
        }
        echo '<span style="margin:0 8px;">Page ' . esc_html((string) $page) . ' of ' . esc_html((string) $pages) . '</span>';
        if ($page < $pages) {
            echo '<a class="button" href="' . esc_url(add_query_arg(array_merge($args, ['paged' => $page + 1]), $base)) . '">Next ›</a>';
        }
        echo '</p>';
    }

    private function status_badge(string $status): string
    {
        $colors = ['success'=>'#15803d','warning'=>'#a16207','error'=>'#b91c1c','info'=>'#2563eb'];
        $color = $colors[$status] ?? '#2563eb';
        return '<strong style="color:' . esc_attr($color) . ';">' . esc_html(ucfirst($status)) . '</strong>';
    }
}
