<?php if (!defined('ABSPATH')) { exit; }
$notice = isset($_GET['sltr_account_notice']) ? sanitize_key((string) wp_unslash($_GET['sltr_account_notice'])) : '';
$messages = [
    'login_success' => sltr_account_t('you_are_signed_in'),
    'login_failed' => sltr_t('This login link is invalid or expired. Please request a new one.'),
    'logged_out' => sltr_t('You have been logged out.'),
    'cancelled' => sltr_t('Booking cancelled. Confirmation email has been queued.'),
    'cancel_failed' => sltr_t('Booking could not be cancelled.'),
    'rescheduled' => sltr_t('Booking rescheduled. Confirmation email has been queued.'),
    'reschedule_failed' => sltr_t('Booking could not be rescheduled. Please check availability or contact support.'),
    'action_failed' => sltr_t('Action confirmation failed. Please refresh the page and try again.'),
];
$account_base_url = remove_query_arg(['sltr_booking', 'sltr_account_notice'], $account_url ?? get_permalink());
$render_booking_card = static function (array $booking, array $packages_by_id, string $account_base_url): void {
    $package = $packages_by_id[(int) ($booking['package_id'] ?? 0)] ?? null;
    $status = sanitize_key((string) ($booking['status'] ?? ''));
    $payment_status = sanitize_key((string) ($booking['payment_status'] ?? ''));
    $can_manage = in_array($status, (function_exists('sltr_active_booking_statuses') ? sltr_active_booking_statuses() : ['confirmed']), true);
    $cancel_token = sanitize_text_field((string) ($booking['cancellation_token'] ?? ''));
    $reschedule_token = sanitize_text_field((string) ($booking['reschedule_token'] ?? ''));
    $display = sltr_booking_display_data($booking, is_array($package) ? $package : []);
    $booking_mode = sanitize_key((string) ($booking['booking_mode'] ?? ($package['booking_mode'] ?? 'fixed')));
    if ($booking_mode === 'flexible') { $booking_mode = 'flex'; }
    $is_date_range_inventory = $booking_mode === 'date_range_inventory';
    $reschedule_available = $can_manage && $reschedule_token !== '' && empty($display['no_datetime']) && empty($display['scheduled_event']);
    $lifecycle = new \Slotera\Application\Services\BookingLifecycleService();
    $cancel_url = $can_manage && $cancel_token !== '' ? $lifecycle->cancellation_url($booking) : '';
    $reschedule_url = $reschedule_available && !$is_date_range_inventory
        ? $lifecycle->reschedule_url($booking)
        : '';
    if ($cancel_url !== '') { $cancel_url = add_query_arg('sltr_return', 'account', $cancel_url); }
    if ($reschedule_url !== '') { $reschedule_url = add_query_arg('sltr_return', 'account', $reschedule_url); }
    $detail_url = add_query_arg('sltr_booking', (string) ($booking['id'] ?? 0), $account_base_url);
    ?>
    <article class="sltr-account-booking">
        <header>
            <h3><?php echo esc_html((string) ($package['title'] ?? ('#' . (string) ($booking['id'] ?? '')))); ?></h3>
            <span class="sltr-account-status sltr-status-<?php echo esc_attr($status); ?>"><?php echo esc_html(sltr_booking_status_label($status, 'frontend')); ?></span>
        </header>
        <dl>
            <?php if (empty($display['no_datetime'])) : ?>
                <div><dt><?php echo esc_html(sltr_t('Date')); ?></dt><dd><?php echo esc_html((string) $display['date']); ?></dd></div>
                <?php if ((string) ($display['time'] ?? '') !== '') : ?><div><dt><?php echo esc_html(sltr_t('Time')); ?></dt><dd><?php echo esc_html((string) $display['time']); ?></dd></div><?php endif; ?>
            <?php else : ?>
                <div><dt><?php echo esc_html(sltr_t('Next step')); ?></dt><dd><?php echo esc_html((string) $display['notice']); ?></dd></div>
            <?php endif; ?>
            <div><dt><?php echo esc_html(sltr_t('Payment')); ?></dt><dd><?php echo esc_html(sltr_payment_status_label($payment_status, 'frontend')); ?></dd></div>
            <?php if ($payment_status === 'partial') : ?><p class="sltr-account-muted sltr-partial-payment-note"><?php echo esc_html(sltr__('frontend.remaining_balance_paid_on_site')); ?></p><?php endif; ?>
            <div><dt><?php echo esc_html(sltr_t('Total')); ?></dt><dd><?php echo esc_html(number_format_i18n((float) ($booking['gross_amount'] ?? $booking['total_amount'] ?? 0), 2)); ?></dd></div>
        </dl>
        <p><a class="sltr-button sltr-button-secondary" href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html(sltr_account_t('view_details')); ?></a></p>
        <?php if (!empty($invoice_pdf_enabled)) : ?>
            <p><a class="sltr-button sltr-button-secondary" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'sltr_account_invoice_pdf', 'booking_id' => (int) ($booking['id'] ?? 0)], admin_url('admin-post.php')), 'sltr_account_invoice_pdf_' . (int) ($booking['id'] ?? 0))); ?>"><?php echo esc_html(sltr_t('PDF invoice')); ?></a></p>
        <?php endif; ?>
        <?php if ($can_manage) : ?>
            <div class="sltr-account-actions">
                <?php if ($cancel_url !== '') : ?>
                    <p class="sltr-account-cancel-link">
                        <a class="sltr-button sltr-button-danger" href="<?php echo esc_url($cancel_url); ?>"><?php echo esc_html(sltr_t('Cancel')); ?></a>
                    </p>
                <?php endif; ?>
                <?php if ($reschedule_available) : ?>
                    <?php if ($is_date_range_inventory) : ?>
                        <details class="sltr-account-reschedule-panel">
                            <summary class="sltr-button sltr-button-secondary"><?php echo esc_html(sltr_t('Reschedule')); ?></summary>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-account-reschedule">
                                <input type="hidden" name="action" value="sltr_account_reschedule_booking">
                                <input type="hidden" name="token" value="<?php echo esc_attr($reschedule_token); ?>">
                                <?php wp_nonce_field('sltr_account_reschedule_' . $reschedule_token); ?>
                                <label><span><?php echo esc_html(sltr_t('New date')); ?></span><input type="date" name="date" required></label>
                                <label><span><?php echo esc_html(sltr_t('Start')); ?></span><input type="time" name="start" required></label>
                                <label><span><?php echo esc_html(sltr_t('End')); ?></span><input type="time" name="end" required></label>
                                <button type="submit" class="sltr-button"><?php echo esc_html(sltr_t('Confirm reschedule')); ?></button>
                                <p class="sltr-account-muted"><?php echo esc_html(sltr_t('For safety, each booking can be rescheduled from the client account only once.')); ?></p>
                            </form>
                        </details>
                    <?php elseif ($reschedule_url !== '') : ?>
                        <p class="sltr-account-reschedule-link">
                            <a class="sltr-button sltr-button-secondary" href="<?php echo esc_url($reschedule_url); ?>"><?php echo esc_html(sltr_t('Reschedule')); ?></a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php
};
?>
<div class="sltr-account sltr-theme-<?php echo esc_attr($theme ?? 'light'); ?>" style="<?php echo esc_attr($style_vars ?? ''); ?>">
    <div class="sltr-account-panel">
    <h2><?php echo esc_html(sltr_t('Client account')); ?></h2>
    <?php if ($notice && isset($messages[$notice])) : ?>
        <div class="sltr-message <?php echo in_array($notice, ['login_success','cancelled','rescheduled'], true) ? 'is-success' : 'is-error'; ?>"><?php echo esc_html($messages[$notice]); ?></div>
    <?php endif; ?>

    <?php if (empty($is_logged_in)) : ?>
        <p><?php echo esc_html(sltr_t('Sign in with a secure email link to view your booking history and manage upcoming bookings.')); ?></p>
        <p><a class="sltr-button" href="<?php echo esc_url($login_url ?? home_url('/')); ?>"><?php echo esc_html(sltr_t('Sign in')); ?></a></p>
    <?php else : ?>
        <div class="sltr-account-header">
            <p><?php printf(esc_html(sltr_t('Signed in as %s')), esc_html($customer_email ?? '')); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="sltr_account_logout">
                <?php wp_nonce_field('sltr_account_logout'); ?>
                <button type="submit" class="sltr-button sltr-button-secondary"><?php echo esc_html(sltr_t('Log out')); ?></button>
            </form>
        </div>

        <?php if (!empty($selected_booking)) :
            $selected_package = $packages_by_id[(int) ($selected_booking['package_id'] ?? 0)] ?? null;
            $selected_display = sltr_booking_display_data($selected_booking, is_array($selected_package) ? $selected_package : []);
            ?>
            <section class="sltr-account-detail">
                <p><a href="<?php echo esc_url($account_base_url); ?>">← <?php echo esc_html(sltr_t('Back to all bookings')); ?></a></p>
                <h3><?php echo esc_html(sltr_t('Booking details')); ?> #<?php echo esc_html((string) ($selected_booking['id'] ?? '')); ?></h3>
                <dl>
                    <div><dt><?php echo esc_html(sltr_t('Service')); ?></dt><dd><?php echo esc_html((string) ($selected_package['title'] ?? ('#' . (string) ($selected_booking['package_id'] ?? '')))); ?></dd></div>
                    <div><dt><?php echo esc_html(sltr_t('Customer')); ?></dt><dd><?php echo esc_html((string) ($selected_booking['customer_name'] ?? '')); ?></dd></div>
                    <div><dt><?php echo esc_html(sltr_t('Email')); ?></dt><dd><?php echo esc_html((string) ($selected_booking['customer_email'] ?? '')); ?></dd></div>
                    <div><dt><?php echo esc_html(sltr_t('Phone')); ?></dt><dd><?php echo esc_html((string) ($selected_booking['customer_phone'] ?? '')); ?></dd></div>
                    <?php if (empty($selected_display['no_datetime'])) : ?>
                        <div><dt><?php echo esc_html(sltr_t('Date')); ?></dt><dd><?php echo esc_html((string) $selected_display['date']); ?></dd></div>
                        <?php if ((string) ($selected_display['time'] ?? '') !== '') : ?><div><dt><?php echo esc_html(sltr_t('Time')); ?></dt><dd><?php echo esc_html((string) $selected_display['time']); ?></dd></div><?php endif; ?>
                    <?php else : ?>
                        <div><dt><?php echo esc_html(sltr_t('Next step')); ?></dt><dd><?php echo esc_html((string) $selected_display['notice']); ?></dd></div>
                    <?php endif; ?>
                    <div><dt><?php echo esc_html(sltr_t('Status')); ?></dt><dd><?php echo esc_html(sltr_booking_status_label((string) ($selected_booking['status'] ?? ''), 'frontend')); ?></dd></div>
                    <div><dt><?php echo esc_html(sltr_t('Payment')); ?></dt><dd><?php echo esc_html(sltr_payment_status_label((string) ($selected_booking['payment_status'] ?? ''), 'frontend')); ?></dd></div>
                    <?php if (sanitize_key((string) ($selected_booking['payment_status'] ?? '')) === 'partial') : ?><div class="sltr-partial-payment-note"><dt></dt><dd><?php echo esc_html(sltr__('frontend.remaining_balance_paid_on_site', (string) ($selected_booking['booking_locale'] ?? ''))); ?></dd></div><?php endif; ?>
                    <?php $selected_extras = json_decode((string) ($selected_booking['selected_extras_json'] ?? '[]'), true); if (is_array($selected_extras)) : foreach ($selected_extras as $extra) : if (!is_array($extra)) continue; ?>
                    <div><dt><?php echo esc_html((string) ($extra['name'] ?? sltr_t('Extra service'))); ?></dt><dd>+<?php echo esc_html(number_format_i18n((float) ($extra['line_amount'] ?? $extra['price'] ?? 0), 2)); ?></dd></div>
                    <?php endforeach; endif; ?>
                    <?php if (trim((string) ($selected_booking['payment_gateway'] ?? '')) !== '') : ?>
                    <div><dt><?php echo esc_html(sltr_t('Gateway')); ?></dt><dd><?php echo esc_html((string) $selected_booking['payment_gateway']); ?></dd></div>
                    <?php endif; ?>
                    <div><dt><?php echo esc_html(sltr_t('Total')); ?></dt><dd><?php echo esc_html(number_format_i18n((float) ($selected_booking['gross_amount'] ?? $selected_booking['total_amount'] ?? 0), 2)); ?></dd></div>
                    <?php if ((float) ($selected_booking['pricing_adjustment_amount'] ?? 0) < 0) : ?><div><dt><?php echo esc_html((string) ($selected_booking['pricing_adjustment_label'] ?? '') !== '' ? (new \Slotera\Application\Services\PricingAdjustmentService())->localize_offer_label((string) $selected_booking['pricing_adjustment_label']) : sltr_t('Special offer')); ?></dt><dd>-<?php echo esc_html(number_format_i18n(abs((float) $selected_booking['pricing_adjustment_amount']), 2)); ?></dd></div><?php endif; ?>
                    <?php if ((float) ($selected_booking['package_discount_amount'] ?? 0) > 0) : ?><div><dt><?php echo esc_html(sltr_t('Package discount')); ?></dt><dd>-<?php echo esc_html(number_format_i18n((float) $selected_booking['package_discount_amount'], 2)); ?></dd></div><?php endif; ?>
                    <?php if ((float) ($selected_booking['coupon_discount_amount'] ?? 0) > 0) : ?><div><dt><?php echo esc_html(sltr_t('Coupon discount')); ?></dt><dd>-<?php echo esc_html(number_format_i18n((float) $selected_booking['coupon_discount_amount'], 2)); ?></dd></div><?php endif; ?>
                    <?php if (!empty($invoice_pdf_enabled)) : ?><div><dt><?php echo esc_html(sltr_t('Invoice')); ?></dt><dd><a class="sltr-button sltr-button-secondary" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'sltr_account_invoice_pdf', 'booking_id' => (int) ($selected_booking['id'] ?? 0)], admin_url('admin-post.php')), 'sltr_account_invoice_pdf_' . (int) ($selected_booking['id'] ?? 0))); ?>"><?php echo esc_html(sltr_t('Download PDF invoice')); ?></a></dd></div><?php endif; ?>
                    <?php if (!empty($selected_booking['notes'])) : ?><div><dt><?php echo esc_html(sltr_t('Notes')); ?></dt><dd><?php echo wp_kses_post((string) $selected_booking['notes']); ?></dd></div><?php endif; ?>
                </dl>
                <?php
                $sltr_customer_history = array_values(array_filter((array) $selected_history, static function ($item): bool {
                    return is_array($item) && sanitize_key((string) ($item['event'] ?? '')) !== 'payment_completed_notified';
                }));
                if (!empty($sltr_customer_history)) : ?>
                    <h4><?php echo esc_html(sltr_t('Activity')); ?></h4>
                    <ul class="sltr-account-history">
                        <?php foreach ($sltr_customer_history as $item) : ?>
                            <li><strong><?php echo esc_html(sltr_activity_event_label((string) ($item['event'] ?? ''), 'frontend', (string) ($selected_booking['booking_locale'] ?? ''))); ?></strong> — <?php echo esc_html((string) ($item['created_at'] ?? '')); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php elseif (empty($bookings)) : ?>
            <p><?php echo esc_html(sltr_t('No bookings were found for this email.')); ?></p>
        <?php else : ?>
            <section class="sltr-account-section">
                <h3><?php sltr_esc_html_e('frontend.account.upcoming_bookings'); ?></h3>
                <?php if (empty($upcoming_bookings)) : ?>
                    <p><?php sltr_esc_html_e('frontend.account.no_upcoming_bookings'); ?></p>
                <?php else : ?>
                    <div class="sltr-account-bookings">
                        <?php foreach ($upcoming_bookings as $booking) { $render_booking_card($booking, $packages_by_id, $account_base_url); } ?>
                    </div>
                <?php endif; ?>
            </section>
            <section class="sltr-account-section">
                <h3><?php echo esc_html(sltr_account_t('past_bookings')); ?></h3>
                <?php if (empty($past_bookings)) : ?>
                    <p><?php echo esc_html(sltr_account_t('no_past_bookings')); ?></p>
                <?php else : ?>
                    <div class="sltr-account-bookings">
                        <?php foreach ($past_bookings as $booking) { $render_booking_card($booking, $packages_by_id, $account_base_url); } ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</div>
