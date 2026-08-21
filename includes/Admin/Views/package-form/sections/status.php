<?php if (!defined('ABSPATH')) { exit; } ?>
            <tr>
                <th scope="row"><?php esc_html_e('Status', 'slotera-booking'); ?></th>
                <td><strong><?php echo !empty($package['is_active']) ? esc_html__('Active', 'slotera-booking') : esc_html__('Draft', 'slotera-booking'); ?></strong><p class="description"><?php esc_html_e('Change status from the Packages list using Deactivate or Restore.', 'slotera-booking'); ?></p></td>
            </tr>
