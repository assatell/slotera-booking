<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

if (!defined('ABSPATH')) { exit; }

final class PaymentInvoicesPage
{
    public function render(): void
    {
        $query = [];
        foreach ($_GET as $key => $value) {
            if ($key === 'page' || !is_scalar($value)) { continue; }
            $query[sanitize_key((string) $key)] = sanitize_text_field((string) wp_unslash($value));
        }
        $query['page'] = 'slotera-payments';
        $query['sltr_payment_tab'] = 'invoices';
        wp_safe_redirect(add_query_arg($query, admin_url('admin.php')));
        exit;
    }
}
