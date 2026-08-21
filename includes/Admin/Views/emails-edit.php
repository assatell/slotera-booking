<?php

if (!defined('ABSPATH')) {
    exit;
}

$subject = (string) ($settings['email_template_' . $scenario_key . '_subject'] ?? $scenario['default_subject']);
$body = (string) ($settings['email_template_' . $scenario_key . '_body'] ?? $scenario['default_body']);
$use_html = (int) ($settings['email_template_' . $scenario_key . '_use_html'] ?? 0);
$html_body = (string) ($settings['email_template_' . $scenario_key . '_html_body'] ?? ($scenario['default_html_body'] ?? ''));
if ($html_body === '') {
    $html_body = '<h2>' . esc_html($scenario['title']) . '</h2>
' . wpautop(esc_html($body)) . '
<p style="margin-top:24px;color:#6b7280;font-size:13px;">{site_name}</p>';
}
$enabled = (int) ($settings['email_template_' . $scenario_key . '_enabled'] ?? 1);

$sltr_email_price_labels = [
    'Package price' => __('Package price', 'slotera-booking'),
    'Package discount' => __('Package discount', 'slotera-booking'),
    'Coupon' => __('Coupon', 'slotera-booking'),
    'Total' => __('Total', 'slotera-booking'),
];
if (in_array(($email_default_locale ?? ''), ['et', 'et_EE'], true)) {
    $sltr_email_price_labels = [
        'Package price' => 'Paketi hind',
        'Package discount' => 'Paketi soodustus',
        'Coupon' => 'Kupong',
        'Total' => 'Kokku',
    ];
} elseif (in_array(($email_default_locale ?? ''), ['ru', 'ru_RU'], true)) {
    $sltr_email_price_labels = [
        'Package price' => 'Цена пакета',
        'Package discount' => 'Скидка пакета',
        'Coupon' => 'Купон',
        'Total' => 'Итого',
    ];
}
$sltr_email_price_summary = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:18px 0;border-collapse:collapse;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">'
    . '<tr><td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#64748b;">' . esc_html($sltr_email_price_labels['Package price']) . '</td><td align="right" style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">100.00 EUR</td></tr>'
    . '<tr><td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#64748b;">' . esc_html($sltr_email_price_labels['Package discount']) . '</td><td align="right" style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">-20.00 EUR</td></tr>'
    . '<tr><td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#64748b;">' . esc_html($sltr_email_price_labels['Coupon']) . ' (WELCOME10)</td><td align="right" style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">-8.00 EUR</td></tr>'
    . '<tr><td style="padding:10px 12px;color:#64748b;">' . esc_html($sltr_email_price_labels['Total']) . '</td><td align="right" style="padding:10px 12px;font-weight:700;">72.00 EUR</td></tr>'
    . '</table>';

$sample = [
    '{booking_id}' => '123',
    '{customer_name}' => 'Demo Customer',
    '{customer_email}' => 'customer@example.com',
    '{customer_phone}' => '+372 5555 0000',
    '{package_title}' => 'Demo Package',
    '{booking_date}' => date('Y-m-d', strtotime('+7 days')),
    '{start_time}' => '10:00',
    '{end_time}' => '11:00',
    '{status}' => function_exists('sltr_booking_status_label') ? sltr_booking_status_label('confirmed', 'emails') : 'Confirmed',
    '{payment_status}' => function_exists('sltr_payment_status_label') ? sltr_payment_status_label('unpaid', 'emails') : 'Unpaid',
    '{status_raw}' => 'confirmed',
    '{payment_status_raw}' => 'unpaid',
    '{status_label}' => function_exists('sltr_booking_status_label') ? sltr_booking_status_label('confirmed', 'emails') : 'Confirmed',
    '{payment_status_label}' => function_exists('sltr_payment_status_label') ? sltr_payment_status_label('unpaid', 'emails') : 'Unpaid',
    '{site_name}' => get_bloginfo('name'),
    '{magic_link}' => home_url('/demo-login-link'),
    '{cancellation_url}' => home_url('/cancel-demo'),
    '{reschedule_url}' => home_url('/reschedule-demo'),
    '{base_amount}' => '100.00 EUR',
    '{package_discount}' => '20.00 EUR',
    '{coupon_code}' => 'WELCOME10',
    '{coupon_discount}' => '8.00 EUR',
    '{coupon_expires}' => date('Y-m-d', strtotime('+30 days')),
    '{discount_amount}' => '28.00 EUR',
    '{final_amount}' => '72.00 EUR',
    '{total_amount}' => '72.00 EUR',
    '{headline}' => 'We miss you 👋',
    '{message}' => 'It has been a while since your last booking. Here is a special offer for your next visit.',
    '{submessage}' => 'Use your code before it expires and book your next appointment when it suits you.',
    '{cta_button}' => '<p style="margin:26px 0 6px;text-align:center;"><a href="#" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-family:Arial,sans-serif;font-size:15px;font-weight:700;line-height:1.2;padding:13px 22px;border-radius:999px;">Book now</a></p>',
    '{booking_url}' => home_url('/booking'),
    '{package_url}' => home_url('/package/demo'),
    '{cta_url}' => home_url('/booking'),
    '{price_summary}' => $sltr_email_price_summary,
];

