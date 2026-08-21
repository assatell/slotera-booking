<?php
if (!defined('ABSPATH')) {
    exit;
}
$sltr_security_tab = isset($_GET['security_tab']) ? sanitize_key((string) wp_unslash($_GET['security_tab'])) : 'protection';
if (!in_array($sltr_security_tab, ['protection', 'privacy'], true)) {
    $sltr_security_tab = 'protection';
}
?>
<div class="wrap sltr-admin">
    <h1><?php esc_html_e('Settings', 'slotera-booking'); ?></h1>
    <h2 class="nav-tab-wrapper">
        <a class="nav-tab" href="<?php echo esc_url(admin_url('admin.php?page=slotera-settings')); ?>"><?php esc_html_e('General Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'seo'], admin_url('admin.php'))); ?>"><?php esc_html_e('SEO Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'email'], admin_url('admin.php'))); ?>"><?php esc_html_e('Email settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'advanced'], admin_url('admin.php'))); ?>"><?php esc_html_e('Advanced', 'slotera-booking'); ?></a>
        <a class="nav-tab nav-tab-active" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'security'], admin_url('admin.php'))); ?>"><?php esc_html_e('Security', 'slotera-booking'); ?></a>
    </h2>

    <h2 class="nav-tab-wrapper" style="margin-top: 18px;">
        <a class="nav-tab <?php echo $sltr_security_tab === 'protection' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'security', 'security_tab' => 'protection'], admin_url('admin.php'))); ?>"><?php esc_html_e('Access & Protection', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $sltr_security_tab === 'privacy' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'security', 'security_tab' => 'privacy'], admin_url('admin.php'))); ?>"><?php esc_html_e('Privacy & Data', 'slotera-booking'); ?></a>
    </h2>

    <?php if ($sltr_security_tab === 'privacy') : ?>
        <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/privacy.php')) { require $sltr_view; } ?>
    <?php else : ?>
        <?php (new \Slotera\Admin\Pages\SecurityPage())->render(true); ?>
    <?php endif; ?>
</div>
