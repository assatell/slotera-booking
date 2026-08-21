<?php if (!defined('ABSPATH')) { exit; } ?>
    <section id="sltr-shortcodes" class="sltr-panel sltr-settings-section" style="margin: 16px 0;">
        <h2><?php esc_html_e('Shortcodes', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Use these shortcodes to place Slotera booking elements on WordPress pages.', 'slotera-booking'); ?></p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Purpose', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Shortcode', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Description', 'slotera-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shortcodes as $shortcode) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($shortcode['title']); ?></strong></td>
                        <td><code style="font-size:13px; user-select:all;"><?php echo esc_html($shortcode['code']); ?></code></td>
                        <td><?php echo esc_html($shortcode['description']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

