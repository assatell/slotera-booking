<?php if (!defined('ABSPATH')) { exit; }
$sltr_get = wp_unslash($_GET);
$id = isset($campaign['id']) ? (int) $campaign['id'] : 0;
$campaign = is_array($campaign ?? null) ? $campaign : [];
$status = (string) ($campaign['status'] ?? 'draft');
$settings = is_array($settings ?? null) ? $settings : [];
$batch = max(1, min(50, (int) ($settings['marketing_emails_per_batch'] ?? 10)));
$interval = (int) ($settings['marketing_cron_interval'] ?? 5);
$max_attempts = max(1, min(10, (int) ($settings['marketing_max_attempts'] ?? 3)));
$require_opt_out_check = (int) ($settings['marketing_require_opt_out_check'] ?? 1) === 1;
$require_unsubscribe_link = (int) ($settings['marketing_require_unsubscribe_link'] ?? 1) === 1;
$minimize_log_payload = (int) ($settings['marketing_minimize_log_payload'] ?? 1) === 1;
$selected_statuses = array_filter(array_map('sanitize_key', explode(',', (string) ($campaign['audience_statuses'] ?? ''))));
$selected_payment_statuses = array_filter(array_map('sanitize_key', explode(',', (string) ($campaign['audience_payment_statuses'] ?? ''))));
$booking_status_options = ['confirmed' => __('Confirmed', 'slotera-booking'), 'completed' => __('Completed', 'slotera-booking'), 'cancelled' => __('Cancelled', 'slotera-booking')];
if (function_exists('sltr_feature_enabled') && sltr_feature_enabled('payments')) { $booking_status_options = ['pending_payment' => __('Awaiting online payment', 'slotera-booking')] + $booking_status_options; }
$payment_status_options = ['unpaid' => __('Unpaid', 'slotera-booking'), 'pending' => __('Pending', 'slotera-booking'), 'processing' => __('Processing', 'slotera-booking'), 'paid' => __('Paid', 'slotera-booking'), 'failed' => __('Failed', 'slotera-booking'), 'refunded' => __('Refunded', 'slotera-booking')];
$selected_template_key = (string) ($campaign['template_key'] ?? '');
if ($selected_template_key === '') { $selected_template_key = 'marketing_promo'; }
$license_status = is_array($license_status ?? null) ? $license_status : [];
$advanced_marketing_allowed = !empty($license_status['advanced_marketing_allowed']);
$unique_coupons_allowed = !empty($license_status['unique_coupons_allowed']);
$queue_settings_allowed = !empty($license_status['queue_settings_allowed']);
$marketing_allowed = !empty($license_status['marketing_allowed']);
$is_grace_limited = (($license_status['state'] ?? '') === 'grace');
?>
<?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/marketing-form/header.php')) { require $sltr_view; } ?>
<?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/marketing-form/campaign-fields.php')) { require $sltr_view; } ?>
</div>
