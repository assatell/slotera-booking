<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap sltr-admin">
    <header class="sltr-page-header">
        <div class="sltr-page-header__content"><h1 class="sltr-page-header__title"><?php esc_html_e('Locations', 'slotera-booking'); ?></h1></div>
        <div class="sltr-page-header__actions"><a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=slotera-locations&action=new')); ?>"><?php esc_html_e('Add New', 'slotera-booking'); ?></a></div>
    </header>
    <?php $sltr_request = new \Slotera\Application\Services\RequestValidator(); ?>
    <?php $sltr_message = $sltr_request->get_key('sltr_message'); ?>
    <?php $sltr_error = $sltr_request->get_key('sltr_error'); ?>
    <?php if ($sltr_message !== '') : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Location updated.', 'slotera-booking'); ?></p></div>
    <?php elseif ($sltr_error === 'in_use') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('This location is in use by one or more packages and cannot be deactivated.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>
    <div class="sltr-admin-help-card">
        <strong><?php esc_html_e('Locations core', 'slotera-booking'); ?></strong>
        <p><?php esc_html_e('Create cities or service areas and assign packages to them. Local SEO landing pages will use these locations in the next step.', 'slotera-booking'); ?></p>
    </div>
    <table class="widefat striped">
        <thead><tr><th><?php esc_html_e('Name', 'slotera-booking'); ?></th><th><?php esc_html_e('Slug', 'slotera-booking'); ?></th><th><?php esc_html_e('Order', 'slotera-booking'); ?></th><th><?php esc_html_e('Status', 'slotera-booking'); ?></th><th><?php esc_html_e('Actions', 'slotera-booking'); ?></th></tr></thead>
        <tbody>
        <?php if (!empty($locations)) : foreach ($locations as $location) : $id = (int) ($location['id'] ?? 0); ?>
            <tr>
                <td><strong><?php echo esc_html((string) ($location['name'] ?? '')); ?></strong></td>
                <td><?php echo esc_html((string) ($location['slug'] ?? '')); ?></td>
                <td><?php echo esc_html((string) ($location['sort_order'] ?? 0)); ?></td>
                <td><?php echo !empty($location['is_active']) ? esc_html__('Active', 'slotera-booking') : esc_html__('Draft', 'slotera-booking'); ?></td>
                <td>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-locations&action=edit&id=' . $id)); ?>"><?php esc_html_e('Edit', 'slotera-booking'); ?></a>
                    &nbsp;|&nbsp;
                    <?php if (!empty($location['is_active']) && (int) ($location['linked_package_count'] ?? 0) === 0) : ?>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=sltr_deactivate_location&id=' . $id), 'sltr_deactivate_location_' . $id)); ?>" data-sltr-confirm="<?php esc_attr_e('Deactivate this location and move it to Draft?', 'slotera-booking'); ?>" data-sltr-confirm-title="<?php esc_attr_e('Deactivate location?', 'slotera-booking'); ?>" data-sltr-confirm-button="<?php esc_attr_e('Deactivate', 'slotera-booking'); ?>"><?php esc_html_e('Deactivate', 'slotera-booking'); ?></a>
                    <?php elseif (empty($location['is_active'])) : ?>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=sltr_restore_location&id=' . $id), 'sltr_restore_location_' . $id)); ?>"><?php esc_html_e('Restore', 'slotera-booking'); ?></a>
                    <?php else : ?>
                        <span class="description"><?php echo esc_html(sprintf(_n('In use by %d package', 'In use by %d packages', (int) ($location['linked_package_count'] ?? 0), 'slotera-booking'), (int) ($location['linked_package_count'] ?? 0))); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; else : ?>
            <tr><td colspan="5"><?php esc_html_e('No locations found.', 'slotera-booking'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
