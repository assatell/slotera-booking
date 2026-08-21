<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap sltr-admin">
    <header class="sltr-page-header">
        <div class="sltr-page-header__content">
            <h1 class="sltr-page-header__title"><?php esc_html_e('Categories', 'slotera-booking'); ?></h1>
        </div>
        <div class="sltr-page-header__actions">
            <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=slotera-categories&action=new')); ?>"><?php esc_html_e('Add New', 'slotera-booking'); ?></a>
        </div>
    </header>

    <?php $sltr_request = new \Slotera\Application\Services\RequestValidator(); ?>
    <?php $sltr_message = $sltr_request->get_key('sltr_message'); ?>
    <?php $sltr_error = $sltr_request->get_key('sltr_error'); ?>
    <?php if ($sltr_message !== '') : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Category updated.', 'slotera-booking'); ?></p></div>
    <?php elseif ($sltr_error === 'in_use') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('This category is in use by one or more packages and cannot be deactivated.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Slug', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Order', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Status', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Actions', 'slotera-booking'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($categories)) : ?>
                <?php foreach ($categories as $c) : ?>
                    <?php
                    $id = (int) ($c['id'] ?? 0);
                    $page_id = (int) ($c['page_id'] ?? 0);
                    $edit_page_url = $page_id > 0 ? get_edit_post_link($page_id, '') : '';
                    $open_page_url = $page_id > 0 ? get_permalink($page_id) : '';
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($c['name'] ?? ''); ?></strong></td>
                        <td><?php echo esc_html($c['slug'] ?? ''); ?></td>
                        <td><?php echo esc_html((string) ($c['sort_order'] ?? 0)); ?></td>
                        <td><?php echo !empty($c['is_active']) ? esc_html__('Active', 'slotera-booking') : esc_html__('Draft', 'slotera-booking'); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-categories&action=edit&id=' . $id)); ?>"><?php esc_html_e('Edit', 'slotera-booking'); ?></a>
                            &nbsp;|&nbsp;
                            <?php if (!empty($c['is_active']) && (int) ($c['linked_package_count'] ?? 0) === 0) : ?>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=sltr_deactivate_category&id=' . $id), 'sltr_deactivate_category_' . $id)); ?>" data-sltr-confirm="<?php esc_attr_e('Deactivate this category and move it to Draft?', 'slotera-booking'); ?>" data-sltr-confirm-title="<?php esc_attr_e('Deactivate category?', 'slotera-booking'); ?>" data-sltr-confirm-button="<?php esc_attr_e('Deactivate', 'slotera-booking'); ?>"><?php esc_html_e('Deactivate', 'slotera-booking'); ?></a>
                            <?php elseif (empty($c['is_active'])) : ?>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=sltr_restore_category&id=' . $id), 'sltr_restore_category_' . $id)); ?>"><?php esc_html_e('Restore', 'slotera-booking'); ?></a>
                            <?php else : ?>
                                <span class="description"><?php echo esc_html(sprintf(_n('In use by %d package', 'In use by %d packages', (int) ($c['linked_package_count'] ?? 0), 'slotera-booking'), (int) ($c['linked_package_count'] ?? 0))); ?></span>
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
                <tr><td colspan="5"><?php esc_html_e('No categories found.', 'slotera-booking'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
