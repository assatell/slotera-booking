<?php if (!defined('ABSPATH')) { exit; } ?>
<?php $sltr_get = wp_unslash($_GET); ?>
<?php $sltr_automation_cta_default = sltr_t('Book now', 'emails', \Slotera\Application\Services\EmailTemplateRegistry::runtime_locale()); ?>
<?php
$sltr_marketing_tabs = ['come-back', 'after-booking', 'campaigns'];
$sltr_marketing_tab = isset($sltr_get['sltr_marketing_tab']) ? sanitize_key((string) $sltr_get['sltr_marketing_tab']) : 'come-back';
if (!in_array($sltr_marketing_tab, $sltr_marketing_tabs, true)) { $sltr_marketing_tab = 'come-back'; }
?>
<div class="wrap sltr-admin-wrap sltr-marketing-page sltr-pro-feature-page sltr-full-width-admin sltr-page-stack">
    <?php $sltr_marketing_section = 'automation'; require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-shell-tabs.php'; ?>
    <nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e('Marketing email sections', 'slotera-booking'); ?>">
        <a class="nav-tab <?php echo $sltr_marketing_tab === 'come-back' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=come-back')); ?>"><?php esc_html_e('Come back automation', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $sltr_marketing_tab === 'after-booking' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=after-booking')); ?>"><?php esc_html_e('After booking automation', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $sltr_marketing_tab === 'campaigns' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=campaigns')); ?>"><?php esc_html_e('Automation campaigns', 'slotera-booking'); ?></a>
    </nav>
    <?php
    $sltr_external_mail_detector = new \Slotera\Application\Services\ExternalMailPluginDetector();
    $sltr_external_mail_plugins = $sltr_external_mail_detector->detected();
    $sltr_external_mail_names = $sltr_external_mail_detector->detected_names();
    ?>
    <?php if ($sltr_external_mail_plugins !== []) : ?>
        <div class="notice notice-info"><p><strong><?php esc_html_e('External email delivery detected:', 'slotera-booking'); ?></strong> <?php echo esc_html($sltr_external_mail_names); ?>. <?php esc_html_e('Marketing Emails remain available. Slotera sends through wp_mail(), so your existing email delivery plugin can handle transport. To avoid conflicts, leave Slotera SMTP disabled; disable the external delivery plugin first only if you intentionally want Slotera to manage SMTP.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($sltr_get['license_limited']) || (isset($license_status) && empty($license_status['marketing_allowed']))) : ?>
        <div class="notice notice-warning"><p><?php esc_html_e('Marketing is paused because the license period has expired. Bookings continue to work. Activate the yearly license to resume campaigns and automations.', 'slotera-booking'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-license')); ?>"><?php esc_html_e('Open License', 'slotera-booking'); ?></a></p></div>
    <?php elseif (isset($license_status) && ($license_status['state'] ?? '') === 'grace') : ?>
        <div class="notice notice-warning"><p><?php esc_html_e('Limited grace period: basic manual campaigns remain available. Automations, advanced filters, personal coupons and queue tuning are locked until license activation.', 'slotera-booking'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-license')); ?>"><?php esc_html_e('Activate license', 'slotera-booking'); ?></a></p></div>
    <?php endif; ?>

    <?php if (!empty($sltr_get['deleted'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Campaign deleted.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['automation_stopped'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Automation stopped.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['automation_started'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Automation started.', 'slotera-booking'); ?></p></div><?php endif; ?>
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
        <div class="notice notice-error"><p><?php echo esc_html($sltr_email_issue_message); ?> <?php esc_html_e('Complete Email Settings before starting this automation.', 'slotera-booking'); ?> <a href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'email'], admin_url('admin.php'))); ?>"><?php esc_html_e('Open Email Settings', 'slotera-booking'); ?></a></p></div>
    <?php endif; ?>

    <?php if (!empty($sltr_get['automation_saved'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Come back automation settings saved.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['automation_ran']) && empty($sltr_get['email_settings_required'])) : ?><div class="notice notice-success"><p><?php if (absint($sltr_get['automation_queued'] ?? 0) === 0) { esc_html_e('Come back automation completed. No eligible recipients were found.', 'slotera-booking'); } else { printf(esc_html__('Come back automation processed. Queued: %1$d, Skipped: %2$d.', 'slotera-booking'), absint($sltr_get['automation_queued'] ?? 0), absint($sltr_get['automation_skipped'] ?? 0)); } ?> <?php if (absint($sltr_get['automation_campaign_id'] ?? 0) > 0) : ?><a href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=campaigns'));  ?>"><?php esc_html_e('Open created campaign', 'slotera-booking'); ?></a><?php endif; ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['after_booking_automation_saved'])) : ?><div class="notice notice-success"><p><?php esc_html_e('After booking automation settings saved.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['settings_updated'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Marketing queue settings saved.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['test_sent'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Test email sent.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['test_failed'])) : ?><div class="notice notice-error"><p><?php esc_html_e('Test email could not be sent.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['after_booking_automation_ran']) && empty($sltr_get['email_settings_required'])) : ?><div class="notice notice-success"><p><?php if (absint($sltr_get['after_booking_automation_queued'] ?? 0) === 0) { esc_html_e('After booking automation completed. No eligible recipients were found.', 'slotera-booking'); } else { printf(esc_html__('After booking automation processed. Queued: %1$d, Skipped: %2$d.', 'slotera-booking'), absint($sltr_get['after_booking_automation_queued'] ?? 0), absint($sltr_get['after_booking_automation_skipped'] ?? 0)); } ?> <?php if (absint($sltr_get['after_booking_automation_campaign_id'] ?? 0) > 0) : ?><a href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=campaigns'));  ?>"><?php esc_html_e('Open created campaign', 'slotera-booking'); ?></a><?php endif; ?></p></div><?php endif; ?>

    <?php if ($sltr_marketing_tab === 'come-back') : ?>
        <?php $sltr_automation_type = 'come-back'; require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-automation-editor.php'; ?>
    <?php endif; ?>

    <?php if ($sltr_marketing_tab === 'after-booking') : ?>
        <?php $sltr_automation_type = 'after-booking'; require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-automation-editor.php'; ?>
    <?php endif; ?>

    <?php if ($sltr_marketing_tab === 'campaigns') : ?>
        <?php $sltr_history_context = 'automation'; require SLTR_PLUGIN_DIR . 'includes/Admin/Views/campaign-history-table.php'; ?>
    <?php endif; ?>
</div>
