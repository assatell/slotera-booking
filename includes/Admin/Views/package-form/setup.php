<?php
if (!defined('ABSPATH')) {
    exit;
}

// New-package screen passes $package as null. Keep the form defensive so helpers
// can safely read defaults before the package exists in the database.
if (!isset($package) || !is_array($package)) {
    $package = [];
}
if (!isset($package_hours) || !is_array($package_hours)) {
    $package_hours = [];
}

$id = (int) ($package['id'] ?? 0);
$sltr_event = [];
if ($id > 0) {
    $sltr_event_candidate = (new \Slotera\Infrastructure\Repositories\EventRepository())->get_first_for_package($id);
    if (is_array($sltr_event_candidate)) { $sltr_event = $sltr_event_candidate; }
}
$sltr_active_mode = sanitize_key((string) ($package['booking_mode'] ?? 'simple'));
if ($sltr_active_mode === 'flexible') { $sltr_active_mode = 'flex'; }
if (!in_array($sltr_active_mode, ['simple', 'fixed', 'flex', 'date_range_inventory'], true)) { $sltr_active_mode = 'simple'; }
$sltr_mode_configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
if (!is_array($sltr_mode_configs)) { $sltr_mode_configs = []; }
// Stable package-level persistence fallback for pricing adjustments. These fields
// are saved outside mode_configs_json for the active block, so reloads are not
// affected by nested checkbox/array serialization quirks.
$sltr_pricing_adjustment_keys = [
    'campaign_note', 'dynamic_pricing_enabled', 'dynamic_weekend_percent', 'dynamic_season_start',
    'dynamic_season_end', 'dynamic_season_percent', 'tax_enabled', 'tax_label',
    'tax_rate', 'tax_mode', 'low_availability_notice_enabled', 'low_availability_threshold',
];
foreach ($sltr_pricing_adjustment_keys as $sltr_adjustment_key) {
    if (array_key_exists($sltr_adjustment_key, $package)) {
        if (!isset($sltr_mode_configs[$sltr_active_mode]) || !is_array($sltr_mode_configs[$sltr_active_mode])) {
            $sltr_mode_configs[$sltr_active_mode] = [];
        }
        $sltr_mode_configs[$sltr_active_mode][$sltr_adjustment_key] = $package[$sltr_adjustment_key];
    }
}
$sltr_mode_value = static function (string $mode, string $key, $default = '') use ($sltr_mode_configs, $package, $sltr_active_mode) {
    if (isset($sltr_mode_configs[$mode]) && is_array($sltr_mode_configs[$mode]) && array_key_exists($key, $sltr_mode_configs[$mode])) {
        return $sltr_mode_configs[$mode][$key];
    }
    if ($mode === $sltr_active_mode && array_key_exists($key, $package)) {
        return $package[$key];
    }
    return $default;
};
$sltr_render_low_availability_fields = static function (string $mode) use ($sltr_mode_value): void {
    ?>
    <p>
        <label>
            <input type="hidden" class="sltr-bool-hidden" name="mode_config[<?php echo esc_attr($mode); ?>][low_availability_notice_enabled]" value="0">
            <input type="checkbox" class="sltr-bool-toggle" autocomplete="off" name="mode_config[<?php echo esc_attr($mode); ?>][low_availability_notice_enabled_checked]" value="1" <?php checked((int) $sltr_mode_value($mode, 'low_availability_notice_enabled', 0), 1); ?>>
            <?php esc_html_e('Show low availability notices', 'slotera-booking'); ?>
        </label>
        <span class="description"> <?php esc_html_e('Shows messages like “Only 1 spot left” during booking.', 'slotera-booking'); ?></span>
    </p>
    <p>
        <label><?php esc_html_e('Low availability threshold', 'slotera-booking'); ?></label><br>
        <input type="number" min="1" max="99" name="mode_config[<?php echo esc_attr($mode); ?>][low_availability_threshold]" value="<?php echo esc_attr((string) $sltr_mode_value($mode, 'low_availability_threshold', 5)); ?>" style="width:90px;">
        <span class="description"> <?php esc_html_e('Show the notice when remaining capacity is at or below this number.', 'slotera-booking'); ?></span>
    </p>
    <?php
};
$sltr_duration_parts = static function ($minutes): array {
    $minutes = max(0, (int) $minutes);
    return [intdiv($minutes, 60), $minutes % 60];
};
$sltr_duration_input = static function (string $base, $minutes, int $min_total = 0) use ($sltr_duration_parts): void {
    [$hours, $mins] = $sltr_duration_parts($minutes);
    echo '<span class="sltr-duration-input">';
    echo '<input type="number" min="0" max="999" class="small-text" name="' . esc_attr($base . '_hours') . '" value="' . esc_attr((string) $hours) . '"> ' . esc_html__('h', 'slotera-booking') . ' ';
    echo '<input type="number" min="0" max="59" class="small-text" name="' . esc_attr($base . '_mins') . '" value="' . esc_attr((string) $mins) . '"> ' . esc_html__('min', 'slotera-booking');
    echo '<input type="hidden" name="' . esc_attr($base === 'duration' ? 'duration_minutes' : $base) . '" value="' . esc_attr((string) max($min_total, (int) $minutes)) . '">';
    echo '</span>';
};
$sltr_mode_duration_input = static function (string $mode, string $key, $minutes, int $min_total = 0) use ($sltr_duration_parts): void {
    [$hours, $mins] = $sltr_duration_parts($minutes);
    $name_hours = 'mode_config[' . $mode . '][' . $key . '_hours]';
    $name_mins = 'mode_config[' . $mode . '][' . $key . '_mins]';
    $name_total = 'mode_config[' . $mode . '][' . $key . ']';
    echo '<span class="sltr-duration-input">';
    echo '<input type="number" min="0" max="999" class="small-text" name="' . esc_attr($name_hours) . '" value="' . esc_attr((string) $hours) . '"> ' . esc_html__('h', 'slotera-booking') . ' ';
    echo '<input type="number" min="0" max="59" class="small-text" name="' . esc_attr($name_mins) . '" value="' . esc_attr((string) $mins) . '"> ' . esc_html__('min', 'slotera-booking');
    echo '<input type="hidden" name="' . esc_attr($name_total) . '" value="' . esc_attr((string) max($min_total, (int) $minutes)) . '">';
    echo '</span>';
};

