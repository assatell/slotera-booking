<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class CustomerService
{
    private BookingFormConfigService $form_config;

    public function __construct(?BookingFormConfigService $form_config = null)
    {
        $this->form_config = $form_config ?: new BookingFormConfigService();
    }

    public function sanitize_booking_data(array $data): array
    {
        return [
            'customer_name' => sanitize_text_field((string) ($data['customer_name'] ?? $data['name'] ?? '')),
            'customer_email' => sanitize_email((string) ($data['customer_email'] ?? $data['email'] ?? '')),
            'customer_phone' => sanitize_text_field((string) ($data['customer_phone'] ?? $data['phone'] ?? '')),
            'city' => sanitize_text_field((string) ($data['city'] ?? '')),
            'state' => sanitize_text_field((string) ($data['state'] ?? '')),
            'address' => sanitize_text_field((string) ($data['address'] ?? '')),
            'company' => sanitize_text_field((string) ($data['company'] ?? '')),
            'notes' => wp_kses_post((string) ($data['notes'] ?? '')),
        ];
    }

    public function validate_booking_fields(array $customer)
    {
        $email = (string) ($customer['customer_email'] ?? '');
        if ($email === '' || !is_email($email)) {
            return new WP_Error('sltr_invalid_customer_email', __('Please enter a valid email address.', 'slotera-booking'));
        }

        $values = [
            'name' => (string) ($customer['customer_name'] ?? ''),
            'email' => $email,
            'phone' => (string) ($customer['customer_phone'] ?? ''),
            'city' => (string) ($customer['city'] ?? ''),
            'state' => (string) ($customer['state'] ?? ''),
            'address' => (string) ($customer['address'] ?? ''),
            'company' => (string) ($customer['company'] ?? ''),
            'notes' => (string) ($customer['notes'] ?? ''),
        ];

        foreach ($this->form_config->fields() as $field_key => $field) {
            if (!empty($field['enabled']) && !empty($field['required']) && trim((string) ($values[$field_key] ?? '')) === '') {
                return new WP_Error('sltr_required_booking_field_missing', __('Please complete all required fields.', 'slotera-booking'));
            }
        }

        return true;
    }
}
