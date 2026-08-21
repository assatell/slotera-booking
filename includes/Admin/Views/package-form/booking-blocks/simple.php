<?php if (!defined('ABSPATH')) { exit; } ?>
                            <div class="sltr-booking-block-header">
                                <label><input type="radio" name="booking_mode_selector" value="simple" <?php checked($sltr_active_mode, 'simple'); ?>> <?php esc_html_e('Booking Request', 'slotera-booking'); ?></label>
                                <span><?php esc_html_e('No date, no time — just a direct booking/request.', 'slotera-booking'); ?></span>
                            </div>
                            <p class="description"><?php esc_html_e('Use this for services where the business confirms details later, such as catering, custom rentals, shows or quote requests.', 'slotera-booking'); ?></p>
                            <div class="sltr-booking-block-body">
                                <p><label><?php esc_html_e('Booking button text', 'slotera-booking'); ?></label><br>
                                    <input type="text" class="regular-text" maxlength="120" name="mode_config[simple][booking_button_text]" value="<?php echo esc_attr((string) $sltr_mode_value('simple', 'booking_button_text', '')); ?>" placeholder="<?php esc_attr_e('Use your own text for the button', 'slotera-booking'); ?>"><br>
                                    <span class="description"><?php esc_html_e('Default: Book now. Leave the field empty to use the default localized text.', 'slotera-booking'); ?></span>
                                </p>
                                <?php $sltr_admin_render_preview_panel('simple', __('Booking Request setup', 'slotera-booking'), __('Use when customers should book or request without choosing a date or time.', 'slotera-booking')); ?>
                                <p><label><?php esc_html_e('Price display', 'slotera-booking'); ?></label><br>
                                    <select name="mode_config[simple][price_mode]" class="sltr-simple-price-mode-select" onchange="var f=this.form;if(f&&f.elements['sltr_simple_price_mode']){f.elements['sltr_simple_price_mode'].value=this.value;}">
                                        <option value="fixed" <?php selected($sltr_mode_value('simple', 'price_mode', 'fixed'), 'fixed'); ?>><?php esc_html_e('Fixed price', 'slotera-booking'); ?></option>
                                        <option value="from" <?php selected($sltr_mode_value('simple', 'price_mode', ''), 'from'); ?>><?php esc_html_e('From price', 'slotera-booking'); ?></option>
                                        <option value="request" <?php selected($sltr_mode_value('simple', 'price_mode', ''), 'request'); ?>><?php esc_html_e('Price on request', 'slotera-booking'); ?></option>
                                    </select>
                                </p>
                                <p><label><?php esc_html_e('Base price', 'slotera-booking'); ?></label><br><input type="number" step="0.01" min="0" name="mode_config[simple][price]" value="<?php echo esc_attr((string) $sltr_mode_value('simple', 'price', 0)); ?>"><br><span class="description"><?php esc_html_e('The default amount shown before coupons, tax, deposits or extra services are applied.', 'slotera-booking'); ?></span></p>
                                <p><label><?php esc_html_e('Capacity', 'slotera-booking'); ?></label><br>
                                    <select name="mode_config[simple][capacity_type]">
                                        <option value="unlimited" <?php selected($sltr_mode_value('simple', 'capacity_type', 'unlimited'), 'unlimited'); ?>><?php esc_html_e('Unlimited', 'slotera-booking'); ?></option>
                                        <option value="limited" <?php selected($sltr_mode_value('simple', 'capacity_type', ''), 'limited'); ?>><?php esc_html_e('Limited total quantity', 'slotera-booking'); ?></option>
                                    </select>
                                    <input type="number" min="1" max="999999" name="mode_config[simple][capacity_total]" value="<?php echo esc_attr((string) $sltr_mode_value('simple', 'capacity_total', 1)); ?>" style="width:100px;"><br>
                                    <span class="description"><?php esc_html_e('Maximum number of bookings allowed at the same time for this package.', 'slotera-booking'); ?></span>
                                </p>
                                <p><label><input type="hidden" name="confirm_immediately_simple" value="0"><input type="checkbox" class="sltr-confirm-immediately-toggle" name="confirm_immediately_simple" value="1" <?php checked((int) $sltr_mode_value('simple', 'confirm_immediately', 0), 1); ?> onchange="var f=this.form;if(f){if(f.elements['sltr_confirm_immediately_simple']){f.elements['sltr_confirm_immediately_simple'].value=this.checked?'1':'0';}if(f.elements['confirm_immediately_simple']){var el=f.elements['confirm_immediately_simple']; if(el.length){el[0].value=this.checked?'1':'0';}}}"> <?php esc_html_e('Confirm immediately when no online payment is required', 'slotera-booking'); ?></label></p>
                                <p><label><?php esc_html_e('Included services', 'slotera-booking'); ?></label><br><textarea name="mode_config[simple][included_services]" rows="4" class="large-text" placeholder="Consultation&#10;Setup&#10;Support"><?php echo esc_textarea((string) $sltr_mode_value('simple', 'included_services', '')); ?></textarea></p>
                                <h4><?php esc_html_e('Extra services', 'slotera-booking'); ?></h4>
                                <p class="description"><?php esc_html_e('Optional add-ons shown during booking. Disable an item to keep it saved without offering it to customers.', 'slotera-booking'); ?></p>
                                <table class="widefat striped sltr-repeat-table"><thead><tr><th><?php esc_html_e('Active', 'slotera-booking'); ?></th><th><?php esc_html_e('Name', 'slotera-booking'); ?></th><th><?php esc_html_e('Description', 'slotera-booking'); ?></th><th><?php esc_html_e('Price', 'slotera-booking'); ?></th><th><?php esc_html_e('Price type', 'slotera-booking'); ?></th></tr></thead><tbody>
                                    <?php foreach ($sltr_extra_services('simple') as $idx => $extra) : ?>
                                        <tr>
                                            <td><input type="hidden" name="mode_config[simple][extra_services][<?php echo esc_attr((string) $idx); ?>][id]" value="<?php echo esc_attr((string) $extra['id']); ?>"><input type="hidden" class="sltr-active-value" name="mode_config[simple][extra_services][<?php echo esc_attr((string) $idx); ?>][active]" value="<?php echo esc_attr((string) ((int) $extra['active'])); ?>"><label><input type="checkbox" class="sltr-active-toggle" autocomplete="off" data-target="mode_config[simple][extra_services][<?php echo esc_attr((string) $idx); ?>][active]" value="1" <?php checked($extra['active'], 1); ?>></label></td>
                                            <td><input type="text" name="mode_config[simple][extra_services][<?php echo esc_attr((string) $idx); ?>][name]" value="<?php echo esc_attr($extra['name']); ?>" placeholder="<?php esc_attr_e('Extra option', 'slotera-booking'); ?>"></td>
                                            <td><input type="text" name="mode_config[simple][extra_services][<?php echo esc_attr((string) $idx); ?>][description]" value="<?php echo esc_attr($extra['description']); ?>"></td>
                                            <td><input type="number" step="0.01" min="0" name="mode_config[simple][extra_services][<?php echo esc_attr((string) $idx); ?>][price]" value="<?php echo esc_attr((string) $extra['price']); ?>" style="width:110px;"></td>
                                            <td><select name="mode_config[simple][extra_services][<?php echo esc_attr((string) $idx); ?>][price_type]"><option value="once" <?php selected($extra['price_type'], 'once'); ?>><?php esc_html_e('Once', 'slotera-booking'); ?></option><option value="per_guest" <?php selected($extra['price_type'], 'per_guest'); ?>><?php esc_html_e('Per guest', 'slotera-booking'); ?></option></select></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody></table>
                                <details class="sltr-advanced-json"><summary><?php esc_html_e('Advanced JSON import/export', 'slotera-booking'); ?></summary><textarea name="mode_config[simple][extra_services_json]" rows="4" class="large-text code"><?php echo esc_textarea((string) $sltr_mode_value('simple', 'extra_services_json', '')); ?></textarea></details>
                                <p><label><?php esc_html_e('Discount', 'slotera-booking'); ?></label><br>
                                    <select name="mode_config[simple][discount_type]"><option value="none" <?php selected($sltr_mode_value('simple', 'discount_type', 'none'), 'none'); ?>><?php esc_html_e('None', 'slotera-booking'); ?></option><option value="percent" <?php selected($sltr_mode_value('simple', 'discount_type', ''), 'percent'); ?>><?php esc_html_e('Percent', 'slotera-booking'); ?></option><option value="fixed" <?php selected($sltr_mode_value('simple', 'discount_type', ''), 'fixed'); ?>><?php esc_html_e('Fixed amount', 'slotera-booking'); ?></option></select>
                                    <input type="number" step="0.01" min="0" name="mode_config[simple][discount_value]" value="<?php echo esc_attr((string) $sltr_mode_value('simple', 'discount_value', 0)); ?>">
                                </p>
                                <p><label><?php esc_html_e('Campaign note', 'slotera-booking'); ?></label><br><input class="regular-text" name="mode_config[simple][campaign_note]" value="<?php echo esc_attr((string) $sltr_mode_value('simple', 'campaign_note', '')); ?>"><span class="description"> <?php esc_html_e('Optional public urgency note shown on frontend, e.g. Offer ends soon.', 'slotera-booking'); ?></span></p>
                                <?php $sltr_render_low_availability_fields('simple'); ?>
                                <?php sltr_render_pricing_adjustment_fields('simple', $sltr_mode_value); ?>
                                <?php sltr_render_payment_policy_fields('simple', $sltr_mode_value); ?>
                            </div>
                        </section>
