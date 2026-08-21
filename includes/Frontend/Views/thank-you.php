<?php
if (!defined('ABSPATH')) { exit; }
$has_booking = !empty($booking_access_verified) && !empty($booking) && is_array($booking);
$access_denied = !empty($access_denied);
$package_title = $package['title'] ?? '';
$display = $has_booking ? sltr_booking_display_data($booking, is_array($package ?? null) ? $package : []) : ['no_datetime' => false, 'date' => '', 'time' => '', 'notice' => ''];
$is_request = $has_booking && !empty($display['no_datetime']);
$request_ui = $is_request ? \Slotera\Application\Services\Translations\BookingRequestTranslations::ui(function_exists('determine_locale') ? determine_locale() : get_locale()) : [];
$payment_result = isset($_GET['sltr_payment']) ? sanitize_key((string) wp_unslash($_GET['sltr_payment'])) : '';
$payment_failed = in_array($payment_result, ['failed', 'cancelled', 'canceled'], true);
$payment_processing = $payment_result === 'processing' || ($has_booking && sanitize_key((string) ($booking['payment_status'] ?? '')) === 'processing');
$custom_payment_method = null;
$settings_repo_for_price = new \Slotera\Infrastructure\Repositories\SettingsRepository();
$currency_for_price = \Slotera\Application\Services\CurrencyService::normalize((string) $settings_repo_for_price->get('payment_currency', 'EUR'));
$currency_position_for_price = \Slotera\Application\Services\CurrencyService::normalize_position((string) $settings_repo_for_price->get('payment_currency_position', 'right_space'));
$money = static function ($amount) use ($currency_for_price, $currency_position_for_price): string {
    return \Slotera\Application\Services\CurrencyService::format((float) $amount, $currency_for_price, $currency_position_for_price);
};
if ($has_booking) {
    $gateway_id = sanitize_key((string) ($booking['payment_gateway'] ?? ''));
    if (strpos($gateway_id, 'custom_') === 0) {
        $settings_repo = new \Slotera\Infrastructure\Repositories\SettingsRepository();
        $custom_methods = $settings_repo->get('payment_custom_methods', []);
        if (is_array($custom_methods)) {
            foreach ($custom_methods as $method) {
                if (is_array($method) && sanitize_key((string) ($method['id'] ?? '')) === $gateway_id) {
                    $custom_payment_method = $method;
                    break;
                }
            }
        }
    }
}
?>
<div class="sltr-thank-you sltr-theme-<?php echo esc_attr($theme ?? 'light'); ?>" style="<?php echo esc_attr($style_vars ?? ''); ?>">
    <div class="sltr-thank-you-card">
        <div class="sltr-success-icon" aria-hidden="true"><?php echo esc_html($payment_failed ? '!' : '✓'); ?></div>
        <h2><?php echo $payment_failed ? esc_html(sltr_t('Booking received, payment needs attention')) : ($payment_processing ? esc_html(sltr_t('Booking received / awaiting payment confirmation')) : esc_html($is_request ? sltr_t('Your booking request has been received.') : sltr_t('Thank you for your booking!'))); ?></h2>
        <?php if ($has_booking) : ?>
            <?php if ($payment_failed) : ?>
                <p class="sltr-thank-you-lead"><?php echo esc_html(sltr_t('Your booking was saved, but the payment attempt failed or could not be started. Please contact the site owner or try another payment method if available.')); ?></p>
            <?php elseif ($payment_processing) : ?>
                <p class="sltr-thank-you-lead"><?php echo esc_html(sltr_t('Payment received. PayPal is processing the payment.')); ?></p>
            <?php elseif (!$is_request) : ?>
                <p class="sltr-thank-you-lead"><?php echo esc_html(sltr_t('Your booking has been received successfully. A confirmation email will be sent to you if email notifications are enabled.')); ?></p>
            <?php endif; ?>
            <div class="sltr-booking-summary">
                <div><span><?php echo esc_html($is_request ? ($request_ui['request_number'] ?? 'Request number') : sltr_t('Booking number')); ?></span><strong>#<?php echo esc_html((string) $booking['id']); ?></strong></div>
                <?php if ($package_title !== '') : ?><div><span><?php echo esc_html(sltr_t('Service')); ?></span><strong><?php echo esc_html($package_title); ?></strong></div><?php endif; ?>
                <?php if (empty($display['no_datetime'])) : ?>
                    <div><span><?php echo esc_html(sltr__('frontend.date')); ?></span><strong><?php echo esc_html((string) $display['date']); ?></strong></div>
                    <?php if ((string) ($display['time'] ?? '') !== '') : ?><div><span><?php echo esc_html(sltr__('frontend.time')); ?></span><strong><?php echo esc_html((string) $display['time']); ?></strong></div><?php endif; ?>
                <?php else : ?>
                    <div class="sltr-simple-booking-next-step"><span><?php echo esc_html(sltr_t('Next step')); ?></span><strong><?php echo esc_html((string) $display['notice']); ?></strong></div>
                <?php endif; ?>
                <div><span><?php echo esc_html($is_request ? ($request_ui['request_status'] ?? 'Request status') : sltr__('frontend.booking.label.status')); ?></span><strong><?php echo esc_html(sltr_booking_status_label((string) ($booking['status'] ?? ''), $is_request ? 'emails' : 'frontend')); ?></strong></div>
                <div><span><?php echo esc_html(sltr__('frontend.booking.label.payment')); ?></span><strong><?php echo esc_html(sltr_payment_status_label((string) ($booking['payment_status'] ?? ''), $is_request ? 'emails' : 'frontend')); ?></strong></div>
                <?php if (sanitize_key((string) ($booking['payment_status'] ?? '')) === 'partial') : ?><div class="sltr-partial-payment-note"><span></span><strong><?php echo esc_html(sltr__('frontend.remaining_balance_paid_on_site', (string) ($booking['booking_locale'] ?? ''))); ?></strong></div><?php endif; ?>
                <?php $selected_extras = json_decode((string) ($booking['selected_extras_json'] ?? '[]'), true); if (is_array($selected_extras)) : foreach ($selected_extras as $extra) : if (!is_array($extra)) continue; ?>
                    <div><span><?php echo esc_html((string) ($extra['name'] ?? sltr_t('Extra service'))); ?></span><strong>+<?php echo esc_html($money((float) ($extra['line_amount'] ?? $extra['price'] ?? 0))); ?></strong></div>
                <?php endforeach; endif; ?>
                <?php if ((float) ($booking['pricing_adjustment_amount'] ?? 0) < 0) : ?><div><span><?php echo esc_html((string) ($booking['pricing_adjustment_label'] ?? '') !== '' ? (new \Slotera\Application\Services\PricingAdjustmentService())->localize_offer_label((string) $booking['pricing_adjustment_label']) : sltr_t('Special offer')); ?></span><strong>-<?php echo esc_html($money(abs((float) $booking['pricing_adjustment_amount']))); ?></strong></div><?php endif; ?>
                <?php if ((float) ($booking['package_discount_amount'] ?? 0) > 0) : ?><div><span><?php echo esc_html(sltr_t('Package discount')); ?></span><strong>-<?php echo esc_html($money($booking['package_discount_amount'])); ?></strong></div><?php endif; ?>
                <?php if ((float) ($booking['coupon_discount_amount'] ?? 0) > 0) : ?><div><span><?php echo esc_html(sltr_t('Coupon discount')); ?></span><strong>-<?php echo esc_html($money($booking['coupon_discount_amount'])); ?></strong></div><?php endif; ?>
                <?php $thank_you_total = (float) ($booking['gross_amount'] ?? $booking['total_amount'] ?? 0); ?><div><span><?php echo esc_html(sltr_t('Total')); ?></span><strong><?php echo esc_html($money($thank_you_total)); ?></strong></div>
                <?php if (is_array($custom_payment_method)) : ?>
                    <div class="sltr-custom-payment-instructions">
                        <span><?php echo esc_html(sltr_t('Payment method')); ?></span>
                        <strong><?php echo esc_html((string) ($custom_payment_method['title'] ?? sltr_t('Custom payment'))); ?></strong>
                        <?php if (!empty($custom_payment_method['instructions'])) : ?>
                            <p><?php echo nl2br(esc_html((string) $custom_payment_method['instructions'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($custom_payment_method['url'])) : ?>
                            <p><a class="button" href="<?php echo esc_url((string) $custom_payment_method['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(sltr_t('Open payment link')); ?></a></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php $lifecycle = new \Slotera\Application\Services\BookingLifecycleService(); $cancel_url = $lifecycle->cancellation_url($booking); $is_scheduled_event = ((string) ($booking['booking_mode'] ?? $package['booking_mode'] ?? '')) === 'date_range_inventory' && !empty($booking['resource_id']); $reschedule_url = (!empty($display['no_datetime']) || $is_scheduled_event) ? '' : $lifecycle->reschedule_url($booking); ?>
                <?php if ($cancel_url !== '' || $reschedule_url !== '') : ?>
                    <div>
                        <span><?php echo esc_html($is_request ? ($request_ui['manage_request'] ?? 'Manage request') : sltr_t('Manage booking')); ?></span>
                        <?php if ($cancel_url !== '') : ?><strong><a href="<?php echo esc_url($cancel_url); ?>"><?php echo esc_html($is_request ? ($request_ui['cancel_request'] ?? 'Cancel request') : sltr_t('Cancel booking')); ?></a></strong><?php endif; ?>
                        <?php if ($reschedule_url !== '') : ?><br><strong><a href="<?php echo esc_url($reschedule_url); ?>"><?php echo esc_html(sltr_t('Reschedule booking')); ?></a></strong><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($access_denied) : ?>
            <p class="sltr-thank-you-lead"><?php echo esc_html(sltr_t('We could not verify access to this booking summary. Please use the confirmation link from your booking flow or email.')); ?></p>
        <?php else : ?>
            <p class="sltr-thank-you-lead"><?php echo esc_html(sltr_t('Your booking request has been received.')); ?></p>
        <?php endif; ?>
    </div>
</div>