$sltr_scheduled_events = static function (string $mode) use ($sltr_mode_value): array {
    $json = (string) $sltr_mode_value($mode, 'scheduled_events_json', '');
    $events = json_decode($json, true);
    if (!is_array($events)) { $events = []; }
    $clean = [];
    foreach ($events as $event) {
        if (!is_array($event)) { continue; }
        $clean[] = [
            'id' => (int) ($event['id'] ?? 0),
            'title' => (string) ($event['title'] ?? ''),
            'start_date' => (string) ($event['start_date'] ?? ''),
            'start_time' => substr((string) ($event['start_time'] ?? ''), 0, 5),
            'end_date' => (string) ($event['end_date'] ?? ''),
            'end_time' => substr((string) ($event['end_time'] ?? ''), 0, 5),
            'use_time' => !empty($event['use_time']) ? 1 : 0,
            'seats' => max(1, (int) ($event['seats'] ?? 1)),
            'price' => max(0, (float) ($event['price'] ?? 0)),
        ];
    }
    if (empty($clean)) {
        $clean[] = ['id' => 1, 'title' => '', 'start_date' => '', 'start_time' => '', 'end_date' => '', 'end_time' => '', 'use_time' => 0, 'seats' => 1, 'price' => 0];
    }
    return $clean;
};