function sltr_email_preview_theme_colors(array $settings): array {
    $theme = (string) ($settings['appearance_theme'] ?? 'light');
    $presets = [
        'light' => ['form_bg' => '#ffffff', 'text' => '#0f172a', 'card_bg' => '#ffffff', 'card_border' => '#dbe3ef', 'primary' => '#2563eb', 'primary_text' => '#ffffff', 'muted' => '#64748b'],
        'dark' => ['form_bg' => '#0f172a', 'text' => '#e5e7eb', 'card_bg' => '#111827', 'card_border' => '#334155', 'primary' => '#60a5fa', 'primary_text' => '#ffffff', 'muted' => '#cbd5e1'],
        'soft' => ['form_bg' => '#fff7ed', 'text' => '#431407', 'card_bg' => '#ffffff', 'card_border' => '#fed7aa', 'primary' => '#f97316', 'primary_text' => '#ffffff', 'muted' => '#9a3412'],
        'minimal' => ['form_bg' => '#ffffff', 'text' => '#111827', 'card_bg' => '#ffffff', 'card_border' => '#111827', 'primary' => '#111827', 'primary_text' => '#ffffff', 'muted' => '#4b5563'],
    ];
    $colors = $presets[$theme] ?? $presets['light'];
    if ($theme === 'custom') {
        $colors = [
            'form_bg' => (string) ($settings['form_background_color'] ?? '#ffffff'),
            'text' => (string) ($settings['form_text_color'] ?? '#0f172a'),
            'card_bg' => (string) ($settings['card_background_color'] ?? '#ffffff'),
            'card_border' => (string) ($settings['card_border_color'] ?? '#dbe3ef'),
            'primary' => (string) ($settings['primary_color'] ?? '#2563eb'),
            'primary_text' => (string) ($settings['primary_text_color'] ?? '#ffffff'),
            'muted' => (string) ($settings['muted_text_color'] ?? '#64748b'),
        ];
    }
    $colors['footer_bg'] = $colors['form_bg'];
    return $colors;
}
$theme_colors = sltr_email_preview_theme_colors($settings);
$sample['{theme_primary_color}'] = $theme_colors['primary'];
$sample['{theme_primary_text_color}'] = $theme_colors['primary_text'];
$sample['{theme_text_color}'] = $theme_colors['text'];
$sample['{theme_muted_text_color}'] = $theme_colors['muted'];
$sample['{theme_card_background_color}'] = $theme_colors['card_bg'];

$preview_subject = strtr($subject, $sample);
$preview_body = strtr($use_html ? $html_body : wpautop($body), $sample);
?>

