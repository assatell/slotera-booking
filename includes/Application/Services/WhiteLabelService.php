<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class WhiteLabelService
{
    private ?array $settings = null;

    public function register_hooks(): void
    {
        add_filter('admin_title', [$this, 'filter_admin_title'], 20, 2);
        add_filter('gettext', [$this, 'filter_admin_brand_text'], 30, 3);
        add_action('admin_head', [$this, 'print_admin_branding_css']);
        add_action('admin_footer', [$this, 'print_admin_branding_script'], 5);
        add_filter('admin_footer_text', [$this, 'filter_admin_footer_text'], 20);
        add_filter('plugin_row_meta', [$this, 'filter_plugin_row_meta'], 20, 2);
        add_filter('all_plugins', [$this, 'filter_plugin_list_data'], 20);
    }

    public function enabled(): bool
    {
        return (int) ($this->settings()['white_label_enabled'] ?? 0) === 1;
    }

    public function brand_name(): string
    {
        $name = trim((string) ($this->settings()['white_label_brand_name'] ?? ''));
        return $this->enabled() && $name !== '' ? $name : 'Slotera';
    }

    public function product_name(): string
    {
        $name = trim((string) ($this->settings()['white_label_product_name'] ?? ''));
        return $this->enabled() && $name !== '' ? $name : 'Slotera Booking';
    }

    public function default_admin_logo_url(): string
    {
        return SLTR_PLUGIN_URL . 'assets/images/slotera-admin-logo.svg';
    }

    public function admin_logo_url(): string
    {
        $custom = esc_url_raw((string) ($this->settings()['white_label_admin_logo_url'] ?? ''));
        if ($this->enabled() && $custom !== '') { return $custom; }
        if ($this->hide_vendor_branding()) { return ''; }
        return $this->default_admin_logo_url();
    }

    public function hide_vendor_branding(): bool
    {
        return (int) ($this->settings()['white_label_hide_vendor_branding'] ?? 0) === 1;
    }

    public function public_attribution_visible(): bool
    {
        return !$this->hide_vendor_branding();
    }

    public function platform_url(): string
    {
        $default = 'https://slotera.app/';
        $url = apply_filters('sltr_platform_url', $default);
        $url = is_string($url) ? esc_url_raw($url) : '';
        return $url !== '' ? $url : $default;
    }

    public function filter_admin_title(string $admin_title, string $title): string
    {
        if (!$this->enabled() || !$this->is_slotera_admin_context()) { return $admin_title; }
        $product = $this->product_name();
        if ($product === 'Slotera Booking') { return $admin_title; }
        return str_replace(['Slotera Booking', 'Slotera'], $product, $admin_title);
    }

    public function print_admin_branding_css(): void
    {
        if (!$this->is_slotera_admin_context()) { return; }
        $logo_url = $this->admin_logo_url();
        ?>
        <style>
            .sltr-white-label-logo {display:inline-flex;align-items:center;justify-content:center;width:142px;height:34px;margin-right:12px;vertical-align:middle;flex:0 0 auto;}
            .sltr-white-label-logo img {max-width:142px;max-height:34px;width:auto;height:auto;display:block;border-radius:4px;}
        </style>
        <?php
    }

    public function print_admin_branding_script(): void
    {
        if (!$this->is_slotera_admin_context()) { return; }
        $logo_url = $this->admin_logo_url();
        if ($logo_url === '') { return; }
        ?>
        <script>
        (function () {
            var logoUrl = <?php echo wp_json_encode($logo_url); ?>;
            if (!logoUrl) { return; }

            var title = document.querySelector(
                '.sltr-page-header__title, .sltr-admin-wrap h1, #wpbody-content .wrap h1, #wpbody-content h1'
            );
            if (!title || title.querySelector('.sltr-white-label-logo')) { return; }

            var badge = document.createElement('span');
            badge.className = 'sltr-white-label-logo';

            var image = document.createElement('img');
            image.src = logoUrl;
            image.alt = <?php echo wp_json_encode($this->brand_name()); ?>;
            image.decoding = 'async';
            badge.appendChild(image);
            title.insertBefore(badge, title.firstChild);
        }());
        </script>
        <?php
    }

    public function filter_admin_brand_text(string $translation, string $text, string $domain): string
    {
        if ($domain !== 'slotera-booking' || !$this->enabled() || !$this->is_slotera_admin_context()) { return $translation; }

        $product = $this->product_name();
        $brand = $this->brand_name();
        $translation = str_replace('Slotera Booking', $product, $translation);
        $translation = str_replace('Slotera', $brand, $translation);
        return $translation;
    }

    public function filter_admin_footer_text(string $text): string
    {
        if (!$this->is_slotera_admin_context()) { return $text; }
        if ($this->hide_vendor_branding()) { return ''; }

        $footer = trim((string) ($this->settings()['white_label_admin_footer_text'] ?? 'Powered by Slotera'));
        if ($this->enabled() && $footer !== '') { return esc_html($footer); }

        return esc_html('Powered by Slotera');
    }

    public function filter_plugin_row_meta(array $links, string $file): array
    {
        if (!$this->hide_vendor_branding() || $file !== SLTR_PLUGIN_BASENAME) { return $links; }
        return [];
    }

    public function filter_plugin_list_data(array $plugins): array
    {
        if (!isset($plugins[SLTR_PLUGIN_BASENAME])) { return $plugins; }
        if ($this->enabled()) {
            $plugins[SLTR_PLUGIN_BASENAME]['Name'] = $this->product_name();
            $description = trim((string) ($this->settings()['white_label_plugin_description'] ?? 'Complete WordPress booking platform with payments, packages, coupons, marketing automation, analytics and customer management.'));
            if ($description !== '') { $plugins[SLTR_PLUGIN_BASENAME]['Description'] = $description; }
        }
        if ($this->hide_vendor_branding()) {
            $plugins[SLTR_PLUGIN_BASENAME]['Author'] = '';
            $plugins[SLTR_PLUGIN_BASENAME]['AuthorName'] = '';
            $plugins[SLTR_PLUGIN_BASENAME]['PluginURI'] = '';
            $plugins[SLTR_PLUGIN_BASENAME]['AuthorURI'] = '';
        }
        return $plugins;
    }

    public function settings(): array
    {
        if ($this->settings === null) { $this->settings = (new SettingsRepository())->all(); }
        return $this->settings;
    }

    private function is_slotera_admin_context(): bool
    {
        if (!is_admin()) { return false; }
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : '';
        if (strpos($page, 'slotera') === 0) { return true; }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id = $screen ? (string) $screen->id : '';
        return strpos($screen_id, 'slotera') !== false;
    }
}
