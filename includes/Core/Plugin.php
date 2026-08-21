<?php

declare(strict_types=1);

namespace Slotera\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    public function run(): void
    {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_filter('gettext', [$this, 'translate_slotera_text'], 20, 3);
        add_action('plugins_loaded', [$this, 'maybe_run_migrations']);
        add_action('admin_init', ['Slotera\\Core\\Capabilities', 'install']);
        add_action('init', [$this, 'register_image_sizes']);
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_notices', [$this, 'print_license_admin_notice']);
        add_action('admin_notices', [$this, 'print_secret_store_admin_notice']);
        Migrator::register_hooks();
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'slotera-booking',
            false,
            dirname(SLTR_PLUGIN_BASENAME) . '/languages'
        );
    }

    public function maybe_run_migrations(): void
    {
        $db_version = (string) get_option(Migrator::DB_VERSION_OPTION, '0.0.0');

        if (version_compare($db_version, SLTR_VERSION, '<')) {
            Migrator::migrate();
        }
    }


    public function register_image_sizes(): void
    {
        add_image_size('slotera_slider', 1400, 788, true);
        add_image_size('slotera_gallery', 900, 600, true);
        add_image_size('slotera_card', 600, 400, true);
    }

    public function init(): void
    {
        $profile_token = \Slotera\Application\Services\PerformanceProfiler::start();

        $this->register_component('license', static function (): void {
            (new \Slotera\Application\Services\LicenseService())->ensure_initialized();
        });
        $this->register_component('translation_maintenance', function (): void {
            $this->maybe_run_translation_maintenance();
        });
        $this->register_component('activity_log', static function (): void {
            (new \Slotera\Application\Services\ActivityLogService())->register_hooks();
        });
        $this->register_component('observability', static function (): void {
            (new \Slotera\Application\Services\ObservabilityLogger())->register_hooks();
        });
        $this->register_component('cron_resilience', static function (): void {
            (new \Slotera\Application\Services\CronResilienceService())->register_hooks();
        });
        $this->register_component('email_notifications', static function (): void {
            (new \Slotera\Application\Services\EmailNotificationService())->register_hooks();
        });
        $this->register_component('secure_attachments', static function (): void {
            (new \Slotera\Application\Services\SecureAttachmentFileService())->register_hooks();
        });
        $this->register_component('account_magic_link', static function (): void {
            (new \Slotera\Application\Services\AccountMagicLinkService())->register_hooks();
        });
        $this->register_component('paypal_gateway', static function (): void {
            (new \Slotera\Application\Services\PayPalGatewayService())->register_hooks();
        });
        $this->register_component('smtp_mailer', static function (): void {
            (new \Slotera\Application\Services\SmtpMailerService())->register_hooks();
        });
        $this->register_component('email_reminders', static function (): void {
            (new \Slotera\Application\Services\EmailReminderService())->register_hooks();
        });
        $this->register_component('marketing_email', static function (): void {
            \Slotera\Application\Services\MarketingEmailService::register_lazy_queue_hooks();
        });
        $this->register_component('marketing_opt_out', static function (): void {
            (new \Slotera\Application\Services\MarketingOptOutService())->register_hooks();
        });
        $this->register_component('marketing_automation', static function (): void {
            (new \Slotera\Application\Services\MarketingAutomationService())->register_hooks();
        });
        $this->register_component('promotion_digest', static function (): void {
            (new \Slotera\Application\Services\PromotionCampaignService())->register_hooks();
        });
        $this->register_component('privacy', static function (): void {
            (new \Slotera\Application\Services\PrivacyService())->register_hooks();
        });
        $this->register_component('cache_invalidation', static function (): void {
            (new \Slotera\Application\Services\CacheInvalidationService())->register_hooks();
        });
        $this->register_component('local_routes', static function (): void {
            (new \Slotera\Application\Services\LocalRouteService())->register_hooks();
        });
        $this->register_component('seo', static function (): void {
            (new \Slotera\Application\Services\SEOService())->register_hooks();
        });
        $this->register_component('sitemap', static function (): void {
            (new \Slotera\Application\Services\SitemapService())->register_hooks();
        });
        $this->register_component('redirects', static function (): void {
            (new \Slotera\Application\Services\RedirectService())->register_hooks();
        });
        $this->register_component('white_label', static function (): void {
            (new \Slotera\Application\Services\WhiteLabelService())->register_hooks();
        });
        $this->register_component('cron_schedule_registry', static function (): void {
            \Slotera\Application\Services\CronScheduleRegistry::maybe_ensure();
        });

        $this->register_component('admin_provider', static function (): void {
            (new \Slotera\Admin\AdminServiceProvider())->register();
        }, $this->should_register_admin_components());

        $register_frontend = $this->should_register_frontend_components();
        $admin_post_action = $this->current_frontend_admin_post_action();
        $register_frontend_booking_shortcode = $register_frontend || $admin_post_action === 'sltr_contact_form_submit';
        $register_frontend_account = $register_frontend || in_array($admin_post_action, [
            'sltr_request_magic_link',
            'sltr_consume_magic_link',
            'sltr_account_logout',
            'sltr_account_cancel_booking',
            'sltr_account_reschedule_booking',
            'sltr_account_invoice_pdf',
        ], true);

        $this->register_component('frontend_booking_shortcode', static function (): void {
            if (class_exists('Slotera\Frontend\Shortcodes\BookingShortcode')) {
                (new \Slotera\Frontend\Shortcodes\BookingShortcode())->register();
            }
        }, $register_frontend_booking_shortcode);
        $this->register_component('frontend_availability', static function (): void {
            if (class_exists('Slotera\Frontend\Controllers\AvailabilityController')) {
                (new \Slotera\Frontend\Controllers\AvailabilityController())->register();
            }
        }, $register_frontend);
        $this->register_component('frontend_booking', static function (): void {
            if (class_exists('Slotera\Frontend\Controllers\BookingController')) {
                (new \Slotera\Frontend\Controllers\BookingController())->register();
            }
        }, $register_frontend);
        $this->register_component('frontend_booking_access', static function (): void {
            if (class_exists('Slotera\Frontend\Controllers\BookingAccessController')) {
                (new \Slotera\Frontend\Controllers\BookingAccessController())->register();
            }
        }, $register_frontend);
        $this->register_component('frontend_account', static function (): void {
            if (class_exists('Slotera\Frontend\Controllers\AccountController')) {
                (new \Slotera\Frontend\Controllers\AccountController())->register();
            }
        }, $register_frontend_account);
        $this->register_component('frontend_rest_api', static function (): void {
            if (class_exists('Slotera\Frontend\Controllers\RestApiController')) {
                (new \Slotera\Frontend\Controllers\RestApiController())->register();
            }
        }, $register_frontend);
        $this->register_component('frontend_analytics', static function (): void {
            if (class_exists('Slotera\Frontend\Controllers\VisitorAnalyticsController')) {
                (new \Slotera\Frontend\Controllers\VisitorAnalyticsController())->register();
            }
        }, $register_frontend);

        do_action('sltr_init');
        \Slotera\Application\Services\PerformanceProfiler::finish('core.plugin.init', $profile_token);
        \Slotera\Application\Services\PerformanceProfiler::register_baseline_capture();
    }

    /**
     * Register one bootstrap component while recording request-local profiling
     * data. The wrapper is intentionally a no-op when profiling is disabled.
     */
    private function register_component(string $name, callable $callback, bool $enabled = true): void
    {
        if (!$enabled) {
            \Slotera\Application\Services\PerformanceProfiler::metric('services_skipped');
            \Slotera\Application\Services\PerformanceProfiler::metric('service_' . $name . '_skipped');
            return;
        }

        \Slotera\Application\Services\PerformanceProfiler::metric('services_initialized');
        \Slotera\Application\Services\PerformanceProfiler::metric('service_' . $name . '_initialized');
        \Slotera\Application\Services\PerformanceProfiler::time('service.register.' . $name, $callback);
    }

    private function should_register_admin_components(): bool
    {
        return function_exists('is_admin') && is_admin();
    }

    private function current_frontend_admin_post_action(): string
    {
        if (!(function_exists('is_admin') && is_admin())) {
            return '';
        }

        $script = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
        $pagenow = isset($GLOBALS['pagenow']) ? (string) $GLOBALS['pagenow'] : '';
        if ($script !== 'admin-post.php' && $pagenow !== 'admin-post.php') {
            return '';
        }

        $action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
        if (function_exists('wp_unslash')) {
            $action = (string) wp_unslash($action);
        }
        if (function_exists('sanitize_key')) {
            $action = sanitize_key($action);
        } else {
            $action = strtolower((string) preg_replace('/[^a-zA-Z0-9_\-]/', '', $action));
        }

        static $allowed = [
            'sltr_request_magic_link',
            'sltr_consume_magic_link',
            'sltr_account_logout',
            'sltr_account_cancel_booking',
            'sltr_account_reschedule_booking',
            'sltr_account_invoice_pdf',
            'sltr_contact_form_submit',
        ];

        return in_array($action, $allowed, true) ? $action : '';
    }

    private function should_register_frontend_components(): bool
    {
        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return false;
        }

        if (function_exists('is_admin') && is_admin()) {
            return function_exists('wp_doing_ajax') && wp_doing_ajax();
        }

        return true;
    }





    private function maybe_run_translation_maintenance(): void
    {
        $locale = function_exists('determine_locale') ? (string) determine_locale() : (string) get_locale();
        $marker = (defined('SLTR_VERSION') ? SLTR_VERSION : '0.0.0') . '|' . $locale;
        $option_name = 'sltr_translation_maintenance_marker';

        if ((string) get_option($option_name, '') === $marker) {
            return;
        }

        try {
            $translation_service = new \Slotera\Application\Services\TranslationService();
            $translation_service->sync_context_locales_from_wordpress(false);
            $translation_service->cleanup_admin_email_builtin_i18n();
            $translation_service->cleanup_frontend_locale_overrides(['ru_RU', 'ru', 'bg_BG', 'mt_MT']);
            update_option($option_name, $marker, false);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Slotera translation maintenance failed: ' . $e->getMessage());
            }
        }
    }

    public function translate_slotera_text(string $translation, string $text, string $domain): string
    {
        if ($domain !== 'slotera-booking' || $text === '') {
            return $translation;
        }

        if ((is_admin() && !$this->is_slotera_admin_context()) || (!is_admin() && !$this->is_slotera_frontend_context())) {
            return $translation;
        }

        /**
         * The gettext filter can run hundreds or thousands of times in a single
         * admin/frontend request. Keep the TranslationService, resolved context
         * locales, and individual string lookups in request-local static caches
         * so the registry/options are not reloaded for every gettext call.
         */
        static $service = null;
        static $locales = [];
        static $translations = [];

        if ($service === null) {
            $service = new \Slotera\Application\Services\TranslationService();
        }

        if (is_admin()) {
            // Admin English Waves 1-4: these Slotera admin surfaces always render
            // their original English source strings, regardless of the WordPress
            // user locale. Protected email screens are deliberately excluded.
            if ($this->is_admin_english_context()) {
                return $text;
            }

            // Email settings/templates are a protected localized admin surface.
            // Resolve their namespace before the English-only Admin UI catalog.
            $groups = $this->is_slotera_email_admin_context() ? ['emails', 'admin'] : ['admin'];
            foreach ($groups as $group) {
                if (!isset($locales[$group])) {
                    $locales[$group] = $service->locale_for_group($group);
                }

                $cache_key = $group . '|' . $locales[$group] . '|' . $text;
                if (!array_key_exists($cache_key, $translations)) {
                    $translations[$cache_key] = $service->translate_default($text, $group, $locales[$group]);
                }

                if ($translations[$cache_key] !== null) {
                    return (string) $translations[$cache_key];
                }
            }

            // Fall back to WordPress gettext instead of the raw English source.
            return $translation;
        }

        if (!isset($locales['frontend'])) {
            $locales['frontend'] = $service->locale_for_group('frontend');
        }

        $cache_key = 'frontend|' . $locales['frontend'] . '|' . $text;
        if (!array_key_exists($cache_key, $translations)) {
            $translations[$cache_key] = $service->translate_default($text, 'frontend', $locales['frontend']);
        }

        return $translations[$cache_key] !== null ? (string) $translations[$cache_key] : $translation;
    }


    public function print_secret_store_admin_notice(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_SETTINGS)) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id = $screen ? (string) $screen->id : '';
        if (strpos($screen_id, 'slotera') === false) {
            return;
        }

        if (!class_exists('Slotera\\Application\\Security\\SecretStore') || \Slotera\Application\Security\SecretStore::encryption_available()) {
            return;
        }

        $attempted_save = (bool) get_transient('sltr_secret_store_unavailable_notice');
        if ($attempted_save) {
            delete_transient('sltr_secret_store_unavailable_notice');
        }

        $message = $attempted_save
            ? __('Encryption is unavailable on this server. Sensitive settings were not saved, and existing secrets were preserved. Enable the PHP Sodium extension and save again.', 'slotera-booking')
            : __('Encryption is unavailable on this server. Sensitive settings cannot be saved until the PHP Sodium extension is enabled.', 'slotera-booking');

        echo '<div class="notice notice-error"><p><strong>' . esc_html__('Slotera secure settings', 'slotera-booking') . '</strong> ' . esc_html($message) . '</p></div>';
    }

    public function print_license_admin_notice(): void
    {
        $license = new \Slotera\Application\Services\LicenseService();
        if ($license->has_built_in_license()) {
            return;
        }
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_SETTINGS)) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id = $screen ? (string) $screen->id : '';
        if (strpos($screen_id, 'slotera') === false) {
            return;
        }
        $status = $license->status();
        if (($status['state'] ?? '') === 'active') {
            return;
        }
        $class = ($status['state'] ?? '') === 'trial' ? 'notice-info' : 'notice-warning';
        echo '<div class="notice ' . esc_attr($class) . '"><p>' . esc_html($license->trial_message()) . ' <a href="' . esc_url(admin_url('admin.php?page=slotera-license')) . '">' . esc_html__('License settings', 'slotera-booking') . '</a></p></div>';
    }

    private function is_admin_english_context(): bool
    {
        if (!is_admin() || $this->is_slotera_email_admin_context()) {
            return false;
        }

        $page = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : '';
        $english_pages = [
            'slotera-security',
            'slotera-diagnostics',
            'slotera-tools',
            'slotera-logs',
            'slotera-categories',
            'slotera-locations',
            'slotera-coupons',
            'slotera-events',
            'slotera-packages',
            'slotera-bookings',
            'slotera-customers',
            'slotera-settings',
            'slotera-booking-form',
            'slotera-seo',
            'slotera-analytics',
            'slotera-marketing',
            'slotera-shared-network',
            'slotera-pro',
            'slotera',
            'slotera-translations',
            'slotera-payments',
            'slotera-payment-transactions',
            'slotera-payment-invoices',
            'slotera-white-label',
            'slotera-license',
        ];

        if (in_array($page, $english_pages, true)) {
            return true;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id = $screen ? (string) $screen->id : '';
        foreach ($english_pages as $english_page) {
            if ($screen_id !== '' && strpos($screen_id, $english_page) !== false) {
                return true;
            }
        }

        return false;
    }

    private function is_slotera_email_admin_context(): bool
    {
        if (!is_admin()) {
            return false;
        }

        $page = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : '';
        if ($page === 'slotera-emails') {
            return true;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id = $screen ? (string) $screen->id : '';
        return strpos($screen_id, 'slotera-emails') !== false;
    }

    private function is_slotera_admin_context(): bool
    {
        if (!is_admin()) {
            return false;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id = $screen ? (string) $screen->id : '';

        return strpos($screen_id, 'slotera') !== false;
    }

    private function is_slotera_frontend_context(): bool
    {
        if (is_admin()) {
            return false;
        }

        if (class_exists('Slotera\\Application\\Services\\LocalRouteService') && \Slotera\Application\Services\LocalRouteService::is_current_route()) {
            return true;
        }

        if (!is_singular()) {
            return false;
        }

        $post = get_post();
        if (!$post || empty($post->post_content)) {
            return false;
        }

        $shortcodes = [
            'slotera_booking',
            'slotera_category',
            'slotera_categories',
            'slotera_packages',
            'slotera_thank_you',
            'slotera_checkout',
            'slotera_contact',
            'slotera_login',
            'slotera_account',
            'slotera_package_slider',
            'slotera_package_media',
            'slotera_package_image',
            'slotera_package_text_block',
        ];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        $settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
        $configured_page_ids = [
            (int) ($settings['booking_page_id'] ?? 0),
            (int) ($settings['packages_page_id'] ?? 0),
            (int) ($settings['categories_page_id'] ?? 0),
            (int) ($settings['thank_you_page_id'] ?? 0),
            (int) ($settings['checkout_page_id'] ?? 0),
            (int) ($settings['login_page_id'] ?? 0),
            (int) ($settings['account_page_id'] ?? 0),
        ];

        return in_array((int) $post->ID, array_filter($configured_page_ids), true);
    }

    private function frontend_no_hover_overrides_css(): string
    {
        return <<<'CSS'
.sltr-package:hover,
.sltr-package:focus,
.sltr-package:active,
.sltr-package-card:hover,
.sltr-package-card:focus,
.sltr-package-card:active,
.sltr-packages-list .sltr-package-card:hover,
.sltr-packages-list .sltr-package-card:focus,
.sltr-packages-list .sltr-package-card:active {
    background: var(--sltr-card-bg, #fff) !important;
    background-color: var(--sltr-card-bg, #fff) !important;
    border-color: var(--sltr-card-border, #dbe3ef) !important;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06) !important;
    color: var(--sltr-form-text, #0f172a) !important;
    filter: none !important;
    outline: none !important;
    transform: none !important;
    text-decoration: none !important;
}
.sltr-package:hover .sltr-package-meta,
.sltr-package-card:hover .sltr-package-meta { color: var(--sltr-muted, #64748b) !important; }
.sltr-package:hover .sltr-package-price b,
.sltr-package-card:hover .sltr-package-price b { color: var(--sltr-price-new, #0f172a) !important; }
.sltr-package:hover .sltr-package-price del,
.sltr-package-card:hover .sltr-package-price del { color: var(--sltr-price-old, #94a3b8) !important; }
.sltr-package:hover .sltr-select-button,
.sltr-package-card:hover .sltr-select-button {
    background: var(--sltr-primary, #2563eb) !important;
    border-color: var(--sltr-primary, #2563eb) !important;
    color: var(--sltr-primary-text, #fff) !important;
    filter: none !important;
}
.sltr-package:hover .sltr-badge-discount,
.sltr-package-card:hover .sltr-badge-discount,
.sltr-package:hover .sltr-badge-popular,
.sltr-package-card:hover .sltr-badge-popular { filter: none !important; transform: none !important; }
CSS;
    }

    private function frontend_i18n(): array
    {
        $service = new \Slotera\Application\Services\TranslationService();
        $strings = \Slotera\Application\Services\TranslationRegistry::strings_for_group('frontend');
        $out = [];
        foreach ($strings as $key => $meta) {
            $default = (string) ($meta['default'] ?? '');
            if ($default !== '') {
                $translated = $service->get((string) $key);
                if ($translated === (string) $key || str_starts_with($translated, 'frontend.')) {
                    $locale = $service->locale_for_group('frontend');
                    $translated = (string) ($meta[$locale] ?? $default);
                }
                $out[$default] = $translated;
            }
        }
        return $out;
    }

    private function frontend_i18n_locales(): array
    {
        $service = new \Slotera\Application\Services\TranslationService();
        $strings = \Slotera\Application\Services\TranslationRegistry::strings_for_group('frontend');
        $languages = \Slotera\Application\Services\TranslationRegistry::languages();
        $out = [];

        foreach (array_keys($languages) as $locale) {
            if ($locale === \Slotera\Application\Services\TranslationRegistry::default_locale()) {
                continue;
            }

            foreach ($strings as $key => $meta) {
                $default = (string) ($meta['default'] ?? '');
                if ($default === '') {
                    continue;
                }

                $translated = $service->get((string) $key, (string) $locale);
                if ($translated === (string) $key || str_starts_with($translated, 'frontend.')) {
                    $translated = (string) ($meta[$locale] ?? $default);
                }
                if ($translated !== '' && $translated !== $default) {
                    $out[$locale][$default] = $translated;
                }
            }
        }

        return $out;
    }

    private function recaptcha_language_for_locale(string $locale): string
    {
        $normalized = str_replace('-', '_', trim($locale));
        if ($normalized === '') {
            return '';
        }

        $map = [
            'pt_BR' => 'pt-BR',
            'pt_PT' => 'pt-PT',
            'en_GB' => 'en-GB',
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        $language = strtolower((string) strtok($normalized, '_'));
        return preg_match('/^[a-z]{2,3}$/', $language) === 1 ? $language : '';
    }


    public function enqueue_frontend_assets(): void
    {
        if (!$this->is_slotera_frontend_context()) {
            return;
        }

        $frontend_css_path = SLTR_PLUGIN_DIR . 'assets/css/frontend.css';
        $frontend_css_version = is_file($frontend_css_path)
            ? SLTR_VERSION . '-' . (string) filemtime($frontend_css_path)
            : SLTR_VERSION;

        wp_enqueue_style(
            'sltr-frontend',
            SLTR_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            $frontend_css_version
        );

        wp_add_inline_style('sltr-frontend', $this->frontend_no_hover_overrides_css());

        $sltr_settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
        if (empty($sltr_settings['show_slotera_page_titles']) && $this->is_slotera_frontend_context()) {
            wp_add_inline_style('sltr-frontend', '.entry-title,.page-title,.wp-block-post-title,.elementor-page-title{display:none!important;}');
        }

        wp_enqueue_script(
            'sltr-frontend-booking-modes',
            SLTR_PLUGIN_URL . 'assets/js/frontend-booking-modes.js',
            [],
            SLTR_VERSION,
            true
        );
        wp_enqueue_script(
            'sltr-frontend-booking-form',
            SLTR_PLUGIN_URL . 'assets/js/frontend-booking-form.js',
            ['sltr-frontend-booking-modes'],
            SLTR_VERSION,
            true
        );
        wp_enqueue_script(
            'sltr-frontend-calendar',
            SLTR_PLUGIN_URL . 'assets/js/frontend-calendar.js',
            ['sltr-frontend-booking-form'],
            SLTR_VERSION,
            true
        );
        wp_enqueue_script(
            'sltr-frontend-package-cards',
            SLTR_PLUGIN_URL . 'assets/js/frontend-package-cards.js',
            ['sltr-frontend-booking-form'],
            SLTR_VERSION,
            true
        );
        $visitor_analytics = new \Slotera\Application\Services\VisitorAnalyticsService();
        if ($visitor_analytics->is_collection_allowed()) {
            wp_enqueue_script(
                'sltr-frontend-analytics',
                SLTR_PLUGIN_URL . 'assets/js/frontend-analytics.js',
                ['sltr-frontend-booking-form'],
                SLTR_VERSION,
                true
            );
        }

        $settings = (new \Slotera\Infrastructure\Repositories\SettingsRepository())->all();
        $currency = \Slotera\Application\Services\CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
        $currency_position = \Slotera\Application\Services\CurrencyService::normalize_position((string) ($settings['payment_currency_position'] ?? 'right_space'));
        $currency_decimals = \Slotera\Application\Services\CurrencyService::decimals($currency);
        $translation_service = new \Slotera\Application\Services\TranslationService();
        $translation_service->cleanup_frontend_locale_overrides(['ru_RU', 'ru', 'bg_BG', 'mt_MT']);
        $frontend_locale = $translation_service->locale_for_group('frontend');
        wp_localize_script('sltr-frontend-booking-form', 'sltr_ajax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sltr_frontend_booking'),
            'rest_url' => esc_url_raw(rest_url('slotera/v1/')),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'currency_symbol' => \Slotera\Application\Services\CurrencyService::symbol($currency),
            'currency_position' => $currency_position,
            'currency_decimals' => $currency_decimals,
            'currency_decimal_separator' => \Slotera\Application\Services\CurrencyService::normalize_separator((string) ($settings['payment_decimal_separator'] ?? '.'), '.'),
            'currency_thousands_separator' => \Slotera\Application\Services\CurrencyService::normalize_separator((string) ($settings['payment_thousands_separator'] ?? ' '), ' '),
            'low_availability_threshold' => 5,
            'frontend_locale' => $frontend_locale,
            'frontend_locale_js' => str_replace('_', '-', $frontend_locale),
            'current_post_id' => is_singular() ? (int) get_queried_object_id() : 0,
            'visitor_analytics_session_enabled' => $visitor_analytics->is_session_collection_allowed() ? 1 : 0,
            'captcha_provider' => (string) ($settings['security_captcha_provider'] ?? 'none'),
            'i18n' => $this->frontend_i18n(),
            'i18n_locales' => $this->frontend_i18n_locales(),
        ]);
        $captcha_provider = (string) ($settings['security_captcha_provider'] ?? 'none');

        if ($captcha_provider === 'turnstile' && !empty($settings['security_turnstile_site_key'])) {
            wp_enqueue_script('sltr-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, true);
        }

        if ($captcha_provider === 'recaptcha' && !empty($settings['security_recaptcha_site_key'])) {
            $recaptcha_url = 'https://www.google.com/recaptcha/api.js';
            $recaptcha_language = $this->recaptcha_language_for_locale($frontend_locale);
            if ($recaptcha_language !== '') {
                $recaptcha_url = add_query_arg('hl', $recaptcha_language, $recaptcha_url);
            }
            wp_enqueue_script('sltr-recaptcha', $recaptcha_url, [], null, true);
        }

        if ($captcha_provider === 'recaptcha_v3' && !empty($settings['security_recaptcha_site_key'])) {
            $recaptcha_url = add_query_arg('render', (string) $settings['security_recaptcha_site_key'], 'https://www.google.com/recaptcha/api.js');
            $recaptcha_language = $this->recaptcha_language_for_locale($frontend_locale);
            if ($recaptcha_language !== '') {
                $recaptcha_url = add_query_arg('hl', $recaptcha_language, $recaptcha_url);
            }
            wp_enqueue_script('sltr-recaptcha-v3-api', $recaptcha_url, [], null, true);
            wp_enqueue_script(
                'sltr-frontend-recaptcha-v3',
                SLTR_PLUGIN_URL . 'assets/js/frontend-recaptcha-v3.js',
                ['sltr-recaptcha-v3-api'],
                SLTR_VERSION,
                true
            );
            wp_localize_script('sltr-frontend-recaptcha-v3', 'sltr_recaptcha_v3', [
                'site_key' => (string) $settings['security_recaptcha_site_key'],
            ]);
        }
    }
}