<div class="wrap sltr-admin sltr-emails sltr-page-stack">
    <header class="sltr-page-header"><div class="sltr-page-header__content"><h1 class="sltr-page-header__title"><?php echo esc_html($scenario['title']); ?></h1><p class="sltr-page-header__description"><?php echo esc_html($scenario['description']); ?></p></div><div class="sltr-page-header__actions"><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=slotera-translations&group=email_templates#sltr-email-templates')); ?>"><?php esc_html_e('Back to emails', 'slotera-booking'); ?></a></div></header>

    <?php $sltr_message = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_message'); ?>
    <?php $sltr_error = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_error'); ?>
    <?php if ($sltr_message !== '') : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($sltr_message === 'test_sent' ? __('Test email sent.', 'slotera-booking') : ($sltr_message === 'reset' ? __('Template reset to default.', 'slotera-booking') : __('Saved.', 'slotera-booking'))); ?></p></div>
    <?php endif; ?>

    <?php if ($sltr_error !== '') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Something went wrong.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>

    <div class="sltr-grid">
        <section class="sltr-panel">
            <h2><?php esc_html_e('Template', 'slotera-booking'); ?></h2>
            <p><?php echo esc_html($scenario['description']); ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="sltr_save_email_template">
                <input type="hidden" name="scenario" value="<?php echo esc_attr($scenario_key); ?>">
                <?php wp_nonce_field('sltr_save_email_template'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enabled', 'slotera-booking'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enabled" value="1" <?php checked($enabled, 1); ?>>
                                    <?php esc_html_e('Send this email when the scenario is triggered', 'slotera-booking'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="sltr-email-subject"><?php esc_html_e('Subject', 'slotera-booking'); ?></label></th>
                            <td><input type="text" class="large-text" id="sltr-email-subject" name="subject" value="<?php echo esc_attr($subject); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="sltr-email-body"><?php esc_html_e('Plain text body', 'slotera-booking'); ?></label></th>
                            <td>
                                <textarea class="large-text code" rows="10" id="sltr-email-body" name="body"><?php echo esc_textarea($body); ?></textarea>
                                <p class="description"><?php esc_html_e('Used when HTML mode is disabled, and as a fallback for older templates.', 'slotera-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('HTML mode', 'slotera-booking'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="use_html" value="1" <?php checked($use_html, 1); ?>>
                                    <?php esc_html_e('Use HTML template for this email', 'slotera-booking'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="sltr-email-html-body"><?php esc_html_e('HTML body', 'slotera-booking'); ?></label></th>
                            <td>
                                <textarea class="large-text code" rows="18" id="sltr-email-html-body" name="html_body"><?php echo esc_textarea($html_body); ?></textarea>
                                <p class="description"><?php esc_html_e('Write only the content part. Slotera adds the email header, white card, footer and mobile-safe wrapper automatically using your selected Slotera theme colors.', 'slotera-booking'); ?></p>
                                <p class="description"><?php esc_html_e('Theme color placeholders are available, for example {theme_primary_color}, {theme_primary_text_color}, {theme_text_color}, {theme_muted_text_color}.', 'slotera-booking'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="sltr-form-actions"><button class="button button-primary"><?php esc_html_e('Save template', 'slotera-booking'); ?></button></div>
            </form>

            <hr>

            <h2><?php esc_html_e('Send test email', 'slotera-booking'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="sltr_send_test_email">
                <input type="hidden" name="scenario" value="<?php echo esc_attr($scenario_key); ?>">
                <?php wp_nonce_field('sltr_send_test_email'); ?>
                <input type="email" class="regular-text" name="test_email" value="<?php echo esc_attr((string) get_option('admin_email')); ?>">
                <button class="button"><?php esc_html_e('Send test', 'slotera-booking'); ?></button>
            </form>
        </section>

        <section class="sltr-panel">
            <h2><?php esc_html_e('Preview', 'slotera-booking'); ?></h2>
            <p><strong><?php esc_html_e('Subject:', 'slotera-booking'); ?></strong> <?php echo esc_html($preview_subject); ?></p>
            <div class="sltr-email-preview sltr-email-preview--themed" data-form-bg="<?php echo esc_attr($theme_colors['form_bg']); ?>">
                <div class="sltr-email-preview__card" data-card-bg="<?php echo esc_attr($theme_colors['card_bg']); ?>" data-card-border="<?php echo esc_attr($theme_colors['card_border']); ?>">
                    <div class="sltr-email-preview__header" data-primary="<?php echo esc_attr($theme_colors['primary']); ?>" data-primary-text="<?php echo esc_attr($theme_colors['primary_text']); ?>">
                        <?php echo esc_html(get_bloginfo('name')); ?>
                    </div>
                    <div class="sltr-email-preview__body" data-text="<?php echo esc_attr($theme_colors['text']); ?>">
                        <?php echo wp_kses_post(wpautop($preview_body)); ?>
                    </div>
                    <div class="sltr-email-preview__footer" data-footer-bg="<?php echo esc_attr($theme_colors['footer_bg']); ?>" data-muted="<?php echo esc_attr($theme_colors['muted']); ?>">
                        <?php echo esc_html(get_bloginfo('name')); ?>
                    </div>
                </div>
            </div>

            <h2><?php esc_html_e('Available placeholders', 'slotera-booking'); ?></h2>
            <div class="sltr-placeholder-list">
                <?php foreach ($placeholders as $placeholder) : ?>
                    <code><?php echo esc_html($placeholder); ?></code>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
