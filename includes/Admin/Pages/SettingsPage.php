<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\BookingFormConfigService;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Infrastructure\Repositories\WorkingHoursRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class SettingsPage
{
    public function render(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_SETTINGS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        $section = isset($_GET['section']) ? sanitize_key((string) wp_unslash($_GET['section'])) : 'general';
        if ($section === 'seo') {
            (new SeoSettingsPage())->render(true);
            return;
        }

        if ($section === 'email') {
            $settings = (new SettingsRepository())->all();
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/settings-email.php';
            return;
        }

        if ($section === 'advanced') {
            $settings = (new SettingsRepository())->all();
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/settings-advanced.php';
            return;
        }

        if ($section === 'security') {
            $settings = (new SettingsRepository())->all();
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/settings-security.php';
            return;
        }

        $hours = (new WorkingHoursRepository())->get_all_global();
        $booking_form_fields = (new BookingFormConfigService())->fields();
        $settings_repository = new SettingsRepository();
        $settings = $settings_repository->all();
        $required_pages_ready = true;
        foreach (['packages', 'categories', 'booking', 'thank_you', 'checkout', 'login', 'account'] as $page_key) {
            $setting_key = $page_key . '_page_id';
            if (!$settings_repository->is_published_page_for_key((int) ($settings[$setting_key] ?? 0), $page_key)) {
                $required_pages_ready = false;
                break;
            }
        }

        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/settings-working-hours.php';
    }
}
