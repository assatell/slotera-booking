<?php
if (!defined('ABSPATH')) { exit; }

$sltr_contact_page_rows = json_decode((string) ($settings['contact_page_details_json'] ?? '[]'), true);
$sltr_contact_page_rows = is_array($sltr_contact_page_rows) ? $sltr_contact_page_rows : [];
$sltr_contact_page_address = '';
$sltr_contact_page_details = [];
$sltr_contact_page_socials = [];
foreach ($sltr_contact_page_rows as $sltr_contact_page_row) {
    if (!is_array($sltr_contact_page_row)) { continue; }
    $sltr_contact_page_type = (string) ($sltr_contact_page_row['type'] ?? 'contact');
    if ($sltr_contact_page_type === 'address') {
        $sltr_contact_page_address = (string) ($sltr_contact_page_row['value'] ?? '');
    } elseif ($sltr_contact_page_type === 'social') {
        $sltr_contact_page_socials[] = $sltr_contact_page_row;
    } else {
        $sltr_contact_page_details[] = $sltr_contact_page_row;
    }
}
$sltr_contact_page_social_platforms = [
    'instagram' => 'Instagram',
    'facebook' => 'Facebook',
    'linkedin' => 'LinkedIn',
    'x' => 'X (Twitter)',
    'youtube' => 'YouTube',
    'tiktok' => 'TikTok',
];
$sltr_contact_page_image_id = (int) ($settings['contact_page_image_id'] ?? 0);
$sltr_contact_page_image_url = $sltr_contact_page_image_id > 0 ? wp_get_attachment_image_url($sltr_contact_page_image_id, 'large') : '';
if (!$sltr_contact_page_image_url) {
    $sltr_contact_page_image_url = SLTR_PLUGIN_URL . 'assets/images/contact-block-default.webp';
}
?>
    <section id="sltr-system-pages" class="sltr-panel sltr-settings-section" style="margin: 16px 0;">
        <h2><?php esc_html_e('Booking pages', 'slotera-booking'); ?></h2>
        <p class="sltr-system-pages-notice"><strong><?php esc_html_e('NB!', 'slotera-booking'); ?></strong> <?php esc_html_e('Slotera creates the required booking pages automatically during installation. For correct and reliable plugin operation, we recommend that you first create a custom page, place the corresponding Slotera shortcode on it, and only then select that page here.', 'slotera-booking'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_save_system_pages">
            <input type="hidden" name="return_to" value="sltr-system-pages">
            <?php wp_nonce_field('sltr_save_system_pages'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="sltr-booking-page"><?php esc_html_e('Booking page', 'slotera-booking'); ?></label></th>
                        <td>
                            <?php wp_dropdown_pages(['name' => 'booking_page_id', 'id' => 'sltr-booking-page', 'selected' => (int) ($settings['booking_page_id'] ?? 0), 'show_option_none' => __('— Select page —', 'slotera-booking'), 'option_none_value' => '0']); ?>
                            <p class="description"><code>[slotera_booking]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-categories-page"><?php esc_html_e('Categories page', 'slotera-booking'); ?></label></th>
                        <td>
                            <?php wp_dropdown_pages(['name' => 'categories_page_id', 'id' => 'sltr-categories-page', 'selected' => (int) ($settings['categories_page_id'] ?? 0), 'show_option_none' => __('— Select page —', 'slotera-booking'), 'option_none_value' => '0']); ?>
                            <p class="description"><code>[slotera_categories]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-thank-you-page"><?php esc_html_e('Thank you page', 'slotera-booking'); ?></label></th>
                        <td>
                            <?php wp_dropdown_pages(['name' => 'thank_you_page_id', 'id' => 'sltr-thank-you-page', 'selected' => (int) ($settings['thank_you_page_id'] ?? 0), 'show_option_none' => __('— Select page —', 'slotera-booking'), 'option_none_value' => '0']); ?>
                            <p class="description"><code>[slotera_thank_you]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-checkout-page"><?php esc_html_e('Checkout page', 'slotera-booking'); ?></label></th>
                        <td>
                            <?php wp_dropdown_pages(['name' => 'checkout_page_id', 'id' => 'sltr-checkout-page', 'selected' => (int) ($settings['checkout_page_id'] ?? 0), 'show_option_none' => __('— Select page —', 'slotera-booking'), 'option_none_value' => '0']); ?>
                            <p class="description"><code>[slotera_checkout]</code></p>
                            <p class="description"><?php esc_html_e('Used as a review step when coupons, VAT/taxes, deposits or partial payments need confirmation before the final thank-you page.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-login-page"><?php esc_html_e('Client login page', 'slotera-booking'); ?></label></th>
                        <td>
                            <?php wp_dropdown_pages(['name' => 'login_page_id', 'id' => 'sltr-login-page', 'selected' => (int) ($settings['login_page_id'] ?? 0), 'show_option_none' => __('— Select page —', 'slotera-booking'), 'option_none_value' => '0']); ?>
                            <p class="description"><code>[slotera_login]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-account-page"><?php esc_html_e('Client account page', 'slotera-booking'); ?></label></th>
                        <td>
                            <?php wp_dropdown_pages(['name' => 'account_page_id', 'id' => 'sltr-account-page', 'selected' => (int) ($settings['account_page_id'] ?? 0), 'show_option_none' => __('— Select page —', 'slotera-booking'), 'option_none_value' => '0']); ?>
                            <p class="description"><code>[slotera_account]</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sltr-contact-page"><?php esc_html_e('Contact page', 'slotera-booking'); ?></label></th>
                        <td>
                            <?php wp_dropdown_pages(['name' => 'contact_page_id', 'id' => 'sltr-contact-page', 'selected' => (int) ($settings['contact_page_id'] ?? 0), 'show_option_none' => __('— Select page —', 'slotera-booking'), 'option_none_value' => '0']); ?>
                            <p class="description"><code>[slotera_contact]</code></p>

                            <div id="sltr-system-contact-settings" class="sltr-system-contact-settings">
                                <h4><?php esc_html_e('Contact page image', 'slotera-booking'); ?></h4>
                                <input type="hidden" id="sltr-system-contact-image-id" name="contact_page_image_id" value="<?php echo esc_attr((string) $sltr_contact_page_image_id); ?>">
                                <div class="sltr-contact-image-preview sltr-media-preview-large sltr-focus-enabled">
                                    <div class="sltr-contact-image-preview-frame">
                                        <img id="sltr-system-contact-image-preview" src="<?php echo esc_url($sltr_contact_page_image_url); ?>" alt="">
                                    </div>
                                </div>
                                <p class="sltr-contact-image-actions">
                                    <button type="button" class="button" id="sltr-system-contact-replace-image"><?php esc_html_e('Replace image', 'slotera-booking'); ?></button>
                                    <button type="button" class="button" id="sltr-system-contact-use-default" data-default-url="<?php echo esc_url(SLTR_PLUGIN_URL . 'assets/images/contact-block-default.webp'); ?>"><?php esc_html_e('Use default', 'slotera-booking'); ?></button>
                                </p>
                                <p class="description"><?php esc_html_e('The default image is used until you select a custom image.', 'slotera-booking'); ?></p>

                                <h4><?php esc_html_e('Address', 'slotera-booking'); ?></h4>
                                <input type="text" id="sltr-system-contact-address" class="large-text" value="<?php echo esc_attr($sltr_contact_page_address); ?>" placeholder="<?php esc_attr_e('Street, city, postal code, country', 'slotera-booking'); ?>">

                                <h4><?php esc_html_e('Google Maps link', 'slotera-booking'); ?></h4>
                                <input type="url" class="large-text code" name="contact_page_map" value="<?php echo esc_attr((string) ($settings['contact_page_map'] ?? '')); ?>" placeholder="https://maps.app.goo.gl/............">
                                <p class="description"><?php esc_html_e('Paste the normal Google Maps page/share link. Slotera does not embed Google Maps; visitors open this link in a new window.', 'slotera-booking'); ?> <a href="https://www.google.com/maps" target="_blank" rel="noopener noreferrer">Google Maps</a></p>

                                <h4><?php esc_html_e('Contact details', 'slotera-booking'); ?></h4>
                                <input type="hidden" id="sltr-system-contact-details-json" name="contact_page_details_json" value="<?php echo esc_attr((string) ($settings['contact_page_details_json'] ?? '[]')); ?>">
                                <div id="sltr-system-contact-detail-rows">
                                    <?php foreach ($sltr_contact_page_details as $row) : if (!is_array($row)) continue; ?>
                                        <div class="sltr-contact-detail-row">
                                            <input type="text" class="regular-text sltr-contact-detail-label" value="<?php echo esc_attr((string) ($row['label'] ?? '')); ?>" placeholder="<?php esc_attr_e('Mobile, Office, Manager…', 'slotera-booking'); ?>">
                                            <input type="text" class="regular-text sltr-contact-detail-value" value="<?php echo esc_attr((string) ($row['value'] ?? '')); ?>" placeholder="<?php esc_attr_e('Phone number or contact detail', 'slotera-booking'); ?>">
                                            <button type="button" class="button-link-delete sltr-system-contact-remove-detail"><?php esc_html_e('Remove', 'slotera-booking'); ?></button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="button" id="sltr-system-contact-add-detail"><?php esc_html_e('Add contact field', 'slotera-booking'); ?></button>

                                <h4><?php esc_html_e('Social links', 'slotera-booking'); ?></h4>
                                <div id="sltr-system-contact-social-rows">
                                    <?php foreach ($sltr_contact_page_socials as $row) : ?>
                                        <?php $sltr_contact_page_social_platform = sanitize_key((string) ($row['platform'] ?? 'instagram')); ?>
                                        <div class="sltr-contact-social-row">
                                            <select class="sltr-contact-social-platform">
                                                <?php foreach ($sltr_contact_page_social_platforms as $sltr_social_key => $sltr_social_label) : ?>
                                                    <option value="<?php echo esc_attr($sltr_social_key); ?>" <?php selected($sltr_contact_page_social_platform, $sltr_social_key); ?>><?php echo esc_html($sltr_social_label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="url" class="regular-text sltr-contact-social-url" value="<?php echo esc_attr((string) ($row['url'] ?? '')); ?>" placeholder="https://">
                                            <button type="button" class="button-link-delete sltr-system-contact-remove-social"><?php esc_html_e('Remove', 'slotera-booking'); ?></button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="button" id="sltr-system-contact-add-social"><?php esc_html_e('Add social link', 'slotera-booking'); ?></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p><button class="button button-primary"><?php esc_html_e('Save booking pages', 'slotera-booking'); ?></button></p>
        </form>

        <hr style="margin: 24px 0;">

        <h3><?php esc_html_e('Additional shortcodes', 'slotera-booking'); ?></h3>
        <table class="widefat striped" style="margin-top: 12px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Purpose', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Shortcode', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Description', 'slotera-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e('Single package booking page', 'slotera-booking'); ?></strong></td>
                    <td><code style="font-size:13px; user-select:all;">[slotera_booking package_id="123"]</code></td>
                    <td><?php esc_html_e('Shows booking form for one specific package. Replace 123 with package ID.', 'slotera-booking'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('Category packages page', 'slotera-booking'); ?></strong></td>
                    <td><code style="font-size:13px; user-select:all;">[slotera_category category_id="5"]</code></td>
                    <td><?php esc_html_e('Shows packages from one category. Replace 5 with category ID.', 'slotera-booking'); ?></td>
                </tr>
            </tbody>
        </table>
    </section>
