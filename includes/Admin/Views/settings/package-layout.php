<?php if (!defined('ABSPATH')) { exit; } ?>
    <section id="sltr-package-layout" class="sltr-panel sltr-settings-section" style="margin: 16px 0;">
        <h2><?php esc_html_e('Package card layout', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Control how many package cards are shown in one row on different screen sizes.', 'slotera-booking'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_save_display_settings">
            <input type="hidden" name="return_to" value="sltr-package-layout">
            <?php wp_nonce_field('sltr_save_display_settings'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="sltr-columns-desktop"><?php esc_html_e('Desktop columns', 'slotera-booking'); ?></label></th>
                        <td><input id="sltr-columns-desktop" type="number" min="1" max="4" name="package_columns_desktop" value="<?php echo esc_attr((string) ($settings['package_columns_desktop'] ?? 3)); ?>"> <p class="description"><?php esc_html_e('Default: 3', 'slotera-booking'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-columns-tablet"><?php esc_html_e('Tablet columns', 'slotera-booking'); ?></label></th>
                        <td><input id="sltr-columns-tablet" type="number" min="1" max="3" name="package_columns_tablet" value="<?php echo esc_attr((string) ($settings['package_columns_tablet'] ?? 2)); ?>"> <p class="description"><?php esc_html_e('Default: 2', 'slotera-booking'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-columns-mobile"><?php esc_html_e('Mobile columns', 'slotera-booking'); ?></label></th>
                        <td><input id="sltr-columns-mobile" type="number" min="1" max="1" name="package_columns_mobile" value="<?php echo esc_attr((string) ($settings['package_columns_mobile'] ?? 1)); ?>"> <p class="description"><?php esc_html_e('Default: 1', 'slotera-booking'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Booking form width', 'slotera-booking'); ?></th>
                        <td>
                            <?php $sltr_booking_width_mode = (string) ($settings['booking_form_width_mode'] ?? '1280'); ?>
                            <fieldset id="sltr-booking-form-width-options">
                                <?php foreach (['full' => __('Full width', 'slotera-booking'), '1100' => '1100 px', '1280' => '1280 px', 'custom' => __('Custom', 'slotera-booking')] as $value => $label) : ?>
                                    <label style="display:block;margin-bottom:6px;"><input type="radio" name="booking_form_width_mode" value="<?php echo esc_attr($value); ?>" <?php checked($sltr_booking_width_mode, $value); ?>> <?php echo esc_html($label); ?><?php echo $value === '1280' ? ' (' . esc_html__('Default', 'slotera-booking') . ')' : ''; ?></label>
                                <?php endforeach; ?>
                            </fieldset>
                            <p>
                                <label for="sltr-booking-form-custom-width"><?php esc_html_e('Custom width', 'slotera-booking'); ?></label><br>
                                <input id="sltr-booking-form-custom-width" type="number" min="800" max="2400" step="1" name="booking_form_custom_width" value="<?php echo esc_attr((string) ($settings['booking_form_custom_width'] ?? 1280)); ?>"> px
                            </p>
                            <p class="description"><?php esc_html_e('Custom width is used only when Custom is selected. Allowed range: 800–2400 px.', 'slotera-booking'); ?></p>
                            <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var custom = document.getElementById('sltr-booking-form-custom-width');
                                var radios = document.querySelectorAll('input[name="booking_form_width_mode"]');
                                function sync() {
                                    var selected = document.querySelector('input[name="booking_form_width_mode"]:checked');
                                    if (custom) custom.disabled = !selected || selected.value !== 'custom';
                                }
                                radios.forEach(function (radio) { radio.addEventListener('change', sync); });
                                sync();
                            });
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Slotera page titles', 'slotera-booking'); ?></th>
                        <td>
                            <input type="hidden" name="show_slotera_page_titles" value="0">
                            <label><input type="checkbox" name="show_slotera_page_titles" value="1" <?php checked(!empty($settings['show_slotera_page_titles'])); ?>> <?php esc_html_e('Show WordPress page titles on Slotera pages', 'slotera-booking'); ?></label>
                            <p class="description"><?php esc_html_e('Disabled by default. This controls theme page titles on pages that contain Slotera shortcodes or are assigned as Slotera system pages.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-select-time-layout"><?php esc_html_e('Select time layout', 'slotera-booking'); ?></label></th>
                        <td>
                            <select id="sltr-select-time-layout" name="select_time_layout">
                                <option value="list" <?php selected((string) ($settings['select_time_layout'] ?? 'grid'), 'list'); ?>><?php esc_html_e('List', 'slotera-booking'); ?></option>
                                <option value="grid" <?php selected((string) ($settings['select_time_layout'] ?? 'grid'), 'grid'); ?>><?php esc_html_e('Grid', 'slotera-booking'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Default: list.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p><button class="button button-primary"><?php esc_html_e('Save layout settings', 'slotera-booking'); ?></button></p>
        </form>
    </section>

