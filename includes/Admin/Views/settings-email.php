<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap sltr-admin sltr-page-stack">
    <header class="sltr-page-header"><div class="sltr-page-header__content"><h1 class="sltr-page-header__title"><?php esc_html_e('Email settings', 'slotera-booking'); ?></h1><p class="sltr-page-header__description"><?php esc_html_e('Configure sender identity, SMTP delivery and email tests.', 'slotera-booking'); ?></p></div></header>
    <h2 class="nav-tab-wrapper">
        <a class="nav-tab" href="<?php echo esc_url(admin_url('admin.php?page=slotera-settings')); ?>"><?php esc_html_e('General Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'seo'], admin_url('admin.php'))); ?>"><?php esc_html_e('SEO Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab nav-tab-active" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'email'], admin_url('admin.php'))); ?>"><?php esc_html_e('Email settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'advanced'], admin_url('admin.php'))); ?>"><?php esc_html_e('Advanced', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'security'], admin_url('admin.php'))); ?>"><?php esc_html_e('Security', 'slotera-booking'); ?></a>
    </h2>

    <?php $sltr_message = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_message'); ?>
    <?php if ($sltr_message !== '') : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Saved.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>

    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/email.php')) { require $sltr_view; } ?>
</div>
