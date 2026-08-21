<?php if (!defined('ABSPATH')) { exit; } ?>
            <tr>
                <th scope="row"><?php esc_html_e('Booking settings', 'slotera-booking'); ?></th>
                <td>
                    <input type="hidden" id="sltr-booking-mode" name="booking_mode" value="<?php echo esc_attr($sltr_active_mode); ?>">
                    <p class="description"><?php esc_html_e('Only one booking block can be active for a package. Settings are isolated per block; only the active block is used for availability, pricing and payment.', 'slotera-booking'); ?></p>
                    <div class="sltr-booking-blocks" data-active-mode="<?php echo esc_attr($sltr_active_mode); ?>">
                        <section class="sltr-booking-block <?php echo esc_attr($sltr_active_mode === 'simple' ? 'is-active' : ''); ?>" data-mode="simple">
                            <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/../booking-blocks.php')) { require $sltr_view; } ?>
                    </div>
                </td>
            </tr>
