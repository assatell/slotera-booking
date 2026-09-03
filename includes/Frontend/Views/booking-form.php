<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
$white_label = new \Slotera\Application\Services\WhiteLabelService();
$show_slotera_attribution = $white_label->public_attribution_visible();
$slotera_platform_url = $white_label->platform_url();
$security_captcha_provider = (string) ($settings['security_captcha_provider'] ?? 'none');
$turnstile_site_key = trim((string) ($settings['security_turnstile_site_key'] ?? ''));
$recaptcha_site_key = trim((string) ($settings['security_recaptcha_site_key'] ?? ''));
$booking_form_config = new \Slotera\Application\Services\BookingFormConfigService();
$booking_form_fields = $booking_form_config->fields();
$payment_gateways = array_intersect_key(
    (new \Slotera\Application\Services\PaymentMethodService())->enabled_methods(),
    array_flip(['stripe', 'apple_pay', 'google_pay', 'paypal', 'mollie'])
);
$payment_gateway_label = static function (string $gateway_id, $gateway): string {
    $label = (string) $gateway->get_label();
    if ($label === '') {
        $defaults = ['apple_pay' => 'Apple Pay', 'google_pay' => 'Google Pay', 'stripe' => 'Card', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank Transfer', 'manual' => 'Pay on arrival'];
        $label = $defaults[$gateway_id] ?? $gateway_id;
    }
    return $label . (method_exists($gateway, 'is_test_mode') && $gateway->is_test_mode() ? sltr_t(' (Test mode)') : '');
};
$render_payment_method_picker = static function (string $select_id, array $gateways) use ($payment_gateway_label): void {
    ?>
    <select id="<?php echo esc_attr($select_id); ?>" class="sltr-payment-method-hidden" aria-hidden="true" tabindex="-1">
        <?php foreach ($gateways as $gateway_id => $gateway) : ?>
            <option value="<?php echo esc_attr((string) $gateway_id); ?>"><?php echo esc_html($payment_gateway_label((string) $gateway_id, $gateway)); ?></option>
        <?php endforeach; ?>
    </select>
    <div class="sltr-payment-method-cards" data-select-id="<?php echo esc_attr($select_id); ?>">
        <?php $i = 0; foreach ($gateways as $gateway_id => $gateway) : $gateway_id = (string) $gateway_id; ?>
            <button type="button" class="sltr-payment-method-card<?php echo $i === 0 ? ' is-selected' : ''; ?>" data-payment-method="<?php echo esc_attr($gateway_id); ?>" data-wallet="<?php echo esc_attr(in_array($gateway_id, ['apple_pay','google_pay'], true) ? $gateway_id : ''); ?>">
                <?php echo esc_html($payment_gateway_label($gateway_id, $gateway)); ?>
            </button>
        <?php $i++; endforeach; ?>
    </div>
    <?php
};
$payment_checkout_available = !empty($payment_gateways);
// Checkout options are package-level settings. The legacy global payment selector is no longer rendered.
$payment_mode_enabled = false;
$prepayment_mode_enabled = false;
$show_payment_selector = false;
$payment_required_unavailable = false;
$show_payment_unavailable_notice = false;
$columns_desktop = max(1, min(4, (int) ($settings['package_columns_desktop'] ?? 3)));
$columns_tablet = max(1, min(3, (int) ($settings['package_columns_tablet'] ?? 2)));
$columns_mobile = 1;
$booking_form_width_mode = sanitize_key((string) ($settings['booking_form_width_mode'] ?? '1280'));
if (!in_array($booking_form_width_mode, ['full', '1100', '1280', 'custom'], true)) {
    $booking_form_width_mode = '1280';
}
$booking_form_custom_width = max(800, min(2400, (int) ($settings['booking_form_custom_width'] ?? 1280)));
$booking_form_max_width = $booking_form_width_mode === 'custom' ? $booking_form_custom_width . 'px' : ($booking_form_width_mode === 'full' ? 'none' : $booking_form_width_mode . 'px');
$booking_form_width = $booking_form_width_mode === 'full' ? '100%' : '100%';
$select_time_layout = (string) ($settings['select_time_layout'] ?? 'grid');
if (!in_array($select_time_layout, ['list', 'grid'], true)) {
    $select_time_layout = 'grid';
}

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

$currency = \Slotera\Application\Services\CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
$currency_position = \Slotera\Application\Services\CurrencyService::normalize_position((string) ($settings['payment_currency_position'] ?? 'right'));
$format_price = static function (float $amount) use ($currency, $currency_position): string {
    return \Slotera\Application\Services\CurrencyService::format($amount, $currency, $currency_position);
};
$format_price_with_unit = static function (float $amount, string $price_unit = '') use ($currency, $currency_position): string {
    return \Slotera\Application\Services\CurrencyService::format_with_unit($amount, $currency, $currency_position, $price_unit);
};
$pricing_adjustments = new \Slotera\Application\Services\PricingAdjustmentService();
$date_inventory_service = new \Slotera\Application\Services\DateRangeInventoryService();
$coupon_service = new \Slotera\Application\Services\CouponService();
$available_coupons = (new \Slotera\Infrastructure\Repositories\CouponRepository())->get_all();
$has_available_coupon_for_package = static function (int $package_id, float $package_amount) use ($available_coupons): bool {
    $now = current_time('timestamp');

    foreach ($available_coupons as $coupon) {
        if ((int) ($coupon['is_active'] ?? 0) !== 1) {
            continue;
        }

        $expires_at = trim((string) ($coupon['expires_at'] ?? ''));
        if ($expires_at !== '') {
            $expires = strtotime($expires_at . ' 23:59:59');
            if ($expires && $expires < $now) {
                continue;
            }
        }

        $usage_limit = max(0, (int) ($coupon['usage_limit'] ?? 0));
        if ($usage_limit > 0 && (int) ($coupon['used_count'] ?? 0) >= $usage_limit) {
            continue;
        }

        $allowed_packages = array_filter(array_map('absint', explode(',', (string) ($coupon['package_ids'] ?? ''))));
        if ($allowed_packages && !in_array($package_id, $allowed_packages, true)) {
            continue;
        }

        $minimum_amount = max(0, (float) ($coupon['min_amount'] ?? 0));
        if ($minimum_amount > 0 && $package_amount < $minimum_amount) {
            continue;
        }

        return true;
    }

    return false;
};
$tax_label_text = static function (array $tax): string {
    $label = trim((string) ($tax['tax_label'] ?? 'VAT'));
    $rate = (float) ($tax['tax_rate'] ?? 0);
    return $rate > 0 ? sprintf('%s (%.2f%%)', $label !== '' ? $label : 'Tax', $rate) : ($label !== '' ? $label : 'Tax');
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

    if ($discount_type === 'percent' && $discount_value > 0) {
        return '-' . (int) min(100, $discount_value) . '%';
    }

    if ($discount_type === 'fixed' && $discount_value > 0) {
        $percent = (int) round((min($price, $discount_value) / $price) * 100);
        return '-' . max(1, $percent) . '%';
    }

    return '';
};


$mode_config = static function (array $package, string $mode): array {
    $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
    return is_array($configs) && isset($configs[$mode]) && is_array($configs[$mode]) ? $configs[$mode] : [];
};
$format_duration = static fn ($minutes): string => \Slotera\Application\Services\FrontendDurationFormatter::format((int) $minutes);

$campaign_note_for_package = static function (array $package, array $active_mode_config): string {
    $note = trim((string) ($active_mode_config['campaign_note'] ?? ''));
    if ($note === '' && array_key_exists('campaign_note', $package)) {
        $note = trim((string) $package['campaign_note']);
    }
    return $note;
};

$get_date_flow = static function (array $package): string {
    $flow = sanitize_key((string) ($package['date_flow'] ?? ''));
    if ($flow === '') {
        $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        $flow = is_array($configs) ? sanitize_key((string) ($configs['date_range_inventory']['date_flow'] ?? '')) : '';
    }
    return in_array($flow, ['customer_choice', 'admin_scheduled'], true) ? $flow : 'customer_choice';
};
?>
<div
    class="sltr-booking sltr-booking-flow sltr-theme-<?php echo esc_attr($theme); ?> sltr-time-layout-<?php echo esc_attr($select_time_layout); ?>"
    id="sltr-booking"
    data-sltr-version="<?php echo esc_attr(SLTR_VERSION); ?>"
    data-payment-required-unavailable="<?php echo esc_attr($payment_required_unavailable ? '1' : '0'); ?>"
    style="<?php echo esc_attr($style_vars); ?>"
>
    <?php if ($show_slotera_attribution) : ?>
        <div class="sltr-platform-attribution">
            <a href="<?php echo esc_url($slotera_platform_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="Powered by Slotera">Powered by <strong>Slotera</strong></a>
        </div>
    <?php endif; ?>

    <div class="sltr-progress" aria-label="<?php echo esc_attr(sltr_t('Booking progress')); ?>">
        <span class="is-active" data-progress="1"><b>1</b><small><?php echo esc_html(sltr_t('Service')); ?></small></span>
        <span data-progress="2"><b>2</b><small><?php echo esc_html(sltr_t('Date')); ?></small></span>
        <span data-progress="3"><b>3</b><small><?php echo esc_html(sltr_t('Time')); ?></small></span>
        <span data-progress="4"><b>4</b><small><?php echo esc_html(sltr_t('Details')); ?></small></span>
    </div>

    <div class="sltr-message-center" id="sltr-message-center" aria-label="<?php echo esc_attr(sltr_t('Status')); ?>" aria-live="polite">
        <p class="sltr-message" id="sltr-global-message"></p>
        <p class="sltr-message" id="sltr-coupon-message"></p>
    </div>

    <div class="sltr-step" data-step="1">
        <h3><?php echo esc_html(sltr_t('Select service')); ?></h3>
        <div class="sltr-packages">
            <?php if (!empty($packages)) : ?>
                <?php foreach ($packages as $package) : ?>
                    <?php
                    $price = max(0, (float) ($package['price'] ?? 0));
                    $dynamic_preview = $pricing_adjustments->apply_dynamic($package, $price, ['booking_date' => current_time('Y-m-d')]);
                    $dynamic_label_preview = (string) ($dynamic_preview['dynamic_label'] ?? '');
                    $price_for_display = (float) ($dynamic_preview['dynamic_amount'] ?? $price);
                    $dynamic_adjustment_amount = (float) ($dynamic_preview['dynamic_adjustment_amount'] ?? 0);
                    $package_preview = array_merge($package, ['price' => $price_for_display]);
                    $final_before_tax = $get_final_price($package_preview);
                    $tax_preview = $pricing_adjustments->apply_tax($package, $final_before_tax);
                    $final_price = (float) ($tax_preview['total_amount'] ?? $final_before_tax);
                    $tax_amount_preview = (float) ($tax_preview['tax_amount'] ?? 0);
                    $tax_label_preview = $tax_label_text($tax_preview);
                    $has_dynamic_offer = $dynamic_label_preview !== '' && $dynamic_adjustment_amount < 0 && $price_for_display < $price;
                    $has_discount = $has_dynamic_offer || $final_before_tax < $price;
                    $discount_label = $get_discount_label($package, $price, $final_before_tax);
                    $package_discount_summary_label = $discount_label !== '' ? sltr_t('Package discount') . ' ' . $discount_label : '';
                    $booking_mode = (string) ($package['booking_mode'] ?? 'fixed');
                    if ($booking_mode === 'flexible') { $booking_mode = 'flex'; }
                    $active_mode_config = $mode_config($package, $booking_mode);
                    $show_duration_for_package = in_array($booking_mode, ['fixed', 'flex'], true) && ((array_key_exists('show_duration_frontend', $package) && (int) $package['show_duration_frontend'] === 1) || (!array_key_exists('show_duration_frontend', $package) && !empty($active_mode_config['show_duration'])));
                    $date_flow = $booking_mode === 'date_range_inventory' ? $get_date_flow($package) : '';
                    $next_scheduled = ($booking_mode === 'date_range_inventory' && $date_flow === 'admin_scheduled') ? ($date_inventory_service->scheduled_event_options($package)[0] ?? null) : null;
                    if (!empty($next_scheduled)) {
                        $next_quote = !empty($next_scheduled['quote']) && is_array($next_scheduled['quote'])
                            ? $pricing_adjustments->apply_to_quote($package, $next_scheduled['quote'], [
                                'start_date' => (string) ($next_scheduled['start_date'] ?? ''),
                                'end_date' => (string) ($next_scheduled['end_date'] ?? ''),
                            ])
                            : [];
                        $price = max(0, (float) ($next_scheduled['price'] ?? $price));
                        $price_for_display = max(0, (float) ($next_quote['dynamic_amount'] ?? $price));
                        $dynamic_adjustment_amount = (float) ($next_quote['dynamic_adjustment_amount'] ?? 0);
                        $final_before_tax = max(0, (float) ($next_quote['pre_tax_amount'] ?? $price));
                        $final_price = max(0, (float) ($next_quote['total_amount'] ?? $price));
                        $dynamic_label_preview = (string) ($next_quote['dynamic_label'] ?? '');
                        $has_dynamic_offer = $dynamic_label_preview !== '' && $dynamic_adjustment_amount < 0 && $price_for_display < $price;
                        $has_discount = $has_dynamic_offer || $final_before_tax < $price;
                        $discount_label = $get_discount_label($package, $price, $final_before_tax);
                        $package_discount_summary_label = $discount_label !== '' ? sltr_t('Package discount') . ' ' . $discount_label : '';
                    }
                    $duration_data = $show_duration_for_package ? $format_duration($package['duration_minutes'] ?? 0) : '';
                    $display_price_unit = ($booking_mode === 'date_range_inventory' && $date_flow === 'customer_choice') ? (string) ($package['price_unit'] ?? '') : '';
                    $simple_price_mode = $booking_mode === 'simple' ? (string) ($package['price_unit'] ?? 'fixed') : '';
                    $simple_price_label = $simple_price_mode === 'request' ? sltr_t('Price on request') : ($simple_price_mode === 'from' ? sprintf(sltr_t('From %s'), $format_price($final_price)) : $format_price($final_price));
                    $campaign_note = $campaign_note_for_package($package, $active_mode_config);
                    $package_policy = (string) ($active_mode_config['payment_policy'] ?? ($package['payment_policy'] ?? 'booking_only'));
                    if ($package_policy === '__from_options') { $package_policy = 'booking_only'; }
                    $hide_payment_methods = !empty($active_mode_config['hide_payment_methods']);
                    $hide_price_on_frontend = !empty($active_mode_config['hide_price_on_frontend']);
                    $display_start_time_only = $booking_mode === 'flex' && !empty($active_mode_config['display_start_time_only']);
                    $fixed_full_day_booking = $booking_mode === 'fixed' && !empty($active_mode_config['full_day_booking']);
                    $package_deposit_type = (string) ($active_mode_config['deposit_type'] ?? ($package['deposit_type'] ?? 'percent'));
                    $package_deposit_value = (string) ($active_mode_config['deposit_value'] ?? ($package['deposit_value'] ?? '0'));
                    $low_availability_enabled = array_key_exists('low_availability_notice_enabled', $package) ? (int) $package['low_availability_notice_enabled'] : (array_key_exists('low_availability_notice_enabled', $active_mode_config) ? (int) $active_mode_config['low_availability_notice_enabled'] : 0);
                    $low_availability_threshold = max(1, min(99, (int) (array_key_exists('low_availability_threshold', $package) ? $package['low_availability_threshold'] : ($active_mode_config['low_availability_threshold'] ?? 5))));
                    $page_id = (int) ($package['page_id'] ?? 0);
                    $package_url = !empty($package['solo_page_enabled']) && $page_id > 0 ? get_permalink($page_id) : '';
                    $custom_booking_button_text = trim((string) ($active_mode_config['booking_button_text'] ?? ''));
                    $cta_label = $custom_booking_button_text !== '' ? $custom_booking_button_text : sltr_t('Book now');
                    $has_available_coupon = $has_available_coupon_for_package((int) ($package['id'] ?? 0), $coupon_service->package_sale_price($package));
                    $booking_card_image_id = absint($package['booking_card_image_id'] ?? 0);
                    $booking_card_image_url = $booking_card_image_id > 0 ? wp_get_attachment_image_url($booking_card_image_id, 'large') : false;
                    $booking_focus_parts = array_map('intval', explode(',', (string) ($package['booking_card_image_focus'] ?? '50,50')));
                    $booking_focus = max(0, min(100, $booking_focus_parts[0] ?? 50)) . '% ' . max(0, min(100, $booking_focus_parts[1] ?? 50)) . '%';
                    $popular_icons = ['star' => '★', 'fire' => '🔥', 'crown' => '♛', 'heart' => '♥', 'bolt' => '⚡'];
                    $popular_glyph = $popular_icons[(string) ($package['popular_icon'] ?? 'star')] ?? '★';
                    ?>
                    <div class="sltr-package sltr-package-card<?php echo (!empty($package['show_more_info']) && $package_url !== '') ? ' has-more-info' : ' no-more-info'; ?><?php echo $booking_card_image_url ? ' has-card-image' : ''; ?>" data-id="<?php echo esc_attr((string) $package['id']); ?>" data-title="<?php echo esc_attr((string) $package['title']); ?>" data-duration="<?php echo esc_attr($duration_data); ?>" data-price="<?php echo esc_attr($simple_price_mode === 'request' ? sltr_t('Price on request') : ($simple_price_mode === 'from' ? sprintf(sltr_t('From %s'), $format_price($final_price)) : $format_price($final_price))); ?>" data-price-raw="<?php echo esc_attr((string) $final_price); ?>" data-mode="<?php echo esc_attr($booking_mode); ?>" data-date-flow="<?php echo esc_attr($date_flow); ?>" data-policy="<?php echo esc_attr($package_policy); ?>" data-deposit-type="<?php echo esc_attr($package_deposit_type); ?>" data-deposit-value="<?php echo esc_attr($package_deposit_value); ?>" data-included="<?php echo esc_attr((string) ($package['included_services'] ?? '')); ?>" data-price-mode="<?php echo esc_attr($simple_price_mode); ?>" data-campaign-note="<?php echo esc_attr($campaign_note); ?>" data-dynamic-label="<?php echo esc_attr($dynamic_label_preview); ?>" data-package-discount-label="<?php echo esc_attr($package_discount_summary_label); ?>" data-tax-amount="<?php echo esc_attr((string) $tax_amount_preview); ?>" data-tax-label="<?php echo esc_attr($tax_label_preview); ?>" data-low-availability-enabled="<?php echo esc_attr((string) $low_availability_enabled); ?>" data-low-availability-threshold="<?php echo esc_attr((string) $low_availability_threshold); ?>" data-hide-payment-methods="<?php echo esc_attr($hide_payment_methods ? '1' : '0'); ?>" data-hide-price-on-frontend="<?php echo esc_attr($hide_price_on_frontend ? '1' : '0'); ?>" data-display-start-time-only="<?php echo esc_attr($display_start_time_only ? '1' : '0'); ?>" data-full-day-booking="<?php echo esc_attr($fixed_full_day_booking ? '1' : '0'); ?>" data-booking-button-text="<?php echo esc_attr($cta_label); ?>" data-has-available-coupon="<?php echo esc_attr($has_available_coupon ? '1' : '0'); ?>" data-extra-services="<?php echo esc_attr((string) ($active_mode_config['extra_services_json'] ?? '')); ?>">
                        <?php if (!empty($package['is_popular'])) : ?>
                            <span class="sltr-badge-popular sltr-badge-popular-icon" style="--sltr-featured-icon-color:<?php echo esc_attr(sanitize_hex_color((string) ($package['popular_icon_color'] ?? '#7c3aed')) ?: '#7c3aed'); ?>;--sltr-featured-icon-size:<?php echo esc_attr((string) max(16, min(48, (int) ($package['popular_icon_size'] ?? 24)))); ?>px;color:<?php echo esc_attr(sanitize_hex_color((string) ($package['popular_icon_color'] ?? '#7c3aed')) ?: '#7c3aed'); ?>!important;font-size:<?php echo esc_attr((string) max(16, min(48, (int) ($package['popular_icon_size'] ?? 24)))); ?>px!important" aria-label="<?php echo esc_attr(sltr_t('Featured package')); ?>"><span aria-hidden="true"><?php echo esc_html($popular_glyph); ?></span></span>
                        <?php endif; ?>
                        <?php if ($has_discount && $discount_label !== '') : ?>
                            <span class="sltr-badge-discount"><?php echo esc_html($discount_label); ?></span>
                        <?php endif; ?>

                        <?php if ($booking_card_image_url) : ?>
                            <div class="sltr-package-card-media"><img src="<?php echo esc_url($booking_card_image_url); ?>" alt="<?php echo esc_attr((string) ($package['title'] ?? '')); ?>" loading="lazy" style="--sltr-image-focus:<?php echo esc_attr($booking_focus); ?>;object-position:var(--sltr-image-focus)"></div>
                        <?php endif; ?>

                        <div class="sltr-package-title-row">
                            <?php if (!empty($package['info_tooltip'])) : ?>
                                <button type="button" class="sltr-package-info-button" style="--sltr-tooltip-size-ratio:<?php echo esc_attr((string) max(0.8, min(2.0, (float) ($package['tooltip_size_ratio'] ?? 1.15)))); ?>;--sltr-tooltip-text-size:<?php echo esc_attr((string) max(10, min(24, (int) ($package['tooltip_text_size'] ?? 13)))); ?>px;" aria-label="<?php echo esc_attr(sltr_t('Package information')); ?>"><span aria-hidden="true">i</span><span class="sltr-tooltip-content" role="tooltip"><?php echo esc_html(wp_strip_all_tags((string) $package['info_tooltip'])); ?></span></button>
                            <?php endif; ?>
                            <strong style="<?php echo esc_attr(trim(((string) ($package['title_font_family'] ?? '') !== '' ? 'font-family:' . (string) $package['title_font_family'] . ';' : '') . 'font-size:' . (string) max(12, min(48, (int) (($package['title_font_size'] ?? 24) ?: 24))) . 'px;')); ?>"><?php echo esc_html($package['title']); ?></strong>
                        </div>

                        <?php if ($show_duration_for_package) : ?>
                            <span class="sltr-package-meta"><?php echo esc_html($format_duration($package['duration_minutes'] ?? 0)); ?></span>
                        <?php endif; ?>

                        <?php if ($campaign_note !== '') : ?>
                            <span class="sltr-urgency-note"><?php echo esc_html($campaign_note); ?></span>
                        <?php endif; ?>

                        <?php if (!$hide_price_on_frontend) : ?>
                        <span class="sltr-package-price">
                            <?php if ($booking_mode === 'simple' && $simple_price_mode === 'request') : ?>
                                <b><?php echo esc_html(sltr_t('Price on request')); ?></b>
                            <?php elseif ($booking_mode === 'simple' && $simple_price_mode === 'from') : ?>
                                <b><?php echo esc_html(sprintf(sltr_t('From %s'), $format_price($final_price))); ?></b>
                            <?php elseif ($has_discount) : ?>
                                <del class="sltr-old-price"><?php echo esc_html($format_price_with_unit($price, $display_price_unit)); ?></del>
                                <b class="sltr-new-price"><?php echo esc_html($format_price_with_unit($final_price, $display_price_unit)); ?></b>
                            <?php else : ?>
                                <b><?php echo esc_html($format_price_with_unit($final_price, $display_price_unit)); ?></b>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!$hide_price_on_frontend && $dynamic_label_preview !== '') : ?>
                            <span class="sltr-dynamic-offer-note"><?php echo esc_html($dynamic_label_preview); ?></span>
                        <?php endif; ?>
                        <?php if (!$hide_price_on_frontend && $tax_amount_preview > 0) : ?>
                            <span class="sltr-tax-note"><?php echo esc_html(sprintf(sltr_t('%s included in total'), $tax_label_preview)); ?></span>
                        <?php endif; ?>

                        <?php if (!empty($package['show_more_info']) && $package_url !== '') : ?>
                            <div class="sltr-package-card-link-row">
                                <a class="sltr-more-info-link" href="<?php echo esc_url($package_url); ?>"><?php echo esc_html(sltr_t('More info')); ?></a>
                            </div>
                        <?php endif; ?>

                        <button type="button" class="sltr-button sltr-select-button sltr-package-select"><?php echo esc_html($cta_label); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p><?php echo esc_html(sltr_t('No packages available.')); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="sltr-step" data-step="2" style="display:none;">
        <h3 id="sltr-step-date-title"><?php echo esc_html(sltr_t('Select date')); ?></h3>
        <input type="hidden" id="sltr-date" value="">
        <div class="sltr-date-range-fields" id="sltr-date-range-fields" style="display:none;">
            <label><?php echo esc_html(sltr_t('From')); ?><br><input type="date" id="sltr-range-start"></label>
            <label><?php echo esc_html(sltr_t('To')); ?><br><input type="date" id="sltr-range-end"></label>
            <button type="button" class="sltr-button" id="sltr-check-range"><?php echo esc_html(sltr_t('Show available rooms')); ?></button>
        </div>

        <div class="sltr-calendar" id="sltr-calendar">
            <div class="sltr-calendar-header">
                <button type="button" class="sltr-calendar-prev" aria-label="<?php echo esc_attr(sltr_t('Previous month')); ?>">‹</button>
                <strong class="sltr-calendar-title"></strong>
                <button type="button" class="sltr-calendar-next" aria-label="<?php echo esc_attr(sltr_t('Next month')); ?>">›</button>
            </div>
            <div class="sltr-calendar-weekdays">
                <span><?php echo esc_html(sltr_t('Mon')); ?></span><span><?php echo esc_html(sltr_t('Tue')); ?></span><span><?php echo esc_html(sltr_t('Wed')); ?></span><span><?php echo esc_html(sltr_t('Thu')); ?></span><span><?php echo esc_html(sltr_t('Fri')); ?></span><span><?php echo esc_html(sltr_t('Sat')); ?></span><span><?php echo esc_html(sltr_t('Sun')); ?></span>
            </div>
            <div class="sltr-calendar-days"></div>
        </div>

        <div class="sltr-inline-availability" id="sltr-inline-availability" aria-live="polite"></div>
        <p class="sltr-message" id="sltr-date-message"></p>
        <p><button type="button" class="sltr-back" data-back="1"><?php echo esc_html(sltr_t('Back to services')); ?></button></p>
    </div>

    <div class="sltr-step" data-step="3" style="display:none;">
        <h3 id="sltr-step-time-title"><?php echo esc_html(sltr_t('Select option')); ?></h3>
        <div id="sltr-date-range-results" class="sltr-date-range-results" style="display:none;"></div>
        <div id="sltr-included-services" class="sltr-included-services" style="display:none;"></div>
        <div id="sltr-extra-services" class="sltr-extra-services" style="display:none;"></div>
        <div id="sltr-slots" class="sltr-slots"></div>
        <div id="sltr-fixed-full-day-end" hidden>
            <p><label><strong><?php echo esc_html(sltr_t('To')); ?></strong><br><input type="date" id="sltr-fixed-end-date"></label></p>
            <p><strong><?php echo esc_html(sltr_t('Total')); ?>:</strong> <span id="sltr-fixed-full-day-total">—</span></p>
            <p><button type="button" class="sltr-button" id="sltr-fixed-full-day-continue"><?php echo esc_html(sltr_t('Continue')); ?></button></p>
        </div>
        <p class="sltr-message" id="sltr-slot-message"></p>
        <p><button type="button" class="sltr-back" data-back="2"><?php echo esc_html(sltr_t('Back to calendar')); ?></button></p>
    </div>

    <div class="sltr-step" data-step="4" style="display:none;">
        <h3><?php echo esc_html(sltr_t('Your details')); ?></h3>

        <div id="sltr-details-extra-services" class="sltr-extra-services" hidden></div>

        <div class="sltr-booking-summary" aria-live="polite">
            <div>
                <span><?php echo esc_html(sltr_t('Service')); ?>:</span>
                <strong id="sltr-summary-service">—</strong>
            </div>
            <div>
                <span><?php echo esc_html(sltr_t('Date')); ?>:</span>
                <strong id="sltr-summary-date">—</strong>
            </div>
            <div id="sltr-summary-time-wrap">
                <span><?php echo esc_html(sltr_t('Time')); ?>:</span>
                <strong id="sltr-summary-time">—</strong>
            </div>
            <div class="sltr-summary-payment" id="sltr-summary-total-wrap" style="display:none;">
                <span><?php echo esc_html(sltr_t('Total')); ?>:</span>
                <strong id="sltr-summary-total">—</strong>
            </div>
            <div class="sltr-summary-payment" id="sltr-summary-dynamic-wrap" style="display:none;">
                <span><?php echo esc_html(sltr_t('Discounts')); ?>:</span>
                <strong id="sltr-summary-dynamic">—</strong>
            </div>
            <div class="sltr-summary-payment" id="sltr-summary-tax-wrap" style="display:none;">
                <span id="sltr-summary-tax-label"><?php echo esc_html(sltr_t('Tax')); ?>:</span>
                <strong id="sltr-summary-tax">—</strong>
            </div>
            <div class="sltr-summary-payment" id="sltr-summary-pay-now-wrap" style="display:none;">
                <span><?php echo esc_html(sltr_t('Pay now')); ?>:</span>
                <strong id="sltr-summary-pay-now">—</strong>
            </div>
            <div class="sltr-summary-payment" id="sltr-summary-pay-later-wrap" style="display:none;">
                <span><?php echo esc_html(sltr_t('Pay later')); ?>:</span>
                <strong id="sltr-summary-pay-later">—</strong>
            </div>
        </div>
        <p class="sltr-urgency-note sltr-summary-urgency" id="sltr-summary-urgency" style="display:none;"></p>

        <div class="sltr-fields sltr-coupon-box" id="sltr-coupon-box" style="display:none;">
            <label>
                <span><?php echo esc_html(sltr_t('Coupon code')); ?></span>
                <input type="text" id="sltr-coupon-code" placeholder="<?php echo esc_attr(sltr_t('Enter coupon code')); ?>" autocomplete="off">
            </label>
            <button type="button" class="sltr-button sltr-apply-coupon" id="sltr-apply-coupon"><?php echo esc_html(sltr_t('Apply coupon')); ?></button>
            <div class="sltr-coupon-summary" id="sltr-coupon-summary" style="display:none;">
                <span><?php echo esc_html(sltr_t('Coupon discount')); ?></span>
                <strong id="sltr-summary-coupon-discount">—</strong>
                <span><?php echo esc_html(sltr_t('Final amount')); ?></span>
                <strong id="sltr-summary-final-amount">—</strong>
            </div>
        </div>

        <div class="sltr-fields">
            <?php foreach ($booking_form_fields as $field_key => $field) : ?>
                <?php if (empty($field['enabled']) || $field_key === 'notes') { continue; } ?>
                <label>
                    <span><?php echo esc_html((string) $field['label']); ?><?php echo !empty($field['required']) ? ' *' : ''; ?></span>
                    <input
                        type="<?php echo esc_attr((string) $field['type']); ?>"
                        id="sltr-<?php echo esc_attr($field_key); ?>"
                        placeholder="<?php echo esc_attr((string) $field['placeholder']); ?>"
                        autocomplete="<?php echo esc_attr((string) $field['autocomplete']); ?>"
                        data-field-key="<?php echo esc_attr($field_key); ?>"
                        data-required="<?php echo esc_attr(!empty($field['required']) ? '1' : '0'); ?>"
                        <?php echo esc_attr(!empty($field['required']) ? 'required' : ''); ?>
                    >
                </label>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($booking_form_fields['notes']['enabled'])) : ?>
            <div class="sltr-fields sltr-notes-field">
                <label>
                    <span><?php echo esc_html((string) $booking_form_fields['notes']['label']); ?><?php echo !empty($booking_form_fields['notes']['required']) ? ' *' : ''; ?></span>
                    <textarea
                        id="sltr-notes"
                        rows="5"
                        placeholder="<?php echo esc_attr((string) $booking_form_fields['notes']['placeholder']); ?>"
                        data-field-key="notes"
                        data-required="<?php echo esc_attr(!empty($booking_form_fields['notes']['required']) ? '1' : '0'); ?>"
                        <?php echo esc_attr(!empty($booking_form_fields['notes']['required']) ? 'required' : ''); ?>
                    ></textarea>
                </label>
            </div>
        <?php endif; ?>

        <div class="sltr-fields sltr-marketing-consent">
            <label class="sltr-marketing-consent-card">
                <span class="sltr-marketing-consent-text"><?php echo esc_html(sltr_t('I agree to receive optional marketing emails. I can unsubscribe at any time.')); ?></span>
                <input type="checkbox" id="sltr-marketing-consent" value="1">
            </label>
        </div>

        <?php if ($show_payment_unavailable_notice) : ?>
            <div class="sltr-fields sltr-payment-unavailable">
                <p><?php echo esc_html(sltr_t('Online payment is currently unavailable. Please contact us or choose another booking option.')); ?></p>
            </div>
        <?php endif; ?>

        <div class="sltr-fields sltr-date-range-payment" id="sltr-date-range-payment" style="display:none;" data-gateways-available="<?php echo esc_attr(!empty($payment_gateways) ? '1' : '0'); ?>">
            <div id="sltr-payment-choice-wrap" class="sltr-payment-choice-wrap" style="display:none;">
                <span class="sltr-payment-section-title"><?php echo esc_html(sltr_t('Choose payment option')); ?></span>
                <select id="sltr-payment-choice" class="sltr-payment-choice-hidden" aria-hidden="true" tabindex="-1">
                    <option value="booking_only"><?php echo esc_html(sltr_t('Pay on arrival')); ?></option>
                    <option value="full_payment"><?php echo esc_html(sltr_t('Pay in full')); ?></option>
                    <option value="deposit_payment"><?php echo esc_html(sltr_t('Prepay / deposit')); ?></option>
                </select>
                <div id="sltr-payment-option-cards" class="sltr-payment-option-cards"></div>
            </div>
            <label id="sltr-date-range-gateway-wrap" style="display:none;">
                <span><?php echo esc_html(sltr_t('Payment method')); ?></span>
                <?php $render_payment_method_picker('sltr-date-range-payment-method', $payment_gateways); ?>
            </label>
            <p class="description" id="sltr-pay-on-arrival-note" style="display:none;"><?php echo esc_html(sltr_t('No online payment is required now. Payment is due on arrival.')); ?></p>
        </div>

        <?php if ($show_payment_selector) : ?>
            <div class="sltr-fields sltr-payment-methods" data-payment-mode-enabled="<?php echo esc_attr($payment_mode_enabled ? '1' : '0'); ?>" data-prepayment-mode-enabled="<?php echo esc_attr($prepayment_mode_enabled ? '1' : '0'); ?>">
                <?php if ($payment_mode_enabled) : ?>
                    <label>
                        <span><?php echo esc_html(sltr_t('Payment method')); ?></span>
                        <?php $render_payment_method_picker('sltr-payment-method', $payment_gateways); ?>
                    </label>
                <?php endif; ?>
                <?php if ($prepayment_mode_enabled) : ?>
                    <label>
                        <span><?php echo esc_html(sltr_t('PrePay method')); ?></span>
                        <?php $render_payment_method_picker('sltr-prepay-method', $payment_gateways); ?>
                    </label>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <script>
        (function(){
            function syncCards(wrapper){
                var selectId = wrapper.getAttribute('data-select-id');
                var select = selectId ? document.getElementById(selectId) : null;
                if (!select) { return; }
                var buttons = Array.prototype.slice.call(wrapper.querySelectorAll('[data-payment-method]'));
                buttons.forEach(function(button){
                    button.addEventListener('click', function(){
                        if (button.hidden) { return; }
                        select.value = button.getAttribute('data-payment-method') || '';
                        buttons.forEach(function(b){ b.classList.toggle('is-selected', b === button); });
                        select.dispatchEvent(new Event('change', {bubbles:true}));
                    });
                });
                var current = buttons.find(function(button){ return !button.hidden && button.getAttribute('data-payment-method') === select.value; }) || buttons.find(function(button){ return !button.hidden; });
                if (current) { current.click(); }
            }
            function detectWallets(){
                var appleAvailable = !!(window.ApplePaySession && ApplePaySession.canMakePayments && ApplePaySession.canMakePayments());
                document.querySelectorAll('[data-wallet="apple_pay"]').forEach(function(button){ button.hidden = !appleAvailable; });
                document.querySelectorAll('[data-wallet="google_pay"]').forEach(function(button){
                    // Stripe Checkout performs the final wallet eligibility check. Keep Google Pay visible on modern browsers.
                    button.hidden = !(window.PaymentRequest || window.google);
                });
            }
            detectWallets();
            document.querySelectorAll('.sltr-payment-method-cards').forEach(syncCards);
        }());
        </script>

        <?php if (!empty($settings['security_honeypot_enabled'])) : ?>
            <div class="sltr-honeypot" aria-hidden="true">
                <label for="sltr-company-website"><?php echo esc_html(sltr_t('Company website')); ?></label>
                <input type="text" id="sltr-company-website" name="company_website" value="" tabindex="-1" autocomplete="off">
            </div>
        <?php endif; ?>
        <input type="hidden" id="sltr-form-started-at" name="form_started_at" value="<?php echo esc_attr((string) time()); ?>">

        <?php if ($security_captcha_provider === 'turnstile' && $turnstile_site_key !== '') : ?>
            <div class="sltr-captcha sltr-turnstile">
                <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($turnstile_site_key); ?>"></div>
            </div>
        <?php elseif ($security_captcha_provider === 'recaptcha' && $recaptcha_site_key !== '') : ?>
            <div class="sltr-captcha sltr-recaptcha">
                <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>"></div>
            </div>
        <?php endif; ?>

        <div class="sltr-form-actions">
            <button type="button" class="sltr-back" id="sltr-details-back" data-back="3"><?php echo esc_html(sltr_t('Back to time')); ?></button>
            <button type="button" id="sltr-submit" data-default-label="<?php echo esc_attr(sltr_t('Book')); ?>" data-payment-label="<?php echo esc_attr(sltr_t('Pay&Book')); ?>" data-prepayment-label="<?php echo esc_attr(sltr_t('PrePay&Book')); ?>" <?php echo $payment_required_unavailable ? 'disabled aria-disabled="true"' : ''; ?>><?php echo esc_html($payment_required_unavailable ? sltr_t('Payment unavailable') : ($payment_mode_enabled ? sltr_t('Pay&Book') : ($prepayment_mode_enabled ? sltr_t('PrePay&Book') : sltr_t('Book')))); ?></button>
        </div>
        <p class="sltr-message" id="sltr-message"></p>
    </div>
</div>
