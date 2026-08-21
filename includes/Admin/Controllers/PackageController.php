<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\BookingModeConfiguration\BookingModeConfigurationManager;
use Slotera\Application\Services\PageBindingService;
use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\BusinessValidator;
use Slotera\Admin\Support\PackageModeConfigUpdater;
use Slotera\Admin\Support\WorkingHoursFactory;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\LocationRepository;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\WorkingHoursRepository;
use Slotera\Infrastructure\Repositories\EventRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class PackageController
{
    private PackageRepository $repo;
    private RequestValidator $request;

    public function __construct(?RequestValidator $request = null)
    {
        $this->repo = new PackageRepository();
        $this->request = $request ?? new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_package', [$this, 'save']);
        add_action('admin_post_sltr_deactivate_package', [$this, 'deactivate']);
        add_action('admin_post_sltr_restore_package', [$this, 'restore']);
    }

    public function save(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);
        $this->request->verify_admin_nonce('sltr_save_package');

        $id = $this->request->post_int('id');
        $existing_package = $id > 0 ? $this->repo->get_by_id($id) : null;
        $requested_slug = sanitize_title($this->request->post_text('slug'));
        if ($id > 0 && is_array($existing_package)) {
            $saved_slug = sanitize_title((string) ($existing_package['slug'] ?? ''));
            if ($saved_slug !== '') {
                // The package slug is immutable after the first save. Ignore the
                // posted readonly value on later section/full-form saves and use
                // the canonical stored slug. PackageRepository::update() enforces
                // the same invariant, so Booking blocks cannot be rejected by a
                // browser/normalization difference in an already locked slug.
                $requested_slug = $saved_slug;
            } else {
                if ($requested_slug === '') { $requested_slug = sanitize_title($this->request->post_text('title')); }
                if ($requested_slug !== '' && $this->repo->slug_exists($requested_slug, $id)) {
                    wp_safe_redirect(admin_url('admin.php?page=slotera-packages&action=edit&id=' . absint($id) . '&sltr_error=slug_exists'));
                    exit;
                }
            }
        } else {
            if ($requested_slug === '') { $requested_slug = sanitize_title($this->request->post_text('title')); }
            if ($requested_slug !== '' && $this->repo->slug_exists($requested_slug)) {
                wp_safe_redirect(admin_url('admin.php?page=slotera-packages&action=new&sltr_error=slug_exists'));
                exit;
            }
        }

        $booking_modes = new BookingModeConfigurationManager($this->request, $this->repo);
        $package_booking_type = $this->request->post_key('package_booking_type', 'standard');
        $booking_mode = $booking_modes->normalize_mode($this->request->post_key('booking_mode_selector', $this->request->post_key('booking_mode', 'simple')));
        if ($package_booking_type === 'events') {
            $booking_mode = 'date_range_inventory';
        }
        $mode_configs = $booking_modes->collect_mode_configs($booking_mode);
        if ($package_booking_type === 'events') {
            if (!isset($mode_configs['date_range_inventory']) || !is_array($mode_configs['date_range_inventory'])) {
                $mode_configs['date_range_inventory'] = [];
            }
            $mode_configs['date_range_inventory']['date_flow'] = 'admin_scheduled';
        }
        $active_config = $mode_configs[$booking_mode] ?? [];
        $fixed_full_day_booking = $booking_mode === 'fixed' && !empty($active_config['full_day_booking']);
        $mode_package_fields = $booking_modes->package_fields_for_mode($booking_mode, $active_config);

        $solo_top_requested = $this->request->post_bool('solo_top_text_active')
            ? '[slotera_package_text_block]'
            : $this->request->post_html('solo_top_content');
        [$solo_top_content, $solo_content, $solo_down_content] = $this->normalize_unique_solo_shortcodes([
            $solo_top_requested,
            $this->request->post_html('solo_content'),
            $this->request->post_html('solo_down_content'),
        ]);

        $solo_page_enabled = $this->request->post_key('solo_page_enabled', '1') === '1' ? 1 : 0;
        $show_more_info = $solo_page_enabled ? $this->request->post_bool('show_more_info') : 0;

        $data = [
            'category_id' => $this->request->post_int('category_id'),
            'title' => $this->request->post_text('title'),
            'slug' => $requested_slug,
            'title_font_family' => $this->request->post_text('title_font_family'),
            'title_font_size' => max(12, min(48, $this->request->post_int('title_font_size', 24))),
            'description' => $this->request->post_html('description'),
            'solo_top_content' => $solo_top_content,
            'show_solo_top_content' => trim(wp_strip_all_tags($solo_top_content)) !== '' || trim($solo_top_content) !== '' ? 1 : 0,
            'solo_content' => $solo_content,
            'solo_down_content' => $solo_down_content,
            'show_solo_down_content' => trim(wp_strip_all_tags($solo_down_content)) !== '' || trim($solo_down_content) !== '' ? 1 : 0,
            'show_more_info' => $show_more_info,
            'solo_page_enabled' => $solo_page_enabled,
            'card_image_id' => $this->request->post_int('card_image_id'),
            'booking_card_image_id' => $this->request->post_int('booking_card_image_id'),
            'card_image_focus' => $this->request->post_text('card_image_focus', '50,50'),
            'booking_card_image_focus' => $this->request->post_text('booking_card_image_focus', '50,50'),
            'popular_icon' => $this->request->post_key('popular_icon', ''),
            'popular_icon_color' => sanitize_hex_color($this->request->post_text('popular_icon_color', '#7c3aed')) ?: '#7c3aed',
            'popular_icon_size' => max(16, min(48, $this->request->post_int('popular_icon_size', 24))),
            'solo_media_json' => $this->request->post_textarea('solo_media_json'),
            'solo_contact_image_id' => $this->request->post_int('solo_contact_image_id'),
            'solo_contact_map' => $this->request->post_textarea('solo_contact_map'),
            'solo_contact_details_json' => $this->request->post_textarea('solo_contact_details_json'),
            'slider_image_ids' => $this->request->post_text('slider_image_ids'),
            'slider_image_focus_json' => $this->request->post_textarea('slider_image_focus_json'),
            'slider_speed' => $this->request->post_int('slider_speed', 4000),
            'slider_image_position' => 'top',
            'gallery_image_ids' => $this->request->post_text('gallery_image_ids'),
            'gallery_image_focus' => $this->request->post_text('gallery_image_focus', '50,50'),
            'gallery_layout' => 'grid',
            'solo_layout' => $this->request->post_key('solo_layout', 'classic'),
            'right_block_title' => $this->request->post_text('right_block_title'),
            'right_block_text' => $this->request->post_html('right_block_text'),
            'right_block_title_font_family' => $this->request->post_text('right_block_title_font_family'),
            'right_block_title_font_size' => $this->request->post_int('right_block_title_font_size', 32),
            'right_block_text_font_family' => $this->request->post_text('right_block_text_font_family', 'Inter, Arial, sans-serif'),
            'right_block_text_font_size' => $this->request->post_int('right_block_text_font_size', 24),
            // Backward compatibility for older installs/forms. Not shown in the UI anymore.
            'right_block_font_family' => $this->request->post_text('right_block_font_family'),
            'right_block_font_size' => $this->request->post_int('right_block_font_size', 18),
            'info_tooltip' => $this->request->post_textarea('info_tooltip'),
            'tooltip_size_ratio' => max(0.8, min(2.0, $this->request->post_float('tooltip_size_ratio', 1.15))),
            'tooltip_text_size' => max(10, min(24, $this->request->post_int('tooltip_text_size', 13))),
            'description_font_family' => $this->request->post_text('description_font_family'),
            'description_font_size' => $this->request->post_int('description_font_size', 18),
            'duration_minutes' => $fixed_full_day_booking ? 1440 : BusinessValidator::duration_minutes($active_config['duration_minutes'] ?? 60, 60, 1, 1440),
            'show_duration_frontend' => (int) ($mode_package_fields['show_duration_frontend'] ?? 0),
            'max_bookings_per_slot' => BusinessValidator::capacity($mode_package_fields['max_bookings_per_slot'] ?? 1),
            'low_availability_notice_enabled' => !empty($active_config['low_availability_notice_enabled']) ? 1 : 0,
            'low_availability_threshold' => BusinessValidator::capacity($active_config['low_availability_threshold'] ?? 5, 5, 1, 99),
            'price' => BusinessValidator::money($active_config['price'] ?? 0),
            'discount_type' => (string) ($active_config['discount_type'] ?? 'none'),
            'discount_value' => BusinessValidator::money($active_config['discount_value'] ?? 0),
            'campaign_note' => sanitize_text_field((string) ($active_config['campaign_note'] ?? '')),
            'dynamic_pricing_enabled' => !empty($active_config['dynamic_pricing_enabled']) ? 1 : 0,
            'dynamic_weekend_percent' => BusinessValidator::percent($active_config['dynamic_weekend_percent'] ?? 0),
            'dynamic_season_start' => BusinessValidator::date($active_config['dynamic_season_start'] ?? ''),
            'dynamic_season_end' => BusinessValidator::date($active_config['dynamic_season_end'] ?? ''),
            'dynamic_season_percent' => BusinessValidator::percent($active_config['dynamic_season_percent'] ?? 0),
            'tax_enabled' => !empty($active_config['tax_enabled']) ? 1 : 0,
            'tax_label' => sanitize_text_field((string) ($active_config['tax_label'] ?? 'VAT')),
            'tax_rate' => BusinessValidator::percent($active_config['tax_rate'] ?? 0),
            'tax_mode' => in_array((string) ($active_config['tax_mode'] ?? 'exclusive'), ['exclusive', 'inclusive'], true) ? (string) $active_config['tax_mode'] : 'exclusive',
            'booking_mode' => $booking_mode,
            'mode_configs_json' => wp_json_encode($mode_configs),
            'slot_step' => $fixed_full_day_booking ? 60 : BusinessValidator::duration_minutes($active_config['slot_step'] ?? ($active_config['duration_minutes'] ?? 60), 60, 1, 1440),
            'price_unit' => (string) ($mode_package_fields['price_unit'] ?? 'fixed'),
            'hourly_price' => BusinessValidator::money($active_config['hourly_price'] ?? 0),
            'checkin_time' => BusinessValidator::time($active_config['checkin_time'] ?? '15:00', '15:00'),
            'checkout_time' => BusinessValidator::time($active_config['checkout_time'] ?? '11:00', '11:00'),
            'min_nights' => BusinessValidator::capacity($active_config['min_nights'] ?? 1, 1, 1, 365),
            'max_nights' => BusinessValidator::capacity($active_config['max_nights'] ?? 30, 30, 1, 365),
            'inventory_units_json' => (string) ($active_config['inventory_units_json'] ?? ''),
            'date_inventory_json' => (string) ($active_config['date_inventory_json'] ?? ''),
            'date_flow' => (string) ($active_config['date_flow'] ?? 'customer_choice'),
            'scheduled_events_json' => (string) ($active_config['scheduled_events_json'] ?? ''),
            'included_services' => (string) ($active_config['included_services'] ?? ''),
            'extra_services_json' => (string) ($active_config['extra_services_json'] ?? ''),
            'checkout_mode' => $this->request->post_key('checkout_mode', 'booking_only'),
            'payment_policy' => (string) ($active_config['payment_policy'] ?? 'booking_only'),
            'deposit_type' => (string) ($active_config['deposit_type'] ?? 'percent'),
            'deposit_value' => BusinessValidator::money($active_config['deposit_value'] ?? 30),
            'hours_mode' => $this->request->post_key('hours_mode', 'global'),
            'open_247' => $this->request->post_bool('open_247'),
            'buffer_before' => $this->post_duration_minutes('buffer_before', 0),
            'buffer_after' => $this->post_duration_minutes('buffer_after', 0),
            'is_popular' => $this->request->post_key('popular_icon', '') !== '',
            'is_active' => is_array($existing_package) ? (int) ($existing_package['is_active'] ?? 1) : 1,
            'location_ids' => array_map('absint', $this->request->post_array('location_ids')),
        ];

        if ((int) $data['max_nights'] < (int) $data['min_nights']) {
            $data['max_nights'] = $data['min_nights'];
        }

        $new_location_ids = $this->create_locations_from_package_form($this->request->post_textarea('new_location_names'));
        if (!empty($new_location_ids)) {
            $data['location_ids'] = array_values(array_unique(array_merge((array) ($data['location_ids'] ?? []), $new_location_ids)));
        }

        $save_ok = false;
        if ($id > 0) {
            $save_ok = $this->repo->update($id, $data);
        } else {
            $id = $this->repo->create($data);
            $save_ok = $id > 0;
        }

        if (!$save_ok) {
            global $wpdb;
            if (function_exists('error_log')) {
                error_log('Slotera package save failed: ' . (string) ($wpdb->last_error ?? 'unknown database error'));
            }
            $action = $id > 0 ? 'edit&id=' . absint($id) : 'new';
            wp_safe_redirect(admin_url('admin.php?page=slotera-packages&action=' . $action . '&sltr_error=save_failed'));
            exit;
        }

        if ($id > 0) {
            $forced_confirm_immediately = $booking_modes->posted_confirm_immediately_simple();
            if ($forced_confirm_immediately !== null) {
                (new PackageModeConfigUpdater($this->repo))->force_simple_confirm_immediately($id, $forced_confirm_immediately);
            }
            $forced_simple_price_mode = $booking_modes->posted_simple_price_mode();
            if ($forced_simple_price_mode !== null) {
                (new PackageModeConfigUpdater($this->repo))->force_simple_price_mode($id, $forced_simple_price_mode);
            }
            $location_repo = new LocationRepository();
            $existing_local_seo = $location_repo->get_relations_for_package($id);
            $location_repo->replace_package_locations($id, (array) ($data['location_ids'] ?? []), [
                'intro_override' => array_map(static fn($row) => (string) ($row['intro_override'] ?? ''), $existing_local_seo),
                'faq_override' => array_map(static function ($row): array {
                    $decoded = json_decode((string) ($row['faq_override_json'] ?? ''), true);
                    return is_array($decoded) ? $decoded : [];
                }, $existing_local_seo),
            ]);
            (new PageBindingService())->sync_package_page($id);
        }

        if ($id > 0 && $package_booking_type === 'events') {
            $event_repo = new EventRepository();
            $event_id = $this->request->post_int('scheduled_event_id');
            if ($event_id <= 0) {
                $existing_event = $event_repo->get_first_for_package($id);
                $event_id = is_array($existing_event) ? (int) ($existing_event['id'] ?? 0) : 0;
            }
            $existing_event = $event_id > 0 ? $event_repo->get_by_id($event_id) : null;
            $payment_options = isset($_POST['event_payment_options']) && is_array($_POST['event_payment_options'])
                ? array_map('sanitize_key', wp_unslash($_POST['event_payment_options']))
                : [];
            $payment_options = array_values(array_intersect($payment_options, ['booking_only', 'deposit_payment', 'full_payment']));
            $has_booking = in_array('booking_only', $payment_options, true);
            $has_deposit = in_array('deposit_payment', $payment_options, true);
            $has_full = in_array('full_payment', $payment_options, true);
            $event_payment_policy = $has_booking && $has_deposit && $has_full ? 'all_options'
                : ($has_full && $has_deposit ? 'full_or_deposit'
                : ($has_booking && $has_full ? 'booking_or_full'
                : ($has_booking && $has_deposit ? 'booking_or_deposit'
                : ($has_full ? 'full_payment' : ($has_deposit ? 'deposit_payment' : 'booking_only')))));
            $event_data = [
                'package_id' => $id,
                'title' => (string) $data['title'],
                'event_date' => BusinessValidator::date_or_today($this->request->post_text('event_date', current_time('Y-m-d'))),
                'end_date' => BusinessValidator::date_or_today($this->request->post_text('end_date', current_time('Y-m-d'))),
                'use_time' => $this->request->post_bool('use_time'),
                'start_time' => BusinessValidator::time($this->request->post_text('start_time', '09:00'), '09:00'),
                'end_time' => BusinessValidator::time($this->request->post_text('end_time', '10:00'), '10:00'),
                'timezone' => $this->request->post_text('timezone', wp_timezone_string()),
                'capacity' => BusinessValidator::capacity($this->request->post_int('capacity', 1)),
                'price_override' => (string) BusinessValidator::money($this->request->post_text('price_override', '0')),
                'discount_type' => $this->request->post_key('event_discount_type', 'none'),
                'discount_value' => (string) BusinessValidator::money($this->request->post_text('event_discount_value', '0')),
                'allow_coupons' => $this->request->post_bool('event_allow_coupons'),
                'payment_policy' => $event_payment_policy,
                'deposit_type' => $this->request->post_key('event_deposit_type', 'percent'),
                'deposit_value' => (string) BusinessValidator::money($this->request->post_text('event_deposit_value', '30')),
                'location' => $this->request->post_text('event_location'),
                'status' => $this->request->post_key('event_status', 'scheduled'),
                'reminder_profile' => is_array($existing_event) ? (string) ($existing_event['reminder_profile'] ?? 'default') : 'default',
                'automation_profile' => is_array($existing_event) ? (string) ($existing_event['automation_profile'] ?? 'default') : 'default',
                'is_active' => $this->request->post_bool('event_is_active'),
                'booked_count' => is_array($existing_event)
                    ? min(max(0, (int) ($existing_event['booked_count'] ?? 0)), BusinessValidator::capacity($this->request->post_int('capacity', 1)))
                    : 0,
            ];
            if ($event_data['end_date'] < $event_data['event_date']) { $event_data['end_date'] = $event_data['event_date']; }
            if ($event_data['use_time'] && $event_data['end_date'] === $event_data['event_date'] && $event_data['end_time'] <= $event_data['start_time']) {
                $event_data['end_time'] = BusinessValidator::time(date('H:i', strtotime($event_data['start_time'] . ' +1 hour')), '10:00');
            }
            $event_saved = $event_id > 0 ? $event_repo->update($event_id, $event_data) : $event_repo->create($event_data) > 0;
            if (!$event_saved) {
                global $wpdb;
                if (function_exists('error_log')) {
                    error_log('Slotera package event save failed: ' . (string) ($wpdb->last_error ?? 'unknown database error'));
                }
                wp_safe_redirect(admin_url('admin.php?page=slotera-packages&action=edit&id=' . absint($id) . '&sltr_message=event_save_failed'));
                exit;
            }
            do_action('sltr_data_changed', 'event_saved', ['package_id' => $id]);
        }

        if ($id > 0 && $data['hours_mode'] === 'custom') {
            $hours = !empty($data['open_247']) ? WorkingHoursFactory::open_247_hours() : $this->request->sanitize_hours_from_post();
            (new WorkingHoursRepository())->replace_scope_hours('package', $id, $hours);
        }

        $save_section = $this->request->post_key('sltr_save_section', '');
        $section_anchor = in_array($save_section, ['package-settings', 'solo-page-settings'], true) ? '#sltr-' . $save_section : '';
        wp_safe_redirect(admin_url('admin.php?page=slotera-packages&action=edit&id=' . absint($id) . '&sltr_message=saved') . $section_anchor);
        exit;
    }



    public function deactivate(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);
        $id = $this->request->get_int('id');
        if ($id <= 0) { wp_safe_redirect(admin_url('admin.php?page=slotera-packages')); exit; }
        $this->request->verify_admin_nonce('sltr_deactivate_package_' . $id);
        $this->repo->update($id, ['is_active' => 0]);
        (new PageBindingService())->trash_package_page($id);
        wp_safe_redirect(admin_url('admin.php?page=slotera-packages&sltr_message=draft'));
        exit;
    }

    public function restore(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);
        $id = $this->request->get_int('id');
        if ($id <= 0) { wp_safe_redirect(admin_url('admin.php?page=slotera-packages')); exit; }
        $this->request->verify_admin_nonce('sltr_restore_package_' . $id);
        $this->repo->update($id, ['is_active' => 1]);
        (new PageBindingService())->sync_package_page($id);
        wp_safe_redirect(admin_url('admin.php?page=slotera-packages&sltr_message=restored'));
        exit;
    }

    private function collect_local_faq_overrides(array $raw): array
    {
        $collected = [];
        foreach ($raw as $location_id => $items) {
            $location_id = absint((string) $location_id);
            if ($location_id <= 0 || !is_array($items)) {
                continue;
            }
            $collected[$location_id] = [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $collected[$location_id][] = [
                    'question' => sanitize_text_field((string) ($item['question'] ?? '')),
                    'answer' => wp_kses_post((string) ($item['answer'] ?? '')),
                ];
            }
        }
        return $collected;
    }

    private function valid_date(string $date): bool
    {
        if ($date === '') {
            return true;
        }

        $d = \DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }


    /**
     * Create new Location records directly from the package edit screen.
     *
     * Admins often discover package locations while editing a package. Keeping
     * this as a lightweight inline helper avoids confusing fixed city lists while
     * the full Locations screen remains the canonical place for advanced edits.
     *
     * @return int[]
     */
    private function create_locations_from_package_form(string $raw_names): array
    {
        $raw_names = trim($raw_names);
        if ($raw_names === '') {
            return [];
        }

        $parts = preg_split('/[\r\n,;]+/', $raw_names) ?: [];
        $repo = new LocationRepository();
        $existing = $repo->get_all();
        $existing_by_slug = [];
        foreach ($existing as $location) {
            $slug = sanitize_title((string) ($location['slug'] ?? $location['name'] ?? ''));
            if ($slug !== '') {
                $existing_by_slug[$slug] = (int) ($location['id'] ?? 0);
            }
        }

        $created_or_existing_ids = [];
        foreach ($parts as $part) {
            $name = sanitize_text_field((string) $part);
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $slug = sanitize_title($name);
            if ($slug === '') {
                continue;
            }
            if (isset($existing_by_slug[$slug]) && $existing_by_slug[$slug] > 0) {
                $created_or_existing_ids[] = $existing_by_slug[$slug];
                continue;
            }
            $new_id = $repo->create([
                'name' => $name,
                'slug' => $slug,
                'description' => '',
                'intro_content' => '',
                'faq' => [],
                'sort_order' => 0,
                'is_active' => 1,
            ]);
            if ($new_id > 0) {
                $existing_by_slug[$slug] = $new_id;
                $created_or_existing_ids[] = $new_id;
            }
        }

        return array_values(array_unique(array_filter(array_map('absint', $created_or_existing_ids))));
    }

    private function post_duration_minutes(string $base, int $default = 0): int
    {
        $post = wp_unslash($_POST);

        if (isset($post[$base . '_hours']) || isset($post[$base . '_mins'])) {
            $hours = isset($post[$base . '_hours']) ? absint($post[$base . '_hours']) : 0;
            $mins = isset($post[$base . '_mins']) ? absint($post[$base . '_mins']) : 0;

            return BusinessValidator::duration_from_hours_minutes($hours, $mins, $default, 0, 1440);
        }

        return BusinessValidator::duration_minutes($this->request->post_int($base . '_minutes', $default), $default, 0, 1440);
    }

    /**
     * Ensure each package media shortcode appears in only one solo-page region.
     * Priority follows the form order: top, right, then down.
     *
     * @param array<int,string> $contents
     * @return array<int,string>
     */
    private function normalize_unique_solo_shortcodes(array $contents): array
    {
        $unique_tags = [
            'slotera_package_slider',
            'slotera_package_image',
            'slotera_package_text_block',
            'slotera_contact',
        ];
        $seen = [];

        foreach ($contents as $index => $content) {
            $normalized = (string) $content;
            foreach ($unique_tags as $tag) {
                $pattern = '/\[' . preg_quote($tag, '/') . '(?:\s[^\]]*)?\]/i';
                $normalized = preg_replace_callback(
                    $pattern,
                    static function (array $match) use (&$seen, $tag): string {
                        if (isset($seen[$tag])) {
                            return '';
                        }
                        $seen[$tag] = true;
                        return (string) $match[0];
                    },
                    $normalized
                ) ?? $normalized;
            }
            $contents[$index] = trim((string) preg_replace('/(?:\r?\n){3,}/', "\n\n", $normalized));
        }

        return array_values($contents);
    }

}
