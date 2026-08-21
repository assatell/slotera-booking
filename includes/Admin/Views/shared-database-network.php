<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
$report = isset($report) && is_array($report) ? $report : [];
$config_line = (string) ($report['config_line'] ?? "define('SLTR_SHARED_TABLE_PREFIX', 'sltr_');");
$config_anchor = (string) ($report['config_anchor'] ?? "/* That's all, stop editing! Happy publishing. */");
$developer_subject = __('Slotera Shared Database Network setup', 'slotera-booking');
$developer_message = sprintf(
    "Hello,\n\nPlease configure Slotera Shared Database Network for this WordPress site.\n\nAdd this line to wp-config.php on every connected website, using the same value on all sites:\n\n%s\n\nPlace it above this line:\n%s\n\nCurrent WordPress DB_NAME: %s\nCurrent WordPress table prefix: %s\nExpected shared Slotera table prefix: sltr_\n\nAfter saving wp-config.php, open WordPress admin > Slotera > PRO > Shared Database Network and run Check/create Slotera tables now.\n\nThank you.",
    $config_line,
    $config_anchor,
    (string) ($report['database_name'] ?? ''),
    (string) ($report['wp_prefix'] ?? '')
);
?>
<div class="wrap sltr-admin-wrap sltr-shared-network-page sltr-pro-feature-page sltr-full-width-admin">
    <h1><?php esc_html_e('Shared Database Network', 'slotera-booking'); ?></h1>
    <?php if (!empty($_GET['tables_created'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Shared Slotera tables were checked/created successfully.', 'slotera-booking'); ?></p></div><?php endif; ?>

    <div class="postbox sltr-pro-panel">
        <h2><?php esc_html_e('Status', 'slotera-booking'); ?></h2>
        <?php if (!empty($report['active'])) : ?>
            <p><strong style="color:#008a20;">✓ <?php esc_html_e('Shared mode is active.', 'slotera-booking'); ?></strong></p>
        <?php else : ?>
            <p><strong><?php esc_html_e('Shared mode is not active on this installation.', 'slotera-booking'); ?></strong></p>
            <p class="description"><?php esc_html_e('For normal one-site installations this is expected. To connect multiple WordPress sites to one Slotera database, enable it in wp-config.php.', 'slotera-booking'); ?></p>
        <?php endif; ?>
        <table class="widefat striped sltr-pro-table">
            <tbody>
                <tr><th><?php esc_html_e('WordPress DB_NAME', 'slotera-booking'); ?></th><td><code><?php echo esc_html((string) ($report['database_name'] ?? '')); ?></code></td></tr>
                <tr><th><?php esc_html_e('WordPress table prefix', 'slotera-booking'); ?></th><td><code><?php echo esc_html((string) ($report['wp_prefix'] ?? '')); ?></code></td></tr>
                <tr><th><?php esc_html_e('Shared Slotera prefix', 'slotera-booking'); ?></th><td><code><?php echo esc_html((string) ($report['shared_prefix'] ?? '')); ?></code></td></tr>
                <tr><th><?php esc_html_e('wp-config.php writable', 'slotera-booking'); ?></th><td><?php echo !empty($report['wp_config_writable']) ? '✓' : '—'; ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="postbox sltr-pro-panel">
        <h2><?php esc_html_e('Setup guide', 'slotera-booking'); ?></h2>
        <ol>
            <li><?php esc_html_e('Use the same MySQL database for all connected WordPress installations, but keep their WordPress table prefixes different.', 'slotera-booking'); ?></li>
            <li><?php esc_html_e('Add the config line below to wp-config.php on every connected site.', 'slotera-booking'); ?></li>
            <li><?php esc_html_e('Place it above the WordPress stop-editing line.', 'slotera-booking'); ?></li>
            <li><?php esc_html_e('Return here and run the diagnostics/table check.', 'slotera-booking'); ?></li>
        </ol>
        <p><strong><?php esc_html_e('Copy this config line:', 'slotera-booking'); ?></strong></p>
        <div class="sltr-pro-inline-control">
            <input id="sltr-shared-config-line" type="text" class="regular-text code" readonly value="<?php echo esc_attr($config_line); ?>" style="flex:1;max-width:none;" />
            <button type="button" class="button button-secondary" data-sltr-copy="#sltr-shared-config-line"><?php esc_html_e('Copy', 'slotera-booking'); ?></button>
        </div>
        <p class="description"><?php printf(esc_html__('Insert it above: %s', 'slotera-booking'), '<code>' . esc_html($config_anchor) . '</code>'); ?></p>
        <p><a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=slotera-shared-network&sltr_create_shared_tables=1'), 'sltr_create_shared_tables')); ?>"><?php esc_html_e('Check/create Slotera tables now', 'slotera-booking'); ?></a></p>
    </div>

    <div class="postbox sltr-pro-panel">
        <h2><?php esc_html_e('Send instructions to developer or hosting support', 'slotera-booking'); ?></h2>
        <p class="description"><?php esc_html_e('Use this when the site owner should not edit wp-config.php manually.', 'slotera-booking'); ?></p>
        <p><strong><?php esc_html_e('Email subject', 'slotera-booking'); ?></strong></p>
        <input id="sltr-shared-dev-subject" type="text" class="large-text code" readonly value="<?php echo esc_attr($developer_subject); ?>" />
        <p><strong><?php esc_html_e('Message', 'slotera-booking'); ?></strong></p>
        <textarea id="sltr-shared-dev-message" class="large-text code" rows="14" readonly><?php echo esc_textarea($developer_message); ?></textarea>
        <p>
            <button type="button" class="button" data-sltr-copy="#sltr-shared-dev-message"><?php esc_html_e('Copy developer instructions', 'slotera-booking'); ?></button>
            <a class="button" href="mailto:?subject=<?php echo rawurlencode($developer_subject); ?>&body=<?php echo rawurlencode($developer_message); ?>"><?php esc_html_e('Open email draft', 'slotera-booking'); ?></a>
        </p>
    </div>

    <div class="postbox sltr-pro-panel">
        <h2><?php esc_html_e('Diagnostics', 'slotera-booking'); ?></h2>
        <table class="widefat striped sltr-pro-table sltr-pro-table--spaced">
            <tbody>
                <tr><th><?php esc_html_e('Configuration constant detected', 'slotera-booking'); ?></th><td><?php echo !empty($report['active']) ? '✓' : '—'; ?></td></tr>
                <tr><th><?php esc_html_e('Expected shared prefix', 'slotera-booking'); ?></th><td><code>sltr_</code></td></tr>
                <tr><th><?php esc_html_e('Current physical table example', 'slotera-booking'); ?></th><td><code><?php echo esc_html((string) (($report['tables']['bookings']['table'] ?? ''))); ?></code></td></tr>
            </tbody>
        </table>
        <h3><?php esc_html_e('Table health', 'slotera-booking'); ?></h3>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Area', 'slotera-booking'); ?></th><th><?php esc_html_e('Table', 'slotera-booking'); ?></th><th><?php esc_html_e('Exists', 'slotera-booking'); ?></th><th><?php esc_html_e('Rows', 'slotera-booking'); ?></th></tr></thead>
            <tbody>
            <?php foreach (($report['tables'] ?? []) as $area => $check) : ?>
                <tr>
                    <td><?php echo esc_html((string) $area); ?></td>
                    <td><code><?php echo esc_html((string) ($check['table'] ?? '')); ?></code></td>
                    <td><?php echo esc_html(!empty($check['exists']) ? '✓' : '—'); ?></td>
                    <td><?php echo isset($check['count']) ? esc_html((string) (int) $check['count']) : '—'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    (function(){
        function copyValue(selector, button) {
            var el = document.querySelector(selector);
            if (!el) { return; }
            var text = el.value || el.textContent || '';
            function done(){
                if (!button) { return; }
                var original = button.textContent;
                button.textContent = '<?php echo esc_js(__('Copied', 'slotera-booking')); ?>';
                window.setTimeout(function(){ button.textContent = original; }, 1600);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(function(){
                    el.focus(); el.select(); document.execCommand('copy'); done();
                });
                return;
            }
            el.focus(); el.select(); document.execCommand('copy'); done();
        }
        document.addEventListener('click', function(e){
            var button = e.target.closest('[data-sltr-copy]');
            if (!button) { return; }
            e.preventDefault();
            copyValue(button.getAttribute('data-sltr-copy'), button);
        });
    })();
    </script>
</div>