$sltr_inventory_units = static function (string $mode) use ($sltr_mode_value): array {
    $json = (string) $sltr_mode_value($mode, 'inventory_units_json', '');
    $units = json_decode($json, true);
    if (!is_array($units)) { $units = []; }
    $clean = [];
    foreach ($units as $unit) {
        if (!is_array($unit)) { continue; }
        $clean[] = [
            'id' => max(1, (int) ($unit['id'] ?? 0)),
            'name' => (string) ($unit['name'] ?? ''),
            'description' => (string) ($unit['description'] ?? ''),
            'capacity' => max(1, (int) ($unit['capacity'] ?? 1)),
            'price' => max(0, (float) ($unit['price'] ?? 0)),
            'hourly_price' => max(0, (float) ($unit['hourly_price'] ?? 0)),
            'active' => array_key_exists('active', $unit) ? (!empty($unit['active']) ? 1 : 0) : 0,
        ];
    }
    if (empty($clean)) {
        $clean[] = ['id' => 1, 'name' => '', 'description' => '', 'capacity' => 1, 'price' => 0, 'hourly_price' => 0, 'active' => 0];
    }
    return $clean;
};

$sltr_date_inventory_overrides = static function (string $mode) use ($sltr_mode_value): array {
    $json = (string) $sltr_mode_value($mode, 'date_inventory_json', '');
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) { $decoded = []; }
    $clean = [];
    if (isset($decoded['periods']) && is_array($decoded['periods'])) {
        $decoded = $decoded['periods'];
    }
    foreach ($decoded as $key => $item) {
        if (!is_array($item)) { continue; }
        $clean[] = [
            'start_date' => (string) ($item['start_date'] ?? (is_string($key) ? $key : '')),
            'end_date' => (string) ($item['end_date'] ?? (string) ($item['start_date'] ?? (is_string($key) ? $key : ''))),
            'capacity' => max(0, (int) ($item['capacity'] ?? 0)),
            'price' => max(0, (float) ($item['price'] ?? 0)),
            'closed' => !empty($item['closed']) ? 1 : 0,
        ];
    }
    if (empty($clean)) {
        $clean[] = ['start_date' => '', 'end_date' => '', 'capacity' => 0, 'price' => 0, 'closed' => 0];
    }
    return $clean;
};

$sltr_extra_services = static function (string $mode) use ($sltr_mode_value): array {
    $json = (string) $sltr_mode_value($mode, 'extra_services_json', '');
    $items = json_decode($json, true);
    if (!is_array($items)) { $items = []; }
    $clean = [];
    foreach ($items as $item) {
        if (!is_array($item)) { continue; }
        $type = (string) ($item['price_type'] ?? 'once');
        if (!in_array($type, ['once', 'per_day', 'per_night', 'per_hour', 'per_guest'], true)) { $type = 'once'; }
        $clean[] = [
            'id' => max(1, (int) ($item['id'] ?? 0)),
            'name' => (string) ($item['name'] ?? ''),
            'description' => (string) ($item['description'] ?? ''),
            'price' => max(0, (float) ($item['price'] ?? 0)),
            'price_type' => $type,
            'active' => array_key_exists('active', $item) ? (!empty($item['active']) ? 1 : 0) : 0,
        ];
    }
    if (empty($clean)) {
        $clean[] = ['id' => 1, 'name' => '', 'description' => '', 'price' => 0, 'price_type' => 'once', 'active' => 0];
    }
    return $clean;
};

$hours_by_day = [];

foreach ((array) $package_hours as $h) {
    $hours_by_day[(int) $h['weekday']] = $h;
}

$days = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    7 => 'Sunday',
];

