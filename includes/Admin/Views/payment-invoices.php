<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap sltr-admin-wrap sltr-payments-page sltr-pro-feature-page sltr-full-width-admin">
    <h1><?php esc_html_e('Payments', 'slotera-booking'); ?></h1>
    <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
        <a class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments')); ?>"><?php esc_html_e('Payment Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $tab === 'transactions' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments&sltr_payment_tab=transactions')); ?>"><?php esc_html_e('Transactions', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $tab === 'invoices' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments&sltr_payment_tab=invoices')); ?>"><?php esc_html_e('Invoices', 'slotera-booking'); ?></a>
    </nav>
    <h2><?php esc_html_e('Payment Invoices', 'slotera-booking'); ?></h2>

    <?php if (!empty($_GET['sltr_invoice_synced'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Invoice synced successfully.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>

    <?php if (!empty($invoice_totals)) : ?>
        <div class="sltr-payment-summary" style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0;">
            <?php foreach ($invoice_totals as $total_row) : ?>
                <div style="background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:10px 12px;min-width:170px;">
                    <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', (string) ($total_row['status'] ?? '')))); ?></strong><br>
                    <?php echo esc_html(number_format_i18n((float) ($total_row['total'] ?? 0), 2) . ' ' . strtoupper((string) ($total_row['currency'] ?? ''))); ?><br>
                    <span class="description"><?php echo esc_html(sprintf(_n('%d invoice', '%d invoices', (int) ($total_row['count'] ?? 0), 'slotera-booking'), (int) ($total_row['count'] ?? 0))); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="get" style="margin:12px 0;">
        <input type="hidden" name="page" value="slotera-payments">
        <input type="hidden" name="sltr_payment_tab" value="invoices">
        <select name="sltr_invoice_status">
            <?php foreach (['all' => 'All statuses', 'unpaid' => 'Unpaid', 'partially_paid' => 'Partially paid', 'paid' => 'Paid', 'refunded' => 'Refunded', 'cancelled' => 'Cancelled'] as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($filters['status'] ?? 'all'), $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="s" value="<?php echo esc_attr((string) ($filters['search'] ?? '')); ?>" placeholder="<?php esc_attr_e('Search invoice, email or customer', 'slotera-booking'); ?>">
        <button class="button"><?php esc_html_e('Filter', 'slotera-booking'); ?></button>
        <?php if (!empty($filters['search']) || ($filters['status'] ?? 'all') !== 'all') : ?>
            <a class="button button-link" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments&sltr_payment_tab=invoices')); ?>"><?php esc_html_e('Clear', 'slotera-booking'); ?></a>
        <?php endif; ?>
    </form>

    <table class="widefat striped">
        <thead><tr>
            <th><?php esc_html_e('Invoice', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Date', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Booking', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Customer', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Status', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Total', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Paid', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Remaining', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Actions', 'slotera-booking'); ?></th>
        </tr></thead>
        <tbody>
        <?php if (empty($invoices)) : ?>
            <tr><td colspan="9"><?php esc_html_e('No invoices yet. Invoices are created when payments are completed or synced from transactions.', 'slotera-booking'); ?></td></tr>
        <?php else : foreach ($invoices as $invoice) : $invoice_id = absint($invoice['id'] ?? 0); $booking_id = absint($invoice['booking_id'] ?? 0); ?>
            <tr>
                <td><strong><?php echo esc_html((string) ($invoice['invoice_number'] ?? '')); ?></strong></td>
                <td><?php echo esc_html((string) ($invoice['issue_date'] ?? '')); ?></td>
                <td><?php if ($booking_id > 0) : ?><a href="<?php echo esc_url(admin_url('admin.php?page=slotera-bookings&booking_id=' . $booking_id)); ?>">#<?php echo esc_html((string) $booking_id); ?></a><?php else : ?>—<?php endif; ?></td>
                <td><?php echo esc_html((string) ($invoice['customer_name'] ?? '')); ?><br><span class="description"><?php echo esc_html((string) ($invoice['customer_email'] ?? '')); ?></span></td>
                <td><strong><?php echo esc_html(str_replace('_', ' ', (string) ($invoice['status'] ?? ''))); ?></strong></td>
                <td><?php echo esc_html(number_format_i18n((float) ($invoice['total'] ?? 0), 2) . ' ' . strtoupper((string) ($invoice['currency'] ?? ''))); ?></td>
                <td><?php echo esc_html(number_format_i18n((float) ($invoice['paid'] ?? 0), 2)); ?></td>
                <td><?php echo esc_html(number_format_i18n((float) ($invoice['remaining'] ?? 0), 2)); ?></td>
                <td>
                    <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=sltr_download_invoice_pdf&invoice_id=' . $invoice_id), 'sltr_download_invoice_pdf_' . $invoice_id)); ?>"><?php esc_html_e('Download PDF', 'slotera-booking'); ?></a>
                    <?php if ($booking_id > 0) : ?>
                        <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=sltr_sync_booking_invoice&booking_id=' . $booking_id), 'sltr_sync_booking_invoice_' . $booking_id)); ?>"><?php esc_html_e('Sync', 'slotera-booking'); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <?php $total_pages = max(1, (int) ceil(($invoices_total ?? 0) / 25)); if ($total_pages > 1) : ?>
        <div class="tablenav"><div class="tablenav-pages">
            <?php echo wp_kses_post(paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => max(1, (int) ($paged ?? 1)), 'total' => $total_pages, 'prev_text' => '&laquo;', 'next_text' => '&raquo;']) ?: ''); ?>
        </div></div>
    <?php endif; ?>
</div>
