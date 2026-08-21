<?php if (!defined('ABSPATH')) { exit; } ?>
<?php $sltr_get = wp_unslash($_GET); ?>
<?php $prepared = $license->prepared_license_fields(); ?>
<div class="wrap sltr-admin-wrap sltr-license-page sltr-pro-feature-page sltr-full-width-admin">
    <h1><?php esc_html_e('Slotera License', 'slotera-booking'); ?></h1>

    <?php if (!empty($sltr_get['license_activated'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Placeholder license key saved. No license validation or enforcement is active.', 'slotera-booking'); ?></p></div>
    <?php elseif (!empty($sltr_get['license_error'])) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Please enter a license key.', 'slotera-booking'); ?></p></div>
    <?php elseif (!empty($sltr_get['license_deactivated'])) : ?>
        <div class="notice notice-warning is-dismissible"><p><?php esc_html_e('Placeholder license key cleared. License enforcement remains disabled.', 'slotera-booking'); ?></p></div>
    <?php elseif (!empty($sltr_get['license_checked'])) : ?>
        <div class="notice notice-info is-dismissible"><p><?php esc_html_e('Local license fields refreshed. This is a preparation step; no external license server is connected yet.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>

    <div class="sltr-card sltr-pro-panel">
        <h2><?php esc_html_e('Current status', 'slotera-booking'); ?></h2>
        <div class="notice notice-warning inline"><p><strong><?php esc_html_e('Licensing: development placeholder / enforcement disabled', 'slotera-booking'); ?></strong></p></div>
        <p><strong><?php echo esc_html((string) $status['label']); ?></strong></p>
        <p><?php echo esc_html($license->trial_message()); ?></p>

        <table class="widefat striped sltr-pro-table">
            <tbody>
                <tr><th><?php esc_html_e('Enforcement', 'slotera-booking'); ?></th><td><?php esc_html_e('Disabled in this development build', 'slotera-booking'); ?></td></tr>
                <tr><th><?php esc_html_e('Trial started', 'slotera-booking'); ?></th><td><?php echo esc_html((string) ($status['trial_started_at'] ?? '')); ?></td></tr>
                <tr><th><?php esc_html_e('Full trial ends', 'slotera-booking'); ?></th><td><?php echo esc_html((string) ($status['trial_ends_at'] ?? '')); ?></td></tr>
                <tr><th><?php esc_html_e('Grace ends', 'slotera-booking'); ?></th><td><?php echo esc_html((string) ($status['grace_ends_at'] ?? '')); ?></td></tr>
                <tr><th><?php esc_html_e('Marketing campaigns', 'slotera-booking'); ?></th><td><?php echo !empty($status['marketing_allowed']) ? ((($status['state'] ?? '') === 'grace') ? esc_html__('Limited basic campaigns', 'slotera-booking') : esc_html__('Available', 'slotera-booking')) : esc_html__('Paused', 'slotera-booking'); ?></td></tr>
                <tr><th><?php esc_html_e('Advanced filters', 'slotera-booking'); ?></th><td><?php echo !empty($status['advanced_marketing_allowed']) ? esc_html__('Available', 'slotera-booking') : esc_html__('Locked', 'slotera-booking'); ?></td></tr>
                <tr><th><?php esc_html_e('Personal coupons', 'slotera-booking'); ?></th><td><?php echo !empty($status['unique_coupons_allowed']) ? esc_html__('Available', 'slotera-booking') : esc_html__('Locked', 'slotera-booking'); ?></td></tr>
                <tr><th><?php esc_html_e('Queue tuning', 'slotera-booking'); ?></th><td><?php echo !empty($status['queue_settings_allowed']) ? esc_html__('Available', 'slotera-booking') : esc_html__('Locked; fixed to 5 emails per batch', 'slotera-booking'); ?></td></tr>
                <tr><th><?php esc_html_e('Automations', 'slotera-booking'); ?></th><td><?php echo !empty($status['automation_allowed']) ? esc_html__('Available', 'slotera-booking') : esc_html__('Paused', 'slotera-booking'); ?></td></tr>
                <tr><th><?php esc_html_e('Bookings', 'slotera-booking'); ?></th><td><?php esc_html_e('Always available. Existing client booking flow is not broken by trial expiration.', 'slotera-booking'); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="sltr-card sltr-pro-panel">
        <h2><?php esc_html_e('Prepared license fields', 'slotera-booking'); ?></h2>
        <p class="description"><?php esc_html_e('These fields prepare Slotera for a future sales website and license server. For now they are stored locally only.', 'slotera-booking'); ?></p>
        <table class="widefat striped sltr-pro-table">
            <tbody>
                <tr><th><?php esc_html_e('License key', 'slotera-booking'); ?></th><td><code><?php echo esc_html($prepared['license_key'] !== '' ? $prepared['license_key'] : __('Not entered', 'slotera-booking')); ?></code></td></tr>
                <tr><th><?php esc_html_e('Licensed domain', 'slotera-booking'); ?></th><td><code><?php echo esc_html($prepared['licensed_domain']); ?></code></td></tr>
                <tr><th><?php esc_html_e('License status', 'slotera-booking'); ?></th><td><code><?php echo esc_html($prepared['license_status']); ?></code></td></tr>
                <tr><th><?php esc_html_e('Expires at', 'slotera-booking'); ?></th><td><?php echo esc_html($prepared['license_expires_at'] !== '' ? $prepared['license_expires_at'] : __('Not set yet', 'slotera-booking')); ?></td></tr>
                <tr><th><?php esc_html_e('Last checked', 'slotera-booking'); ?></th><td><?php echo esc_html($prepared['license_last_checked_at'] !== '' ? $prepared['license_last_checked_at'] : __('Not checked yet', 'slotera-booking')); ?></td></tr>
                <tr><th><?php esc_html_e('Last check result', 'slotera-booking'); ?></th><td><code><?php echo esc_html($prepared['license_last_check_result']); ?></code></td></tr>
            </tbody>
        </table>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
            <?php wp_nonce_field('sltr_check_license_local'); ?>
            <input type="hidden" name="action" value="sltr_check_license_local">
            <button type="submit" class="button"><?php esc_html_e('Refresh local license fields', 'slotera-booking'); ?></button>
        </form>
    </div>

    <div class="sltr-card sltr-pro-panel">
        <h2><?php esc_html_e('Development license placeholder', 'slotera-booking'); ?></h2>
        <p class="description"><?php esc_html_e('This build does not validate or enforce licenses. A key may be stored only as a development placeholder for future license-server integration.', 'slotera-booking'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('sltr_activate_license'); ?>
            <input type="hidden" name="action" value="sltr_activate_license">
            <input type="password" name="license_key" class="regular-text" value="" autocomplete="new-password" placeholder="<?php echo esc_attr(!empty($data['license_key']) ? __('License key saved; enter a new key to replace', 'slotera-booking') : 'SLTR-XXXX-XXXX-XXXX'); ?>">
            <button type="submit" class="button button-primary"><?php esc_html_e('Save placeholder key', 'slotera-booking'); ?></button>
        </form>
        <?php if (!empty($data['license_key'])) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
                <?php wp_nonce_field('sltr_deactivate_license'); ?>
                <input type="hidden" name="action" value="sltr_deactivate_license">
                <button type="submit" class="button"><?php esc_html_e('Clear placeholder key', 'slotera-booking'); ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>
