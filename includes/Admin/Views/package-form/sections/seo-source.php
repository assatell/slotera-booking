<?php if (!defined('ABSPATH')) { exit; } ?>
            <tr>
                <th scope="row"><?php esc_html_e('SEO Source', 'slotera-booking'); ?></th>
                <td>
                    <p><strong><?php esc_html_e('Managed by SEO Center', 'slotera-booking'); ?></strong></p>
                    <p class="description"><?php esc_html_e('All SEO fields, including multilingual SEO overrides, were moved to Slotera → Individual SEO Settings to avoid duplicate settings and conflicts.', 'slotera-booking'); ?></p>
                    <?php if ($id > 0) : ?>
                        <p><a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'seo', 'tab' => 'individual', 'sltr_focus' => 'package-' . $id], admin_url('admin.php'))); ?>"><?php esc_html_e('Edit SEO', 'slotera-booking'); ?></a></p>
                    <?php else : ?>
                        <p class="description"><?php esc_html_e('Save this item first, then use Edit SEO from the list or Individual SEO Settings.', 'slotera-booking'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
