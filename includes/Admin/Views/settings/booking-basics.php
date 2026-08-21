<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
$sltr_booking_currencies = \Slotera\Application\Services\CurrencyService::currencies();
$sltr_booking_currency = \Slotera\Application\Services\CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
?>
<section id="sltr-booking-basics" class="sltr-panel sltr-settings-section" style="margin: 16px 0;">
    <h2><?php esc_html_e('Booking basics', 'slotera-booking'); ?></h2>
    <p><?php esc_html_e('Configure the core currency, checkout and notification options used by new bookings.', 'slotera-booking'); ?></p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_save_booking_basics">
        <input type="hidden" name="return_to" value="sltr-booking-basics">
        <?php wp_nonce_field('sltr_save_booking_basics'); ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="sltr-payment-currency"><?php esc_html_e('Currency', 'slotera-booking'); ?></label></th>
                    <td>
                        <select class="regular-text" id="sltr-payment-currency" name="payment_currency">
                            <?php foreach ($sltr_booking_currencies as $code => $currency_data) : ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($sltr_booking_currency, $code); ?>><?php echo esc_html($code . ' — ' . $currency_data['name'] . ' (' . $currency_data['symbol'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sltr-checkout-mode"><?php esc_html_e('Checkout mode', 'slotera-booking'); ?></label></th>
                    <td>
                        <select id="sltr-checkout-mode" name="payment_checkout_mode">
                            <?php foreach (['booking_only' => __('Booking only', 'slotera-booking'), 'payment_required' => __('Payment required', 'slotera-booking'), 'mixed' => __('Mixed', 'slotera-booking')] as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($settings['payment_checkout_mode'] ?? 'mixed'), $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sltr-admin-notification-email"><?php esc_html_e('Admin notification email', 'slotera-booking'); ?></label></th>
                    <td><input class="regular-text" id="sltr-admin-notification-email" type="email" name="admin_notification_email" value="<?php echo esc_attr((string) ($settings['admin_notification_email'] ?? get_option('admin_email'))); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Booking options', 'slotera-booking'); ?></th>
                    <td>
                        <label><input type="checkbox" name="payment_pay_on_arrival_enabled" value="1" <?php checked(!empty($settings['payment_pay_on_arrival_enabled'])); ?>> <?php esc_html_e('Allow pay on arrival', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="email_notifications_enabled" value="1" <?php checked(!empty($settings['email_notifications_enabled'])); ?>> <?php esc_html_e('Enable email notifications', 'slotera-booking'); ?></label>
                    </td>
                </tr>
            </tbody>
        </table>

        <p><button class="button button-primary" type="submit"><?php esc_html_e('Save booking basics', 'slotera-booking'); ?></button></p>
    </form>
</section>
