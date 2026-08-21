<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class BookingFormConfigService
{
    private SettingsRepository $settings;

    public function __construct(?SettingsRepository $settings = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
    }

    public function fields(): array
    {
        $settings = $this->settings->all();
        $fields = [];

        foreach ($this->definitions() as $key => $definition) {
            $locked = !empty($definition['locked']);
            $default_enabled = !empty($definition['enabled']);
            $default_required = !empty($definition['required']);

            $fields[$key] = array_merge($definition, [
                'enabled' => $locked ? true : !empty($settings['booking_form_' . $key . '_enabled']),
                'required' => $locked ? true : !empty($settings['booking_form_' . $key . '_required']),
                'locked' => $locked,
            ]);

            if (!array_key_exists('booking_form_' . $key . '_enabled', $settings)) {
                $fields[$key]['enabled'] = $default_enabled;
            }

            if (!array_key_exists('booking_form_' . $key . '_required', $settings)) {
                $fields[$key]['required'] = $default_required;
            }
        }

        return $fields;
    }

    public function definitions(): array
    {
        return [
            'name' => [
                'label' => \sltr__('frontend.name'),
                'placeholder' => \sltr__('frontend.your_name'),
                'autocomplete' => 'name',
                'type' => 'text',
                'enabled' => true,
                'required' => true,
            ],
            'email' => [
                'label' => \sltr__('frontend.email'),
                'placeholder' => \sltr__('frontend.you_example_com'),
                'autocomplete' => 'email',
                'type' => 'email',
                'enabled' => true,
                'required' => true,
                'locked' => true,
            ],
            'phone' => [
                'label' => \sltr__('frontend.phone'),
                'placeholder' => \sltr__('frontend.phone_number'),
                'autocomplete' => 'tel',
                'type' => 'tel',
                'enabled' => true,
                'required' => false,
            ],
            'city' => [
                'label' => \sltr__('frontend.city'),
                'placeholder' => \sltr__('frontend.city'),
                'autocomplete' => 'address-level2',
                'type' => 'text',
                'enabled' => false,
                'required' => false,
            ],
            'state' => [
                'label' => \sltr__('frontend.state_county'),
                'placeholder' => \sltr__('frontend.state_county_region'),
                'autocomplete' => 'address-level1',
                'type' => 'text',
                'enabled' => false,
                'required' => false,
            ],
            'address' => [
                'label' => \sltr__('frontend.address'),
                'placeholder' => \sltr__('frontend.street_address'),
                'autocomplete' => 'street-address',
                'type' => 'text',
                'enabled' => false,
                'required' => false,
            ],
            'company' => [
                'label' => \sltr__('frontend.company'),
                'placeholder' => \sltr__('frontend.company_name'),
                'autocomplete' => 'organization',
                'type' => 'text',
                'enabled' => false,
                'required' => false,
            ],
            'notes' => [
                'label' => \sltr__('frontend.additional_notes_wishes'),
                'placeholder' => \sltr__('frontend.tell_us_about_wishes_special_requests'),
                'autocomplete' => '',
                'type' => 'textarea',
                'enabled' => true,
                'required' => false,
            ],
        ];
    }

    public function required_keys(): array
    {
        $required = [];
        foreach ($this->fields() as $key => $field) {
            if (!empty($field['enabled']) && !empty($field['required'])) {
                $required[] = $key;
            }
        }
        return $required;
    }
}
