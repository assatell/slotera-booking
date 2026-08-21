<?php

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap sltr-admin sltr-emails sltr-page-stack">
    <header class="sltr-page-header"><div class="sltr-page-header__content"><h1 class="sltr-page-header__title"><?php esc_html_e('Emails', 'slotera-booking'); ?></h1><p class="sltr-page-header__description"><?php esc_html_e('Configure delivery, SMTP, test messages and reusable email templates.', 'slotera-booking'); ?></p></div></header>

    <?php $sltr_message = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_message'); ?>
    <?php $sltr_error = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_error'); ?>
    <?php if ($sltr_message !== '') : ?>
        <?php
        $sltr_success_messages = [
            'saved' => __('Saved.', 'slotera-booking'),
            'smtp_test_sent' => __('SMTP test email sent successfully.', 'slotera-booking'),
            'email_locale_saved' => __('Default language for this section saved.', 'slotera-booking'),
            'reset_all' => __('All email templates were reset to the current language defaults.', 'slotera-booking'),
        ];
        ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($sltr_success_messages[$sltr_message] ?? __('Saved.', 'slotera-booking')); ?></p></div>
    <?php endif; ?>

    <?php if ($sltr_error !== '') : ?>
        <?php
        $sltr_error_messages = [
            'invalid_smtp_test_email' => __('Please enter a valid test email address.', 'slotera-booking'),
            'smtp_test_failed' => __('SMTP test email could not be sent. Please check host, port, encryption and authentication settings.', 'slotera-booking'),
        ];
        ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html($sltr_error_messages[$sltr_error] ?? __('Something went wrong.', 'slotera-booking')); ?></p></div>
    <?php endif; ?>

    <section class="sltr-panel sltr-panel--accent">
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
                                <input type="checkbox" name="smtp_enabled" value="1" <?php checked((int) ($settings['smtp_enabled'] ?? 0), 1); ?>>
                                <?php esc_html_e('Send plugin emails through custom SMTP', 'slotera-booking'); ?>
                            </label>
                        </td>
                    </tr>
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

    <section class="sltr-panel"><div class="sltr-panel__body">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-inline-form">
            <?php wp_nonce_field('sltr_save_email_template_locale'); ?>
            <input type="hidden" name="action" value="sltr_save_email_template_locale">
            <label for="sltr-email-context-locale"><strong><?php esc_html_e('Default language for this section', 'slotera-booking'); ?></strong></label>
            <select id="sltr-email-context-locale" name="locale">
                <?php foreach (($email_languages ?? []) as $code => $label) : ?>
                    <option value="<?php echo esc_attr((string) $code); ?>" <?php selected(($email_default_locale ?? 'en_US'), (string) $code); ?>><?php echo esc_html((string) $label . ' (' . (string) $code . ')'); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button"><?php esc_html_e('Save default language', 'slotera-booking'); ?></button>
            <span class="description"><?php esc_html_e('Email templates use this language as the default editing/sending context. The available languages match the Frontend UI language list.', 'slotera-booking'); ?></span>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-inline-form sltr-field-spaced">
            <?php wp_nonce_field('sltr_reset_all_email_templates'); ?>
            <input type="hidden" name="action" value="sltr_reset_all_email_templates">
            <button type="submit" class="button button-secondary" data-sltr-confirm="<?php echo esc_attr(__('Reset all email templates for the current language? This will overwrite custom email template text.', 'slotera-booking')); ?>" data-sltr-confirm-title="<?php esc_attr_e('Confirm action', 'slotera-booking'); ?>" data-sltr-confirm-button="<?php esc_attr_e('Confirm', 'slotera-booking'); ?>"><?php esc_html_e('Reset all email templates for current language', 'slotera-booking'); ?></button>
            <span class="description"><?php esc_html_e('Use this after changing the default language when you want saved templates to be replaced by that language\'s defaults.', 'slotera-booking'); ?></span>
        </form>
    </div></section>

    <section id="sltr-email-templates" class="sltr-panel sltr-panel--flush sltr-scroll-target">
        <h2><?php esc_html_e('Email templates', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Edit subjects and message templates for each email scenario.', 'slotera-booking'); ?></p>

        <div class="sltr-responsive-table-wrapper" tabindex="0" role="region" aria-label="<?php esc_attr_e('Email templates', 'slotera-booking'); ?>"><table class="widefat striped sltr-responsive-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Scenario', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Description', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Recipient', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Enabled', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Actions', 'slotera-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scenarios as $key => $scenario) : ?>
                    <?php $enabled = (int) ($settings['email_template_' . $key . '_enabled'] ?? 1); ?>
                    <tr>
                        <td><strong><?php echo esc_html($scenario['title']); ?></strong></td>
                        <td><?php echo esc_html($scenario['description']); ?></td>
                        <td><?php echo esc_html(ucfirst((string) ($scenario['recipient'] ?? 'customer'))); ?></td>
                        <td><?php echo $enabled ? esc_html__('Yes', 'slotera-booking') : esc_html__('No', 'slotera-booking'); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=slotera-translations&group=email_templates&scenario=' . $key)); ?>">
                                <?php esc_html_e('Edit template', 'slotera-booking'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>
</div>
