<?php
if (!defined('ABSPATH')) { exit; }
$event = isset($event) && is_array($event) ? $event : [];
$id = (int) ($event['id'] ?? 0);
$package_id = absint($_GET['return_package_id'] ?? $_GET['package_id'] ?? $event['package_id'] ?? 0);
$package_title = '';
foreach (($packages ?? []) as $package) {
    if ((int) ($package['id'] ?? 0) === $package_id) { $package_title = (string) ($package['title'] ?? ''); break; }
}
$defaults = [
    'package_id' => $package_id, 'title' => $package_title, 'event_date' => current_time('Y-m-d'), 'end_date' => current_time('Y-m-d'),
    'use_time' => 1, 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'timezone' => wp_timezone_string(),
    'capacity' => 1, 'booked_count' => 0, 'price_override' => '0', 'discount_type' => 'none', 'discount_value' => '0',
    'allow_coupons' => 1, 'payment_policy' => 'booking_only', 'deposit_type' => 'percent', 'deposit_value' => '30',
    'location' => '', 'status' => 'scheduled', 'reminder_profile' => 'default',
    'automation_profile' => 'default', 'is_active' => 1,
];
$event = array_merge($defaults, $event);
?>
<div class="wrap sltr-admin-wrap">
    <h1><?php esc_html_e('Scheduled event', 'slotera-booking'); ?></h1>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_save_event">
        <input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>">
        <input type="hidden" name="return_package_id" value="<?php echo esc_attr((string) $package_id); ?>">
        <?php wp_nonce_field('sltr_save_event'); ?>
        <table class="form-table" role="presentation"><tbody>
            <tr><th><?php esc_html_e('Package', 'slotera-booking'); ?></th><td><strong><?php echo esc_html($package_title); ?></strong></td></tr>
            <tr><th><label for="sltr-event-date"><?php esc_html_e('Start date', 'slotera-booking'); ?></label></th><td><input id="sltr-event-date" type="date" name="event_date" value="<?php echo esc_attr((string) $event['event_date']); ?>"> &nbsp; <label for="sltr-event-start-time"><?php esc_html_e('Start time', 'slotera-booking'); ?></label> <input id="sltr-event-start-time" type="time" name="start_time" value="<?php echo esc_attr(substr((string) $event['start_time'], 0, 5)); ?>"></td></tr>
            <tr><th><label for="sltr-event-end-date"><?php esc_html_e('End date', 'slotera-booking'); ?></label></th><td><input id="sltr-event-end-date" type="date" name="end_date" value="<?php echo esc_attr((string) $event['end_date']); ?>"> &nbsp; <label for="sltr-event-end-time"><?php esc_html_e('End time', 'slotera-booking'); ?></label> <input id="sltr-event-end-time" type="time" name="end_time" value="<?php echo esc_attr(substr((string) $event['end_time'], 0, 5)); ?>"></td></tr>
            <tr><th><?php esc_html_e('Use time', 'slotera-booking'); ?></th><td><label><input type="checkbox" name="use_time" value="1" <?php checked((int) $event['use_time'], 1); ?>> <?php esc_html_e('Show and use event times', 'slotera-booking'); ?></label></td></tr>
            <tr><th><label for="sltr-event-timezone"><?php esc_html_e('Timezone', 'slotera-booking'); ?></label></th><td><input id="sltr-event-timezone" class="regular-text" type="text" name="timezone" value="<?php echo esc_attr((string) $event['timezone']); ?>"><p class="description"><?php esc_html_e('Use an IANA timezone such as Europe/Tallinn.', 'slotera-booking'); ?></p></td></tr>
            <tr><th><label for="sltr-event-capacity"><?php esc_html_e('Capacity', 'slotera-booking'); ?></label></th><td><input id="sltr-event-capacity" type="number" min="1" name="capacity" value="<?php echo esc_attr((string) (int) $event['capacity']); ?>"></td></tr>
            <tr><th><label for="sltr-event-price"><?php esc_html_e('Price', 'slotera-booking'); ?></label></th><td><input id="sltr-event-price" type="number" step="0.01" min="0" name="price_override" required value="<?php echo esc_attr((string) $event['price_override']); ?>"></td></tr>
            <tr><th><?php esc_html_e('Discount', 'slotera-booking'); ?></th><td><select name="discount_type"><option value="none" <?php selected($event['discount_type'], 'none'); ?>><?php esc_html_e('None', 'slotera-booking'); ?></option><option value="percent" <?php selected($event['discount_type'], 'percent'); ?>><?php esc_html_e('Percent', 'slotera-booking'); ?></option><option value="fixed" <?php selected($event['discount_type'], 'fixed'); ?>><?php esc_html_e('Fixed amount', 'slotera-booking'); ?></option></select> <input type="number" step="0.01" min="0" name="discount_value" value="<?php echo esc_attr((string) $event['discount_value']); ?>"></td></tr>
            <tr><th><?php esc_html_e('Coupons', 'slotera-booking'); ?></th><td><label><input type="checkbox" name="allow_coupons" value="1" <?php checked((int) $event['allow_coupons'], 1); ?>> <?php esc_html_e('Allow coupons for this event', 'slotera-booking'); ?></label></td></tr>
            <tr><th><?php esc_html_e('Payment options', 'slotera-booking'); ?></th><td>
                <?php
                $event_policy = (string) ($event['payment_policy'] ?? 'booking_only');
                $event_payment_options = ['booking_only'];
                if ($event_policy === 'full_payment') { $event_payment_options = ['full_payment']; }
                elseif ($event_policy === 'deposit_payment') { $event_payment_options = ['deposit_payment']; }
                elseif ($event_policy === 'full_or_deposit') { $event_payment_options = ['full_payment', 'deposit_payment']; }
                elseif ($event_policy === 'booking_or_full') { $event_payment_options = ['booking_only', 'full_payment']; }
                elseif ($event_policy === 'booking_or_deposit') { $event_payment_options = ['booking_only', 'deposit_payment']; }
                elseif ($event_policy === 'all_options') { $event_payment_options = ['booking_only', 'deposit_payment', 'full_payment']; }
                ?>
                <label style="display:block;margin:4px 0;"><input type="checkbox" name="payment_options[]" value="booking_only" <?php checked(in_array('booking_only', $event_payment_options, true)); ?>> <?php esc_html_e('Pay on arrival / booking only', 'slotera-booking'); ?></label>
                <label style="display:block;margin:4px 0;"><input type="checkbox" name="payment_options[]" value="deposit_payment" <?php checked(in_array('deposit_payment', $event_payment_options, true)); ?>> <?php esc_html_e('Prepayment / deposit', 'slotera-booking'); ?></label>
                <label style="display:block;margin:4px 0;"><input type="checkbox" name="payment_options[]" value="full_payment" <?php checked(in_array('full_payment', $event_payment_options, true)); ?>> <?php esc_html_e('Full payment', 'slotera-booking'); ?></label>
                <p><label><?php esc_html_e('Deposit', 'slotera-booking'); ?> <select name="deposit_type"><option value="percent" <?php selected((string) $event['deposit_type'], 'percent'); ?>>%</option><option value="fixed" <?php selected((string) $event['deposit_type'], 'fixed'); ?>><?php esc_html_e('Fixed', 'slotera-booking'); ?></option></select> <input type="number" step="0.01" min="0" name="deposit_value" value="<?php echo esc_attr((string) $event['deposit_value']); ?>"></label></p>
            </td></tr>
            <tr><th><label for="sltr-event-location"><?php esc_html_e('Location', 'slotera-booking'); ?></label></th><td><input id="sltr-event-location" class="regular-text" type="text" name="location" value="<?php echo esc_attr((string) $event['location']); ?>"></td></tr>
            <tr><th><label for="sltr-event-status"><?php esc_html_e('Status', 'slotera-booking'); ?></label></th><td><select id="sltr-event-status" name="status"><?php foreach (['scheduled','draft','cancelled','completed'] as $status): ?><option value="<?php echo esc_attr($status); ?>" <?php selected((string)$event['status'],$status); ?>><?php echo esc_html(ucfirst($status)); ?></option><?php endforeach; ?></select></td></tr>
            <tr><th><?php esc_html_e('Active', 'slotera-booking'); ?></th><td><label><input type="checkbox" name="is_active" value="1" <?php checked((int) $event['is_active'], 1); ?>> <?php esc_html_e('Enable this event', 'slotera-booking'); ?></label></td></tr>
        </tbody></table>
        <p><button class="button button-primary"><?php esc_html_e('Save changes', 'slotera-booking'); ?></button> <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=slotera-packages&action=edit&id=' . $package_id)); ?>"><?php esc_html_e('Back to package details', 'slotera-booking'); ?></a></p>
    </form>
</div>
