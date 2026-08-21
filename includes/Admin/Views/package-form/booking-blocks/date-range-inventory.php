<?php if (!defined('ABSPATH')) { exit; } ?>
<?php if (($sltr_package_step ?? 'details') === 'events') : ?>
<section class="sltr-booking-block is-active" data-mode="date_range_inventory">
    <h2><?php esc_html_e('Events', 'slotera-booking'); ?></h2>
    <p class="description"><?php esc_html_e('Create fixed dates with start and end times, available seats and a price.', 'slotera-booking'); ?></p>
    <input type="hidden" name="booking_mode_selector" value="date_range_inventory">
    <input type="hidden" name="mode_config[date_range_inventory][date_flow]" value="admin_scheduled">
    <div class="sltr-booking-block-body">
                                <div class="sltr-date-flow-panel sltr-date-flow-admin_scheduled">
                                    <p class="description"><?php esc_html_e('Create ready-made packages/departures. Customers see the start/end date, price and seats left, then go straight to details.', 'slotera-booking'); ?></p>
                                    <table class="widefat striped sltr-scheduled-events-table">
                                        <thead><tr><th><?php esc_html_e('Title', 'slotera-booking'); ?></th><th><?php esc_html_e('Start date/time', 'slotera-booking'); ?></th><th><?php esc_html_e('End date/time', 'slotera-booking'); ?></th><th><?php esc_html_e('Use time', 'slotera-booking'); ?></th><th><?php esc_html_e('Free seats', 'slotera-booking'); ?></th><th><?php esc_html_e('Price', 'slotera-booking'); ?></th><th></th></tr></thead>
                                        <tbody>
                                            <?php foreach ($sltr_scheduled_events('date_range_inventory') as $idx => $event) : ?>
                                            <tr>
                                                <td><input type="hidden" name="mode_config[date_range_inventory][scheduled_events][<?php echo esc_attr((string) $idx); ?>][id]" value="<?php echo esc_attr((string) $event['id']); ?>"><input type="text" name="mode_config[date_range_inventory][scheduled_events][<?php echo esc_attr((string) $idx); ?>][title]" value="<?php echo esc_attr($event['title']); ?>" placeholder="<?php esc_attr_e('Group tour', 'slotera-booking'); ?>"></td>
                                                <td><input type="date" name="mode_config[date_range_inventory][scheduled_events][<?php echo esc_attr((string) $idx); ?>][start_date]" value="<?php echo esc_attr($event['start_date']); ?>"> <input type="time" name="mode_config[date_range_inventory][scheduled_events][<?php echo esc_attr((string) $idx); ?>][start_time]" value="<?php echo esc_attr($event['start_time']); ?>"></td>
                                                <td><input type="date" name="mode_config[date_range_inventory][scheduled_events][<?php echo esc_attr((string) $idx); ?>][end_date]" value="<?php echo esc_attr($event['end_date']); ?>"> <input type="time" name="mode_config[date_range_inventory][scheduled_events][<?php echo esc_attr((string) $idx); ?>][end_time]" value="<?php echo esc_attr($event['end_time']); ?>"></td>
                                                <td><label><input type="checkbox" name="mode_config[date_range_inventory][scheduled_events][<?php echo esc_attr((string) $idx); ?>][use_time]" value="1" <?php checked($event['use_time'], 1); ?>> <?php esc_html_e('Yes', 'slotera-booking'); ?></label></td>
                                                <td><input type="number" min="1" max="9999" name="mode_config[date_range_inventory][scheduled_events][<?php echo esc_attr((string) $idx); ?>][seats]" value="<?php echo esc_attr((string) $event['seats']); ?>" style="width:80px;"></td>
                                                <td><input type="number" step="0.01" min="0" name="mode_config[date_range_inventory][scheduled_events][<?php echo esc_attr((string) $idx); ?>][price]" value="<?php echo esc_attr((string) $event['price']); ?>" style="width:110px;"></td>
                                                <td><button type="button" class="button sltr-remove-scheduled-event"><?php esc_html_e('Remove', 'slotera-booking'); ?></button></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <p><button type="button" class="button sltr-add-scheduled-event"><?php esc_html_e('Add event', 'slotera-booking'); ?></button></p>
                                </div>

    </div>
