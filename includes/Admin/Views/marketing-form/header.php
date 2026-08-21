<?php if (!defined('ABSPATH')) { exit; } ?>
<?php $sltr_is_coupon_campaign = (($sltr_campaign_context ?? '') === 'coupon'); ?>
<div class="wrap sltr-admin-wrap sltr-marketing-page sltr-pro-feature-page sltr-full-width-admin sltr-page-stack">
    <?php $sltr_marketing_section = 'coupons'; require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-shell-tabs.php'; ?>
    <header class="sltr-page-header"><div class="sltr-page-header__content"><h1 class="sltr-page-header__title"><?php echo $id > 0 ? esc_html__('Coupon campaign', 'slotera-booking') : esc_html__('Create coupon campaign', 'slotera-booking'); ?></h1><p class="sltr-page-header__description"><?php esc_html_e('Prepare the email campaign for this coupon and choose its audience.', 'slotera-booking'); ?></p></div><div class="sltr-page-header__actions"><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing')); ?>"><?php esc_html_e('Back to Coupons', 'slotera-booking'); ?></a></div></header>
    <?php if (!empty($sltr_get['updated'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Campaign saved.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['email_settings_required'])) : ?>
        <?php
        $sltr_email_issue = sanitize_key((string) ($sltr_get['email_settings_issue'] ?? ''));
        $sltr_email_issue_messages = [
            'email_notifications_disabled' => __('Email notifications are disabled.', 'slotera-booking'),
            'from_name_missing' => __('From name is missing.', 'slotera-booking'),
            'from_email_invalid' => __('From email is missing or invalid.', 'slotera-booking'),
            'smtp_host_missing' => __('SMTP is enabled but SMTP Host is missing.', 'slotera-booking'),
            'smtp_port_invalid' => __('SMTP Port is invalid.', 'slotera-booking'),
            'smtp_sender_name_missing' => __('SMTP Sender name is missing.', 'slotera-booking'),
            'smtp_sender_email_invalid' => __('SMTP Sender email is missing or invalid.', 'slotera-booking'),
            'smtp_username_missing' => __('SMTP authentication is enabled but Username is missing.', 'slotera-booking'),
            'smtp_password_missing' => __('SMTP authentication is enabled but no Password is saved.', 'slotera-booking'),
        ];
        $sltr_email_issue_message = $sltr_email_issue_messages[$sltr_email_issue] ?? __('Email sending is not configured.', 'slotera-booking');
        ?>
        <div class="notice notice-error"><p><?php echo esc_html($sltr_email_issue_message); ?> <?php esc_html_e('Complete Email Settings before starting this campaign.', 'slotera-booking'); ?> <a href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'email'], admin_url('admin.php'))); ?>"><?php esc_html_e('Open Email Settings', 'slotera-booking'); ?></a></p></div>
    <?php endif; ?>
    <?php if (!empty($sltr_get['settings_updated'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Marketing queue settings saved.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['test_sent'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Test email sent.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['test_failed'])) : ?><div class="notice notice-error"><p><?php esc_html_e('Test email failed.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (isset($sltr_get['queued']) && empty($sltr_get['email_settings_required'])) : ?><div class="notice notice-success"><p><?php printf(esc_html__('Campaign queued. Queued: %d, Skipped: %d.', 'slotera-booking'), absint($sltr_get['queued']), absint($sltr_get['skipped'] ?? 0)); ?></p></div><?php endif; ?>
    <?php if (isset($sltr_get['processed_sent']) && empty($sltr_get['email_settings_required'])) : ?><div class="notice notice-success"><p><?php printf(esc_html__('Queue processed. Sent: %d, Failed: %d.', 'slotera-booking'), absint($sltr_get['processed_sent']), absint($sltr_get['processed_failed'] ?? 0)); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['paused'])) : ?><div class="notice notice-warning"><p><?php esc_html_e('Campaign paused.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['resumed'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Campaign resumed.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['stopped'])) : ?><div class="notice notice-warning"><p><?php esc_html_e('Campaign stopped.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (isset($sltr_get['retried'])) : ?><div class="notice notice-success"><p><?php printf(esc_html__('Failed emails returned to queue: %d.', 'slotera-booking'), absint($sltr_get['retried'])); ?></p></div><?php endif; ?>
    <?php if (!$marketing_allowed) : ?><div class="notice notice-warning"><p><?php esc_html_e('Marketing is paused because the license period has expired. Bookings and system emails continue to work.', 'slotera-booking'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-license')); ?>"><?php esc_html_e('Open License', 'slotera-booking'); ?></a></p></div><?php elseif ($is_grace_limited) : ?><div class="notice notice-warning"><p><?php esc_html_e('Limited grace period: basic manual campaigns are available, but advanced filters, personal coupons, automations, and queue tuning are locked. Queue processing is capped at 5 emails per batch.', 'slotera-booking'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-license')); ?>"><?php esc_html_e('Activate license', 'slotera-booking'); ?></a></p></div><?php endif; ?>
