<?php if (!defined('ABSPATH')) { exit; }
$enabled = array_filter(array_map('sanitize_key', preg_split('/[\s,]+/', (string) ($settings['payment_enabled_gateways'] ?? '')) ?: []));
$custom_methods = is_array($settings['payment_custom_methods'] ?? null) ? $settings['payment_custom_methods'] : [];
$sltr_currencies = \Slotera\Application\Services\CurrencyService::currencies();
$sltr_current_currency = \Slotera\Application\Services\CurrencyService::normalize((string) ($settings['payment_currency'] ?? 'EUR'));
$sltr_current_position = \Slotera\Application\Services\CurrencyService::normalize_position((string) ($settings['payment_currency_position'] ?? 'right_space'));
$custom_seen = [];
$custom_methods = array_values(array_filter($custom_methods, static function ($method) use (&$custom_seen) {
    if (!is_array($method)) { return false; }
    $slug = sanitize_title((string) ($method['slug'] ?? str_replace('custom_', '', (string) ($method['id'] ?? ''))));
    $title = trim((string) ($method['title'] ?? ''));
    if ($slug === '' || $title === '') { return false; }
    if (isset($custom_seen[$slug])) { return false; }
    $custom_seen[$slug] = true;
    return true;
}));
?>
<div class="wrap sltr-admin-wrap sltr-payments-page sltr-pro-feature-page sltr-full-width-admin">
    <h1><?php esc_html_e('Payments', 'slotera-booking'); ?></h1>
    <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
        <a class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments')); ?>"><?php esc_html_e('Payment Settings', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $tab === 'transactions' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments&sltr_payment_tab=transactions')); ?>"><?php esc_html_e('Transactions', 'slotera-booking'); ?></a>
        <a class="nav-tab <?php echo $tab === 'invoices' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=slotera-payments&sltr_payment_tab=invoices')); ?>"><?php esc_html_e('Invoices', 'slotera-booking'); ?></a>
    </nav>
    <?php if (isset($_GET['sltr_message']) && sanitize_key((string) wp_unslash($_GET['sltr_message'])) === 'saved') : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Payment settings saved.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>

    <section class="sltr-settings-card sltr-payment-settings-block sltr-payment-settings-block--general">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-settings-form">
            <input type="hidden" name="action" value="sltr_save_payment_settings">
            <input type="hidden" name="payment_settings_section" value="general">
            <?php wp_nonce_field('sltr_save_payment_settings_general'); ?>
        <h2><?php esc_html_e('General checkout', 'slotera-booking'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="payment_currency"><?php esc_html_e('Currency', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="payment_currency" name="payment_currency" class="regular-text">
                        <?php foreach ($sltr_currencies as $code => $currency_data) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($sltr_current_currency, $code); ?>><?php echo esc_html($code . ' — ' . $currency_data['name'] . ' (' . $currency_data['symbol'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Choose the ISO 4217 currency used for prices, checkout, payments, invoices and emails.', 'slotera-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_currency_position"><?php esc_html_e('Currency position', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="payment_currency_position" name="payment_currency_position">
                        <?php foreach (['left' => 'Left', 'right' => 'Right', 'left_space' => 'Left with space', 'right_space' => 'Right with space'] as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($sltr_current_position, $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_decimal_separator"><?php esc_html_e('Decimal separator', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="payment_decimal_separator" name="payment_decimal_separator">
                        <option value="." <?php selected((string) ($settings['payment_decimal_separator'] ?? '.'), '.'); ?>>1,234.56</option>
                        <option value="," <?php selected((string) ($settings['payment_decimal_separator'] ?? '.'), ','); ?>>1 234,56</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_thousands_separator"><?php esc_html_e('Thousands separator', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="payment_thousands_separator" name="payment_thousands_separator">
                        <option value=" " <?php selected((string) ($settings['payment_thousands_separator'] ?? ' '), ' '); ?>><?php esc_html_e('Space', 'slotera-booking'); ?></option>
                        <option value="," <?php selected((string) ($settings['payment_thousands_separator'] ?? ' '), ','); ?>><?php esc_html_e('Comma', 'slotera-booking'); ?></option>
                        <option value="." <?php selected((string) ($settings['payment_thousands_separator'] ?? ' '), '.'); ?>><?php esc_html_e('Dot', 'slotera-booking'); ?></option>
                        <option value="" <?php selected((string) ($settings['payment_thousands_separator'] ?? ' '), ''); ?>><?php esc_html_e('None', 'slotera-booking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Global checkout modes', 'slotera-booking'); ?></th>
                <td>
                    <label><input type="checkbox" name="payment_pay_on_arrival_enabled" value="1" <?php checked((int) ($settings['payment_pay_on_arrival_enabled'] ?? 1), 1); ?>> <?php esc_html_e('Allow pay on arrival / booking only', 'slotera-booking'); ?></label><br>
                    <label><input type="checkbox" name="payment_mode_enabled" value="1" <?php checked((int) ($settings['payment_mode_enabled'] ?? 0), 1); ?>> <?php esc_html_e('Allow full payment option', 'slotera-booking'); ?></label><br>
                    <label><input type="checkbox" name="prepayment_mode_enabled" value="1" <?php checked((int) ($settings['prepayment_mode_enabled'] ?? 0), 1); ?>> <?php esc_html_e('Allow deposit / prepayment option', 'slotera-booking'); ?></label>
                    <p class="description"><?php esc_html_e('Package-level payment options still decide what customers see for each service.', 'slotera-booking'); ?></p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Taxes / VAT', 'slotera-booking'); ?></h2>
        <p class="description"><?php esc_html_e('Configure global tax/VAT rules for booking totals. Package-level tax settings still override these defaults when configured.', 'slotera-booking'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable global tax/VAT', 'slotera-booking'); ?></th>
                <td>
                    <label><input type="checkbox" name="payment_tax_enabled" value="1" <?php checked((int) ($settings['payment_tax_enabled'] ?? 0), 1); ?>> <?php esc_html_e('Apply tax/VAT to booking totals by default', 'slotera-booking'); ?></label>
                    <p class="description"><?php esc_html_e('Use this when most services use the same VAT/tax rule. Disable it if every package controls tax separately.', 'slotera-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_tax_label"><?php esc_html_e('Tax label', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="payment_tax_label" name="payment_tax_label" value="<?php echo esc_attr((string) ($settings['payment_tax_label'] ?? 'VAT')); ?>" placeholder="VAT"></td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_tax_rate"><?php esc_html_e('Tax rate', 'slotera-booking'); ?></label></th>
                <td><input type="number" min="0" max="100" step="0.01" id="payment_tax_rate" name="payment_tax_rate" value="<?php echo esc_attr((string) ($settings['payment_tax_rate'] ?? 0)); ?>" style="width:120px;"> %</td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_tax_mode"><?php esc_html_e('Price mode', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="payment_tax_mode" name="payment_tax_mode">
                        <option value="exclusive" <?php selected((string) ($settings['payment_tax_mode'] ?? 'exclusive'), 'exclusive'); ?>><?php esc_html_e('Added on top of price', 'slotera-booking'); ?></option>
                        <option value="inclusive" <?php selected((string) ($settings['payment_tax_mode'] ?? 'exclusive'), 'inclusive'); ?>><?php esc_html_e('Included in price', 'slotera-booking'); ?></option>
                    </select>
                </td>
            </tr>
        </table>



        <h2><?php esc_html_e('Invoices', 'slotera-booking'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('PDF invoices', 'slotera-booking'); ?></th>
                <td>
                    <label><input type="checkbox" name="invoice_pdf_enabled" value="1" <?php checked((int) ($settings['invoice_pdf_enabled'] ?? 1), 1); ?>> <?php esc_html_e('Enable PDF invoice downloads', 'slotera-booking'); ?></label>
                    <p class="description"><?php esc_html_e('Invoices are generated automatically from booking payment data and can be downloaded from Payments → Invoices.', 'slotera-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="invoice_pdf_brand_name"><?php esc_html_e('PDF brand name', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="invoice_pdf_brand_name" name="invoice_pdf_brand_name" value="<?php echo esc_attr((string) ($settings['invoice_pdf_brand_name'] ?? get_bloginfo('name'))); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="invoice_pdf_footer_text"><?php esc_html_e('PDF footer text', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="invoice_pdf_footer_text" name="invoice_pdf_footer_text" value="<?php echo esc_attr((string) ($settings['invoice_pdf_footer_text'] ?? '')); ?>" placeholder="<?php esc_attr_e('Optional', 'slotera-booking'); ?>"></td>
            </tr>
        </table>


            <p class="submit sltr-payment-settings-submit"><button type="submit" class="button button-primary"><?php esc_html_e('Save general checkout', 'slotera-booking'); ?></button></p>
        </form>
    </section>
    <section class="sltr-settings-card sltr-payment-settings-block sltr-payment-settings-block--stripe">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-settings-form">
            <input type="hidden" name="action" value="sltr_save_payment_settings">
            <input type="hidden" name="payment_settings_section" value="stripe">
            <?php wp_nonce_field('sltr_save_payment_settings_stripe'); ?>
        <h2><?php esc_html_e('Stripe Checkout', 'slotera-booking'); ?></h2>
        <p class="description"><?php esc_html_e('Use Stripe Checkout for online card payments. Add both test and live keys, then switch mode when you are ready.', 'slotera-booking'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="payment_stripe_title"><?php esc_html_e('Method title', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="payment_stripe_title" name="payment_stripe_title" value="<?php echo esc_attr((string) ($settings['payment_stripe_title'] ?? 'Card')); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Payment method', 'slotera-booking'); ?></th>
                <td>
                    <label><input type="checkbox" name="payment_stripe_enabled" value="1" <?php checked(in_array('stripe', $enabled, true)); ?>> <?php esc_html_e('Enable Card', 'slotera-booking'); ?></label>
                    <p class="description"><?php esc_html_e('Show Card as a customer payment choice and process it through Stripe Checkout.', 'slotera-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Wallet payments', 'slotera-booking'); ?></th>
                <td>
                    <label><input type="checkbox" name="payment_stripe_apple_pay_enabled" value="1" <?php checked((int) ($settings['payment_stripe_apple_pay_enabled'] ?? 1), 1); ?>> <?php esc_html_e('Enable Apple Pay button', 'slotera-booking'); ?></label><br>
                    <input class="regular-text" name="payment_stripe_apple_pay_title" value="<?php echo esc_attr((string) ($settings['payment_stripe_apple_pay_title'] ?? 'Apple Pay')); ?>" placeholder="Apple Pay"><br><br>
                    <label><input type="checkbox" name="payment_stripe_google_pay_enabled" value="1" <?php checked((int) ($settings['payment_stripe_google_pay_enabled'] ?? 1), 1); ?>> <?php esc_html_e('Enable Google Pay button', 'slotera-booking'); ?></label><br>
                    <input class="regular-text" name="payment_stripe_google_pay_title" value="<?php echo esc_attr((string) ($settings['payment_stripe_google_pay_title'] ?? 'Google Pay')); ?>" placeholder="Google Pay">
                    <p class="description"><?php esc_html_e('Apple Pay and Google Pay are shown as separate customer choices, but are processed securely through Stripe Checkout. Stripe will only display wallets that are available on the customer device/browser.', 'slotera-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_stripe_mode"><?php esc_html_e('Mode', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="payment_stripe_mode" name="payment_stripe_mode">
                        <option value="test" <?php selected((string) ($settings['payment_stripe_mode'] ?? 'test'), 'test'); ?>><?php esc_html_e('Test', 'slotera-booking'); ?></option>
                        <option value="live" <?php selected((string) ($settings['payment_stripe_mode'] ?? 'test'), 'live'); ?>><?php esc_html_e('Live', 'slotera-booking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_stripe_test_publishable_key"><?php esc_html_e('Test publishable key', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="payment_stripe_test_publishable_key" name="payment_stripe_test_publishable_key" value="<?php echo esc_attr((string) ($settings['payment_stripe_test_publishable_key'] ?? '')); ?>" placeholder="pk_test_..."></td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_stripe_test_secret_key"><?php esc_html_e('Test secret key', 'slotera-booking'); ?></label></th>
                <td>
                    <input class="regular-text" type="password" id="payment_stripe_test_secret_key" name="payment_stripe_test_secret_key" value="" placeholder="<?php echo !empty($settings['payment_stripe_test_secret_key']) ? esc_attr__('Saved — enter a new value to replace', 'slotera-booking') : 'sk_test_...'; ?>" autocomplete="new-password">
                    <?php if (!empty($settings['payment_stripe_test_secret_key'])) : ?><label><input type="checkbox" name="payment_stripe_test_secret_key_clear" value="1"> <?php esc_html_e('Clear saved secret', 'slotera-booking'); ?></label><?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_stripe_live_publishable_key"><?php esc_html_e('Live publishable key', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="payment_stripe_live_publishable_key" name="payment_stripe_live_publishable_key" value="<?php echo esc_attr((string) ($settings['payment_stripe_live_publishable_key'] ?? '')); ?>" placeholder="pk_live_..."></td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_stripe_live_secret_key"><?php esc_html_e('Live secret key', 'slotera-booking'); ?></label></th>
                <td>
                    <input class="regular-text" type="password" id="payment_stripe_live_secret_key" name="payment_stripe_live_secret_key" value="" placeholder="<?php echo !empty($settings['payment_stripe_live_secret_key']) ? esc_attr__('Saved — enter a new value to replace', 'slotera-booking') : 'sk_live_...'; ?>" autocomplete="new-password">
                    <?php if (!empty($settings['payment_stripe_live_secret_key'])) : ?><label><input type="checkbox" name="payment_stripe_live_secret_key_clear" value="1"> <?php esc_html_e('Clear saved secret', 'slotera-booking'); ?></label><?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_stripe_webhook_secret"><?php esc_html_e('Webhook signing secret', 'slotera-booking'); ?></label></th>
                <td>
                    <input class="regular-text" type="password" id="payment_stripe_webhook_secret" name="payment_stripe_webhook_secret" value="" placeholder="<?php echo !empty($settings['payment_stripe_webhook_secret']) ? esc_attr__('Saved — enter a new value to replace', 'slotera-booking') : 'whsec_...'; ?>" autocomplete="new-password">
                    <?php if (!empty($settings['payment_stripe_webhook_secret'])) : ?><label><input type="checkbox" name="payment_stripe_webhook_secret_clear" value="1"> <?php esc_html_e('Clear saved secret', 'slotera-booking'); ?></label><?php endif; ?>
                    <p class="description"><?php echo esc_html(sprintf(__('Webhook endpoint: %s', 'slotera-booking'), rest_url('slotera/v1/payments/stripe/webhook'))); ?></p>
                </td>
            </tr>
        </table>



            <p class="submit sltr-payment-settings-submit"><button type="submit" class="button button-primary"><?php esc_html_e('Save Stripe settings', 'slotera-booking'); ?></button></p>
        </form>
    </section>
    <section class="sltr-settings-card sltr-payment-settings-block sltr-payment-settings-block--paypal">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-settings-form">
            <input type="hidden" name="action" value="sltr_save_payment_settings">
            <input type="hidden" name="payment_settings_section" value="paypal">
            <?php wp_nonce_field('sltr_save_payment_settings_paypal'); ?>
        <h2><?php esc_html_e('PayPal Checkout', 'slotera-booking'); ?></h2>
        <p class="description"><?php esc_html_e('Use PayPal Checkout as an online payment gateway. Configure sandbox keys for testing and live keys before launch.', 'slotera-booking'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="payment_paypal_title"><?php esc_html_e('Method title', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="payment_paypal_title" name="payment_paypal_title" value="<?php echo esc_attr((string) ($settings['payment_paypal_title'] ?? 'PayPal')); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Payment method', 'slotera-booking'); ?></th>
                <td>
                    <label><input type="checkbox" name="payment_paypal_enabled" value="1" <?php checked(in_array('paypal', $enabled, true)); ?>> <?php esc_html_e('Enable PayPal Checkout', 'slotera-booking'); ?></label>
                    <p class="description"><?php esc_html_e('Show PayPal Checkout as a customer payment choice.', 'slotera-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_paypal_mode"><?php esc_html_e('Mode', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="payment_paypal_mode" name="payment_paypal_mode">
                        <option value="sandbox" <?php selected((string) ($settings['payment_paypal_mode'] ?? 'sandbox'), 'sandbox'); ?>><?php esc_html_e('Sandbox', 'slotera-booking'); ?></option>
                        <option value="live" <?php selected((string) ($settings['payment_paypal_mode'] ?? 'sandbox'), 'live'); ?>><?php esc_html_e('Live', 'slotera-booking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_paypal_sandbox_client_id"><?php esc_html_e('Sandbox client ID', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="payment_paypal_sandbox_client_id" name="payment_paypal_sandbox_client_id" value="<?php echo esc_attr((string) ($settings['payment_paypal_sandbox_client_id'] ?? '')); ?>" autocomplete="off"></td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_paypal_sandbox_client_secret"><?php esc_html_e('Sandbox client secret', 'slotera-booking'); ?></label></th>
                <td>
                    <input class="regular-text" type="password" id="payment_paypal_sandbox_client_secret" name="payment_paypal_sandbox_client_secret" value="" placeholder="<?php echo !empty($settings['payment_paypal_sandbox_client_secret']) ? esc_attr__('Saved — enter a new value to replace', 'slotera-booking') : esc_attr__('Enter sandbox client secret', 'slotera-booking'); ?>" autocomplete="new-password">
                    <?php if (!empty($settings['payment_paypal_sandbox_client_secret'])) : ?><label><input type="checkbox" name="payment_paypal_sandbox_client_secret_clear" value="1"> <?php esc_html_e('Clear saved secret', 'slotera-booking'); ?></label><?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_paypal_live_client_id"><?php esc_html_e('Live client ID', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="payment_paypal_live_client_id" name="payment_paypal_live_client_id" value="<?php echo esc_attr((string) ($settings['payment_paypal_live_client_id'] ?? '')); ?>" autocomplete="off"></td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_paypal_live_client_secret"><?php esc_html_e('Live client secret', 'slotera-booking'); ?></label></th>
                <td>
                    <input class="regular-text" type="password" id="payment_paypal_live_client_secret" name="payment_paypal_live_client_secret" value="" placeholder="<?php echo !empty($settings['payment_paypal_live_client_secret']) ? esc_attr__('Saved — enter a new value to replace', 'slotera-booking') : esc_attr__('Enter live client secret', 'slotera-booking'); ?>" autocomplete="new-password">
                    <?php if (!empty($settings['payment_paypal_live_client_secret'])) : ?><label><input type="checkbox" name="payment_paypal_live_client_secret_clear" value="1"> <?php esc_html_e('Clear saved secret', 'slotera-booking'); ?></label><?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_paypal_webhook_id"><?php esc_html_e('Webhook ID', 'slotera-booking'); ?></label></th>
                <td>
                    <input class="regular-text" id="payment_paypal_webhook_id" name="payment_paypal_webhook_id" value="<?php echo esc_attr((string) ($settings['payment_paypal_webhook_id'] ?? '')); ?>" autocomplete="off">
                    <p class="description"><?php echo esc_html(sprintf(__('Webhook endpoint: %s', 'slotera-booking'), rest_url('slotera/v1/payments/paypal/webhook'))); ?></p>
                </td>
            </tr>
        </table>



            <p class="submit sltr-payment-settings-submit"><button type="submit" class="button button-primary"><?php esc_html_e('Save PayPal settings', 'slotera-booking'); ?></button></p>
        </form>
    </section>
    <section class="sltr-settings-card sltr-payment-settings-block sltr-payment-settings-block--mollie">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-settings-form">
            <input type="hidden" name="action" value="sltr_save_payment_settings">
            <input type="hidden" name="payment_settings_section" value="mollie">
            <?php wp_nonce_field('sltr_save_payment_settings_mollie'); ?>
        <h2><?php esc_html_e('Mollie Checkout', 'slotera-booking'); ?></h2>
        <p class="description"><?php esc_html_e('Use Mollie for European checkout methods such as iDEAL, Bancontact, SEPA bank transfer/direct debit, EPS, Przelewy24 and Klarna. Enable individual methods in your Mollie dashboard.', 'slotera-booking'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="payment_mollie_title"><?php esc_html_e('Method title', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="payment_mollie_title" name="payment_mollie_title" value="<?php echo esc_attr((string) ($settings['payment_mollie_title'] ?? 'Mollie Checkout')); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_mollie_mode"><?php esc_html_e('Mode', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="payment_mollie_mode" name="payment_mollie_mode">
                        <option value="test" <?php selected((string) ($settings['payment_mollie_mode'] ?? 'test'), 'test'); ?>><?php esc_html_e('Test', 'slotera-booking'); ?></option>
                        <option value="live" <?php selected((string) ($settings['payment_mollie_mode'] ?? 'test'), 'live'); ?>><?php esc_html_e('Live', 'slotera-booking'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_mollie_test_api_key"><?php esc_html_e('Test API key', 'slotera-booking'); ?></label></th>
                <td>
                    <input class="regular-text" type="password" id="payment_mollie_test_api_key" name="payment_mollie_test_api_key" value="" placeholder="<?php echo !empty($settings['payment_mollie_test_api_key']) ? esc_attr__('Saved — enter a new value to replace', 'slotera-booking') : 'test_...'; ?>" autocomplete="new-password">
                    <?php if (!empty($settings['payment_mollie_test_api_key'])) : ?><label><input type="checkbox" name="payment_mollie_test_api_key_clear" value="1"> <?php esc_html_e('Clear saved secret', 'slotera-booking'); ?></label><?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_mollie_live_api_key"><?php esc_html_e('Live API key', 'slotera-booking'); ?></label></th>
                <td>
                    <input class="regular-text" type="password" id="payment_mollie_live_api_key" name="payment_mollie_live_api_key" value="" placeholder="<?php echo !empty($settings['payment_mollie_live_api_key']) ? esc_attr__('Saved — enter a new value to replace', 'slotera-booking') : 'live_...'; ?>" autocomplete="new-password">
                    <?php if (!empty($settings['payment_mollie_live_api_key'])) : ?><label><input type="checkbox" name="payment_mollie_live_api_key_clear" value="1"> <?php esc_html_e('Clear saved secret', 'slotera-booking'); ?></label><?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_mollie_method"><?php esc_html_e('Preferred method', 'slotera-booking'); ?></label></th>
                <td>
                    <select id="payment_mollie_method" name="payment_mollie_method">
                        <?php foreach (['all' => __('Hosted checkout / all enabled methods', 'slotera-booking'), 'ideal' => 'iDEAL', 'bancontact' => 'Bancontact', 'creditcard' => __('Cards', 'slotera-booking'), 'banktransfer' => __('SEPA bank transfer', 'slotera-booking'), 'directdebit' => __('SEPA direct debit', 'slotera-booking'), 'sofort' => 'SOFORT', 'eps' => 'EPS', 'p24' => 'Przelewy24', 'klarna' => 'Klarna'] as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($settings['payment_mollie_method'] ?? 'all'), $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php echo esc_html(sprintf(__('Webhook endpoint: %s', 'slotera-booking'), rest_url('slotera/v1/payments/mollie/webhook'))); ?></p>
                </td>
            </tr>
        </table>


            <p class="submit sltr-payment-settings-submit"><button type="submit" class="button button-primary"><?php esc_html_e('Save Mollie settings', 'slotera-booking'); ?></button></p>
        </form>
    </section>
    <section class="sltr-settings-card sltr-payment-settings-block sltr-payment-settings-block--methods">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-settings-form">
            <input type="hidden" name="action" value="sltr_save_payment_settings">
            <input type="hidden" name="payment_settings_section" value="methods">
            <?php wp_nonce_field('sltr_save_payment_settings_methods'); ?>
        <h2><?php esc_html_e('Payment methods', 'slotera-booking'); ?></h2>
        <p class="description"><?php esc_html_e('Choose the payment methods customers can use. Online gateways are EU-ready; regional non-EU gateway placeholders are not shown.', 'slotera-booking'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Online methods', 'slotera-booking'); ?></th>
                <td>
                    <label><input type="checkbox" name="payment_enabled_gateways[]" value="apple_pay" <?php checked(in_array('apple_pay', $enabled, true)); ?>> <?php esc_html_e('Apple Pay', 'slotera-booking'); ?></label><br>
                    <label><input type="checkbox" name="payment_enabled_gateways[]" value="google_pay" <?php checked(in_array('google_pay', $enabled, true)); ?>> <?php esc_html_e('Google Pay', 'slotera-booking'); ?></label><br>
                    <label><input type="checkbox" name="payment_enabled_gateways[]" value="mollie" <?php checked(in_array('mollie', $enabled, true)); ?>> <?php esc_html_e('Mollie Checkout (EU methods)', 'slotera-booking'); ?></label>
                    <p class="description"><?php esc_html_e('Mollie covers EU/local methods such as iDEAL, Bancontact, SEPA, EPS, Przelewy24 and Klarna where available in your Mollie account.', 'slotera-booking'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Offline methods', 'slotera-booking'); ?></th>
                <td>
                    <label><input type="checkbox" name="payment_enabled_gateways[]" value="manual" <?php checked(in_array('manual', $enabled, true)); ?>> <?php esc_html_e('Pay on arrival', 'slotera-booking'); ?></label><br>
                    <label><input type="checkbox" name="payment_enabled_gateways[]" value="bank_transfer" <?php checked(in_array('bank_transfer', $enabled, true)); ?>> <?php esc_html_e('Bank transfer', 'slotera-booking'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_manual_title"><?php esc_html_e('Pay on arrival title', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="payment_manual_title" name="payment_manual_title" value="<?php echo esc_attr((string) ($settings['payment_manual_title'] ?? 'Pay on arrival')); ?>"><br><textarea class="large-text" rows="3" name="payment_manual_instructions"><?php echo esc_textarea((string) ($settings['payment_manual_instructions'] ?? '')); ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="payment_bank_transfer_title"><?php esc_html_e('Bank transfer title', 'slotera-booking'); ?></label></th>
                <td><input class="regular-text" id="payment_bank_transfer_title" name="payment_bank_transfer_title" value="<?php echo esc_attr((string) ($settings['payment_bank_transfer_title'] ?? 'Bank transfer')); ?>"><br><textarea class="large-text" rows="4" name="payment_bank_transfer_instructions" placeholder="IBAN, payment reference, confirmation notes..."><?php echo esc_textarea((string) ($settings['payment_bank_transfer_instructions'] ?? '')); ?></textarea></td>
            </tr>
        </table>


            <p class="submit sltr-payment-settings-submit"><button type="submit" class="button button-primary"><?php esc_html_e('Save payment methods', 'slotera-booking'); ?></button></p>
        </form>
    </section>
    <section class="sltr-settings-card sltr-payment-settings-block sltr-payment-settings-block--custom">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-settings-form">
            <input type="hidden" name="action" value="sltr_save_payment_settings">
            <input type="hidden" name="payment_settings_section" value="custom">
            <?php wp_nonce_field('sltr_save_payment_settings_custom'); ?>
        <h2><?php esc_html_e('Custom offline methods', 'slotera-booking'); ?></h2>
        <p class="description"><?php esc_html_e('Optional manual methods only. Empty rows are ignored and duplicate slugs are skipped.', 'slotera-booking'); ?></p>
        <table class="widefat striped sltr-pro-table" id="sltr-custom-offline-methods">
            <thead>
                <tr>
                    <th><?php esc_html_e('Slug', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Title', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Instructions', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Actions', 'slotera-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($custom_methods as $i => $method) : if (!is_array($method)) { continue; } ?>
                <tr>
                    <td><input type="text" name="payment_custom_methods[<?php echo esc_attr((string) $i); ?>][slug]" value="<?php echo esc_attr((string) ($method['slug'] ?? str_replace('custom_', '', (string) ($method['id'] ?? '')))); ?>" placeholder="invoice"></td>
                    <td><input type="text" name="payment_custom_methods[<?php echo esc_attr((string) $i); ?>][title]" value="<?php echo esc_attr((string) ($method['title'] ?? '')); ?>" placeholder="Invoice"></td>
                    <td><textarea class="large-text" rows="2" name="payment_custom_methods[<?php echo esc_attr((string) $i); ?>][instructions]"><?php echo esc_textarea((string) ($method['instructions'] ?? '')); ?></textarea></td>
                    <td><button type="button" class="button sltr-remove-custom-method"><?php esc_html_e('Remove', 'slotera-booking'); ?></button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p id="sltr-custom-methods-empty" <?php echo count($custom_methods) > 0 ? 'style="display:none;"' : ''; ?>><?php esc_html_e('No custom methods yet.', 'slotera-booking'); ?></p>
        <p><button type="button" class="button" id="sltr-add-custom-method"><?php esc_html_e('Add method', 'slotera-booking'); ?></button></p>
        <script>
        (function () {
            var table = document.getElementById('sltr-custom-offline-methods');
            var tbody = table ? table.querySelector('tbody') : null;
            var addButton = document.getElementById('sltr-add-custom-method');
            var emptyMessage = document.getElementById('sltr-custom-methods-empty');
            var nextIndex = tbody ? tbody.querySelectorAll('tr').length : 0;

            function esc(value) {
                return String(value).replace(/[&<>"']/g, function (match) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[match];
                });
            }

            function updateEmptyMessage() {
                if (!emptyMessage || !tbody) { return; }
                emptyMessage.style.display = tbody.querySelectorAll('tr').length ? 'none' : '';
            }

            function rowHtml(index) {
                return '<tr>'
                    + '<td><input type="text" name="payment_custom_methods[' + esc(index) + '][slug]" value="" placeholder="invoice"></td>'
                    + '<td><input type="text" name="payment_custom_methods[' + esc(index) + '][title]" value="" placeholder="Invoice"></td>'
                    + '<td><textarea class="large-text" rows="2" name="payment_custom_methods[' + esc(index) + '][instructions]"></textarea></td>'
                    + '<td><button type="button" class="button sltr-remove-custom-method"><?php echo esc_js(__('Remove', 'slotera-booking')); ?></button></td>'
                    + '</tr>';
            }

            if (addButton && tbody) {
                addButton.addEventListener('click', function () {
                    tbody.insertAdjacentHTML('beforeend', rowHtml(nextIndex++));
                    updateEmptyMessage();
                });
            }

            if (tbody) {
                tbody.addEventListener('click', function (event) {
                    var button = event.target && event.target.closest ? event.target.closest('.sltr-remove-custom-method') : null;
                    if (!button) { return; }
                    var row = button.closest('tr');
                    if (row) { row.parentNode.removeChild(row); }
                    updateEmptyMessage();
                });
            }

            updateEmptyMessage();
        }());
        </script>

            <p class="submit sltr-payment-settings-submit"><button type="submit" class="button button-primary"><?php esc_html_e('Save custom offline methods', 'slotera-booking'); ?></button></p>
        </form>
    </section>

</div>
