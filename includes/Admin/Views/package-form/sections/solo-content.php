<?php if (!defined('ABSPATH')) { exit; } ?>
<tr class="sltr-solo-page-setting"><th scope="row"><label for="sltr-package-solo-layout"><?php sltr_esc_html_e('admin.package.solo_page_layout'); ?></label></th><td>
<select id="sltr-package-solo-layout" name="solo_layout"><option value="classic" <?php selected((string) ($package['solo_layout'] ?? 'classic'),'classic'); ?>><?php esc_html_e('Classic: left card + right content','slotera-booking'); ?></option><option value="stacked" <?php selected((string) ($package['solo_layout'] ?? 'classic'),'stacked'); ?>><?php esc_html_e('Stacked: card above content','slotera-booking'); ?></option></select></td></tr>
<tr class="sltr-solo-page-setting"><th scope="row"><label for="sltr-package-description"><?php sltr_esc_html_e('admin.package.description'); ?></label></th><td><textarea id="sltr-package-description" name="description" rows="4" class="large-text"><?php echo esc_textarea($package['description'] ?? ''); ?></textarea></td></tr>
            <tr class="sltr-solo-page-setting">
                <th scope="row"><?php esc_html_e('Description typography', 'slotera-booking'); ?></th>
                <td>
                    <label for="sltr-package-description-font-family"><?php esc_html_e('Font family', 'slotera-booking'); ?></label><br>
                    <?php $sltr_font_value = (string) ($package['description_font_family'] ?? ''); ?>
                    <select id="sltr-package-description-font-family" name="description_font_family">
                        <option value="" <?php selected($sltr_font_value, ''); ?>><?php esc_html_e('Inherit site font', 'slotera-booking'); ?></option>
                        <option value="system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" <?php selected($sltr_font_value, "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"); ?>>System UI</option>
                        <option value="Arial, sans-serif" <?php selected($sltr_font_value, 'Arial, sans-serif'); ?>>Arial</option>
                        <option value="Helvetica, Arial, sans-serif" <?php selected($sltr_font_value, 'Helvetica, Arial, sans-serif'); ?>>Helvetica</option>
                        <option value="Verdana, Geneva, sans-serif" <?php selected($sltr_font_value, 'Verdana, Geneva, sans-serif'); ?>>Verdana</option>
                        <option value="Tahoma, Geneva, sans-serif" <?php selected($sltr_font_value, 'Tahoma, Geneva, sans-serif'); ?>>Tahoma</option>
                        <option value="'Trebuchet MS', Arial, sans-serif" <?php selected($sltr_font_value, "'Trebuchet MS', Arial, sans-serif"); ?>>Trebuchet MS</option>
                        <option value="Georgia, serif" <?php selected($sltr_font_value, 'Georgia, serif'); ?>>Georgia</option>
                        <option value="'Times New Roman', Times, serif" <?php selected($sltr_font_value, "'Times New Roman', Times, serif"); ?>>Times New Roman</option>
                        <option value="'Courier New', Courier, monospace" <?php selected($sltr_font_value, "'Courier New', Courier, monospace"); ?>>Courier New</option>
                        <option value="Inter, Arial, sans-serif" <?php selected($sltr_font_value, 'Inter, Arial, sans-serif'); ?>>Inter</option>
                    </select><br><br>
                    <label for="sltr-package-description-font-size"><?php esc_html_e('Font size', 'slotera-booking'); ?></label><br>
                    <?php
                    $sltr_description_font_size = isset($package['description_font_size']) ? (int) $package['description_font_size'] : 18;
                    if ($sltr_description_font_size < 12 || $sltr_description_font_size > 48) {
                        $sltr_description_font_size = 18;
                    }
                    ?>
                    <input id="sltr-package-description-font-size" type="number" min="12" max="48" step="1" name="description_font_size" value="<?php echo esc_attr((string) $sltr_description_font_size); ?>"> px
                    <p class="description"><?php esc_html_e('Applies only to the solo package page description. Leave font family empty to inherit the site font.', 'slotera-booking'); ?></p>
                </td>
            </tr>
<?php
$solo_context_help = __('Each Solo module is activated once per package. Edit its content later without changing the shortcode.', 'slotera-booking');
$sltr_media_fit_mode = (string) ($package['media_fit_mode'] ?? ((new \Slotera\Infrastructure\Repositories\SettingsRepository())->all()['media_fit_mode'] ?? 'cover'));
$sltr_media_focus_disabled = $sltr_media_fit_mode === 'contain' ? '1' : '0';

