<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\LicenseService;
use Slotera\Application\Services\RequestValidator;

if (!defined('ABSPATH')) { exit; }

final class LicenseController
{
    private RequestValidator $request;

    public function __construct(?RequestValidator $request = null)
    {
        $this->request = $request ?: new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_activate_license', [$this, 'activate']);
        add_action('admin_post_sltr_deactivate_license', [$this, 'deactivate']);
        add_action('admin_post_sltr_check_license_local', [$this, 'check_local']);
    }

    public function activate(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce('sltr_activate_license');
        $key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash((string) $_POST['license_key'])) : '';
        $ok = (new LicenseService())->activate_license($key);
        wp_safe_redirect(admin_url('admin.php?page=slotera-license&license_' . ($ok ? 'activated=1' : 'error=1')));
        exit;
    }

    public function deactivate(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce('sltr_deactivate_license');
        (new LicenseService())->deactivate_license();
        wp_safe_redirect(admin_url('admin.php?page=slotera-license&license_deactivated=1'));
        exit;
    }

    public function check_local(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce('sltr_check_license_local');
        (new LicenseService())->check_license_locally();
        wp_safe_redirect(admin_url('admin.php?page=slotera-license&license_checked=1'));
        exit;
    }
}
