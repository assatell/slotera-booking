<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class PaymentMethodService
{
    private SettingsRepository $settings;

    public function __construct(?SettingsRepository $settings = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
    }

    /** @return array<string,PaymentMethod> */
    public function enabled_methods(): array
    {
        $settings = $this->settings->all();
        $ids = array_filter(array_map('sanitize_key', preg_split('/[\s,]+/', (string) ($settings['payment_enabled_gateways'] ?? '')) ?: []));
        $methods = [];
        foreach ($ids as $id) {
            $method = $this->method($id, $settings);
            if ($method) { $methods[$id] = $method; }
        }
        return $methods;
    }

    public function method_exists(string $id): bool
    {
        $id = sanitize_key($id);
        if ($id === '') { return false; }
        return isset($this->enabled_methods()[$id]);
    }

    private function method(string $id, array $settings): ?PaymentMethod
    {
        if ($id === 'manual') {
            return new PaymentMethod('manual', (string) ($settings['payment_manual_title'] ?? 'Pay on arrival'), (string) ($settings['payment_manual_instructions'] ?? ''));
        }
        if ($id === 'bank_transfer') {
            return new PaymentMethod('bank_transfer', (string) ($settings['payment_bank_transfer_title'] ?? 'Bank transfer'), (string) ($settings['payment_bank_transfer_instructions'] ?? ''));
        }
        if ($id === 'stripe') {
            $stripe = new StripeGatewayService($this->settings);
            $label = (string) ($settings['payment_stripe_title'] ?? 'Card');
            return new PaymentMethod('stripe', $label !== '' ? $label : 'Card', '', $stripe->is_test_mode());
        }

        if ($id === 'apple_pay') {
            if (empty($settings['payment_stripe_apple_pay_enabled'])) { return null; }
            $stripe = new StripeGatewayService($this->settings);
            $label = (string) ($settings['payment_stripe_apple_pay_title'] ?? 'Apple Pay');
            return new PaymentMethod('apple_pay', $label !== '' ? $label : 'Apple Pay', '', $stripe->is_test_mode());
        }
        if ($id === 'google_pay') {
            if (empty($settings['payment_stripe_google_pay_enabled'])) { return null; }
            $stripe = new StripeGatewayService($this->settings);
            $label = (string) ($settings['payment_stripe_google_pay_title'] ?? 'Google Pay');
            return new PaymentMethod('google_pay', $label !== '' ? $label : 'Google Pay', '', $stripe->is_test_mode());
        }
        if ($id === 'paypal') {
            $paypal = new PayPalGatewayService($this->settings);
            $label = (string) ($settings['payment_paypal_title'] ?? 'PayPal');
            return new PaymentMethod('paypal', $label !== '' ? $label : 'PayPal', '', $paypal->is_test_mode());
        }
        if ($id === 'mollie') {
            $mollie = new MollieGatewayService($this->settings);
            $label = (string) ($settings['payment_mollie_title'] ?? 'Mollie Checkout');
            return new PaymentMethod('mollie', $label !== '' ? $label : 'Mollie Checkout', '', $mollie->is_test_mode());
        }
        if (strpos($id, 'custom_') === 0) {
            foreach ((array) ($settings['payment_custom_methods'] ?? []) as $method) {
                if (is_array($method) && sanitize_key((string) ($method['id'] ?? '')) === $id) {
                    return new PaymentMethod($id, (string) ($method['title'] ?? $id), (string) ($method['instructions'] ?? ''));
                }
            }
        }
        return null;
    }
}
