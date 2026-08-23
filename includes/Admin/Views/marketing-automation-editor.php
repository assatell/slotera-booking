<?php
if (!defined('ABSPATH')) { exit; }

$sltr_after = $sltr_automation_type === 'after-booking';
$sltr_prefix = $sltr_after ? 'after_booking_automation_' : 'comeback_automation_';
$sltr_title = $sltr_after ? __('After booking automation', 'slotera-booking') : __('Come back automation', 'slotera-booking');
$sltr_description = $sltr_after
    ? __('Automatically sends a personal follow-up offer after a completed booking.', 'slotera-booking')
    : __('Automatically sends a personal offer to customers who have not booked for the configured number of days.', 'slotera-booking');
$sltr_save_action = $sltr_after ? 'sltr_save_after_booking_automation' : 'sltr_save_comeback_automation';
$sltr_save_label = $sltr_after ? __('Save after booking automation', 'slotera-booking') : __('Save automation', 'slotera-booking');
$sltr_offer_type = (string) ($settings[$sltr_prefix . 'offer_discount_type'] ?? 'percent');
$sltr_offer_value = (float) ($settings[$sltr_prefix . 'offer_discount_value'] ?? 10);
$sltr_offer_valid_days = max(1, (int) ($settings[$sltr_prefix . 'offer_valid_days'] ?? 14));
$sltr_offer_package_ids = array_values(array_filter(array_map('absint', explode(',', (string) ($settings[$sltr_prefix . 'offer_package_ids'] ?? '')))));

