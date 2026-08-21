<?php
if (!defined('ABSPATH')) { exit; }
$summary = $diagnostics['summary'] ?? [];
$groups = $diagnostics['checks'] ?? [];
$status_labels = [
    'ok' => __('OK', 'slotera-booking'),
    'warning' => __('Warning', 'slotera-booking'),
    'critical' => __('Critical', 'slotera-booking'),
    'info' => __('Info', 'slotera-booking'),
];
$embedded = isset($embedded) ? (bool) $embedded : false;
?>
<?php if (!$embedded): ?><div class="wrap sltr-admin sltr-diagnostics"><?php else: ?><div class="sltr-admin sltr-diagnostics"><?php endif; ?>
    <h2><?php esc_html_e('Diagnostics', 'slotera-booking'); ?></h2>

    <?php if (isset($_GET['sltr_paypal_reconciled'])) : ?>
        <div class="notice notice-success inline"><p>
            <?php echo esc_html(sprintf(
                __('PayPal reconciliation finished: checked %1$d, completed %2$d, pending %3$d, failed %4$d, errors %5$d.', 'slotera-booking'),
                absint($_GET['checked'] ?? 0),
                absint($_GET['completed'] ?? 0),
                absint($_GET['pending'] ?? 0),
                absint($_GET['failed'] ?? 0),
                absint($_GET['errors'] ?? 0)
            )); ?>
        </p></div>
    <?php endif; ?>

    <div class="sltr-cards sltr-health-summary">
        <?php foreach (['ok', 'warning', 'critical', 'info'] as $status) : ?>
            <div class="sltr-card sltr-status-card sltr-status-<?php echo esc_attr($status); ?>">
                <span><?php echo esc_html(sprintf('%s checks', $status_labels[$status])); ?></span>
                <strong><?php echo esc_html((string) ($summary[$status] ?? 0)); ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <p>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=slotera-diagnostics&tab=diagnostics')); ?>"><?php esc_html_e('Refresh checks', 'slotera-booking'); ?></a>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=slotera-settings')); ?>"><?php esc_html_e('Open settings', 'slotera-booking'); ?></a>
    </p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin: 0 0 16px;">
        <input type="hidden" name="action" value="sltr_paypal_reconcile_now">
        <?php wp_nonce_field('sltr_paypal_reconcile_now'); ?>
        <button type="submit" class="button"><?php esc_html_e('Reconcile PayPal now', 'slotera-booking'); ?></button>
        <span class="description"><?php esc_html_e('Checks eligible PayPal processing captures against the PayPal API immediately.', 'slotera-booking'); ?></span>
    </form>

    <?php foreach ($groups as $group_key => $checks) : ?>
        <section class="sltr-panel sltr-health-panel">
            <h2><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $group_key))); ?></h2>
            <table class="widefat striped sltr-health-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Check', 'slotera-booking'); ?></th>
                        <th><?php esc_html_e('Status', 'slotera-booking'); ?></th>
                        <th><?php esc_html_e('Details', 'slotera-booking'); ?></th>
                        <th><?php esc_html_e('Recommendation', 'slotera-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ((array) $checks as $check) : $status = (string) ($check['status'] ?? 'info'); ?>
                    <tr>
                        <td><strong><?php echo esc_html((string) ($check['label'] ?? '')); ?></strong></td>
                        <td><span class="sltr-badge sltr-badge-<?php echo esc_attr($status); ?>"><?php echo esc_html($status_labels[$status] ?? $status); ?></span></td>
                        <td><?php echo esc_html((string) ($check['detail'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($check['recommendation'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endforeach; ?>


    <?php if (!empty($observability_events)) : ?>
        <section class="sltr-panel sltr-health-panel">
            <h2><?php esc_html_e('Recent observability events', 'slotera-booking'); ?></h2>
            <p><?php esc_html_e('Production-safe diagnostic events with redacted payloads and correlation/request IDs.', 'slotera-booking'); ?></p>
            <table class="widefat striped sltr-health-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Time', 'slotera-booking'); ?></th>
                        <th><?php esc_html_e('Level', 'slotera-booking'); ?></th>
                        <th><?php esc_html_e('Event', 'slotera-booking'); ?></th>
                        <th><?php esc_html_e('Message', 'slotera-booking'); ?></th>
                        <th><?php esc_html_e('Request ID', 'slotera-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array) $observability_events as $event) :
                        $payload = json_decode((string) ($event['payload_json'] ?? '{}'), true);
                        $request_id = is_array($payload) ? (string) ($payload['request_id'] ?? '') : '';
                    ?>
                        <tr>
                            <td><?php echo esc_html((string) ($event['created_at'] ?? '')); ?></td>
                            <td><span class="sltr-badge sltr-badge-<?php echo esc_attr((string) ($event['status'] ?? 'info')); ?>"><?php echo esc_html((string) ($event['status'] ?? 'info')); ?></span></td>
                            <td><code><?php echo esc_html((string) ($event['event'] ?? '')); ?></code></td>
                            <td><?php echo esc_html((string) ($event['message'] ?? '')); ?></td>
                            <td><code><?php echo esc_html($request_id); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</div>
