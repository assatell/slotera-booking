<?php if (!defined('ABSPATH')) { exit; } ?>
    <section id="sltr-advanced" class="sltr-panel sltr-settings-section" style="margin: 16px 0;">
        <h2><?php esc_html_e('Cron settings', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Production-oriented technical settings for hosting and scheduled background tasks.', 'slotera-booking'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_save_advanced_settings">
            <input type="hidden" name="return_to" value="sltr-advanced">
            <input type="hidden" name="settings_section" value="advanced">
            <?php wp_nonce_field('sltr_save_advanced_settings'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Real server cron configured', 'slotera-booking'); ?></th>
                        <td>
                            <label><input type="checkbox" name="real_server_cron_configured" value="1" <?php checked((int) ($settings['real_server_cron_configured'] ?? 0), 1); ?>> <?php esc_html_e('I have configured a real server cron to run wp-cron.php.', 'slotera-booking'); ?></label>
                            <p class="description"><?php esc_html_e('Enable this when DISABLE_WP_CRON is true and your hosting panel runs wp-cron.php on a real schedule. Slotera diagnostics will then treat disabled WP pseudo-cron as healthy.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p><button class="button button-primary"><?php esc_html_e('Save cron settings', 'slotera-booking'); ?></button></p>
        </form>
    </section>
