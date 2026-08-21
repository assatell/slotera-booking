<?php

declare(strict_types=1);

namespace Slotera\Admin;

use Slotera\Admin\Controllers\BookingActionsController;
use Slotera\Admin\Controllers\BookingFormController;
use Slotera\Admin\Controllers\CategoryController;
use Slotera\Admin\Controllers\CouponController;
use Slotera\Admin\Controllers\EmailController;
use Slotera\Admin\Controllers\EventController;
use Slotera\Admin\Controllers\MarketingController;
use Slotera\Admin\Controllers\LicenseController;
use Slotera\Admin\Controllers\LocationController;
use Slotera\Admin\Controllers\PackageController;
use Slotera\Admin\Controllers\PaymentsController;
use Slotera\Admin\Controllers\SettingsController;
use Slotera\Admin\Controllers\SecurityController;
use Slotera\Admin\Controllers\SeoController;
use Slotera\Admin\Controllers\ToolsController;
use Slotera\Admin\Controllers\TranslationController;
use Slotera\Admin\Controllers\WorkingHoursController;
use Slotera\Admin\Controllers\WhiteLabelController;
use Slotera\Admin\Pages\BookingsPage;
use Slotera\Admin\Pages\BookingFormPage;
use Slotera\Admin\Pages\CategoriesPage;
use Slotera\Admin\Pages\CouponsPage;
use Slotera\Admin\Pages\CustomersPage;
use Slotera\Admin\Pages\DashboardPage;
use Slotera\Admin\Pages\AnalyticsPage;
use Slotera\Admin\Pages\EmailsPage;
use Slotera\Admin\Pages\EventsPage;
use Slotera\Admin\Pages\LogsPage;
use Slotera\Admin\Pages\LicensePage;
use Slotera\Admin\Pages\LocationsPage;
use Slotera\Admin\Pages\DiagnosticsPage;
use Slotera\Admin\Pages\MarketingPage;
use Slotera\Admin\Pages\PackagesPage;
use Slotera\Admin\Pages\PaymentsPage;
use Slotera\Admin\Pages\PaymentTransactionsPage;
use Slotera\Admin\Pages\PaymentInvoicesPage;
use Slotera\Admin\Pages\ProFeaturesPage;
use Slotera\Admin\Pages\SharedDatabaseNetworkPage;
use Slotera\Admin\Pages\SettingsPage;
use Slotera\Admin\Pages\SecurityPage;
use Slotera\Admin\Pages\SeoSettingsPage;
use Slotera\Admin\Pages\ToolsPage;
use Slotera\Admin\Pages\TranslationsPage;
use Slotera\Admin\Pages\WhiteLabelPage;
use Slotera\Core\Capabilities;
use Slotera\Application\Services\LicenseService;
use Slotera\Application\Services\WhiteLabelService;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminServiceProvider
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_menu', [$this, 'position_external_slotera_submenus'], 9999);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_head', [$this, 'print_menu_position_fix']);

        (new BookingActionsController())->register();
        (new BookingFormController())->register();
        (new PackageController())->register();
        (new CategoryController())->register();
        (new LocationController())->register();
        (new WorkingHoursController())->register();
        (new SettingsController())->register();
        (new SeoController())->register();
        (new SecurityController())->register();
        (new EmailController())->register();
        (new EventController())->register();
        (new CouponController())->register();
        (new MarketingController())->register();
        (new PaymentsController())->register();
        (new LicenseController())->register();
        (new ToolsController())->register();
        (new WhiteLabelController())->register();

        (new TranslationController())->register();
    }

    public function register_menu(): void
    {
        $white_label = new WhiteLabelService();
        $brand_name = $white_label->brand_name();
        $product_name = $white_label->product_name();

        add_menu_page($product_name, $brand_name, Capabilities::MANAGE, 'slotera', [new DashboardPage(), 'render'], 'dashicons-calendar-alt', 26);

        add_submenu_page('slotera', $product_name . ' Dashboard', $brand_name . ' Dashboard', Capabilities::MANAGE, 'slotera', [new DashboardPage(), 'render']);
        add_submenu_page('slotera', 'Bookings', 'Bookings', Capabilities::MANAGE_BOOKINGS, 'slotera-bookings', [new BookingsPage(), 'render']);
        add_submenu_page(null, 'Customers', 'Customers', Capabilities::MANAGE_BOOKINGS, 'slotera-customers', [new CustomersPage(), 'render']);
        add_submenu_page('slotera', 'Settings', 'Settings', Capabilities::MANAGE_SETTINGS, 'slotera-settings', [new SettingsPage(), 'render']);
        add_submenu_page(null, 'SEO Settings', 'SEO Settings', Capabilities::MANAGE_SETTINGS, 'slotera-seo', [new SeoSettingsPage(), 'redirectLegacy']);
        add_submenu_page('slotera', 'Categories', 'Categories', Capabilities::MANAGE_PACKAGES, 'slotera-categories', [new CategoriesPage(), 'render']);
        add_submenu_page('slotera', 'Packages', 'Packages', Capabilities::MANAGE_PACKAGES, 'slotera-packages', [new PackagesPage(), 'render']);
        add_submenu_page('slotera', 'Locations', 'Locations', Capabilities::MANAGE_PACKAGES, 'slotera-locations', [new LocationsPage(), 'render']);
        add_submenu_page(null, 'Coupons', 'Coupons', Capabilities::MANAGE_MARKETING, 'slotera-coupons', [new CouponsPage(), 'render']);
        add_submenu_page(null, 'Events', 'Events', Capabilities::MANAGE_PACKAGES, 'slotera-events', [new EventsPage(), 'render']);
        add_submenu_page(null, 'Booking Form', 'Booking Form', Capabilities::MANAGE_SETTINGS, 'slotera-booking-form', [new BookingFormPage(), 'redirectLegacy']);
        add_submenu_page('slotera', 'Translations', 'Translations', Capabilities::MANAGE_SETTINGS, 'slotera-translations', [new TranslationsPage(), 'render']);
        add_submenu_page(null, 'Security', 'Security', Capabilities::MANAGE_SETTINGS, 'slotera-security', [new SecurityPage(), 'render']);
        add_submenu_page('slotera', 'Diagnostics & Tools', 'Diagnostics & Tools', Capabilities::MANAGE_TOOLS, 'slotera-diagnostics', [new DiagnosticsPage(), 'render']);
        add_submenu_page(null, 'Tools', 'Tools', Capabilities::MANAGE_TOOLS, 'slotera-tools', [new ToolsPage(), 'redirectLegacy']);
        add_submenu_page(null, 'Logs', 'Logs', Capabilities::VIEW_LOGS, 'slotera-logs', [new LogsPage(), 'redirectLegacy']);
        add_submenu_page('slotera', 'Payments', 'Payments', Capabilities::MANAGE_PAYMENTS, 'slotera-payments', [new PaymentsPage(), 'render']);
        add_submenu_page(null, 'Payment Transactions', 'Transactions', Capabilities::MANAGE_PAYMENTS, 'slotera-payment-transactions', [new PaymentTransactionsPage(), 'render']);
        add_submenu_page(null, 'Payment Invoices', 'Invoices', Capabilities::MANAGE_PAYMENTS, 'slotera-payment-invoices', [new PaymentInvoicesPage(), 'render']);
        add_submenu_page('slotera', 'Analytics', 'Analytics', Capabilities::MANAGE_BOOKINGS, 'slotera-analytics', [new AnalyticsPage(), 'render']);
        add_submenu_page('slotera', 'Marketing Emails', 'Marketing Emails', Capabilities::MANAGE_MARKETING, 'slotera-marketing', [new MarketingPage(), 'render']);
        add_submenu_page('slotera', 'Shared Database Network', 'Shared Database Network', Capabilities::MANAGE_SETTINGS, 'slotera-shared-network', [new SharedDatabaseNetworkPage(), 'render']);
        add_submenu_page('slotera', 'White Label', 'White Label', Capabilities::MANAGE_SETTINGS, 'slotera-white-label', [new WhiteLabelPage(), 'render']);
        if (!(new LicenseService())->has_built_in_license()) {
            add_submenu_page('slotera', 'License', 'License', Capabilities::MANAGE_SETTINGS, 'slotera-license', [new LicensePage(), 'render']);
        }
    }

    /**
     * Keep externally registered Slotera add-on submenu pages in the intended order.
     *
     * Slotera Performance Cache is a separate plugin and may register its submenu after
     * Slotera Booking. WordPress appends such external submenu pages by default, so we
     * normalize its position after Security once all admin_menu callbacks have run.
     */
    public function position_external_slotera_submenus(): void
    {
        // Security now lives under Settings; keep external submenu order unchanged.
    }

    private function is_performance_cache_submenu_item(string $label, string $slug): bool
    {
        $normalized_label = strtolower($label);
        $normalized_slug = strtolower($slug);

        if (strpos($normalized_slug, 'slotera-performance-cache') !== false || strpos($normalized_slug, 'performance-cache') !== false) {
            return true;
        }

        return strpos($normalized_label, 'slotera performance cache') !== false
            || strpos($normalized_label, 'performance cache') !== false;
    }

    public function enqueue_assets(string $hook): void
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : '';
        $is_slotera_page = strpos($hook, 'slotera') !== false || $page === 'slotera' || strpos($page, 'slotera-') === 0;

        if (!$is_slotera_page) {
            return;
        }

        wp_enqueue_style('sltr-admin', SLTR_PLUGIN_URL . 'assets/css/admin.css', [], SLTR_VERSION);

        if (strpos($hook, 'slotera-packages') !== false || strpos($hook, 'slotera-categories') !== false || strpos($hook, 'slotera-locations') !== false) {
            wp_enqueue_media();
        }

        wp_enqueue_script('sltr-admin-runtime', SLTR_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], SLTR_VERSION, true);
        wp_localize_script('sltr-admin-runtime', 'sltr_ajax', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'i18n' => $this->admin_i18n(),
            'ux' => [
                'processing' => __('Processing…', 'slotera-booking'),
                'confirm_title' => __('Confirm action', 'slotera-booking'),
                'confirm_message' => __('Are you sure?', 'slotera-booking'),
                'confirm_button' => __('Confirm', 'slotera-booking'),
                'cancel_button' => __('Cancel', 'slotera-booking'),
                'confirm_check_label' => __('I understand the consequences of this action.', 'slotera-booking'),
                'table_label' => __('Scrollable data table', 'slotera-booking'),
            ],
        ]);

        foreach ($this->admin_script_handles_for_hook($hook) as $handle => $path) {
            wp_enqueue_script($handle, SLTR_PLUGIN_URL . $path, ['sltr-admin-runtime'], SLTR_VERSION, true);
        }
    }



    public function print_menu_position_fix(): void
    {
        ?>
        <style>
            /* Prevent the Slotera flyout submenu from clipping against the viewport/admin edge. */
            #adminmenu .toplevel_page_slotera.wp-has-submenu > .wp-submenu {
                transform: translateX(8px);
            }
            body.folded #adminmenu .toplevel_page_slotera.wp-has-submenu > .wp-submenu,
            body.auto-fold #adminmenu .toplevel_page_slotera.wp-has-submenu > .wp-submenu {
                transform: translateX(10px);
            }
        </style>
        <?php
    }


    /**
     * Return page-scoped admin scripts to avoid loading one large admin bundle everywhere.
     *
     * @return array<string,string> Script handle => asset path.
     */
    private function admin_script_handles_for_hook(string $hook): array
    {
        $scripts = [];

        if (strpos($hook, 'slotera-settings') !== false || strpos($hook, 'slotera-booking-form') !== false || strpos($hook, 'slotera-emails') !== false) {
            $scripts['sltr-admin-appearance'] = 'assets/js/admin-appearance.js';
        }

        if (strpos($hook, 'slotera-packages') !== false || strpos($hook, 'slotera-categories') !== false || strpos($hook, 'slotera-locations') !== false || strpos($hook, 'slotera-marketing') !== false) {
            $scripts['sltr-admin-package-editor'] = 'assets/js/admin-package-editor.js';
            $scripts['sltr-admin-package-hours-toggle'] = 'assets/js/admin/components/package-hours-toggle.js';
            $scripts['sltr-admin-package-booking-mode'] = 'assets/js/admin/components/package-booking-mode.js';
            $scripts['sltr-admin-package-date-range-repeaters'] = 'assets/js/admin/components/package-date-range-repeaters.js';
            $scripts['sltr-admin-package-live-preview'] = 'assets/js/admin/components/package-live-preview.js';
            $scripts['sltr-admin-package-save-state'] = 'assets/js/admin/components/package-save-state.js';
        }

        if (strpos($hook, 'slotera-translations') !== false) {
            $scripts['sltr-admin-translations'] = 'assets/js/admin-translations.js';
        }

        if (strpos($hook, 'toplevel_page_slotera') !== false || strpos($hook, 'slotera_page_slotera') !== false) {
            $scripts['sltr-admin-dashboard'] = 'assets/js/admin-dashboard.js';
        }

        if (strpos($hook, 'slotera-settings') !== false || strpos($hook, 'slotera-seo') !== false || strpos($hook, 'slotera-packages') !== false || strpos($hook, 'slotera-categories') !== false || strpos($hook, 'slotera-locations') !== false) {
            $scripts['sltr-admin-seo'] = 'assets/js/admin-seo.js';
        }

        return $scripts;
    }

    private function admin_i18n(): array
    {
        $service = new \Slotera\Application\Services\TranslationService();
        $strings = \Slotera\Application\Services\TranslationRegistry::strings_for_group('admin');
        $out = [];
        foreach ($strings as $key => $meta) {
            $default = (string) ($meta['default'] ?? '');
            if ($default !== '') {
                $out[$default] = $service->get((string) $key);
            }
        }
        return $out;
    }
}
