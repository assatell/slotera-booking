<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class SettingsController
{
    private const SOCIAL_SHARE_NETWORK_ALLOWLIST = ['facebook', 'x', 'whatsapp', 'telegram', 'linkedin', 'line', 'kakaotalk', 'viber', 'copy'];

    private SettingsRepository $settings;
    private RequestValidator $request;

    public function __construct(?SettingsRepository $settings = null, ?RequestValidator $request = null)
    {
        $this->settings = $settings ?? new SettingsRepository();
        $this->request = $request ?? new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_booking_availability', [$this, 'save_booking_availability']);
        add_action('admin_post_sltr_save_closures', [$this, 'save_closures']);
        add_action('admin_post_sltr_save_booking_basics', [$this, 'save_booking_basics']);
        add_action('admin_post_sltr_save_system_pages', [$this, 'save_system_pages']);
        add_action('admin_post_sltr_save_social_login_settings', [$this, 'save_social_login_settings']);
        add_action('admin_post_sltr_save_display_settings', [$this, 'save_display_settings']);
        add_action('admin_post_sltr_save_appearance_settings', [$this, 'save_appearance_settings']);
        add_action('admin_post_sltr_save_social_share_settings', [$this, 'save_social_share_settings']);
        add_action('admin_post_sltr_save_email_settings', [$this, 'save_email_settings']);
        add_action('admin_post_sltr_save_privacy_settings', [$this, 'save_privacy_settings']);
        add_action('admin_post_sltr_save_advanced_settings', [$this, 'save_advanced_settings']);
        add_action('admin_post_sltr_save_seo_settings', [$this, 'save_seo_settings']);
        add_action('admin_post_sltr_delete_seo_redirect', [$this, 'delete_seo_redirect']);
    }



    public function save_booking_availability(): void
    {
        $this->verify('sltr_save_booking_availability');
        $this->settings->update([
            'booking_availability_status' => $this->request->post_key('booking_availability_status', 'available') === 'paused' ? 'paused' : 'available',
        ]);
        $this->redirect_saved();
    }


    public function save_closures(): void
    {
        $this->verify('sltr_save_closures');

        $rows = $this->request->post_raw_array('closures');
        $closures = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $start = sanitize_text_field((string) ($row['start_date'] ?? ''));
            $end = sanitize_text_field((string) ($row['end_date'] ?? ''));
            $reason = sanitize_text_field((string) ($row['reason'] ?? ''));
            $note = sanitize_text_field((string) ($row['note'] ?? ''));
            if (!$this->valid_date($start)) {
                continue;
            }
            if (!$this->valid_date($end)) {
                $end = $start;
            }
            if ($end < $start) {
                [$start, $end] = [$end, $start];
            }
            $closures[] = [
                'start_date' => $start,
                'end_date' => $end,
                'reason' => in_array($reason, ['holiday', 'inventory', 'maintenance', 'private_event', 'other'], true) ? $reason : 'other',
                'note' => substr($note, 0, 191),
            ];
        }

        usort($closures, static fn(array $a, array $b): int => strcmp($a['start_date'], $b['start_date']));
        $this->settings->update(['business_closures' => array_slice($closures, 0, 200)]);
        $this->redirect_saved();
    }

    private function valid_date(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed instanceof \DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    public function save_booking_basics(): void
    {
        $this->verify('sltr_save_booking_basics');

        $this->settings->update([
            'payment_currency' => strtoupper(substr($this->request->post_text('payment_currency', 'EUR'), 0, 3)),
            'payment_checkout_mode' => $this->request->post_key('payment_checkout_mode', 'mixed'),
            'payment_pay_on_arrival_enabled' => $this->request->post_bool('payment_pay_on_arrival_enabled'),
            'email_notifications_enabled' => $this->request->post_bool('email_notifications_enabled'),
            'admin_notification_email' => $this->request->post_email('admin_notification_email'),
        ]);

        $this->redirect_saved();
    }

    public function save_system_pages(): void
    {
        $this->verify('sltr_save_system_pages');

        $this->settings->update([
            'booking_page_id' => $this->request->post_int('booking_page_id'),
            'categories_page_id' => $this->request->post_int('categories_page_id'),
            'thank_you_page_id' => $this->request->post_int('thank_you_page_id'),
            'checkout_page_id' => $this->request->post_int('checkout_page_id'),
            'login_page_id' => $this->request->post_int('login_page_id'),
            'account_page_id' => $this->request->post_int('account_page_id'),
        ]);

        $this->redirect_saved();
    }


    public function save_social_login_settings(): void
    {
        $this->verify('sltr_save_social_login_settings');

        $current = $this->settings->all();
        $google_secret = $this->preserve_or_replace_secret('social_login_google_client_secret', $current);
        $facebook_secret = $this->preserve_or_replace_secret('social_login_facebook_client_secret', $current);
        $apple_private_key = $this->preserve_or_replace_secret('social_login_apple_private_key', $current, true);

        $this->settings->update([
            'social_login_google_enabled' => $this->request->post_bool('social_login_google_enabled'),
            'social_login_google_client_id' => $this->request->post_text('social_login_google_client_id'),
            'social_login_google_client_secret' => $google_secret,
            'social_login_facebook_enabled' => $this->request->post_bool('social_login_facebook_enabled'),
            'social_login_facebook_client_id' => $this->request->post_text('social_login_facebook_client_id'),
            'social_login_facebook_client_secret' => $facebook_secret,
            'social_login_apple_enabled' => $this->request->post_bool('social_login_apple_enabled'),
            'social_login_apple_client_id' => $this->request->post_text('social_login_apple_client_id'),
            'social_login_apple_team_id' => $this->request->post_text('social_login_apple_team_id'),
            'social_login_apple_key_id' => $this->request->post_text('social_login_apple_key_id'),
            'social_login_apple_private_key' => $apple_private_key,
        ]);

        $this->redirect_saved();
    }

    private function preserve_or_replace_secret(string $key, array $current, bool $multiline = false): string
    {
        if (!empty($_POST[$key . '_clear'])) {
            return '';
        }
        $posted = isset($_POST[$key]) ? wp_unslash((string) $_POST[$key]) : '';
        $posted = $multiline
            ? (string) preg_replace('/[\x00]/', '', $posted)
            : (string) preg_replace('/[\x00\r\n]/', '', $posted);
        if ($posted === '') {
            return (string) ($current[$key] ?? '');
        }
        return $posted;
    }

    public function save_display_settings(): void
    {
        $this->verify('sltr_save_display_settings');

        $this->settings->update([
            'package_columns_desktop' => $this->request->post_int('package_columns_desktop', 3),
            'package_columns_tablet' => $this->request->post_int('package_columns_tablet', 2),
            'package_columns_mobile' => $this->request->post_int('package_columns_mobile', 1),
            'booking_form_width_mode' => $this->request->post_key('booking_form_width_mode', '1280'),
            'booking_form_custom_width' => $this->request->post_int('booking_form_custom_width', 1280),
            'show_duration_on_cards' => $this->request->post_bool('show_duration_on_cards'),
            'show_slotera_page_titles' => $this->request->post_bool('show_slotera_page_titles'),
            'select_time_layout' => $this->request->post_key('select_time_layout', 'grid'),
        ]);

        $this->redirect_saved();
    }


    public function save_social_share_settings(): void
    {
        $this->verify('sltr_save_social_share_settings');

        $networks = isset($_POST['social_share_networks']) ? (array) wp_unslash($_POST['social_share_networks']) : [];
        $this->settings->update([
            'social_share_enabled' => $this->request->post_bool('social_share_enabled'),
            'social_share_networks' => $this->sanitize_social_share_networks($networks),
        ]);

        $this->redirect_saved();
    }


    /**
     * @param array<int|string,mixed> $networks
     * @return array<int,string>
     */
    private function sanitize_social_share_networks(array $networks): array
    {
        $enabled = [];
        foreach ($networks as $network) {
            $network = sanitize_key((string) $network);
            if (in_array($network, self::SOCIAL_SHARE_NETWORK_ALLOWLIST, true)) {
                $enabled[] = $network;
            }
        }

        return array_values(array_unique($enabled));
    }


    public function save_appearance_settings(): void
    {
        $this->verify('sltr_save_appearance_settings');

        $this->settings->update([
            'appearance_theme' => $this->request->post_key('appearance_theme', 'light'),
            'form_background_color' => $this->sanitize_color('form_background_color'),
            'form_text_color' => $this->sanitize_color('form_text_color'),
            'card_background_color' => $this->sanitize_color('card_background_color'),
            'card_border_color' => $this->sanitize_color('card_border_color'),
            'primary_color' => $this->sanitize_color('primary_color'),
            'primary_text_color' => $this->sanitize_color('primary_text_color'),
            'muted_text_color' => $this->sanitize_color('muted_text_color'),
            'price_old_color' => $this->sanitize_color('price_old_color'),
            'price_new_color' => $this->sanitize_color('price_new_color'),
            'discount_badge_background_color' => $this->sanitize_color('discount_badge_background_color'),
            'discount_badge_text_color' => $this->sanitize_color('discount_badge_text_color'),
            'tooltip_icon_color' => $this->sanitize_color('tooltip_icon_color'),
            'tooltip_background_color' => $this->sanitize_color('tooltip_background_color'),
            'tooltip_text_color' => $this->sanitize_color('tooltip_text_color'),
            'calendar_background_color' => $this->sanitize_color('calendar_background_color'),
            'calendar_text_color' => $this->sanitize_color('calendar_text_color'),
            'calendar_border_color' => $this->sanitize_color('calendar_border_color'),
            'calendar_day_background_color' => $this->sanitize_color('calendar_day_background_color'),
            'calendar_disabled_background_color' => $this->sanitize_color('calendar_disabled_background_color'),
            'calendar_disabled_text_color' => $this->sanitize_color('calendar_disabled_text_color'),
            'price_old_style' => $this->request->post_key('price_old_style', 'line-through'),
            'price_old_size_ratio' => $this->request->post_float('price_old_size_ratio', 0.85),
        ]);

        $this->redirect_saved();
    }


    public function save_advanced_settings(): void
    {
        $this->verify('sltr_save_advanced_settings');

        $this->settings->update([
            'real_server_cron_configured' => $this->request->post_bool('real_server_cron_configured'),
        ]);

        $this->redirect_saved();
    }

    public function save_email_settings(): void
    {
        $this->verify('sltr_save_email_settings');

        $this->settings->update([
            'email_notifications_enabled' => $this->request->post_bool('email_notifications_enabled'),
            'customer_booking_email_enabled' => $this->request->post_bool('customer_booking_email_enabled'),
            'admin_booking_email_enabled' => $this->request->post_bool('admin_booking_email_enabled'),
            'email_from_name' => $this->request->post_text('email_from_name'),
            'email_from_address' => $this->request->post_email('email_from_address'),
            'admin_notification_email' => $this->request->post_email('admin_notification_email'),
            'customer_booking_subject' => $this->request->post_text('customer_booking_subject'),
            'customer_booking_body' => $this->request->post_html('customer_booking_body'),
            'admin_booking_subject' => $this->request->post_text('admin_booking_subject'),
            'admin_booking_body' => $this->request->post_html('admin_booking_body'),
        ]);

        $this->redirect_saved();
    }



    public function save_seo_settings(): void
    {
        $this->verify('sltr_save_seo_settings');

        $this->settings->update([
            'seo_sitemap_enabled' => $this->request->post_bool('seo_sitemap_enabled'),
            'seo_sitemap_include_packages' => $this->request->post_bool('seo_sitemap_include_packages'),
            'seo_sitemap_include_categories' => $this->request->post_bool('seo_sitemap_include_categories'),
            'seo_sitemap_include_locations' => $this->request->post_bool('seo_sitemap_include_locations'),
            'seo_sitemap_include_other_pages' => $this->request->post_bool('seo_sitemap_include_other_pages'),
            'seo_sitemap_include_posts' => $this->request->post_bool('seo_sitemap_include_posts'),
            'seo_robots_smart_builder_enabled' => $this->request->post_bool('seo_robots_smart_builder_enabled'),
            'seo_robots_block_wp_search' => $this->request->post_bool('seo_robots_block_wp_search'),
            'seo_robots_block_slotera_technical' => $this->request->post_bool('seo_robots_block_slotera_technical'),
            'seo_robots_block_tracking_params' => $this->request->post_bool('seo_robots_block_tracking_params'),
            'seo_robots_block_attachment_pages' => $this->request->post_bool('seo_robots_block_attachment_pages'),
            'seo_robots_add_sitemap' => $this->request->post_bool('seo_robots_add_sitemap'),
            'seo_robots_custom_rules' => $this->request->post_textarea('seo_robots_custom_rules'),
            'seo_meta_output_mode' => $this->request->post_key('seo_meta_output_mode', 'auto'),
            'seo_default_robots' => $this->request->post_text('seo_default_robots', 'index,follow'),
            'seo_noindex_empty_categories' => $this->request->post_bool('seo_noindex_empty_categories'),
            'seo_noindex_inactive_items' => $this->request->post_bool('seo_noindex_inactive_items'),
            'seo_title_format' => $this->request->post_text('seo_title_format', '{title} | {site}'),
            'seo_breadcrumbs_enabled' => $this->request->post_bool('seo_breadcrumbs_enabled'),
            'seo_breadcrumbs_show_packages' => $this->request->post_bool('seo_breadcrumbs_show_packages'),
            'seo_breadcrumbs_show_categories' => $this->request->post_bool('seo_breadcrumbs_show_categories'),
            'seo_breadcrumbs_show_local' => $this->request->post_bool('seo_breadcrumbs_show_local'),
            'seo_local_business_schema_enabled' => $this->request->post_bool('seo_local_business_schema_enabled'),
            'seo_local_business_type' => $this->request->post_text('seo_local_business_type', 'LocalBusiness'),
            'seo_local_business_name' => $this->request->post_text('seo_local_business_name'),
            'seo_local_business_phone' => $this->request->post_text('seo_local_business_phone'),
            'seo_local_business_email' => sanitize_email($this->request->post_text('seo_local_business_email')),
            'seo_local_business_logo' => esc_url_raw($this->request->post_text('seo_local_business_logo')),
            'seo_local_business_street' => $this->request->post_text('seo_local_business_street'),
            'seo_local_business_city' => $this->request->post_text('seo_local_business_city'),
            'seo_local_business_region' => $this->request->post_text('seo_local_business_region'),
            'seo_local_business_postal_code' => $this->request->post_text('seo_local_business_postal_code'),
            'seo_local_business_country' => $this->request->post_text('seo_local_business_country'),
            'seo_local_business_price_range' => $this->request->post_text('seo_local_business_price_range'),
            'seo_local_business_same_as' => $this->request->post_textarea('seo_local_business_same_as'),
        ]);

        $this->redirect_saved();
    }

    public function delete_seo_redirect(): void
    {
        $this->verify('sltr_delete_seo_redirect');
        $slug = $this->request->post_key('slug');
        (new \Slotera\Application\Services\RedirectService())->delete_redirect($slug);
        $this->redirect_saved();
    }

    public function save_privacy_settings(): void
    {
        $this->verify('sltr_save_privacy_settings');

        $this->settings->update([
            'privacy_remove_data_on_uninstall' => $this->request->post_bool('privacy_remove_data_on_uninstall'),
            'privacy_visitor_analytics_enabled' => $this->request->post_bool('privacy_visitor_analytics_enabled'),
            'privacy_visitor_session_analytics_enabled' => $this->request->post_bool('privacy_visitor_session_analytics_enabled'),
            'privacy_visitor_analytics_retention_days' => $this->request->post_int('privacy_visitor_analytics_retention_days', 90),
            'privacy_anonymize_completed_bookings_days' => $this->request->post_int('privacy_anonymize_completed_bookings_days', 365),
            'privacy_activity_log_retention_days' => $this->request->post_int('privacy_activity_log_retention_days', 365),
            'privacy_booking_history_retention_days' => $this->request->post_int('privacy_booking_history_retention_days', 1095),
            'privacy_email_queue_retention_days' => $this->request->post_int('privacy_email_queue_retention_days', 90),
            'privacy_marketing_log_retention_days' => $this->request->post_int('privacy_marketing_log_retention_days', 180),
            'privacy_webhook_event_retention_days' => $this->request->post_int('privacy_webhook_event_retention_days', 180),
            'privacy_outgoing_webhook_retention_days' => $this->request->post_int('privacy_outgoing_webhook_retention_days', 180),
        ]);

        $this->redirect_saved();
    }

    private function verify(string $action): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce($action);
    }

    private function sanitize_color(string $key): string
    {
        $value = $this->request->post_text($key);

        if ($value === '') {
            return '';
        }

        $hex = sanitize_hex_color($value);

        return is_string($hex) ? $hex : '';
    }

    private function redirect_saved(): void
    {
        $return_to = $this->request->post_key('return_to');
        $settings_section = $this->request->post_key('settings_section');
        $security_tab = $this->request->post_key('security_tab');

        $args = [
            'page' => 'slotera-settings',
            'sltr_message' => 'saved',
        ];
        if (in_array($settings_section, ['email', 'advanced', 'security'], true)) {
            $args['section'] = $settings_section;
        }
        if ($settings_section === 'security' && in_array($security_tab, ['protection', 'privacy'], true)) {
            $args['security_tab'] = $security_tab;
        }

        $url = add_query_arg($args, admin_url('admin.php'));

        if ($return_to !== '') {
            $url .= '#' . $return_to;
        }

        wp_safe_redirect($url);
        exit;
    }
}
