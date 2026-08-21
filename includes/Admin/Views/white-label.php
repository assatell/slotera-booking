<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
$message = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_message');
$white_label_service = new \Slotera\Application\Services\WhiteLabelService();
$custom_logo_url = (string) ($settings['white_label_admin_logo_url'] ?? '');
$default_logo_url = $white_label_service->default_admin_logo_url();
$logo_url = $custom_logo_url !== '' ? $custom_logo_url : $default_logo_url;
$product_name = (string) ($settings['white_label_product_name'] ?? 'Slotera Booking');
$brand_name = (string) ($settings['white_label_brand_name'] ?? 'Slotera');
?>
<div class="wrap sltr-admin-wrap sltr-white-label-page sltr-pro-feature-page sltr-full-width-admin">
    <h1>
        <?php if ($white_label_service->admin_logo_url() !== '') : ?>
            <span class="sltr-white-label-logo"><img src="<?php echo esc_url($white_label_service->admin_logo_url()); ?>" alt="Slotera"></span>
        <?php endif; ?>
        <?php esc_html_e('White Label', 'slotera-booking'); ?>
    </h1>

    <?php if ($message === 'saved') : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('White Label settings saved.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>

    <p class="description sltr-pro-intro">
    </p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-pro-form">
        <input type="hidden" name="action" value="sltr_save_white_label_settings">
        <?php wp_nonce_field('sltr_save_white_label_settings'); ?>

        <div class="postbox" style="padding:18px;">
            <h2 style="margin-top:0;"><?php esc_html_e('Branding', 'slotera-booking'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable White Label', 'slotera-booking'); ?></th>
                    <td>
                        <label><input type="checkbox" name="white_label_enabled" value="1" <?php checked((int) ($settings['white_label_enabled'] ?? 0), 1); ?>> <?php esc_html_e('Use custom client/agency branding', 'slotera-booking'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="white_label_brand_name"><?php esc_html_e('Brand name', 'slotera-booking'); ?></label></th>
                    <td><input class="regular-text" type="text" id="white_label_brand_name" name="white_label_brand_name" value="<?php echo esc_attr($brand_name); ?>" placeholder="Slotera"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="white_label_product_name"><?php esc_html_e('Product name', 'slotera-booking'); ?></label></th>
                    <td><input class="regular-text" type="text" id="white_label_product_name" name="white_label_product_name" value="<?php echo esc_attr($product_name); ?>" placeholder="Slotera Booking"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="white_label_admin_logo_url"><?php esc_html_e('Admin logo URL', 'slotera-booking'); ?></label></th>
                    <td>
                        <input class="large-text" type="url" id="white_label_admin_logo_url" name="white_label_admin_logo_url" value="<?php echo esc_attr($logo_url); ?>" placeholder="https://example.com/logo.svg">
                        <p class="description"><?php esc_html_e('The built-in Slotera logo is used by default. Enable White Label and replace this URL with a media-library or CDN image to use your own admin logo.', 'slotera-booking'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="postbox" style="padding:18px;margin-top:16px;">
            <h2 style="margin-top:0;"><?php esc_html_e('Vendor visibility', 'slotera-booking'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Hide Slotera vendor branding', 'slotera-booking'); ?></th>
                    <td>
                        <label><input type="checkbox" name="white_label_hide_vendor_branding" value="1" <?php checked((int) ($settings['white_label_hide_vendor_branding'] ?? 0), 1); ?>> <?php esc_html_e('Hide Slotera plugin metadata, admin footer branding, and the public booking-form attribution. This works independently of custom White Label branding.', 'slotera-booking'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="white_label_admin_footer_text"><?php esc_html_e('Admin footer text', 'slotera-booking'); ?></label></th>
                    <td><input class="large-text" type="text" id="white_label_admin_footer_text" name="white_label_admin_footer_text" value="<?php echo esc_attr((string) ($settings['white_label_admin_footer_text'] ?? 'Powered by Slotera')); ?>" placeholder="Powered by Slotera"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="white_label_plugin_description"><?php esc_html_e('Plugin list description', 'slotera-booking'); ?></label></th>
                    <td><textarea class="large-text" rows="3" id="white_label_plugin_description" name="white_label_plugin_description"><?php echo esc_textarea((string) ($settings['white_label_plugin_description'] ?? 'Complete WordPress booking platform with payments, packages, coupons, marketing automation, analytics and customer management.')); ?></textarea></td>
                </tr>
            </table>
        </div>

        <p><button type="submit" class="button button-primary"><?php esc_html_e('Save White Label settings', 'slotera-booking'); ?></button></p>
    </form>
</div>
