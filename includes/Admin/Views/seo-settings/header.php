<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap sltr-admin-page sltr-admin-wrap sltr-seo-center">
    <h1><?php esc_html_e('Settings', 'slotera-booking'); ?></h1>
    <h2 class="nav-tab-wrapper">
        <a class="nav-tab" href="<?php echo esc_url(admin_url('admin.php?page=slotera-settings')); ?>"><?php esc_html_e('General Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab nav-tab-active" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'seo'], admin_url('admin.php'))); ?>"><?php esc_html_e('SEO Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'email'], admin_url('admin.php'))); ?>"><?php esc_html_e('Email settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'advanced'], admin_url('admin.php'))); ?>"><?php esc_html_e('Advanced', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'security'], admin_url('admin.php'))); ?>"><?php esc_html_e('Security', 'slotera-booking'); ?></a>
    </h2>

    <?php if (!empty($_GET['sltr_message'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sanitize_text_field((string) wp_unslash($_GET['sltr_message']))); ?></p></div>
    <?php endif; ?>

    <?php if ($sltr_seo_plugins_blocking) : ?>
        <div class="notice notice-error" style="padding:10px 12px;">
            <p><strong><?php esc_html_e('External SEO plugin detected:', 'slotera-booking'); ?></strong> <?php echo esc_html(implode(', ', $detected_seo_plugins)); ?></p>
            <p><?php esc_html_e('Slotera will not allow WordPress page SEO to be activated while a dedicated SEO plugin is active. This prevents duplicate title/meta/canonical output.', 'slotera-booking'); ?></p>
        </div>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper">
        <a class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>" href="<?php echo $sltr_tab_url('settings'); ?>"><?php esc_html_e('Global SEO', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $tab === 'individual' ? 'nav-tab-active' : ''; ?>" href="<?php echo $sltr_tab_url('individual'); ?>"><?php esc_html_e('Individual SEO Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $tab === 'templates' ? 'nav-tab-active' : ''; ?>" href="<?php echo $sltr_tab_url('templates'); ?>"><?php esc_html_e('SEO Templates & Bulk', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $tab === 'warnings' ? 'nav-tab-active' : ''; ?>" href="<?php echo $sltr_tab_url('warnings'); ?>"><?php esc_html_e('Conflict Checks', 'slotera-booking'); ?></a>
    </h2>

    <?php
    $sltr_page_titles = [
        'settings' => __('Global SEO', 'slotera-booking'),
        'individual' => __('Individual SEO Settings', 'slotera-booking'),
        'templates' => __('SEO Templates & Bulk', 'slotera-booking'),
        'warnings' => __('Conflict Checks', 'slotera-booking'),
    ];
    ?>
    <h2><?php echo esc_html($sltr_page_titles[$tab] ?? $sltr_page_titles['settings']); ?></h2>

    <?php if ($tab === 'settings') : ?>
        <div class="notice notice-warning inline" style="padding:10px 12px;">
            <p><strong><?php esc_html_e('Important conflict warning for Slotera pages', 'slotera-booking'); ?></strong></p>
            <p><?php esc_html_e('If package/category SEO is configured in Slotera, do not enable duplicate SEO output for the same Slotera pages in Yoast, Rank Math, SEOPress or similar plugins. Duplicate canonical, robots, Open Graph or schema output can damage search results and may break page output on some sites.', 'slotera-booking'); ?></p>
        </div>
    <?php endif; ?>
