<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap sltr-admin-wrap sltr-payments-page sltr-pro-feature-page sltr-full-width-admin">
    <h1><?php esc_html_e('Payments', 'slotera-booking'); ?></h1>
    <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
        <a class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments')); ?>"><?php esc_html_e('Payment Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $tab === 'transactions' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments&sltr_payment_tab=transactions')); ?>"><?php esc_html_e('Transactions', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $tab === 'invoices' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments&sltr_payment_tab=invoices')); ?>"><?php esc_html_e('Invoices', 'slotera-booking'); ?></a>
    </nav>
    <h2><?php esc_html_e('Payment Transactions', 'slotera-booking'); ?></h2>

    <?php if (!empty($_GET['sltr_refund_done'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Refund recorded successfully.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($_GET['sltr_refund_error'])) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html(sanitize_text_field(wp_unslash((string) $_GET['sltr_refund_error']))); ?></p></div>
    <?php endif; ?>

    <?php if (!empty($transaction_totals)) : ?>
        <div class="sltr-payment-summary" style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
            <?php foreach ($transaction_totals as $total_row) : ?>
                <div style="background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:10px 12px;min-width:150px;">
                    <strong><?php echo esc_html(ucfirst((string) ($total_row['status'] ?? ''))); ?></strong><br>
                    <?php echo esc_html(number_format_i18n((float) ($total_row['total'] ?? 0), 2) . ' ' . strtoupper((string) ($total_row['currency'] ?? ''))); ?><br>
                    <span class="description"><?php echo esc_html(sprintf(_n('%d transaction', '%d transactions', (int) ($total_row['count'] ?? 0), 'slotera-booking'), (int) ($total_row['count'] ?? 0))); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="get" style="margin:12px 0;">
        <input type="hidden" name="page" value="slotera-payments">
        <input type="hidden" name="sltr_payment_tab" value="transactions">
        <select name="sltr_payment_status">
            <?php foreach (['all' => 'All statuses', 'pending' => 'Pending', 'paid' => 'Paid', 'partial' => 'Deposit paid', 'failed' => 'Failed', 'refunded' => 'Refunded', 'partially_refunded' => 'Partially refunded', 'unpaid' => 'Unpaid'] as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($filters['status'] ?? 'all'), $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sltr_payment_gateway">
            <?php foreach (['all' => 'All gateways', 'stripe' => 'Stripe', 'paypal' => 'PayPal', 'manual' => 'Manual', 'bank_transfer' => 'Bank transfer', 'admin' => 'Admin'] as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($filters['gateway'] ?? 'all'), $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="s" value="<?php echo esc_attr((string) ($filters['search'] ?? '')); ?>" placeholder="<?php esc_attr_e('Search email, transaction ID or description', 'slotera-booking'); ?>">
        <button class="button"><?php esc_html_e('Filter', 'slotera-booking'); ?></button>
        <?php if (!empty($filters['search']) || ($filters['status'] ?? 'all') !== 'all' || ($filters['gateway'] ?? 'all') !== 'all') : ?>
            <a class="button button-link" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments&sltr_payment_tab=transactions')); ?>"><?php esc_html_e('Clear', 'slotera-booking'); ?></a>
        <?php endif; ?>
    </form>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Date', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Booking', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Customer', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Gateway', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Status', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Amount', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Transaction ID', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Description', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Actions', 'slotera-booking'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($transactions)) : ?>
            <tr><td colspan="9"><?php esc_html_e('No payment transactions yet.', 'slotera-booking'); ?></td></tr>
        <?php else : ?>
            <?php foreach ($transactions as $transaction) : ?>
                <tr>
                    <td><?php echo esc_html((string) ($transaction['created_at'] ?? '')); ?></td>
                    <td>
                        <?php $booking_id = absint($transaction['booking_id'] ?? 0); ?>
                        <?php if ($booking_id > 0) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-bookings&booking_id=' . $booking_id)); ?>">#<?php echo esc_html((string) $booking_id); ?></a>
                        <?php else : ?>—<?php endif; ?>
                    </td>
                    <td><?php echo esc_html((string) ($transaction['customer_email'] ?? '')); ?></td>
                    <td><?php echo esc_html((string) ($transaction['gateway'] ?? '')); ?><?php if (!empty($transaction['mode'])) : ?> <span class="description">(<?php echo esc_html((string) $transaction['mode']); ?>)</span><?php endif; ?></td>
                    <td><strong><?php echo esc_html((string) ($transaction['status'] ?? '')); ?></strong></td>
                    <td><?php echo esc_html(number_format_i18n((float) ($transaction['amount'] ?? 0), 2) . ' ' . strtoupper((string) ($transaction['currency'] ?? ''))); ?></td>
                    <td><code><?php echo esc_html((string) ($transaction['external_id'] ?? '')); ?></code></td>
                    <td>
                        <?php echo esc_html((string) ($transaction['description'] ?? '')); ?>
                        <?php if (!empty($transaction['error_message'])) : ?><br><span style="color:#b32d2e;"><?php echo esc_html((string) $transaction['error_message']); ?></span><?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $can_refund = in_array((string) ($transaction['status'] ?? ''), ['paid', 'partial'], true)
                            && in_array((string) ($transaction['transaction_type'] ?? ''), ['payment', 'deposit'], true)
                            && (float) ($transaction['amount'] ?? 0) > 0;
                        ?>
                        <?php if ($can_refund) : ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                <input type="hidden" name="action" value="sltr_refund_payment_transaction">
                                <input type="hidden" name="transaction_id" value="<?php echo esc_attr((string) ($transaction['id'] ?? 0)); ?>">
                                <?php wp_nonce_field('sltr_refund_payment_transaction_' . (int) ($transaction['id'] ?? 0)); ?>
                                <input type="number" step="0.01" min="0.01" max="<?php echo esc_attr((string) (float) ($transaction['amount'] ?? 0)); ?>" name="refund_amount" value="<?php echo esc_attr((string) (float) ($transaction['amount'] ?? 0)); ?>" style="width:96px;">
                                <select name="refund_reason">
                                    <option value="requested_by_customer"><?php esc_html_e('Customer request', 'slotera-booking'); ?></option>
                                    <option value="duplicate"><?php esc_html_e('Duplicate', 'slotera-booking'); ?></option>
                                    <option value="fraudulent"><?php esc_html_e('Fraudulent', 'slotera-booking'); ?></option>
                                </select>
                                <button class="button button-small" data-sltr-confirm="<?php echo esc_attr(__('Create refund for this payment transaction?', 'slotera-booking')); ?>" data-sltr-confirm-title="<?php esc_attr_e('Confirm action', 'slotera-booking'); ?>" data-sltr-confirm-button="<?php esc_attr_e('Confirm', 'slotera-booking'); ?>"><?php esc_html_e('Refund', 'slotera-booking'); ?></button>
                            </form>
                        <?php else : ?>
                            <span class="description">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <?php
    $total_pages = max(1, (int) ceil(($transactions_total ?? 0) / 25));
    if ($total_pages > 1) :
        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo wp_kses_post(paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'current' => max(1, (int) ($paged ?? 1)),
            'total' => $total_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]) ?: '');
        echo '</div></div>';
    endif;
    ?>
</div>
