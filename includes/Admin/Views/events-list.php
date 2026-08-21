<?php
if (!defined('ABSPATH')) { exit; }
$events = isset($events) && is_array($events) ? $events : [];
$packages = isset($packages) && is_array($packages) ? $packages : [];
$package_id = isset($package_id) ? absint($package_id) : absint($_GET['package_id'] ?? 0);
$return_package_id = isset($return_package_id) ? absint($return_package_id) : absint($_GET['return_package_id'] ?? $package_id);
$package_titles = [];
foreach ($packages as $package) { $package_titles[(int) ($package['id'] ?? 0)] = (string) ($package['title'] ?? ''); }
?>
<div class="wrap sltr-admin-wrap sltr-page-stack">
    <header class="sltr-page-header">
        <div class="sltr-page-header__content"><h1 class="sltr-page-header__title"><?php esc_html_e('Events overview', 'slotera-booking'); ?></h1><p class="sltr-page-header__description"><?php esc_html_e('Review schedules and capacity here. Event creation and editing are available only from the related Package.', 'slotera-booking'); ?></p></div>
        <?php if ($return_package_id > 0) : ?><div class="sltr-page-header__actions"><a href="<?php echo esc_url(admin_url('admin.php?page=slotera-packages&action=edit&id=' . $return_package_id)); ?>" class="button button-primary"><?php esc_html_e('Open package details', 'slotera-booking'); ?></a></div><?php endif; ?>
    </header>
    <?php if (isset($_GET['sltr_message'])) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e('Event saved.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <section class="sltr-panel sltr-panel--flush">
        <h2><?php esc_html_e('Scheduled events', 'slotera-booking'); ?></h2>
        <?php if (!$events) : ?>
            <div class="sltr-panel__body"><div class="sltr-empty-state"><h3 class="sltr-empty-state__title"><?php esc_html_e('No events yet', 'slotera-booking'); ?></h3><p><?php esc_html_e('Open a package to create its first event.', 'slotera-booking'); ?></p><?php if ($return_package_id > 0) : ?><div class="sltr-empty-state__actions"><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=slotera-packages&action=edit&id=' . $return_package_id)); ?>"><?php esc_html_e('Open package', 'slotera-booking'); ?></a></div><?php endif; ?></div></div>
        <?php else : ?>
            <div class="sltr-responsive-table-wrapper" tabindex="0" role="region" aria-label="<?php esc_attr_e('Scheduled events', 'slotera-booking'); ?>"><table class="widefat striped sltr-responsive-table"><thead><tr><th><?php esc_html_e('Event', 'slotera-booking'); ?></th><th><?php esc_html_e('Package', 'slotera-booking'); ?></th><th><?php esc_html_e('Date', 'slotera-booking'); ?></th><th><?php esc_html_e('Time', 'slotera-booking'); ?></th><th><?php esc_html_e('Capacity', 'slotera-booking'); ?></th><th><?php esc_html_e('Status', 'slotera-booking'); ?></th><th><?php esc_html_e('Active', 'slotera-booking'); ?></th><th><?php esc_html_e('Action', 'slotera-booking'); ?></th></tr></thead><tbody>
            <?php foreach ($events as $event) : $event_package_id = (int) ($event['package_id'] ?? 0); $status = (string) ($event['status'] ?? 'scheduled'); ?>
                <tr><td><strong><?php echo esc_html((string) ($event['title'] ?? '')); ?></strong></td><td><?php echo esc_html($package_titles[$event_package_id] ?? __('Any package', 'slotera-booking')); ?></td><td><?php echo esc_html((string) ($event['event_date'] ?? '')); ?></td><td><?php echo esc_html(substr((string) ($event['start_time'] ?? ''), 0, 5) . ' – ' . substr((string) ($event['end_time'] ?? ''), 0, 5)); ?></td><td><?php echo esc_html((string) ((int) ($event['booked_count'] ?? 0) . ' / ' . (int) ($event['capacity'] ?? 1))); ?></td><td><span class="sltr-status-badge sltr-status-badge--<?php echo esc_attr(sanitize_html_class($status)); ?>"><?php echo esc_html(ucfirst($status)); ?></span></td><td><span class="sltr-status-badge sltr-status-badge--<?php echo !empty($event['is_active']) ? 'active' : 'inactive'; ?>"><?php echo !empty($event['is_active']) ? esc_html__('Yes', 'slotera-booking') : esc_html__('No', 'slotera-booking'); ?></span></td><td><?php if ($event_package_id > 0) : ?><a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=slotera-packages&action=edit&id=' . $event_package_id)); ?>"><?php esc_html_e('Open package', 'slotera-booking'); ?></a><?php else : ?>—<?php endif; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>
</div>
