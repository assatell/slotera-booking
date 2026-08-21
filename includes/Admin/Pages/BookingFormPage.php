<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\BookingFormConfigService;

if (!defined('ABSPATH')) { exit; }

final class BookingFormPage
{
    public function redirectLegacy(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_SETTINGS)) {
            wp_die(esc_html__('You do not have permission to view this page.', 'slotera-booking'));
        }

        wp_safe_redirect(add_query_arg(['page' => 'slotera-settings'], admin_url('admin.php')));
        exit;
    }
}
