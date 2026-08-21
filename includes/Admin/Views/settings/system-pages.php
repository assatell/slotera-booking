<?php if (!defined('ABSPATH')) { exit; } ?>
    <section id="sltr-system-pages" class="sltr-panel sltr-settings-section" style="margin: 16px 0;">
        <h2><?php esc_html_e('Booking pages', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Slotera creates the required booking pages automatically during installation. Before selecting a custom page here, place the corresponding Slotera shortcode on that page.', 'slotera-booking'); ?></p>

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
                    <td><strong><?php esc_html_e('Contact form', 'slotera-booking'); ?></strong></td>
                    <td><code style="font-size:13px; user-select:all;">[slotera_contact]</code></td>
                    <td><?php esc_html_e('Shows a theme-styled contact form with required Name, Email and Message fields, optional Message Subject, and Anti-spam defaults support.', 'slotera-booking'); ?></td>
                </tr>
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
