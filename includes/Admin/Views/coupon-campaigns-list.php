<?php if (!defined('ABSPATH')) { exit; } ?>
<?php $sltr_get = wp_unslash($_GET); ?>
<?php if (!empty($sltr_embedded_in_marketing)) : $sltr_marketing_section = 'coupons'; require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-shell-tabs.php'; endif; ?>
<div class="wrap sltr-admin-wrap">
    <header class="sltr-page-header">
        <div class="sltr-page-header__content"><h1 class="sltr-page-header__title"><?php esc_html_e('Coupons', 'slotera-booking'); ?></h1></div>
    </header>
    <nav class="nav-tab-wrapper sltr-admin-tabs" aria-label="<?php esc_attr_e('Coupons sections', 'slotera-booking'); ?>">
        <a class="nav-tab" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing')); ?>"><?php esc_html_e('Coupons', 'slotera-booking'); ?></a>
        <a class="nav-tab nav-tab-active" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns')); ?>"><?php esc_html_e('Coupon Campaigns', 'slotera-booking'); ?></a>
    </nav>
    <?php if (!empty($sltr_get['deleted'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Campaign deleted.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['binding_error'])) : ?><div class="notice notice-error"><p><?php esc_html_e('Coupon campaign could not be saved because its source coupon is missing.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['campaign_coupon_missing'])) : ?><div class="notice notice-error"><p><?php esc_html_e('This campaign no longer has a valid source coupon and cannot be edited.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php $sltr_history_context = 'coupon'; require SLTR_PLUGIN_DIR . 'includes/Admin/Views/campaign-history-table.php'; ?>
</div>
