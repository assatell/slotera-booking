<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\WhiteLabelService;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class WhiteLabelPage
{
    private RequestValidator $request;

    public function __construct(?RequestValidator $request = null)
    {
        $this->request = $request ?: new RequestValidator();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);

        $license_policy = new \Slotera\Application\Services\LicenseFeaturePolicy();
        if (!$license_policy->allows(\Slotera\Application\Services\LicenseFeaturePolicy::WHITE_LABEL)) {
            ?>
            <div class="wrap sltr-admin-wrap">
                <h1><?php esc_html_e('White Label', 'slotera-booking'); ?></h1>
                <div class="notice notice-warning"><p><strong><?php echo esc_html($license_policy->locked_message(__('White Label', 'slotera-booking'))); ?></strong> <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-license')); ?>"><?php esc_html_e('Open License', 'slotera-booking'); ?></a></p></div>
            </div>
            <?php
            return;
        }

        $settings = (new SettingsRepository())->all();
        $white_label = new WhiteLabelService();
        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/white-label.php';
    }
}
