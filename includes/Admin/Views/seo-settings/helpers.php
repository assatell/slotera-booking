<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
$sltr_render_seo_length_meter = static function (string $field_id, string $kind): void {
    $is_title = $kind === 'title';
    $min = $is_title ? 40 : 120;
    $max = $is_title ? 60 : 160;
    $label = sprintf(
        /* translators: 1: minimum characters, 2: maximum characters. */
        __('Recommended length: %1$d–%2$d characters.', 'slotera-booking'),
        $min,
        $max
    );
    ?>
    <span class="sltr-seo-meter" data-sltr-seo-meter-for="<?php echo esc_attr($field_id); ?>" data-sltr-seo-state="empty" aria-hidden="true"><span class="sltr-seo-meter-bar"></span></span>
    <span class="sltr-seo-meter-text" data-sltr-seo-text-for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label); ?></span>
    <?php
};

$sltr_render_slotera_seo_form = static function (array $item, string $type) use ($sltr_render_seo_length_meter): void {
    $id = (int) ($item['id'] ?? 0);
    $name = (string) ($type === 'package' ? ($item['title'] ?? '') : ($item['name'] ?? ''));
    $action = $type === 'package' ? 'sltr_save_slotera_package_seo' : 'sltr_save_slotera_category_seo';
    $nonce = $action;
    $slug = (string) ($item['slug'] ?? '');
    $i18n = json_decode((string) ($item['seo_i18n_json'] ?? ''), true);
    $i18n = is_array($i18n) ? $i18n : [];
    $languages = \Slotera\Application\Services\TranslationRegistry::languages_for_group('frontend');
    $translation_service = new \Slotera\Application\Services\TranslationService();
    $frontend_locale = str_replace('-', '_', (string) $translation_service->locale_for_group('frontend'));
    $wp_locale = function_exists('determine_locale') ? (string) determine_locale() : (string) get_locale();
    $wp_locale = str_replace('-', '_', $wp_locale);
    $robots = (string) ($item['seo_robots'] ?? 'index,follow');
    $position = (string) ($item['seo_site_title_position'] ?? 'right');
    $focus_id = $type . '-' . $id;
    ?>
    <details id="<?php echo esc_attr($focus_id); ?>" class="sltr-seo-item-editor" <?php echo isset($_GET['sltr_focus']) && sanitize_text_field((string) wp_unslash($_GET['sltr_focus'])) === $focus_id ? 'open' : ''; ?>>
        <summary><strong><?php echo esc_html($name !== '' ? $name : sprintf(__('Item #%d', 'slotera-booking'), $id)); ?></strong> <span class="description">/ <?php echo esc_html($slug); ?></span></summary>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
            <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
            <input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>">
            <?php wp_nonce_field($nonce); ?>
            <table class="form-table" role="presentation"><tbody>
                <tr><th scope="row"><?php esc_html_e('SEO title', 'slotera-booking'); ?></th><td><?php $seo_title_field_id = 'sltr-seo-title-' . $type . '-' . $id; ?><input id="<?php echo esc_attr($seo_title_field_id); ?>" class="large-text sltr-seo-meter-field" name="seo_title" maxlength="255" data-sltr-seo-kind="title" data-sltr-seo-min="40" data-sltr-seo-max="60" value="<?php echo esc_attr((string) ($item['seo_title'] ?? '')); ?>"><?php $sltr_render_seo_length_meter($seo_title_field_id, 'title'); ?></td></tr>
                <tr><th scope="row"><?php esc_html_e('Site Title Location', 'slotera-booking'); ?></th><td><select name="seo_site_title_position"><option value="right" <?php selected($position, 'right'); ?>><?php esc_html_e('Right: SEO title | Site title', 'slotera-booking'); ?></option><option value="left" <?php selected($position, 'left'); ?>><?php esc_html_e('Left: Site title | SEO title', 'slotera-booking'); ?></option></select></td></tr>
                <tr><th scope="row"><?php esc_html_e('Meta description', 'slotera-booking'); ?></th><td><?php $seo_description_field_id = 'sltr-seo-description-' . $type . '-' . $id; ?><textarea id="<?php echo esc_attr($seo_description_field_id); ?>" class="large-text sltr-seo-meter-field" rows="3" name="seo_description" data-sltr-seo-kind="description" data-sltr-seo-min="120" data-sltr-seo-max="160"><?php echo esc_textarea((string) ($item['seo_description'] ?? '')); ?></textarea><?php $sltr_render_seo_length_meter($seo_description_field_id, 'description'); ?></td></tr>
                <tr><th scope="row"><?php esc_html_e('OpenGraph title', 'slotera-booking'); ?></th><td><input class="large-text" name="seo_og_title" maxlength="255" value="<?php echo esc_attr((string) ($item['seo_og_title'] ?? '')); ?>"></td></tr>
                <tr><th scope="row"><?php esc_html_e('OpenGraph description', 'slotera-booking'); ?></th><td><textarea class="large-text" rows="3" name="seo_og_description"><?php echo esc_textarea((string) ($item['seo_og_description'] ?? '')); ?></textarea></td></tr>
                <tr><th scope="row"><?php esc_html_e('OpenGraph image URL', 'slotera-booking'); ?></th><td><input class="large-text sltr-seo-og-image-field" name="seo_og_image" type="url" value="<?php echo esc_attr((string) ($item['seo_og_image'] ?? '')); ?>"><button type="button" class="button sltr-seo-og-image-upload" data-target=".sltr-seo-og-image-field"><?php esc_html_e('Choose image', 'slotera-booking'); ?></button></td></tr>
                <tr><th scope="row"><?php esc_html_e('Canonical URL', 'slotera-booking'); ?></th><td><input class="large-text" name="seo_canonical" type="url" value="<?php echo esc_attr((string) ($item['seo_canonical'] ?? '')); ?>"></td></tr>
                <tr><th scope="row"><?php esc_html_e('301 Redirect URL', 'slotera-booking'); ?></th><td><input class="large-text" name="seo_redirect_301" type="url" value="<?php echo esc_attr((string) ($item['seo_redirect_301'] ?? '')); ?>"><p class="description"><?php esc_html_e('Optional. If set, this page will return a 301 redirect to this URL before SEO meta output.', 'slotera-booking'); ?></p></td></tr>
                <tr><th scope="row"><?php esc_html_e('Robots', 'slotera-booking'); ?></th><td><select name="seo_robots"><option value="index,follow" <?php selected($robots, 'index,follow'); ?>>index, follow</option><option value="noindex,follow" <?php selected($robots, 'noindex,follow'); ?>>noindex, follow</option><option value="index,nofollow" <?php selected($robots, 'index,nofollow'); ?>>index, nofollow</option><option value="noindex,nofollow" <?php selected($robots, 'noindex,nofollow'); ?>>noindex, nofollow</option></select></td></tr>
                <tr><th scope="row"><?php esc_html_e('Multilingual SEO overrides', 'slotera-booking'); ?></th><td>
                    <p class="description"><?php echo esc_html(sprintf(__('Current frontend language: %1$s. WP locale: %2$s.', 'slotera-booking'), $frontend_locale, $wp_locale)); ?></p>
                    <?php foreach ($languages as $locale => $label) : $lang = is_array($i18n[$locale] ?? null) ? $i18n[$locale] : []; ?>
                        <details class="sltr-language-seo-panel"><summary><?php echo esc_html($label . ' (' . $locale . ')'); ?></summary>
                            <p><?php $seo_i18n_title_field_id = 'sltr-seo-title-' . $type . '-' . $id . '-' . sanitize_key((string) $locale); ?><label for="<?php echo esc_attr($seo_i18n_title_field_id); ?>"><?php esc_html_e('SEO title', 'slotera-booking'); ?></label><br><input id="<?php echo esc_attr($seo_i18n_title_field_id); ?>" class="large-text sltr-seo-meter-field" name="seo_i18n[<?php echo esc_attr((string) $locale); ?>][seo_title]" maxlength="255" data-sltr-seo-kind="title" data-sltr-seo-min="40" data-sltr-seo-max="60" value="<?php echo esc_attr((string) ($lang['seo_title'] ?? '')); ?>"><?php $sltr_render_seo_length_meter($seo_i18n_title_field_id, 'title'); ?></p>
                            <p><?php $seo_i18n_description_field_id = 'sltr-seo-description-' . $type . '-' . $id . '-' . sanitize_key((string) $locale); ?><label for="<?php echo esc_attr($seo_i18n_description_field_id); ?>"><?php esc_html_e('Meta description', 'slotera-booking'); ?></label><br><textarea id="<?php echo esc_attr($seo_i18n_description_field_id); ?>" class="large-text sltr-seo-meter-field" rows="2" name="seo_i18n[<?php echo esc_attr((string) $locale); ?>][seo_description]" data-sltr-seo-kind="description" data-sltr-seo-min="120" data-sltr-seo-max="160"><?php echo esc_textarea((string) ($lang['seo_description'] ?? '')); ?></textarea><?php $sltr_render_seo_length_meter($seo_i18n_description_field_id, 'description'); ?></p>
                            <p><label><?php esc_html_e('OpenGraph title', 'slotera-booking'); ?><br><input class="large-text" name="seo_i18n[<?php echo esc_attr((string) $locale); ?>][seo_og_title]" maxlength="255" value="<?php echo esc_attr((string) ($lang['seo_og_title'] ?? '')); ?>"></label></p>
                            <p><label><?php esc_html_e('OpenGraph description', 'slotera-booking'); ?><br><textarea class="large-text" rows="2" name="seo_i18n[<?php echo esc_attr((string) $locale); ?>][seo_og_description]"><?php echo esc_textarea((string) ($lang['seo_og_description'] ?? '')); ?></textarea></label></p>
                            <p><label><?php esc_html_e('OpenGraph image URL', 'slotera-booking'); ?><br><input class="large-text" type="url" name="seo_i18n[<?php echo esc_attr((string) $locale); ?>][seo_og_image]" value="<?php echo esc_attr((string) ($lang['seo_og_image'] ?? '')); ?>"></label></p>
                            <p><label><?php esc_html_e('Canonical URL', 'slotera-booking'); ?><br><input class="large-text" type="url" name="seo_i18n[<?php echo esc_attr((string) $locale); ?>][seo_canonical]" value="<?php echo esc_attr((string) ($lang['seo_canonical'] ?? '')); ?>"></label></p>
                        </details>
                    <?php endforeach; ?>
                </td></tr>
            </tbody></table>
            <p><button class="button button-primary"><?php esc_html_e('Save SEO', 'slotera-booking'); ?></button></p>
        </form>
    </details>
    <?php
};


