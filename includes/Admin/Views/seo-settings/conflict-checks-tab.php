<?php if (!defined('ABSPATH')) { exit; } ?>
            <h2><?php esc_html_e('Conflict Checks', 'slotera-booking'); ?></h2>
            <table class="widefat striped"><tbody>
                <tr><td><?php esc_html_e('External SEO plugin', 'slotera-booking'); ?></td><td><?php echo esc_html($sltr_seo_plugins_blocking ? implode(', ', $detected_seo_plugins) : __('Not detected', 'slotera-booking')); ?></td></tr>
                <tr><td><?php esc_html_e('Other Pages SEO status', 'slotera-booking'); ?></td><td><?php echo esc_html($sltr_wp_enabled ? __('Enabled', 'slotera-booking') : __('Disabled / blocked', 'slotera-booking')); ?></td></tr>
                <tr><td><?php esc_html_e('Slotera SEO output mode', 'slotera-booking'); ?></td><td><?php echo esc_html($sltr_seo_mode); ?></td></tr>
            </tbody></table>
            <p><?php esc_html_e('Use one SEO source per page type. Slotera can manage Slotera packages/categories. Ordinary WP pages should be managed either by Slotera or by a dedicated SEO plugin, never both.', 'slotera-booking'); ?></p>
        </div>
