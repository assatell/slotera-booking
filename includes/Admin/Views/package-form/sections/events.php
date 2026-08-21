<?php
if (!defined('ABSPATH')) { exit; }
$sltr_is_event_package = $sltr_active_mode === 'date_range_inventory'
    && (string) $sltr_mode_value('date_range_inventory', 'date_flow', 'customer_choice') === 'admin_scheduled';
?>
<tr>
    <th scope="row"><label for="sltr-package-booking-type"><?php esc_html_e('Booking mode', 'slotera-booking'); ?></label></th>
    <td>
        <select id="sltr-package-booking-type" name="package_booking_type">
            <option value="standard" <?php selected(!$sltr_is_event_package); ?>><?php esc_html_e('Standard booking', 'slotera-booking'); ?></option>
            <option value="events" <?php selected($sltr_is_event_package); ?>><?php esc_html_e('Scheduled events', 'slotera-booking'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Choose how customers book this package. The settings below update immediately for the selected mode.', 'slotera-booking'); ?></p>
    </td>
</tr>
