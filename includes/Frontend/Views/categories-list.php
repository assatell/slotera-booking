<?php
if (!defined('ABSPATH')) { exit; }

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

$theme = (string) ($settings['appearance_theme'] ?? 'light');
$allowed_themes = ['light', 'dark', 'soft', 'minimal', 'custom'];
if (!in_array($theme, $allowed_themes, true)) { $theme = 'light'; }

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
    '--sltr-booking-form-width:100%%;--sltr-booking-form-max-width:%s;--sltr-package-columns-desktop:%d;--sltr-package-columns-tablet:%d;--sltr-package-columns-mobile:%d;--sltr-price-old-decoration:%s;--sltr-price-old-ratio:%s;--sltr-tooltip-size-ratio:%s;--sltr-tooltip-text-size:%spx;%s',
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

$package_repo = new \Slotera\Infrastructure\Repositories\PackageRepository();
$booking_page_url = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->get_page_url('booking');
if ($booking_page_url === '') { $booking_page_url = '#sltr-booking'; }
$currency = \Slotera\Application\Services\CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
$currency_position = \Slotera\Application\Services\CurrencyService::normalize_position((string) ($settings['payment_currency_position'] ?? 'right'));
$format_price = static function (float $amount, string $price_unit = '') use ($currency, $currency_position): string {
    return \Slotera\Application\Services\CurrencyService::format_with_unit($amount, $currency, $currency_position, $price_unit);
};
$get_final_price = static function (array $package): float {
    $price = max(0, (float) ($package['price'] ?? 0));
    $discount_type = (string) ($package['discount_type'] ?? 'none');
    $discount_value = max(0, (float) ($package['discount_value'] ?? 0));
    if ($discount_type === 'percent') { return max(0, $price - ($price * min(100, $discount_value) / 100)); }
    if ($discount_type === 'fixed') { return max(0, $price - $discount_value); }
    return $price;
};
$format_duration = static fn ($minutes): string => \Slotera\Application\Services\FrontendDurationFormatter::format((int) $minutes);
?>

<div
    class="sltr-categories-page sltr-packages-list-wrapper sltr-theme-<?php echo esc_attr($theme); ?>"
    id="sltr-categories"
    data-sltr-version="<?php echo esc_attr(SLTR_VERSION); ?>"
    style="<?php echo esc_attr($style_vars); ?>"