</section>
<?php else : ?>
                        <section class="sltr-booking-block <?php echo esc_attr($sltr_active_mode === 'date_range_inventory' ? 'is-active' : ''); ?>" data-mode="date_range_inventory">
                            <h2>
                                <label><input type="radio" name="booking_mode_selector" value="date_range_inventory" <?php checked($sltr_active_mode, 'date_range_inventory'); ?>> <?php esc_html_e('Date range inventory', 'slotera-booking'); ?></label>
                            </h2>
                            <p class="description"><?php esc_html_e('Date-based bookings with rooms/units, check-in/check-out, included services and extras.', 'slotera-booking'); ?></p>
                            <div class="sltr-booking-block-body">
                                <p><label><?php esc_html_e('Booking button text', 'slotera-booking'); ?></label><br>
                                    <input type="text" class="regular-text" maxlength="120" name="mode_config[date_range_inventory][booking_button_text]" value="<?php echo esc_attr((string) $sltr_mode_value('date_range_inventory', 'booking_button_text', '')); ?>" placeholder="<?php esc_attr_e('Use your own text for the button', 'slotera-booking'); ?>"><br>
                                    <span class="description"><?php esc_html_e('Default: Book now. Leave the field empty to use the default localized text.', 'slotera-booking'); ?></span>
                                </p>
                                <?php $sltr_admin_render_preview_panel('date_range_inventory', __('Date range inventory setup', 'slotera-booking'), __('Use for nights/days/hours, rooms/units, or admin-scheduled tours/events.', 'slotera-booking')); ?>
                                <?php $sltr_date_flow = 'customer_choice'; ?>
                                <input type="hidden" name="mode_config[date_range_inventory][date_flow]" value="customer_choice">

                                <div class="sltr-date-flow-panel sltr-date-flow-customer_choice">
                                    <p><label><?php esc_html_e('Price', 'slotera-booking'); ?></label><br><input type="number" step="0.01" min="0" name="mode_config[date_range_inventory][price]" value="<?php echo esc_attr((string) $sltr_mode_value('date_range_inventory', 'price', 0)); ?>"></p>
                                    <p><label><?php esc_html_e('Price unit', 'slotera-booking'); ?></label><br>
                                        <select name="mode_config[date_range_inventory][price_unit]"><option value="fixed" <?php selected($sltr_mode_value('date_range_inventory', 'price_unit', 'fixed'), 'fixed'); ?>><?php esc_html_e('Fixed for range', 'slotera-booking'); ?></option><option value="per_day" <?php selected($sltr_mode_value('date_range_inventory', 'price_unit', ''), 'per_day'); ?>><?php esc_html_e('Per day', 'slotera-booking'); ?></option><option value="per_night" <?php selected($sltr_mode_value('date_range_inventory', 'price_unit', ''), 'per_night'); ?>><?php esc_html_e('Per night', 'slotera-booking'); ?></option><option value="per_hour" <?php selected($sltr_mode_value('date_range_inventory', 'price_unit', ''), 'per_hour'); ?>><?php esc_html_e('Per hour', 'slotera-booking'); ?></option></select>
                                        <?php esc_html_e('Hourly price', 'slotera-booking'); ?> <input type="number" step="0.01" min="0" name="mode_config[date_range_inventory][hourly_price]" value="<?php echo esc_attr((string) $sltr_mode_value('date_range_inventory', 'hourly_price', 0)); ?>">
                                    </p>
                                    <p>
                                        <?php esc_html_e('Check-in', 'slotera-booking'); ?> <input type="time" name="mode_config[date_range_inventory][checkin_time]" value="<?php echo esc_attr(substr((string) $sltr_mode_value('date_range_inventory', 'checkin_time', '15:00'), 0, 5)); ?>">
                                        <?php esc_html_e('Check-out', 'slotera-booking'); ?> <input type="time" name="mode_config[date_range_inventory][checkout_time]" value="<?php echo esc_attr(substr((string) $sltr_mode_value('date_range_inventory', 'checkout_time', '11:00'), 0, 5)); ?>">
                                        <?php esc_html_e('Min nights', 'slotera-booking'); ?> <input type="number" min="1" name="mode_config[date_range_inventory][min_nights]" value="<?php echo esc_attr((string) $sltr_mode_value('date_range_inventory', 'min_nights', 1)); ?>" style="width:80px;">
                                        <?php esc_html_e('Max nights', 'slotera-booking'); ?> <input type="number" min="1" name="mode_config[date_range_inventory][max_nights]" value="<?php echo esc_attr((string) $sltr_mode_value('date_range_inventory', 'max_nights', 30)); ?>" style="width:80px;">
                                    </p>
                                    <h3><?php esc_html_e('Rooms / units', 'slotera-booking'); ?></h3>
                                    <p class="description"><?php esc_html_e('Add rooms, vehicles, apartments or other bookable units. Disable a unit to keep it saved without making it bookable.', 'slotera-booking'); ?></p>
                                    <p class="description"><?php esc_html_e('Capacity: Maximum number of bookings allowed at the same time for this unit.', 'slotera-booking'); ?></p>
                                    <table class="widefat striped sltr-repeat-table sltr-inventory-units-table">
                                        <thead><tr><th><?php esc_html_e('Active', 'slotera-booking'); ?></th><th><?php esc_html_e('Name', 'slotera-booking'); ?></th><th><?php esc_html_e('Description', 'slotera-booking'); ?></th><th><?php esc_html_e('Capacity', 'slotera-booking'); ?></th><th><?php esc_html_e('Base price', 'slotera-booking'); ?></th><th><?php esc_html_e('Hourly price', 'slotera-booking'); ?></th><th></th></tr></thead>
                                        <tbody>
                                            <?php foreach ($sltr_inventory_units('date_range_inventory') as $idx => $unit) : ?>
                                            <tr>
                                                <td><input type="hidden" name="mode_config[date_range_inventory][inventory_units][<?php echo esc_attr((string) $idx); ?>][id]" value="<?php echo esc_attr((string) $unit['id']); ?>"><input type="hidden" class="sltr-active-value" name="mode_config[date_range_inventory][inventory_units][<?php echo esc_attr((string) $idx); ?>][active]" value="0"><label><input type="checkbox" class="sltr-active-toggle" autocomplete="off" name="mode_config[date_range_inventory][inventory_units][<?php echo esc_attr((string) $idx); ?>][active_checked]" value="1" <?php checked($unit['active'], 1); ?>></label></td>
                                                <td><input type="text" name="mode_config[date_range_inventory][inventory_units][<?php echo esc_attr((string) $idx); ?>][name]" value="<?php echo esc_attr($unit['name']); ?>" placeholder="<?php esc_attr_e('Room 1', 'slotera-booking'); ?>"></td>
                                                <td><input type="text" name="mode_config[date_range_inventory][inventory_units][<?php echo esc_attr((string) $idx); ?>][description]" value="<?php echo esc_attr($unit['description']); ?>"></td>
                                                <td><input type="number" min="1" max="9999" name="mode_config[date_range_inventory][inventory_units][<?php echo esc_attr((string) $idx); ?>][capacity]" value="<?php echo esc_attr((string) $unit['capacity']); ?>" style="width:80px;" aria-label="<?php esc_attr_e('Capacity. Maximum number of bookings allowed at the same time for this unit.', 'slotera-booking'); ?>"></td>
                                                <td><input type="number" step="0.01" min="0" name="mode_config[date_range_inventory][inventory_units][<?php echo esc_attr((string) $idx); ?>][price]" value="<?php echo esc_attr((string) $unit['price']); ?>" style="width:110px;"></td>
                                                <td><input type="number" step="0.01" min="0" name="mode_config[date_range_inventory][inventory_units][<?php echo esc_attr((string) $idx); ?>][hourly_price]" value="<?php echo esc_attr((string) $unit['hourly_price']); ?>" style="width:110px;"></td>
                                                <td><button type="button" class="button sltr-remove-row"><?php esc_html_e('Remove', 'slotera-booking'); ?></button></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <p><button type="button" class="button sltr-add-inventory-unit"><?php esc_html_e('Add room / unit', 'slotera-booking'); ?></button></p>
                                    <details class="sltr-advanced-json"><summary><?php esc_html_e('Advanced JSON import/export', 'slotera-booking'); ?></summary><textarea name="mode_config[date_range_inventory][inventory_units_json]" rows="4" class="large-text code"><?php echo esc_textarea((string) $sltr_mode_value('date_range_inventory', 'inventory_units_json', '')); ?></textarea></details>

                                    <h3><?php esc_html_e('Date inventory overrides', 'slotera-booking'); ?></h3>
                                    <p class="description"><?php esc_html_e('Optional date periods for closing inventory, changing capacity or overriding price.', 'slotera-booking'); ?></p>
                                    <table class="widefat striped sltr-repeat-table sltr-date-overrides-table">
                                        <thead><tr><th><?php esc_html_e('Start date', 'slotera-booking'); ?></th><th><?php esc_html_e('End date', 'slotera-booking'); ?></th><th><?php esc_html_e('Available quantity', 'slotera-booking'); ?></th><th><?php esc_html_e('Price override', 'slotera-booking'); ?></th><th><?php esc_html_e('Closed', 'slotera-booking'); ?></th><th></th></tr></thead>
                                        <tbody>
                                            <?php foreach ($sltr_date_inventory_overrides('date_range_inventory') as $idx => $override) : ?>
                                            <tr>
                                                <td><input type="date" name="mode_config[date_range_inventory][date_inventory_overrides][<?php echo esc_attr((string) $idx); ?>][start_date]" value="<?php echo esc_attr($override['start_date']); ?>"></td>
                                                <td><input type="date" name="mode_config[date_range_inventory][date_inventory_overrides][<?php echo esc_attr((string) $idx); ?>][end_date]" value="<?php echo esc_attr($override['end_date']); ?>"></td>
                                                <td><input type="number" min="0" max="9999" name="mode_config[date_range_inventory][date_inventory_overrides][<?php echo esc_attr((string) $idx); ?>][capacity]" value="<?php echo esc_attr((string) $override['capacity']); ?>" style="width:100px;"></td>
                                                <td><input type="number" step="0.01" min="0" name="mode_config[date_range_inventory][date_inventory_overrides][<?php echo esc_attr((string) $idx); ?>][price]" value="<?php echo esc_attr((string) $override['price']); ?>" style="width:110px;"></td>
                                                <td><label><input type="checkbox" name="mode_config[date_range_inventory][date_inventory_overrides][<?php echo esc_attr((string) $idx); ?>][closed]" value="1" <?php checked($override['closed'], 1); ?>></label></td>
                                                <td><button type="button" class="button sltr-remove-row"><?php esc_html_e('Remove', 'slotera-booking'); ?></button></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <p><button type="button" class="button sltr-add-date-override"><?php esc_html_e('Add date override', 'slotera-booking'); ?></button></p>
                                    <details class="sltr-advanced-json"><summary><?php esc_html_e('Advanced JSON import/export', 'slotera-booking'); ?></summary><textarea name="mode_config[date_range_inventory][date_inventory_json]" rows="3" class="large-text code"><?php echo esc_textarea((string) $sltr_mode_value('date_range_inventory', 'date_inventory_json', '')); ?></textarea></details>
                                </div>

                                <p><label><?php esc_html_e('Included services', 'slotera-booking'); ?></label><br><textarea name="mode_config[date_range_inventory][included_services]" rows="4" class="large-text" placeholder="Wi-Fi&#10;Breakfast&#10;Parking"><?php echo esc_textarea((string) $sltr_mode_value('date_range_inventory', 'included_services', '')); ?></textarea></p>
                                <h3><?php esc_html_e('Extra services', 'slotera-booking'); ?></h3>
                                <p class="description"><?php esc_html_e('Optional add-ons shown during booking. Disable an item to keep it saved without offering it to customers.', 'slotera-booking'); ?></p>
                                <table class="widefat striped sltr-repeat-table sltr-extra-services-table">
                                    <thead><tr><th><?php esc_html_e('Active', 'slotera-booking'); ?></th><th><?php esc_html_e('Name', 'slotera-booking'); ?></th><th><?php esc_html_e('Description', 'slotera-booking'); ?></th><th><?php esc_html_e('Price', 'slotera-booking'); ?></th><th><?php esc_html_e('Price type', 'slotera-booking'); ?></th><th></th></tr></thead>
                                    <tbody>
                                        <?php foreach ($sltr_extra_services('date_range_inventory') as $idx => $extra) : ?>
                                        <tr>
                                            <td><input type="hidden" name="mode_config[date_range_inventory][extra_services][<?php echo esc_attr((string) $idx); ?>][id]" value="<?php echo esc_attr((string) $extra['id']); ?>"><input type="hidden" class="sltr-active-value" name="mode_config[date_range_inventory][extra_services][<?php echo esc_attr((string) $idx); ?>][active]" value="0"><label><input type="checkbox" class="sltr-active-toggle" autocomplete="off" name="mode_config[date_range_inventory][extra_services][<?php echo esc_attr((string) $idx); ?>][active_checked]" value="1" <?php checked($extra['active'], 1); ?>></label></td>
                                            <td><input type="text" name="mode_config[date_range_inventory][extra_services][<?php echo esc_attr((string) $idx); ?>][name]" value="<?php echo esc_attr($extra['name']); ?>" placeholder="<?php esc_attr_e('Airport transfer', 'slotera-booking'); ?>"></td>
                                            <td><input type="text" name="mode_config[date_range_inventory][extra_services][<?php echo esc_attr((string) $idx); ?>][description]" value="<?php echo esc_attr($extra['description']); ?>"></td>
                                            <td><input type="number" step="0.01" min="0" name="mode_config[date_range_inventory][extra_services][<?php echo esc_attr((string) $idx); ?>][price]" value="<?php echo esc_attr((string) $extra['price']); ?>" style="width:110px;"></td>
                                            <td><select name="mode_config[date_range_inventory][extra_services][<?php echo esc_attr((string) $idx); ?>][price_type]"><option value="once" <?php selected($extra['price_type'], 'once'); ?>><?php esc_html_e('Once', 'slotera-booking'); ?></option><option value="per_day" <?php selected($extra['price_type'], 'per_day'); ?>><?php esc_html_e('Per day', 'slotera-booking'); ?></option><option value="per_night" <?php selected($extra['price_type'], 'per_night'); ?>><?php esc_html_e('Per night', 'slotera-booking'); ?></option><option value="per_hour" <?php selected($extra['price_type'], 'per_hour'); ?>><?php esc_html_e('Per hour', 'slotera-booking'); ?></option><option value="per_guest" <?php selected($extra['price_type'], 'per_guest'); ?>><?php esc_html_e('Per guest', 'slotera-booking'); ?></option></select></td>
                                            <td><button type="button" class="button sltr-remove-row"><?php esc_html_e('Remove', 'slotera-booking'); ?></button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <p><button type="button" class="button sltr-add-extra-service"><?php esc_html_e('Add extra service', 'slotera-booking'); ?></button></p>
                                <details class="sltr-advanced-json"><summary><?php esc_html_e('Advanced JSON import/export', 'slotera-booking'); ?></summary><textarea name="mode_config[date_range_inventory][extra_services_json]" rows="4" class="large-text code"><?php echo esc_textarea((string) $sltr_mode_value('date_range_inventory', 'extra_services_json', '')); ?></textarea><span class="description"> <?php esc_html_e('price_type: once, per_day, per_night, per_hour or per_guest.', 'slotera-booking'); ?></span></details>
                                <p><label><?php esc_html_e('Discount', 'slotera-booking'); ?></label><br>
                                    <select name="mode_config[date_range_inventory][discount_type]"><option value="none" <?php selected($sltr_mode_value('date_range_inventory', 'discount_type', 'none'), 'none'); ?>><?php esc_html_e('None', 'slotera-booking'); ?></option><option value="percent" <?php selected($sltr_mode_value('date_range_inventory', 'discount_type', ''), 'percent'); ?>><?php esc_html_e('Percent', 'slotera-booking'); ?></option><option value="fixed" <?php selected($sltr_mode_value('date_range_inventory', 'discount_type', ''), 'fixed'); ?>><?php esc_html_e('Fixed amount', 'slotera-booking'); ?></option></select>
                                    <input type="number" step="0.01" min="0" name="mode_config[date_range_inventory][discount_value]" value="<?php echo esc_attr((string) $sltr_mode_value('date_range_inventory', 'discount_value', 0)); ?>">
                                </p>
                                <p><label><?php esc_html_e('Campaign note', 'slotera-booking'); ?></label><br><input class="regular-text" name="mode_config[date_range_inventory][campaign_note]" value="<?php echo esc_attr((string) $sltr_mode_value('date_range_inventory', 'campaign_note', '')); ?>"><span class="description"> <?php esc_html_e('Optional public urgency note shown on frontend, e.g. Offer ends soon.', 'slotera-booking'); ?></span></p>
                                <?php $sltr_render_low_availability_fields('date_range_inventory'); ?>
                                <?php sltr_render_pricing_adjustment_fields('date_range_inventory', $sltr_mode_value); ?>
                                <?php sltr_render_payment_policy_fields('date_range_inventory', $sltr_mode_value); ?>
                            </div>
                        </section>
                    </div>
                </td>

<?php endif; ?>
