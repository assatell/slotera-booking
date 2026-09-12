<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\AnalyticsService;
use Slotera\Core\Capabilities;

if (!defined('ABSPATH')) { exit; }

final class AnalyticsPage
{
    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_BOOKINGS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        $license_policy = new \Slotera\Application\Services\LicenseFeaturePolicy();
        if (!$license_policy->allows(\Slotera\Application\Services\LicenseFeaturePolicy::ANALYTICS)) {
            ?>
            <div class="wrap sltr-admin-wrap">
                <h1><?php esc_html_e('Analytics', 'slotera-booking'); ?></h1>
                <div class="notice notice-warning"><p><strong><?php echo esc_html($license_policy->locked_message(__('Analytics', 'slotera-booking'))); ?></strong> <a href="<?php echo esc_url(admin_url('admin.php?page=slotera-license')); ?>"><?php esc_html_e('Open License', 'slotera-booking'); ?></a></p></div>
            </div>
            <?php
            return;
        }

        $period = isset($_GET['period']) ? sanitize_key((string) wp_unslash($_GET['period'])) : '30';
        $report = (new AnalyticsService())->report($period);

        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/analytics.php';
    }
}
