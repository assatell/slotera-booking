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

        $period = isset($_GET['period']) ? sanitize_key((string) wp_unslash($_GET['period'])) : '30';
        $report = (new AnalyticsService())->report($period);

        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/analytics.php';
    }
}
