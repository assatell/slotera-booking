<?php if (!defined('ABSPATH')) exit; ?>
<?php
$settings_repo = new \Slotera\Infrastructure\Repositories\SettingsRepository();
$currency = \Slotera\Application\Services\CurrencyService::normalize((string) $settings_repo->get('payment_currency', 'EUR'));
$currency_position = \Slotera\Application\Services\CurrencyService::normalize_position((string) $settings_repo->get('payment_currency_position', 'right_space'));
$sltr_money = static function ($amount) use ($currency, $currency_position): string {
    return \Slotera\Application\Services\CurrencyService::format((float) $amount, $currency, $currency_position);
};
$base_amount = (float) ($booking['base_amount'] ?? 0);
$total_amount = (float) ($booking['total_amount'] ?? 0);
$package_discount = (float) ($booking['package_discount_amount'] ?? 0);
$coupon_code = trim((string) ($booking['coupon_code'] ?? ''));
$coupon_discount = (float) ($booking['coupon_discount_amount'] ?? 0);
$total_discount = max(0, $package_discount + $coupon_discount);
$tax_amount = (float) ($booking['tax_amount'] ?? 0);
$gross_amount = (float) ($booking['gross_amount'] ?? $total_amount);
$due_now = (float) ($booking['amount_due_now'] ?? $total_amount);
$remaining_amount = (float) ($booking['remaining_amount'] ?? 0);

