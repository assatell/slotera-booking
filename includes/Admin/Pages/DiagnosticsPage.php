<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\DiagnosticsService;
use Slotera\Core\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class DiagnosticsPage
{
    public function render(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_TOOLS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'diagnostics';
        if (!in_array($tab, ['diagnostics', 'tools', 'logs'], true)) {
            $tab = 'diagnostics';
        }
        if ($tab === 'logs' && !current_user_can(Capabilities::VIEW_LOGS)) {
            $tab = 'diagnostics';
        }

        echo '<div class="wrap sltr-admin-wrap">';
        echo '<h1>' . esc_html__('Diagnostics & Tools', 'slotera-booking') . '</h1>';
        echo '<nav class="nav-tab-wrapper" style="margin-bottom:20px;">';
        foreach ([
            'diagnostics' => __('Diagnostics', 'slotera-booking'),
            'tools' => __('Tools', 'slotera-booking'),
            'logs' => __('Logs', 'slotera-booking'),
        ] as $key => $label) {
            if ($key === 'logs' && !current_user_can(Capabilities::VIEW_LOGS)) {
                continue;
            }
            $url = add_query_arg(['page' => 'slotera-diagnostics', 'tab' => $key], admin_url('admin.php'));
            echo '<a class="nav-tab ' . ($tab === $key ? 'nav-tab-active' : '') . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';

        if ($tab === 'tools') {
            (new ToolsPage())->renderEmbedded();
        } elseif ($tab === 'logs') {
            (new LogsPage())->renderEmbedded();
        } else {
            $service = new DiagnosticsService();
            $diagnostics = $service->run();
            $observability_events = $service->recent_observability_events(10);
            $embedded = true;
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/diagnostics.php';
        }
        echo '</div>';
    }
}
