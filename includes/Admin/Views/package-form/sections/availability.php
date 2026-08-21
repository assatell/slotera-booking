<?php if (!defined('ABSPATH')) { exit; } ?>
            <tr>
                <th scope="row"><?php esc_html_e('Buffers', 'slotera-booking'); ?></th>
                <td>
                    <?php esc_html_e('Before', 'slotera-booking'); ?> <?php $sltr_duration_input('buffer_before', (int) ($package['buffer_before'] ?? 0)); ?>
                    &nbsp;&nbsp;<?php esc_html_e('After', 'slotera-booking'); ?> <?php $sltr_duration_input('buffer_after', (int) ($package['buffer_after'] ?? 0)); ?>
                    <p class="description"><?php esc_html_e('Buffers are package-wide and applied only by booking modes that use time slots.', 'slotera-booking'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="sltr-hours-mode"><?php esc_html_e('Availability schedule', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="sltr-hours-mode" name="hours_mode">
                        <option value="global" <?php selected($package['hours_mode'] ?? 'global', 'global'); ?>><?php esc_html_e('Use global working hours', 'slotera-booking'); ?></option>
                        <option value="custom" <?php selected($package['hours_mode'] ?? '', 'custom'); ?>><?php esc_html_e('Custom schedule for this package', 'slotera-booking'); ?></option>
                    </select>
                    <p style="margin-top:8px;">
                        <label>
                            <input type="checkbox" id="sltr-open-247" name="open_247" value="1" <?php checked((int) ($package['open_247'] ?? 0), 1); ?>>
                            <?php esc_html_e('Open 24/7', 'slotera-booking'); ?>
                        </label>
                        <span class="description"><?php esc_html_e('When enabled, this package is available every day from 00:00 to 23:59 and manual rows below are ignored.', 'slotera-booking'); ?></span>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Package working hours', 'slotera-booking'); ?></th>
                <td>
                    <table class="widefat striped sltr-package-hours-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Day', 'slotera-booking'); ?></th>
                                <th><?php esc_html_e('Enabled', 'slotera-booking'); ?></th>
                                <th><?php esc_html_e('Start', 'slotera-booking'); ?></th>
                                <th><?php esc_html_e('End', 'slotera-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($days as $d => $label) : $row = $hours_by_day[$d] ?? []; ?>
                                <tr>
                                    <td><?php echo esc_html($label); ?></td>
                                    <td><input type="checkbox" name="enabled[<?php echo (int) $d; ?>]" value="1" <?php checked((int) ($row['is_enabled'] ?? 0), 1); ?>></td>
                                    <td><input type="time" name="start[<?php echo (int) $d; ?>]" value="<?php echo esc_attr(substr((string) ($row['start_time'] ?? '09:00'), 0, 5)); ?>"></td>
                                    <td><input type="time" name="end[<?php echo (int) $d; ?>]" value="<?php echo esc_attr(substr((string) ($row['end_time'] ?? '18:00'), 0, 5)); ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="description"><?php esc_html_e('Used only when Availability schedule is Custom and Open 24/7 is disabled.', 'slotera-booking'); ?></p>
                </td>
            </tr>