>
    <div class="sltr-categories-form">
        <?php if (!empty($categories)) : ?>
            <div class="sltr-categories-stack" role="list">
                <?php foreach ($categories as $category) : ?>
                    <?php
                    $category_id = absint($category['id'] ?? 0);
                    $category_name = trim((string) ($category['name'] ?? ''));
                    $category_description = trim((string) ($category['description'] ?? ''));
                    $category_packages = $category_id > 0 ? $package_repo->get_active_by_category($category_id, 100, 0) : [];
                    ?>
                    <section class="sltr-category-card-block" role="listitem" data-category-id="<?php echo esc_attr((string) $category_id); ?>">
                        <header class="sltr-category-card-header">
                            <h3><?php echo esc_html($category_name !== '' ? $category_name : sltr_t('Category')); ?></h3>
                            <?php if ($category_description !== '') : ?>
                                <div class="sltr-category-card-description"><?php echo wp_kses_post(wpautop($category_description)); ?></div>
                            <?php endif; ?>
                        </header>

                        <?php if (!empty($category_packages)) : ?>
                            <div class="sltr-category-packages sltr-packages-list" role="list">
                                <?php foreach ($category_packages as $package) : ?>
                                    <?php
                                    $package_id = absint($package['id'] ?? 0);
                                    $page_id = (int) ($package['page_id'] ?? 0);
                                    $package_url = !empty($package['solo_page_enabled']) && $page_id > 0 ? get_permalink($page_id) : '';
                                    $booking_url = add_query_arg(['sltr_package_id' => $package_id, 'sltr_step' => 'calendar'], $booking_page_url);
                                    if (strpos($booking_url, '#') === false) { $booking_url .= '#sltr-booking'; }
                                    $title = trim((string) ($package['title'] ?? ''));
                                    $description = trim((string) ($package['description'] ?? ''));
                                    $mode = (string) ($package['booking_mode'] ?? 'fixed');
                                    if ($mode === 'flexible') { $mode = 'flex'; }
                                    $mode_configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
                                    $active_mode_config = is_array($mode_configs) && isset($mode_configs[$mode]) && is_array($mode_configs[$mode]) ? $mode_configs[$mode] : [];
                                    $hide_price_on_frontend = !empty($active_mode_config['hide_price_on_frontend']);
                                    $custom_booking_button_text = trim((string) ($active_mode_config['booking_button_text'] ?? ''));
                                    $cta_label = $custom_booking_button_text !== '' ? $custom_booking_button_text : sltr_t('Book now');
                                    $price_unit = $mode === 'simple' ? (string) ($package['price_unit'] ?? '') : '';
                                    $price = max(0, (float) ($package['price'] ?? 0));
                                    $final_price = $get_final_price($package);
                                    $show_duration = in_array($mode, ['fixed', 'flex', 'flexible'], true);
                                    ?>
                                    <article class="sltr-package-card sltr-category-package-card no-more-info<?php echo $package_url !== '' ? ' is-clickable' : ''; ?>" role="listitem"<?php echo $package_url !== '' ? ' tabindex="0" data-package-url="' . esc_url($package_url) . '" aria-label="' . esc_attr(sprintf(sltr_t('Open %s package page'), $title !== '' ? $title : sltr_t('Package'))) . '"' : ''; ?>>
                                        <h3 style="<?php echo esc_attr(trim(((string) ($package['title_font_family'] ?? '') !== '' ? 'font-family:' . (string) $package['title_font_family'] . ';' : '') . 'font-size:' . (string) max(12, min(48, (int) (($package['title_font_size'] ?? 24) ?: 24))) . 'px;')); ?>"><?php echo esc_html($title !== '' ? $title : sltr_t('Package')); ?></h3>
                                        <?php if ($show_duration && !empty($package['duration_minutes'])) : ?>
                                            <p class="sltr-package-meta"><?php echo esc_html($format_duration($package['duration_minutes'])); ?></p>
                                        <?php endif; ?>
                                        <?php if ($description !== '') : ?>
                                            <p class="sltr-package-description"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($description), 22)); ?></p>
                                        <?php endif; ?>
                                        <?php if (!$hide_price_on_frontend) : ?>
                                        <p class="sltr-package-price">
                                            <?php if ($mode === 'simple' && $price_unit === 'request') : ?>
                                                <b><?php echo esc_html(sltr_t('Price on request')); ?></b>
                                            <?php elseif ($final_price < $price) : ?>
                                                <del class="sltr-old-price"><?php echo esc_html($format_price($price, $price_unit)); ?></del>
                                                <b class="sltr-new-price"><?php echo esc_html($format_price($final_price, $price_unit)); ?></b>
                                            <?php else : ?>
                                                <b><?php echo esc_html($format_price($final_price, $price_unit)); ?></b>
                                            <?php endif; ?>
                                        </p>
                                        <?php endif; ?>
                                        <a class="sltr-button sltr-select-button" href="<?php echo esc_url($booking_url); ?>"><?php echo esc_html($cta_label); ?></a>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p class="sltr-category-empty"><?php esc_html_e('No packages available in this category.', 'slotera-booking'); ?></p>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="sltr-category-empty"><?php esc_html_e('No categories found.', 'slotera-booking'); ?></p>
        <?php endif; ?>
    </div>
</div>

<script id="sltr-categories-card-click-js">
(function(){
    var root = document.getElementById('sltr-categories');
    if (!root) { return; }
    function isInteractive(target) {
        return !!(target && target.closest && target.closest('a,button,input,select,textarea,label,[role="button"]'));
    }
    root.addEventListener('click', function(event){
        var card = event.target && event.target.closest ? event.target.closest('.sltr-category-package-card[data-package-url]') : null;
        if (!card || !root.contains(card) || isInteractive(event.target)) { return; }
        var url = card.getAttribute('data-package-url');
        if (url) { window.location.href = url; }
    });
    root.addEventListener('keydown', function(event){
        if (event.key !== 'Enter' && event.key !== ' ') { return; }
        var card = event.target && event.target.closest ? event.target.closest('.sltr-category-package-card[data-package-url]') : null;
        if (!card || !root.contains(card) || isInteractive(event.target)) { return; }
        var url = card.getAttribute('data-package-url');
        if (url) {
            event.preventDefault();
            window.location.href = url;
        }
    });
})();
</script>
