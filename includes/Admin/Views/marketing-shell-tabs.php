<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
$sltr_marketing_section = isset($sltr_marketing_section) ? sanitize_key((string) $sltr_marketing_section) : 'coupons';
if (!in_array($sltr_marketing_section, ['coupons', 'automation', 'promotions'], true)) { $sltr_marketing_section = 'coupons'; }
?>
<header class="sltr-page-header">
    <div class="sltr-page-header__content">
        <h1 class="sltr-page-header__title"><?php esc_html_e('Marketing Emails', 'slotera-booking'); ?></h1>
    </div>
</header>
<nav class="nav-tab-wrapper sltr-admin-tabs sltr-marketing-primary-tabs" aria-label="<?php esc_attr_e('Marketing Emails sections', 'slotera-booking'); ?>">
    <a class="nav-tab <?php echo $sltr_marketing_section === 'coupons' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing')); ?>"><?php esc_html_e('Coupons', 'slotera-booking'); ?></a>
    <a class="nav-tab <?php echo $sltr_marketing_section === 'automation' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation')); ?>"><?php esc_html_e('Marketing automation', 'slotera-booking'); ?></a>
    <a class="nav-tab <?php echo $sltr_marketing_section === 'promotions' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=promotions')); ?>">Promotions</a>
</nav>
