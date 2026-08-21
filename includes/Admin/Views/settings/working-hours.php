<?php if (!defined('ABSPATH')) { exit; } ?>
    <section id="sltr-working-hours" class="sltr-panel sltr-settings-section" style="margin: 16px 0;">
        <h2><?php esc_html_e('Global working hours', 'slotera-booking'); ?></h2>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_save_working_hours">
            <input type="hidden" name="return_to" value="sltr-working-hours">
            <?php wp_nonce_field('sltr_save_working_hours'); ?>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Day', 'slotera-booking'); ?></th>
                        <th><?php esc_html_e('Enabled', 'slotera-booking'); ?></th>
                        <th><?php esc_html_e('Start', 'slotera-booking'); ?></th>
                        <th><?php esc_html_e('End', 'slotera-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day => $label) : ?>
                        <?php $row = $hours[$day] ?? []; ?>
                        <tr>
                            <td><?php echo esc_html($label); ?></td>
                            <td><input type="checkbox" name="enabled[<?php echo (int) $day; ?>]" value="1" <?php checked((int) ($row['is_enabled'] ?? 0), 1); ?>></td>
                            <td><input type="time" name="start[<?php echo (int) $day; ?>]" value="<?php echo esc_attr(substr((string) ($row['start_time'] ?? '09:00'), 0, 5)); ?>"></td>
                            <td><input type="time" name="end[<?php echo (int) $day; ?>]" value="<?php echo esc_attr(substr((string) ($row['end_time'] ?? '18:00'), 0, 5)); ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p><button class="button button-primary"><?php esc_html_e('Save working hours', 'slotera-booking'); ?></button></p>
        </form>
    </section>
