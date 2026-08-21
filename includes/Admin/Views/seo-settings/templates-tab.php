<?php
if (!defined('ABSPATH')) { exit; }

$sltr_seo_variables = class_exists('\Slotera\Application\Services\SEOTemplateService')
    ? \Slotera\Application\Services\SEOTemplateService::variables()
    : [];
?>
<div class="sltr-panel" style="margin-top:16px;">
    <h2><?php esc_html_e('SEO Templates', 'slotera-booking'); ?></h2>
    <p><?php esc_html_e('Templates are used as fallbacks when an individual package/category SEO title or description is empty. Individual SEO fields always win.', 'slotera-booking'); ?></p>
    <p><strong><?php esc_html_e('Available variables:', 'slotera-booking'); ?></strong>
        <?php foreach ($sltr_seo_variables as $variable => $label) : ?>
            <code><?php echo esc_html($variable); ?></code>
        <?php endforeach; ?>
    </p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_save_seo_templates">
        <?php wp_nonce_field('sltr_save_seo_templates'); ?>
        <table class="form-table" role="presentation"><tbody>
            <tr>
                <th scope="row"><?php esc_html_e('Package page title', 'slotera-booking'); ?></th>
                <td><input class="large-text" name="seo_template_package_title" value="<?php echo esc_attr((string) ($settings['seo_template_package_title'] ?? '{package_name} | {site_name}')); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Package page description', 'slotera-booking'); ?></th>
                <td><textarea class="large-text" rows="2" name="seo_template_package_description"><?php echo esc_textarea((string) ($settings['seo_template_package_description'] ?? 'Book {package_name} online at {site_name}.')); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Category page title', 'slotera-booking'); ?></th>
                <td><input class="large-text" name="seo_template_category_title" value="<?php echo esc_attr((string) ($settings['seo_template_category_title'] ?? '{category_name} | {site_name}')); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Category page description', 'slotera-booking'); ?></th>
                <td><textarea class="large-text" rows="2" name="seo_template_category_description"><?php echo esc_textarea((string) ($settings['seo_template_category_description'] ?? 'Browse {category_name} packages and book online at {site_name}.')); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Location landing title', 'slotera-booking'); ?></th>
                <td><input class="large-text" name="seo_template_location_title" value="<?php echo esc_attr((string) ($settings['seo_template_location_title'] ?? 'Services in {location_name} | {site_name}')); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Location landing description', 'slotera-booking'); ?></th>
                <td><textarea class="large-text" rows="2" name="seo_template_location_description"><?php echo esc_textarea((string) ($settings['seo_template_location_description'] ?? 'Browse available packages and book services in {location_name} online at {site_name}.')); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Local package title', 'slotera-booking'); ?></th>
                <td><input class="large-text" name="seo_template_local_package_title" value="<?php echo esc_attr((string) ($settings['seo_template_local_package_title'] ?? '{package_name} in {location_name} | {site_name}')); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Local package description', 'slotera-booking'); ?></th>
                <td><textarea class="large-text" rows="2" name="seo_template_local_package_description"><?php echo esc_textarea((string) ($settings['seo_template_local_package_description'] ?? 'Book {package_name} in {location_name} online at {site_name}.')); ?></textarea></td>
            </tr>
        </tbody></table>
        <p><button class="button button-primary"><?php esc_html_e('Save SEO templates', 'slotera-booking'); ?></button></p>
    </form>
</div>

<div class="sltr-panel" style="margin-top:16px;">
    <h2><?php esc_html_e('Bulk metadata management', 'slotera-booking'); ?></h2>
    <p><?php esc_html_e('Generate package/category SEO titles and descriptions from the templates above. Use empty-only mode first to avoid overwriting hand-written SEO.', 'slotera-booking'); ?></p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_bulk_apply_seo_metadata">
        <?php wp_nonce_field('sltr_bulk_apply_seo_metadata'); ?>
        <table class="form-table" role="presentation"><tbody>
            <tr>
                <th scope="row"><?php esc_html_e('Target', 'slotera-booking'); ?></th>
                <td>
                    <select name="bulk_target">
                        <option value="packages"><?php esc_html_e('Packages', 'slotera-booking'); ?></option>
                        <option value="categories"><?php esc_html_e('Categories', 'slotera-booking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Fields', 'slotera-booking'); ?></th>
                <td>
                    <label><input type="checkbox" name="bulk_include_titles" value="1" checked> <?php esc_html_e('SEO titles', 'slotera-booking'); ?></label><br>
                    <label><input type="checkbox" name="bulk_include_descriptions" value="1" checked> <?php esc_html_e('SEO descriptions', 'slotera-booking'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Mode', 'slotera-booking'); ?></th>
                <td>
                    <label><input type="radio" name="bulk_mode" value="empty_only" checked> <?php esc_html_e('Fill only empty metadata', 'slotera-booking'); ?></label><br>
                    <label><input type="radio" name="bulk_mode" value="overwrite"> <?php esc_html_e('Overwrite existing metadata', 'slotera-booking'); ?></label>
                </td>
            </tr>
        </tbody></table>
        <p><button class="button button-secondary"><?php esc_html_e('Apply templates in bulk', 'slotera-booking'); ?></button></p>
    </form>
</div>
