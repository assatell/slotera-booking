<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\PaymentMethodService;
use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Infrastructure\Repositories\PaymentTransactionRepository;
use Slotera\Infrastructure\Repositories\PaymentInvoiceRepository;

if (!defined('ABSPATH')) { exit; }

final class PaymentsPage
{
    private RequestValidator $request;
    private SettingsRepository $settings;

    public function __construct(?RequestValidator $request = null, ?SettingsRepository $settings = null)
    {
        $this->request = $request ?: new RequestValidator();
        $this->settings = $settings ?: new SettingsRepository();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $tab = sanitize_key((string) ($_GET['sltr_payment_tab'] ?? 'settings'));
        if (!in_array($tab, ['settings', 'transactions', 'invoices'], true)) {
            $tab = 'settings';
        }

        if ($tab === 'transactions') {
            $filters = [
                'status' => sanitize_key((string) ($_GET['sltr_payment_status'] ?? 'all')),
                'gateway' => sanitize_key((string) ($_GET['sltr_payment_gateway'] ?? 'all')),
                'search' => sanitize_text_field((string) wp_unslash($_GET['s'] ?? '')),
            ];
            $paged = max(1, absint($_GET['paged'] ?? 1));
            $per_page = 25;
            $offset = ($paged - 1) * $per_page;
            $repository = new PaymentTransactionRepository();
            $transactions = $repository->search($filters, $per_page, $offset);
            $transactions_total = $repository->count($filters);
            $transaction_totals = $repository->totals();
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/payment-transactions.php';
            return;
        }

        if ($tab === 'invoices') {
            $filters = [
                'status' => sanitize_key((string) ($_GET['sltr_invoice_status'] ?? 'all')),
                'search' => sanitize_text_field((string) wp_unslash($_GET['s'] ?? '')),
            ];
            $paged = max(1, absint($_GET['paged'] ?? 1));
            $per_page = 25;
            $offset = ($paged - 1) * $per_page;
            $repository = new PaymentInvoiceRepository();
            $invoices = $repository->search($filters, $per_page, $offset);
            $invoices_total = $repository->count($filters);
            $invoice_totals = $repository->totals();
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/payment-invoices.php';
            return;
        }

        $settings = $this->settings->all();
        $methods = (new PaymentMethodService($this->settings))->enabled_methods();
        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/payments.php';
    }
}
