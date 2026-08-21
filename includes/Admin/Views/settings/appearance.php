<?php if (!defined('ABSPATH')) { exit; } ?>
    <section id="sltr-appearance" class="sltr-panel sltr-settings-section sltr-appearance-section" style="margin: 16px 0;">
        <h2><?php esc_html_e('Appearance', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Choose a preset theme or select Custom to use your own colors for the form, cards, prices and badges.', 'slotera-booking'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_save_appearance_settings">
            <input type="hidden" name="return_to" value="sltr-appearance">
            <?php wp_nonce_field('sltr_save_appearance_settings'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="sltr-appearance-theme"><?php esc_html_e('Preset theme', 'slotera-booking'); ?></label></th>
                        <td>
                            <select id="sltr-appearance-theme" name="appearance_theme" class="sltr-appearance-theme-select">
                                <?php foreach (['light' => 'Light', 'dark' => 'Dark', 'soft' => 'Soft', 'minimal' => 'Minimal', 'custom' => 'Custom'] as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($settings['appearance_theme'] ?? 'light'), $value); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Theme preview', 'slotera-booking'); ?></th>
                        <td>
                            <div class="sltr-appearance-preview sltr-theme-<?php echo esc_attr($appearance_theme); ?>" style="<?php echo esc_attr($appearance_style_vars); ?>">
                                <div class="sltr-preview-card">
                                    <span class="sltr-badge-popular sltr-badge-popular-icon" style="--sltr-featured-icon-color:#7c3aed" aria-hidden="true">★</span>
                                    <span class="sltr-badge-discount">-20%</span>
                                    <h3><?php esc_html_e('Sample package', 'slotera-booking'); ?></h3>
                                    <p><?php esc_html_e('This preview uses the same colors as the frontend package cards.', 'slotera-booking'); ?></p>
                                    <div class="sltr-preview-price"><del>100.00</del> <b>80.00</b></div>
                                    <button type="button" class="button button-primary sltr-preview-button"><?php esc_html_e('Book now', 'slotera-booking'); ?></button>
                                </div>
                            </div>
                            <p class="description"><?php esc_html_e('Preview updates instantly while you edit. Preset themes fill the color fields; Custom lets you adjust every color manually.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                    <?php
                    $color_fields = [
                        'form_background_color' => __('Form background', 'slotera-booking'),
                        'form_text_color' => __('Form text', 'slotera-booking'),
                        'card_background_color' => __('Card background', 'slotera-booking'),
                        'card_border_color' => __('Card border', 'slotera-booking'),
                        'primary_color' => __('Primary color', 'slotera-booking'),
                        'primary_text_color' => __('Primary text', 'slotera-booking'),
                        'muted_text_color' => __('Muted text', 'slotera-booking'),
                        'price_old_color' => __('Old price color', 'slotera-booking'),
                        'price_new_color' => __('New price color', 'slotera-booking'),
                        'discount_badge_background_color' => __('Discount badge background', 'slotera-booking'),
                        'discount_badge_text_color' => __('Discount badge text', 'slotera-booking'),
                        'tooltip_icon_color' => __('Tooltip icon color', 'slotera-booking'),
                        'tooltip_background_color' => __('Tooltip background', 'slotera-booking'),
                        'tooltip_text_color' => __('Tooltip text', 'slotera-booking'),
                        'calendar_background_color' => __('Calendar background', 'slotera-booking'),
                        'calendar_text_color' => __('Calendar text', 'slotera-booking'),
                        'calendar_border_color' => __('Calendar border', 'slotera-booking'),
                        'calendar_day_background_color' => __('Calendar day background', 'slotera-booking'),
                        'calendar_disabled_background_color' => __('Blocked date background', 'slotera-booking'),
                        'calendar_disabled_text_color' => __('Blocked date text / lock', 'slotera-booking'),
                    ];
                    foreach ($color_fields as $field => $label) :
                    ?>
                        <tr>
                            <th scope="row"><label for="sltr-<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?></label></th>
                            <td><input id="sltr-<?php echo esc_attr($field); ?>" class="sltr-color-input" data-setting="<?php echo esc_attr($field); ?>" type="color" name="<?php echo esc_attr($field); ?>" value="<?php echo esc_attr((string) ($settings[$field] ?? '#000000')); ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th scope="row"><label for="sltr-price-old-style"><?php esc_html_e('Old price style', 'slotera-booking'); ?></label></th>
                        <td>
                            <select id="sltr-price-old-style" name="price_old_style" class="sltr-price-old-style-input">
                                <option value="line-through" <?php selected((string) ($settings['price_old_style'] ?? 'line-through'), 'line-through'); ?>><?php esc_html_e('Line through', 'slotera-booking'); ?></option>
                                <option value="none" <?php selected((string) ($settings['price_old_style'] ?? 'line-through'), 'none'); ?>><?php esc_html_e('None', 'slotera-booking'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-price-old-size-ratio"><?php esc_html_e('Old price size ratio', 'slotera-booking'); ?></label></th>
                        <td>
                            <input id="sltr-price-old-size-ratio" class="sltr-price-old-size-ratio-input" type="number" min="0.6" max="1.2" step="0.05" name="price_old_size_ratio" value="<?php echo esc_attr((string) ($settings['price_old_size_ratio'] ?? 0.85)); ?>">
                            <p class="description"><?php esc_html_e('Example: 0.85 means the old price is 85% of the new price size.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p><button class="button button-primary"><?php esc_html_e('Save appearance settings', 'slotera-booking'); ?></button></p>
        </form>
    </section>