$batch = max(1, min(50, (int) ($settings['marketing_emails_per_batch'] ?? 10)));
$interval = (int) ($settings['marketing_cron_interval'] ?? 5);
if (!in_array($interval, [1,5,10,15], true)) { $interval = 5; }
$max_attempts = max(1, min(10, (int) ($settings['marketing_max_attempts'] ?? 3)));
$require_opt_out_check = (int) ($settings['marketing_require_opt_out_check'] ?? 1) === 1;
$require_unsubscribe_link = (int) ($settings['marketing_require_unsubscribe_link'] ?? 1) === 1;
$minimize_log_payload = (int) ($settings['marketing_minimize_log_payload'] ?? 1) === 1;
$queue_settings_allowed = !empty($license_status['queue_settings_allowed']);
$marketing_allowed = !empty($license_status['marketing_allowed']);
$automation_allowed = !empty($license_status['automation_allowed']);
$preview_url = wp_nonce_url(
    admin_url('admin-post.php?action=sltr_preview_marketing_automation&type=' . rawurlencode($sltr_automation_type)),
    'sltr_preview_marketing_automation_' . $sltr_automation_type
);
?>
<section class="sltr-panel sltr-marketing-panel">
    <h2><?php echo esc_html($sltr_title); ?></h2>
    <p class="description"><?php echo esc_html($sltr_description); ?></p>
    <form id="sltr-automation-settings-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="<?php echo esc_attr($sltr_save_action); ?>">
        <?php wp_nonce_field($sltr_save_action); ?>
        <table class="form-table" role="presentation"><tbody>
            <tr><th scope="row"><?php esc_html_e('Offer', 'slotera-booking'); ?></th><td>
                <p><strong><?php esc_html_e('Every recipient receives a unique one-use coupon automatically.', 'slotera-booking'); ?></strong></p>
                <p>
                    <label for="<?php echo esc_attr($sltr_prefix); ?>offer_discount_value"><strong><?php esc_html_e('Discount', 'slotera-booking'); ?></strong></label><br>
                    <input type="number" min="0" step="0.01" id="<?php echo esc_attr($sltr_prefix); ?>offer_discount_value" name="<?php echo esc_attr($sltr_prefix); ?>offer_discount_value" value="<?php echo esc_attr((string) $sltr_offer_value); ?>">
                    <select name="<?php echo esc_attr($sltr_prefix); ?>offer_discount_type">
                        <option value="percent" <?php selected($sltr_offer_type, 'percent'); ?>><?php esc_html_e('Percent', 'slotera-booking'); ?></option>
                        <option value="fixed" <?php selected($sltr_offer_type, 'fixed'); ?>><?php esc_html_e('Fixed amount', 'slotera-booking'); ?></option>
                    </select>
                </p>
                <p>
                    <label for="<?php echo esc_attr($sltr_prefix); ?>offer_valid_days"><strong><?php esc_html_e('Valid for', 'slotera-booking'); ?></strong></label><br>
                    <input type="number" min="1" max="3650" id="<?php echo esc_attr($sltr_prefix); ?>offer_valid_days" name="<?php echo esc_attr($sltr_prefix); ?>offer_valid_days" value="<?php echo esc_attr((string) $sltr_offer_valid_days); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?>
                </p>
                <p><strong><?php esc_html_e('Packages', 'slotera-booking'); ?></strong></p>
                <div class="sltr-admin-inline-fields">
                    <?php foreach (($packages ?? []) as $package) : $package_id = (int) ($package['id'] ?? 0); ?>
                        <label class="sltr-option-inline"><input type="checkbox" name="<?php echo esc_attr($sltr_prefix); ?>offer_package_ids[]" value="<?php echo esc_attr((string) $package_id); ?>" <?php checked(in_array($package_id, $sltr_offer_package_ids, true)); ?>> <?php echo esc_html(trim((string) ($package['title'] ?? '')) !== '' ? (string) $package['title'] : ('#' . $package_id)); ?></label>
                    <?php endforeach; ?>
                </div>
                <p class="description"><?php esc_html_e('Leave all packages unchecked to make the offer valid for any package.', 'slotera-booking'); ?></p>
                <p><strong><?php esc_html_e('Usage', 'slotera-booking'); ?></strong><br><?php esc_html_e('One time per recipient.', 'slotera-booking'); ?></p>
            </td></tr>

            <?php if ($sltr_after) : ?>
                <tr><th scope="row"><label for="after_booking_automation_delay_days"><?php esc_html_e('Send after', 'slotera-booking'); ?></label></th><td><input type="number" min="0" max="3650" id="after_booking_automation_delay_days" name="after_booking_automation_delay_days" value="<?php echo esc_attr((string) (int) ($settings['after_booking_automation_delay_days'] ?? 3)); ?>"> <?php esc_html_e('days after completed booking', 'slotera-booking'); ?></td></tr>
                <tr><th scope="row"><label for="after_booking_automation_repeat_days"><?php esc_html_e('Do not repeat for', 'slotera-booking'); ?></label></th><td><input type="number" min="1" max="3650" id="after_booking_automation_repeat_days" name="after_booking_automation_repeat_days" value="<?php echo esc_attr((string) (int) ($settings['after_booking_automation_repeat_days'] ?? 30)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?></td></tr>
            <?php else : ?>
                <tr><th scope="row"><label for="comeback_automation_inactive_days"><?php esc_html_e('Inactive for', 'slotera-booking'); ?></label></th><td><input type="number" min="1" max="3650" id="comeback_automation_inactive_days" name="comeback_automation_inactive_days" value="<?php echo esc_attr((string) (int) ($settings['comeback_automation_inactive_days'] ?? 30)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?></td></tr>
                <tr><th scope="row"><label for="comeback_automation_repeat_days"><?php esc_html_e('Do not repeat for', 'slotera-booking'); ?></label></th><td><input type="number" min="1" max="3650" id="comeback_automation_repeat_days" name="comeback_automation_repeat_days" value="<?php echo esc_attr((string) (int) ($settings['comeback_automation_repeat_days'] ?? 90)); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?></td></tr>
            <?php endif; ?>

            <tr><th scope="row"><label><?php esc_html_e('Template', 'slotera-booking'); ?></label></th><td><select name="<?php echo esc_attr($sltr_prefix); ?>template_key"><?php foreach (($templates ?? []) as $key => $template) : ?><option value="<?php echo esc_attr((string) $key); ?>" <?php selected((string) ($settings[$sltr_prefix . 'template_key'] ?? 'marketing_promo'), (string) $key); ?>><?php echo esc_html((string) ($template['title'] ?? $key)); ?></option><?php endforeach; ?></select></td></tr>
            <tr><th scope="row"><label><?php esc_html_e('Subject', 'slotera-booking'); ?></label></th><td><input class="regular-text" name="<?php echo esc_attr($sltr_prefix); ?>subject_override" value="<?php echo esc_attr((string) ($settings[$sltr_prefix . 'subject_override'] ?? '')); ?>"></td></tr>
            <tr><th scope="row"><?php esc_html_e('Message', 'slotera-booking'); ?></th><td>
                <p><input class="large-text" name="<?php echo esc_attr($sltr_prefix); ?>headline" value="<?php echo esc_attr((string) ($settings[$sltr_prefix . 'headline'] ?? '')); ?>" placeholder="<?php echo esc_attr($sltr_after ? __('Thank you for your booking ✨', 'slotera-booking') : __('We miss you 👋', 'slotera-booking')); ?>"></p>
                <p><textarea class="large-text" rows="3" name="<?php echo esc_attr($sltr_prefix); ?>message"><?php echo esc_textarea((string) ($settings[$sltr_prefix . 'message'] ?? '')); ?></textarea></p>
                <p><textarea class="large-text" rows="2" name="<?php echo esc_attr($sltr_prefix); ?>submessage"><?php echo esc_textarea((string) ($settings[$sltr_prefix . 'submessage'] ?? '')); ?></textarea></p>
            </td></tr>
            <tr><th scope="row"><?php esc_html_e('CTA', 'slotera-booking'); ?></th><td>
                <label><input type="checkbox" name="<?php echo esc_attr($sltr_prefix); ?>cta_enabled" value="1" <?php checked((int) ($settings[$sltr_prefix . 'cta_enabled'] ?? 1), 1); ?>> <?php esc_html_e('Show CTA button', 'slotera-booking'); ?></label><br>
                <input class="regular-text sltr-field-spaced" name="<?php echo esc_attr($sltr_prefix); ?>cta_label" value="<?php echo esc_attr(trim((string) ($settings[$sltr_prefix . 'cta_label'] ?? '')) !== '' ? (string) $settings[$sltr_prefix . 'cta_label'] : $sltr_automation_cta_default); ?>"><br>
                <select class="sltr-field-spaced" name="<?php echo esc_attr($sltr_prefix); ?>cta_url_type"><option value="booking" <?php selected((string) ($settings[$sltr_prefix . 'cta_url_type'] ?? 'booking'), 'booking'); ?>><?php esc_html_e('Booking page', 'slotera-booking'); ?></option><option value="package" <?php selected((string) ($settings[$sltr_prefix . 'cta_url_type'] ?? 'booking'), 'package'); ?>><?php esc_html_e('Package solo page', 'slotera-booking'); ?></option><option value="custom" <?php selected((string) ($settings[$sltr_prefix . 'cta_url_type'] ?? 'booking'), 'custom'); ?>><?php esc_html_e('Custom URL', 'slotera-booking'); ?></option></select><br>
                <input class="regular-text sltr-field-spaced" name="<?php echo esc_attr($sltr_prefix); ?>cta_custom_url" value="<?php echo esc_attr((string) ($settings[$sltr_prefix . 'cta_custom_url'] ?? '')); ?>" placeholder="https://example.com/book">
            </td></tr>

            <tr><th scope="row"><?php esc_html_e('Marketing queue settings', 'slotera-booking'); ?></th><td>
                <p><label><?php esc_html_e('Emails per batch', 'slotera-booking'); ?> <input type="number" min="1" max="50" name="marketing_emails_per_batch" value="<?php echo esc_attr((string) ($queue_settings_allowed ? $batch : 5)); ?>" <?php disabled(!$queue_settings_allowed); ?>></label></p>
                <p><label><?php esc_html_e('Cron interval', 'slotera-booking'); ?> <select name="marketing_cron_interval" <?php disabled(!$queue_settings_allowed); ?>><?php foreach ([1,5,10,15] as $m) : ?><option value="<?php echo esc_attr((string) $m); ?>" <?php selected($interval, $m); ?>><?php printf(esc_html__('%d minutes', 'slotera-booking'), $m); ?></option><?php endforeach; ?></select></label></p>
                <p><label><?php esc_html_e('Max attempts', 'slotera-booking'); ?> <input type="number" min="1" max="10" name="marketing_max_attempts" value="<?php echo esc_attr((string) $max_attempts); ?>" <?php disabled(!$queue_settings_allowed); ?>></label></p>
                <p><label><input type="checkbox" value="1" checked disabled> <?php esc_html_e('Check explicit consent and marketing opt-out before queueing and sending (required).', 'slotera-booking'); ?></label></p>
                <p><label><input type="checkbox" value="1" checked disabled> <?php esc_html_e('Include unsubscribe footer and List-Unsubscribe headers (required).', 'slotera-booking'); ?></label></p>
                <p><label><input type="checkbox" value="1" checked disabled> <?php esc_html_e('Minimize personal data in marketing logs (required).', 'slotera-booking'); ?></label></p>
            </td></tr>
            <tr><th scope="row"><?php esc_html_e('Schedule', 'slotera-booking'); ?></th><td><p class="description"><?php printf(esc_html__('Automation check runs hourly. Next check: %s. Last run: %s.', 'slotera-booking'), !empty($automation_next_run) ? esc_html(wp_date('Y-m-d H:i', (int) $automation_next_run)) : esc_html__('not scheduled', 'slotera-booking'), esc_html((string) ($settings[$sltr_prefix . 'last_run'] ?? 'never'))); ?></p></td></tr>
        </tbody></table>
    </form>

    <div class="sltr-panel__body">
        <h3><?php esc_html_e('Preview and test', 'slotera-booking'); ?></h3>
        <p class="description"><?php esc_html_e('Preview and test use the saved automation settings. They do not create a campaign or real personal coupons.', 'slotera-booking'); ?></p>
        <p><label><strong><?php esc_html_e('Preview / test as email', 'slotera-booking'); ?></strong></label><br><input type="email" class="regular-text sltr-automation-preview-email" id="sltr_marketing_preview_email" value="<?php echo esc_attr((string) get_option('admin_email')); ?>"> <button type="button" class="button button-secondary sltr-marketing-preview-open" data-preview-url="<?php echo esc_url($preview_url); ?>"><?php esc_html_e('Preview in popup', 'slotera-booking'); ?></button></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-marketing-test-form"><input type="hidden" name="action" value="sltr_send_marketing_automation_test"><input type="hidden" name="type" value="<?php echo esc_attr($sltr_automation_type); ?>"><?php wp_nonce_field('sltr_send_marketing_automation_test_' . $sltr_automation_type); ?><input type="email" name="test_email" class="regular-text" value="<?php echo esc_attr((string) get_option('admin_email')); ?>"> <?php submit_button(__('Send test email with preview data', 'slotera-booking'), 'secondary', 'submit', false); ?></form>
        <div class="sltr-marketing-preview-modal" aria-hidden="true"><div class="sltr-marketing-preview-backdrop"></div><div class="sltr-marketing-preview-dialog" role="dialog" aria-modal="true"><div class="sltr-marketing-preview-header"><strong><?php esc_html_e('Automation preview', 'slotera-booking'); ?></strong><button type="button" class="button-link sltr-marketing-preview-close">×</button></div><iframe title="<?php esc_attr_e('Automation preview', 'slotera-booking'); ?>" src="about:blank"></iframe></div></div>
        <?php submit_button($sltr_save_label, 'primary', 'submit', false, $marketing_allowed && $automation_allowed ? ['form' => 'sltr-automation-settings-form'] : ['disabled' => 'disabled', 'form' => 'sltr-automation-settings-form']); ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-left:8px;">
            <input type="hidden" name="action" value="sltr_run_marketing_automation">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="type" value="<?php echo esc_attr($sltr_automation_type); ?>">
            <?php wp_nonce_field('sltr_run_marketing_automation_' . $sltr_automation_type); ?>
            <?php submit_button(__('Run now', 'slotera-booking'), 'secondary', 'submit', false, $marketing_allowed && $automation_allowed ? [] : ['disabled' => 'disabled']); ?>
        </form>
    </div>
</section>
