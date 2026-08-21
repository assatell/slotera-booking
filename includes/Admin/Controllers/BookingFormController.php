<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\BookingFormConfigService;
use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class BookingFormController
{
    private RequestValidator $request;
    private SettingsRepository $settings;
    private BookingFormConfigService $config;

    public function __construct(?RequestValidator $request = null, ?SettingsRepository $settings = null, ?BookingFormConfigService $config = null)
    {
        $this->request = $request ?: new RequestValidator();
        $this->settings = $settings ?: new SettingsRepository();
        $this->config = $config ?: new BookingFormConfigService($this->settings);
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_booking_form', [$this, 'save']);
    }

    public function save(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_SETTINGS)) {
            wp_die(esc_html__('You do not have permission to manage booking form settings.', 'slotera-booking'));
        }

        $this->request->verify_admin_nonce('sltr_save_booking_form');

        $settings = [];
        foreach ($this->config->definitions() as $key => $field) {
            if (!empty($field['locked'])) {
                $settings['booking_form_' . $key . '_enabled'] = 1;
                $settings['booking_form_' . $key . '_required'] = 1;
                continue;
            }

            $settings['booking_form_' . $key . '_enabled'] = $this->request->post_bool('booking_form_' . $key . '_enabled');
            $settings['booking_form_' . $key . '_required'] = $this->request->post_bool('booking_form_' . $key . '_required');
        }

        $this->settings->update($settings);

        wp_safe_redirect(add_query_arg(['page' => 'slotera-settings', 'booking_form_updated' => '1'], admin_url('admin.php')));
        exit;
    }
}
