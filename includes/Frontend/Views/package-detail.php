<?php
/** @var array|null $package */

if (!defined('ABSPATH')) {
    exit;
}

$sltr_allow_package_shortcodes = \Slotera\Core\HtmlSanitizer::allow_package_shortcodes();

$settings_repo = new \Slotera\Infrastructure\Repositories\SettingsRepository();
$settings = $settings_repo->all();
$theme = (string) ($settings['appearance_theme'] ?? 'light');
$allowed_themes = ['light', 'dark', 'soft', 'minimal', 'custom'];
if (!in_array($theme, $allowed_themes, true)) {
    $theme = 'light';
}
$booking_form_width_mode = sanitize_key((string) ($settings['booking_form_width_mode'] ?? '1280'));
if (!in_array($booking_form_width_mode, ['full', '1100', '1280', 'custom'], true)) {
    $booking_form_width_mode = '1280';
}
$booking_form_custom_width = max(800, min(2400, (int) ($settings['booking_form_custom_width'] ?? 1280)));
$booking_form_max_width = $booking_form_width_mode === 'custom' ? $booking_form_custom_width . 'px' : ($booking_form_width_mode === 'full' ? 'none' : $booking_form_width_mode . 'px');

$custom_style_vars = '';
if ($theme === 'custom') {
    $custom_style_vars = sprintf(
        '--sltr-form-bg:%s;--sltr-form-text:%s;--sltr-card-bg:%s;--sltr-card-border:%s;--sltr-primary:%s;--sltr-primary-text:%s;--sltr-muted:%s;--sltr-price-old:%s;--sltr-price-new:%s;--sltr-discount-bg:%s;--sltr-discount-text:%s;',
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
        esc_attr((string) ($settings['discount_badge_text_color'] ?? '#ffffff'))
    );
}
$description_font_family = trim((string) ($package['description_font_family'] ?? ''));
$description_font_size = max(12, min(48, (int) ($package['description_font_size'] ?? 18)));
$description_style_vars = sprintf(
    '--sltr-package-description-font-family:%s;--sltr-package-description-font-size:%dpx;',
    $description_font_family !== '' ? esc_attr($description_font_family) : 'inherit',
    $description_font_size
);
$style_vars = '--sltr-booking-form-width:100%;--sltr-booking-form-max-width:' . esc_attr($booking_form_max_width) . ';' . $custom_style_vars . $description_style_vars;
$solo_layout = (string) ($package['solo_layout'] ?? 'classic');
if (!in_array($solo_layout, ['classic', 'stacked'], true)) { $solo_layout = 'classic'; }
$solo_top_content = trim((string) ($package['solo_top_content'] ?? ''));
$show_solo_top_content = trim((string) ($package['solo_top_content'] ?? '')) !== '';
$solo_content = trim((string) ($package['solo_content'] ?? ''));
$solo_down_content = trim((string) ($package['solo_down_content'] ?? ''));
$show_solo_down_content = trim((string) ($package['solo_down_content'] ?? '')) !== '';
if ($solo_content === '') {
    $auto_blocks = [];
    if (trim((string) ($package['right_block_title'] ?? '')) !== '' || trim((string) ($package['right_block_text'] ?? '')) !== '') {
        $auto_blocks[] = '[slotera_package_text_block]';
    }
    if (trim((string) ($package['slider_image_ids'] ?? '')) !== '') {
        $auto_blocks[] = '[slotera_package_slider]';
    }
    if (trim((string) ($package['gallery_image_ids'] ?? '')) !== '') {
        $auto_blocks[] = '[slotera_package_image]';
    }
    $solo_content = implode("

", $auto_blocks);
}
if (class_exists('Slotera\\Application\\Services\\BreadcrumbService') && !\Slotera\Application\Services\LocalRouteService::is_current_route()) {
    (new \Slotera\Application\Services\BreadcrumbService())->render_package((array) $package);
}

$booking_page_url = $settings_repo->get_page_url('booking');
if ($booking_page_url === '') {
    $booking_page_url = '#sltr-booking';
}

$booking_url = add_query_arg([
    'sltr_package_id' => (int) ($package['id'] ?? 0),
    'sltr_step' => 'calendar',
], $booking_page_url);
if (strpos($booking_url, '#') === false) {
    $booking_url .= '#sltr-booking';
}

$currency = \Slotera\Application\Services\CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
$currency_position = \Slotera\Application\Services\CurrencyService::normalize_position((string) ($settings['payment_currency_position'] ?? 'right'));
$format_price = static function ($value) use ($currency, $currency_position): string {
    return \Slotera\Application\Services\CurrencyService::format($value, $currency, $currency_position);
};
$format_price_with_unit = static function ($value, string $price_unit = '') use ($currency, $currency_position): string {
    return \Slotera\Application\Services\CurrencyService::format_with_unit($value, $currency, $currency_position, $price_unit);
};

$get_final_price = static function (array $package): float {
    $price = max(0, (float) ($package['price'] ?? 0));
    $type = (string) ($package['discount_type'] ?? 'none');
    $value = max(0, (float) ($package['discount_value'] ?? 0));

    if ($type === 'percent' && $value > 0) {
        return max(0, $price - ($price * min(100, $value) / 100));
    }

    if ($type === 'fixed' && $value > 0) {
        return max(0, $price - $value);
    }

    return $price;
};

$get_discount_label = static function (array $package, float $price, float $final_price): string {
    if ($final_price >= $price || $price <= 0) {
        return '';
    }

    $discount_type = (string) ($package['discount_type'] ?? 'none');
    $discount_value = max(0, (float) ($package['discount_value'] ?? 0));

    if ($discount_type === 'percent') {
        return '-' . (int) min(100, $discount_value) . '%';
    }

    $percent = (int) round((($price - $final_price) / $price) * 100);
    return '-' . max(1, $percent) . '%';
};

$pricing_adjustments = new \Slotera\Application\Services\PricingAdjustmentService();
$price = max(0, (float) ($package['price'] ?? 0));
$dynamic_preview = $pricing_adjustments->apply_dynamic($package ?? [], $price, ['booking_date' => current_time('Y-m-d')]);
$dynamic_label = (string) ($dynamic_preview['dynamic_label'] ?? '');
$package_preview = array_merge($package ?? [], ['price' => (float) ($dynamic_preview['dynamic_amount'] ?? $price)]);
$final_before_tax = $get_final_price($package_preview);
$tax_preview = $pricing_adjustments->apply_tax($package ?? [], $final_before_tax);
$final_price = (float) ($tax_preview['total_amount'] ?? $final_before_tax);
$display_price_unit = '';
$has_discount = $final_before_tax < $price;
$discount_label = $get_discount_label($package ?? [], $price, $final_before_tax);
$booking_mode = (string) ($package['booking_mode'] ?? 'fixed');
if ($booking_mode === 'flexible') { $booking_mode = 'flex'; }
$mode_configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
$active_mode_config = is_array($mode_configs) && isset($mode_configs[$booking_mode]) && is_array($mode_configs[$booking_mode]) ? $mode_configs[$booking_mode] : [];
$format_duration = static fn ($minutes): string => \Slotera\Application\Services\FrontendDurationFormatter::format((int) $minutes);
$hide_price_on_frontend = !empty($active_mode_config['hide_price_on_frontend']);
$show_duration = in_array($booking_mode, ['fixed', 'flex'], true) && ((array_key_exists('show_duration_frontend', $package) && (int) $package['show_duration_frontend'] === 1) || (!array_key_exists('show_duration_frontend', $package) && !empty($active_mode_config['show_duration'])));
$duration = $show_duration ? (int) ($package['duration_minutes'] ?? 0) : 0;
$price_unit = (string) ($package['price_unit'] ?? 'fixed');
$date_inventory_service = new \Slotera\Application\Services\DateRangeInventoryService();
$date_flow = $booking_mode === 'date_range_inventory' ? $date_inventory_service->date_flow($package ?? []) : '';
$scheduled_options = ($booking_mode === 'date_range_inventory' && $date_flow === 'admin_scheduled') ? $date_inventory_service->scheduled_event_options($package ?? []) : [];
// Event packages do not have a meaningful standalone base price. Use the next
// available event quote for the hero price instead of showing 0.00.
if (!empty($scheduled_options)) {
    $next_event = $scheduled_options[0];
    $next_event_quote = !empty($next_event['quote']) && is_array($next_event['quote'])
        ? $pricing_adjustments->apply_to_quote($package ?? [], $next_event['quote'], [
            'start_date' => (string) ($next_event['start_date'] ?? ''),
            'end_date' => (string) ($next_event['end_date'] ?? ''),
        ])
        : [];
    $price = max(0, (float) ($next_event['price'] ?? $price));
    $final_before_tax = max(0, (float) ($next_event_quote['pre_tax_amount'] ?? $price));
    $final_price = max(0, (float) ($next_event_quote['total_amount'] ?? $price));
    $tax_preview = [
        'tax_amount' => (float) ($next_event_quote['tax_amount'] ?? 0),
        'tax_label' => (string) ($next_event_quote['tax_label'] ?? ''),
        'tax_rate' => (float) ($next_event_quote['tax_rate'] ?? 0),
    ];
    $dynamic_label = (string) ($next_event_quote['dynamic_label'] ?? '');
    $has_discount = $final_before_tax < $price;
    $discount_label = $get_discount_label($package ?? [], $price, $final_before_tax);
}
$display_price_unit = ($booking_mode === 'date_range_inventory' && $date_flow === 'customer_choice') ? $price_unit : '';
$simple_price_mode = $booking_mode === 'simple' ? $price_unit : '';
$custom_booking_button_text = trim((string) ($active_mode_config['booking_button_text'] ?? ''));
$cta_label = $custom_booking_button_text !== '' ? $custom_booking_button_text : sltr_t('Book now');
$low_availability_enabled = array_key_exists('low_availability_notice_enabled', $package) ? (int) $package['low_availability_notice_enabled'] : (array_key_exists('low_availability_notice_enabled', $active_mode_config) ? (int) $active_mode_config['low_availability_notice_enabled'] : 0);
$low_availability_threshold = max(1, min(99, (int) (array_key_exists('low_availability_threshold', $package) ? $package['low_availability_threshold'] : ($active_mode_config['low_availability_threshold'] ?? 5))));
$format_event_period = static function (array $event): string {
    $start_date = sanitize_text_field((string) ($event['start_date'] ?? ''));
    $end_date = sanitize_text_field((string) ($event['end_date'] ?? ''));
    $start_time = !empty($event['start_time']) ? substr((string) $event['start_time'], 0, 5) : '';
    $end_time = !empty($event['end_time']) ? substr((string) $event['end_time'], 0, 5) : '';
    $frontend_locale = class_exists('Slotera\Application\Services\TranslationService')
        ? (new \Slotera\Application\Services\TranslationService())->locale_for_group('frontend')
        : (function_exists('determine_locale') ? determine_locale() : get_locale());
    $start_label = $start_date !== '' ? sltr_format_localized_date($start_date, $frontend_locale) : '';
    $end_label = $end_date !== '' ? sltr_format_localized_date($end_date, $frontend_locale) : '';
    if ($start_date !== '' && $start_date === $end_date) {
        if ($start_time !== '' && $end_time !== '') { return $start_label . ', ' . $start_time . '–' . $end_time; }
        if ($start_time !== '') { return $start_label . ', ' . $start_time; }
        return $start_label;
    }
    return trim($start_label . ($start_time !== '' ? ' ' . $start_time : '') . ' → ' . $end_label . ($end_time !== '' ? ' ' . $end_time : ''));
};

$availability_label = static function (int $left) use ($low_availability_threshold, $low_availability_enabled): string {
    if ($left <= 0) { return sltr_t('Sold out'); }
    if ($low_availability_enabled && $left <= $low_availability_threshold) {
        return sltr_places_left_label($left, true);
    }
    return sltr_places_left_label($left, false);
};
$campaign_note = trim((string) ($active_mode_config['campaign_note'] ?? ''));
if ($campaign_note === '' && array_key_exists('campaign_note', $package)) {
    $campaign_note = trim((string) $package['campaign_note']);
}

$social_share_enabled = (int) ($settings['social_share_enabled'] ?? 1) === 1;
$social_share_networks = array_filter(array_map('trim', explode(',', (string) ($settings['social_share_networks'] ?? 'facebook,x,whatsapp,telegram,linkedin,line,kakaotalk,viber,copy'))));
$social_share_allowed = ['facebook', 'x', 'whatsapp', 'telegram', 'linkedin', 'line', 'kakaotalk', 'viber', 'copy'];
$social_share_networks = array_values(array_intersect($social_share_networks, $social_share_allowed));
$share_title = (string) ($package['title'] ?? get_bloginfo('name'));
$sltr_local_context = class_exists('Slotera\Application\Services\LocalRouteService') ? \Slotera\Application\Services\LocalRouteService::current_context() : null;
$share_url = is_array($sltr_local_context) && !empty($sltr_local_context['url']) ? (string) $sltr_local_context['url'] : get_permalink();
if (!is_string($share_url) || $share_url === '') {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '/';
    $share_url = home_url($request_uri);
}
$social_share_labels = [
    'facebook' => sltr_t('Share on Facebook'),
    'x' => sltr_t('Share on X'),
    'whatsapp' => sltr_t('Share on WhatsApp'),
    'telegram' => sltr_t('Share on Telegram'),
    'linkedin' => sltr_t('Share on LinkedIn'),
    'line' => sltr_t('Share on LINE'),
    'kakaotalk' => sltr_t('Share on KakaoTalk'),
    'viber' => sltr_t('Share on Viber'),
    'copy' => sltr_t('Copy link'),
];
$social_share_mobile_only = ['whatsapp', 'telegram', 'kakaotalk', 'viber'];
$social_share_href = static function (string $network) use ($share_url, $share_title): string {
    $url = rawurlencode($share_url);
    $title = rawurlencode($share_title);
    $text = rawurlencode(trim($share_title . ' ' . $share_url));
    $map = [
        'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
        'x' => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
        'whatsapp' => 'https://api.whatsapp.com/send?text=' . $text,
        'telegram' => 'https://t.me/share/url?url=' . $url . '&text=' . $title,
        'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url,
        'line' => 'https://social-plugins.line.me/lineit/share?url=' . $url,
        'kakaotalk' => 'https://story.kakao.com/share?url=' . $url,
        'viber' => 'viber://forward?text=' . $text,
    ];
    return (string) ($map[$network] ?? '#');
};
$social_share_icon = static function (string $network): string {
    $icons = [
        'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3c-3 0-5 2-5 5v2H6v4h3v7h4v-7h3l1-4h-4V9c0-.6.4-1 1-1z"/></svg>',
        'x' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h4.8l4 5.5L17.7 4H20l-6.1 7 6.7 9h-4.8l-4.4-6-5.3 6H3.8l6.5-7.4L4 4zm3.4 1.8 9.3 12.4h1.5L8.9 5.8H7.4z"/></svg>',
        'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 0 0-7.7 13.7L3 21l4.4-1.2A9 9 0 1 0 12 3zm0 2a7 7 0 0 1 0 14c-1.2 0-2.4-.3-3.4-.9l-.4-.2-2.2.6.6-2.1-.3-.5A7 7 0 0 1 12 5zm-3 3.8c-.2 0-.6.1-.9.5-.3.4-.7.9-.7 2.1s.8 2.4 1 2.6c.1.2 1.6 2.6 4 3.5 2 .8 2.4.6 2.8.6.4 0 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1-.1-.1-.2-.2-.5-.4l-1.5-.7c-.2-.1-.4-.1-.6.2l-.7.9c-.1.2-.3.2-.5.1-1.4-.7-2.3-1.3-3.1-2.8-.2-.2 0-.4.1-.5l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.7-1.7c-.2-.6-.5-.6-.7-.6H9z"/></svg>',
        'telegram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 4.5 18 20c-.2 1-.8 1.2-1.6.8l-4.5-3.3-2.2 2.1c-.2.2-.4.4-.9.4l.3-4.7L17.7 8c.4-.3-.1-.5-.6-.2L6.5 14.5 2 13.1c-1-.3-1-1 0-1.4L19.6 4.9c.8-.3 1.5.2 1.4-.4z"/></svg>',
        'linkedin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 8.8H3V21h3.5V8.8zM4.8 3A2 2 0 1 0 4.7 7a2 2 0 0 0 .1-4zm6.8 5.8H8.2V21h3.4v-6.4c0-1.7.7-2.8 2.1-2.8 1.3 0 1.8.9 1.8 2.8V21H19v-7c0-3.7-2-5.5-4.6-5.5-2 0-2.9 1.1-3.4 1.9h-.1l.1-1.6z"/></svg>',
        'line' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4C6.5 4 2 7.5 2 11.8c0 3.8 3.4 7 8 7.7.3.1.7.2.8.5.1.3.1.6 0 .9l-.1.8c0 .2-.1.9.8.5.9-.4 5-3 6.8-5.1 1.2-1.3 1.7-2.7 1.7-4.3C20 7.5 15.5 4 12 4zM8.2 13.8H5.9V9.7h1v3.2h1.3v.9zm1.7 0h-1V9.7h1v4.1zm4.4 0h-.9l-1.8-2.5v2.5h-1V9.7h.9l1.8 2.5V9.7h1v4.1zm3.8-3.2h-1.6v.7H18v.9h-1.5v.7h1.6v.9h-2.6V9.7h2.6v.9z"/></svg>',
        'kakaotalk' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4C6.5 4 2 7.5 2 11.8c0 2.8 1.9 5.2 4.8 6.5l-.7 2.7c-.1.3.2.5.5.3l3.3-2.2c.7.1 1.4.2 2.1.2 5.5 0 10-3.5 10-7.8S17.5 4 12 4z"/></svg>',
        'viber' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c4.9 0 8 2.7 8 7.7 0 5-3.1 7.7-8 7.7h-.7l-3.6 2.8v-3.4C5.3 16.8 4 14.4 4 10.7 4 5.7 7.1 3 12 3zm-2.4 5.1c-.2 0-.6.1-.8.4-.2.3-.7.8-.5 2 .2 1.2 1.1 2.8 2.3 4 1.1 1.1 2.9 2.1 4 2.3 1.2.2 1.7-.3 2-.5.3-.2.4-.6.4-.8l-.2-1.3c-.1-.3-.4-.5-.7-.6l-1.2-.4c-.3-.1-.6 0-.8.3l-.3.5c-.2.2-.4.3-.7.1-.7-.4-1.4-.8-2-1.4-.6-.6-1-1.3-1.4-2-.1-.3-.1-.5.1-.7l.5-.3c.3-.2.4-.5.3-.8l-.4-1.2c-.1-.3-.3-.6-.6-.6z"/></svg>',
        'copy' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7a5 5 0 0 1 5-5h3a5 5 0 0 1 0 10h-2v-2h2a3 3 0 0 0 0-6h-3a3 3 0 0 0-3 3v2H9V7zm6 10a5 5 0 0 1-5 5H7A5 5 0 0 1 7 12h2v2H7a3 3 0 0 0 0 6h3a3 3 0 0 0 3-3v-2h2v2zm-7-4h8v-2H8v2z"/></svg>',
    ];
    return $icons[$network] ?? $icons['copy'];
};
$price_unit_label = '';
if ($booking_mode === 'date_range_inventory') {
    $price_unit_label = [
        'per_day' => sltr_t('per day'),
        'per_night' => sltr_t('per night'),
        'per_hour' => sltr_t('per hour'),
        'fixed' => sltr_t('fixed price'),
    ][$price_unit] ?? '';
}
?>
<section data-sltr-package-id="<?php echo esc_attr((string) ($package['id'] ?? 0)); ?>" class="sltr-package-landing sltr-package-page-shellless sltr-package-landing-layout-<?php echo esc_attr($solo_layout); ?><?php echo ($show_solo_top_content && $solo_top_content !== '') ? ' sltr-package-landing-has-top-content' : ''; ?> sltr-theme-<?php echo esc_attr($theme); ?>" style="<?php echo esc_attr($style_vars); ?>">
    <?php if ($show_solo_top_content && $solo_top_content !== '') : ?>
        <div class="sltr-package-landing-top-content" aria-label="<?php esc_attr_e('Package top content area', 'slotera-booking'); ?>">
            <?php
            $GLOBALS['sltr_current_package'] = $package;
            echo \Slotera\Core\HtmlSanitizer::render_public_content($solo_top_content, false, $sltr_allow_package_shortcodes);
            unset($GLOBALS['sltr_current_package']);
            ?>
        </div>
    <?php endif; ?>
    <div class="sltr-package-landing-hero">
        <div class="sltr-package-landing-kicker">
            <?php if (!empty($package['is_popular'])) : ?>
                <?php $popular_icons = ['star' => '★', 'fire' => '🔥', 'crown' => '♛', 'heart' => '♥', 'bolt' => '⚡']; $popular_glyph = $popular_icons[(string) ($package['popular_icon'] ?? 'star')] ?? '★'; ?>
                <span class="sltr-badge-popular sltr-badge-popular-icon" style="--sltr-featured-icon-color:<?php echo esc_attr(sanitize_hex_color((string) ($package['popular_icon_color'] ?? '#7c3aed')) ?: '#7c3aed'); ?>;--sltr-featured-icon-size:<?php echo esc_attr((string) max(16, min(48, (int) ($package['popular_icon_size'] ?? 24)))); ?>px" aria-label="<?php echo esc_attr(sltr_t('Featured package')); ?>"><span aria-hidden="true"><?php echo esc_html($popular_glyph); ?></span></span>
            <?php endif; ?>
            <?php if ($dynamic_label !== '') : ?>
                <span class="sltr-badge-discount"><?php echo esc_html($dynamic_label); ?></span>
            <?php elseif ($has_discount && $discount_label !== '') : ?>
                <span class="sltr-badge-discount"><?php echo esc_html($discount_label); ?></span>
            <?php endif; ?>
        </div>

        <h1 style="<?php echo esc_attr(trim(((string) ($package['title_font_family'] ?? '') !== '' ? 'font-family:' . (string) $package['title_font_family'] . ';' : '') . 'font-size:' . (string) max(12, min(48, (int) (($package['title_font_size'] ?? 24) ?: 24))) . 'px;')); ?>"><?php echo esc_html((string) ($package['title'] ?? '')); ?></h1>

        <div class="sltr-package-landing-meta" aria-label="<?php esc_attr_e('Package details', 'slotera-booking'); ?>">
            <?php if ($duration > 0) : ?>
                <span><?php echo esc_html($format_duration($duration)); ?></span>
            <?php endif; ?>
            <?php if (!$hide_price_on_frontend) : ?>
            <span class="sltr-package-price">
                <?php if ($booking_mode === 'simple' && $simple_price_mode === 'request') : ?>
                    <b><?php echo esc_html(sltr_t('Price on request')); ?></b>
                <?php elseif ($booking_mode === 'simple' && $simple_price_mode === 'from') : ?>
                    <b><?php echo esc_html(sprintf(sltr_t('From %s'), $format_price($final_price))); ?></b>
                <?php elseif ($has_discount) : ?>
                    <del><?php echo esc_html($format_price_with_unit($price, $display_price_unit)); ?></del>
                    <b><?php echo esc_html($format_price_with_unit($final_price, $display_price_unit)); ?></b>
                <?php else : ?>
                    <b><?php echo esc_html($format_price_with_unit($final_price, $display_price_unit)); ?></b>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
        <?php if (!$hide_price_on_frontend && $dynamic_label !== '') : ?>
            <p class="sltr-dynamic-offer-note"><?php echo esc_html($dynamic_label); ?></p>
        <?php endif; ?>
        <?php if (!$hide_price_on_frontend && !empty($tax_preview['tax_amount'])) : ?>
            <p class="sltr-tax-note"><?php echo esc_html(sprintf(sltr_t('%s included in total'), trim((string) ($tax_preview['tax_label'] ?? 'VAT')) . ' (' . number_format((float) ($tax_preview['tax_rate'] ?? 0), 2) . '%)')); ?></p>
        <?php endif; ?>

        <?php if ($campaign_note !== '') : ?>
            <p class="sltr-urgency-note sltr-landing-urgency"><?php echo esc_html($campaign_note); ?></p>
        <?php endif; ?>

        <?php if (!empty($package['description'])) : ?>
            <details class="sltr-package-description-popover">
                <summary aria-label="<?php echo esc_attr(sltr__('frontend.show_package_description')); ?>">
                    <span class="sltr-package-description-popover-icon" aria-hidden="true">i</span>
                    <span><?php echo esc_html(sltr__('frontend.description')); ?></span>
                </summary>
                <div class="sltr-package-description-popover-panel">
                    <?php echo wp_kses_post(wpautop((string) $package['description'])); ?>
                </div>
            </details>
        <?php endif; ?>


        <?php if (!empty($scheduled_options)) : ?>
            <div class="sltr-scheduled-preview">
                <?php foreach (array_slice($scheduled_options, 0, 4) as $event) : ?>
                    <div class="sltr-scheduled-preview-row">
                        <strong><?php echo esc_html($event['title'] !== '' ? $event['title'] : sltr_t('Scheduled event')); ?></strong>
                        <span><?php echo esc_html($format_event_period($event)); ?></span>
                        <span><?php $event_quote = !empty($event['quote']) && is_array($event['quote']) ? $pricing_adjustments->apply_to_quote($package ?? [], $event['quote'], ['start_date' => (string) ($event['start_date'] ?? ''), 'end_date' => (string) ($event['end_date'] ?? '')]) : []; if (!$hide_price_on_frontend) { echo esc_html($format_price((float) ($event_quote['total_amount'] ?? $event['price'] ?? 0))) . ' · '; } echo esc_html($availability_label((int) ($event['seats_left'] ?? 0))); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a class="sltr-button sltr-package-landing-button" href="<?php echo esc_url($booking_url); ?>"><?php echo esc_html($cta_label); ?></a>
    </div>



    <?php if ($social_share_enabled && !empty($social_share_networks)) : ?>
        <div class="sltr-package-share-block" aria-label="<?php esc_attr_e('Share package', 'slotera-booking'); ?>">
            <?php foreach ($social_share_networks as $network) : ?>
                <?php
                $is_copy = $network === 'copy';
                $mobile_only_class = in_array($network, $social_share_mobile_only, true) ? ' sltr-share-mobile-only' : '';
                $label = (string) ($social_share_labels[$network] ?? sltr_t('Share package'));
                ?>
                <?php if ($is_copy) : ?>
                    <button type="button" class="sltr-share-button sltr-share-<?php echo esc_attr($network); ?><?php echo esc_attr($mobile_only_class); ?>" data-sltr-copy-link="<?php echo esc_url($share_url); ?>" aria-label="<?php echo esc_attr($label); ?>" title="<?php echo esc_attr($label); ?>">
                        <?php echo $social_share_icon($network); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </button>
                <?php else : ?>
                    <a class="sltr-share-button sltr-share-<?php echo esc_attr($network); ?><?php echo esc_attr($mobile_only_class); ?>" href="<?php echo esc_url($social_share_href($network)); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($label); ?>" title="<?php echo esc_attr($label); ?>">
                        <?php echo $social_share_icon($network); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="sltr-package-landing-content" aria-label="<?php esc_attr_e('Package page content area', 'slotera-booking'); ?>">
        <?php if ($solo_content !== '') : ?>
            <?php
            $GLOBALS['sltr_current_package'] = $package;
            echo \Slotera\Core\HtmlSanitizer::render_public_content($solo_content, false, $sltr_allow_package_shortcodes);
            unset($GLOBALS['sltr_current_package']);
            ?>
        <?php endif; ?>
    </div>

    <?php if ($show_solo_down_content && $solo_down_content !== '') : ?>
        <?php
        $sltr_is_contact_block = strpos($solo_down_content, '[slotera_contact') !== false;
        $sltr_contact_rows = json_decode((string) ($package['solo_contact_details_json'] ?? '[]'), true);
        $sltr_contact_rows = is_array($sltr_contact_rows) ? $sltr_contact_rows : [];
        $sltr_contact_address = '';
        $sltr_contact_details = [];
        $sltr_contact_socials = [];
        foreach ($sltr_contact_rows as $sltr_contact_row) {
            if (!is_array($sltr_contact_row)) { continue; }
            $sltr_contact_type = (string) ($sltr_contact_row['type'] ?? 'contact');
            if ($sltr_contact_type === 'address') {
                $sltr_contact_address = (string) ($sltr_contact_row['value'] ?? '');
            } elseif ($sltr_contact_type === 'social') {
                $sltr_contact_socials[] = $sltr_contact_row;
            } else {
                $sltr_contact_details[] = $sltr_contact_row;
            }
        }
        $sltr_social_labels = [
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'linkedin' => 'LinkedIn',
            'x' => 'X (Twitter)',
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok',
        ];
        $sltr_contact_image_id = (int) ($package['solo_contact_image_id'] ?? 0);
        $sltr_contact_image_url = $sltr_contact_image_id > 0 ? wp_get_attachment_image_url($sltr_contact_image_id, 'large') : '';
        if (!$sltr_contact_image_url) {
            $sltr_contact_image_url = SLTR_PLUGIN_URL . 'assets/images/contact-block-default.webp';
        }
        ?>
        <div class="sltr-package-landing-down-content<?php echo $sltr_is_contact_block ? ' sltr-package-contact-block' : ''; ?>" aria-label="<?php esc_attr_e('Package full width content area', 'slotera-booking'); ?>">
            <?php if ($sltr_is_contact_block) : ?>
                <div class="sltr-package-contact-form">
                    <?php $GLOBALS['sltr_current_package'] = $package; echo \Slotera\Core\HtmlSanitizer::render_public_content('[slotera_contact]', false, $sltr_allow_package_shortcodes); unset($GLOBALS['sltr_current_package']); ?>
                </div>
                <div class="sltr-package-contact-aside">
                    <img class="sltr-package-contact-image" src="<?php echo esc_url($sltr_contact_image_url); ?>" alt="" loading="lazy">
                    <?php $sltr_contact_map_url = esc_url((string) ($package['solo_contact_map'] ?? '')); ?>
                    <?php if ($sltr_contact_address !== '') : ?>
                        <div class="sltr-package-contact-details sltr-package-contact-address">
                            <div class="sltr-package-contact-detail"><strong><?php esc_html_e('Address', 'slotera-booking'); ?></strong><span><?php echo esc_html($sltr_contact_address); ?></span></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($sltr_contact_map_url !== '') : ?>
                        <p class="sltr-package-contact-map-link">
                            <a href="<?php echo $sltr_contact_map_url; ?>" target="_blank" rel="noopener noreferrer" data-sltr-google-maps-popup><?php esc_html_e('Open in Google Maps', 'slotera-booking'); ?></a>
                        </p>
                    <?php endif; ?>
                    <?php if ($sltr_contact_details) : ?>
                        <div class="sltr-package-contact-details">
                            <?php foreach ($sltr_contact_details as $detail) : if (!is_array($detail)) continue; ?>
                                <div class="sltr-package-contact-detail"><strong><?php echo esc_html((string) ($detail['label'] ?? '')); ?></strong><span><?php echo esc_html((string) ($detail['value'] ?? '')); ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($sltr_contact_socials) : ?>
                        <div class="sltr-package-contact-details sltr-package-contact-socials" aria-label="<?php esc_attr_e('Social links', 'slotera-booking'); ?>">
                            <?php foreach ($sltr_contact_socials as $social) : if (!is_array($social)) continue; ?>
                                <?php
                                $sltr_social_platform = sanitize_key((string) ($social['platform'] ?? ''));
                                $sltr_social_url = esc_url((string) ($social['url'] ?? ''));
                                if ($sltr_social_url === '' || !isset($sltr_social_labels[$sltr_social_platform])) { continue; }
                                ?>
                                <div class="sltr-package-contact-detail"><strong><?php echo esc_html($sltr_social_labels[$sltr_social_platform]); ?></strong><a href="<?php echo $sltr_social_url; ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open', 'slotera-booking'); ?></a></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <?php $GLOBALS['sltr_current_package'] = $package; echo \Slotera\Core\HtmlSanitizer::render_public_content($solo_down_content, false, $sltr_allow_package_shortcodes); unset($GLOBALS['sltr_current_package']); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