$sltr_settings_for_preview = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
$sltr_preview_currency = \Slotera\Application\Services\CurrencyService::normalize((string) ($sltr_settings_for_preview['payment_currency'] ?? 'EUR'));
$sltr_preview_currency_position = \Slotera\Application\Services\CurrencyService::normalize_position((string) ($sltr_settings_for_preview['payment_currency_position'] ?? 'right'));
$sltr_admin_format_price = static function ($amount) use ($sltr_preview_currency, $sltr_preview_currency_position): string {
    return \Slotera\Application\Services\CurrencyService::format((float) $amount, $sltr_preview_currency, $sltr_preview_currency_position);
};
$sltr_admin_price_unit_label = static function (string $unit): string {
    if ($unit === 'per_night') { return __('night', 'slotera-booking'); }
    if ($unit === 'per_day') { return __('day', 'slotera-booking'); }
    if ($unit === 'per_hour') { return __('hour', 'slotera-booking'); }
    return '';
};
$sltr_admin_mode_price_preview = static function (string $mode) use ($sltr_mode_value, $sltr_admin_format_price, $sltr_admin_price_unit_label, $package): string {
    if ($mode === 'simple') {
        $price_mode = (string) $sltr_mode_value('simple', 'price_mode', 'fixed');
        if ($price_mode === 'request') { return __('Price on request', 'slotera-booking'); }
        $price = $sltr_admin_format_price((float) $sltr_mode_value('simple', 'price', 0));
        return $price_mode === 'from' ? sprintf(__('From %s', 'slotera-booking'), $price) : $price;
    }
    if ($mode === 'fixed') {
        return $sltr_admin_format_price((float) $sltr_mode_value('fixed', 'price', 0));
    }
    if ($mode === 'flex') {
        return $sltr_admin_format_price((float) $sltr_mode_value('flex', 'price', 0)) . ' / ' . __('hour', 'slotera-booking');
    }
    $flow = (string) $sltr_mode_value('date_range_inventory', 'date_flow', 'customer_choice');
    if ($flow === 'admin_scheduled') {
        $options = (new \Slotera\Application\Services\DateRangeInventoryService())->scheduled_event_options($package ?? []);
        $first = isset($options[0]) && is_array($options[0]) ? $options[0] : null;
        $quote = $first && !empty($first['quote']) && is_array($first['quote']) ? $first['quote'] : [];
        $event_price = $first ? (float) ($quote['total_amount'] ?? $first['price'] ?? 0) : 0.0;
        return $first ? $sltr_admin_format_price($event_price) . ' ' . __('per event', 'slotera-booking') : __('Add event price', 'slotera-booking');
    }
    $unit = (string) $sltr_mode_value('date_range_inventory', 'price_unit', 'per_night');
    $label = $sltr_admin_price_unit_label($unit);
    $price = (float) $sltr_mode_value('date_range_inventory', 'price', 0);
    return $sltr_admin_format_price($price) . ($label ? ' / ' . $label : '');
};
$sltr_admin_mode_warnings = static function (string $mode) use ($sltr_mode_value, $sltr_inventory_units, $sltr_scheduled_events): array {
    $warnings = [];
    if ($mode === 'simple') {
        $price_mode = (string) $sltr_mode_value('simple', 'price_mode', 'fixed');
        if ($price_mode !== 'request' && (float) $sltr_mode_value('simple', 'price', 0) <= 0) {
            $warnings[] = __('Set a price, or switch Price display to Price on request.', 'slotera-booking');
        }
    } elseif ($mode === 'fixed') {
        if ((int) $sltr_mode_value('fixed', 'duration_minutes', 60) <= 0) { $warnings[] = __('Set a fixed slot duration.', 'slotera-booking'); }
        if ((float) $sltr_mode_value('fixed', 'price', 0) <= 0) { $warnings[] = __('Set a slot price.', 'slotera-booking'); }
    } elseif ($mode === 'flex') {
        if ((int) $sltr_mode_value('flex', 'step_minutes', 30) <= 0) { $warnings[] = __('Set a duration step.', 'slotera-booking'); }
        if ((float) $sltr_mode_value('flex', 'price', 0) <= 0) { $warnings[] = __('Set an hourly price.', 'slotera-booking'); }
    } else {
        $flow = (string) $sltr_mode_value('date_range_inventory', 'date_flow', 'customer_choice');
        if ($flow === 'admin_scheduled') {
            $has_ready_event = false;
            foreach ($sltr_scheduled_events('date_range_inventory') as $event) {
                if (!empty($event['start_date']) && !empty($event['end_date']) && (int) $event['seats'] > 0) { $has_ready_event = true; break; }
            }
            if (!$has_ready_event) { $warnings[] = __('Add at least one event with start date, end date and seats.', 'slotera-booking'); }
        } else {
            $has_active_unit = false;
            foreach ($sltr_inventory_units('date_range_inventory') as $unit) {
                if (!empty($unit['active']) && (int) $unit['capacity'] > 0) { $has_active_unit = true; break; }
            }
            if (!$has_active_unit) { $warnings[] = __('Add at least one active room/unit with capacity.', 'slotera-booking'); }
            if ((float) $sltr_mode_value('date_range_inventory', 'price', 0) <= 0) { $warnings[] = __('Set the base date-range price.', 'slotera-booking'); }
        }
    }
    $policy = (string) $sltr_mode_value($mode, 'payment_policy', 'booking_only');
    if ($policy === '' || $policy === '__from_options') {
        // The current request will rebuild this from checkboxes on save. Existing saved packages use a concrete value.
    }
    return $warnings;
};
$sltr_admin_render_preview_panel = static function (string $mode, string $title, string $hint) use ($sltr_admin_mode_price_preview, $sltr_admin_mode_warnings, $package): void {
    $warnings = $sltr_admin_mode_warnings($mode);
    ?>
    <div class="sltr-admin-ux-panel" data-sltr-preview-mode="<?php echo esc_attr($mode); ?>">
        <div class="sltr-admin-ux-hint"><strong><?php echo esc_html($title); ?></strong><br><span><?php echo esc_html($hint); ?></span></div>
        <div class="sltr-admin-ux-preview-card">
            <span class="sltr-admin-preview-kicker"><?php esc_html_e('Frontend card preview', 'slotera-booking'); ?></span>
            <strong class="sltr-admin-preview-title" data-preview-title><?php echo esc_html((string) ($package['title'] ?? __('Package title', 'slotera-booking'))); ?></strong>
            <span class="sltr-admin-preview-price" data-preview-price><?php echo esc_html($sltr_admin_mode_price_preview($mode)); ?></span>
            <button type="button" class="button button-primary" disabled><?php esc_html_e('Book now', 'slotera-booking'); ?></button>
        </div>
        <div class="sltr-admin-ux-warnings" data-preview-warnings>
            <?php if (empty($warnings)) : ?>
                <p class="sltr-admin-ok">✓ <?php esc_html_e('This block has the minimum settings needed to publish.', 'slotera-booking'); ?></p>
            <?php else : ?>
                <p><strong><?php esc_html_e('Before publishing:', 'slotera-booking'); ?></strong></p>
                <ul><?php foreach ($warnings as $warning) : ?><li><?php echo esc_html($warning); ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </div>
    </div>
    <?php
};

