<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Core\Database;
use Slotera\Core\HtmlSanitizer;

if (!defined('ABSPATH')) {
    exit;
}

final class PackageRepository
{
    public function get_by_id(int $id): ?array
    {
        global $wpdb;
        $table = Database::packages_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id), ARRAY_A);
        return $row ?: null;
    }

    public function get_all(): array
    {
        global $wpdb;
        $table = Database::packages_table();
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY is_popular DESC, created_at DESC", ARRAY_A) ?: [];
    }

    public function get_active(int $limit = 100, int $offset = 0): array
    {
        global $wpdb;
        $table = Database::packages_table();
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY is_popular DESC, title ASC LIMIT %d OFFSET %d", $limit, $offset),
            ARRAY_A
        ) ?: [];
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = Database::packages_table();
        $now = current_time('mysql');
        $normalized = $this->normalize($data);
        $requested_slug = sanitize_title((string) ($data['slug'] ?? ''));
        $slug = $requested_slug !== '' ? $requested_slug : sanitize_title((string) $normalized['title']);
        if ($slug === '') { $slug = 'package'; }
        if ($this->slug_exists($slug)) { return 0; }

        $inserted = $wpdb->insert(
            $table,
            array_merge($normalized, [
                'slug' => $slug,
                'created_at' => $now,
                'updated_at' => $now,
            ])
        );

        $id = $inserted ? (int) $wpdb->insert_id : 0;
        if ($id > 0) {
            do_action('sltr_data_changed', 'package_created', ['package_id' => $id]);
        }
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $table = Database::packages_table();
        $current = $this->get_by_id($id);

        if (!$current) {
            return false;
        }
        $normalized = $this->normalize(array_merge($current, $data));
        $normalized['updated_at'] = current_time('mysql');

        $old_slug = sanitize_title((string) ($current['slug'] ?? ''));
        $normalized['slug'] = $old_slug;
        if ($old_slug === '') {
            $requested_slug = sanitize_title((string) ($data['slug'] ?? ''));
            if ($requested_slug === '' || $this->slug_exists($requested_slug, $id)) { return false; }
            $normalized['slug'] = $requested_slug;
        }
        $new_slug = sanitize_title((string) ($normalized['slug'] ?? $old_slug));

        $query_result = $wpdb->update($table, $normalized, ['id' => $id]);
        $updated = $query_result !== false;
        if ($updated) {
            do_action('sltr_data_changed', 'package_updated', [
                'package_id' => $id,
                'old_slug' => $old_slug,
                'new_slug' => $new_slug,
                'page_id' => (int) ($current['page_id'] ?? 0),
            ]);
        }
        return $updated;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        $table = Database::packages_table();
        $hours_table = Database::working_hours_table();
        $package_locations_table = Database::package_locations_table();

        $wpdb->delete($hours_table, ['scope_type' => 'package', 'scope_id' => $id], ['%s', '%d']);
        $wpdb->delete($package_locations_table, ['package_id' => $id], ['%d']);
        $current = $this->get_by_id($id);
        $result = $wpdb->delete($table, ['id' => $id], ['%d']);

        $deleted = $result !== false;
        if ($deleted) {
            do_action('sltr_data_changed', 'package_deleted', ['package_id' => $id, 'package' => is_array($current) ? $current : []]);
        }
        return $deleted;
    }

    public function get_active_by_category(int $category_id, int $limit = 100, int $offset = 0): array
    {
        global $wpdb;
        $table = Database::packages_table();
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE is_active = 1 AND category_id = %d ORDER BY is_popular DESC, title ASC LIMIT %d OFFSET %d", $category_id, $limit, $offset),
            ARRAY_A
        ) ?: [];
    }

    private function column_exists(string $table, string $column): bool
    {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
        return is_string($found) && $found === $column;
    }

    private function normalize(array $data): array
    {
        $title = sanitize_text_field((string) ($data['title'] ?? ''));
        $duration = max(1, (int) ($data['duration_minutes'] ?? 60));
        $mode = sanitize_key((string) ($data['booking_mode'] ?? 'fixed'));

        if ($mode === 'flexible') {
            $mode = 'flex';
        }

        if (!in_array($mode, ['simple', 'fixed', 'flex', 'date_range_inventory'], true)) {
            $mode = 'simple';
        }

        $slot_step = max(1, (int) ($data['slot_step'] ?? $duration));

        if ($mode === 'fixed' || $mode === 'date_range_inventory' || $slot_step > $duration) {
            $slot_step = $duration;
        }

        $discount_type = sanitize_key((string) ($data['discount_type'] ?? 'none'));

        if (!in_array($discount_type, ['none', 'percent', 'fixed'], true)) {
            $discount_type = 'none';
        }

        $hours_mode = sanitize_key((string) ($data['hours_mode'] ?? 'global'));

        if ($hours_mode === 'package') {
            $hours_mode = 'custom';
        }

        if (!in_array($hours_mode, ['global', 'custom'], true)) {
            $hours_mode = 'global';
        }


        return [
            'category_id' => max(0, (int) ($data['category_id'] ?? 0)),
            'title' => $title,
            'description' => HtmlSanitizer::public_content((string) ($data['description'] ?? '')),
            'title_font_family' => $this->sanitize_font_family((string) ($data['title_font_family'] ?? '')),
            'title_font_size' => max(0, min(48, (int) ($data['title_font_size'] ?? 24))),
            'seo_title' => sanitize_text_field((string) ($data['seo_title'] ?? '')),
            'seo_site_title_position' => $this->sanitize_choice((string) ($data['seo_site_title_position'] ?? 'right'), ['left', 'right'], 'right'),
            'seo_description' => sanitize_textarea_field((string) ($data['seo_description'] ?? '')),
            'seo_og_title' => sanitize_text_field((string) ($data['seo_og_title'] ?? '')),
            'seo_og_description' => sanitize_textarea_field((string) ($data['seo_og_description'] ?? '')),
            'seo_og_image' => esc_url_raw((string) ($data['seo_og_image'] ?? '')),
            'seo_canonical' => esc_url_raw((string) ($data['seo_canonical'] ?? '')),
            'seo_redirect_301' => esc_url_raw((string) ($data['seo_redirect_301'] ?? '')),
            'seo_robots' => $this->sanitize_choice((string) ($data['seo_robots'] ?? 'index,follow'), ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'], 'index,follow'),
            'seo_i18n_json' => $this->sanitize_seo_i18n_json((string) ($data['seo_i18n_json'] ?? '')),
            'solo_top_content' => HtmlSanitizer::public_content((string) ($data['solo_top_content'] ?? ''), HtmlSanitizer::allow_package_shortcodes()),
            'show_solo_top_content' => !empty($data['show_solo_top_content']) ? 1 : 0,
            'solo_content' => HtmlSanitizer::public_content((string) ($data['solo_content'] ?? ''), HtmlSanitizer::allow_package_shortcodes()),
            'solo_down_content' => HtmlSanitizer::public_content((string) ($data['solo_down_content'] ?? ''), HtmlSanitizer::allow_package_shortcodes()),
            'show_solo_down_content' => !empty($data['show_solo_down_content']) ? 1 : 0,
            'show_more_info' => !empty($data['show_more_info']) ? 1 : 0,
            'solo_page_enabled' => !empty($data['solo_page_enabled']) ? 1 : 0,
            'card_image_id' => max(0, (int) ($data['card_image_id'] ?? 0)),
            'booking_card_image_id' => max(0, (int) ($data['booking_card_image_id'] ?? 0)),
            'card_image_focus' => $this->sanitize_focus((string) ($data['card_image_focus'] ?? '50,50')),
            'booking_card_image_focus' => $this->sanitize_focus((string) ($data['booking_card_image_focus'] ?? '50,50')),
            'popular_icon' => $this->sanitize_choice((string) ($data['popular_icon'] ?? ''), ['', 'star', 'fire', 'crown', 'heart', 'bolt'], ''),
            'popular_icon_color' => sanitize_hex_color((string) ($data['popular_icon_color'] ?? '#7c3aed')) ?: '#7c3aed',
            'popular_icon_size' => max(16, min(48, (int) ($data['popular_icon_size'] ?? 24))),
            'solo_media_json' => $this->sanitize_solo_media_json((string) ($data['solo_media_json'] ?? '{}')),
            'solo_contact_image_id' => max(0, (int) ($data['solo_contact_image_id'] ?? 0)),
            'solo_contact_map' => $this->sanitize_google_maps_link((string) ($data['solo_contact_map'] ?? '')),
            'solo_contact_details_json' => $this->sanitize_solo_contact_details_json((string) ($data['solo_contact_details_json'] ?? '[]')),
            'slider_image_ids' => $this->sanitize_attachment_ids((string) ($data['slider_image_ids'] ?? '')),
            'slider_image_focus_json' => $this->sanitize_focus_json((string) ($data['slider_image_focus_json'] ?? '{}')),
            'slider_speed' => max(1000, min(30000, (int) ($data['slider_speed'] ?? 4000))),
            'media_fit_mode' => $this->sanitize_choice((string) ($data['media_fit_mode'] ?? 'cover'), ['cover', 'contain'], 'cover'),
            'slider_image_position' => 'top',
            'gallery_image_ids' => $this->sanitize_attachment_ids((string) ($data['gallery_image_ids'] ?? ''), 1),
            'gallery_image_focus' => $this->sanitize_focus((string) ($data['gallery_image_focus'] ?? '50,50')),
            'gallery_layout' => 'grid',
            'solo_layout' => in_array((string) ($data['solo_layout'] ?? 'classic'), ['classic', 'stacked'], true) ? (string) ($data['solo_layout'] ?? 'classic') : 'classic',
            'right_block_title' => sanitize_text_field((string) ($data['right_block_title'] ?? '')),
            'right_block_text' => HtmlSanitizer::public_content((string) ($data['right_block_text'] ?? '')),
            'right_block_title_font_family' => $this->sanitize_font_family((string) ($data['right_block_title_font_family'] ?? ($data['right_block_font_family'] ?? ''))),
            'right_block_title_font_size' => max(12, min(48, (int) ($data['right_block_title_font_size'] ?? 32))),
            'right_block_text_font_family' => $this->sanitize_font_family((string) ($data['right_block_text_font_family'] ?? ($data['right_block_font_family'] ?? 'Inter, Arial, sans-serif'))),
            'right_block_text_font_size' => max(12, min(48, (int) ($data['right_block_text_font_size'] ?? ($data['right_block_font_size'] ?? 24)))),
            'right_block_font_family' => $this->sanitize_font_family((string) ($data['right_block_font_family'] ?? '')),
            'right_block_font_size' => max(12, min(48, (int) ($data['right_block_font_size'] ?? 18))),
            'info_tooltip' => sanitize_textarea_field((string) ($data['info_tooltip'] ?? '')),
            'tooltip_size_ratio' => max(0.8, min(2.0, (float) ($data['tooltip_size_ratio'] ?? 1.15))),
            'tooltip_text_size' => max(10, min(24, (int) ($data['tooltip_text_size'] ?? 13))),
            'description_font_family' => $this->sanitize_font_family((string) ($data['description_font_family'] ?? '')),
            'description_font_size' => max(12, min(48, (int) ($data['description_font_size'] ?? 18))),
            'duration_minutes' => $duration,
            'show_duration_frontend' => !empty($data['show_duration_frontend']) ? 1 : 0,
            'price' => (float) ($data['price'] ?? 0),
            'discount_type' => $discount_type,
            'discount_value' => max(0, (float) ($data['discount_value'] ?? 0)),
            'campaign_note' => sanitize_text_field((string) ($data['campaign_note'] ?? '')),
            'dynamic_pricing_enabled' => !empty($data['dynamic_pricing_enabled']) ? 1 : 0,
            'dynamic_weekend_percent' => max(0, min(100, abs((float) ($data['dynamic_weekend_percent'] ?? 0)))),
            'dynamic_season_start' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($data['dynamic_season_start'] ?? '')) ? sanitize_text_field((string) $data['dynamic_season_start']) : null,
            'dynamic_season_end' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($data['dynamic_season_end'] ?? '')) ? sanitize_text_field((string) $data['dynamic_season_end']) : null,
            'dynamic_season_percent' => max(0, min(100, abs((float) ($data['dynamic_season_percent'] ?? 0)))),
            'tax_enabled' => !empty($data['tax_enabled']) ? 1 : 0,
            'tax_label' => sanitize_text_field((string) ($data['tax_label'] ?? 'VAT')),
            'tax_rate' => max(0, min(100, (float) ($data['tax_rate'] ?? 0))),
            'tax_mode' => $this->sanitize_choice((string) ($data['tax_mode'] ?? 'exclusive'), ['exclusive', 'inclusive'], 'exclusive'),
            'buffer_before' => max(0, (int) ($data['buffer_before'] ?? 0)),
            'buffer_after' => max(0, (int) ($data['buffer_after'] ?? 0)),
            'slot_step' => $slot_step,
            'lead_time_minutes' => max(0, (int) ($data['lead_time_minutes'] ?? 0)),
            'price_unit' => $mode === 'simple' ? $this->sanitize_choice((string) ($data['price_unit'] ?? 'fixed'), ['fixed', 'from', 'request'], 'fixed') : $this->sanitize_choice((string) ($data['price_unit'] ?? 'fixed'), ['fixed', 'per_day', 'per_night', 'per_hour'], 'fixed'),
            'hourly_price' => max(0, (float) ($data['hourly_price'] ?? 0)),
            'checkin_time' => $this->normalize_time((string) ($data['checkin_time'] ?? '15:00')),
            'checkout_time' => $this->normalize_time((string) ($data['checkout_time'] ?? '11:00')),
            'min_nights' => max(1, (int) ($data['min_nights'] ?? 1)),
            'max_nights' => max(1, (int) ($data['max_nights'] ?? 30)),
            'inventory_units_json' => $this->sanitize_inventory_units((string) ($data['inventory_units_json'] ?? '')),
            'date_inventory_json' => $this->sanitize_text_json((string) ($data['date_inventory_json'] ?? '')),
            'date_flow' => $this->sanitize_choice((string) ($data['date_flow'] ?? 'customer_choice'), ['customer_choice', 'admin_scheduled'], 'customer_choice'),
            'scheduled_events_json' => $this->sanitize_scheduled_events_json((string) ($data['scheduled_events_json'] ?? '')),
            'included_services' => sanitize_textarea_field((string) ($data['included_services'] ?? '')),
            'extra_services_json' => $this->sanitize_extra_services((string) ($data['extra_services_json'] ?? '')),
            'payment_policy' => $this->sanitize_choice((string) ($data['payment_policy'] ?? ($data['checkout_mode'] ?? 'booking_only')), ['booking_only', 'full_payment', 'deposit_payment', 'full_or_deposit', 'booking_or_full', 'booking_or_deposit', 'all_options'], 'booking_only'),
            'deposit_type' => $this->sanitize_choice((string) ($data['deposit_type'] ?? 'percent'), ['percent', 'fixed'], 'percent'),
            'deposit_value' => max(0, (float) ($data['deposit_value'] ?? 30)),
            'booking_mode' => $mode,
            'mode_configs_json' => $this->sanitize_mode_configs_json((string) ($data['mode_configs_json'] ?? '')),
            'checkout_mode' => sanitize_key((string) ($data['checkout_mode'] ?? 'booking_only')),
            'hours_mode' => $hours_mode,
            'open_247' => !empty($data['open_247']) ? 1 : 0,
            'max_bookings_per_slot' => max(1, (int) ($data['max_bookings_per_slot'] ?? 1)),
            'low_availability_notice_enabled' => !empty($data['low_availability_notice_enabled']) ? 1 : 0,
            'low_availability_threshold' => max(1, min(99, (int) ($data['low_availability_threshold'] ?? 5))),
            'is_popular' => !empty($data['is_popular']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];
    }

    private function sanitize_seo_i18n_json(string $json): string
    {
        $decoded = json_decode(wp_unslash($json), true);
        if (!is_array($decoded)) {
            return '';
        }
        $clean = [];
        foreach ($decoded as $locale => $fields) {
            $locale = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $locale);
            if ($locale === '' || !is_array($fields)) {
                continue;
            }
            $entry = [
                'seo_title' => sanitize_text_field((string) ($fields['seo_title'] ?? '')),
                'seo_description' => sanitize_textarea_field((string) ($fields['seo_description'] ?? '')),
                'seo_og_title' => sanitize_text_field((string) ($fields['seo_og_title'] ?? '')),
                'seo_og_description' => sanitize_textarea_field((string) ($fields['seo_og_description'] ?? '')),
                'seo_og_image' => esc_url_raw((string) ($fields['seo_og_image'] ?? '')),
                'seo_canonical' => esc_url_raw((string) ($fields['seo_canonical'] ?? '')),
            ];
            $entry = array_filter($entry, static fn($value) => $value !== '');
            if (!empty($entry)) {
                $clean[$locale] = $entry;
            }
        }
        return empty($clean) ? '' : (string) wp_json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }


    private function bool_from_source(array $source, string $key): bool
    {
        if (!array_key_exists($key, $source)) {
            return false;
        }
        $value = $source[$key];
        if (is_bool($value)) {
            return $value;
        }
        if (is_array($value)) {
            $value = end($value);
        }
        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function sanitize_mode_configs_json(string $json): string
    {
        $decoded = json_decode(trim($json), true);
        if (!is_array($decoded)) { return ''; }
        $allowed_modes = ['simple', 'fixed', 'flex', 'date_range_inventory'];
        $clean = [];
        foreach ($allowed_modes as $mode) {
            if (!isset($decoded[$mode]) || !is_array($decoded[$mode])) { continue; }
            $src = $decoded[$mode];
            $discount_type = $this->sanitize_choice((string) ($src['discount_type'] ?? 'none'), ['none', 'percent', 'fixed'], 'none');
            $payment_policy = $this->sanitize_choice((string) ($src['payment_policy'] ?? 'booking_only'), ['booking_only', 'full_payment', 'deposit_payment', 'full_or_deposit', 'booking_or_full', 'booking_or_deposit', 'all_options'], 'booking_only');
            $deposit_type = $this->sanitize_choice((string) ($src['deposit_type'] ?? 'percent'), ['percent', 'fixed'], 'percent');
            $clean[$mode] = [
                'duration_minutes' => max(1, min(1440, (int) ($src['duration_minutes'] ?? 60))),
                'full_day_booking' => $mode === 'fixed' && $this->bool_from_source($src, 'full_day_booking') ? 1 : 0,
                'slot_step' => max(1, min(1440, (int) ($src['slot_step'] ?? ($src['duration_minutes'] ?? 60)))),
                'max_bookings_per_slot' => max(1, (int) ($src['max_bookings_per_slot'] ?? 1)),
                'show_duration' => $this->bool_from_source($src, 'show_duration') ? 1 : 0,
                'price' => max(0, (float) ($src['price'] ?? 0)),
                'discount_type' => $discount_type,
                'discount_value' => max(0, (float) ($src['discount_value'] ?? 0)),
                'campaign_note' => sanitize_text_field((string) ($src['campaign_note'] ?? '')),
                'booking_button_text' => sanitize_text_field((string) ($src['booking_button_text'] ?? '')),
                'dynamic_pricing_enabled' => !empty($src['dynamic_pricing_enabled']) ? 1 : 0,
                'dynamic_weekend_percent' => max(-100, min(500, (float) ($src['dynamic_weekend_percent'] ?? 0))),
                'dynamic_season_start' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($src['dynamic_season_start'] ?? '')) ? sanitize_text_field((string) $src['dynamic_season_start']) : '',
                'dynamic_season_end' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($src['dynamic_season_end'] ?? '')) ? sanitize_text_field((string) $src['dynamic_season_end']) : '',
                'dynamic_season_percent' => max(-100, min(500, (float) ($src['dynamic_season_percent'] ?? 0))),
                'tax_enabled' => !empty($src['tax_enabled']) ? 1 : 0,
                'tax_label' => sanitize_text_field((string) ($src['tax_label'] ?? 'VAT')),
                'tax_rate' => max(0, min(100, (float) ($src['tax_rate'] ?? 0))),
                'tax_mode' => $this->sanitize_choice((string) ($src['tax_mode'] ?? 'exclusive'), ['exclusive', 'inclusive'], 'exclusive'),
                'payment_policy' => !empty($src['hide_payment_methods']) ? 'booking_only' : $payment_policy,
                'hide_payment_methods' => $this->bool_from_source($src, 'hide_payment_methods') ? 1 : 0,
                'hide_price_on_frontend' => $this->bool_from_source($src, 'hide_price_on_frontend') ? 1 : 0,
                'deposit_type' => $deposit_type,
                'deposit_value' => max(0, (float) ($src['deposit_value'] ?? 30)),
                'low_availability_notice_enabled' => $this->bool_from_source($src, array_key_exists('low_availability_notice_enabled_checked', $src) ? 'low_availability_notice_enabled_checked' : 'low_availability_notice_enabled') ? 1 : 0,
                'low_availability_threshold' => max(1, min(99, (int) ($src['low_availability_threshold'] ?? 5))),
            ];
            if ($mode === 'flex') {
                $clean[$mode]['display_start_time_only'] = $this->bool_from_source($src, 'display_start_time_only') ? 1 : 0;
            }
            if ($mode === 'simple') {
                $clean[$mode]['price_mode'] = $this->sanitize_choice((string) ($src['price_mode'] ?? 'fixed'), ['fixed', 'from', 'request'], 'fixed');
                $clean[$mode]['capacity_type'] = $this->sanitize_choice((string) ($src['capacity_type'] ?? 'unlimited'), ['unlimited', 'limited'], 'unlimited');
                $clean[$mode]['capacity_total'] = max(1, (int) ($src['capacity_total'] ?? 1));
                $clean[$mode]['confirm_immediately'] = $this->bool_from_source($src, 'confirm_immediately') ? 1 : 0;
                $clean[$mode]['included_services'] = sanitize_textarea_field((string) ($src['included_services'] ?? ''));
                $clean[$mode]['extra_services_json'] = $this->sanitize_extra_services((string) ($src['extra_services_json'] ?? ''));
            }
            if ($mode === 'date_range_inventory') {
                $clean[$mode]['price_unit'] = $this->sanitize_choice((string) ($src['price_unit'] ?? 'fixed'), ['fixed', 'per_day', 'per_night', 'per_hour'], 'fixed');
                $clean[$mode]['hourly_price'] = max(0, (float) ($src['hourly_price'] ?? 0));
                $clean[$mode]['checkin_time'] = $this->normalize_time((string) ($src['checkin_time'] ?? '15:00'));
                $clean[$mode]['checkout_time'] = $this->normalize_time((string) ($src['checkout_time'] ?? '11:00'));
                $clean[$mode]['min_nights'] = max(1, (int) ($src['min_nights'] ?? 1));
                $clean[$mode]['max_nights'] = max(1, (int) ($src['max_nights'] ?? 30));
                $clean[$mode]['inventory_units_json'] = $this->sanitize_inventory_units((string) ($src['inventory_units_json'] ?? ''));
                $clean[$mode]['date_inventory_json'] = $this->sanitize_text_json((string) ($src['date_inventory_json'] ?? ''));
                $clean[$mode]['date_flow'] = $this->sanitize_choice((string) ($src['date_flow'] ?? 'customer_choice'), ['customer_choice', 'admin_scheduled'], 'customer_choice');
                $clean[$mode]['scheduled_events_json'] = $this->sanitize_scheduled_events_json((string) ($src['scheduled_events_json'] ?? ''));
                $clean[$mode]['included_services'] = sanitize_textarea_field((string) ($src['included_services'] ?? ''));
                $clean[$mode]['extra_services_json'] = $this->sanitize_extra_services((string) ($src['extra_services_json'] ?? ''));
            }
        }
        return wp_json_encode($clean);
    }


    private function sanitize_scheduled_events_json(string $json): string
    {
        $decoded = json_decode(trim($json), true);
        if (!is_array($decoded)) { return ''; }
        $clean = [];
        foreach ($decoded as $event) {
            if (!is_array($event)) { continue; }
            $start_date = sanitize_text_field((string) ($event['start_date'] ?? ''));
            $end_date = sanitize_text_field((string) ($event['end_date'] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) !== 1 || preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) !== 1 || $end_date < $start_date) { continue; }
            $use_time = !empty($event['use_time']) ? 1 : 0;
            $clean[] = [
                'id' => max(1, (int) ($event['id'] ?? (count($clean) + 1))),
                'title' => sanitize_text_field((string) ($event['title'] ?? '')),
                'start_date' => $start_date,
                'start_time' => $use_time ? $this->normalize_time((string) ($event['start_time'] ?? '00:00')) : '',
                'end_date' => $end_date,
                'end_time' => $use_time ? $this->normalize_time((string) ($event['end_time'] ?? '23:59')) : '',
                'use_time' => $use_time,
                'seats' => max(1, (int) ($event['seats'] ?? 1)),
                'price' => max(0, (float) ($event['price'] ?? 0)),
            ];
        }
        return wp_json_encode($clean);
    }

    private function sanitize_choice(string $value, array $allowed, string $fallback): string
    {
        $value = sanitize_key($value);
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function normalize_time(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^\d{1,2}:\d{2}$/', $time) === 1) { $time .= ':00'; }
        if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d:[0-5]\d$/', $time) !== 1) { return '00:00:00'; }
        [$h, $m, $s] = array_map('intval', explode(':', $time));
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    private function sanitize_text_json(string $json): string
    {
        $json = trim(wp_unslash($json));
        if ($json === '') { return ''; }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? wp_json_encode($decoded) : '';
    }

    private function sanitize_inventory_units(string $json): string
    {
        $decoded = json_decode(trim($json), true);
        if (!is_array($decoded)) { return ''; }
        $units = [];
        foreach ($decoded as $i => $unit) {
            if (!is_array($unit)) { continue; }
            $id = max(1, (int) ($unit['id'] ?? ($i + 1)));
            $active = array_key_exists('active', $unit) ? ($this->bool_from_source($unit, 'active') ? 1 : 0) : 1;
            $name = sanitize_text_field((string) ($unit['name'] ?? ''));
            if ($name === '' && $active === 0) { $name = sprintf('Unit %d', $id); }
            if ($name === '') { continue; }
            $units[] = [
                'id' => $id,
                'name' => $name,
                'description' => sanitize_text_field((string) ($unit['description'] ?? '')),
                'capacity' => max(1, (int) ($unit['capacity'] ?? 1)),
                'price' => max(0, (float) ($unit['price'] ?? 0)),
                'hourly_price' => max(0, (float) ($unit['hourly_price'] ?? 0)),
                'active' => $active,
            ];
        }
        return wp_json_encode(array_values($units));
    }

    private function sanitize_extra_services(string $json): string
    {
        $decoded = json_decode(trim($json), true);
        if (!is_array($decoded)) { return ''; }
        $items = [];
        foreach ($decoded as $i => $item) {
            if (!is_array($item)) { continue; }
            $id = max(1, (int) ($item['id'] ?? ($i + 1)));
            $active = array_key_exists('active', $item) ? ($this->bool_from_source($item, 'active') ? 1 : 0) : 1;
            $name = sanitize_text_field((string) ($item['name'] ?? ''));
            if ($name === '' && $active === 0) { $name = sprintf('Extra service %d', $id); }
            if ($name === '') { continue; }
            $type = $this->sanitize_choice((string) ($item['price_type'] ?? 'once'), ['once', 'per_day', 'per_night', 'per_hour', 'per_guest'], 'once');
            $items[] = ['id' => $id, 'name' => $name, 'description' => sanitize_text_field((string) ($item['description'] ?? '')), 'price' => max(0, (float) ($item['price'] ?? 0)), 'price_type' => $type, 'active' => $active];
        }
        return wp_json_encode(array_values($items));
    }

    private function sanitize_google_maps_link(string $value): string
    {
        $url = esc_url_raw(trim($value), ['https']);
        if ($url === '') {
            return '';
        }

        $parts = wp_parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        $is_google_maps_host =
            preg_match('/(^|\.)google\.(?:com|[a-z]{2}|co\.[a-z]{2})$/i', $host) === 1
            || preg_match('/(^|\.)maps\.google\.(?:com|[a-z]{2}|co\.[a-z]{2})$/i', $host) === 1
            || in_array($host, ['maps.app.goo.gl', 'goo.gl'], true);

        $is_maps_path = strpos($host, 'maps.app.goo.gl') !== false
            || strpos($path, '/maps') === 0
            || strpos($path, '/maps/') !== false
            || strpos($path, '/maps?') !== false;

        return ($is_google_maps_host && $is_maps_path) ? $url : '';
    }

    private function sanitize_solo_contact_details_json(string $json): string
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) { return '[]'; }
        $clean = [];
        $social_platforms = ['instagram', 'facebook', 'linkedin', 'x', 'youtube', 'tiktok'];

        foreach ($decoded as $row) {
            if (!is_array($row)) { continue; }
            $type = sanitize_key((string) ($row['type'] ?? 'contact'));

            if ($type === 'address') {
                $value = sanitize_text_field((string) ($row['value'] ?? ''));
                if ($value === '') { continue; }
                $clean[] = ['type' => 'address', 'value' => $value];
            } elseif ($type === 'social') {
                $platform = sanitize_key((string) ($row['platform'] ?? ''));
                $url = esc_url_raw((string) ($row['url'] ?? ''), ['http', 'https']);
                if (!in_array($platform, $social_platforms, true) || $url === '') { continue; }
                $clean[] = ['type' => 'social', 'platform' => $platform, 'url' => $url];
            } else {
                $label = sanitize_text_field((string) ($row['label'] ?? ''));
                $value = sanitize_text_field((string) ($row['value'] ?? ''));
                if ($label === '' && $value === '') { continue; }
                $clean[] = ['type' => 'contact', 'label' => $label, 'value' => $value];
            }

            if (count($clean) >= 20) { break; }
        }
        return wp_json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    private function sanitize_solo_media_json(string $json): string
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) { return '{}'; }
        $clean = [];
        foreach ($decoded as $key => $item) {
            $id = sanitize_key((string) $key);
            if ($id === '' || !is_array($item)) { continue; }
            $type = (string) ($item['type'] ?? 'images');
            if (!in_array($type, ['images', 'video'], true)) { $type = 'images'; }
            $image_ids = $this->sanitize_attachment_ids((string) ($item['ids'] ?? ''), 20);
            $video_id = absint((string) ($item['video_id'] ?? 0));
            if ($type === 'video') {
                $image_ids = '';
                $video_mime = $video_id > 0 ? strtolower((string) get_post_mime_type($video_id)) : '';
                if (!in_array($video_mime, ['video/mp4', 'video/webm', 'video/ogg'], true)) {
                    $video_id = 0;
                }
            } else {
                $video_id = 0;
            }
            $clean[$id] = [
                'type' => $type,
                'ids' => $image_ids,
                'focus' => $this->sanitize_focus_json((string) ($item['focus'] ?? '{}')),
                'speed' => max(1000, min(30000, (int) ($item['speed'] ?? 4000))),
                'video_id' => $video_id,
                'autoplay' => $type === 'video' && $video_id > 0 && !empty($item['autoplay']) ? 1 : 0,
            ];
        }
        return wp_json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function sanitize_attachment_ids(string $ids, int $max = 20): string
    {
        $values = array_filter(array_map('absint', preg_split('/[\s,]+/', $ids) ?: []));
        $values = array_values(array_unique($values));
        if ($max > 0) {
            $values = array_slice($values, 0, $max);
        }
        return implode(',', $values);
    }

    private function sanitize_font_family(string $font): string
    {
        $font = sanitize_text_field($font);
        $font = preg_replace("~[^a-zA-Z0-9\s,\-\"\x27]~", "", $font) ?: "";
        return substr(trim($font), 0, 120);
    }

    public function slug_exists(string $slug, int $exclude_id = 0): bool
    {
        global $wpdb;
        $table = Database::packages_table();

        if ($exclude_id > 0) {
            return (bool) $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s AND id <> %d LIMIT 1", $slug, $exclude_id)
            );
        }

        return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slug = %s LIMIT 1", $slug));
    }
    private function sanitize_focus(string $value): string
    {
        $parts = array_map('trim', explode(',', $value));
        $x = max(0, min(100, (int) ($parts[0] ?? 50)));
        $y = max(0, min(100, (int) ($parts[1] ?? 50)));
        return $x . ',' . $y;
    }

    private function sanitize_focus_json(string $value): string
    {
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) { return '{}'; }
        $clean = [];
        foreach ($decoded as $id => $focus) {
            $id = (int) $id;
            if ($id <= 0) { continue; }
            $clean[(string) $id] = $this->sanitize_focus(is_string($focus) ? $focus : '50,50');
        }
        return wp_json_encode($clean);
    }

}
