<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
$columns_desktop = max(1, min(4, (int) ($settings['package_columns_desktop'] ?? 3)));
$columns_tablet = max(1, min(3, (int) ($settings['package_columns_tablet'] ?? 2)));
$columns_mobile = 1;
$booking_form_width_mode = sanitize_key((string) ($settings['booking_form_width_mode'] ?? '1280'));
if (!in_array($booking_form_width_mode, ['full', '1100', '1280', 'custom'], true)) {
    $booking_form_width_mode = '1280';
}
$booking_form_custom_width = max(800, min(2400, (int) ($settings['booking_form_custom_width'] ?? 1280)));
$booking_form_max_width = $booking_form_width_mode === 'custom' ? $booking_form_custom_width . 'px' : ($booking_form_width_mode === 'full' ? 'none' : $booking_form_width_mode . 'px');
$booking_form_width = '100%';

$theme = (string) ($settings['appearance_theme'] ?? 'light');
$allowed_themes = ['light', 'dark', 'soft', 'minimal', 'custom'];
if (!in_array($theme, $allowed_themes, true)) {
    $theme = 'light';
}

$custom_style_vars = '';

if ($theme === 'custom') {
    $custom_style_vars = sprintf(
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

$style_vars = sprintf(
    '--sltr-booking-form-width:%s;--sltr-booking-form-max-width:%s;--sltr-package-columns-desktop:%d;--sltr-package-columns-tablet:%d;--sltr-package-columns-mobile:%d;--sltr-price-old-decoration:%s;--sltr-price-old-ratio:%s;--sltr-tooltip-size-ratio:%s;--sltr-tooltip-text-size:%spx;%s',
    esc_attr($booking_form_width),
    esc_attr($booking_form_max_width),
    $columns_desktop,
    $columns_tablet,
    $columns_mobile,
    esc_attr((string) ($settings['price_old_style'] ?? 'line-through')),
    esc_attr((string) ($settings['price_old_size_ratio'] ?? 0.85)),
    esc_attr((string) ($settings['tooltip_size_ratio'] ?? 1.15)),
    esc_attr((string) ($settings['tooltip_text_size'] ?? 13)),
    $custom_style_vars
);

$booking_page_url = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->get_page_url('booking');
if ($booking_page_url === '') {
    $booking_page_url = '#sltr-booking';
}

$currency = \Slotera\Application\Services\CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
$currency_position = \Slotera\Application\Services\CurrencyService::normalize_position((string) ($settings['payment_currency_position'] ?? 'right'));
$format_price = static function (float $amount) use ($currency, $currency_position): string {
    return \Slotera\Application\Services\CurrencyService::format($amount, $currency, $currency_position);
};
$format_price_with_unit = static function (float $amount, string $price_unit = '') use ($currency, $currency_position): string {
    return \Slotera\Application\Services\CurrencyService::format_with_unit($amount, $currency, $currency_position, $price_unit);
};

$get_final_price = static function (array $package): float {
    $price = max(0, (float) ($package['price'] ?? 0));
    $discount_type = (string) ($package['discount_type'] ?? 'none');
    $discount_value = max(0, (float) ($package['discount_value'] ?? 0));

    if ($discount_type === 'percent') {
        return max(0, $price - ($price * min(100, $discount_value) / 100));
    }

    if ($discount_type === 'fixed') {
        return max(0, $price - $discount_value);
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


$mode_config = static function (array $package, string $mode): array {
    $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
    return is_array($configs) && isset($configs[$mode]) && is_array($configs[$mode]) ? $configs[$mode] : [];
};
$format_duration = static function ($minutes): string {
    $minutes = max(0, (int) $minutes);
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    if ($hours > 0 && $mins > 0) {
        return sprintf(sltr_t('%dh %dmin'), $hours, $mins);
    }
    if ($hours > 0) {
        return sprintf(sltr_t('%dh'), $hours);
    }
    return sprintf(sltr_t('%dmin'), $mins);
};

$get_date_flow = static function (array $package): string {
    $flow = sanitize_key((string) ($package['date_flow'] ?? ''));
    if ($flow === '') {
        $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        $flow = is_array($configs) ? sanitize_key((string) ($configs['date_range_inventory']['date_flow'] ?? '')) : '';
    }
    return in_array($flow, ['customer_choice', 'admin_scheduled'], true) ? $flow : 'customer_choice';
};
$date_inventory_service = new \Slotera\Application\Services\DateRangeInventoryService();
$pricing_adjustments = new \Slotera\Application\Services\PricingAdjustmentService();
$low_availability_threshold = 5;
$availability_label = static function (int $left) use ($low_availability_threshold): string {
    if ($left <= 0) { return sltr_t('Sold out'); }
    if ($left <= $low_availability_threshold) {
        return sltr_places_left_label($left, true);
    }
    return sltr_places_left_label($left, false);
};
$campaign_note_for_package = static function (array $package, array $active_mode_config): string {
    $note = trim((string) ($active_mode_config['campaign_note'] ?? ''));
    if ($note === '' && array_key_exists('campaign_note', $package)) {
        $note = trim((string) $package['campaign_note']);
    }
    return $note;
};
?>

<div class="sltr-packages-page sltr-theme-<?php echo esc_attr($theme); ?>" data-sltr-version="<?php echo esc_attr(SLTR_VERSION); ?>" style="<?php echo esc_attr($style_vars); ?>">
    <div class="sltr-packages-form">
        <div class="sltr-packages-list">
    <?php if (!empty($packages)) : ?>
        <?php foreach ($packages as $package) : ?>
            <?php
            $page_id = (int) ($package['page_id'] ?? 0);
            $package_url = !empty($package['solo_page_enabled']) && $page_id > 0 ? get_permalink($page_id) : '#';
            $booking_url = add_query_arg([
                'sltr_package_id' => (int) ($package['id'] ?? 0),
                'sltr_step' => 'calendar',
            ], $booking_page_url);
            if (strpos($booking_url, '#') === false) {
                $booking_url .= '#sltr-booking';
            }
            $booking_mode = (string) ($package['booking_mode'] ?? 'fixed');
            if ($booking_mode === 'flexible') { $booking_mode = 'flex'; }
            $active_mode_config = $mode_config($package, $booking_mode);
            $hide_price_on_frontend = !empty($active_mode_config['hide_price_on_frontend']);
            $show_duration_for_package = in_array($booking_mode, ['fixed', 'flex'], true) && ((array_key_exists('show_duration_frontend', $package) && (int) $package['show_duration_frontend'] === 1) || (!array_key_exists('show_duration_frontend', $package) && !empty($active_mode_config['show_duration'])));
            $date_flow = $booking_mode === 'date_range_inventory' ? $get_date_flow($package) : '';
            $next_scheduled = ($booking_mode === 'date_range_inventory' && $date_flow === 'admin_scheduled') ? ($date_inventory_service->scheduled_event_options($package)[0] ?? null) : null;
            $campaign_note = $campaign_note_for_package($package, $active_mode_config);
            $price = max(0, (float) ($package['price'] ?? 0));
            $display_price_unit = ($booking_mode === 'date_range_inventory' && $date_flow === 'customer_choice') ? (string) ($package['price_unit'] ?? '') : '';
            $preview_context = ['booking_date' => current_time('Y-m-d')];
            $dynamic_preview = $pricing_adjustments->apply_dynamic($package, $price, $preview_context);
            $dynamic_label = (string) ($dynamic_preview['dynamic_label'] ?? '');
            $dynamic_amount = max(0, (float) ($dynamic_preview['dynamic_amount'] ?? $price));
            $dynamic_adjustment_amount = (float) ($dynamic_preview['dynamic_adjustment_amount'] ?? 0);
            $package_preview = array_merge($package, ['price' => $dynamic_amount]);
            $final_before_tax = $get_final_price($package_preview);
            $tax_preview = $pricing_adjustments->apply_tax($package, $final_before_tax);
            $final_price = (float) ($tax_preview['total_amount'] ?? $final_before_tax);
            $simple_price_mode = $booking_mode === 'simple' ? (string) ($package['price_unit'] ?? 'fixed') : '';
            if (!empty($next_scheduled)) { $next_quote = !empty($next_scheduled['quote']) && is_array($next_scheduled['quote']) ? $pricing_adjustments->apply_to_quote($package, $next_scheduled['quote'], ['start_date' => (string) ($next_scheduled['start_date'] ?? ''), 'end_date' => (string) ($next_scheduled['end_date'] ?? '')]) : []; $price = max(0, (float) ($next_scheduled['price'] ?? $price)); $dynamic_amount = max(0, (float) ($next_quote['dynamic_amount'] ?? $price)); $dynamic_adjustment_amount = (float) ($next_quote['dynamic_adjustment_amount'] ?? 0); $final_before_tax = max(0, (float) ($next_quote['pre_tax_amount'] ?? $price)); $final_price = max(0, (float) ($next_quote['total_amount'] ?? $price)); $display_price_unit = ''; }
            $has_dynamic_offer = $dynamic_label !== '' && $dynamic_adjustment_amount < 0 && $dynamic_amount < $price;
            $has_discount = $has_dynamic_offer || $final_before_tax < $price;
            $discount_label = $get_discount_label($package, $price, $final_before_tax);
            $custom_booking_button_text = trim((string) ($active_mode_config['booking_button_text'] ?? ''));
            $cta_label = $custom_booking_button_text !== '' ? $custom_booking_button_text : sltr_t('Book now');
            ?>
            <?php
            $card_image_id = absint($package['card_image_id'] ?? 0);
            $card_image_url = $card_image_id > 0 ? wp_get_attachment_image_url($card_image_id, 'large') : false;
            $card_focus_parts = array_map('intval', explode(',', (string) ($package['card_image_focus'] ?? '50,50')));
            $card_focus = max(0, min(100, $card_focus_parts[0] ?? 50)) . '% ' . max(0, min(100, $card_focus_parts[1] ?? 50)) . '%';
            $discount_badge = '';
            if ($has_discount && $price > 0) {
                $discount_badge = '-' . (string) max(1, (int) round((1 - ($final_price / $price)) * 100)) . '%';
            }
            ?>
            <div class="sltr-package-card<?php echo esc_attr(!empty($package['is_popular']) ? ' is-popular' : ''); ?><?php echo esc_attr((!empty($package['show_more_info']) && $package_url !== '#') ? ' has-more-info' : ' no-more-info'); ?><?php echo $card_image_url ? ' has-card-image' : ''; ?>">
                <div class="sltr-package-card-badges">
                    <span><?php if ($discount_badge !== '') : ?><span class="sltr-badge-discount"><?php echo esc_html($discount_badge); ?></span><?php endif; ?></span>
                    <span><?php if (!empty($package['is_popular'])) : ?><?php $popular_icons = ['star' => '★', 'fire' => '🔥', 'crown' => '♛', 'heart' => '♥', 'bolt' => '⚡']; $popular_glyph = $popular_icons[(string) ($package['popular_icon'] ?? 'star')] ?? '★'; ?><span class="sltr-badge-popular sltr-badge-popular-icon" style="--sltr-featured-icon-color:<?php echo esc_attr(sanitize_hex_color((string) ($package['popular_icon_color'] ?? '#7c3aed')) ?: '#7c3aed'); ?>;--sltr-featured-icon-size:<?php echo esc_attr((string) max(16, min(48, (int) ($package['popular_icon_size'] ?? 24)))); ?>px" aria-label="<?php echo esc_attr(sltr_t('Featured package')); ?>"><span aria-hidden="true"><?php echo esc_html($popular_glyph); ?></span></span><?php endif; ?></span>
                </div>
                <?php if ($card_image_url) : ?>
                    <div class="sltr-package-card-media"><img src="<?php echo esc_url($card_image_url); ?>" alt="<?php echo esc_attr((string) ($package['title'] ?? '')); ?>" loading="lazy" style="--sltr-image-focus:<?php echo esc_attr($card_focus); ?>;object-position:var(--sltr-image-focus)"></div>
                <?php endif; ?>
                <div class="sltr-package-card-body">
                    <div class="sltr-package-title-row">
                        <?php if (!empty($package['info_tooltip'])) : ?>
                            <button type="button" class="sltr-package-info-button" style="--sltr-tooltip-size-ratio:<?php echo esc_attr((string) max(0.8, min(2.0, (float) ($package['tooltip_size_ratio'] ?? 1.15)))); ?>;--sltr-tooltip-text-size:<?php echo esc_attr((string) max(10, min(24, (int) ($package['tooltip_text_size'] ?? 13)))); ?>px;" aria-label="<?php echo esc_attr(sltr_t('Package information')); ?>"><span aria-hidden="true">i</span><span class="sltr-tooltip-content" role="tooltip"><?php echo esc_html(wp_strip_all_tags((string) $package['info_tooltip'])); ?></span></button>
                        <?php endif; ?>
                        <h3 style="<?php echo esc_attr(trim(((string) ($package['title_font_family'] ?? '') !== '' ? 'font-family:' . (string) $package['title_font_family'] . ';' : '') . 'font-size:' . (string) max(12, min(48, (int) (($package['title_font_size'] ?? 24) ?: 24))) . 'px;')); ?>"><?php echo esc_html($package['title'] ?? ''); ?></h3>
                    </div>
                    <?php if (!empty($package['show_more_info']) && $package_url !== '#') : ?>
                        <div class="sltr-package-card-link-row">
                            <a class="sltr-more-info-link" href="<?php echo esc_url($package_url); ?>"><?php echo esc_html(sltr_t('More info')); ?></a>
                        </div>
                    <?php endif; ?>
                    <div class="sltr-package-card-facts">
                        <?php if (!$hide_price_on_frontend) : ?>
                            <p class="sltr-package-price">
                            <?php if ($booking_mode === 'simple' && $simple_price_mode === 'request') : ?><b><?php echo esc_html(sltr_t('Price on request')); ?></b>
                            <?php elseif ($booking_mode === 'simple' && $simple_price_mode === 'from') : ?><b><?php echo esc_html(sprintf(sltr_t('From %s'), $format_price($final_price))); ?></b>
                            <?php elseif ($has_discount) : ?><b class="sltr-new-price"><?php echo esc_html($format_price_with_unit($final_price, $display_price_unit)); ?></b> <del class="sltr-old-price"><?php echo esc_html($format_price_with_unit($price, $display_price_unit)); ?></del>
                            <?php else : ?><b><?php echo esc_html($format_price_with_unit($final_price, $display_price_unit)); ?></b><?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($show_duration_for_package) : ?><p class="sltr-package-meta"><span aria-hidden="true">◷</span> <?php echo esc_html($format_duration($package['duration_minutes'] ?? 0)); ?></p><?php endif; ?>
                    </div>
                    <div class="sltr-package-card-actions">
                        <a class="sltr-button sltr-select-button" href="<?php echo esc_url($booking_url); ?>"><?php echo esc_html($cta_label); ?></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <p><?php esc_html_e('No packages available.', 'slotera-booking'); ?></p>
    <?php endif; ?>
        </div>
    </div>
</div>
