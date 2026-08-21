<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="sltr-settings-card">
    <h2><?php esc_html_e('Booking Form', 'slotera-booking'); ?></h2>

    <?php if (!empty($_GET['booking_form_updated'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Booking form settings saved.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_save_booking_form">
        <?php wp_nonce_field('sltr_save_booking_form'); ?>

        <p><?php esc_html_e('Choose which customer fields appear in the public booking form. Email is always enabled and required.', 'slotera-booking'); ?></p>

        <table class="widefat striped sltr-booking-form-settings">
            <thead>
                <tr>
                    <th><?php esc_html_e('Field', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Show', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Required', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Type', 'slotera-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($booking_form_fields ?? []) as $key => $field) : ?>
                    <?php $locked = !empty($field['locked']); ?>
                    <tr>
                        <td><strong><?php echo esc_html((string) $field['label']); ?></strong><?php if ($key === 'notes') : ?><br><small><?php esc_html_e('Large wishes / additional notes field shown after customer details.', 'slotera-booking'); ?></small><?php endif; ?></td>
                        <td>
                            <label>
                                <input type="checkbox" name="booking_form_<?php echo esc_attr($key); ?>_enabled" value="1" <?php checked(!empty($field['enabled'])); ?> <?php disabled($locked); ?>>
                                <?php esc_html_e('Show field', 'slotera-booking'); ?>
                            </label>
                            <?php if ($locked) : ?><input type="hidden" name="booking_form_<?php echo esc_attr($key); ?>_enabled" value="1"><?php endif; ?>
                        </td>
                        <td>
                            <label>
                                <input type="checkbox" name="booking_form_<?php echo esc_attr($key); ?>_required" value="1" <?php checked(!empty($field['required'])); ?> <?php disabled($locked); ?>>
                                <?php esc_html_e('Required', 'slotera-booking'); ?>
                            </label>
                            <?php if ($locked) : ?><input type="hidden" name="booking_form_<?php echo esc_attr($key); ?>_required" value="1"><?php endif; ?>
                        </td>
                        <td><?php echo esc_html((string) $field['type']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e('Save booking form', 'slotera-booking'); ?></button></p>
    </form>
</div>
