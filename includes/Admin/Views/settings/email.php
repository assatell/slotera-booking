<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
$sltr_external_mail_detector = new \Slotera\Application\Services\ExternalMailPluginDetector();
$sltr_external_mail_plugins = $sltr_external_mail_detector->detected();
$sltr_external_mail_names = $sltr_external_mail_detector->detected_names();
$sltr_settings_get = wp_unslash($_GET);
$sltr_smtp_conflict_message = __('External email delivery plugin detected. Slotera SMTP was not enabled to avoid conflicting mail configuration. Disable the external mail plugin first if you intentionally want Slotera to manage SMTP.', 'slotera-booking');
?>
    <section id="sltr-email-settings" class="sltr-panel sltr-panel--accent sltr-scroll-target">
        <h2><?php esc_html_e('Calendar invite attachments (ICS)', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Attach calendar files to customer booking emails so clients can add bookings to Google Calendar, Apple Calendar, Outlook and other calendar apps.', 'slotera-booking'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_save_email_calendar_invites">
            <?php wp_nonce_field('sltr_save_email_calendar_invites'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('ICS calendar invites', 'slotera-booking'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="email_attach_ics_invites" value="1" <?php checked((int) ($settings['email_attach_ics_invites'] ?? 0), 1); ?>>
                                <?php esc_html_e('Attach ICS calendar invites to customer booking emails and reminders', 'slotera-booking'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('This is a safe notification setting. It does not connect to external calendars, change availability, affect payments or touch customer login/magic links.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit"><button class="button button-primary" type="submit"><?php esc_html_e('Save calendar invite settings', 'slotera-booking'); ?></button></p>
        </form>
    </section>

    <section class="sltr-panel">
        <h2>General email settings</h2>
        <?php if ($sltr_external_mail_plugins !== []) : ?>
            <div class="notice notice-info inline"><p><strong><?php esc_html_e('External email delivery detected:', 'slotera-booking'); ?></strong> <?php echo esc_html($sltr_external_mail_names); ?>. <?php esc_html_e('Slotera will continue to send through wp_mail(), allowing the existing delivery plugin to handle transport. Built-in Slotera SMTP is kept disabled to avoid conflicts.', 'slotera-booking'); ?></p></div>
        <?php endif; ?>
        <?php if (!empty($sltr_settings_get['sltr_smtp_external_plugin_blocked'])) : ?>
            <div class="notice notice-warning inline"><p><?php echo esc_html($sltr_smtp_conflict_message); ?></p></div>
        <?php endif; ?>
        <p>These settings control sender details and global email delivery.</p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_save_email_general">
            <?php wp_nonce_field('sltr_save_email_general'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">Enable emails</th>
                        <td>
                            <label>
                                <input type="checkbox" name="email_notifications_enabled" value="1" <?php checked((int) ($settings['email_notifications_enabled'] ?? 1), 1); ?>>
                                Send email notifications
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-email-from-name">From name</label></th>
                        <td><input type="text" class="regular-text" id="sltr-email-from-name" name="email_from_name" value="<?php echo esc_attr((string) ($settings['email_from_name'] ?? get_bloginfo('name'))); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-email-from-address">From email</label></th>
                        <td>
                            <input type="email" class="regular-text" id="sltr-email-from-address" name="email_from_address" value="<?php echo esc_attr((string) ($settings['email_from_address'] ?? get_option('admin_email'))); ?>">
                            <p class="description">For best delivery, use an email address from the same domain as this website.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-admin-notification-email">Admin notification email</label></th>
                        <td><input type="email" class="regular-text" id="sltr-admin-notification-email" name="admin_notification_email" value="<?php echo esc_attr((string) ($settings['admin_notification_email'] ?? get_option('admin_email'))); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-email-retry-max">Retry attempts</label></th>
                        <td>
                            <input type="number" min="1" max="10" id="sltr-email-retry-max" name="email_retry_max_attempts" value="<?php echo esc_attr((string) ($settings['email_retry_max_attempts'] ?? 3)); ?>">
                            <p class="description">Failed queued emails are retried automatically by WP-Cron.</p>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2"><h3 class="sltr-section-title"><?php esc_html_e('SMTP Settings', 'slotera-booking'); ?></h3></th>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable SMTP', 'slotera-booking'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="sltr-smtp-enabled" name="smtp_enabled" value="1" <?php checked((int) ($settings['smtp_enabled'] ?? 0), 1); ?> data-external-mail-conflict="<?php echo $sltr_external_mail_plugins !== [] ? '1' : '0'; ?>">
                                <?php esc_html_e('Send plugin emails through custom SMTP', 'slotera-booking'); ?>
                            </label>
                        </td>
                    </tr>
                    <?php if ($sltr_external_mail_plugins !== []) : ?>
                    <tr id="sltr-smtp-external-conflict-row">
                        <th scope="row"></th>
                        <td><p class="description"><strong><?php echo esc_html($sltr_smtp_conflict_message); ?></strong></p></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row"><label for="sltr-smtp-sender-email"><?php esc_html_e('Sender email', 'slotera-booking'); ?></label></th>
                        <td>
                            <input type="email" class="regular-text" id="sltr-smtp-sender-email" name="smtp_sender_email" value="<?php echo esc_attr((string) ($settings['smtp_sender_email'] ?? ($settings['email_from_address'] ?? get_option('admin_email')))); ?>">
                            <p class="description"><?php esc_html_e('Email address used as the SMTP From address. Leave it matching your SMTP mailbox when possible.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-smtp-sender-name"><?php esc_html_e('Sender name', 'slotera-booking'); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="sltr-smtp-sender-name" name="smtp_sender_name" value="<?php echo esc_attr((string) ($settings['smtp_sender_name'] ?? ($settings['email_from_name'] ?? get_bloginfo('name')))); ?>">
                            <p class="description"><?php esc_html_e('Display name shown in the From field of outgoing SMTP emails.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-smtp-host"><?php esc_html_e('SMTP Host', 'slotera-booking'); ?></label></th>
                        <td><input type="text" class="regular-text" id="sltr-smtp-host" name="smtp_host" value="<?php echo esc_attr((string) ($settings['smtp_host'] ?? '')); ?>" placeholder="smtp.example.com"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-smtp-port"><?php esc_html_e('SMTP Port', 'slotera-booking'); ?></label></th>
                        <td><input type="number" min="1" max="65535" id="sltr-smtp-port" name="smtp_port" value="<?php echo esc_attr((string) ($settings['smtp_port'] ?? 587)); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-smtp-encryption"><?php esc_html_e('Encryption', 'slotera-booking'); ?></label></th>
                        <td>
                            <?php $sltr_smtp_encryption = (string) ($settings['smtp_encryption'] ?? 'tls'); ?>
                            <select id="sltr-smtp-encryption" name="smtp_encryption">
                                <option value="none" <?php selected($sltr_smtp_encryption, 'none'); ?>><?php esc_html_e('None', 'slotera-booking'); ?></option>
                                <option value="tls" <?php selected($sltr_smtp_encryption, 'tls'); ?>>TLS</option>
                                <option value="ssl" <?php selected($sltr_smtp_encryption, 'ssl'); ?>>SSL</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-smtp-timeout"><?php esc_html_e('SMTP timeout', 'slotera-booking'); ?></label></th>
                        <td>
                            <input type="number" min="5" max="120" id="sltr-smtp-timeout" name="smtp_timeout" value="<?php echo esc_attr((string) ($settings['smtp_timeout'] ?? 20)); ?>">
                            <p class="description"><?php esc_html_e('Connection timeout in seconds. Default: 20.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('SSL compatibility', 'slotera-booking'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="smtp_allow_insecure_ssl" value="1" <?php checked((int) ($settings['smtp_allow_insecure_ssl'] ?? 0), 1); ?>>
                                <?php esc_html_e('Allow self-signed/relaxed SSL certificates', 'slotera-booking'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Enable only if your SMTP test fails with a certificate verification error on shared hosting.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Authentication', 'slotera-booking'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="smtp_auth" value="1" <?php checked((int) ($settings['smtp_auth'] ?? 1), 1); ?>>
                                <?php esc_html_e('Use SMTP authentication', 'slotera-booking'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-smtp-username"><?php esc_html_e('SMTP Username', 'slotera-booking'); ?></label></th>
                        <td><input type="text" class="regular-text" id="sltr-smtp-username" name="smtp_username" value="<?php echo esc_attr((string) ($settings['smtp_username'] ?? '')); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-smtp-password"><?php esc_html_e('SMTP Password', 'slotera-booking'); ?></label></th>
                        <td>
                            <input type="password" class="regular-text" id="sltr-smtp-password" name="smtp_password" value="" autocomplete="new-password">
                            <p class="description"><?php esc_html_e('Leave blank to keep the current password. For security, saved passwords are never displayed here.', 'slotera-booking'); ?></p>
                            <?php if (!empty($settings['smtp_password'])) : ?>
                                <p><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> <strong><?php esc_html_e('SMTP password is saved.', 'slotera-booking'); ?></strong></p>
                                <label><input type="checkbox" name="smtp_password_clear" value="1"> <?php esc_html_e('Clear saved SMTP password', 'slotera-booking'); ?></label>
                            <?php else : ?>
                                <p><span class="dashicons dashicons-warning" aria-hidden="true"></span> <strong><?php esc_html_e('No SMTP password is saved.', 'slotera-booking'); ?></strong></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p><button class="button button-primary">Save general email settings</button></p>
        </form>

    </section>
    <section class="sltr-panel">
        <h2><?php esc_html_e('Send Test Email', 'slotera-booking'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-panel-form">
            <input type="hidden" name="action" value="sltr_send_smtp_test_email">
            <?php wp_nonce_field('sltr_send_smtp_test_email'); ?>
            <p class="description"><?php esc_html_e('Save SMTP settings first, then send a test email to confirm delivery.', 'slotera-booking'); ?></p>
            <p>
                <label for="sltr-smtp-test-email"><strong><?php esc_html_e('Test recipient email', 'slotera-booking'); ?></strong></label><br>
                <input type="email" class="regular-text" id="sltr-smtp-test-email" name="smtp_test_email" value="<?php echo esc_attr((string) ($settings['admin_notification_email'] ?? get_option('admin_email'))); ?>">
            </p>
            <p><button class="button button-secondary"><?php esc_html_e('Send Test Email', 'slotera-booking'); ?></button></p>
        </form>
    </section>

<?php if ($sltr_external_mail_plugins !== []) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkbox = document.getElementById('sltr-smtp-enabled');
    if (!checkbox) return;
    checkbox.checked = false;
    checkbox.addEventListener('change', function () {
        if (!checkbox.checked) return;
        checkbox.checked = false;
        window.alert(<?php echo wp_json_encode($sltr_smtp_conflict_message); ?>);
    });
});
</script>
<?php endif; ?>
