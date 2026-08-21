<?php

if (!defined('ABSPATH')) {
    exit;
}

$settings = isset($settings) && is_array($settings) ? $settings : [];

$days = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    7 => 'Sunday',
];

$shortcodes = [
    [
        'title' => 'General booking form',
        'code' => '[slotera_booking]',
        'description' => 'Shows the general booking form with package selection.',
    ],
    [
        'title' => 'All categories page',
        'code' => '[slotera_categories]',
        'description' => 'Shows all active package categories.',
    ],
    [
        'title' => 'Single package booking page',
        'code' => '[slotera_booking package_id="123"]',
        'description' => 'Shows booking form for one specific package. Replace 123 with package ID.',
    ],
    [
        'title' => 'Category packages page',
        'code' => '[slotera_category category_id="5"]',
        'description' => 'Shows packages from one category. Replace 5 with category ID.',
    ],
    [
        'title' => 'Thank you page',
        'code' => '[slotera_thank_you]',
        'description' => 'System shortcode for the booking thank-you page.',
    ],
    [
        'title' => 'Checkout page',
        'code' => '[slotera_checkout]',
        'description' => 'System shortcode for reviewing coupons, VAT/taxes, deposits and amount due before completion.',
    ],
    [
        'title' => 'Contact form',
        'code' => '[slotera_contact]',
        'description' => 'Shows a theme-styled contact form with required Name, Email and Message fields, optional Message Subject, and Anti-spam defaults support.',
    ],
    [
        'title' => 'Client login page',
        'code' => '[slotera_login]',
        'description' => 'System shortcode for future client login with email magic link and Google.',
    ],
    [
        'title' => 'Client account page',
        'code' => '[slotera_account]',
        'description' => 'System shortcode for future client account and bookings management.',
    ],
];

$appearance_theme = (string) ($settings['appearance_theme'] ?? 'light');
$allowed_appearance_themes = ['light', 'dark', 'soft', 'minimal', 'custom'];
if (!in_array($appearance_theme, $allowed_appearance_themes, true)) {
    $appearance_theme = 'light';
}

$appearance_style_vars = sprintf('--sltr-tooltip-size-ratio:%s;--sltr-tooltip-text-size:%spx;', esc_attr((string) ($settings['tooltip_size_ratio'] ?? 1.15)), esc_attr((string) ($settings['tooltip_text_size'] ?? 13)));
if ($appearance_theme === 'custom') {
    $appearance_style_vars = sprintf(
        '--sltr-form-bg:%s;--sltr-form-text:%s;--sltr-card-bg:%s;--sltr-card-border:%s;--sltr-primary:%s;--sltr-primary-text:%s;--sltr-muted:%s;--sltr-price-old:%s;--sltr-price-new:%s;--sltr-discount-bg:%s;--sltr-discount-text:%s;--sltr-tooltip-icon:%s;--sltr-tooltip-bg:%s;--sltr-tooltip-text:%s;--sltr-tooltip-size-ratio:%s;--sltr-tooltip-text-size:%spx;--sltr-calendar-bg:%s;--sltr-calendar-text:%s;--sltr-calendar-border:%s;--sltr-calendar-day-bg:%s;--sltr-calendar-disabled-bg:%s;--sltr-calendar-disabled-text:%s;',
        esc_attr((string) ($settings['form_background_color'] ?? '#ffffff')),
        esc_attr((string) ($settings['form_text_color'] ?? '#0f172a')),
        esc_attr((string) ($settings['card_background_color'] ?? '#ffffff')),
        esc_attr((string) ($settings['card_border_color'] ?? '#dbe3ef')),
        esc_attr((string) ($settings['primary_color'] ?? '#2563eb')),
        esc_attr((string) ($settings['primary_text_color'] ?? '#ffffff')),
        esc_attr((string) ($settings['muted_text_color'] ?? '#64748b')),
        esc_attr((string) ($settings['price_old_color'] ?? '#94a3b8')),
        esc_attr((string) ($settings['price_new_color'] ?? '#0f172a')),
        esc_attr((string) ($settings['discount_badge_background_color'] ?? '#dc2626')),
        esc_attr((string) ($settings['discount_badge_text_color'] ?? '#ffffff')),
        esc_attr((string) ($settings['tooltip_icon_color'] ?? '#2563eb')),
        esc_attr((string) ($settings['tooltip_background_color'] ?? '#0f172a')),
        esc_attr((string) ($settings['tooltip_text_color'] ?? '#ffffff')),
        esc_attr((string) ($settings['tooltip_size_ratio'] ?? 1.15)),
        esc_attr((string) ($settings['tooltip_text_size'] ?? 13)),
        esc_attr((string) ($settings['calendar_background_color'] ?? '#f8fafc')),
        esc_attr((string) ($settings['calendar_text_color'] ?? '#0f172a')),
        esc_attr((string) ($settings['calendar_border_color'] ?? '#dbe3ef')),
        esc_attr((string) ($settings['calendar_day_background_color'] ?? '#ffffff')),
        esc_attr((string) ($settings['calendar_disabled_background_color'] ?? '#f1f5f9')),
        esc_attr((string) ($settings['calendar_disabled_text_color'] ?? '#94a3b8'))
    );
}
?>
<div class="wrap sltr-admin">
    <h1><?php esc_html_e('Settings', 'slotera-booking'); ?></h1>
    <h2 class="nav-tab-wrapper">
        <a class="nav-tab nav-tab-active" href="<?php echo esc_url(admin_url('admin.php?page=slotera-settings')); ?>"><?php esc_html_e('General Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'seo'], admin_url('admin.php'))); ?>"><?php esc_html_e('SEO Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'email'], admin_url('admin.php'))); ?>"><?php esc_html_e('Email settings', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'advanced'], admin_url('admin.php'))); ?>"><?php esc_html_e('Advanced', 'slotera-booking'); ?></a>
        <a class="nav-tab" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'security'], admin_url('admin.php'))); ?>"><?php esc_html_e('Security', 'slotera-booking'); ?></a>
    </h2>

    <?php $sltr_message = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_message'); ?>
    <?php if ($sltr_message !== '') : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Saved.', 'slotera-booking'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/booking-availability.php')) { require $sltr_view; } ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/working-hours.php')) { require $sltr_view; } ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/closures.php')) { require $sltr_view; } ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/booking-basics.php')) { require $sltr_view; } ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/system-pages.php')) { require $sltr_view; } ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/booking-form.php')) { require $sltr_view; } ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/package-layout.php')) { require $sltr_view; } ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/settings/appearance.php')) { require $sltr_view; } ?>
</div>
