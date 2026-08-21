<?php if (!defined('ABSPATH')) { exit; } ?>
            <tr>
                <th scope="row"><label for="sltr-package-title"><?php sltr_esc_html_e('admin.package.title'); ?></label></th>
                <td><input id="sltr-package-title" class="regular-text sltr-seo-preview-source" required name="title" value="<?php echo esc_attr($package['title'] ?? ''); ?>"></td>
            </tr>

            <tr>
                <th scope="row"><label for="sltr-package-slug"><?php sltr_esc_html_e('admin.package.slug'); ?></label></th>
                <td>
                    <?php $sltr_slug_locked = !empty($package['id']) && trim((string) ($package['slug'] ?? '')) !== ''; ?>
                    <input id="sltr-package-slug" class="regular-text sltr-slug-field" name="slug" value="<?php echo esc_attr($package['slug'] ?? ''); ?>" placeholder="<?php sltr_esc_attr_e('admin.package.auto_generated_from_title'); ?>" data-sltr-slug-source="#sltr-package-title" <?php echo $sltr_slug_locked ? 'readonly aria-readonly="true"' : ''; ?>>
                    <?php if ($sltr_slug_locked) : ?>
                        <button type="button" class="button" disabled><?php esc_html_e('Generated', 'slotera-booking'); ?></button>
                    <?php else : ?>
                        <button type="button" class="button sltr-generate-slug" data-sltr-no-processing="1" data-target="#sltr-package-slug" data-source="#sltr-package-title"><?php esc_html_e('Generate slug', 'slotera-booking'); ?></button>
                    <?php endif; ?>
                    <p class="description"><?php esc_html_e('The slug must be unique. After the first save it is locked and cannot be changed.', 'slotera-booking'); ?></p>
                    <p class="description"><strong><?php esc_html_e('Slug preview:', 'slotera-booking'); ?></strong> <code class="sltr-slug-preview" data-source="#sltr-package-slug"></code></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="sltr-package-title-font-family"><?php esc_html_e('Title font family', 'slotera-booking'); ?></label></th>
                <td>
                    <?php $sltr_title_font_family = (string) ($package['title_font_family'] ?? ''); ?>
                    <select id="sltr-package-title-font-family" name="title_font_family">
                        <option value="" <?php selected($sltr_title_font_family, ''); ?>><?php esc_html_e('Inherit site font', 'slotera-booking'); ?></option>
                        <option value="system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" <?php selected($sltr_title_font_family, "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"); ?>>System UI</option>
                        <option value="Arial, sans-serif" <?php selected($sltr_title_font_family, 'Arial, sans-serif'); ?>>Arial</option>
                        <option value="Helvetica, Arial, sans-serif" <?php selected($sltr_title_font_family, 'Helvetica, Arial, sans-serif'); ?>>Helvetica</option>
                        <option value="Verdana, Geneva, sans-serif" <?php selected($sltr_title_font_family, 'Verdana, Geneva, sans-serif'); ?>>Verdana</option>
                        <option value="Tahoma, Geneva, sans-serif" <?php selected($sltr_title_font_family, 'Tahoma, Geneva, sans-serif'); ?>>Tahoma</option>
                        <option value="'Trebuchet MS', Arial, sans-serif" <?php selected($sltr_title_font_family, "'Trebuchet MS', Arial, sans-serif"); ?>>Trebuchet MS</option>
                        <option value="Georgia, serif" <?php selected($sltr_title_font_family, 'Georgia, serif'); ?>>Georgia</option>
                        <option value="'Times New Roman', Times, serif" <?php selected($sltr_title_font_family, "'Times New Roman', Times, serif"); ?>>Times New Roman</option>
                        <option value="'Courier New', Courier, monospace" <?php selected($sltr_title_font_family, "'Courier New', Courier, monospace"); ?>>Courier New</option>
                        <option value="Inter, Arial, sans-serif" <?php selected($sltr_title_font_family, 'Inter, Arial, sans-serif'); ?>>Inter</option>
                    </select>
                    <p class="description"><?php esc_html_e('Choose the font used for this package title. Inherit site font keeps the active WordPress theme typography.', 'slotera-booking'); ?></p>
                </td>
            </tr>

            <?php
            $sltr_title_font_size = isset($package['title_font_size']) ? (int) $package['title_font_size'] : 24;
            if ($sltr_title_font_size < 12 || $sltr_title_font_size > 48) {
                $sltr_title_font_size = 24;
            }
            ?>
            <tr>
                <th scope="row"><label for="sltr-package-title-font-size"><?php esc_html_e('Title font size', 'slotera-booking'); ?></label></th>
                <td>
                    <input id="sltr-package-title-font-size" type="number" min="12" max="48" step="1" name="title_font_size" value="<?php echo esc_attr((string) $sltr_title_font_size); ?>"> px
                    <p class="description"><?php esc_html_e('Allowed range: 12–48 px. Leave empty to keep the current theme/card default.', 'slotera-booking'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('More info link', 'slotera-booking'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="show_more_info" value="1" <?php checked((int) ($package['show_more_info'] ?? 1), 1); ?>>
                        <?php esc_html_e('Show "More info" link on package cards', 'slotera-booking'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('When enabled, the package card shows a More info link to the solo package page above the Select button.', 'slotera-booking'); ?></p>
                </td>
            </tr>

            

            <tr>
                <th scope="row"><label for="sltr-package-category"><?php esc_html_e('Category', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="sltr-package-category" name="category_id">
                        <option value="0"><?php esc_html_e('No category', 'slotera-booking'); ?></option>
                        <?php foreach ((array) $categories as $c) : ?>
                            <option value="<?php echo esc_attr((string) $c['id']); ?>" <?php selected((int) ($package['category_id'] ?? 0), (int) $c['id']); ?>>
                                <?php echo esc_html($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-categories&action=new')); ?>" style="margin-left:10px;"><?php esc_html_e('Create category', 'slotera-booking'); ?></a>
                </td>
            </tr>

            <?php $sltr_selected_location_id = (int) (((array) ($selected_location_ids ?? []))[0] ?? 0); ?>
            <tr>
                <th scope="row"><label for="sltr-package-location"><?php esc_html_e('Location', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="sltr-package-location" name="location_ids[]">
                        <option value="0"><?php esc_html_e('No location', 'slotera-booking'); ?></option>
                        <?php foreach ((array) $locations as $location) : $sltr_location_id = (int) ($location['id'] ?? 0); ?>
                            <option value="<?php echo esc_attr((string) $sltr_location_id); ?>" <?php selected($sltr_selected_location_id, $sltr_location_id); ?>><?php echo esc_html((string) ($location['name'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-locations&action=new')); ?>" style="margin-left:10px;"><?php esc_html_e('Create location', 'slotera-booking'); ?></a>
                </td>
            </tr>

            



<tr>
                <th scope="row"><?php esc_html_e('Package page image', 'slotera-booking'); ?></th>
                <td>
                    <input type="hidden" class="sltr-media-ids" id="sltr-package-card-image-id" name="card_image_id" value="<?php echo esc_attr((string) ($package['card_image_id'] ?? '')); ?>" data-max="1">
                    <button type="button" class="button sltr-media-select" data-target="#sltr-package-card-image-id" data-multiple="0" data-max="1"><?php esc_html_e('Select package page image', 'slotera-booking'); ?></button>
                    <button type="button" class="button sltr-media-clear" data-target="#sltr-package-card-image-id"><?php esc_html_e('Clear', 'slotera-booking'); ?></button>
                    <input type="hidden" id="sltr-package-card-image-focus" name="card_image_focus" value="<?php echo esc_attr((string) ($package['card_image_focus'] ?? '50,50')); ?>">
                    <div class="sltr-media-preview sltr-media-preview-large sltr-focus-enabled" data-source="#sltr-package-card-image-id" data-focus-source="#sltr-package-card-image-focus" data-focus-ratio="16 / 9"></div>
                    <p class="description"><?php esc_html_e('Displayed in the package selection on the Slotera Booking page. If empty, no image is shown.', 'slotera-booking'); ?></p>
                    <p class="description"><strong><?php esc_html_e('Focus Point:', 'slotera-booking'); ?></strong> <?php esc_html_e('Click the important area of the image. The marker and preview update immediately.', 'slotera-booking'); ?></p>
                </td>
            </tr>
<tr>
                <th scope="row"><?php esc_html_e('Slotera Booking page image', 'slotera-booking'); ?></th>
                <td>
                    <input type="hidden" class="sltr-media-ids" id="sltr-booking-card-image-id" name="booking_card_image_id" value="<?php echo esc_attr((string) ($package['booking_card_image_id'] ?? '')); ?>" data-max="1">
                    <button type="button" class="button sltr-media-select" data-target="#sltr-booking-card-image-id" data-multiple="0" data-max="1"><?php esc_html_e('Select booking page image', 'slotera-booking'); ?></button>
                    <button type="button" class="button sltr-media-clear" data-target="#sltr-booking-card-image-id"><?php esc_html_e('Clear', 'slotera-booking'); ?></button>
                    <input type="hidden" id="sltr-booking-card-image-focus" name="booking_card_image_focus" value="<?php echo esc_attr((string) ($package['booking_card_image_focus'] ?? '50,50')); ?>">
                    <div class="sltr-media-preview sltr-media-preview-large sltr-focus-enabled" data-source="#sltr-booking-card-image-id" data-focus-source="#sltr-booking-card-image-focus" data-focus-ratio="16 / 9"></div>
                    <p class="description"><?php esc_html_e('Displayed only in the package card on the Slotera Booking page. If empty, no image is shown.', 'slotera-booking'); ?></p>
                    <p class="description"><strong><?php esc_html_e('Focus Point:', 'slotera-booking'); ?></strong> <?php esc_html_e('Click the important area of the image. The marker and preview update immediately.', 'slotera-booking'); ?></p>
                </td>
            </tr>
<tr>
                <th scope="row"><?php esc_html_e('Featured package icon', 'slotera-booking'); ?></th>
                <td>
                    <?php $popular_icon = (string) ($package['popular_icon'] ?? (!empty($package['is_popular']) ? 'star' : '')); ?>
                    <fieldset class="sltr-popular-icon-options">
                        <?php foreach (['' => '—', 'star' => '★', 'fire' => '🔥', 'crown' => '♛', 'heart' => '♥', 'bolt' => '⚡'] as $icon_key => $icon_glyph) : ?>
                            <label style="display:inline-flex;align-items:center;justify-content:center;min-width:42px;height:36px;margin:0 6px 6px 0;border:1px solid #c3c4c7;border-radius:8px;background:#fff;cursor:pointer;">
                                <input type="radio" name="popular_icon" value="<?php echo esc_attr($icon_key); ?>" <?php checked($popular_icon, $icon_key); ?> style="margin-right:5px;">
                                <span aria-hidden="true" style="font-size:18px;line-height:1;"><?php echo esc_html($icon_glyph); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <p class="description"><?php esc_html_e('Choose an icon to mark this package as featured. Choose the dash to remove the badge.', 'slotera-booking'); ?></p>
                    <p style="margin-top:12px;">
                        <label for="sltr-popular-icon-color"><strong><?php esc_html_e('Featured icon color', 'slotera-booking'); ?></strong></label><br>
                        <input id="sltr-popular-icon-color" type="color" name="popular_icon_color" value="<?php echo esc_attr((string) ($package['popular_icon_color'] ?? '#7c3aed')); ?>">
                    </p>
                    <p style="margin-top:12px;">
                        <label for="sltr-popular-icon-size"><strong><?php esc_html_e('Featured icon size', 'slotera-booking'); ?></strong></label><br>
                        <input id="sltr-popular-icon-size" type="number" name="popular_icon_size" min="16" max="48" step="1" value="<?php echo esc_attr((string) ($package['popular_icon_size'] ?? 24)); ?>"> px
                    </p>
                </td>
            </tr>
<tr>
                <th scope="row"><label for="sltr-package-info-tooltip"><?php esc_html_e('Info tooltip', 'slotera-booking'); ?></label></th>
                <td>
                    <textarea id="sltr-package-info-tooltip" name="info_tooltip" rows="3" class="large-text"><?php echo esc_textarea($package['info_tooltip'] ?? ''); ?></textarea>
                    <p class="description"><?php esc_html_e('Shown only as the i tooltip on package list cards. Not shown on the solo package page.', 'slotera-booking'); ?></p>
                    <div style="display:flex;gap:24px;flex-wrap:wrap;margin-top:12px;">
                        <p style="margin:0;">
                            <label for="sltr-package-tooltip-size-ratio"><strong><?php esc_html_e('Tooltip size ratio', 'slotera-booking'); ?></strong></label><br>
                            <input id="sltr-package-tooltip-size-ratio" type="number" min="0.8" max="2" step="0.05" name="tooltip_size_ratio" value="<?php echo esc_attr((string) ($package['tooltip_size_ratio'] ?? 1.15)); ?>">
                            <span class="description"><?php esc_html_e('Example: 1.15 = 115%.', 'slotera-booking'); ?></span>
                        </p>
                        <p style="margin:0;">
                            <label for="sltr-package-tooltip-text-size"><strong><?php esc_html_e('Text size inside the tooltip', 'slotera-booking'); ?></strong></label><br>
                            <input id="sltr-package-tooltip-text-size" type="number" min="10" max="24" step="1" name="tooltip_text_size" value="<?php echo esc_attr((string) ($package['tooltip_text_size'] ?? 13)); ?>"> px
                        </p>
                    </div>
                </td>
            </tr>
