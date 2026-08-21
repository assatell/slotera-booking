<?php
if (!defined('ABSPATH')) { exit; }
$sltr_event = isset($sltr_event) && is_array($sltr_event) ? $sltr_event : [];
$sltr_event_defaults = [
    'id' => 0,
    'event_date' => current_time('Y-m-d'),
    'end_date' => current_time('Y-m-d'),
    'use_time' => 1,
    'start_time' => '09:00:00',
    'end_time' => '10:00:00',
    'timezone' => wp_timezone_string(),
    'capacity' => 1,
    'price_override' => '0',
    'discount_type' => 'none',
    'discount_value' => '0',
    'allow_coupons' => 1,
    'payment_policy' => 'booking_only',
    'deposit_type' => 'percent',
    'deposit_value' => '30',
    'location' => '',
    'status' => 'scheduled',
    'is_active' => 1,
];
$sltr_event = array_merge($sltr_event_defaults, $sltr_event);
$sltr_event_policy = (string) ($sltr_event['payment_policy'] ?? 'booking_only');
$sltr_event_payment_options = ['booking_only'];
if ($sltr_event_policy === 'full_payment') { $sltr_event_payment_options = ['full_payment']; }
elseif ($sltr_event_policy === 'deposit_payment') { $sltr_event_payment_options = ['deposit_payment']; }
elseif ($sltr_event_policy === 'full_or_deposit') { $sltr_event_payment_options = ['full_payment', 'deposit_payment']; }
elseif ($sltr_event_policy === 'booking_or_full') { $sltr_event_payment_options = ['booking_only', 'full_payment']; }
elseif ($sltr_event_policy === 'booking_or_deposit') { $sltr_event_payment_options = ['booking_only', 'deposit_payment']; }
elseif ($sltr_event_policy === 'all_options') { $sltr_event_payment_options = ['booking_only', 'deposit_payment', 'full_payment']; }
?>
<tr class="sltr-scheduled-event-heading"><th colspan="2"><h2 style="margin:0;"><?php esc_html_e('Scheduled event', 'slotera-booking'); ?></h2></th></tr>
<tr><th><?php esc_html_e('Package', 'slotera-booking'); ?></th><td><strong data-sltr-event-package-title><?php echo esc_html((string) ($package['title'] ?? __('New package', 'slotera-booking'))); ?></strong><input type="hidden" name="scheduled_event_id" value="<?php echo esc_attr((string) (int) $sltr_event['id']); ?>"></td></tr>
<tr><th><label for="sltr-event-date"><?php esc_html_e('Start date', 'slotera-booking'); ?></label></th><td><input id="sltr-event-date" type="date" name="event_date" value="<?php echo esc_attr((string) $sltr_event['event_date']); ?>"> &nbsp; <label for="sltr-event-start-time"><?php esc_html_e('Start time', 'slotera-booking'); ?></label> <input id="sltr-event-start-time" type="time" name="start_time" value="<?php echo esc_attr(substr((string) $sltr_event['start_time'], 0, 5)); ?>"></td></tr>
<tr><th><label for="sltr-event-end-date"><?php esc_html_e('End date', 'slotera-booking'); ?></label></th><td><input id="sltr-event-end-date" type="date" name="end_date" value="<?php echo esc_attr((string) $sltr_event['end_date']); ?>"> &nbsp; <label for="sltr-event-end-time"><?php esc_html_e('End time', 'slotera-booking'); ?></label> <input id="sltr-event-end-time" type="time" name="end_time" value="<?php echo esc_attr(substr((string) $sltr_event['end_time'], 0, 5)); ?>"></td></tr>
<tr><th><?php esc_html_e('Use time', 'slotera-booking'); ?></th><td><label><input type="checkbox" name="use_time" value="1" <?php checked((int) $sltr_event['use_time'], 1); ?>> <?php esc_html_e('Show and use event times', 'slotera-booking'); ?></label></td></tr>
<tr><th><label for="sltr-event-timezone"><?php esc_html_e('Timezone', 'slotera-booking'); ?></label></th><td><input id="sltr-event-timezone" class="regular-text" type="text" name="timezone" value="<?php echo esc_attr((string) $sltr_event['timezone']); ?>"><p class="description"><?php esc_html_e('Use an IANA timezone such as Europe/Tallinn.', 'slotera-booking'); ?></p></td></tr>
<tr><th><label for="sltr-event-capacity"><?php esc_html_e('Capacity', 'slotera-booking'); ?></label></th><td><input id="sltr-event-capacity" type="number" min="1" name="capacity" value="<?php echo esc_attr((string) (int) $sltr_event['capacity']); ?>"></td></tr>
<tr><th><label for="sltr-event-price"><?php esc_html_e('Price', 'slotera-booking'); ?></label></th><td><input id="sltr-event-price" type="number" step="0.01" min="0" name="price_override" value="<?php echo esc_attr((string) $sltr_event['price_override']); ?>"></td></tr>
<tr><th><?php esc_html_e('Discount', 'slotera-booking'); ?></th><td><select name="event_discount_type"><option value="none" <?php selected($sltr_event['discount_type'], 'none'); ?>><?php esc_html_e('None', 'slotera-booking'); ?></option><option value="percent" <?php selected($sltr_event['discount_type'], 'percent'); ?>><?php esc_html_e('Percent', 'slotera-booking'); ?></option><option value="fixed" <?php selected($sltr_event['discount_type'], 'fixed'); ?>><?php esc_html_e('Fixed amount', 'slotera-booking'); ?></option></select> <input type="number" step="0.01" min="0" name="event_discount_value" value="<?php echo esc_attr((string) $sltr_event['discount_value']); ?>"></td></tr>
<tr><th><?php esc_html_e('Coupons', 'slotera-booking'); ?></th><td><label><input type="checkbox" name="event_allow_coupons" value="1" <?php checked((int) $sltr_event['allow_coupons'], 1); ?>> <?php esc_html_e('Allow coupons for this event', 'slotera-booking'); ?></label></td></tr>
<tr><th><?php esc_html_e('Payment options', 'slotera-booking'); ?></th><td>
    <label style="display:block;margin:4px 0;"><input type="checkbox" name="event_payment_options[]" value="booking_only" <?php checked(in_array('booking_only', $sltr_event_payment_options, true)); ?>> <?php esc_html_e('Pay on arrival / booking only', 'slotera-booking'); ?></label>
    <label style="display:block;margin:4px 0;"><input type="checkbox" name="event_payment_options[]" value="deposit_payment" <?php checked(in_array('deposit_payment', $sltr_event_payment_options, true)); ?>> <?php esc_html_e('Prepayment / deposit', 'slotera-booking'); ?></label>
    <label style="display:block;margin:4px 0;"><input type="checkbox" name="event_payment_options[]" value="full_payment" <?php checked(in_array('full_payment', $sltr_event_payment_options, true)); ?>> <?php esc_html_e('Full payment', 'slotera-booking'); ?></label>
    <p><label><?php esc_html_e('Deposit', 'slotera-booking'); ?> <select name="event_deposit_type"><option value="percent" <?php selected((string) $sltr_event['deposit_type'], 'percent'); ?>>%</option><option value="fixed" <?php selected((string) $sltr_event['deposit_type'], 'fixed'); ?>><?php esc_html_e('Fixed', 'slotera-booking'); ?></option></select> <input type="number" step="0.01" min="0" name="event_deposit_value" value="<?php echo esc_attr((string) $sltr_event['deposit_value']); ?>"></label></p>
</td></tr>
<tr><th><label for="sltr-event-location"><?php esc_html_e('Location', 'slotera-booking'); ?></label></th><td><input id="sltr-event-location" class="regular-text" type="text" name="event_location" value="<?php echo esc_attr((string) $sltr_event['location']); ?>"></td></tr>
<tr><th><label for="sltr-event-status"><?php esc_html_e('Status', 'slotera-booking'); ?></label></th><td><select id="sltr-event-status" name="event_status"><?php foreach (['scheduled','draft','cancelled','completed'] as $status): ?><option value="<?php echo esc_attr($status); ?>" <?php selected((string) $sltr_event['status'], $status); ?>><?php echo esc_html(ucfirst($status)); ?></option><?php endforeach; ?></select></td></tr>
<tr><th><?php esc_html_e('Active', 'slotera-booking'); ?></th><td><label><input type="checkbox" name="event_is_active" value="1" <?php checked((int) $sltr_event['is_active'], 1); ?>> <?php esc_html_e('Enable this event', 'slotera-booking'); ?></label></td></tr>
