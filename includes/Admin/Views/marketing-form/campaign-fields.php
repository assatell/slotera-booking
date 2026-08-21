<?php if (!defined('ABSPATH')) { exit; } ?>
    <section class="sltr-panel"><h2><?php esc_html_e('Campaign', 'slotera-booking'); ?></h2><div class="sltr-panel__body"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_save_marketing_campaign">
        <input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>">
        <input type="hidden" name="campaign_source" value="<?php echo esc_attr((string) ($campaign['source'] ?? 'coupon')); ?>">
        <?php wp_nonce_field('sltr_save_marketing_campaign'); ?>
        <table class="form-table" role="presentation"><tbody>
            <tr><th scope="row"><label for="name"><?php esc_html_e('Campaign name', 'slotera-booking'); ?></label></th><td><input class="regular-text" id="name" name="name" value="<?php echo esc_attr((string) ($campaign['name'] ?? '')); ?>" required></td></tr>
            <tr><th scope="row"><label for="template_key"><?php esc_html_e('Email template', 'slotera-booking'); ?></label></th><td><select id="template_key" name="template_key">
                <?php foreach ($templates as $key => $template) : ?><option value="<?php echo esc_attr((string) $key); ?>" <?php selected($selected_template_key, (string) $key); ?>><?php echo esc_html((string) ($template['title'] ?? $key)); ?></option><?php endforeach; ?>
            </select><p class="description"><?php esc_html_e('Choose “Marketing — Promo” for promotional campaigns. It supports {headline}, {message}, {submessage}, {coupon_code} and {cta_button}.', 'slotera-booking'); ?></p></td></tr>
            <tr><th scope="row"><label for="subject_override"><?php esc_html_e('Subject override', 'slotera-booking'); ?></label></th><td><input class="regular-text" id="subject_override" name="subject_override" value="<?php echo esc_attr((string) ($campaign['subject_override'] ?? '')); ?>"><p class="description"><?php esc_html_e('Leave empty to use the selected template subject.', 'slotera-booking'); ?></p></td></tr>

            <tr><th scope="row"><?php esc_html_e('Marketing content', 'slotera-booking'); ?></th><td>
                <p class="description"><?php esc_html_e('These fields are used by the Marketing — Promo template and available as {headline}, {message}, and {submessage}.', 'slotera-booking'); ?></p>
                <p><label for="marketing_headline"><strong><?php esc_html_e('Headline', 'slotera-booking'); ?></strong></label><br>
                <input class="large-text" id="marketing_headline" name="marketing_headline" value="<?php echo esc_attr((string) ($campaign['marketing_headline'] ?? '')); ?>" placeholder="<?php esc_attr_e('We miss you 👋', 'slotera-booking'); ?>"></p>
                <p><label for="marketing_message"><strong><?php esc_html_e('Message', 'slotera-booking'); ?></strong></label><br>
                <textarea class="large-text" rows="4" id="marketing_message" name="marketing_message" placeholder="<?php esc_attr_e('It has been a while since your last booking. Here is a special offer for your next visit.', 'slotera-booking'); ?>"><?php echo esc_textarea((string) ($campaign['marketing_message'] ?? '')); ?></textarea></p>
                <p><label for="marketing_submessage"><strong><?php esc_html_e('Submessage', 'slotera-booking'); ?></strong></label><br>
                <textarea class="large-text" rows="3" id="marketing_submessage" name="marketing_submessage" placeholder="<?php esc_attr_e('Use your code before it expires and book your next appointment when it suits you.', 'slotera-booking'); ?>"><?php echo esc_textarea((string) ($campaign['marketing_submessage'] ?? '')); ?></textarea></p>
            </td></tr>
            <tr><th scope="row"><label for="audience_type"><?php esc_html_e('Audience', 'slotera-booking'); ?></label></th><td><select id="audience_type" name="audience_type">
                <option value="all" <?php selected((string) ($campaign['audience_type'] ?? 'all'), 'all'); ?>><?php esc_html_e('All customers', 'slotera-booking'); ?></option>
                <option value="package" <?php selected((string) ($campaign['audience_type'] ?? ''), 'package'); ?>><?php esc_html_e('Customers by package', 'slotera-booking'); ?></option>
                <option value="completed" <?php selected((string) ($campaign['audience_type'] ?? ''), 'completed'); ?>><?php esc_html_e('Customers with completed bookings', 'slotera-booking'); ?></option>
                <option value="inactive_30" <?php selected((string) ($campaign['audience_type'] ?? ''), 'inactive_30'); ?>><?php esc_html_e('Inactive customers — 30 days', 'slotera-booking'); ?></option>
                <option value="advanced" <?php selected((string) ($campaign['audience_type'] ?? ''), 'advanced'); ?> <?php disabled(!$advanced_marketing_allowed); ?>><?php esc_html_e('Advanced filters', 'slotera-booking'); ?><?php if (!$advanced_marketing_allowed) : ?> — <?php esc_html_e('locked', 'slotera-booking'); ?><?php endif; ?></option>
            </select><?php if ($id > 0) : ?><p class="description"><?php printf(esc_html__('Current matching audience: %d unique emails.', 'slotera-booking'), (int) $audience_count); ?></p><?php endif; ?></td></tr>
            <tr><th scope="row"><?php esc_html_e('Advanced audience filters', 'slotera-booking'); ?></th><td>
                <fieldset class="sltr-admin-inline-fields">
                    <p><strong><?php esc_html_e('Booking statuses', 'slotera-booking'); ?></strong></p>
                    <?php foreach ($booking_status_options as $value => $label) : ?>
                        <label class="sltr-option-inline"><input type="checkbox" name="audience_statuses[]" value="<?php echo esc_attr((string) $value); ?>" <?php checked(in_array((string) $value, $selected_statuses, true)); ?>> <?php echo esc_html((string) $label); ?></label>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e('Leave empty to include every booking status. These filters are used when Audience is set to Advanced filters.', 'slotera-booking'); ?></p>

                    <p><strong><?php esc_html_e('Payment statuses', 'slotera-booking'); ?></strong></p>
                    <?php foreach ($payment_status_options as $value => $label) : ?>
                        <label class="sltr-option-inline"><input type="checkbox" name="audience_payment_statuses[]" value="<?php echo esc_attr((string) $value); ?>" <?php checked(in_array((string) $value, $selected_payment_statuses, true)); ?>> <?php echo esc_html((string) $label); ?></label>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e('Leave empty to include every payment status.', 'slotera-booking'); ?></p>

                    <p><strong><?php esc_html_e('Last booking', 'slotera-booking'); ?></strong></p>
                    <select name="audience_last_booking_mode">
                        <option value="any" <?php selected((string) ($campaign['audience_last_booking_mode'] ?? 'any'), 'any'); ?>><?php esc_html_e('Any time', 'slotera-booking'); ?></option>
                        <option value="within_days" <?php selected((string) ($campaign['audience_last_booking_mode'] ?? ''), 'within_days'); ?>><?php esc_html_e('Within the last X days', 'slotera-booking'); ?></option>
                        <option value="older_than_days" <?php selected((string) ($campaign['audience_last_booking_mode'] ?? ''), 'older_than_days'); ?>><?php esc_html_e('Older than X days', 'slotera-booking'); ?></option>
                    </select>
                    <input class="sltr-input--xs" type="number" min="1" max="3650" name="audience_last_booking_days" value="<?php echo esc_attr((string) max(1, (int) ($campaign['audience_last_booking_days'] ?? 30))); ?>"> <?php esc_html_e('days', 'slotera-booking'); ?>

                    <p><strong><?php esc_html_e('Booking count', 'slotera-booking'); ?></strong></p>
                    <label><?php esc_html_e('Min', 'slotera-booking'); ?> <input class="sltr-input--xs" type="number" min="0" name="audience_min_bookings" value="<?php echo esc_attr((string) (int) ($campaign['audience_min_bookings'] ?? 0)); ?>"></label>
                    <label class="sltr-field-inline-spaced"><?php esc_html_e('Max', 'slotera-booking'); ?> <input class="sltr-input--xs" type="number" min="0" name="audience_max_bookings" value="<?php echo esc_attr((string) (int) ($campaign['audience_max_bookings'] ?? 0)); ?>"></label>
                    <p class="description"><?php esc_html_e('Use 0 for no limit.', 'slotera-booking'); ?></p>

                    <p><strong><?php esc_html_e('Total spent', 'slotera-booking'); ?></strong></p>
                    <label><?php esc_html_e('Min', 'slotera-booking'); ?> <input class="sltr-input--sm" type="number" min="0" step="0.01" name="audience_min_spent" value="<?php echo esc_attr((string) (float) ($campaign['audience_min_spent'] ?? 0)); ?>"></label>
                    <label class="sltr-field-inline-spaced"><?php esc_html_e('Max', 'slotera-booking'); ?> <input class="sltr-input--sm" type="number" min="0" step="0.01" name="audience_max_spent" value="<?php echo esc_attr((string) (float) ($campaign['audience_max_spent'] ?? 0)); ?>"></label>

                    <p><strong><?php esc_html_e('Coupon behavior', 'slotera-booking'); ?></strong></p>
                    <select name="audience_coupon_filter">
                        <option value="any" <?php selected((string) ($campaign['audience_coupon_filter'] ?? 'any'), 'any'); ?>><?php esc_html_e('Any customer', 'slotera-booking'); ?></option>
                        <option value="used_coupon" <?php selected((string) ($campaign['audience_coupon_filter'] ?? ''), 'used_coupon'); ?>><?php esc_html_e('Used any coupon before', 'slotera-booking'); ?></option>
                        <option value="never_used_coupon" <?php selected((string) ($campaign['audience_coupon_filter'] ?? ''), 'never_used_coupon'); ?>><?php esc_html_e('Never used a coupon', 'slotera-booking'); ?></option>
                        <option value="used_selected_coupon" <?php selected((string) ($campaign['audience_coupon_filter'] ?? ''), 'used_selected_coupon'); ?>><?php esc_html_e('Used selected coupon before', 'slotera-booking'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('For “Used selected coupon before”, choose the coupon in Promotion below.', 'slotera-booking'); ?></p>
                </fieldset>
            </td></tr>
            <tr><th scope="row"><?php esc_html_e('Packages', 'slotera-booking'); ?></th><td><input type="hidden" name="package_id" value="<?php echo esc_attr((string) (count($sltr_bound_coupon_package_ids ?? []) === 1 ? (int) $sltr_bound_coupon_package_ids[0] : 0)); ?>"><strong><?php echo esc_html(!empty($sltr_bound_coupon_package_labels) ? implode(', ', $sltr_bound_coupon_package_labels) : __('Any package', 'slotera-booking')); ?></strong><p class="description"><?php esc_html_e('Inherited from this coupon and cannot be changed in the campaign.', 'slotera-booking'); ?></p></td></tr>
            <tr><th scope="row"><?php esc_html_e('Promotion', 'slotera-booking'); ?></th><td><input type="hidden" name="coupon_id" value="<?php echo esc_attr((string) ($sltr_bound_coupon['id'] ?? 0)); ?>"><strong><?php echo esc_html((string) ($sltr_bound_coupon['code'] ?? '')); ?></strong><p class="description"><?php esc_html_e('This campaign sends the coupon you just created or selected. Promotion cannot be changed here.', 'slotera-booking'); ?></p></td></tr>
            <tr><th scope="row"><?php esc_html_e('Personal coupons', 'slotera-booking'); ?></th><td><label><input type="checkbox" name="generate_unique_coupons" value="1" <?php checked((int) ($campaign['generate_unique_coupons'] ?? 0), 1); ?> <?php disabled(!$unique_coupons_allowed); ?>> <?php esc_html_e('Generate unique coupon per recipient', 'slotera-booking'); ?></label><p class="description"><?php echo $unique_coupons_allowed ? esc_html__('When enabled, Slotera creates a one-use personal coupon for each queued recipient using the selected coupon as a template. {coupon_code} becomes unique in every email.', 'slotera-booking') : esc_html__('Locked during the limited grace period. Regular attached coupons still work.', 'slotera-booking'); ?></p></td></tr>
            <tr><th scope="row"><?php esc_html_e('CTA button', 'slotera-booking'); ?></th><td>
                <label><input type="checkbox" name="cta_enabled" value="1" <?php checked((int) ($campaign['cta_enabled'] ?? 1), 1); ?>> <?php esc_html_e('Add “Book now” CTA button to this campaign email', 'slotera-booking'); ?></label>
                <p class="description"><?php esc_html_e('Use {cta_button}, {booking_url}, {package_url}, or {cta_url} inside HTML templates. If {cta_button} is not present, Slotera appends the button below the email content.', 'slotera-booking'); ?></p>
            </td></tr>
            <?php $sltr_campaign_cta_default = sltr_t('Book now', 'emails', \Slotera\Application\Services\EmailTemplateRegistry::runtime_locale()); ?>
            <tr><th scope="row"><label for="cta_label"><?php esc_html_e('CTA label', 'slotera-booking'); ?></label></th><td><input class="regular-text" id="cta_label" name="cta_label" value="<?php echo esc_attr(trim((string) ($campaign['cta_label'] ?? '')) !== '' ? (string) $campaign['cta_label'] : $sltr_campaign_cta_default); ?>" placeholder="<?php echo esc_attr($sltr_campaign_cta_default); ?>"><p class="description"><?php esc_html_e('Leave unchanged or empty to use the default text translated to the email language.', 'slotera-booking'); ?></p></td></tr>
            <tr><th scope="row"><label for="cta_url_type"><?php esc_html_e('CTA link target', 'slotera-booking'); ?></label></th><td><select id="cta_url_type" name="cta_url_type">
                <option value="booking" <?php selected((string) ($campaign['cta_url_type'] ?? 'booking'), 'booking'); ?>><?php esc_html_e('Booking page', 'slotera-booking'); ?></option>
                <option value="package" <?php selected((string) ($campaign['cta_url_type'] ?? ''), 'package'); ?>><?php esc_html_e('Package solo page', 'slotera-booking'); ?></option>
                <option value="custom" <?php selected((string) ($campaign['cta_url_type'] ?? ''), 'custom'); ?>><?php esc_html_e('Custom URL', 'slotera-booking'); ?></option>
            </select><p class="description"><?php esc_html_e('Package page works best when the campaign is filtered by one package. Otherwise Slotera falls back to the booking page.', 'slotera-booking'); ?></p></td></tr>
            <tr><th scope="row"><label for="cta_custom_url"><?php esc_html_e('Custom CTA URL', 'slotera-booking'); ?></label></th><td><input class="regular-text code" id="cta_custom_url" name="cta_custom_url" value="<?php echo esc_url((string) ($campaign['cta_custom_url'] ?? '')); ?>" placeholder="https://example.com/booking"></td></tr>
        </tbody></table>
        <div class="sltr-form-actions"><?php submit_button($id > 0 ? __('Save campaign', 'slotera-booking') : __('Create campaign', 'slotera-booking')); ?></div>
    </form></div></section>
