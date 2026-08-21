<?php if (!defined('ABSPATH')) { exit; }
$sltr_event_repo = new \Slotera\Infrastructure\Repositories\EventRepository();
$sltr_booking_repo = new \Slotera\Infrastructure\Repositories\BookingRepository();
$sltr_format_duration = static function ($minutes): string {
    $minutes = max(0, (int) $minutes);
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h > 0 && $m > 0) { return $h . ' h ' . $m . ' min'; }
    if ($h > 0) { return $h . ' h'; }
    return $m . ' min';
}; ?>
<div class="wrap sltr-admin">
    <header class="sltr-page-header">
        <div class="sltr-page-header__content">
            <h1 class="sltr-page-header__title"><?php esc_html_e('Packages', 'slotera-booking'); ?></h1>
        </div>
        <div class="sltr-page-header__actions">
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=slotera-packages&action=new')); ?>"><?php esc_html_e('Add New', 'slotera-booking'); ?></a>
        </div>
    </header>

    <?php $sltr_message = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_message'); ?>
    <?php if ($sltr_message !== '') : ?>
        <?php if ($sltr_message === 'archived') : ?>
            <div class="notice notice-warning is-dismissible"><p><?php esc_html_e('Package has existing bookings, so it was deactivated instead of permanently deleted. Booking history remains intact.', 'slotera-booking'); ?></p></div>
        <?php elseif ($sltr_message === 'deleted') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Package deleted.', 'slotera-booking'); ?></p></div>
        <?php else : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Package updated.', 'slotera-booking'); ?></p></div>
        <?php endif; ?>
    <?php endif; ?>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Title', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Category', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Duration', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Price', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Discount', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Mode', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Capacity', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Hours', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Status', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Actions', 'slotera-booking'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($packages)) : ?>
                <?php foreach ($packages as $p) : ?>
                    <?php
                    $id = (int) ($p['id'] ?? 0);
                    $page_id = (int) ($p['page_id'] ?? 0);
                    $edit_page_url = $page_id > 0 ? get_edit_post_link($page_id, '') : '';
                    $open_page_url = $page_id > 0 ? get_permalink($page_id) : '';
                    $event_minutes = null; $event_price = null; $event_discount_type = null; $event_discount_value = null;
                    $sltr_event = sanitize_key((string) ($p['booking_mode'] ?? '')) === 'date_range_inventory' ? $sltr_event_repo->get_first_for_package($id) : null;
                    $sltr_has_bookings = $sltr_booking_repo->count_by_package_id($id) > 0;
                    if (is_array($sltr_event)) {
                        $start_ts = strtotime((string) ($sltr_event['event_date'] ?? '') . ' ' . (string) ($sltr_event['start_time'] ?? '00:00:00'));
                        $end_ts = strtotime((string) ($sltr_event['end_date'] ?? '') . ' ' . (string) ($sltr_event['end_time'] ?? '00:00:00'));
                        $event_minutes = ($start_ts && $end_ts && $end_ts > $start_ts) ? (int) round(($end_ts - $start_ts) / 60) : 0;
                        $event_price = max(0, (float) ($sltr_event['price_override'] ?? 0));
                        $event_discount_type = (string) ($sltr_event['discount_type'] ?? 'none');
                        $event_discount_value = (float) ($sltr_event['discount_value'] ?? 0);
                    }
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($p['title'] ?? ''); ?></strong><?php if (!empty($p['is_popular'])) : ?><?php $popular_icons = ['star' => '★', 'fire' => '🔥', 'crown' => '♛', 'heart' => '♥', 'bolt' => '⚡']; $popular_glyph = $popular_icons[(string) ($p['popular_icon'] ?? 'star')] ?? '★'; ?> <span class="sltr-badge-popular sltr-badge-popular-icon" style="position:static;vertical-align:middle;--sltr-featured-icon-color:<?php echo esc_attr(sanitize_hex_color((string) ($p['popular_icon_color'] ?? '#7c3aed')) ?: '#7c3aed'); ?>;--sltr-featured-icon-size:<?php echo esc_attr((string) max(16, min(48, (int) ($p['popular_icon_size'] ?? 24)))); ?>px" aria-label="<?php echo esc_attr(sltr_t('Featured package')); ?>"><span aria-hidden="true"><?php echo esc_html($popular_glyph); ?></span></span><?php endif; ?></td>
                        <td>#<?php echo esc_html((string) ($p['category_id'] ?? 0)); ?></td>
                        <td><?php echo esc_html($sltr_format_duration(isset($event_minutes) ? $event_minutes : ($p['duration_minutes'] ?? 0))); ?></td>
                        <td><?php echo esc_html(number_format_i18n(isset($event_price) ? $event_price : (float) ($p['price'] ?? 0), 2)); ?></td>
                        <td><?php echo esc_html(isset($event_discount_type) ? ($event_discount_type . ' ' . $event_discount_value) : (($p['discount_type'] ?? 'none') . ' ' . ($p['discount_value'] ?? '0'))); ?></td>
                        <td><?php echo esc_html(is_array($sltr_event) ? __('Scheduled events', 'slotera-booking') : ($p['booking_mode'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) (is_array($sltr_event) ? max(1, (int) ($sltr_event['capacity'] ?? 1)) : max(1, (int) ($p['max_bookings_per_slot'] ?? 1)))); ?></td>
                        <td><?php echo esc_html(is_array($sltr_event) ? '—' : ($p['hours_mode'] ?? '')); ?></td>
                        <td><?php echo !empty($p['is_active']) ? esc_html__('Active', 'slotera-booking') : esc_html__('Draft', 'slotera-booking'); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-packages&action=edit&id=' . $id)); ?>"><?php esc_html_e('Edit', 'slotera-booking'); ?></a>
                            &nbsp;|&nbsp;
                            <?php if (!empty($p['is_active'])) : ?>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=sltr_deactivate_package&id=' . $id), 'sltr_deactivate_package_' . $id)); ?>" data-sltr-confirm="<?php esc_attr_e('Deactivate this package and move it to Draft?', 'slotera-booking'); ?>" data-sltr-confirm-title="<?php esc_attr_e('Deactivate package?', 'slotera-booking'); ?>" data-sltr-confirm-button="<?php esc_attr_e('Deactivate', 'slotera-booking'); ?>"><?php esc_html_e('Deactivate', 'slotera-booking'); ?></a>
                            <?php else : ?>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=sltr_restore_package&id=' . $id), 'sltr_restore_package_' . $id)); ?>"><?php esc_html_e('Restore', 'slotera-booking'); ?></a>
                            <?php endif; ?>
                            <?php if ($edit_page_url) : ?>
                                &nbsp;|&nbsp;<a href="<?php echo esc_url($edit_page_url); ?>"><?php esc_html_e('Edit page', 'slotera-booking'); ?></a>
                            <?php endif; ?>
                            <?php if ($open_page_url) : ?>
                                &nbsp;|&nbsp;<a href="<?php echo esc_url($open_page_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open page', 'slotera-booking'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="10"><div class="sltr-empty-state"><strong class="sltr-empty-state__title"><?php esc_html_e('No packages found.', 'slotera-booking'); ?></strong></div></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
