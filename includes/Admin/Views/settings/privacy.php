<?php if (!defined('ABSPATH')) { exit; } ?>
    <section id="sltr-privacy" class="sltr-panel sltr-settings-section" style="margin: 16px 0;">
        <h2><?php esc_html_e('Privacy & Data', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Control how long Slotera keeps personal data in operational records, logs, email queues and webhook payloads.', 'slotera-booking'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_save_privacy_settings">
            <input type="hidden" name="return_to" value="sltr-privacy">
            <input type="hidden" name="settings_section" value="security">
            <input type="hidden" name="security_tab" value="privacy">
            <?php wp_nonce_field('sltr_save_privacy_settings'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Visitor analytics', 'slotera-booking'); ?></th>
                        <td>
                            <label><input type="checkbox" name="privacy_visitor_analytics_enabled" value="1" <?php checked((int) ($settings['privacy_visitor_analytics_enabled'] ?? 1), 1); ?>> <?php esc_html_e('Collect privacy-first first-party analytics on Slotera pages.', 'slotera-booking'); ?></label>
                            <p class="description"><?php esc_html_e('Basic analytics does not create a visitor identifier, use browser storage, or store an IP hash. Query strings are removed from analytics URLs.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Session analytics', 'slotera-booking'); ?></th>
                        <td>
                            <label><input type="checkbox" name="privacy_visitor_session_analytics_enabled" value="1" <?php checked((int) ($settings['privacy_visitor_session_analytics_enabled'] ?? 0), 1); ?>> <?php esc_html_e('Measure unique visitor sessions using browser session storage.', 'slotera-booking'); ?></label>
                            <p class="description"><?php esc_html_e('Disabled by default. Session tracking is activated only when a consent manager grants analytics consent through the Slotera consent filter.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-privacy-visitor-analytics-days"><?php esc_html_e('Visitor analytics retention', 'slotera-booking'); ?></label></th>
                        <td><input id="sltr-privacy-visitor-analytics-days" type="number" min="1" max="3650" name="privacy_visitor_analytics_retention_days" value="<?php echo esc_attr((string) ($settings['privacy_visitor_analytics_retention_days'] ?? 90)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?><p class="description"><?php esc_html_e('Expired visitor events are deleted automatically in bounded daily batches.', 'slotera-booking'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Delete data on uninstall', 'slotera-booking'); ?></th>
                        <td>
                            <label><input type="checkbox" name="privacy_remove_data_on_uninstall" value="1" <?php checked((int) ($settings['privacy_remove_data_on_uninstall'] ?? 0), 1); ?>> <?php esc_html_e('Delete Slotera tables, options and scheduled jobs when the plugin is uninstalled.', 'slotera-booking'); ?></label>
                            <p class="description"><?php esc_html_e('Disabled by default to protect bookings. Enable only when the site owner explicitly wants a full data wipe. Deactivation never removes data.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-privacy-bookings-days"><?php esc_html_e('Anonymize completed/cancelled bookings after', 'slotera-booking'); ?></label></th>
                        <td><input id="sltr-privacy-bookings-days" type="number" min="0" max="3650" name="privacy_anonymize_completed_bookings_days" value="<?php echo esc_attr((string) ($settings['privacy_anonymize_completed_bookings_days'] ?? 365)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?><p class="description"><?php esc_html_e('0 disables automatic anonymization. Future/active bookings are not anonymized.', 'slotera-booking'); ?></p></td>
                    </tr>
                    <tr><th scope="row"><label for="sltr-privacy-activity-days"><?php esc_html_e('Activity log retention', 'slotera-booking'); ?></label></th><td><input id="sltr-privacy-activity-days" type="number" min="0" max="3650" name="privacy_activity_log_retention_days" value="<?php echo esc_attr((string) ($settings['privacy_activity_log_retention_days'] ?? 365)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?></td></tr>
                    <tr><th scope="row"><label for="sltr-privacy-history-days"><?php esc_html_e('Booking history retention', 'slotera-booking'); ?></label></th><td><input id="sltr-privacy-history-days" type="number" min="0" max="3650" name="privacy_booking_history_retention_days" value="<?php echo esc_attr((string) ($settings['privacy_booking_history_retention_days'] ?? 1095)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?></td></tr>
                    <tr><th scope="row"><label for="sltr-privacy-email-days"><?php esc_html_e('Email queue retention', 'slotera-booking'); ?></label></th><td><input id="sltr-privacy-email-days" type="number" min="0" max="3650" name="privacy_email_queue_retention_days" value="<?php echo esc_attr((string) ($settings['privacy_email_queue_retention_days'] ?? 90)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?></td></tr>
                    <tr><th scope="row"><label for="sltr-privacy-marketing-days"><?php esc_html_e('Marketing log retention', 'slotera-booking'); ?></label></th><td><input id="sltr-privacy-marketing-days" type="number" min="0" max="3650" name="privacy_marketing_log_retention_days" value="<?php echo esc_attr((string) ($settings['privacy_marketing_log_retention_days'] ?? 180)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?></td></tr>
                    <tr><th scope="row"><label for="sltr-privacy-webhook-days"><?php esc_html_e('Incoming webhook payload retention', 'slotera-booking'); ?></label></th><td><input id="sltr-privacy-webhook-days" type="number" min="0" max="3650" name="privacy_webhook_event_retention_days" value="<?php echo esc_attr((string) ($settings['privacy_webhook_event_retention_days'] ?? 180)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?></td></tr>
                    <tr><th scope="row"><label for="sltr-privacy-outgoing-days"><?php esc_html_e('Outgoing webhook delivery retention', 'slotera-booking'); ?></label></th><td><input id="sltr-privacy-outgoing-days" type="number" min="0" max="3650" name="privacy_outgoing_webhook_retention_days" value="<?php echo esc_attr((string) ($settings['privacy_outgoing_webhook_retention_days'] ?? 180)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?></td></tr>
                </tbody>
            </table>

            <p><button class="button button-primary"><?php esc_html_e('Save privacy settings', 'slotera-booking'); ?></button></p>
        </form>
    </section>
