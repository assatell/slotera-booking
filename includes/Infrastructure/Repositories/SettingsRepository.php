<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Repositories;

use Slotera\Application\Services\EmailTemplateRegistry;
use Slotera\Application\Services\PerformanceProfiler;
use Slotera\Application\Security\SecretStore;

if (!defined('ABSPATH')) {
    exit;
}

final class SettingsRepository
{
    public const OPTION_NAME = 'sltr_settings';

    /** @var array<string,array<string,mixed>> Request-local resolved settings by raw option fingerprint. */
    private static array $request_cache = [];

    public function all(): array
    {
        $raw_settings = get_option(self::OPTION_NAME, []);
        if (!is_array($raw_settings)) {
            $raw_settings = [];
        }

        // get_option() is already object-cached by WordPress. Keep that cheap
        // read so an update_option() performed elsewhere in the same request is
        // still observed, while avoiding repeated legacy normalization, secret
        // decryption and email-template localization for unchanged settings.
        $fingerprint = hash('sha256', serialize($raw_settings));
        PerformanceProfiler::metric('settings.all.calls');
        if (isset(self::$request_cache[$fingerprint])) {
            PerformanceProfiler::metric('settings.all.cache_hits');
            return self::$request_cache[$fingerprint];
        }

        PerformanceProfiler::metric('settings.all.cache_misses');
        $started_at = microtime(true);
        $settings = $this->normalize_legacy_settings($raw_settings);
        $settings = $this->migrate_and_decrypt_secrets($settings);
        $resolved = $this->localize_email_template_defaults(array_merge($this->defaults(), $settings), $settings);
        PerformanceProfiler::metric('settings.all.resolve_ms', round((microtime(true) - $started_at) * 1000, 3));

        // Only the current raw state is useful. Keeping a single entry also
        // avoids retaining decrypted settings for historical states in memory.
        self::$request_cache = [$fingerprint => $resolved];
        return $resolved;
    }

