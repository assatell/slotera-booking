<?php if (!defined('ABSPATH')) { exit; } ?>
<?php $sltr_get = wp_unslash($_GET); ?>
<?php if (!empty($sltr_embedded_in_marketing)) : $sltr_marketing_section = 'coupons'; require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-shell-tabs.php'; endif; ?>
<div class="wrap sltr-admin-wrap">
    <?php $sltr_get = wp_unslash($_GET); ?>
    <?php if (!empty($sltr_get['campaign_saved'])) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e('Campaign saved.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['campaign_queued'])) :
        $sltr_queued = absint($sltr_get['queued'] ?? 0);
        $sltr_skipped = absint($sltr_get['skipped'] ?? 0);
        $sltr_skipped_already = absint($sltr_get['skipped_already_queued'] ?? 0);
        $sltr_skipped_unsubscribed = absint($sltr_get['skipped_unsubscribed'] ?? 0);
        $sltr_skipped_invalid_email = absint($sltr_get['skipped_invalid_email'] ?? 0);
        $sltr_skipped_invalid_recipient = absint($sltr_get['skipped_invalid_recipient'] ?? 0);
    ?>
        <?php if ($sltr_queued === 0 && $sltr_skipped > 0) : ?>
            <div class="notice notice-warning is-dismissible"><p>
                <?php printf(esc_html__('Campaign not sent. %d recipient(s) skipped.', 'slotera-booking'), $sltr_skipped); ?>
                <?php if ($sltr_skipped_already > 0) : ?> <?php printf(esc_html__('%d already queued/sent.', 'slotera-booking'), $sltr_skipped_already); ?><?php endif; ?>
                <?php if ($sltr_skipped_unsubscribed > 0) : ?> <?php printf(esc_html__('%d unsubscribed from marketing emails.', 'slotera-booking'), $sltr_skipped_unsubscribed); ?><?php endif; ?>
                <?php if ($sltr_skipped_invalid_email > 0) : ?> <?php printf(esc_html__('%d invalid or missing email.', 'slotera-booking'), $sltr_skipped_invalid_email); ?><?php endif; ?>
                <?php if ($sltr_skipped_invalid_recipient > 0) : ?> <?php printf(esc_html__('%d invalid recipient record.', 'slotera-booking'), $sltr_skipped_invalid_recipient); ?><?php endif; ?>
            </p></div>
        <?php else : ?>
            <div class="notice notice-success is-dismissible"><p><?php printf(esc_html__('Campaign queued. Queued: %1$d, Skipped: %2$d.', 'slotera-booking'), $sltr_queued, $sltr_skipped); ?></p></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($sltr_get['campaign_deleted'])) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e('Campaign deleted.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <header class="sltr-page-header">
        <div class="sltr-page-header__content"><h1 class="sltr-page-header__title"><?php esc_html_e('Coupons', 'slotera-booking'); ?></h1></div>
        <div class="sltr-page-header__actions"><a href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&action=new')); ?>" class="page-title-action"><?php esc_html_e('Add New', 'slotera-booking'); ?></a></div>
    </header>
    <nav class="nav-tab-wrapper sltr-admin-tabs" aria-label="<?php esc_attr_e('Coupons sections', 'slotera-booking'); ?>">
        <a class="nav-tab nav-tab-active" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing')); ?>"><?php esc_html_e('Coupons', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns')); ?>"><?php esc_html_e('Coupon Campaigns', 'slotera-booking'); ?></a>
    </nav>
    <?php if (!empty($sltr_get['updated'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Coupon saved.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <?php if (!empty($sltr_get['deleted'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Coupon deleted.', 'slotera-booking'); ?></p></div><?php endif; ?>
    <table class="widefat striped">
        <thead><tr><th><?php esc_html_e('Code', 'slotera-booking'); ?></th><th><?php esc_html_e('Discount', 'slotera-booking'); ?></th><th><?php esc_html_e('Used', 'slotera-booking'); ?></th><th><?php esc_html_e('Expires', 'slotera-booking'); ?></th><th><?php esc_html_e('Active', 'slotera-booking'); ?></th><th><?php esc_html_e('Actions', 'slotera-booking'); ?></th></tr></thead>
        <tbody>
        <?php if (!empty($coupons)) : foreach ($coupons as $coupon) : ?>
            <tr>
                <td><strong><?php echo esc_html((string) $coupon['code']); ?></strong></td>
                <td><?php echo esc_html((string) $coupon['discount_value'] . ((string) $coupon['discount_type'] === 'percent' ? '%' : ' fixed')); ?></td>
                <td><?php echo esc_html((string) ($coupon['used_count'] ?? 0)); ?><?php echo !empty($coupon['usage_limit']) ? ' / ' . esc_html((string) $coupon['usage_limit']) : ''; ?></td>
                <td><?php echo esc_html((string) ($coupon['expires_at'] ?? '—')); ?></td>
                <td><?php echo !empty($coupon['is_active']) ? esc_html__('Yes', 'slotera-booking') : esc_html__('No', 'slotera-booking'); ?></td>
                <td>
                    <?php $sltr_campaign = $coupon_campaigns[(int) $coupon['id']] ?? null; ?>
                    <?php if ($sltr_campaign) : $sltr_campaign_id = (int) ($sltr_campaign['id'] ?? 0); $sltr_campaign_status = sanitize_key((string) ($sltr_campaign['status'] ?? 'draft')); ?>
                        <div class="sltr-form-actions sltr-form-actions--compact">
                            <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns&action=edit&id=' . $sltr_campaign_id)); ?>"><?php esc_html_e('View', 'slotera-booking'); ?></a>
                            <?php if (in_array($sltr_campaign_status, ['draft', 'completed'], true)) : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="sltr_send_marketing_campaign"><input type="hidden" name="id" value="<?php echo esc_attr((string) $sltr_campaign_id); ?>"><input type="hidden" name="return_coupons" value="1"><?php wp_nonce_field('sltr_send_marketing_campaign_' . $sltr_campaign_id); ?><button class="button button-small button-primary" type="submit"><?php esc_html_e('Send now', 'slotera-booking'); ?></button></form>
                            <?php else : ?>
                                <span class="sltr-status-badge sltr-status-badge--<?php echo esc_attr(sanitize_html_class($sltr_campaign_status)); ?>"><?php echo esc_html($sltr_campaign_status); ?></span>
                            <?php endif; ?>
                            <a class="button button-small submitdelete" data-sltr-confirm="<?php echo esc_attr(__('Delete this campaign and its sending history? The coupon will remain active until its own expiry or deactivation.', 'slotera-booking')); ?>" data-sltr-confirm-title="<?php esc_attr_e('Confirm action', 'slotera-booking'); ?>" data-sltr-confirm-button="<?php esc_attr_e('Confirm', 'slotera-booking'); ?>" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=sltr_delete_marketing_campaign&id=' . $sltr_campaign_id . '&return_coupons=1'), 'sltr_delete_marketing_campaign_' . $sltr_campaign_id)); ?>"><?php esc_html_e('Delete campaign', 'slotera-booking'); ?></a>
                        </div>
                    <?php else : ?>
                        <div class="sltr-form-actions sltr-form-actions--compact">
                            <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns&action=new&coupon_id=' . (int) $coupon['id'])); ?>"><?php esc_html_e('Create campaign', 'slotera-booking'); ?></a>
                            <a class="button button-small submitdelete" data-sltr-confirm="<?php echo esc_attr(__('Delete this coupon? Any customer holding this code will no longer be able to use it.', 'slotera-booking')); ?>" data-sltr-confirm-title="<?php esc_attr_e('Confirm action', 'slotera-booking'); ?>" data-sltr-confirm-button="<?php esc_attr_e('Confirm', 'slotera-booking'); ?>" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=sltr_delete_coupon&id=' . (int) $coupon['id']), 'sltr_delete_coupon_' . (int) $coupon['id'])); ?>"><?php esc_html_e('Delete coupon', 'slotera-booking'); ?></a>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; else : ?>
            <tr><td colspan="6"><div class="sltr-empty-state"><strong class="sltr-empty-state__title"><?php esc_html_e('No coupons yet.', 'slotera-booking'); ?></strong></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