$sltr_render_local_seo_form = static function (array $relation): void {
    $package_id = (int) ($relation['package_id'] ?? 0);
    $location_id = (int) ($relation['location_id'] ?? 0);
    $focus_id = 'local-package-' . $package_id;
    $faq = json_decode((string) ($relation['faq_override_json'] ?? ''), true);
    if (!is_array($faq) || empty($faq)) {
        $faq = [['question' => '', 'answer' => '']];
    }
    ?>
    <details id="<?php echo esc_attr('local-package-' . $package_id . '-location-' . $location_id); ?>" class="sltr-seo-item-editor" <?php echo isset($_GET['sltr_focus']) && sanitize_text_field((string) wp_unslash($_GET['sltr_focus'])) === $focus_id ? 'open' : ''; ?>>
        <summary><strong><?php echo esc_html((string) ($relation['package_title'] ?? __('Package', 'slotera-booking'))); ?></strong> <span class="description">→ <?php echo esc_html((string) ($relation['location_name'] ?? __('Location', 'slotera-booking'))); ?></span></summary>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
            <input type="hidden" name="action" value="sltr_save_slotera_local_seo">
            <input type="hidden" name="package_id" value="<?php echo esc_attr((string) $package_id); ?>">
            <input type="hidden" name="location_id" value="<?php echo esc_attr((string) $location_id); ?>">
            <?php wp_nonce_field('sltr_save_slotera_local_seo'); ?>
            <table class="form-table" role="presentation"><tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Local intro override', 'slotera-booking'); ?></th>
                    <td>
                        <textarea class="large-text" rows="4" name="local_intro_override"><?php echo esc_textarea((string) ($relation['intro_override'] ?? '')); ?></textarea>
                        <p class="description"><?php esc_html_e('Optional. Leave empty to use the general location intro and FAQ.', 'slotera-booking'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Local FAQ override', 'slotera-booking'); ?></th>
                    <td>
                        <?php foreach ($faq as $index => $item) : ?>
                            <div style="margin:8px 0;padding:10px;border-left:3px solid #dcdcde;background:#f6f7f7;">
                                <input class="large-text" name="local_faq_override[<?php echo esc_attr((string) $index); ?>][question]" placeholder="<?php esc_attr_e('Question', 'slotera-booking'); ?>" value="<?php echo esc_attr((string) ($item['question'] ?? '')); ?>"><br><br>
                                <textarea class="large-text" rows="3" name="local_faq_override[<?php echo esc_attr((string) $index); ?>][answer]" placeholder="<?php esc_attr_e('Answer', 'slotera-booking'); ?>"><?php echo esc_textarea((string) ($item['answer'] ?? '')); ?></textarea>
                            </div>
                        <?php endforeach; ?>
                        <div style="margin:8px 0;padding:10px;border-left:3px solid #dcdcde;background:#f6f7f7;">
                            <input class="large-text" name="local_faq_override[new][question]" placeholder="<?php esc_attr_e('New question', 'slotera-booking'); ?>"><br><br>
                            <textarea class="large-text" rows="3" name="local_faq_override[new][answer]" placeholder="<?php esc_attr_e('New answer', 'slotera-booking'); ?>"></textarea>
                        </div>
                    </td>
                </tr>
            </tbody></table>
            <p><button class="button button-primary"><?php esc_html_e('Save local SEO', 'slotera-booking'); ?></button></p>
        </form>
    </details>
    <?php
};
