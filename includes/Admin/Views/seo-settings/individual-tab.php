<?php if (!defined('ABSPATH')) { exit; } ?>
            <div class="sltr-settings-card sltr-individual-seo-card">
                <div class="sltr-settings-card-header">
                    <h2><?php esc_html_e('Individual SEO Settings', 'slotera-booking'); ?></h2>
                    <p><?php esc_html_e('Choose the content you want to edit. The selected SEO form appears below.', 'slotera-booking'); ?></p>
                </div>

                <div class="sltr-individual-seo-picker">
                    <label for="sltr-package-seo-select"><strong><?php esc_html_e('Packages SEO', 'slotera-booking'); ?></strong></label>
                    <select id="sltr-package-seo-select" class="sltr-seo-editor-select" data-panel-group="packages" <?php disabled(empty($packages)); ?>>
                        <?php if (empty($packages)) : ?>
                            <option value=""><?php esc_html_e('No packages found yet.', 'slotera-booking'); ?></option>
                        <?php else : ?>
                            <option value=""><?php esc_html_e('Select a package', 'slotera-booking'); ?></option>
                            <?php foreach ($packages as $package) : ?>
                                <option value="<?php echo esc_attr('package-' . (int) ($package['id'] ?? 0)); ?>"><?php echo esc_html((string) ($package['title'] ?? __('Untitled package', 'slotera-booking'))); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if (!empty($packages)) : ?>
                    <div class="sltr-seo-editor-group" data-panel-group="packages">
                        <?php foreach ($packages as $package) : ?><?php $sltr_render_slotera_seo_form((array) $package, 'package'); ?><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="sltr-individual-seo-picker">
                    <label for="sltr-category-seo-select"><strong><?php esc_html_e('Categories SEO', 'slotera-booking'); ?></strong></label>
                    <select id="sltr-category-seo-select" class="sltr-seo-editor-select" data-panel-group="categories" <?php disabled(empty($categories)); ?>>
                        <?php if (empty($categories)) : ?>
                            <option value=""><?php esc_html_e('No categories found yet.', 'slotera-booking'); ?></option>
                        <?php else : ?>
                            <option value=""><?php esc_html_e('Select a category', 'slotera-booking'); ?></option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo esc_attr('category-' . (int) ($category['id'] ?? 0)); ?>"><?php echo esc_html((string) ($category['name'] ?? __('Untitled category', 'slotera-booking'))); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if (!empty($categories)) : ?>
                    <div class="sltr-seo-editor-group" data-panel-group="categories">
                        <?php foreach ($categories as $category) : ?><?php $sltr_render_slotera_seo_form((array) $category, 'category'); ?><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="sltr-individual-seo-picker">
                    <label for="sltr-local-seo-select"><strong><?php esc_html_e('Local SEO', 'slotera-booking'); ?></strong></label>
                    <select id="sltr-local-seo-select" class="sltr-seo-editor-select" data-panel-group="local">
                        <option value=""><?php esc_html_e('Select a package and location', 'slotera-booking'); ?></option>
                        <?php foreach ($package_location_relations as $relation) : ?>
                            <option value="<?php echo esc_attr('local-package-' . (int) ($relation['package_id'] ?? 0) . '-location-' . (int) ($relation['location_id'] ?? 0)); ?>"><?php echo esc_html((string) ($relation['package_title'] ?? __('Package', 'slotera-booking')) . ' → ' . (string) ($relation['location_name'] ?? __('Location', 'slotera-booking'))); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sltr-seo-editor-group" data-panel-group="local">
                    <p class="description"><?php esc_html_e('Package/location local SEO overrides are managed here only. Package edit screens only assign locations.', 'slotera-booking'); ?></p>
                    <?php if (empty($package_location_relations)) : ?>
                        <p><?php esc_html_e('No package/location assignments found yet.', 'slotera-booking'); ?></p>
                    <?php else : ?>
                        <?php foreach ($package_location_relations as $relation) : ?><?php $sltr_render_local_seo_form((array) $relation); ?><?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="sltr-individual-seo-picker">
                    <label for="sltr-page-seo-select"><strong><?php esc_html_e('Other Pages SEO', 'slotera-booking'); ?></strong></label>
                    <select id="sltr-page-seo-select" class="sltr-seo-editor-select" data-panel-group="pages">
                        <option value=""><?php esc_html_e('Select a WordPress page', 'slotera-booking'); ?></option>
                        <?php foreach ((array) $pages as $page_option) : ?>
                            <option value="<?php echo esc_attr('wp-page-' . (int) $page_option->ID); ?>"><?php echo esc_html(get_the_title((int) $page_option->ID)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sltr-seo-editor-group" data-panel-group="pages">
                    <p><?php esc_html_e('Lightweight SEO manager for ordinary WordPress pages that are not assigned to Slotera packages/categories and do not contain Slotera shortcodes.', 'slotera-booking'); ?></p>
                    <form method="get" class="sltr-other-pages-seo-filters">
                        <input type="hidden" name="page" value="slotera-settings">
                        <input type="hidden" name="section" value="seo">
                        <input type="hidden" name="tab" value="individual">
                        <label>
                            <span class="screen-reader-text"><?php esc_html_e('Search pages', 'slotera-booking'); ?></span>
                            <input type="search" name="sltr_other_pages_search" value="<?php echo esc_attr((string) ($other_pages_search ?? '')); ?>" placeholder="<?php esc_attr_e('Search pages...', 'slotera-booking'); ?>">
                        </label>
                        <label>
                            <span class="screen-reader-text"><?php esc_html_e('Filter by status', 'slotera-booking'); ?></span>
                            <select name="sltr_other_pages_status">
                                <option value="all" <?php selected((string) ($other_pages_status ?? 'all'), 'all'); ?>><?php esc_html_e('All statuses', 'slotera-booking'); ?></option>
                                <option value="publish" <?php selected((string) ($other_pages_status ?? 'all'), 'publish'); ?>><?php esc_html_e('Published', 'slotera-booking'); ?></option>
                                <option value="draft" <?php selected((string) ($other_pages_status ?? 'all'), 'draft'); ?>><?php esc_html_e('Draft', 'slotera-booking'); ?></option>
                                <option value="pending" <?php selected((string) ($other_pages_status ?? 'all'), 'pending'); ?>><?php esc_html_e('Pending', 'slotera-booking'); ?></option>
                                <option value="private" <?php selected((string) ($other_pages_status ?? 'all'), 'private'); ?>><?php esc_html_e('Private', 'slotera-booking'); ?></option>
                            </select>
                        </label>
                        <button class="button"><?php esc_html_e('Filter', 'slotera-booking'); ?></button>
                        <?php if (!empty($other_pages_search) || (string) ($other_pages_status ?? 'all') !== 'all') : ?>
                            <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'seo', 'tab' => 'individual'], admin_url('admin.php'))); ?>"><?php esc_html_e('Reset', 'slotera-booking'); ?></a>
                        <?php endif; ?>
                        <span class="description"><?php echo esc_html(sprintf(_n('%d page found', '%d pages found', (int) ($other_pages_total_items ?? 0), 'slotera-booking'), (int) ($other_pages_total_items ?? 0))); ?></span>
                    </form>
                    <?php if (!$sltr_wp_enabled) : ?>
                        <div class="notice notice-info inline"><p><?php echo esc_html($sltr_seo_plugins_blocking ? __('Locked because an SEO plugin is active.', 'slotera-booking') : __('Other Pages SEO is currently disabled in Global SEO.', 'slotera-booking')); ?></p></div>
                    <?php endif; ?>
                    <?php if (empty($pages)) : ?>
                        <p><?php esc_html_e('No ordinary WordPress pages found.', 'slotera-booking'); ?></p>
                    <?php endif; ?>
                    <?php foreach ((array) $pages as $page) :
                        $pid = (int) $page->ID;
                        $enabled = (int) get_post_meta($pid, '_sltr_wp_page_seo_enabled', true) === 1;
                        $title = (string) get_post_meta($pid, '_sltr_wp_page_seo_title', true);
                        $desc = (string) get_post_meta($pid, '_sltr_wp_page_seo_description', true);
                        $og_title = (string) get_post_meta($pid, '_sltr_wp_page_seo_og_title', true);
                        $og_desc = (string) get_post_meta($pid, '_sltr_wp_page_seo_og_description', true);
                        $og_image = (string) get_post_meta($pid, '_sltr_wp_page_seo_og_image', true);
                        $noindex = (int) get_post_meta($pid, '_sltr_wp_page_seo_noindex', true) === 1;
                        $canonical = (string) get_post_meta($pid, '_sltr_wp_page_seo_canonical', true);
                        $redirect_301 = (string) get_post_meta($pid, '_sltr_wp_page_seo_redirect_301', true);
                        $preview_title = $title !== '' ? $title : get_the_title($pid);
                        $preview_desc = $desc !== '' ? $desc : wp_trim_words(wp_strip_all_tags((string) $page->post_content), 24);
                    ?>
                        <details id="<?php echo esc_attr('wp-page-' . $pid); ?>" class="sltr-seo-item-editor" <?php echo isset($_GET['sltr_focus']) && sanitize_text_field((string) wp_unslash($_GET['sltr_focus'])) === 'wp-page-' . $pid ? 'open' : ''; ?>>
                            <summary><strong><?php echo esc_html(get_the_title($pid)); ?></strong> <span class="description">/ <?php echo esc_html((string) wp_make_link_relative((string) get_permalink($pid))); ?></span></summary>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
                                <input type="hidden" name="action" value="sltr_save_wp_page_seo">
                                <input type="hidden" name="post_id" value="<?php echo esc_attr((string) $pid); ?>">
                                <?php wp_nonce_field('sltr_save_wp_page_seo'); ?>
                                <table class="form-table" role="presentation"><tbody>
                                    <tr><th scope="row"><?php esc_html_e('Enable SEO for this page', 'slotera-booking'); ?></th><td><label><input type="checkbox" name="enabled" value="1" <?php checked($enabled && $sltr_wp_enabled); ?> <?php disabled(!$sltr_wp_enabled); ?>> <?php esc_html_e('Works only when Other Pages SEO Module is enabled globally.', 'slotera-booking'); ?></label></td></tr>
                                    <tr><th scope="row"><?php esc_html_e('SEO title', 'slotera-booking'); ?></th><td><?php $wp_seo_title_field_id = 'sltr-seo-title-wp-page-' . $pid; ?><input id="<?php echo esc_attr($wp_seo_title_field_id); ?>" class="large-text sltr-seo-meter-field" name="seo_title" maxlength="255" data-sltr-seo-kind="title" data-sltr-seo-min="40" data-sltr-seo-max="60" value="<?php echo esc_attr($title); ?>"><?php $sltr_render_seo_length_meter($wp_seo_title_field_id, 'title'); ?></td></tr>
                                    <tr><th scope="row"><?php esc_html_e('Meta description', 'slotera-booking'); ?></th><td><?php $wp_seo_description_field_id = 'sltr-seo-description-wp-page-' . $pid; ?><textarea id="<?php echo esc_attr($wp_seo_description_field_id); ?>" class="large-text sltr-seo-meter-field" rows="3" name="seo_description" data-sltr-seo-kind="description" data-sltr-seo-min="120" data-sltr-seo-max="160"><?php echo esc_textarea($desc); ?></textarea><?php $sltr_render_seo_length_meter($wp_seo_description_field_id, 'description'); ?></td></tr>
                                    <tr><th scope="row"><?php esc_html_e('OpenGraph title', 'slotera-booking'); ?></th><td><input class="large-text" name="seo_og_title" maxlength="255" value="<?php echo esc_attr($og_title); ?>"></td></tr>
                                    <tr><th scope="row"><?php esc_html_e('OpenGraph description', 'slotera-booking'); ?></th><td><textarea class="large-text" rows="3" name="seo_og_description"><?php echo esc_textarea($og_desc); ?></textarea></td></tr>
                                    <tr><th scope="row"><?php esc_html_e('OpenGraph image URL', 'slotera-booking'); ?></th><td><input class="large-text" type="url" name="seo_og_image" value="<?php echo esc_attr($og_image); ?>"></td></tr>
                                    <tr><th scope="row"><?php esc_html_e('Canonical URL', 'slotera-booking'); ?></th><td><input class="large-text" type="url" name="seo_canonical" value="<?php echo esc_attr($canonical); ?>"></td></tr>
                                    <tr><th scope="row"><?php esc_html_e('301 Redirect URL', 'slotera-booking'); ?></th><td><input class="large-text" type="url" name="seo_redirect_301" value="<?php echo esc_attr($redirect_301); ?>"></td></tr>
                                    <tr><th scope="row"><?php esc_html_e('Robots', 'slotera-booking'); ?></th><td><label><input type="checkbox" name="seo_noindex" value="1" <?php checked($noindex); ?>> <?php esc_html_e('Noindex this page', 'slotera-booking'); ?></label></td></tr>
                                </tbody></table>
                                <div style="max-width:520px;border:1px solid #dcdcde;border-radius:6px;padding:10px;background:#fff;margin:12px 0;">
                                    <div style="color:#1a0dab;font-size:16px;line-height:1.3;"><?php echo esc_html($preview_title); ?></div>
                                    <div style="color:#006621;font-size:12px;"><?php echo esc_html($canonical !== '' ? $canonical : get_permalink($pid)); ?></div>
                                    <div style="color:#545454;font-size:13px;line-height:1.4;"><?php echo esc_html($preview_desc); ?></div>
                                    <?php if ($redirect_301 !== '') : ?><div style="margin-top:6px;color:#b32d2e;font-size:12px;"><?php echo esc_html('301 → ' . $redirect_301); ?></div><?php endif; ?>
                                </div>
                                <p>
                                    <button class="button button-primary" <?php disabled(!$sltr_wp_enabled); ?>><?php esc_html_e('Save page SEO', 'slotera-booking'); ?></button>
                                    <a class="button" href="<?php echo esc_url(get_permalink($pid)); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View', 'slotera-booking'); ?></a>
                                    <a class="button" href="<?php echo esc_url(get_edit_post_link($pid, '')); ?>"><?php esc_html_e('Edit page', 'slotera-booking'); ?></a>
                                </p>
                            </form>
                        </details>
                    <?php endforeach; ?>
                    <?php if ((int) ($other_pages_total_pages ?? 1) > 1) : ?>
                        <div class="tablenav"><div class="tablenav-pages">
                            <?php
                            $base_args = [
                                'page' => 'slotera-settings', 'section' => 'seo',
                                'tab' => 'individual',
                                'sltr_other_pages_search' => (string) ($other_pages_search ?? ''),
                                'sltr_other_pages_status' => (string) ($other_pages_status ?? 'all'),
                            ];
                            $current_page = (int) ($other_pages_page ?? 1);
                            $total_pages = (int) ($other_pages_total_pages ?? 1);
                            ?>
                            <?php if ($current_page > 1) : ?>
                                <a class="button" href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['sltr_other_pages_page' => $current_page - 1]), admin_url('admin.php'))); ?>">&lsaquo; <?php esc_html_e('Previous', 'slotera-booking'); ?></a>
                            <?php endif; ?>
                            <span class="paging-input"><?php echo esc_html(sprintf(__('Page %1$d of %2$d', 'slotera-booking'), $current_page, $total_pages)); ?></span>
                            <?php if ($current_page < $total_pages) : ?>
                                <a class="button" href="<?php echo esc_url(add_query_arg(array_merge($base_args, ['sltr_other_pages_page' => $current_page + 1]), admin_url('admin.php'))); ?>"><?php esc_html_e('Next', 'slotera-booking'); ?> &rsaquo;</a>
                            <?php endif; ?>
                        </div></div>
                    <?php endif; ?>
                </div>
            </div>
