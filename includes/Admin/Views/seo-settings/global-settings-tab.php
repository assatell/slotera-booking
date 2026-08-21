<?php if (!defined('ABSPATH')) { exit; } ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-panel" style="margin-top:16px;">
            <input type="hidden" name="action" value="sltr_save_seo_center_settings">
            <?php wp_nonce_field('sltr_save_seo_center_settings'); ?>
            <table class="form-table" role="presentation"><tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Other Pages SEO Module', 'slotera-booking'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="seo_wp_pages_enabled" value="1" <?php checked($sltr_wp_enabled); ?> <?php disabled($sltr_seo_plugins_blocking); ?>>
                            <?php esc_html_e('Master switch for ordinary WordPress pages. Individual pages can still be enabled or disabled separately.', 'slotera-booking'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Default: off. Automatically locked when a dedicated SEO plugin is detected.', 'slotera-booking'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('SEO plugin compatibility', 'slotera-booking'); ?></th>
                    <td>
                        <select name="seo_meta_output_mode">
                            <option value="auto" <?php selected($sltr_seo_mode, 'auto'); ?>><?php esc_html_e('Auto: do not duplicate meta tags when an SEO plugin is detected', 'slotera-booking'); ?></option>
                            <option value="force" <?php selected($sltr_seo_mode, 'force'); ?>><?php esc_html_e('Force Slotera meta output for Slotera pages', 'slotera-booking'); ?></option>
                            <option value="disabled" <?php selected($sltr_seo_mode, 'disabled'); ?>><?php esc_html_e('Disable all Slotera SEO meta output', 'slotera-booking'); ?></option>
                        </select>
                        <p class="description"><strong><?php esc_html_e('Detected:', 'slotera-booking'); ?></strong> <?php echo esc_html($sltr_seo_plugins_blocking ? implode(', ', $detected_seo_plugins) : __('No external SEO plugin detected', 'slotera-booking')); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Robots file', 'slotera-booking'); ?></th>
                    <td>
                        <p><strong><?php esc_html_e('robots.txt status:', 'slotera-booking'); ?></strong> <?php echo esc_html($robots_file_exists ? __('File exists', 'slotera-booking') : __('No physical robots.txt file found', 'slotera-booking')); ?></p>
                        <p class="description"><?php echo esc_html($robots_file_path); ?></p>
                        <?php if (!$robots_file_writable) : ?>
                            <div class="notice notice-error inline"><p><?php esc_html_e('Slotera cannot write to the site root. Check filesystem permissions before creating robots.txt.', 'slotera-booking'); ?></p></div>
                        <?php endif; ?>
                        <?php if ($sltr_seo_plugins_blocking) : ?>
                            <div class="notice notice-error inline"><p><strong><?php esc_html_e('Strict warning:', 'slotera-booking'); ?></strong> <?php esc_html_e('SEO plugins are active. If they manage robots.txt, replacing the physical file can make those plugins work incorrectly or stop applying their robots.txt rules.', 'slotera-booking'); ?></p></div>
                        <?php endif; ?>
                        <p><strong><?php esc_html_e('Smart Robots Builder', 'slotera-booking'); ?></strong></p>
                        <label><input type="checkbox" name="seo_robots_smart_builder_enabled" value="1" <?php checked(!empty($settings['seo_robots_smart_builder_enabled'])); ?>> <?php esc_html_e('Enable Smart Robots Builder', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_robots_block_wp_search" value="1" <?php checked(!empty($settings['seo_robots_block_wp_search'])); ?>> <?php esc_html_e('Block WP search URLs', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_robots_block_slotera_technical" value="1" <?php checked(!empty($settings['seo_robots_block_slotera_technical'])); ?>> <?php esc_html_e('Block technical Slotera pages', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_robots_block_tracking_params" value="1" <?php checked(!empty($settings['seo_robots_block_tracking_params'])); ?>> <?php esc_html_e('Block tracking and filter parameter URLs', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_robots_block_attachment_pages" value="1" <?php checked(!empty($settings['seo_robots_block_attachment_pages'])); ?>> <?php esc_html_e('Block WordPress attachment pages', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_robots_add_sitemap" value="1" <?php checked(!empty($settings['seo_robots_add_sitemap'])); ?>> <?php esc_html_e('Add sitemap automatically', 'slotera-booking'); ?></label>
                        <p class="description"><?php esc_html_e('Save SEO settings first, then create robots.txt to generate the latest extended rules.', 'slotera-booking'); ?></p>
                        <p><label for="sltr-seo-robots-custom-rules"><strong><?php esc_html_e('Custom robots rules', 'slotera-booking'); ?></strong></label></p>
                        <textarea id="sltr-seo-robots-custom-rules" class="large-text code" rows="5" name="seo_robots_custom_rules"><?php echo esc_textarea((string) ($settings['seo_robots_custom_rules'] ?? '')); ?></textarea>
                        <p class="description"><?php esc_html_e('Optional extra Allow/Disallow lines appended to the generated robots.txt.', 'slotera-booking'); ?></p>
                        <p><strong><?php esc_html_e('Generated preview', 'slotera-booking'); ?></strong></p>
                        <textarea class="large-text code" rows="12" readonly><?php echo esc_textarea($robots_default_content); ?></textarea>
                        <?php if ($robots_file_exists) : ?>
                            <div class="notice notice-warning inline"><p><?php esc_html_e('A physical robots.txt file already exists. Slotera requires overwrite confirmation before replacing it.', 'slotera-booking'); ?></p></div>
                            <p><label><input type="checkbox" name="confirm_overwrite" value="1" form="sltr-robots-default-form"> <?php esc_html_e('I understand this will overwrite the existing robots.txt file', 'slotera-booking'); ?></label></p>
                        <?php endif; ?>
                        <p><button type="submit" class="button" form="sltr-robots-default-form" <?php disabled(!$robots_file_writable); ?>><?php esc_html_e('Create smart robots.txt', 'slotera-booking'); ?></button></p>
                        <hr>
                        <p><strong><?php esc_html_e('Use your own robots.txt', 'slotera-booking'); ?></strong></p>
                        <textarea class="large-text code" rows="9" name="robots_custom_content" form="sltr-robots-custom-form"><?php echo esc_textarea($robots_current_content !== '' ? $robots_current_content : $robots_default_content); ?></textarea>
                        <?php if ($robots_file_exists) : ?>
                            <p><label><input type="checkbox" name="confirm_overwrite" value="1" form="sltr-robots-custom-form"> <?php esc_html_e('I understand this will overwrite the existing robots.txt file', 'slotera-booking'); ?></label></p>
                        <?php endif; ?>
                        <p><button type="submit" class="button" form="sltr-robots-custom-form" <?php disabled(!$robots_file_writable); ?>><?php esc_html_e('Create file with these settings', 'slotera-booking'); ?></button></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Sitemap', 'slotera-booking'); ?></th>
                    <td>
                        <label><input type="checkbox" name="seo_sitemap_enabled" value="1" <?php checked(!empty($settings['seo_sitemap_enabled']) && !$sltr_seo_plugins_blocking); ?> <?php disabled($sltr_seo_plugins_blocking); ?>> <?php esc_html_e('Enable Slotera XML sitemap', 'slotera-booking'); ?></label><br>
                        <?php if ($sltr_seo_plugins_blocking) : ?>
                            <div class="notice notice-info inline"><p><?php esc_html_e('Locked because a dedicated SEO plugin is active. Use that plugin sitemap to avoid duplicate XML sitemaps.', 'slotera-booking'); ?></p></div>
                        <?php endif; ?>
                        <p><strong><?php esc_html_e('Content types:', 'slotera-booking'); ?></strong></p>
                        <label><input type="checkbox" name="seo_sitemap_include_packages" value="1" <?php checked(!empty($settings['seo_sitemap_include_packages'])); ?> <?php disabled($sltr_seo_plugins_blocking); ?>> <?php esc_html_e('Include packages', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_sitemap_include_categories" value="1" <?php checked(!empty($settings['seo_sitemap_include_categories'])); ?> <?php disabled($sltr_seo_plugins_blocking); ?>> <?php esc_html_e('Include categories', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_sitemap_include_locations" value="1" <?php checked(!empty($settings['seo_sitemap_include_locations'])); ?> <?php disabled($sltr_seo_plugins_blocking); ?>> <?php esc_html_e('Include locations / local SEO pages', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_sitemap_include_other_pages" value="1" <?php checked(!empty($settings['seo_sitemap_include_other_pages'])); ?> <?php disabled($sltr_seo_plugins_blocking || !$sltr_wp_enabled); ?>> <?php esc_html_e('Include other WP pages', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_sitemap_include_posts" value="1" <?php checked(!empty($settings['seo_sitemap_include_posts'])); ?> <?php disabled($sltr_seo_plugins_blocking); ?>> <?php esc_html_e('Include posts', 'slotera-booking'); ?></label>
                        <p class="description"><?php esc_html_e('Other WP pages are included only when the Other Pages SEO Module is enabled globally, the individual page is enabled, noindex is off, and no 301 redirect is set.', 'slotera-booking'); ?></p>
                        <p class="description"><?php esc_html_e('Sitemap URL:', 'slotera-booking'); ?> <a href="<?php echo esc_url($sltr_sitemap_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($sltr_sitemap_url); ?></a></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Title format', 'slotera-booking'); ?></th>
                    <td><input class="regular-text" name="seo_title_format" value="<?php echo esc_attr((string) ($settings['seo_title_format'] ?? '{title} | {site}')); ?>"><p class="description">{title}, {site}</p></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Robots defaults', 'slotera-booking'); ?></th>
                    <td>
                        <?php $sltr_robots = (string) ($settings['seo_default_robots'] ?? 'index,follow'); ?>
                        <select name="seo_default_robots">
                            <?php foreach (['index,follow','noindex,follow','index,nofollow','noindex,nofollow'] as $robot) : ?>
                                <option value="<?php echo esc_attr($robot); ?>" <?php selected($sltr_robots, $robot); ?>><?php echo esc_html($robot); ?></option>
                            <?php endforeach; ?>
                        </select><br>
                        <label><input type="checkbox" name="seo_noindex_empty_categories" value="1" <?php checked(!empty($settings['seo_noindex_empty_categories'])); ?>> <?php esc_html_e('Noindex empty categories by default', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_noindex_inactive_items" value="1" <?php checked(!empty($settings['seo_noindex_inactive_items'])); ?>> <?php esc_html_e('Noindex inactive packages/categories by default', 'slotera-booking'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Breadcrumbs', 'slotera-booking'); ?></th>
                    <td>
                        <label><input type="checkbox" name="seo_breadcrumbs_enabled" value="1" <?php checked(!empty($settings['seo_breadcrumbs_enabled'])); ?>> <?php esc_html_e('Enable breadcrumbs', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_breadcrumbs_show_packages" value="1" <?php checked(!empty($settings['seo_breadcrumbs_show_packages'])); ?>> <?php esc_html_e('Show on package pages', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_breadcrumbs_show_categories" value="1" <?php checked(!empty($settings['seo_breadcrumbs_show_categories'])); ?>> <?php esc_html_e('Show on category pages', 'slotera-booking'); ?></label><br>
                        <label><input type="checkbox" name="seo_breadcrumbs_show_local" value="1" <?php checked(!empty($settings['seo_breadcrumbs_show_local'])); ?>> <?php esc_html_e('Show on local SEO pages', 'slotera-booking'); ?></label>
                    </td>
                </tr>
            </tbody></table>
            <p><button class="button button-primary"><?php esc_html_e('Save SEO settings', 'slotera-booking'); ?></button></p>
        </form>
        <form id="sltr-robots-default-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:none;">
            <input type="hidden" name="action" value="sltr_create_robots_txt">
            <input type="hidden" name="robots_mode" value="default">
            <?php wp_nonce_field('sltr_create_robots_txt'); ?>
        </form>
        <form id="sltr-robots-custom-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:none;">
            <input type="hidden" name="action" value="sltr_create_robots_txt">
            <input type="hidden" name="robots_mode" value="custom">
            <?php wp_nonce_field('sltr_create_robots_txt'); ?>
        </form>
