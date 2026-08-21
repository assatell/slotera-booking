<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Application\Services\PaymentService;
use Slotera\Application\Services\InvoiceService;
use Slotera\Application\Services\PayPalGatewayService;

if (!defined('ABSPATH')) { exit; }

final class PaymentsController
{
    private RequestValidator $request;
    private SettingsRepository $settings;

    public function __construct(?RequestValidator $request = null, ?SettingsRepository $settings = null)
    {
        $this->request = $request ?: new RequestValidator();
        $this->settings = $settings ?: new SettingsRepository();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_payment_settings', [$this, 'save']);
        add_action('admin_post_sltr_refund_payment_transaction', [$this, 'refund_transaction']);
        add_action('admin_post_sltr_download_invoice_pdf', [$this, 'download_invoice_pdf']);
        add_action('admin_post_sltr_sync_booking_invoice', [$this, 'sync_booking_invoice']);
        add_action('admin_post_sltr_paypal_reconcile_now', [$this, 'paypal_reconcile_now']);
    }




    public function paypal_reconcile_now(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_TOOLS);
        check_admin_referer('sltr_paypal_reconcile_now');
        $summary = (new PayPalGatewayService())->reconcile_processing_payments();
        $redirect = add_query_arg([
            'page' => 'slotera-diagnostics',
            'tab' => 'diagnostics',
            'sltr_paypal_reconciled' => '1',
            'checked' => absint($summary['checked'] ?? 0),
            'completed' => absint($summary['completed'] ?? 0),
            'pending' => absint($summary['pending'] ?? 0),
            'failed' => absint($summary['failed'] ?? 0),
            'errors' => absint($summary['errors'] ?? 0),
        ], admin_url('admin.php'));
        wp_safe_redirect($redirect);
        exit;
    }

    public function download_invoice_pdf(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PAYMENTS);
        $invoice_id = absint($_GET['invoice_id'] ?? 0);
        if ($invoice_id <= 0) { wp_die(esc_html__('Invalid invoice.', 'slotera-booking')); }
        check_admin_referer('sltr_download_invoice_pdf_' . $invoice_id);
        (new InvoiceService())->stream_pdf($invoice_id);
    }

    public function sync_booking_invoice(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PAYMENTS);
        $booking_id = absint($_GET['booking_id'] ?? 0);
        if ($booking_id <= 0) { wp_die(esc_html__('Invalid booking.', 'slotera-booking')); }
        check_admin_referer('sltr_sync_booking_invoice_' . $booking_id);
        (new InvoiceService())->sync_for_booking($booking_id);
        wp_safe_redirect(add_query_arg('sltr_invoice_synced', '1', admin_url('admin.php?page=slotera-payments&sltr_payment_tab=invoices')));
        exit;
    }

    public function refund_transaction(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PAYMENTS);
        $transaction_id = $this->request->post_int('transaction_id');
        if ($transaction_id <= 0) { wp_die(esc_html__('Invalid payment transaction.', 'slotera-booking')); }
        $this->request->verify_admin_nonce('sltr_refund_payment_transaction_' . $transaction_id);

        $amount = $this->request->post_float('refund_amount', 0.0);
        $reason = $this->request->post_key('refund_reason', 'requested_by_customer');
        $result = (new PaymentService())->refund_transaction($transaction_id, $amount, $reason);

        $redirect = wp_get_referer();
        if (!$redirect) { $redirect = admin_url('admin.php?page=slotera-payments&sltr_payment_tab=transactions'); }
        $redirect = remove_query_arg(['sltr_refund_error', 'sltr_refund_done'], $redirect);
        if (is_wp_error($result)) {
            $redirect = add_query_arg('sltr_refund_error', rawurlencode($result->get_error_message()), $redirect);
        } else {
            $redirect = add_query_arg('sltr_refund_done', '1', $redirect);
        }
        wp_safe_redirect($redirect);
        exit;
    }

    public function save(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PAYMENTS);
        $section = $this->request->post_key('payment_settings_section', '');
        $allowed_sections = ['general', 'stripe', 'paypal', 'mollie', 'methods', 'custom'];
        if (!in_array($section, $allowed_sections, true)) {
            wp_die(esc_html__('Invalid payment settings section.', 'slotera-booking'));
        }
        $this->request->verify_admin_nonce('sltr_save_payment_settings_' . $section);

        $current = $this->settings->all();
        $data = [];

        if ($section === 'general') {
            $data = [
                'payment_currency' => $this->request->post_text('payment_currency', 'EUR'),
                'payment_currency_position' => $this->request->post_key('payment_currency_position', 'right_space'),
                'payment_decimal_separator' => $this->request->post_text('payment_decimal_separator', '.'),
                'payment_thousands_separator' => $this->request->post_text('payment_thousands_separator', ' '),
                'payment_tax_enabled' => $this->request->post_bool('payment_tax_enabled'),
                'payment_tax_label' => $this->request->post_text('payment_tax_label', 'VAT'),
                'payment_tax_rate' => $this->request->post_float('payment_tax_rate', 0.0),
                'payment_tax_mode' => $this->request->post_key('payment_tax_mode', 'exclusive'),
                'payment_pay_on_arrival_enabled' => $this->request->post_bool('payment_pay_on_arrival_enabled'),
                'payment_mode_enabled' => $this->request->post_bool('payment_mode_enabled'),
                'prepayment_mode_enabled' => $this->request->post_bool('prepayment_mode_enabled'),
                'invoice_pdf_enabled' => $this->request->post_bool('invoice_pdf_enabled'),
                'invoice_pdf_brand_name' => $this->request->post_text('invoice_pdf_brand_name', get_bloginfo('name')),
                'invoice_pdf_footer_text' => $this->request->post_text('invoice_pdf_footer_text', ''),
            ];
        } elseif ($section === 'stripe') {
            $data = [
                'payment_stripe_mode' => $this->request->post_key('payment_stripe_mode', 'test'),
                'payment_stripe_title' => $this->request->post_text('payment_stripe_title', 'Card'),
                'payment_stripe_google_pay_enabled' => $this->request->post_bool('payment_stripe_google_pay_enabled'),
                'payment_stripe_google_pay_title' => $this->request->post_text('payment_stripe_google_pay_title', 'Google Pay'),
                'payment_stripe_apple_pay_enabled' => $this->request->post_bool('payment_stripe_apple_pay_enabled'),
                'payment_stripe_apple_pay_title' => $this->request->post_text('payment_stripe_apple_pay_title', 'Apple Pay'),
                'payment_stripe_test_publishable_key' => $this->request->post_text('payment_stripe_test_publishable_key'),
                'payment_stripe_test_secret_key' => $this->preserve_or_replace_secret('payment_stripe_test_secret_key', $current),
                'payment_stripe_live_publishable_key' => $this->request->post_text('payment_stripe_live_publishable_key'),
                'payment_stripe_live_secret_key' => $this->preserve_or_replace_secret('payment_stripe_live_secret_key', $current),
                'payment_stripe_webhook_secret' => $this->preserve_or_replace_secret('payment_stripe_webhook_secret', $current),
            ];
            $data['payment_enabled_gateways'] = $this->with_gateway_enabled(
                $this->current_gateways($current),
                'stripe',
                $this->request->post_bool('payment_stripe_enabled')
            );
        } elseif ($section === 'paypal') {
            $data = [
                'payment_paypal_mode' => $this->request->post_key('payment_paypal_mode', 'sandbox'),
                'payment_paypal_title' => $this->request->post_text('payment_paypal_title', 'PayPal'),
                'payment_paypal_sandbox_client_id' => $this->request->post_text('payment_paypal_sandbox_client_id'),
                'payment_paypal_sandbox_client_secret' => $this->preserve_or_replace_secret('payment_paypal_sandbox_client_secret', $current),
                'payment_paypal_live_client_id' => $this->request->post_text('payment_paypal_live_client_id'),
                'payment_paypal_live_client_secret' => $this->preserve_or_replace_secret('payment_paypal_live_client_secret', $current),
                'payment_paypal_webhook_id' => $this->request->post_text('payment_paypal_webhook_id'),
            ];
            $data['payment_enabled_gateways'] = $this->with_gateway_enabled(
                $this->current_gateways($current),
                'paypal',
                $this->request->post_bool('payment_paypal_enabled')
            );
        } elseif ($section === 'mollie') {
            $data = [
                'payment_mollie_title' => $this->request->post_text('payment_mollie_title', 'Mollie Checkout'),
                'payment_mollie_mode' => $this->request->post_key('payment_mollie_mode', 'test'),
                'payment_mollie_test_api_key' => $this->preserve_or_replace_secret('payment_mollie_test_api_key', $current),
                'payment_mollie_live_api_key' => $this->preserve_or_replace_secret('payment_mollie_live_api_key', $current),
                'payment_mollie_method' => $this->request->post_key('payment_mollie_method', 'all'),
            ];
        } elseif ($section === 'methods') {
            $gateways = $this->sanitize_gateways($this->request->post_raw_array('payment_enabled_gateways'));
            foreach ($this->current_gateways($current) as $gateway) {
                if ((strpos($gateway, 'custom_') === 0 || in_array($gateway, ['stripe', 'paypal'], true)) && !in_array($gateway, $gateways, true)) {
                    $gateways[] = $gateway;
                }
            }
            $data = [
                'payment_enabled_gateways' => array_values(array_unique($gateways)),
                'payment_manual_title' => $this->request->post_text('payment_manual_title', 'Pay on arrival'),
                'payment_manual_instructions' => $this->request->post_textarea('payment_manual_instructions'),
                'payment_bank_transfer_title' => $this->request->post_text('payment_bank_transfer_title', 'Bank transfer'),
                'payment_bank_transfer_instructions' => $this->request->post_textarea('payment_bank_transfer_instructions'),
            ];
        } elseif ($section === 'custom') {
            $custom_methods = [];
            $custom_seen = [];
            foreach ($this->request->post_raw_array('payment_custom_methods') as $row) {
                if (!is_array($row)) { continue; }
                $slug = sanitize_title((string) ($row['slug'] ?? ''));
                $title = sanitize_text_field((string) ($row['title'] ?? ''));
                $instructions = sanitize_textarea_field((string) ($row['instructions'] ?? ''));
                if ($slug === '' || $title === '' || isset($custom_seen[$slug])) { continue; }
                $custom_seen[$slug] = true;
                $custom_methods[] = [
                    'id' => 'custom_' . str_replace('-', '_', $slug),
                    'slug' => $slug,
                    'title' => $title,
                    'instructions' => $instructions,
                ];
            }
            $standard_gateways = array_values(array_filter(
                $this->current_gateways($current),
                static fn(string $gateway): bool => strpos($gateway, 'custom_') !== 0
            ));
            foreach ($custom_methods as $method) {
                $standard_gateways[] = (string) $method['id'];
            }
            $data = [
                'payment_custom_methods' => $custom_methods,
                'payment_enabled_gateways' => array_values(array_unique($standard_gateways)),
            ];
        }

        $this->settings->update($data);
        wp_safe_redirect(add_query_arg([
            'page' => 'slotera-payments',
            'sltr_message' => 'saved',
            'sltr_saved_section' => $section,
        ], admin_url('admin.php')));
        exit;
    }

    private function current_gateways(array $current): array
    {
        $raw = $current['payment_enabled_gateways'] ?? '';
        $values = is_array($raw) ? $raw : (preg_split('/[\s,]+/', (string) $raw) ?: []);
        return $this->sanitize_gateways($values);
    }

    /** @param array<int|string,mixed> $gateways */
    private function sanitize_gateways(array $gateways): array
    {
        $allowed = ['manual', 'bank_transfer', 'stripe', 'apple_pay', 'google_pay', 'paypal', 'mollie'];
        return array_values(array_unique(array_filter(array_map('sanitize_key', $gateways), static function (string $gateway) use ($allowed): bool {
            return in_array($gateway, $allowed, true) || strpos($gateway, 'custom_') === 0;
        })));
    }

    /** @param array<int,string> $gateways */
    private function with_gateway_enabled(array $gateways, string $gateway, bool|int $enabled): array
    {
        $enabled = (bool) $enabled;
        $gateways = array_values(array_filter($gateways, static fn(string $value): bool => $value !== $gateway));
        if ($enabled) {
            $gateways[] = $gateway;
        }
        return array_values(array_unique($gateways));
    }

    private function preserve_or_replace_secret(string $key, array $current): string
    {
        if ($this->request->post_truthy($key . '_clear')) {
            return '';
        }
        $posted = $this->request->post_raw($key, '');
        if (is_array($posted)) {
            return (string) ($current[$key] ?? '');
        }
        $posted = (string) preg_replace('/[\x00\r\n]/', '', (string) $posted);
        return $posted === '' ? (string) ($current[$key] ?? '') : $posted;
    }
}
