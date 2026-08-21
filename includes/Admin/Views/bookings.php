<?php
if (!defined('ABSPATH')) {
    exit;
}

$base = admin_url('admin.php?page=slotera-bookings');
?>
<div class="wrap sltr-admin">
    <h1><?php esc_html_e('Bookings', 'slotera-booking'); ?></h1>

    <nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e('Booking sections', 'slotera-booking'); ?>">
        <a class="nav-tab nav-tab-active" href="<?php echo esc_url($base); ?>" aria-current="page"><?php esc_html_e('Bookings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg('tab', 'customers', $base)); ?>"><?php esc_html_e('Customers', 'slotera-booking'); ?></a>
    </nav>

    <form method="get" class="sltr-filters">
        <input type="hidden" name="page" value="slotera-bookings">
        <input
            type="search"
            name="s"
            value="<?php echo esc_attr($filters['search'] ?? ''); ?>"
            placeholder="<?php esc_attr_e('Search...', 'slotera-booking'); ?>"
        >
        <button class="button button-primary"><?php esc_html_e('Apply', 'slotera-booking'); ?></button>
        <a class="button" href="<?php echo esc_url($base); ?>"><?php esc_html_e('Reset', 'slotera-booking'); ?></a>
    </form>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Email', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Package', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Date', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Time', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Payment', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Status', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Actions', 'slotera-booking'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($bookings)) : ?>
                <?php foreach ($bookings as $booking) : ?>
                    <?php
                    $booking_id = (int) $booking['id'];
                    $sltr_booking_package = (new \Slotera\Infrastructure\Repositories\PackageRepository())->get_by_id((int) ($booking['package_id'] ?? 0));
                    $sltr_booking_display = sltr_booking_display_data($booking, is_array($sltr_booking_package) ? $sltr_booking_package : []);
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html((string) ($booking['customer_name'] ?? '')); ?></strong></td>
                        <td><?php echo esc_html((string) ($booking['customer_email'] ?? '')); ?></td>
                        <td>#<?php echo esc_html((string) ($booking['package_id'] ?? '')); ?></td>
                        <td><?php echo esc_html(!empty($sltr_booking_display['no_datetime']) ? '—' : (string) ($booking['booking_date'] ?? '')); ?></td>
                        <td><?php echo esc_html(!empty($sltr_booking_display['no_datetime']) ? __('Not scheduled', 'slotera-booking') : trim((string) ($booking['start_time'] ?? '') . ' - ' . (string) ($booking['end_time'] ?? ''), ' -')); ?></td>
                        <td><?php echo esc_html((string) ($booking['payment_status'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($booking['status'] ?? '')); ?></td>
                        <td>
                            <a
                                class="button button-small"
                                href="<?php echo esc_url(admin_url('admin.php?page=slotera-bookings&booking_id=' . $booking_id)); ?>"
                            >
                                <?php esc_html_e('View', 'slotera-booking'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8"><?php esc_html_e('No bookings found.', 'slotera-booking'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
