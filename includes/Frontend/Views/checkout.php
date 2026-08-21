<?php
if (!defined('ABSPATH')) { exit; }

$settings = new \Slotera\Infrastructure\Repositories\SettingsRepository();
$currency = \Slotera\Application\Services\CurrencyService::normalize((string) $settings->get('payment_currency', 'EUR'));
$currency_position = \Slotera\Application\Services\CurrencyService::normalize_position((string) $settings->get('payment_currency_position', 'right_space'));
$package_title = trim((string) ($package['title'] ?? $package['name'] ?? $booking['package_name'] ?? ''));
$booking_date = trim((string) ($booking['booking_date'] ?? $booking['date'] ?? ''));
$start_time = trim((string) ($booking['start_time'] ?? ''));
$end_time = trim((string) ($booking['end_time'] ?? ''));
$format_time = static function (string $time): string {
    return preg_match('/^\d{2}:\d{2}/', $time) ? substr($time, 0, 5) : $time;
};
$display = !empty($booking) && is_array($booking)
    ? sltr_booking_display_data($booking, is_array($package ?? null) ? $package : [])
    : ['no_datetime' => false, 'start_time_only' => false, 'date' => '', 'time' => '', 'notice' => ''];
$time_label = (string) $display['time'];
$booking_date = (string) $display['date'];
$thank_you_url = $settings->get_page_url('thank_you');
$booking_id_value = isset($booking['id']) ? (int) $booking['id'] : 0;
if ($thank_you_url !== '' && $booking_id_value > 0) {
    $thank_you_url = add_query_arg(['booking_id' => $booking_id_value], $thank_you_url);
}
$money = static function ($amount) use ($currency, $currency_position): string {
    return esc_html(\Slotera\Application\Services\CurrencyService::format((float) $amount, $currency, $currency_position));
};
?>
<div class="sltr-booking sltr-checkout sltr-theme-<?php echo esc_attr($theme ?? 'light'); ?>" style="<?php echo esc_attr($style_vars ?? ''); ?>">
    <div class="sltr-checkout-panel">
    <?php if (!empty($access_denied) || empty($booking)) : ?>
        <div class="sltr-message sltr-error"><?php echo esc_html(sltr_t('Booking access link is invalid or expired.')); ?></div>
    <?php else : ?>
        <h2><?php echo esc_html(sltr_t('Checkout')); ?></h2>
        <p><?php echo esc_html(sltr_t('Please review your booking, discounts, taxes and payment amount before continuing.')); ?></p>

        <div class="sltr-card sltr-checkout-summary">
            <h3><?php echo esc_html(sltr_t('Booking summary')); ?></h3>
            <dl>
                <dt><?php echo esc_html(sltr_t('Service')); ?></dt>
                <dd><?php echo esc_html($package_title); ?></dd>
                <?php if (empty($display['no_datetime'])) : ?>
                    <dt><?php echo esc_html(sltr_t('Date')); ?></dt>
                    <dd><?php echo esc_html($booking_date); ?></dd>
                    <?php if ($time_label !== '') : ?><dt><?php echo esc_html(sltr_t('Time')); ?></dt>
                    <dd><?php echo esc_html($time_label); ?></dd><?php endif; ?>
                <?php else : ?>
                    <dt><?php echo esc_html(sltr_t('Next step')); ?></dt>
                    <dd><?php echo esc_html((string) $display['notice']); ?></dd>
                <?php endif; ?>
                <dt><?php echo esc_html(sltr_t('Customer')); ?></dt>
                <dd><?php echo esc_html((string) ($booking['customer_name'] ?? '')); ?></dd>
            </dl>
        </div>

        <div class="sltr-card sltr-checkout-price">
            <h3><?php echo esc_html(sltr_t('Price summary')); ?></h3>
            <dl>
                <dt><?php echo esc_html(sltr_t('Subtotal')); ?></dt>
                <dd><?php echo esc_html($money($booking['base_amount'] ?? $booking['gross_amount'] ?? $booking['total_amount'] ?? 0)); ?></dd>
                <?php if ((float) ($booking['pricing_adjustment_amount'] ?? 0) < 0) : ?>
                    <dt><?php echo esc_html((string) ($booking['pricing_adjustment_label'] ?? '') !== '' ? (new \Slotera\Application\Services\PricingAdjustmentService())->localize_offer_label((string) $booking['pricing_adjustment_label']) : sltr_t('Special offer')); ?></dt>
                    <dd>-<?php echo esc_html($money(abs((float) $booking['pricing_adjustment_amount']))); ?></dd>
                <?php endif; ?>
                <?php if ((float) ($booking['package_discount_amount'] ?? 0) > 0) : ?>
                    <dt><?php echo esc_html(sltr_t('Package discount')); ?></dt>
                    <dd>-<?php echo esc_html($money($booking['package_discount_amount'])); ?></dd>
                <?php endif; ?>
                <?php $selected_extras = json_decode((string) ($booking['selected_extras_json'] ?? '[]'), true); if (is_array($selected_extras)) : foreach ($selected_extras as $extra) : if (!is_array($extra)) continue; ?>
                <div><dt><?php echo esc_html((string) ($extra['name'] ?? sltr_t('Extra service'))); ?></dt><dd>+<?php echo esc_html($money((float) ($extra['line_amount'] ?? $extra['price'] ?? 0))); ?></dd></div>
                <?php endforeach; endif; ?>
                <?php if (!empty($booking['coupon_code']) || (float) ($booking['coupon_discount_amount'] ?? 0) > 0) : ?>
                    <dt><?php echo esc_html(sltr_t('Coupon discount')); ?><?php if (!empty($booking['coupon_code'])) : ?> <small>(<?php echo esc_html((string) $booking['coupon_code']); ?>)</small><?php endif; ?></dt>
                    <dd>-<?php echo esc_html($money($booking['coupon_discount_amount'] ?? 0)); ?></dd>
                <?php endif; ?>
                <?php if ((float) ($booking['tax_amount'] ?? 0) > 0) : ?>
                    <dt><?php echo esc_html(sltr_t('VAT / tax')); ?></dt>
                    <dd><?php echo esc_html($money($booking['tax_amount'])); ?></dd>
                <?php endif; ?>
                <dt><?php echo esc_html(sltr_t('Total')); ?></dt>
                <dd><strong><?php echo esc_html($money($booking['gross_amount'] ?? $booking['total_amount'] ?? 0)); ?></strong></dd>
                <?php if ((float) ($booking['amount_due_now'] ?? 0) > 0) : ?>
                    <dt><?php echo esc_html(sltr_t('Amount due now')); ?></dt>
                    <dd><strong><?php echo esc_html($money($booking['amount_due_now'])); ?></strong></dd>
                <?php endif; ?>
                <?php if ((float) ($booking['gross_amount'] ?? 0) > (float) ($booking['amount_due_now'] ?? 0)) : ?>
                    <dt><?php echo esc_html(sltr_t('Remaining balance')); ?></dt>
                    <dd><?php echo esc_html($money(max(0, (float) ($booking['gross_amount'] ?? 0) - (float) ($booking['amount_due_now'] ?? 0)))); ?></dd>
                <?php endif; ?>
            </dl>
        </div>

        <?php if (!empty($booking['payment_redirect_url'])) : ?>
            <p><a class="button sltr-button sltr-button-primary" href="<?php echo esc_url((string) $booking['payment_redirect_url']); ?>"><?php echo esc_html(sltr_t('Continue to payment')); ?></a></p>
        <?php elseif ($thank_you_url !== '') : ?>
            <p><a class="button sltr-button sltr-button-primary" href="<?php echo esc_url($thank_you_url); ?>"><?php echo esc_html(sltr_t('Confirm and continue')); ?></a></p>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</div>
