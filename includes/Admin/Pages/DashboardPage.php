<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\DashboardService;

if (!defined('ABSPATH')) {
    exit;
}

final class DashboardPage
{
    private static bool $rendered = false;

    public function render(): void
    {
        if (self::$rendered) {
            return;
        }

        self::$rendered = true;

        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        $data = (new DashboardService())->get_dashboard_data(10);

        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/dashboard.php';
    }
}