?>

<?php

if (!function_exists('sltr_render_pricing_adjustment_fields')) {
    function sltr_render_pricing_adjustment_fields(string $mode, callable $value): void
    {
        $dynamic_enabled = (int) $value($mode, 'dynamic_pricing_enabled', 0);
        $tax_enabled = (int) $value($mode, 'tax_enabled', 0);
        ?>
        <details class="sltr-pricing-adjustments" open>
            <summary><?php esc_html_e('Dynamic pricing & Taxes / VAT', 'slotera-booking'); ?></summary>
            <fieldset style="margin:10px 0;padding:10px;border:1px solid #dcdcde;border-radius:8px;">
                <legend><?php esc_html_e('Dynamic pricing', 'slotera-booking'); ?></legend>
                <input type="hidden" name="mode_config[<?php echo esc_attr($mode); ?>][dynamic_pricing_enabled]" value="0">
                <label><input type="checkbox" name="mode_config[<?php echo esc_attr($mode); ?>][dynamic_pricing_enabled]" value="1" <?php checked($dynamic_enabled, 1); ?>> <?php esc_html_e('Enable dynamic pricing', 'slotera-booking'); ?></label>
                <p class="description"><?php esc_html_e('Shows a customer-facing offer and reduces the package price before coupons and tax.', 'slotera-booking'); ?></p>
                <p><?php echo esc_html(sltr__('admin.weekend_offer_discount')); ?> <input type="number" step="0.01" min="0" max="100" name="mode_config[<?php echo esc_attr($mode); ?>][dynamic_weekend_percent]" value="<?php echo esc_attr((string) $value($mode, 'dynamic_weekend_percent', 0)); ?>" style="width:100px;"> %</p>
                <p><?php esc_html_e('Seasonal offer period', 'slotera-booking'); ?>
                    <input type="date" name="mode_config[<?php echo esc_attr($mode); ?>][dynamic_season_start]" value="<?php echo esc_attr((string) $value($mode, 'dynamic_season_start', '')); ?>">
                    — <input type="date" name="mode_config[<?php echo esc_attr($mode); ?>][dynamic_season_end]" value="<?php echo esc_attr((string) $value($mode, 'dynamic_season_end', '')); ?>">
                    <input type="number" step="0.01" min="0" max="100" name="mode_config[<?php echo esc_attr($mode); ?>][dynamic_season_percent]" value="<?php echo esc_attr((string) $value($mode, 'dynamic_season_percent', 0)); ?>" style="width:100px;"> %
                </p>
            </fieldset>
            <fieldset style="margin:10px 0;padding:10px;border:1px solid #dcdcde;border-radius:8px;">
                <legend><?php esc_html_e('Taxes / VAT', 'slotera-booking'); ?></legend>
                <input type="hidden" name="mode_config[<?php echo esc_attr($mode); ?>][tax_enabled]" value="0">
                <label><input type="checkbox" name="mode_config[<?php echo esc_attr($mode); ?>][tax_enabled]" value="1" <?php checked($tax_enabled, 1); ?>> <?php esc_html_e('Enable tax/VAT calculation', 'slotera-booking'); ?></label>
                <p><?php esc_html_e('Label', 'slotera-booking'); ?> <input type="text" name="mode_config[<?php echo esc_attr($mode); ?>][tax_label]" value="<?php echo esc_attr((string) $value($mode, 'tax_label', 'VAT')); ?>" style="width:100px;">
                    <?php esc_html_e('Rate', 'slotera-booking'); ?> <input type="number" step="0.01" min="0" max="100" name="mode_config[<?php echo esc_attr($mode); ?>][tax_rate]" value="<?php echo esc_attr((string) $value($mode, 'tax_rate', 0)); ?>" style="width:100px;"> %
                    <select name="mode_config[<?php echo esc_attr($mode); ?>][tax_mode]"><option value="exclusive" <?php selected((string) $value($mode, 'tax_mode', 'exclusive'), 'exclusive'); ?>><?php esc_html_e('Added on top', 'slotera-booking'); ?></option><option value="inclusive" <?php selected((string) $value($mode, 'tax_mode', ''), 'inclusive'); ?>><?php esc_html_e('Included in price', 'slotera-booking'); ?></option></select>
                </p>
            </fieldset>
        </details>
        <?php
    }
}

