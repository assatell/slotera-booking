<?php if (!defined('ABSPATH')) { exit; } ?>
<?php $sltr_get = wp_unslash($_GET); $prepared = $license->prepared_license_fields(); ?>
<div class="wrap sltr-admin-wrap sltr-license-page sltr-full-width-admin">
    <h1><?php esc_html_e('Slotera License', 'slotera-booking'); ?></h1>

    <?php if (!empty($sltr_get['license_activated'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('The license was verified and activated.', 'slotera-booking'); ?></p></div>
    <?php elseif (!empty($sltr_get['license_error']) || !empty($sltr_get['trial_error'])) : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('The request was rejected or the license server could not be reached. The last verified state was preserved.', 'slotera-booking'); ?></p></div>
    <?php elseif (!empty($sltr_get['license_deactivated'])) : ?>
        <div class="notice notice-warning is-dismissible"><p><?php esc_html_e('The license key and local certificate were removed from this site.', 'slotera-booking'); ?></p></div>
    <?php elseif (!empty($sltr_get['license_checked'])) : ?>
        <div class="notice notice-info is-dismissible"><p><?php esc_html_e('License status refresh completed. If the server was unavailable, the last verified state was preserved.', 'slotera-booking'); ?></p></div>
    <?php elseif (!empty($sltr_get['trial_started'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('The 30-day trial was started for this root domain.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>

    <div class="sltr-card">
        <h2><?php esc_html_e('Current status', 'slotera-booking'); ?></h2>
        <p><strong><?php echo esc_html((string) $status['label']); ?></strong></p>
        <table class="widefat striped">
            <tbody>
                <tr><th><?php esc_html_e('Plan', 'slotera-booking'); ?></th><td><code><?php echo esc_html($prepared['license_plan'] !== '' ? $prepared['license_plan'] : '—'); ?></code></td></tr>
                <tr><th><?php esc_html_e('Licensed domain', 'slotera-booking'); ?></th><td><code><?php echo esc_html($prepared['licensed_domain'] !== '' ? $prepared['licensed_domain'] : '—'); ?></code></td></tr>
                <tr><th><?php esc_html_e('State', 'slotera-booking'); ?></th><td><code><?php echo esc_html($prepared['license_status']); ?></code></td></tr>
                <tr><th><?php esc_html_e('Expires at', 'slotera-booking'); ?></th><td><?php echo esc_html($prepared['license_expires_at'] !== '' ? $prepared['license_expires_at'] : '—'); ?></td></tr>
                <tr><th><?php esc_html_e('Last checked', 'slotera-booking'); ?></th><td><?php echo esc_html($prepared['license_last_checked_at'] !== '' ? $prepared['license_last_checked_at'] : '—'); ?></td></tr>
                <tr><th><?php esc_html_e('Last result', 'slotera-booking'); ?></th><td><code><?php echo esc_html($prepared['license_last_check_result']); ?></code></td></tr>
            </tbody>
        </table>
        <?php if ($prepared['license_key'] !== '') : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
                <?php wp_nonce_field('sltr_check_license_local'); ?>
                <input type="hidden" name="action" value="sltr_check_license_local">
                <button type="submit" class="button"><?php esc_html_e('Refresh status', 'slotera-booking'); ?></button>
            </form>
        <?php endif; ?>
    </div>

    <div class="sltr-card">
        <h2><?php esc_html_e('Activate or replace license', 'slotera-booking'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('sltr_activate_license'); ?>
            <input type="hidden" name="action" value="sltr_activate_license">
            <input type="password" name="license_key" class="regular-text" value="" autocomplete="new-password" placeholder="SLTR-XXXXXXXX-XXXXXXXX-XXXXXXXX" required>
            <button type="submit" class="button button-primary"><?php esc_html_e('Activate license', 'slotera-booking'); ?></button>
        </form>
        <?php if ($prepared['license_key'] !== '') : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
                <?php wp_nonce_field('sltr_deactivate_license'); ?>
                <input type="hidden" name="action" value="sltr_deactivate_license">
                <button type="submit" class="button"><?php esc_html_e('Remove license from this site', 'slotera-booking'); ?></button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (($status['state'] ?? '') === 'unverified') : ?>
        <div class="sltr-card">
            <h2><?php esc_html_e('Free trial', 'slotera-booking'); ?></h2>
            <p><?php esc_html_e('Start one 30-day trial for this root domain.', 'slotera-booking'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('sltr_start_license_trial'); ?>
                <input type="hidden" name="action" value="sltr_start_license_trial">
                <button type="submit" class="button"><?php esc_html_e('Start 30-day trial', 'slotera-booking'); ?></button>
            </form>
        </div>
    <?php endif; ?>

    <p class="description"><?php esc_html_e('A server outage never disables booking functionality. Slotera keeps the last valid signed license state.', 'slotera-booking'); ?></p>
</div>
