<?php
if (!defined('ABSPATH')) {
    exit;
}

$bookings_url = admin_url('admin.php?page=slotera-bookings');
$customers_url = admin_url('admin.php?page=slotera-bookings&tab=customers');
?>
<div class="wrap sltr-admin-page">
    <h1><?php esc_html_e('Customers', 'slotera-booking'); ?></h1>

    <nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e('Booking sections', 'slotera-booking'); ?>">
        <a class="nav-tab" href="<?php echo esc_url($bookings_url); ?>"><?php esc_html_e('Bookings', 'slotera-booking'); ?></a>
        <a class="nav-tab nav-tab-active" href="<?php echo esc_url($customers_url); ?>" aria-current="page"><?php esc_html_e('Customers', 'slotera-booking'); ?></a>
    </nav>


    <form method="get" class="sltr-filters">
        <input type="hidden" name="page" value="slotera-bookings">
        <input type="hidden" name="tab" value="customers">
        <input
            type="search"
            name="s"
            value="<?php echo esc_attr($search); ?>"
            placeholder="<?php esc_attr_e('Name, email or phone', 'slotera-booking'); ?>"
        >
        <button class="button button-primary"><?php esc_html_e('Apply', 'slotera-booking'); ?></button>
        <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-bookings', 'tab' => 'customers'], admin_url('admin.php'))); ?>"><?php esc_html_e('Reset', 'slotera-booking'); ?></a>
    </form>

    <table class="widefat striped">
        <thead><tr>
            <th><?php esc_html_e('Customer', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Email', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Phone', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Bookings', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Active', 'slotera-booking'); ?></th>
            <th><?php esc_html_e('Last booking', 'slotera-booking'); ?></th>
        </tr></thead>
        <tbody>
        <?php if (!$rows) : ?>
            <tr><td colspan="6"><?php esc_html_e('No customers found yet.', 'slotera-booking'); ?></td></tr>
        <?php else : foreach ($rows as $row) : $email = sanitize_email((string) ($row['customer_email'] ?? '')); ?>
            <tr>
                <td><?php echo esc_html((string) ($row['customer_name'] ?: '—')); ?></td>
                <td><a href="<?php echo esc_url(admin_url('admin.php?page=slotera-bookings&s=' . rawurlencode($email))); ?>"><?php echo esc_html($email); ?></a></td>
                <td><?php echo esc_html((string) ($row['customer_phone'] ?: '—')); ?></td>
                <td><?php echo esc_html((string) (int) ($row['bookings_count'] ?? 0)); ?></td>
                <td><?php echo esc_html((string) (int) ($row['active_bookings'] ?? 0)); ?></td>
                <td><?php echo esc_html((string) ($row['last_booking_at'] ?: '—')); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom"><div class="tablenav-pages">
            <?php echo wp_kses_post(paginate_links([
                'base' => add_query_arg(['page' => 'slotera-bookings', 'tab' => 'customers', 's' => rawurlencode($search), 'paged' => '%#%'], admin_url('admin.php')),
                'format' => '',
                'current' => $paged,
                'total' => $total_pages,
            ]) ?: ''); ?>
        </div></div>
    <?php endif; ?>
</div>
