<?php
if (!defined('ABSPATH')) {
    exit;
}

$category_id = (int) ($category['id'] ?? 0);
$is_active = (int) ($category['is_active'] ?? 1);
?>
<div class="wrap sltr-admin">
    <?php $sltr_error = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_error'); ?>
    <?php if ($sltr_error === 'save_failed') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Category could not be saved. Please review the fields and try again.', 'slotera-booking'); ?></p></div>
    <?php elseif ($sltr_error === 'slug_exists') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('This slug is already in use. Please choose another one.', 'slotera-booking'); ?></p></div>
    <?php elseif ($sltr_error === 'slug_locked') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('This operation is not possible. The slug is locked after the first save.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>
    <h1>
        <?php echo $category_id ? esc_html__('Edit category', 'slotera-booking') : esc_html__('New category', 'slotera-booking'); ?>
    </h1>
    <div class="sltr-admin-help-card">
        <strong><?php esc_html_e('Category setup guide', 'slotera-booking'); ?></strong>
        <p><?php esc_html_e('Categories group packages on public package lists and category shortcodes. Inactive categories are hidden from customers.', 'slotera-booking'); ?></p>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_save_category">
        <input type="hidden" name="id" value="<?php echo esc_attr((string) $category_id); ?>">
        <?php wp_nonce_field('sltr_save_category'); ?>

        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Name', 'slotera-booking'); ?></th>
                <td>
                    <input
                        required
                        id="sltr-category-name"
                        class="regular-text sltr-seo-preview-source"
                        name="name"
                        value="<?php echo esc_attr((string) ($category['name'] ?? '')); ?>"
                    >
                    <p class="description"><?php esc_html_e('Customer-facing category name shown in admin lists and public category pages.', 'slotera-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Slug', 'slotera-booking'); ?></th>
                <td>
                    <input
                        id="sltr-category-slug"
                        class="regular-text sltr-slug-field"
                        name="slug"
                        data-sltr-slug-source="#sltr-category-name"
                        value="<?php echo esc_attr((string) ($category['slug'] ?? '')); ?>"
                        <?php echo $category_id > 0 && trim((string) ($category['slug'] ?? '')) !== '' ? 'readonly aria-readonly="true"' : ''; ?>
                    >
                    <?php if ($category_id > 0 && trim((string) ($category['slug'] ?? '')) !== '') : ?>
                        <button type="button" class="button" disabled><?php esc_html_e('Generated', 'slotera-booking'); ?></button>
                    <?php else : ?>
                        <button type="button" class="button sltr-generate-slug" data-sltr-no-processing="1" data-target="#sltr-category-slug" data-source="#sltr-category-name"><?php esc_html_e('Generate slug', 'slotera-booking'); ?></button>
                    <?php endif; ?>
                    <p class="description"><?php esc_html_e('The slug must be unique. After the first save it is locked and cannot be changed.', 'slotera-booking'); ?></p>
                    <p class="description"><strong><?php esc_html_e('Slug preview:', 'slotera-booking'); ?></strong> <code class="sltr-slug-preview" data-source="#sltr-category-slug"></code></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Description', 'slotera-booking'); ?></th>
                <td>
                    <textarea id="sltr-category-description" class="large-text" rows="4" name="description"><?php echo esc_textarea((string) ($category['description'] ?? '')); ?></textarea>
                    <p class="description"><?php esc_html_e('Optional text shown wherever the theme or shortcode displays category descriptions.', 'slotera-booking'); ?></p>
                </td>
            </tr>

            
            <tr>
                <th><?php esc_html_e('Sort order', 'slotera-booking'); ?></th>
                <td>
                    <input
                        type="number"
                        name="sort_order"
                        value="<?php echo esc_attr((string) ($category['sort_order'] ?? 0)); ?>"
                    >
                    <p class="description"><?php esc_html_e('Lower numbers appear first in category lists.', 'slotera-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Status', 'slotera-booking'); ?></th>
                <td><strong><?php echo $is_active ? esc_html__('Active', 'slotera-booking') : esc_html__('Draft', 'slotera-booking'); ?></strong><p class="description"><?php esc_html_e('Change status from the Categories list using Deactivate or Restore.', 'slotera-booking'); ?></p></td>
            </tr>
        </table>

        <p>
            <button class="button button-primary"><?php esc_html_e('Save category', 'slotera-booking'); ?></button>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=slotera-categories')); ?>">
                <?php esc_html_e('Back', 'slotera-booking'); ?>
            </a>
        </p>
    </form>
</div>
