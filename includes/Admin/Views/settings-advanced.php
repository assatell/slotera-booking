<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap sltr-admin">
    <h1><?php esc_html_e('Settings', 'slotera-booking'); ?></h1>
    <h2 class="nav-tab-wrapper">
        <a class="nav-tab" href="<?php echo esc_url(admin_url('admin.php?page=slotera-settings')); ?>"><?php esc_html_e('General Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'seo'], admin_url('admin.php'))); ?>"><?php esc_html_e('SEO Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'email'], admin_url('admin.php'))); ?>"><?php esc_html_e('Email settings', 'slotera-booking'); ?></a>
        <a class="nav-tab nav-tab-active" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'advanced'], admin_url('admin.php'))); ?>"><?php esc_html_e('Advanced', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'security'], admin_url('admin.php'))); ?>"><?php esc_html_e('Security', 'slotera-booking'); ?></a>
    </h2>

    <?php $sltr_message = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_message'); ?>
    <?php if ($sltr_message !== '') : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Saved.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>

    <?php
    $sltr_social_login_service = class_exists('Slotera\Application\Services\SocialLoginService') ? new \Slotera\Application\Services\SocialLoginService() : null;
    $sltr_social_google_callback = $sltr_social_login_service ? $sltr_social_login_service->callback_url('google') : home_url('/slotera-social-login/google/callback/');
    $sltr_social_facebook_callback = $sltr_social_login_service ? $sltr_social_login_service->callback_url('facebook') : home_url('/slotera-social-login/facebook/callback/');
    $sltr_social_apple_callback = $sltr_social_login_service ? $sltr_social_login_service->callback_url('apple') : home_url('/slotera-social-login/apple/callback/');
    ?>

    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/social-login.php')) { require $sltr_view; } ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/social-share.php')) { require $sltr_view; } ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/advanced.php')) { require $sltr_view; } ?>
</div>
