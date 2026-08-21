<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\PaymentInvoiceRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class InvoiceService
{
    private BookingRepository $bookings;
    private PackageRepository $packages;
    private PaymentInvoiceRepository $invoices;
    private SettingsRepository $settings;

    public function __construct(?BookingRepository $bookings = null, ?PackageRepository $packages = null, ?PaymentInvoiceRepository $invoices = null, ?SettingsRepository $settings = null)
    {
        $this->bookings = $bookings ?: new BookingRepository();
        $this->packages = $packages ?: new PackageRepository();
        $this->invoices = $invoices ?: new PaymentInvoiceRepository();
        $this->settings = $settings ?: new SettingsRepository();
    }

    public function sync_for_booking(int $booking_id): int
    {
        $booking = $this->bookings->get_by_id($booking_id);
        if (!$booking) { return 0; }
        $settings = $this->settings->all();
        $total = (float) ($booking['gross_amount'] ?? $booking['total_amount'] ?? 0);
        $tax = (float) ($booking['tax_amount'] ?? 0);
        $subtotal = round(max(0, $total - $tax), 2);
        $paid = (float) ($booking['paid_amount'] ?? 0);
        $remaining = (float) ($booking['remaining_amount'] ?? max(0, $total - $paid));
        $payment_status = sanitize_key((string) ($booking['payment_status'] ?? 'unpaid'));
        $status = 'unpaid';
        if (in_array($payment_status, ['paid'], true) || $remaining <= 0.009) { $status = 'paid'; }
        elseif (in_array($payment_status, ['partial','partially_refunded'], true) || $paid > 0) { $status = 'partially_paid'; }
        elseif (in_array($payment_status, ['refunded'], true)) { $status = 'refunded'; }
        elseif (in_array($payment_status, ['failed','cancelled'], true)) { $status = 'unpaid'; }

        return $this->invoices->upsert_for_booking($booking_id, [
            'customer_email' => (string) ($booking['customer_email'] ?? ''),
            'customer_name' => (string) ($booking['customer_name'] ?? ''),
            'status' => $status,
            'currency' => CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR')),
            'subtotal' => $subtotal,
            'tax_label' => (string) ($settings['payment_tax_label'] ?? 'VAT'),
            'tax_rate' => (float) ($settings['payment_tax_rate'] ?? 0),
            'tax_amount' => $tax,
            'total' => $total,
            'paid' => $paid,
            'remaining' => $remaining,
            'issue_date' => current_time('Y-m-d'),
            'due_date' => $remaining > 0 ? gmdate('Y-m-d', strtotime('+14 days')) : null,
            'metadata' => ['payment_status' => $payment_status, 'payment_gateway' => (string) ($booking['payment_gateway'] ?? '')],
        ]);
    }

    public function stream_pdf(int $invoice_id): void
    {
        $invoice = $this->invoices->get($invoice_id);
        if (!$invoice) { wp_die(esc_html__('Invoice not found.', 'slotera-booking')); }
        $booking = $this->bookings->get_by_id((int) ($invoice['booking_id'] ?? 0));
        if (!$booking) { wp_die(esc_html__('Booking not found.', 'slotera-booking')); }
        $package = $this->packages->get_by_id((int) ($booking['package_id'] ?? 0)) ?: [];
        (new PdfInvoiceService($this->settings))->stream($booking + ['invoice_number' => (string) ($invoice['invoice_number'] ?? '')], $package);
        exit;
    }
}
