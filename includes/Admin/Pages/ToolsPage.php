<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class ToolsPage
{
    public function render(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_TOOLS)) { return; }
        $this->renderPage(false);
    }

    public function renderEmbedded(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_TOOLS)) { return; }
        $this->renderPage(true);
    }

    public function redirectLegacy(): void
    {
        wp_safe_redirect(add_query_arg(['page' => 'slotera-diagnostics', 'tab' => 'tools'], admin_url('admin.php')));
        exit;
    }

    private function renderPage(bool $embedded): void
    {
        $result = get_transient('sltr_tools_result_' . get_current_user_id());
        if ($result !== false) { delete_transient('sltr_tools_result_' . get_current_user_id()); }
        ?>
        <?php if (!$embedded): ?><div class="wrap sltr-admin-wrap"><?php endif; ?>
            <h2><?php echo esc_html__('Tools', 'slotera-booking'); ?></h2>
            <div class="sltr-admin-help-card">
                <strong><?php echo esc_html__('Maintenance and diagnostics', 'slotera-booking'); ?></strong>
                <p><?php echo esc_html__('Use these tools for safe cleanup, reconciliation, data rebuilds, CSV transfer, webhook testing and system checks. Destructive or risky actions validate data before changing bookings.', 'slotera-booking'); ?></p>
            </div>

            <?php if (is_array($result)): ?>
                <div class="notice notice-<?php echo esc_attr(($result['status'] ?? '') === 'error' ? 'error' : (($result['status'] ?? '') === 'warning' ? 'warning' : 'success')); ?> is-dismissible">
                    <p><strong><?php echo esc_html((string) ($result['message'] ?? __('Done.', 'slotera-booking'))); ?></strong></p>
                    <?php $this->render_result_details($result); ?>
                </div>
            <?php endif; ?>

            <div class="sltr-tools-layout">
                <div class="sltr-tools-layout__status">
                    <?php $this->render_status(); ?>
                </div>
                <div class="sltr-tools-layout__actions">
                    <?php $this->render_maintenance(); ?>
                    <?php $this->render_rebuild(); ?>
                    <?php $this->render_export_import(); ?>
                </div>
            </div>
        <?php if (!$embedded): ?></div><?php endif; ?>
        <?php
    }

    private function render_maintenance(): void
    {
        ?>
        <div class="sltr-card">
            <h2><?php echo esc_html__('Maintenance', 'slotera-booking'); ?></h2>
            <p><?php echo esc_html__('Run cleanup jobs and prune old operational logs.', 'slotera-booking'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="sltr_tools_prune_logs">
                <?php wp_nonce_field('sltr_tools_prune_logs'); ?>
                <label><?php echo esc_html__('Delete successful/permanent logs older than', 'slotera-booking'); ?>
                    <input type="number" name="days" value="90" min="7" max="3650" style="width:90px;"> <?php echo esc_html__('days', 'slotera-booking'); ?>
                </label>
                <p><button class="button" type="submit"><?php echo esc_html__('Prune logs', 'slotera-booking'); ?></button></p>
            </form>
        </div>
        <?php
    }

    private function render_rebuild(): void
    {
        ?>
        <div class="sltr-card">
            <h2><?php echo esc_html__('Rebuild data', 'slotera-booking'); ?></h2>
            <p><?php echo esc_html__('Repair derived booking data after imports, migrations, or manual database changes.', 'slotera-booking'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="sltr_tools_rebuild_hashes">
                <?php wp_nonce_field('sltr_tools_rebuild_hashes'); ?>
                <label><?php echo esc_html__('Batch size', 'slotera-booking'); ?>
                    <input type="number" name="limit" value="500" min="25" max="2000" style="width:90px;">
                </label>
                <p><button class="button button-primary" type="submit"><?php echo esc_html__('Rebuild active slot hashes', 'slotera-booking'); ?></button></p>
            </form>
            <p class="description"><?php echo esc_html__('Runs a safe batch. Repeat if you have a very large bookings table.', 'slotera-booking'); ?></p>
        </div>
        <?php
    }

    private function render_export_import(): void
    {
        ?>
        <div class="sltr-card">
            <h2><?php echo esc_html__('Export / Import', 'slotera-booking'); ?></h2>
            <h3><?php echo esc_html__('Export bookings CSV', 'slotera-booking'); ?></h3>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="sltr_tools_export_bookings">
                <?php wp_nonce_field('sltr_tools_export_bookings'); ?>
                <p>
                    <label><?php echo esc_html__('Status', 'slotera-booking'); ?>
                        <select name="status">
                            <option value=""><?php echo esc_html__('Any', 'slotera-booking'); ?></option>
                            <?php foreach ((function_exists('sltr_booking_statuses') ? sltr_booking_statuses() : ['confirmed','cancelled','completed']) as $status): ?>
                                <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </p>
                <p>
                    <label><?php echo esc_html__('From', 'slotera-booking'); ?> <input type="date" name="from"></label>
                    <label style="margin-left:8px;"><?php echo esc_html__('To', 'slotera-booking'); ?> <input type="date" name="to"></label>
                </p>
                <button class="button" type="submit"><?php echo esc_html__('Download CSV', 'slotera-booking'); ?></button>
            </form>
            <hr>
            <h3><?php echo esc_html__('Safe bookings CSV import', 'slotera-booking'); ?></h3>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="sltr_tools_import_bookings">
                <?php wp_nonce_field('sltr_tools_import_bookings'); ?>
                <input type="file" name="bookings_csv" accept=".csv,text/csv" required>
                <p><button class="button button-primary" type="submit"><?php echo esc_html__('Validate CSV', 'slotera-booking'); ?></button></p>
                <p class="description"><?php echo esc_html__('Safe import always validates first. It never imports paid/refunded payment states, never updates existing bookings, never bypasses availability, and rejects duplicates with a reason.', 'slotera-booking'); ?></p>
                <p class="description"><?php echo esc_html__('Uploads are limited to CSV files under 2 MB and 1000 rows. The final import runs as an all-or-nothing commit; if a row fails the second validation pass, no rows are imported.', 'slotera-booking'); ?></p>
                <p class="description"><?php echo esc_html__('Required columns: package_id, customer_name, customer_email, booking_date, start_time, end_time. Date range inventory also requires end_date and resource_id.', 'slotera-booking'); ?></p>
            </form>
        </div>
        <?php
    }

    private function render_status(): void
    {
        global $wpdb;
        $tables = [
            'packages' => Database::packages_table(),
            'bookings' => Database::bookings_table(),
            'activity_log' => Database::activity_log_table(),
            'webhook_deliveries' => Database::outgoing_webhook_deliveries_table(),
            'email_queue' => Database::email_queue_table(),
        ];
        $cron_health = class_exists('\\Slotera\\Application\\Services\\CronResilienceService') ? \Slotera\Application\Services\CronResilienceService::health() : [];
        ?>
        <div class="sltr-card">
            <h2><?php echo esc_html__('System status', 'slotera-booking'); ?></h2>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:12px 0;">
                <input type="hidden" name="action" value="sltr_tools_save_profiling">
                <?php wp_nonce_field('sltr_tools_save_profiling'); ?>
                <label><input type="checkbox" name="sltr_profiling_mode" value="1" <?php checked((int) get_option('sltr_profiling_mode', 0), 1); ?>> <?php echo esc_html__('Enable admin performance profiling', 'slotera-booking'); ?></label>
                <p class="description"><?php echo esc_html__('Admin/dev only. Logs Slotera operations slower than 300ms and shows SQL counters in diagnostics.', 'slotera-booking'); ?></p>
                <button class="button" type="submit"><?php echo esc_html__('Save profiling mode', 'slotera-booking'); ?></button>
            </form>
            <table class="widefat striped"><tbody>
                <tr><th><?php echo esc_html__('Slotera version', 'slotera-booking'); ?></th><td><?php echo esc_html(defined('SLTR_VERSION') ? SLTR_VERSION : 'unknown'); ?></td></tr>
                <tr><th><?php echo esc_html__('WordPress', 'slotera-booking'); ?></th><td><?php echo esc_html(get_bloginfo('version')); ?></td></tr>
                <tr><th><?php echo esc_html__('PHP', 'slotera-booking'); ?></th><td><?php echo esc_html(PHP_VERSION); ?></td></tr>
                <tr><th><?php echo esc_html__('Database', 'slotera-booking'); ?></th><td><?php echo esc_html($wpdb->db_version()); ?></td></tr>
                <tr><th><?php echo esc_html__('WP-Cron disabled', 'slotera-booking'); ?></th><td><?php echo defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? esc_html__('Yes', 'slotera-booking') : esc_html__('No', 'slotera-booking'); ?></td></tr>
            </tbody></table>
            <h3><?php echo esc_html__('Tables', 'slotera-booking'); ?></h3>
            <table class="widefat striped"><tbody>
                <?php foreach ($tables as $label => $table): $exists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table); ?>
                    <tr><th><?php echo esc_html($label); ?></th><td><?php echo $exists ? esc_html__('OK', 'slotera-booking') : esc_html__('Missing', 'slotera-booking'); ?><?php if ($exists): ?> — <?php echo esc_html((string) (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $table)); ?> <?php echo esc_html__('rows', 'slotera-booking'); ?><?php endif; ?></td></tr>
                <?php endforeach; ?>
            </tbody></table>
            <h3><?php echo esc_html__('Cron resilience', 'slotera-booking'); ?></h3>
            <table class="widefat striped">
                <thead><tr><th><?php echo esc_html__('Job', 'slotera-booking'); ?></th><th><?php echo esc_html__('Next run', 'slotera-booking'); ?></th><th><?php echo esc_html__('Last success', 'slotera-booking'); ?></th><th><?php echo esc_html__('Status', 'slotera-booking'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($cron_health as $state): $next = (int) ($state['next_run'] ?? 0); ?>
                    <tr>
                        <th><?php echo esc_html((string) ($state['label'] ?? $state['hook'] ?? '')); ?></th>
                        <td><?php echo $next > 0 ? esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $next)) : esc_html__('Not scheduled', 'slotera-booking'); ?></td>
                        <td><?php echo esc_html((string) ($state['last_success_at'] ?? '—')); ?></td>
                        <td><?php echo !empty($state['locked']) ? esc_html__('Running / locked', 'slotera-booking') : esc_html((string) ($state['last_status'] ?? 'waiting')); ?><?php if (!empty($state['last_error'])): ?> — <?php echo esc_html((string) $state['last_error']); ?><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
                <input type="hidden" name="action" value="sltr_tools_preview_due_cron">
                <?php wp_nonce_field('sltr_tools_preview_due_cron'); ?>
                <button class="button" type="submit"><?php echo esc_html__('Preview due Slotera cron jobs', 'slotera-booking'); ?></button>
                <p class="description"><?php echo esc_html__('Dry-run only: shows which Slotera cron jobs are due and what they are expected to process. Nothing is executed from this button.', 'slotera-booking'); ?></p>
            </form>
        </div>
        <?php
    }

    private function render_result_details(array $result): void
    {
        if (($result['type'] ?? '') === 'safe_import_preview' && !empty($result['import_key']) && (int) ($result['valid_rows'] ?? 0) > 0) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:8px 0 12px;">';
            echo '<input type="hidden" name="action" value="sltr_tools_import_bookings_commit">';
            echo '<input type="hidden" name="import_key" value="' . esc_attr((string) $result['import_key']) . '">';
            wp_nonce_field('sltr_tools_import_bookings_commit');
            echo '<button class="button button-primary" type="submit">' . esc_html__('Import valid rows only', 'slotera-booking') . '</button> ';
            echo '<span class="description">' . esc_html__('Validation is repeated before insert, so rows can still be rejected if availability changed.', 'slotera-booking') . '</span>';
            echo '</form>';
        }

        if (($result['type'] ?? '') === 'cron_dry_run_preview') {
            $jobs = is_array($result['jobs'] ?? null) ? $result['jobs'] : [];
            if ($jobs !== []) {
                echo '<table class="widefat striped" style="margin:8px 0 12px;"><thead><tr>';
                echo '<th>' . esc_html__('Job', 'slotera-booking') . '</th>';
                echo '<th>' . esc_html__('Due since', 'slotera-booking') . '</th>';
                echo '<th>' . esc_html__('Estimate', 'slotera-booking') . '</th>';
                echo '<th>' . esc_html__('Status', 'slotera-booking') . '</th>';
                echo '</tr></thead><tbody>';
                foreach ($jobs as $job) {
                    $next = (int) ($job['next_run'] ?? 0);
                    $estimate = is_array($job['estimate'] ?? null) ? $job['estimate'] : [];
                    $count = array_key_exists('count', $estimate) && $estimate['count'] !== null ? (string) (int) $estimate['count'] : '—';
                    echo '<tr>';
                    echo '<th>' . esc_html((string) ($job['label'] ?? $job['hook'] ?? '')) . '</th>';
                    echo '<td>' . ($next > 0 ? esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $next)) : esc_html__('Unknown', 'slotera-booking')) . '</td>';
                    echo '<td><strong>' . esc_html((string) ($estimate['label'] ?? __('Items', 'slotera-booking'))) . ': ' . esc_html($count) . '</strong><br><span class="description">' . esc_html((string) ($estimate['detail'] ?? '')) . '</span></td>';
                    echo '<td>' . (!empty($job['locked']) ? esc_html__('Locked/running — will be skipped', 'slotera-booking') : esc_html__('Ready to run', 'slotera-booking')) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:8px 0 12px;">';
                echo '<input type="hidden" name="action" value="sltr_tools_run_due_cron">';
                wp_nonce_field('sltr_tools_run_due_cron');
                echo '<button class="button button-primary" type="submit">' . esc_html__('Run these due Slotera cron jobs now', 'slotera-booking') . '</button> ';
                echo '<span class="description">' . esc_html__('Executes only jobs that are still due at click time; locked jobs are skipped.', 'slotera-booking') . '</span>';
                echo '</form>';
            }
        }

        $skip = ['message', 'import_key', 'jobs'];
        echo '<ul style="margin-left:18px;list-style:disc;">';
        foreach ($result as $key => $value) {
            if (in_array((string) $key, $skip, true)) { continue; }
            if ($key === 'errors' && is_array($value)) {
                echo '<li><code>' . esc_html((string) $key) . '</code>:';
                echo '<ol style="margin:6px 0 0 18px;">';
                foreach (array_slice($value, 0, 25) as $error) {
                    if (is_array($error)) {
                        $row = isset($error['row']) ? sprintf(__('Row %d', 'slotera-booking'), (int) $error['row']) : __('Row', 'slotera-booking');
                        $reason = (string) ($error['reason'] ?? '');
                        echo '<li>' . esc_html($row . ': ' . $reason) . '</li>';
                    } else {
                        echo '<li>' . esc_html((string) $error) . '</li>';
                    }
                }
                echo '</ol></li>';
                continue;
            }
            if (is_array($value)) { $value = wp_json_encode($value); }
            echo '<li><code>' . esc_html((string) $key) . '</code>: ' . esc_html((string) $value) . '</li>';
        }
        echo '</ul>';
    }

}