$sltr_text_active = strpos((string) ($package['solo_top_content'] ?? ''), '[slotera_package_text_block') !== false;
$sltr_media_shortcode = '[slotera_package_media id="media"]';
$sltr_media_json = (string) ($package['solo_media_json'] ?? '{}');
$sltr_media_all = json_decode($sltr_media_json, true);
$sltr_media_all = is_array($sltr_media_all) ? $sltr_media_all : [];
$sltr_media = is_array($sltr_media_all['media'] ?? null) ? $sltr_media_all['media'] : [];
$sltr_media_type = (string) ($sltr_media['type'] ?? 'images');
$sltr_media_video_id = (int) ($sltr_media['video_id'] ?? 0);
$sltr_media_video_mime = $sltr_media_video_id > 0 ? strtolower((string) get_post_mime_type($sltr_media_video_id)) : '';
if ($sltr_media_video_id > 0 && !in_array($sltr_media_video_mime, ['video/mp4', 'video/webm', 'video/ogg'], true)) {
    $sltr_media_video_id = 0;
}
$sltr_media_video_autoplay = $sltr_media_video_id > 0 && !empty($sltr_media['autoplay']);
$sltr_media_active = strpos((string) ($package['solo_content'] ?? ''), '[slotera_package_media') !== false;

$sltr_contact_active = strpos((string) ($package['solo_down_content'] ?? ''), '[slotera_contact') !== false;
$sltr_contact_details_json = (string) ($package['solo_contact_details_json'] ?? '[]');
$sltr_contact_rows = json_decode($sltr_contact_details_json, true);
$sltr_contact_rows = is_array($sltr_contact_rows) ? $sltr_contact_rows : [];
$sltr_contact_address = '';
$sltr_contact_details = [];
$sltr_contact_socials = [];
foreach ($sltr_contact_rows as $sltr_contact_row) {
    if (!is_array($sltr_contact_row)) { continue; }
    $sltr_contact_type = (string) ($sltr_contact_row['type'] ?? 'contact');
    if ($sltr_contact_type === 'address') {
        $sltr_contact_address = (string) ($sltr_contact_row['value'] ?? '');
    } elseif ($sltr_contact_type === 'social') {
        $sltr_contact_socials[] = $sltr_contact_row;
    } else {
        $sltr_contact_details[] = $sltr_contact_row;
    }
}
$sltr_social_platforms = [
    'instagram' => 'Instagram',
    'facebook' => 'Facebook',
    'linkedin' => 'LinkedIn',
    'x' => 'X (Twitter)',
    'youtube' => 'YouTube',
    'tiktok' => 'TikTok',
];
?>
            <tr class="sltr-solo-page-setting">
                <th scope="row"><label for="sltr-package-solo-top-content"><?php esc_html_e('Solo page top title/text block', 'slotera-booking'); ?></label></th>
                <td>
                    <div class="sltr-right-content-tools">
                        <button type="button" class="button sltr-activate-text-block" data-target="#sltr-package-solo-top-content"><?php esc_html_e('Insert title/text block', 'slotera-booking'); ?></button>
                    </div>
                    <input type="hidden" id="sltr-package-solo-top-content" name="solo_top_content" value="<?php echo esc_attr($sltr_text_active ? '[slotera_package_text_block]' : ''); ?>">
                    <input type="hidden" id="sltr-package-solo-top-active" name="solo_top_text_active" value="<?php echo $sltr_text_active ? '1' : '0'; ?>">
                    <p id="sltr-package-solo-top-status" class="description"><?php echo esc_html($sltr_text_active ? __('Title/text block is active for this package.', 'slotera-booking') : $solo_context_help); ?></p>
                </td>
            </tr>
            <tr class="sltr-solo-page-setting">
                <th scope="row"><?php esc_html_e('Solo page top title/text block', 'slotera-booking'); ?></th>
                <td>
                    <label for="sltr-package-right-block-title"><?php sltr_esc_html_e('admin.package.title'); ?></label><br>
                    <input id="sltr-package-right-block-title" class="regular-text" name="right_block_title" value="<?php echo esc_attr($package['right_block_title'] ?? ''); ?>"><br><br>
                    <label for="sltr-package-right-block-text"><?php esc_html_e('Text', 'slotera-booking'); ?></label><br>
                    <textarea id="sltr-package-right-block-text" name="right_block_text" rows="5" class="large-text"><?php echo esc_textarea($package['right_block_text'] ?? ''); ?></textarea><br><br>
                    <h4 style="margin:16px 0 8px;"><?php esc_html_e('Title typography', 'slotera-booking'); ?></h4>
                    <label for="sltr-package-right-block-title-font-family"><?php esc_html_e('Title font family', 'slotera-booking'); ?></label><br>
                    <?php $sltr_font_value = (string) ($package['right_block_title_font_family'] ?? ($package['right_block_font_family'] ?? '')); ?>
                    <select id="sltr-package-right-block-title-font-family" name="right_block_title_font_family">
                        <option value="" <?php selected($sltr_font_value, ''); ?>><?php esc_html_e('Inherit site font', 'slotera-booking'); ?></option>
                        <option value="system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" <?php selected($sltr_font_value, "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"); ?>>System UI</option>
                        <option value="Arial, sans-serif" <?php selected($sltr_font_value, 'Arial, sans-serif'); ?>>Arial</option>
                        <option value="Helvetica, Arial, sans-serif" <?php selected($sltr_font_value, 'Helvetica, Arial, sans-serif'); ?>>Helvetica</option>
                        <option value="Verdana, Geneva, sans-serif" <?php selected($sltr_font_value, 'Verdana, Geneva, sans-serif'); ?>>Verdana</option>
                        <option value="Tahoma, Geneva, sans-serif" <?php selected($sltr_font_value, 'Tahoma, Geneva, sans-serif'); ?>>Tahoma</option>
                        <option value="'Trebuchet MS', Arial, sans-serif" <?php selected($sltr_font_value, "'Trebuchet MS', Arial, sans-serif"); ?>>Trebuchet MS</option>
                        <option value="Georgia, serif" <?php selected($sltr_font_value, 'Georgia, serif'); ?>>Georgia</option>
                        <option value="'Times New Roman', Times, serif" <?php selected($sltr_font_value, "'Times New Roman', Times, serif"); ?>>Times New Roman</option>
                        <option value="'Courier New', Courier, monospace" <?php selected($sltr_font_value, "'Courier New', Courier, monospace"); ?>>Courier New</option>
                        <option value="Inter, Arial, sans-serif" <?php selected($sltr_font_value, 'Inter, Arial, sans-serif'); ?>>Inter</option>
                    </select><br><br>
                    <label for="sltr-package-right-block-title-font-size"><?php esc_html_e('Title font size', 'slotera-booking'); ?></label><br>
                    <input id="sltr-package-right-block-title-font-size" type="number" min="12" max="48" step="1" name="right_block_title_font_size" value="<?php echo esc_attr((string) ($package['right_block_title_font_size'] ?? 32)); ?>"> px

                    <h4 style="margin:16px 0 8px;"><?php esc_html_e('Text typography', 'slotera-booking'); ?></h4>
                    <label for="sltr-package-right-block-text-font-family"><?php esc_html_e('Text font family', 'slotera-booking'); ?></label><br>
                    <?php $sltr_font_value = (string) ($package['right_block_text_font_family'] ?? ($package['right_block_font_family'] ?? 'Inter, Arial, sans-serif')); ?>
                    <select id="sltr-package-right-block-text-font-family" name="right_block_text_font_family">
                        <option value="" <?php selected($sltr_font_value, ''); ?>><?php esc_html_e('Inherit site font', 'slotera-booking'); ?></option>
                        <option value="system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" <?php selected($sltr_font_value, "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"); ?>>System UI</option>
                        <option value="Arial, sans-serif" <?php selected($sltr_font_value, 'Arial, sans-serif'); ?>>Arial</option>
                        <option value="Helvetica, Arial, sans-serif" <?php selected($sltr_font_value, 'Helvetica, Arial, sans-serif'); ?>>Helvetica</option>
                        <option value="Verdana, Geneva, sans-serif" <?php selected($sltr_font_value, 'Verdana, Geneva, sans-serif'); ?>>Verdana</option>
                        <option value="Tahoma, Geneva, sans-serif" <?php selected($sltr_font_value, 'Tahoma, Geneva, sans-serif'); ?>>Tahoma</option>
                        <option value="'Trebuchet MS', Arial, sans-serif" <?php selected($sltr_font_value, "'Trebuchet MS', Arial, sans-serif"); ?>>Trebuchet MS</option>
                        <option value="Georgia, serif" <?php selected($sltr_font_value, 'Georgia, serif'); ?>>Georgia</option>
                        <option value="'Times New Roman', Times, serif" <?php selected($sltr_font_value, "'Times New Roman', Times, serif"); ?>>Times New Roman</option>
                        <option value="'Courier New', Courier, monospace" <?php selected($sltr_font_value, "'Courier New', Courier, monospace"); ?>>Courier New</option>
                        <option value="Inter, Arial, sans-serif" <?php selected($sltr_font_value, 'Inter, Arial, sans-serif'); ?>>Inter</option>
                    </select><br><br>
                    <label for="sltr-package-right-block-text-font-size"><?php esc_html_e('Text font size', 'slotera-booking'); ?></label><br>
                    <input id="sltr-package-right-block-text-font-size" type="number" min="12" max="48" step="1" name="right_block_text_font_size" value="<?php echo esc_attr((string) ($package['right_block_text_font_size'] ?? ($package['right_block_font_size'] ?? 24))); ?>"> px
                    <p class="description"><?php esc_html_e('Shortcode: [slotera_package_text_block]. Use this when the right 2/3 column needs an editable heading and text block.', 'slotera-booking'); ?></p>
                </td>
            </tr>

            <tr class="sltr-solo-page-setting">
                <th scope="row"><label for="sltr-package-solo-content"><?php esc_html_e('Solo page right content', 'slotera-booking'); ?></label></th>
                <td>
                    <div class="sltr-right-content-tools">
                        <button type="button" id="sltr-activate-package-images" class="button sltr-activate-package-media" data-media-type="images" data-target="#sltr-package-solo-content"><?php esc_html_e('Insert image/slider', 'slotera-booking'); ?></button>
                        <button type="button" id="sltr-activate-package-video" class="button sltr-activate-package-video" data-target="#sltr-package-solo-content"><?php esc_html_e('Insert video', 'slotera-booking'); ?></button>
                    </div>
                    <input type="hidden" id="sltr-package-solo-content" name="solo_content" value="<?php echo esc_attr((string) ($package['solo_content'] ?? '')); ?>">
                    <input type="hidden" id="sltr-package-solo-media-json" name="solo_media_json" value="<?php echo esc_attr($sltr_media_json); ?>">
                    <div id="sltr-package-media-settings" class="<?php echo $sltr_media_active ? '' : 'sltr-admin-hidden'; ?>">
                        <p><strong><?php esc_html_e('Media for shortcode:', 'slotera-booking'); ?></strong> <code><?php echo esc_html($sltr_media_shortcode); ?></code></p>
                        <input type="hidden" id="sltr-package-media-type" value="<?php echo esc_attr(in_array($sltr_media_type, ['images', 'video'], true) ? $sltr_media_type : 'images'); ?>">
                        <div id="sltr-package-image-settings" class="<?php echo $sltr_media_type === 'video' ? 'sltr-admin-hidden' : ''; ?>">
                        <input type="hidden" class="sltr-media-ids" id="sltr-package-media-ids" value="<?php echo esc_attr((string) ($sltr_media['ids'] ?? '')); ?>" data-max="20">
                        <button type="button" class="button sltr-media-select" data-target="#sltr-package-media-ids" data-multiple="1" data-max="20"><?php esc_html_e('Select images', 'slotera-booking'); ?></button>
                        <button type="button" class="button sltr-media-clear" data-target="#sltr-package-media-ids"><?php esc_html_e('Clear', 'slotera-booking'); ?></button>
                        <p id="sltr-package-media-validation" class="description sltr-media-validation-message" aria-live="polite"></p>
                        <input type="hidden" id="sltr-package-media-focus" value="<?php echo esc_attr((string) ($sltr_media['focus'] ?? '{}')); ?>">
                        <div class="sltr-media-preview sltr-media-preview-large sltr-focus-enabled" data-source="#sltr-package-media-ids" data-focus-source="#sltr-package-media-focus" data-focus-multiple="1" data-focus-ratio="16 / 9" data-focus-disabled="<?php echo esc_attr($sltr_media_focus_disabled); ?>"></div>
                        <label><?php esc_html_e('Autoplay speed', 'slotera-booking'); ?> <input id="sltr-package-media-speed" type="number" min="1000" max="30000" step="500" value="<?php echo esc_attr((string) ($sltr_media['speed'] ?? 4000)); ?>"> ms</label>
                        <p><label for="sltr-package-media-fit-mode"><strong><?php esc_html_e('Slider display', 'slotera-booking'); ?></strong></label><br>
                            <select id="sltr-package-media-fit-mode" name="media_fit_mode">
                                <option value="cover" <?php selected($sltr_media_fit_mode, 'cover'); ?>><?php esc_html_e('Cover: fill the block and crop edges if needed', 'slotera-booking'); ?></option>
                                <option value="contain" <?php selected($sltr_media_fit_mode, 'contain'); ?>><?php esc_html_e('Contain: show the full image with possible empty space', 'slotera-booking'); ?></option>
                            </select>
                        </p>
                        <p class="description"><?php esc_html_e('One image is displayed as a normal image. The slider is used only when two or more images are selected. You can replace the images at any time; the shortcode does not change.', 'slotera-booking'); ?></p>
                        </div>
                        <div id="sltr-package-video-settings" class="<?php echo $sltr_media_type === 'video' ? '' : 'sltr-admin-hidden'; ?>">
                            <input type="hidden" id="sltr-package-media-video-id" value="<?php echo esc_attr((string) $sltr_media_video_id); ?>">
                            <button type="button" class="button sltr-video-select" data-target="#sltr-package-media-video-id"><?php esc_html_e('Select video', 'slotera-booking'); ?></button>
                            <button type="button" class="button sltr-video-clear" data-target="#sltr-package-media-video-id"><?php esc_html_e('Clear', 'slotera-booking'); ?></button>
                            <div id="sltr-package-video-preview" class="sltr-package-video-admin-preview" data-source="#sltr-package-media-video-id"></div>
                            <label><input type="checkbox" id="sltr-package-media-video-autoplay" value="1" <?php checked($sltr_media_video_autoplay); ?>> <?php esc_html_e('Autoplay', 'slotera-booking'); ?></label>
                            <p class="description"><?php esc_html_e('Supported video formats: MP4, WebM and Ogg. For the widest browser compatibility, use MP4 encoded with H.264 video and AAC audio. QuickTime/MOV files are not supported. Image/slider and video are mutually exclusive; clear the current media selection before choosing the other type.', 'slotera-booking'); ?></p>
                        </div>
                    </div>
                    <!-- Legacy media values stay stored for backward compatibility. -->
                    <input type="hidden" name="slider_image_ids" value="<?php echo esc_attr((string) ($package['slider_image_ids'] ?? '')); ?>">
                    <input type="hidden" name="slider_image_focus_json" value="<?php echo esc_attr((string) ($package['slider_image_focus_json'] ?? '{}')); ?>">
                    <input type="hidden" name="slider_speed" value="<?php echo esc_attr((string) ($package['slider_speed'] ?? 4000)); ?>">
                    <input type="hidden" name="gallery_image_ids" value="<?php echo esc_attr((string) ($package['gallery_image_ids'] ?? '')); ?>">
                    <input type="hidden" name="gallery_image_focus" value="<?php echo esc_attr((string) ($package['gallery_image_focus'] ?? '50,50')); ?>">
                </td>
            </tr>

            <tr class="sltr-solo-page-setting">
                <th scope="row"><label for="sltr-package-solo-down-content"><?php esc_html_e('Solo page down contact block', 'slotera-booking'); ?></label></th>
                <td>
                    <div class="sltr-right-content-tools">
                        <button type="button" class="button sltr-activate-contact-block" data-target="#sltr-package-solo-down-content"><?php esc_html_e('Insert [slotera_contact]', 'slotera-booking'); ?></button>
                    </div>
                    <input type="hidden" id="sltr-package-solo-down-content" name="solo_down_content" value="<?php echo esc_attr((string) ($package['solo_down_content'] ?? '')); ?>">
                    <div id="sltr-contact-block-settings" class="<?php echo $sltr_contact_active ? '' : 'sltr-admin-hidden'; ?>">
                        <h4><?php esc_html_e('Contact block image', 'slotera-booking'); ?></h4>
                        <input type="hidden" id="sltr-contact-image-id" name="solo_contact_image_id" value="<?php echo esc_attr((string) ($package['solo_contact_image_id'] ?? 0)); ?>">
                        <?php
                        $sltr_contact_custom_image_id = (int) ($package['solo_contact_image_id'] ?? 0);
                        $sltr_contact_preview_url = $sltr_contact_custom_image_id > 0 ? wp_get_attachment_image_url($sltr_contact_custom_image_id, 'large') : '';
                        if (!$sltr_contact_preview_url) {
                            $sltr_contact_preview_url = SLTR_PLUGIN_URL . 'assets/images/contact-block-default.webp';
                        }
                        ?>
                        <div class="sltr-contact-image-preview sltr-media-preview-large sltr-focus-enabled">
                            <div class="sltr-contact-image-preview-frame">
                                <img id="sltr-contact-image-preview-img" src="<?php echo esc_url($sltr_contact_preview_url); ?>" alt="">
                            </div>
                        </div>
                        <p class="sltr-contact-image-actions">
                            <button type="button" class="button sltr-replace-contact-image"><?php esc_html_e('Replace image', 'slotera-booking'); ?></button>
                            <button type="button" class="button sltr-use-default-contact-image" data-default-url="<?php echo esc_url(SLTR_PLUGIN_URL . 'assets/images/contact-block-default.webp'); ?>"><?php esc_html_e('Use default', 'slotera-booking'); ?></button>
                        </p>
                        <p class="description"><?php esc_html_e('The neutral office image is used by default. Replace it at any time or restore the default image; the contact shortcode does not change.', 'slotera-booking'); ?></p>

                        <h4><?php esc_html_e('Address', 'slotera-booking'); ?></h4>
                        <input type="text" id="sltr-contact-address" class="large-text" value="<?php echo esc_attr($sltr_contact_address); ?>" placeholder="<?php esc_attr_e('Street, city, postal code, country', 'slotera-booking'); ?>">

                        <h4><?php esc_html_e('Google Maps link', 'slotera-booking'); ?></h4>
                        <input type="url" class="large-text code" name="solo_contact_map" value="<?php echo esc_attr((string) ($package['solo_contact_map'] ?? '')); ?>" placeholder="https://maps.app.goo.gl/............">
                        <p class="description">
                            <?php esc_html_e('Paste the normal Google Maps page/share link. Slotera does not embed Google Maps; visitors open this link in a new window.', 'slotera-booking'); ?>
                            <a href="https://www.google.com/maps" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open Google Maps', 'slotera-booking'); ?></a>
                        </p>

                        <h4><?php esc_html_e('Contact details', 'slotera-booking'); ?></h4>
                        <input type="hidden" id="sltr-contact-details-json" name="solo_contact_details_json" value="<?php echo esc_attr($sltr_contact_details_json); ?>">
                        <div id="sltr-contact-details-rows">
                            <?php foreach ($sltr_contact_details as $row) : if (!is_array($row)) continue; ?>
                                <div class="sltr-contact-detail-row">
                                    <input type="text" class="regular-text sltr-contact-detail-label" value="<?php echo esc_attr((string) ($row['label'] ?? '')); ?>" placeholder="<?php esc_attr_e('Mobile, Office, Manager…', 'slotera-booking'); ?>">
                                    <input type="text" class="regular-text sltr-contact-detail-value" value="<?php echo esc_attr((string) ($row['value'] ?? '')); ?>" placeholder="<?php esc_attr_e('Phone number or contact detail', 'slotera-booking'); ?>">
                                    <button type="button" class="button-link-delete sltr-remove-contact-detail"><?php esc_html_e('Remove', 'slotera-booking'); ?></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button" id="sltr-add-contact-detail"><?php esc_html_e('Add contact field', 'slotera-booking'); ?></button>

                        <h4><?php esc_html_e('Social links', 'slotera-booking'); ?></h4>
                        <div id="sltr-contact-social-rows">
                            <?php foreach ($sltr_contact_socials as $row) : ?>
                                <?php $sltr_social_platform = sanitize_key((string) ($row['platform'] ?? 'instagram')); ?>
                                <div class="sltr-contact-social-row">
                                    <select class="sltr-contact-social-platform">
                                        <?php foreach ($sltr_social_platforms as $sltr_social_key => $sltr_social_label) : ?>
                                            <option value="<?php echo esc_attr($sltr_social_key); ?>" <?php selected($sltr_social_platform, $sltr_social_key); ?>><?php echo esc_html($sltr_social_label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="url" class="regular-text sltr-contact-social-url" value="<?php echo esc_attr((string) ($row['url'] ?? '')); ?>" placeholder="https://">
                                    <button type="button" class="button-link-delete sltr-remove-contact-social"><?php esc_html_e('Remove', 'slotera-booking'); ?></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button" id="sltr-add-contact-social"><?php esc_html_e('Add social link', 'slotera-booking'); ?></button>
                    </div>
                </td>
            </tr>