    public function get(string $key, $default = null)
    {
        $settings = $this->all();
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    private function normalize_legacy_settings(array $settings): array
    {
        // RC58: populate White Label fields that were previously shipped with empty defaults.
        // Existing non-empty custom values are preserved.
        if (!isset($settings['white_label_defaults_v2_migrated'])) {
            if (!isset($settings['white_label_admin_footer_text']) || trim((string) $settings['white_label_admin_footer_text']) === '') {
                $settings['white_label_admin_footer_text'] = 'Powered by Slotera';
            }
            if (!isset($settings['white_label_plugin_description']) || trim((string) $settings['white_label_plugin_description']) === '') {
                $settings['white_label_plugin_description'] = 'Complete WordPress booking platform with payments, packages, coupons, marketing automation, analytics and customer management.';
            }
            $settings['white_label_defaults_v2_migrated'] = 1;
            update_option(self::OPTION_NAME, $settings, false);
        }

        if (array_key_exists('booking_pending_confirmation_cleanup_minutes', $settings)) {
            if (!array_key_exists('payment_pending_cleanup_minutes', $settings)) {
                $settings['payment_pending_cleanup_minutes'] = max(15, min(10080, (int) $settings['booking_pending_confirmation_cleanup_minutes']));
            }

            unset($settings['booking_pending_confirmation_cleanup_minutes']);
        }

        // v1.0.592: migrate only untouched values from previous Dark presets.
        // Individually customized colors are preserved.
        if (($settings['appearance_theme'] ?? '') === 'dark') {
            $dark_palette_migration = [
                'form_text_color' => ['#e5e7eb', '#ffffff'],
                'primary_text_color' => ['#0f172a', '#ffffff'],
                'price_old_color' => ['#94a3b8', '#EF4444'],
                'price_new_color' => ['#f8fafc', '#61CE4B'],
                'calendar_disabled_text_color' => ['#94a3b8', '#BCC9DC'],
                'primary_color' => ['#60a5fa', '#334155'],
                'tooltip_icon_color' => ['#93c5fd', '#ffffff'],
                'tooltip_background_color' => ['#020617', '#334155'],
                'tooltip_text_color' => ['#f8fafc', '#ffffff'],
                'calendar_text_color' => ['#e5e7eb', '#ffffff'],
            ];

            $changed = false;
            foreach ($dark_palette_migration as $key => [$old_value, $new_value]) {
                if (isset($settings[$key]) && strtolower((string) $settings[$key]) === strtolower($old_value)) {
                    $settings[$key] = $new_value;
                    $changed = true;
                }
            }

            if ($changed) {
                update_option(self::OPTION_NAME, $settings, false);
            }
        }

        return $settings;
    }

    private function migrate_and_decrypt_secrets(array $settings): array
    {
        static $migrating = false;
        $needs_migration = false;

        foreach (SecretStore::sensitive_keys() as $key) {
            if (!array_key_exists($key, $settings) || !is_string($settings[$key]) || $settings[$key] === '') {
                continue;
            }
            if (!SecretStore::is_current_encrypted($settings[$key])) {
                $needs_migration = true;
                break;
            }
        }

        // Never rewrite plaintext secrets when authenticated encryption is
        // unavailable. SecretStore::encrypt_string() intentionally returns an
        // empty string in that environment, so a read-time migration without
        // this guard would destroy the existing credentials.
        if ($needs_migration && !$migrating && SecretStore::encryption_available()) {
            $decrypted = SecretStore::decrypt_settings($settings);
            $encrypted = SecretStore::encrypt_settings($decrypted);
            $migration_is_complete = true;
            foreach (SecretStore::sensitive_keys() as $key) {
                if (!array_key_exists($key, $settings) || !is_string($settings[$key]) || $settings[$key] === '') {
                    continue;
                }
                if (empty($encrypted[$key]) || !SecretStore::is_encrypted($encrypted[$key])) {
                    $migration_is_complete = false;
                    break;
                }
            }

            if ($migration_is_complete) {
                $migrating = true;
                try {
                    update_option(self::OPTION_NAME, $encrypted, false);
                } finally {
                    $migrating = false;
                }
            }
        }

        return SecretStore::decrypt_settings($settings);
    }


    private function localize_email_template_defaults(array $merged, array $stored): array
    {
        foreach (EmailTemplateRegistry::scenarios() as $scenario_key => $scenario) {
            foreach (['subject' => 'default_subject', 'body' => 'default_body', 'html_body' => 'default_html_body'] as $suffix => $scenario_field) {
                if (!array_key_exists($scenario_field, $scenario)) {
                    continue;
                }

                $setting_key = 'email_template_' . $scenario_key . '_' . $suffix;
                $stored_value = array_key_exists($setting_key, $stored) ? (string) $stored[$setting_key] : null;
                $resolved_value = EmailTemplateRegistry::resolve_runtime_value($scenario_key, $scenario_field, $stored_value);

                // Preserve genuinely customized templates, while replacing any
                // legacy/default template (including HTML or newline variants)
                // with the default for the currently selected Email locale.
                $merged[$setting_key] = $resolved_value;
            }
        }

        return $merged;
    }

    public function update(array $settings): bool
    {
        $clean = $this->sanitize($settings);

        if (!SecretStore::encryption_available()) {
            $blocked_sensitive_keys = [];
            foreach (SecretStore::sensitive_keys() as $key) {
                if (array_key_exists($key, $clean) && is_string($clean[$key]) && $clean[$key] !== '') {
                    unset($clean[$key]);
                    $blocked_sensitive_keys[] = $key;
                }
            }

            if ($blocked_sensitive_keys !== []) {
                set_transient('sltr_secret_store_unavailable_notice', 1, 60);
            }

            $stored = get_option(self::OPTION_NAME, []);
            if (!is_array($stored)) {
                $stored = [];
            }
            $stored = $this->normalize_legacy_settings($stored);
            $merged = array_merge($stored, $clean);
        } else {
            $current = $this->all();
            $merged = array_merge($current, $clean);
        }

        $updated = update_option(self::OPTION_NAME, SecretStore::encrypt_settings($merged), false);
        self::$request_cache = [];
        if ($updated) {
            do_action('sltr_data_changed', 'settings_updated');
        }
        return $updated;
    }

    public function get_page_id(string $key): int
    {
        $page_id = max(0, (int) $this->get($key . '_page_id', 0));
        if ($this->is_published_page_for_key($page_id, $key)) {
            return $page_id;
        }

        return $this->find_page_id_by_key($key);
    }

    public function get_page_url(string $key): string
    {
        $page_id = $this->get_page_id($key);
        if ($page_id <= 0) {
            return '';
        }
        $url = get_permalink($page_id);
        return is_string($url) ? $url : '';
    }

    public function find_page_id_by_key(string $key): int
    {
        $shortcode = $this->shortcode_for_page_key($key);
        if ($shortcode === '') {
            return 0;
        }

        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            's' => '[' . $shortcode,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        foreach ((array) $pages as $candidate_id) {
            $candidate_id = (int) $candidate_id;
            if ($this->is_published_page_for_key($candidate_id, $key)) {
                return $candidate_id;
            }
        }

        return 0;
    }

    public function is_published_page_for_key(int $page_id, string $key): bool
    {
        if ($page_id <= 0) {
            return false;
        }

        $shortcode = $this->shortcode_for_page_key($key);
        if ($shortcode === '') {
            return false;
        }

        $post = get_post($page_id);
        if (!$post || $post->post_type !== 'page' || $post->post_status !== 'publish') {
            return false;
        }

        $content = (string) $post->post_content;

        // Do not depend on runtime shortcode registration here. RC65.1 skips
        // the heavy frontend shortcode bundle on ordinary wp-admin requests,
        // while Diagnostics and System Pages still need to validate the saved
        // page bindings. WordPress has_shortcode() returns false when a tag is
        // not registered, even if the shortcode is present in post_content.
        if (preg_match('/\[' . preg_quote($shortcode, '/') . '\b/i', $content) !== 1) {
            return false;
        }

        // The main booking page must contain the general [slotera_booking]
        // shortcode. Package/event landing pages use package_id and must never
        // be auto-bound as the system Booking page.
        if ($key === 'booking') {
            if (!preg_match_all('/\[slotera_booking\b([^\]]*)\]/i', $content, $matches)) {
                return false;
            }
            foreach ((array) ($matches[1] ?? []) as $attributes) {
                if (!preg_match('/\bpackage_id\s*=/i', (string) $attributes)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    private function shortcode_for_page_key(string $key): string
    {
        $map = [
            'booking' => 'slotera_booking',
            'packages' => 'slotera_packages',
            'categories' => 'slotera_categories',
            'thank_you' => 'slotera_thank_you',
            'checkout' => 'slotera_checkout',
            'login' => 'slotera_login',
            'account' => 'slotera_account',
        ];

        return (string) ($map[$key] ?? '');
    }

    public function defaults(): array
    {
        $defaults = [
            'booking_availability_status' => 'available',
            'business_closures' => [],
            'booking_page_id' => 0,
            'packages_page_id' => 0,
            'categories_page_id' => 0,
            'thank_you_page_id' => 0,
            'checkout_page_id' => 0,
            'login_page_id' => 0,
            'account_page_id' => 0,

            'package_columns_desktop' => 3,
            'package_columns_tablet' => 2,
            'package_columns_mobile' => 1,
            'booking_form_width_mode' => '1280',
            'booking_form_custom_width' => 1280,
            'show_duration_on_cards' => 0,
            'show_slotera_page_titles' => 0,
            'select_time_layout' => 'grid',

            'social_share_enabled' => 1,
            'social_share_networks' => 'facebook,x,whatsapp,telegram,linkedin,line,kakaotalk,viber,copy',
            'social_login_google_enabled' => 0,
            'social_login_google_client_id' => '',
            'social_login_google_client_secret' => '',
            'social_login_facebook_enabled' => 0,
            'social_login_facebook_client_id' => '',
            'social_login_facebook_client_secret' => '',
            'social_login_apple_enabled' => 0,
            'social_login_apple_client_id' => '',
            'social_login_apple_team_id' => '',
            'social_login_apple_key_id' => '',
            'social_login_apple_private_key' => '',

            'appearance_theme' => 'light',
            'form_background_color' => '#ffffff',
            'form_text_color' => '#0f172a',
            'card_background_color' => '#ffffff',
            'card_border_color' => '#dbe3ef',
            'primary_color' => '#2563eb',
            'primary_text_color' => '#ffffff',
            'muted_text_color' => '#64748b',
            'price_old_color' => '#94a3b8',
            'price_new_color' => '#0f172a',
            'discount_badge_background_color' => '#dc2626',
            'discount_badge_text_color' => '#ffffff',
            'tooltip_icon_color' => '#2563eb',
            'tooltip_background_color' => '#0f172a',
            'tooltip_text_color' => '#ffffff',
            'tooltip_size_ratio' => 1.15,
            'tooltip_text_size' => 13,
            'calendar_background_color' => '#f8fafc',
            'calendar_text_color' => '#0f172a',
            'calendar_border_color' => '#dbe3ef',
            'calendar_day_background_color' => '#ffffff',
            'calendar_disabled_background_color' => '#f1f5f9',
            'calendar_disabled_text_color' => '#94a3b8',
            'price_old_style' => 'line-through',
            'price_old_size_ratio' => 0.85,

            'real_server_cron_configured' => 0,

            'security_honeypot_enabled' => 1,
            'security_public_rest_booking_enabled' => 0,
            'security_public_rest_booking_security_reviewed' => 0,
            'security_public_rest_booking_auth_mode' => 'site_form',
            'security_public_rest_booking_api_key' => '',
            'security_public_rest_booking_hmac_secret' => '',
            'security_min_submit_seconds' => 4,
            'security_rate_limit_ip_enabled' => 1,
            'security_rate_limit_ip_attempts' => 30,
            'security_availability_rate_limit_enabled' => 1,
            'security_availability_rate_limit_attempts' => 120,
            'security_rate_limit_email_enabled' => 1,
            'security_rate_limit_email_attempts' => 10,
            'security_rate_limit_window_minutes' => 15,
            'security_trusted_ips' => '',
            'security_trusted_proxies' => '',
            'security_captcha_provider' => 'none',
            'security_turnstile_site_key' => '',
            'security_turnstile_secret_key' => '',
            'security_recaptcha_site_key' => '',
            'security_recaptcha_secret_key' => '',
            'security_recaptcha_v3_threshold' => '0.5',

            'booking_form_name_enabled' => 1,
            'booking_form_name_required' => 1,
            'booking_form_email_enabled' => 1,
            'booking_form_email_required' => 1,
            'booking_form_phone_enabled' => 1,
            'booking_form_phone_required' => 0,
            'booking_form_city_enabled' => 0,
            'booking_form_city_required' => 0,
            'booking_form_state_enabled' => 0,
            'booking_form_state_required' => 0,
            'booking_form_address_enabled' => 0,
            'booking_form_address_required' => 0,
            'booking_form_company_enabled' => 0,
            'booking_form_company_required' => 0,
            'booking_form_notes_enabled' => 1,
            'booking_form_notes_required' => 0,

            'payment_currency' => 'EUR',
            'payment_currency_position' => 'right_space',
            'payment_decimal_separator' => '.',
            'payment_thousands_separator' => ' ',
            'payment_tax_enabled' => 0,
            'payment_tax_label' => 'VAT',
            'payment_tax_rate' => 0,
            'payment_tax_mode' => 'exclusive',
            'payment_checkout_mode' => 'mixed',
            'payment_pay_on_arrival_enabled' => 1,
            'payment_mode_enabled' => 0,
            'prepayment_mode_enabled' => 0,
            'invoice_pdf_enabled' => 1,
            'invoice_pdf_brand_name' => '',
            'invoice_pdf_footer_text' => '',
            'payment_enabled_gateways' => '',
            'payment_custom_methods' => [],
            'payment_manual_title' => 'Pay on arrival',
            'payment_manual_instructions' => 'Please pay on arrival.',
            'payment_bank_transfer_title' => 'Bank transfer',
            'payment_bank_transfer_instructions' => '',
            'payment_stripe_publishable_key' => '',
            'payment_stripe_secret_key' => '',
            'payment_stripe_test_publishable_key' => '',
            'payment_stripe_test_secret_key' => '',
            'payment_stripe_live_publishable_key' => '',
            'payment_stripe_live_secret_key' => '',
            'payment_stripe_webhook_secret' => '',
            'payment_stripe_mode' => 'test',
            'payment_stripe_title' => 'Card',
            'payment_stripe_google_pay_enabled' => 1,
            'payment_stripe_google_pay_title' => 'Google Pay',
            'payment_stripe_apple_pay_enabled' => 1,
            'payment_stripe_apple_pay_title' => 'Apple Pay',
            'payment_paypal_client_id' => '',
            'payment_paypal_client_secret' => '',
            'payment_paypal_mode' => 'sandbox',
            'payment_paypal_webhook_id' => '',
            'payment_mollie_title' => 'Mollie Checkout',
            'payment_mollie_mode' => 'test',
            'payment_mollie_test_api_key' => '',
            'payment_mollie_live_api_key' => '',
            'payment_mollie_method' => 'all',
            'payment_razorpay_key_id' => '',
            'payment_razorpay_key_secret' => '',
            'payment_razorpay_webhook_secret' => '',
            'payment_razorpay_mode' => 'test',
            'payment_alipay_app_id' => '',
            'payment_alipay_private_key' => '',
            'payment_alipay_public_key' => '',
            'payment_alipay_mode' => 'sandbox',
            'payment_pending_cleanup_minutes' => 60,

            'email_notifications_enabled' => 1,
            'email_attach_ics_invites' => 0,
            'email_retry_max_attempts' => 3,
            'marketing_emails_per_batch' => 10,
            'marketing_cron_interval' => 5,
            'marketing_max_attempts' => 3,
            'marketing_require_opt_out_check' => 1,
            'marketing_require_unsubscribe_link' => 1,
            'marketing_minimize_log_payload' => 1,
            'promotion_digest_enabled' => 0,
            'promotion_digest_frequency' => 'manual',
            'promotion_digest_subject' => 'Special offers',
            'promotion_digest_intro' => 'See our current special offers.',
            'promotion_digest_closing' => '',
            'promotion_digest_button_label' => 'Book now',
            'promotion_digest_fallback_image_id' => 0,
            'promotion_digest_test_email' => get_option('admin_email'),
            'promotion_digest_last_run' => '',
            'promotion_digest_last_result' => '',
            'comeback_automation_enabled' => 0,
            'comeback_automation_inactive_days' => 30,
            'comeback_automation_repeat_days' => 90,
            'comeback_automation_template_key' => 'marketing_promo',
            'comeback_automation_offer_discount_type' => 'percent',
            'comeback_automation_offer_discount_value' => 10,
            'comeback_automation_offer_valid_days' => 14,
            'comeback_automation_offer_package_ids' => '',
            'comeback_automation_subject_override' => '',
            'comeback_automation_headline' => 'We miss you 👋',
            'comeback_automation_message' => 'It has been a while since your last booking. Here is a special offer for your next visit.',
            'comeback_automation_submessage' => 'Use your code before it expires and book your next appointment when it suits you.',
            'comeback_automation_cta_enabled' => 1,
            'comeback_automation_cta_label' => '',
            'comeback_automation_cta_url_type' => 'booking',
            'comeback_automation_cta_custom_url' => '',
            'comeback_automation_last_run' => '',
            'after_booking_automation_enabled' => 0,
            'after_booking_automation_delay_days' => 3,
            'after_booking_automation_repeat_days' => 30,
            'after_booking_automation_template_key' => 'marketing_promo',
            'after_booking_automation_offer_discount_type' => 'percent',
            'after_booking_automation_offer_discount_value' => 10,
            'after_booking_automation_offer_valid_days' => 14,
            'after_booking_automation_offer_package_ids' => '',
            'after_booking_automation_subject_override' => '',
            'after_booking_automation_headline' => '',
            'after_booking_automation_message' => '',
            'after_booking_automation_submessage' => '',
            'after_booking_automation_cta_enabled' => 1,
            'after_booking_automation_cta_label' => '',
            'after_booking_automation_cta_url_type' => 'booking',
            'after_booking_automation_cta_custom_url' => '',
            'after_booking_automation_last_run' => '',
            'email_from_name' => get_bloginfo('name'),
            'email_from_address' => get_option('admin_email'),
            'admin_notification_email' => get_option('admin_email'),
            'smtp_enabled' => 0,
            'smtp_sender_email' => get_option('admin_email'),
            'smtp_sender_name' => get_bloginfo('name'),
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_auth' => 1,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_allow_insecure_ssl' => 0,
            'smtp_timeout' => 20,

            'privacy_remove_data_on_uninstall' => 0,
            'privacy_visitor_analytics_enabled' => 1,
            'privacy_visitor_session_analytics_enabled' => 0,
            'privacy_visitor_analytics_require_consent' => 1,
            'privacy_visitor_analytics_retention_days' => 90,
            'privacy_anonymize_completed_bookings_days' => 365,
            'privacy_activity_log_retention_days' => 365,
            'privacy_booking_history_retention_days' => 1095,
            'privacy_email_queue_retention_days' => 90,
            'privacy_marketing_log_retention_days' => 180,
            'privacy_webhook_event_retention_days' => 180,
            'privacy_outgoing_webhook_retention_days' => 180,

            'seo_wp_pages_enabled' => 0,
            'seo_sitemap_enabled' => 1,
            'seo_sitemap_include_packages' => 1,
            'seo_sitemap_include_categories' => 1,
            'seo_sitemap_include_locations' => 1,
            'seo_sitemap_include_other_pages' => 0,
            'seo_sitemap_include_posts' => 0,
            'seo_robots_smart_builder_enabled' => 1,
            'seo_robots_block_wp_search' => 1,
            'seo_robots_block_slotera_technical' => 1,
            'seo_robots_block_tracking_params' => 1,
            'seo_robots_block_attachment_pages' => 1,
            'seo_robots_add_sitemap' => 1,
            'seo_robots_custom_rules' => '',
            'seo_meta_output_mode' => 'auto',
            'seo_default_robots' => 'index,follow',
            'seo_noindex_empty_categories' => 0,
            'seo_noindex_inactive_items' => 1,
            'seo_title_format' => '{title} | {site}',
            'seo_template_package_title' => '{package_name} | {site_name}',
            'seo_template_package_description' => 'Book {package_name} online at {site_name}.',
            'seo_template_category_title' => '{category_name} | {site_name}',
            'seo_template_category_description' => 'Browse {category_name} packages and book online at {site_name}.',
            'seo_template_location_title' => 'Services in {location_name} | {site_name}',
            'seo_template_location_description' => 'Browse available packages and book services in {location_name} online at {site_name}.',
            'seo_template_local_package_title' => '{package_name} in {location_name} | {site_name}',
            'seo_template_local_package_description' => 'Book {package_name} in {location_name} online at {site_name}.',
            'seo_breadcrumbs_enabled' => 0,
            'seo_breadcrumbs_show_packages' => 0,
            'seo_breadcrumbs_show_categories' => 0,
            'seo_breadcrumbs_show_local' => 0,

            'white_label_enabled' => 0,
            'white_label_brand_name' => 'Slotera',
            'white_label_product_name' => 'Slotera Booking',
            'white_label_admin_logo_url' => '',
            'white_label_admin_footer_text' => 'Powered by Slotera',
            'white_label_plugin_description' => 'Complete WordPress booking platform with payments, packages, coupons, marketing automation, analytics and customer management.',
            'white_label_hide_vendor_branding' => 0,
        ];

        foreach (EmailTemplateRegistry::scenarios() as $key => $scenario) {
            $defaults['email_template_' . $key . '_enabled'] = 1;
            $defaults['email_template_' . $key . '_subject'] = (string) ($scenario['default_subject'] ?? '');
            $defaults['email_template_' . $key . '_body'] = (string) ($scenario['default_body'] ?? '');
            $defaults['email_template_' . $key . '_use_html'] = (int) ($scenario['default_use_html'] ?? 0);
            $defaults['email_template_' . $key . '_html_body'] = (string) ($scenario['default_html_body'] ?? '');
        }

        $defaults['customer_booking_email_enabled'] = (int) ($defaults['email_template_booking_created_customer_enabled'] ?? 1);
        $defaults['admin_booking_email_enabled'] = (int) ($defaults['email_template_booking_created_admin_enabled'] ?? 1);
        $defaults['customer_booking_subject'] = (string) ($defaults['email_template_booking_created_customer_subject'] ?? '');
        $defaults['customer_booking_body'] = (string) ($defaults['email_template_booking_created_customer_body'] ?? '');
        $defaults['admin_booking_subject'] = (string) ($defaults['email_template_booking_created_admin_subject'] ?? '');
        $defaults['admin_booking_body'] = (string) ($defaults['email_template_booking_created_admin_body'] ?? '');

        return $defaults;
    }

    private function sanitize(array $settings): array
    {
        $clean = [];

        foreach ($settings as $key => $value) {
            if ($key === 'booking_availability_status') {
                $status = sanitize_key((string) $value);
                $clean[$key] = $status === 'paused' ? 'paused' : 'available';
                continue;
            }

            if ($key === 'business_closures') {
                $clean[$key] = [];
                if (!is_array($value)) {
                    continue;
                }
                foreach (array_slice($value, 0, 200) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $start = sanitize_text_field((string) ($row['start_date'] ?? ''));
                    $end = sanitize_text_field((string) ($row['end_date'] ?? ''));
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
                        continue;
                    }
                    $reason = sanitize_key((string) ($row['reason'] ?? 'other'));
                    $clean[$key][] = [
                        'start_date' => $start,
                        'end_date' => $end < $start ? $start : $end,
                        'reason' => in_array($reason, ['holiday', 'inventory', 'maintenance', 'private_event', 'other'], true) ? $reason : 'other',
                        'note' => substr(sanitize_text_field((string) ($row['note'] ?? '')), 0, 191),
                    ];
                }
                continue;
            }

            if (in_array($key, ['booking_page_id', 'packages_page_id', 'categories_page_id', 'thank_you_page_id', 'checkout_page_id', 'login_page_id', 'account_page_id'], true)) {
                $clean[$key] = max(0, (int) $value);
                continue;
            }

            if ($key === 'package_columns_desktop') {
                $clean[$key] = max(1, min(4, (int) $value));
                continue;
            }

            if ($key === 'package_columns_tablet') {
                $clean[$key] = max(1, min(3, (int) $value));
                continue;
            }

            if ($key === 'package_columns_mobile') {
                $clean[$key] = 1;
                continue;
            }

            if ($key === 'booking_form_width_mode') {
                $mode = sanitize_key((string) $value);
                $clean[$key] = in_array($mode, ['full', '1100', '1280', 'custom'], true) ? $mode : '1280';
                continue;
            }

            if ($key === 'booking_form_custom_width') {
                $clean[$key] = max(800, min(2400, (int) $value));
                continue;
            }

            if ($key === 'show_duration_on_cards') {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if ($key === 'select_time_layout') {
                $layout = sanitize_key((string) $value);
                $clean[$key] = in_array($layout, ['list', 'grid'], true) ? $layout : 'grid';
                continue;
            }

            if (in_array($key, ['social_share_enabled', 'social_login_google_enabled', 'social_login_facebook_enabled', 'social_login_apple_enabled'], true)) {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if (in_array($key, ['social_login_google_client_id', 'social_login_google_client_secret', 'social_login_facebook_client_id', 'social_login_facebook_client_secret', 'social_login_apple_client_id', 'social_login_apple_team_id', 'social_login_apple_key_id'], true)) {
                $clean[$key] = sanitize_text_field((string) $value);
                continue;
            }

            if ($key === 'social_login_apple_private_key') {
                $clean[$key] = sanitize_textarea_field((string) $value);
                continue;
            }

            if ($key === 'social_share_networks') {
                $allowed = ['facebook', 'x', 'whatsapp', 'telegram', 'linkedin', 'line', 'kakaotalk', 'viber', 'copy'];
                $ids = is_array($value) ? $value : preg_split('/[\s,]+/', (string) $value);
                $enabled = [];
                foreach ((array) $ids as $id) {
                    $id = sanitize_key((string) $id);
                    if (in_array($id, $allowed, true)) {
                        $enabled[] = $id;
                    }
                }
                $clean[$key] = implode(',', array_values(array_unique($enabled)));
                continue;
            }

            if ($key === 'appearance_theme') {
                $theme = sanitize_key((string) $value);
                $clean[$key] = in_array($theme, ['light', 'dark', 'soft', 'minimal', 'custom'], true) ? $theme : 'light';
                continue;
            }

            if (in_array($key, [
                'form_background_color',
                'form_text_color',
                'card_background_color',
                'card_border_color',
                'primary_color',
                'primary_text_color',
                'muted_text_color',
                'price_old_color',
                'price_new_color',
                'discount_badge_background_color',
                'discount_badge_text_color',
                'tooltip_icon_color',
                'tooltip_background_color',
                'tooltip_text_color',
                'calendar_background_color',
                'calendar_text_color',
                'calendar_border_color',
                'calendar_day_background_color',
                'calendar_disabled_background_color',
                'calendar_disabled_text_color',
            ], true)) {
                $color = sanitize_hex_color((string) $value);
                if ($color !== null) {
                    $clean[$key] = $color;
                }
                continue;
            }

            if ($key === 'price_old_style') {
                $style = sanitize_key((string) $value);
                $clean[$key] = in_array($style, ['line-through', 'none'], true) ? $style : 'line-through';
                continue;
            }

            if ($key === 'price_old_size_ratio') {
                $ratio = (float) $value;
                $clean[$key] = max(0.6, min(1.2, $ratio));
                continue;
            }

            if ($key === 'tooltip_size_ratio') {
                $ratio = (float) $value;
                $clean[$key] = max(0.8, min(2.0, $ratio));
                continue;
            }

            if ($key === 'tooltip_text_size') {
                $clean[$key] = max(10, min(24, (int) $value));
                continue;
            }

            if ($key === 'real_server_cron_configured') {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if (in_array($key, ['security_honeypot_enabled', 'security_public_rest_booking_enabled', 'security_public_rest_booking_security_reviewed', 'security_rate_limit_ip_enabled', 'security_rate_limit_email_enabled', 'security_availability_rate_limit_enabled'], true)) {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }


            if ($key === 'security_public_rest_booking_auth_mode') {
                $mode = sanitize_key((string) $value);
                $clean[$key] = in_array($mode, ['site_form', 'hmac'], true) ? $mode : 'site_form';
                continue;
            }

            if ($key === 'security_public_rest_booking_api_key') {
                $clean[$key] = sanitize_text_field((string) $value);
                continue;
            }

            if (in_array($key, ['seo_wp_pages_enabled', 'seo_sitemap_enabled', 'seo_sitemap_include_packages', 'seo_sitemap_include_categories', 'seo_sitemap_include_locations', 'seo_sitemap_include_other_pages', 'seo_sitemap_include_posts', 'seo_robots_smart_builder_enabled', 'seo_robots_block_wp_search', 'seo_robots_block_slotera_technical', 'seo_robots_block_tracking_params', 'seo_robots_block_attachment_pages', 'seo_robots_add_sitemap', 'seo_noindex_empty_categories', 'seo_noindex_inactive_items', 'seo_breadcrumbs_enabled', 'seo_breadcrumbs_show_packages', 'seo_breadcrumbs_show_categories', 'seo_breadcrumbs_show_local', 'show_slotera_page_titles'], true)) {
                if ($key === 'seo_wp_pages_enabled') {
                    $external_plugins = class_exists('\Slotera\Application\Services\SEOService') ? (new \Slotera\Application\Services\SEOService())->detected_external_plugins() : [];
                    $clean[$key] = empty($external_plugins) && !empty($value) ? 1 : 0;
                    continue;
                }
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if ($key === 'seo_robots_custom_rules') {
                $clean[$key] = substr(sanitize_textarea_field((string) $value), 0, 5000);
                continue;
            }

            if ($key === 'seo_meta_output_mode') {
                $mode = sanitize_key((string) $value);
                $clean[$key] = in_array($mode, ['auto', 'force', 'disabled'], true) ? $mode : 'auto';
                continue;
            }

            if ($key === 'seo_default_robots') {
                $robots = strtolower(sanitize_text_field((string) $value));
                $clean[$key] = in_array($robots, ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'], true) ? $robots : 'index,follow';
                continue;
            }

            if ($key === 'seo_title_format') {
                $format = trim(sanitize_text_field((string) $value));
                if ($format === '') {
                    $format = '{title} | {site}';
                }
                $clean[$key] = substr($format, 0, 120);
                continue;
            }

            if (in_array($key, ['seo_template_package_title', 'seo_template_category_title', 'seo_template_location_title', 'seo_template_local_package_title'], true)) {
                $clean[$key] = substr(sanitize_text_field((string) $value), 0, 255);
                continue;
            }

            if (in_array($key, ['seo_template_package_description', 'seo_template_category_description', 'seo_template_location_description', 'seo_template_local_package_description'], true)) {
                $clean[$key] = substr(sanitize_textarea_field((string) $value), 0, 320);
                continue;
            }

            if (in_array($key, ['privacy_remove_data_on_uninstall', 'privacy_visitor_analytics_enabled', 'privacy_visitor_session_analytics_enabled', 'privacy_visitor_analytics_require_consent'], true)) {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if ($key === 'privacy_visitor_analytics_retention_days') {
                $clean[$key] = max(1, min(3650, (int) $value));
                continue;
            }

            if (in_array($key, ['privacy_anonymize_completed_bookings_days', 'privacy_activity_log_retention_days', 'privacy_booking_history_retention_days', 'privacy_email_queue_retention_days', 'privacy_marketing_log_retention_days', 'privacy_webhook_event_retention_days', 'privacy_outgoing_webhook_retention_days'], true)) {
                $clean[$key] = max(0, min(3650, (int) $value));
                continue;
            }

            if ($key === 'security_min_submit_seconds') {
                $clean[$key] = max(0, min(60, (int) $value));
                continue;
            }

            if (in_array($key, ['security_rate_limit_ip_attempts', 'security_rate_limit_email_attempts'], true)) {
                $clean[$key] = max(1, min(1000, (int) $value));
                continue;
            }

            if ($key === 'security_availability_rate_limit_attempts') {
                $clean[$key] = max(1, min(5000, (int) $value));
                continue;
            }

            if ($key === 'security_rate_limit_window_minutes') {
                $clean[$key] = max(1, min(1440, (int) $value));
                continue;
            }

            if (in_array($key, ['security_trusted_ips', 'security_trusted_proxies'], true)) {
                $lines = preg_split('/\r\n|\r|\n/', sanitize_textarea_field((string) $value));
                $valid = [];
                foreach ((array) $lines as $line) {
                    $line = trim((string) $line);
                    if ($line === '') {
                        continue;
                    }
                    if (filter_var($line, FILTER_VALIDATE_IP) || preg_match('/^([0-9a-fA-F:.]+)\/(\d{1,3})$/', $line)) {
                        $valid[] = $line;
                    }
                }
                $clean[$key] = implode("\n", array_unique($valid));
                continue;
            }

            if ($key === 'security_captcha_provider') {
                $provider = sanitize_key((string) $value);
                $clean[$key] = in_array($provider, ['none', 'turnstile', 'recaptcha', 'recaptcha_v3'], true) ? $provider : 'none';
                continue;
            }

            if (in_array($key, ['security_turnstile_site_key', 'security_turnstile_secret_key', 'security_recaptcha_site_key', 'security_recaptcha_secret_key'], true)) {
                $clean[$key] = sanitize_text_field((string) $value);
                continue;
            }

            if ($key === 'security_recaptcha_v3_threshold') {
                $threshold = (float) $value;
                $clean[$key] = (string) max(0.0, min(1.0, $threshold));
                continue;
            }

            if (preg_match('/^booking_form_(name|email|phone|city|state|address|company|notes)_(enabled|required)$/', $key)) {
                if (strpos($key, 'booking_form_email_') === 0) {
                    $clean[$key] = 1;
                } else {
                    $clean[$key] = !empty($value) ? 1 : 0;
                }
                continue;
            }

            if ($key === 'payment_checkout_mode') {
                $mode = sanitize_key((string) $value);
                $clean[$key] = in_array($mode, ['booking_only', 'payment_required', 'mixed'], true) ? $mode : 'mixed';
                continue;
            }

            if (in_array($key, ['payment_pay_on_arrival_enabled', 'payment_mode_enabled', 'prepayment_mode_enabled', 'invoice_pdf_enabled', 'payment_tax_enabled'], true)) {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if ($key === 'invoice_pdf_brand_name') {
                $clean[$key] = substr(sanitize_text_field((string) $value), 0, 120);
                continue;
            }

            if ($key === 'invoice_pdf_footer_text') {
                $clean[$key] = substr(sanitize_text_field((string) $value), 0, 240);
                continue;
            }

            if ($key === 'payment_currency') {
                $clean[$key] = \Slotera\Application\Services\CurrencyService::normalize((string) $value);
                continue;
            }

            if ($key === 'payment_currency_position') {
                $clean[$key] = \Slotera\Application\Services\CurrencyService::normalize_position((string) $value);
                continue;
            }

            if ($key === 'payment_decimal_separator') {
                $clean[$key] = \Slotera\Application\Services\CurrencyService::normalize_separator((string) $value, '.');
                continue;
            }

            if ($key === 'payment_thousands_separator') {
                $clean[$key] = \Slotera\Application\Services\CurrencyService::normalize_separator((string) $value, ' ');
                continue;
            }

            if ($key === 'payment_tax_rate') {
                $clean[$key] = max(0, min(100, (float) $value));
                continue;
            }

            if ($key === 'payment_tax_mode') {
                $mode = sanitize_key((string) $value);
                $clean[$key] = in_array($mode, ['exclusive', 'inclusive'], true) ? $mode : 'exclusive';
                continue;
            }

            if ($key === 'payment_enabled_gateways') {
                $allowed = ['manual', 'bank_transfer', 'stripe', 'apple_pay', 'google_pay', 'paypal', 'mollie'];
                $ids = is_array($value) ? $value : preg_split('/[\s,]+/', (string) $value);
                $enabled = [];
                foreach ((array) $ids as $id) {
                    $id = sanitize_key((string) $id);
                    if (in_array($id, $allowed, true) || preg_match('/^custom_[a-z0-9_]+$/', $id)) { $enabled[] = $id; }
                }
                $clean[$key] = implode(',', array_values(array_unique($enabled)));
                continue;
            }


            if ($key === 'payment_custom_methods') {
                $methods = [];
                foreach ((array) $value as $method) {
                    if (!is_array($method)) { continue; }
                    $raw_id = sanitize_key((string) ($method['id'] ?? ''));
                    $title = sanitize_text_field((string) ($method['title'] ?? ''));
                    if ($title === '') { continue; }
                    $id = $raw_id !== '' && strpos($raw_id, 'custom_') === 0 ? $raw_id : 'custom_' . substr(md5($title . wp_json_encode($method)), 0, 10);
                    $methods[] = [
                        'id' => $id,
                        'enabled' => !empty($method['enabled']) ? 1 : 0,
                        'title' => $title,
                        'url' => esc_url_raw((string) ($method['url'] ?? '')),
                        'instructions' => sanitize_textarea_field((string) ($method['instructions'] ?? '')),
                        'after_booking_status' => 'confirmed',
                    ];
                }
                $clean[$key] = array_values($methods);
                continue;
            }

            if (in_array($key, ['payment_paypal_mode', 'payment_stripe_mode', 'payment_mollie_mode'], true)) {
                $mode = sanitize_key((string) $value);
                $clean[$key] = in_array($mode, ['sandbox', 'test', 'live'], true) ? $mode : ($key === 'payment_paypal_mode' ? 'sandbox' : 'test');
                continue;
            }
            if ($key === 'payment_mollie_method') {
                $method = sanitize_key((string) $value);
                $allowed_methods = ['all', 'ideal', 'bancontact', 'creditcard', 'paypal', 'banktransfer', 'directdebit', 'sofort', 'eps', 'p24', 'klarna'];
                $clean[$key] = in_array($method, $allowed_methods, true) ? $method : 'all';
                continue;
            }
            if (preg_match('/^payment_/', $key)) {
                if (SecretStore::is_sensitive_key($key)) {
                    $clean[$key] = in_array($key, ['payment_alipay_private_key'], true)
                        ? (string) preg_replace('/[\x00]/', '', (string) $value)
                        : (string) preg_replace('/[\x00\r\n]/', '', (string) $value);
                    continue;
                }
                $clean[$key] = in_array($key, ['payment_manual_instructions', 'payment_bank_transfer_instructions'], true)
                    ? sanitize_textarea_field((string) $value)
                    : sanitize_text_field((string) $value);
                continue;
            }

            if ($key === 'email_retry_max_attempts') {
                $clean[$key] = max(1, min(10, (int) $value));
                continue;
            }

            if ($key === 'marketing_emails_per_batch') {
                $clean[$key] = max(1, min(50, (int) $value));
                continue;
            }

            if ($key === 'marketing_cron_interval') {
                $interval = (int) $value;
                $clean[$key] = in_array($interval, [1, 5, 10, 15], true) ? $interval : 5;
                continue;
            }

            if ($key === 'marketing_max_attempts') {
                $clean[$key] = max(1, min(10, (int) $value));
                continue;
            }

            if (in_array($key, ['marketing_require_opt_out_check', 'marketing_require_unsubscribe_link', 'marketing_minimize_log_payload'], true)) {
                $clean[$key] = 1;
                continue;
            }

            if ($key === 'promotion_digest_enabled') { $clean[$key] = !empty($value) ? 1 : 0; continue; }
            if ($key === 'promotion_digest_frequency') { $v = sanitize_key((string) $value); $clean[$key] = in_array($v, ['manual','weekly','biweekly','monthly'], true) ? $v : 'manual'; continue; }
            if ($key === 'promotion_digest_fallback_image_id') { $clean[$key] = max(0, (int) $value); continue; }
            if ($key === 'promotion_digest_test_email') { $clean[$key] = sanitize_email((string) $value); continue; }
            if (in_array($key, ['promotion_digest_intro','promotion_digest_closing'], true)) { $clean[$key] = wp_kses_post((string) $value); continue; }
            if (in_array($key, ['promotion_digest_subject','promotion_digest_button_label','promotion_digest_last_run','promotion_digest_last_result'], true)) { $clean[$key] = sanitize_text_field((string) $value); continue; }


            if (in_array($key, ['comeback_automation_enabled', 'comeback_automation_generate_unique_coupons', 'comeback_automation_cta_enabled', 'after_booking_automation_enabled', 'after_booking_automation_generate_unique_coupons', 'after_booking_automation_cta_enabled'], true)) {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if (in_array($key, ['comeback_automation_inactive_days', 'comeback_automation_repeat_days', 'after_booking_automation_repeat_days'], true)) {
                $clean[$key] = max(1, min(3650, (int) $value));
                continue;
            }

            if ($key === 'after_booking_automation_delay_days') {
                $clean[$key] = max(0, min(3650, (int) $value));
                continue;
            }

            if (in_array($key, ['comeback_automation_coupon_id', 'after_booking_automation_coupon_id'], true)) {
                $clean[$key] = max(0, (int) $value);
                continue;
            }

            if (in_array($key, ['comeback_automation_template_key', 'after_booking_automation_template_key'], true)) {
                $clean[$key] = sanitize_key((string) $value) ?: 'marketing_promo';
                continue;
            }

            if (in_array($key, ['comeback_automation_offer_discount_type', 'after_booking_automation_offer_discount_type'], true)) {
                $type = sanitize_key((string) $value);
                $clean[$key] = in_array($type, ['percent', 'fixed'], true) ? $type : 'percent';
                continue;
            }

            if (in_array($key, ['comeback_automation_offer_discount_value', 'after_booking_automation_offer_discount_value'], true)) {
                $clean[$key] = max(0, (float) $value);
                continue;
            }

            if (in_array($key, ['comeback_automation_offer_valid_days', 'after_booking_automation_offer_valid_days'], true)) {
                $clean[$key] = max(1, min(3650, (int) $value));
                continue;
            }

            if (in_array($key, ['comeback_automation_offer_package_ids', 'after_booking_automation_offer_package_ids'], true)) {
                $ids = array_values(array_unique(array_filter(array_map('absint', preg_split('/[\s,]+/', (string) $value) ?: []))));
                $clean[$key] = implode(',', $ids);
                continue;
            }

            if (in_array($key, ['comeback_automation_subject_override', 'comeback_automation_headline', 'comeback_automation_cta_label', 'comeback_automation_last_run', 'after_booking_automation_subject_override', 'after_booking_automation_headline', 'after_booking_automation_cta_label', 'after_booking_automation_last_run'], true)) {
                $clean[$key] = sanitize_text_field((string) $value);
                continue;
            }

            if (in_array($key, ['comeback_automation_message', 'comeback_automation_submessage', 'after_booking_automation_message', 'after_booking_automation_submessage'], true)) {
                $clean[$key] = wp_kses_post((string) $value);
                continue;
            }

            if (in_array($key, ['comeback_automation_cta_url_type', 'after_booking_automation_cta_url_type'], true)) {
                $type = sanitize_key((string) $value);
                $clean[$key] = in_array($type, ['booking', 'package', 'custom'], true) ? $type : 'booking';
                continue;
            }

            if (in_array($key, ['comeback_automation_cta_custom_url', 'after_booking_automation_cta_custom_url'], true)) {
                $clean[$key] = esc_url_raw((string) $value);
                continue;
            }

            if (in_array($key, ['email_notifications_enabled', 'email_attach_ics_invites', 'smtp_enabled', 'smtp_auth', 'smtp_allow_insecure_ssl'], true)) {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if ($key === 'smtp_port') {
                $clean[$key] = max(1, min(65535, (int) $value));
                continue;
            }

            if ($key === 'smtp_timeout') {
                $clean[$key] = max(5, min(120, (int) $value));
                continue;
            }

            if ($key === 'smtp_encryption') {
                $encryption = sanitize_key((string) $value);
                $clean[$key] = in_array($encryption, ['none', 'tls', 'ssl'], true) ? $encryption : 'tls';
                continue;
            }

            if ($key === 'smtp_password') {
                // Preserve SMTP/app passwords exactly; sanitizing can break symbols.
                $clean[$key] = (string) preg_replace('/[\x00\r\n]/', '', (string) $value);
                continue;
            }

            if (in_array($key, ['smtp_host', 'smtp_username', 'smtp_sender_name'], true)) {
                $clean[$key] = sanitize_text_field((string) $value);
                continue;
            }

            if ($key === 'smtp_sender_email') {
                $email = sanitize_email((string) $value);
                if ($email !== '' && is_email($email)) {
                    $clean[$key] = $email;
                }
                continue;
            }

            if (in_array($key, ['email_from_name'], true)) {
                $clean[$key] = sanitize_text_field((string) $value);
                continue;
            }

            if (in_array($key, ['email_from_address', 'admin_notification_email'], true)) {
                $email = sanitize_email((string) $value);
                if ($email !== '' && is_email($email)) {
                    $clean[$key] = $email;
                }
                continue;
            }

            if (in_array($key, ['white_label_enabled', 'white_label_hide_vendor_branding'], true)) {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if (in_array($key, ['white_label_brand_name', 'white_label_product_name', 'white_label_admin_footer_text'], true)) {
                $clean[$key] = substr(sanitize_text_field((string) $value), 0, 120);
                continue;
            }

            if ($key === 'white_label_admin_logo_url') {
                $clean[$key] = esc_url_raw((string) $value);
                continue;
            }

            if ($key === 'white_label_plugin_description') {
                $clean[$key] = substr(sanitize_textarea_field((string) $value), 0, 300);
                continue;
            }

            if (preg_match('/^email_template_[a-z0-9_]+_enabled$/', $key)) {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if (preg_match('/^email_template_[a-z0-9_]+_subject$/', $key)) {
                $clean[$key] = EmailTemplateRegistry::sanitize_template_placeholders(sanitize_text_field((string) $value));
                continue;
            }

            if (preg_match('/^email_template_[a-z0-9_]+_use_html$/', $key)) {
                $clean[$key] = !empty($value) ? 1 : 0;
                continue;
            }

            if (preg_match('/^email_template_[a-z0-9_]+_body$/', $key)) {
                $clean[$key] = EmailTemplateRegistry::sanitize_template_placeholders(wp_kses_post((string) $value));
                continue;
            }

            if (preg_match('/^email_template_[a-z0-9_]+_html_body$/', $key)) {
                $clean[$key] = EmailTemplateRegistry::sanitize_template_placeholders(wp_kses_post((string) $value));
                continue;
            }

            switch ($key) {
                case 'customer_booking_email_enabled':
                    $clean['email_template_booking_created_customer_enabled'] = !empty($value) ? 1 : 0;
                    break;
                case 'admin_booking_email_enabled':
                    $clean['email_template_booking_created_admin_enabled'] = !empty($value) ? 1 : 0;
                    break;
                case 'customer_booking_subject':
                    $clean['email_template_booking_created_customer_subject'] = EmailTemplateRegistry::sanitize_template_placeholders(sanitize_text_field((string) $value));
                    break;
                case 'admin_booking_subject':
                    $clean['email_template_booking_created_admin_subject'] = EmailTemplateRegistry::sanitize_template_placeholders(sanitize_text_field((string) $value));
                    break;
                case 'customer_booking_body':
                    $clean['email_template_booking_created_customer_body'] = EmailTemplateRegistry::sanitize_template_placeholders(wp_kses_post((string) $value));
                    break;
                case 'admin_booking_body':
                    $clean['email_template_booking_created_admin_body'] = EmailTemplateRegistry::sanitize_template_placeholders(wp_kses_post((string) $value));
                    break;
            }
        }

        return $clean;
    }
}
