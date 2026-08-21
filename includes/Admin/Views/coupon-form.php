<?php if (!defined('ABSPATH')) { exit; }
$coupon = $coupon ?: ['id'=>0,'code'=>'','description'=>'','discount_type'=>'percent','discount_value'=>10,'usage_limit'=>0,'usage_limit_per_email'=>0,'expires_at'=>'','package_ids'=>'','min_amount'=>0,'is_active'=>1];
$selected_packages = array_filter(array_map('absint', explode(',', (string) ($coupon['package_ids'] ?? ''))));
?>
<?php if (!empty($sltr_embedded_in_marketing)) : $sltr_marketing_section = 'coupons'; require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-shell-tabs.php'; endif; ?>
<div class="wrap sltr-admin-wrap">
    <?php if (!empty($sltr_get['campaign_requires_coupon'])) : ?><div class="notice notice-info"><p><?php esc_html_e('Create and save a coupon first. The coupon campaign is created from that coupon automatically.', 'slotera-booking'); ?></p></div><?php endif; ?>

    <h1><?php echo !empty($coupon['id']) ? esc_html__('Edit Coupon', 'slotera-booking') : esc_html__('Add Coupon', 'slotera-booking'); ?></h1>
    <div class="sltr-admin-help-card">
        <strong><?php esc_html_e('Coupon setup guide', 'slotera-booking'); ?></strong>
        <p><?php esc_html_e('Coupons are applied before payment. Use limits and package restrictions to control who can use a discount.', 'slotera-booking'); ?></p>
    </div>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_save_coupon">
        <input type="hidden" name="id" value="<?php echo esc_attr((string) ($coupon['id'] ?? 0)); ?>">
        <?php wp_nonce_field('sltr_save_coupon'); ?>
        <table class="form-table" role="presentation">
            <tr><th><label for="sltr-coupon-code"><?php esc_html_e('Code', 'slotera-booking'); ?></label></th><td><input class="regular-text" id="sltr-coupon-code" name="code" value="<?php echo esc_attr((string) ($coupon['code'] ?? '')); ?>" required><p class="description"><?php esc_html_e('Example: WELCOME10. Codes are stored uppercase.', 'slotera-booking'); ?></p></td></tr>
            <tr><th><label for="sltr-coupon-description"><?php esc_html_e('Description', 'slotera-booking'); ?></label></th><td><textarea class="large-text" rows="3" id="sltr-coupon-description" name="description"><?php echo esc_textarea((string) ($coupon['description'] ?? '')); ?></textarea></td></tr>
            <tr><th><?php esc_html_e('Discount', 'slotera-booking'); ?></th><td><select name="discount_type"><option value="percent" <?php selected($coupon['discount_type'] ?? '', 'percent'); ?>><?php esc_html_e('Percent', 'slotera-booking'); ?></option><option value="fixed" <?php selected($coupon['discount_type'] ?? '', 'fixed'); ?>><?php esc_html_e('Fixed amount', 'slotera-booking'); ?></option></select> <input type="number" min="0" step="0.01" name="discount_value" value="<?php echo esc_attr((string) ($coupon['discount_value'] ?? 0)); ?>"><p class="description"><?php esc_html_e('Percent discounts use values like 10 for 10%. Fixed discounts use the store currency.', 'slotera-booking'); ?></p></td></tr>
            <tr><th><label for="sltr-coupon-min"><?php esc_html_e('Minimum amount', 'slotera-booking'); ?></label></th><td><input id="sltr-coupon-min" type="number" min="0" step="0.01" name="min_amount" value="<?php echo esc_attr((string) ($coupon['min_amount'] ?? 0)); ?>"><p class="description"><?php esc_html_e('Set 0 to allow the coupon for any booking total.', 'slotera-booking'); ?></p></td></tr>
            <tr><th><label for="sltr-coupon-limit"><?php esc_html_e('Usage limit', 'slotera-booking'); ?></label></th><td><input id="sltr-coupon-limit" type="number" min="0" step="1" name="usage_limit" value="<?php echo esc_attr((string) ($coupon['usage_limit'] ?? 0)); ?>"><p class="description"><?php esc_html_e('0 means unlimited.', 'slotera-booking'); ?></p></td></tr>
            <tr><th><label for="sltr-coupon-email-limit"><?php esc_html_e('Usage per email', 'slotera-booking'); ?></label></th><td><input id="sltr-coupon-email-limit" type="number" min="0" step="1" name="usage_limit_per_email" value="<?php echo esc_attr((string) ($coupon['usage_limit_per_email'] ?? 0)); ?>"><p class="description"><?php esc_html_e('0 means unlimited.', 'slotera-booking'); ?></p></td></tr>
            <tr><th><label for="sltr-coupon-expires"><?php esc_html_e('Expires at', 'slotera-booking'); ?></label></th><td><input id="sltr-coupon-expires" type="date" name="expires_at" value="<?php echo esc_attr((string) ($coupon['expires_at'] ?? '')); ?>"><p class="description"><?php esc_html_e('Leave empty if the coupon should not expire automatically.', 'slotera-booking'); ?></p></td></tr>
            <tr><th><?php esc_html_e('Packages', 'slotera-booking'); ?></th><td>
                <p class="description"><?php esc_html_e('Leave all unchecked to allow this coupon for all packages.', 'slotera-booking'); ?></p>
                <?php foreach ($packages as $package) : ?>
                    <label style="display:block;margin:4px 0;"><input type="checkbox" name="package_ids[]" value="<?php echo esc_attr((string) $package['id']); ?>" <?php checked(in_array((int) $package['id'], $selected_packages, true)); ?>> <?php echo esc_html((string) $package['title']); ?></label>
                <?php endforeach; ?>
            </td></tr>
            <tr><th><?php esc_html_e('Active', 'slotera-booking'); ?></th><td><label><input type="checkbox" name="is_active" value="1" <?php checked(!empty($coupon['is_active'])); ?>> <?php esc_html_e('Coupon is active', 'slotera-booking'); ?></label></td></tr>
        </table>
        <?php submit_button(__('Save Coupon', 'slotera-booking')); ?>
    </form>
</div>