if (!function_exists('sltr_render_payment_policy_fields')) {
    function sltr_render_payment_policy_fields(string $mode, callable $value): void
    {
        $policy = (string) $value($mode, 'payment_policy', 'booking_only');
        $deposit_type = (string) $value($mode, 'deposit_type', 'percent');
        $deposit_value = (string) $value($mode, 'deposit_value', 30);
        $unpaid_status = (string) $value($mode, 'unpaid_booking_status', 'confirmed');
        $hide_payment_methods = (int) $value($mode, 'hide_payment_methods', 0) === 1;
        $hide_price_on_frontend = (int) $value($mode, 'hide_price_on_frontend', 0) === 1;
        ?>
        <?php
        $enabled_options = ['booking_only'];
        if ($policy === 'full_payment') { $enabled_options = ['full_payment']; }
        elseif ($policy === 'deposit_payment') { $enabled_options = ['deposit_payment']; }
        elseif ($policy === 'full_or_deposit') { $enabled_options = ['full_payment', 'deposit_payment']; }
        elseif ($policy === 'booking_or_full') { $enabled_options = ['booking_only', 'full_payment']; }
        elseif ($policy === 'booking_or_deposit') { $enabled_options = ['booking_only', 'deposit_payment']; }
        elseif ($policy === 'all_options') { $enabled_options = ['booking_only', 'full_payment', 'deposit_payment']; }
        if ($hide_payment_methods) { $enabled_options = []; }
        ?>
        <fieldset class="sltr-payment-policy-options">
            <legend><?php esc_html_e('Checkout options', 'slotera-booking'); ?></legend>
            <input type="hidden" name="mode_config[<?php echo esc_attr($mode); ?>][payment_policy]" value="__from_options">
            <label style="display:block;margin:4px 0;"><input type="checkbox" class="sltr-payment-option-toggle" name="mode_config[<?php echo esc_attr($mode); ?>][payment_options][]" value="booking_only" <?php checked(in_array('booking_only', $enabled_options, true)); ?>> <?php esc_html_e('Pay on arrival / booking only', 'slotera-booking'); ?></label>
            <label style="display:block;margin:4px 0;"><input type="checkbox" class="sltr-payment-option-toggle" name="mode_config[<?php echo esc_attr($mode); ?>][payment_options][]" value="full_payment" <?php checked(in_array('full_payment', $enabled_options, true)); ?>> <?php esc_html_e('Full payment', 'slotera-booking'); ?></label>
            <label style="display:block;margin:4px 0;"><input type="checkbox" class="sltr-payment-option-toggle" name="mode_config[<?php echo esc_attr($mode); ?>][payment_options][]" value="deposit_payment" <?php checked(in_array('deposit_payment', $enabled_options, true)); ?>> <?php esc_html_e('Prepayment / deposit', 'slotera-booking'); ?></label>
            <input type="hidden" name="mode_config[<?php echo esc_attr($mode); ?>][hide_payment_methods]" value="0">
            <label style="display:block;margin:8px 0 4px;"><input type="checkbox" class="sltr-hide-payment-methods" name="mode_config[<?php echo esc_attr($mode); ?>][hide_payment_methods]" value="1" <?php checked($hide_payment_methods); ?>> <?php esc_html_e('Do not display payment methods', 'slotera-booking'); ?></label>
            <p class="description"><?php esc_html_e('Hide checkout and payment method choices on the booking form. The booking is created without online payment.', 'slotera-booking'); ?></p>
            <p class="description"><?php esc_html_e('You can enable multiple checkout options. The customer chooses one option during booking.', 'slotera-booking'); ?></p>
            <input type="hidden" name="mode_config[<?php echo esc_attr($mode); ?>][hide_price_on_frontend]" value="0">
            <label style="display:block;margin:12px 0 4px;"><input type="checkbox" name="mode_config[<?php echo esc_attr($mode); ?>][hide_price_on_frontend]" value="1" <?php checked($hide_price_on_frontend); ?>> <?php esc_html_e('Do not display price on frontend', 'slotera-booking'); ?></label>
            <p class="description"><?php esc_html_e('Hides the package price in frontend package cards, package details and the booking form. Price calculations and saved booking amounts are unchanged.', 'slotera-booking'); ?></p>
        </fieldset>
        <p><?php esc_html_e('Pay on arrival booking status', 'slotera-booking'); ?>
            <select name="mode_config[<?php echo esc_attr($mode); ?>][unpaid_booking_status]">
                <option value="confirmed" <?php selected($unpaid_status, 'confirmed'); ?>><?php esc_html_e('Confirm immediately', 'slotera-booking'); ?></option>
            </select>
            <span class="description"><?php esc_html_e('Booking status stays confirmed; unpaid/pending payment status tracks whether money still needs to be collected.', 'slotera-booking'); ?></span>
        </p>
        <p><?php esc_html_e('Deposit', 'slotera-booking'); ?>
            <select name="mode_config[<?php echo esc_attr($mode); ?>][deposit_type]"><option value="percent" <?php selected($deposit_type, 'percent'); ?>>%</option><option value="fixed" <?php selected($deposit_type, 'fixed'); ?>><?php esc_html_e('Fixed', 'slotera-booking'); ?></option></select>
            <input type="number" step="0.01" min="0" name="mode_config[<?php echo esc_attr($mode); ?>][deposit_value]" value="<?php echo esc_attr($deposit_value); ?>">
        </p>
        <?php
    }
}
