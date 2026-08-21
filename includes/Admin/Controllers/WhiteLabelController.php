<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\WhiteLabelService;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class WhiteLabelController
{
    private SettingsRepository $settings;
    private RequestValidator $request;

    public function __construct(?SettingsRepository $settings = null, ?RequestValidator $request = null)
    {
        $this->settings = $settings ?? new SettingsRepository();
        $this->request = $request ?? new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_white_label_settings', [$this, 'save']);
    }

    public function save(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce('sltr_save_white_label_settings');

        $admin_logo_url = esc_url_raw($this->request->post_text('white_label_admin_logo_url'));
        $default_logo_url = (new WhiteLabelService())->default_admin_logo_url();
        if ($admin_logo_url === $default_logo_url) { $admin_logo_url = ''; }

        $this->settings->update([
            'white_label_enabled' => $this->request->post_bool('white_label_enabled'),
            'white_label_brand_name' => $this->request->post_text('white_label_brand_name', 'Slotera'),
            'white_label_product_name' => $this->request->post_text('white_label_product_name', 'Slotera Booking'),
            'white_label_admin_logo_url' => $admin_logo_url,
            'white_label_admin_footer_text' => $this->request->post_text('white_label_admin_footer_text'),
            'white_label_plugin_description' => $this->request->post_textarea('white_label_plugin_description'),
            'white_label_hide_vendor_branding' => $this->request->post_bool('white_label_hide_vendor_branding'),
        ]);

        wp_safe_redirect(admin_url('admin.php?page=slotera-white-label&sltr_message=saved'));
        exit;
    }
}