$sltr_status_label = static function ($status): string {
    $labels = ['confirmed'=>__('Confirmed', 'slotera-booking'),'cancelled'=>__('Cancelled', 'slotera-booking'),'completed'=>__('Completed', 'slotera-booking')];
    if (function_exists('sltr_feature_enabled') && sltr_feature_enabled('payments')) { $labels = ['pending_payment'=>__('Awaiting online payment', 'slotera-booking')] + $labels; }
    $status = (string) $status;
    return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
};
$sltr_payment_label = static function ($status): string {
    $labels = ['pending'=>__('Awaiting payment', 'slotera-booking'),'unpaid'=>__('Unpaid', 'slotera-booking'),'paid'=>__('Paid', 'slotera-booking'),'partial'=>__('Partially paid', 'slotera-booking'),'failed'=>__('Payment failed', 'slotera-booking'),'refunded'=>__('Refunded', 'slotera-booking')];
    $status = (string) $status;
    return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
};
$sltr_action_url = static function (string $action, array $booking): string {
    $id = (int) ($booking['id'] ?? 0);
    return wp_nonce_url(admin_url('admin-post.php?action=sltr_booking_' . $action . '&booking_id=' . $id), 'sltr_booking_' . $action . '_' . $id);
};
?>
<div class="wrap sltr-admin sltr-page-stack">
    <header class="sltr-page-header">
        <div class="sltr-page-header__content"><h1 class="sltr-page-header__title"><?php echo esc_html(sprintf(__('Booking #%d', 'slotera-booking'), (int) ($booking['id'] ?? 0))); ?></h1><p class="sltr-page-header__description"><?php esc_html_e('Booking details, lifecycle, payment and customer activity.', 'slotera-booking'); ?></p></div>
        <div class="sltr-page-header__actions"><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=slotera-bookings')); ?>"><?php esc_html_e('Back to bookings', 'slotera-booking'); ?></a></div>
    </header>
    <?php if(!$booking): ?>
        <div class="sltr-empty-state"><h2 class="sltr-empty-state__title"><?php esc_html_e('Booking not found', 'slotera-booking'); ?></h2><p><?php esc_html_e('The booking may have been removed or the link is no longer valid.', 'slotera-booking'); ?></p></div>
    <?php else: ?>
        <div class="sltr-component-grid sltr-component-grid--2">

            <section class="sltr-panel">
                <h2><?php esc_html_e('Manage booking', 'slotera-booking'); ?></h2>
                <div class="sltr-panel__body"><div class="sltr-status-list"><span><strong><?php esc_html_e('Status', 'slotera-booking'); ?>:</strong> <span class="sltr-status-badge sltr-status-badge--<?php echo esc_attr(sanitize_html_class((string) ($booking['status'] ?? ''))); ?>"><?php echo esc_html($sltr_status_label($booking['status'] ?? '')); ?></span></span><span><strong><?php esc_html_e('Payment', 'slotera-booking'); ?>:</strong> <span class="sltr-status-badge sltr-status-badge--<?php echo esc_attr(sanitize_html_class((string) ($booking['payment_status'] ?? ''))); ?>"><?php echo esc_html($sltr_payment_label($booking['payment_status'] ?? '')); ?></span></span></div>
                <div class="sltr-form-actions">
                    <?php if (!in_array((string)($booking['status'] ?? ''), (function_exists('sltr_booking_statuses') ? sltr_booking_statuses() : ['confirmed','cancelled','completed']), true)) : ?>
                        <a class="button button-primary" href="<?php echo esc_url($sltr_action_url('confirm', $booking)); ?>"><?php esc_html_e('Confirm booking', 'slotera-booking'); ?></a>
                    <?php endif; ?>
                    <?php if (($booking['status'] ?? '') !== 'cancelled' && ($booking['status'] ?? '') !== 'completed') : ?>
                        <a class="button" href="<?php echo esc_url($sltr_action_url('cancel', $booking)); ?>"><?php esc_html_e('Cancel booking', 'slotera-booking'); ?></a>
                    <?php endif; ?>
                    <?php if (($booking['status'] ?? '') === 'confirmed') : ?>
                        <a class="button" href="<?php echo esc_url($sltr_action_url('complete', $booking)); ?>"><?php esc_html_e('Mark completed', 'slotera-booking'); ?></a>
                    <?php endif; ?>
                    <?php if (($booking['payment_status'] ?? '') !== 'paid') : ?>
                        <a class="button" href="<?php echo esc_url($sltr_action_url('mark_paid', $booking)); ?>"><?php esc_html_e('Mark paid', 'slotera-booking'); ?></a>
                    <?php endif; ?>
                    <?php if (($booking['payment_status'] ?? '') === 'paid') : ?>
                        <a class="button" href="<?php echo esc_url($sltr_action_url('mark_unpaid', $booking)); ?>"><?php esc_html_e('Mark unpaid', 'slotera-booking'); ?></a>
                    <?php endif; ?>
                </div></div>
            </section>
            <section class="sltr-panel">
                <h2><?php esc_html_e('Price & discounts', 'slotera-booking'); ?></h2>
                <div class="sltr-responsive-table-wrapper" tabindex="0" role="region" aria-label="<?php esc_attr_e('Price and discounts', 'slotera-booking'); ?>"><table class="widefat striped sltr-responsive-table"><tbody>
                    <tr><th><?php esc_html_e('Package price', 'slotera-booking'); ?></th><td><?php echo esc_html($sltr_money($base_amount)); ?></td></tr>
                    <?php $sltr_selected_extras = json_decode((string) ($booking['selected_extras_json'] ?? '[]'), true); if (is_array($sltr_selected_extras)) : foreach ($sltr_selected_extras as $sltr_extra) : if (!is_array($sltr_extra)) continue; ?>
                    <tr><th><?php echo esc_html((string) ($sltr_extra['name'] ?? __('Extra service', 'slotera-booking'))); ?></th><td>+<?php echo esc_html($sltr_money((float) ($sltr_extra['line_amount'] ?? $sltr_extra['price'] ?? 0))); ?></td></tr>
                    <?php endforeach; endif; ?>
                    <tr><th><?php esc_html_e('Package discount', 'slotera-booking'); ?></th><td><?php echo esc_html($package_discount > 0 ? '-' . $sltr_money($package_discount) : $sltr_money(0)); ?></td></tr>
                    <tr><th><?php esc_html_e('Coupon', 'slotera-booking'); ?></th><td><?php echo $coupon_code !== '' ? '<code>' . esc_html($coupon_code) . '</code>' : esc_html__('No coupon used', 'slotera-booking'); ?></td></tr>
                    <tr><th><?php esc_html_e('Coupon discount', 'slotera-booking'); ?></th><td><?php echo esc_html($coupon_discount > 0 ? '-' . $sltr_money($coupon_discount) : $sltr_money(0)); ?></td></tr>
                    <tr><th><?php esc_html_e('Total discount', 'slotera-booking'); ?></th><td><?php echo esc_html($total_discount > 0 ? '-' . $sltr_money($total_discount) : $sltr_money(0)); ?></td></tr>
                    <tr><th><?php esc_html_e('Tax / VAT', 'slotera-booking'); ?></th><td><?php echo esc_html($tax_amount > 0 ? $sltr_money($tax_amount) : $sltr_money(0)); ?></td></tr>
                    <tr><th><strong><?php esc_html_e('Total', 'slotera-booking'); ?></strong></th><td><strong><?php echo esc_html($sltr_money($gross_amount)); ?></strong></td></tr>
                    <tr><th><?php esc_html_e('Due now', 'slotera-booking'); ?></th><td><?php echo esc_html($sltr_money($due_now)); ?></td></tr>
                    <tr><th><?php esc_html_e('Remaining', 'slotera-booking'); ?></th><td><?php echo esc_html($sltr_money($remaining_amount)); ?></td></tr>
                </tbody></table></div>
            </section>
            <section class="sltr-panel">
                <h2><?php esc_html_e('Details', 'slotera-booking'); ?></h2>
                <div class="sltr-responsive-table-wrapper" tabindex="0" role="region" aria-label="<?php esc_attr_e('Booking details', 'slotera-booking'); ?>"><table class="widefat striped sltr-responsive-table"><tbody><?php foreach($booking as $k=>$v): ?><tr><th><?php echo esc_html($k); ?></th><td><?php echo esc_html((string)$v); ?></td></tr><?php endforeach; ?></tbody></table></div>
            </section>
            <section class="sltr-panel">
                <h2><?php esc_html_e('Customer links', 'slotera-booking'); ?></h2>
                <?php
                $lifecycle = new \Slotera\Application\Services\BookingLifecycleService();
                $cancel_url = $lifecycle->cancellation_url($booking);
                $reschedule_url = $lifecycle->reschedule_url($booking);
                ?>
                <div class="sltr-panel__body sltr-link-list"><p><strong><?php esc_html_e('Cancellation link', 'slotera-booking'); ?></strong><code><?php echo esc_html($cancel_url); ?></code></p><p><strong><?php esc_html_e('Reschedule link', 'slotera-booking'); ?></strong><code><?php echo esc_html($reschedule_url); ?></code></p></div>
            </section>
            <section class="sltr-panel">
                <h2><?php esc_html_e('Booking history', 'slotera-booking'); ?></h2>
                <?php if(!empty($history)): ?><div class="sltr-responsive-table-wrapper" tabindex="0" role="region" aria-label="<?php esc_attr_e('Booking history', 'slotera-booking'); ?>"><table class="widefat striped sltr-responsive-table"><thead><tr><th><?php esc_html_e('Time', 'slotera-booking'); ?></th><th><?php esc_html_e('Event', 'slotera-booking'); ?></th><th><?php esc_html_e('Status', 'slotera-booking'); ?></th><th><?php esc_html_e('Payment', 'slotera-booking'); ?></th><th><?php esc_html_e('Message', 'slotera-booking'); ?></th></tr></thead><tbody><?php foreach($history as $h): ?><tr><td><?php echo esc_html($h['created_at']??''); ?></td><td><?php echo esc_html($h['event']??''); ?></td><td><?php echo esc_html(($h['old_status']??'') . ' → ' . ($h['new_status']??'')); ?></td><td><?php echo esc_html(($h['old_payment_status']??'') . ' → ' . ($h['new_payment_status']??'')); ?></td><td><?php echo esc_html($h['message']??''); ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="sltr-panel__body"><div class="sltr-empty-state sltr-empty-state--compact"><?php esc_html_e('No history yet.', 'slotera-booking'); ?></div></div><?php endif; ?>
            </section>
            <section class="sltr-panel">
                <h2><?php esc_html_e('Activity log', 'slotera-booking'); ?></h2>
                <div class="sltr-panel__body"><?php if(!empty($logs)): ?><ol class="sltr-activity-timeline"><?php foreach($logs as $l): ?><li class="sltr-activity-timeline__item"><span class="sltr-activity-timeline__marker" aria-hidden="true"></span><div><strong><?php echo esc_html($l['message']??''); ?></strong><p><?php echo esc_html(($l['event']??'').' · '.($l['created_at']??'')); ?></p></div></li><?php endforeach; ?></ol><?php else: ?><div class="sltr-empty-state sltr-empty-state--compact"><?php esc_html_e('No activity.', 'slotera-booking'); ?></div><?php endif; ?></div>
            </section>
        </div>
    <?php endif; ?>
</div>
