<?php
if (!defined('ABSPATH')) { exit; }
$status = (string) ($settings['booking_availability_status'] ?? 'available');
?>
<div class="sltr-card">
    <h2><?php esc_html_e('Booking availability', 'slotera-booking'); ?></h2>
    <p><?php esc_html_e('Temporarily pause new bookings without taking your website offline. Visitors can continue browsing packages, while booking forms and checkout are disabled until bookings are resumed.', 'slotera-booking'); ?></p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_save_booking_availability">
        <?php wp_nonce_field('sltr_save_booking_availability'); ?>
        <table class="form-table" role="presentation"><tbody><tr>
            <th scope="row"><label for="sltr-booking-availability-status"><?php esc_html_e('Booking availability', 'slotera-booking'); ?></label></th>
            <td>
                <select id="sltr-booking-availability-status" name="booking_availability_status">
                    <option value="available" <?php selected($status, 'available'); ?>><?php esc_html_e('Available', 'slotera-booking'); ?></option>
                    <option value="paused" <?php selected($status, 'paused'); ?>><?php esc_html_e('Pause new bookings', 'slotera-booking'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Pausing affects only new bookings. Existing bookings, package pages, client accounts and the WordPress admin area remain available.', 'slotera-booking'); ?></p>
            </td>
        </tr></tbody></table>
        <?php
        submit_button(__('Save changes'), 'primary', 'submit', true, [
            'id' => 'sltr-booking-availability-submit',
            'disabled' => 'disabled',
        ]);
        ?>
    <script>
    (function () {
        var select = document.getElementById('sltr-booking-availability-status');
        var button = document.getElementById('sltr-booking-availability-submit');
        if (!select || !button) { return; }

        var initialValue = select.value;
        var updateButtonState = function () {
            button.disabled = select.value === initialValue;
        };

        select.addEventListener('change', updateButtonState);
        updateButtonState();
    }());
    </script>
    </form>
</div>
