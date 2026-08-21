<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\EmailTemplateRegistry;
use Slotera\Application\Services\ExternalMailPluginDetector;
use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\TranslationRegistry;
use Slotera\Application\Services\TranslationService;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class EmailController
{
    private SettingsRepository $settings;
    private RequestValidator $request;

    public function __construct(?SettingsRepository $settings = null, ?RequestValidator $request = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
        $this->request = $request ?? new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_email_general', [$this, 'save_general']);
        add_action('admin_post_sltr_save_email_template_locale', [$this, 'save_template_locale']);
        add_action('admin_post_sltr_save_email_calendar_invites', [$this, 'save_calendar_invites']);
        add_action('admin_post_sltr_save_email_template', [$this, 'save_template']);
        add_action('admin_post_sltr_send_test_email', [$this, 'send_test']);
        add_action('admin_post_sltr_send_smtp_test_email', [$this, 'send_smtp_test']);
        add_action('admin_post_sltr_reset_email_template', [$this, 'reset_template']);
        add_action('admin_post_sltr_reset_all_email_templates', [$this, 'reset_all_templates']);
    }



    public function save_template_locale(): void
    {
        $this->verify('sltr_save_email_template_locale');

        $locale = $this->request->post_text('locale', TranslationRegistry::default_locale());
        $translations = new TranslationService();
        $languages = TranslationRegistry::languages_for_group('frontend');

        if (!isset($languages[$locale])) {
            $locale = TranslationRegistry::default_locale();
        }

        $translations->save_group_locale('emails', $locale);

        $this->redirect(['sltr_message' => 'email_locale_saved']);
    }

    public function save_calendar_invites(): void
    {
        $this->verify('sltr_save_email_calendar_invites');

        $this->settings->update([
            'email_attach_ics_invites' => $this->request->post_bool('email_attach_ics_invites'),
        ]);

        $this->redirect_settings(['sltr_message' => 'saved']);
    }

    public function save_general(): void
    {
        $this->verify('sltr_save_email_general');

        $current = $this->settings->all();
        $smtp_encryption = $this->request->post_key('smtp_encryption', 'tls');
        if (!in_array($smtp_encryption, ['none', 'tls', 'ssl'], true)) {
            $smtp_encryption = 'tls';
        }

        $smtp_port = $this->request->post_int('smtp_port', 587);
        if ($smtp_port < 1 || $smtp_port > 65535) {
            $smtp_port = 587;
        }

        $smtp_password = isset($_POST['smtp_password']) ? $this->clean_smtp_password(wp_unslash((string) $_POST['smtp_password'])) : '';
        if ($smtp_password === '' && empty($_POST['smtp_password_clear'])) {
            $smtp_password = (string) ($current['smtp_password'] ?? '');
        }

        $smtp_requested = $this->request->post_bool('smtp_enabled');
        $external_mail_plugins = (new ExternalMailPluginDetector())->detected();
        $smtp_blocked_by_external_plugin = $smtp_requested === 1 && $external_mail_plugins !== [];
        if ($smtp_blocked_by_external_plugin) {
            $smtp_requested = 0;
        }

        $this->settings->update([
            'email_notifications_enabled' => $this->request->post_bool('email_notifications_enabled'),
            'email_from_name' => $this->request->post_text('email_from_name'),
            'email_from_address' => $this->request->post_email('email_from_address'),
            'admin_notification_email' => $this->request->post_email('admin_notification_email'),
            'email_retry_max_attempts' => $this->request->post_int('email_retry_max_attempts'),
            'smtp_enabled' => $smtp_requested,
            'smtp_sender_email' => $this->request->post_email('smtp_sender_email'),
            'smtp_sender_name' => $this->request->post_text('smtp_sender_name'),
            'smtp_host' => $this->request->post_text('smtp_host'),
            'smtp_port' => $smtp_port,
            'smtp_encryption' => $smtp_encryption,
            'smtp_auth' => $this->request->post_bool('smtp_auth'),
            'smtp_username' => $this->request->post_text('smtp_username'),
            'smtp_password' => $smtp_password,
            'smtp_allow_insecure_ssl' => $this->request->post_bool('smtp_allow_insecure_ssl'),
            'smtp_timeout' => $this->request->post_int('smtp_timeout'),
        ]);

        $args = ['sltr_message' => 'saved'];
        if ($smtp_blocked_by_external_plugin) {
            $args['sltr_smtp_external_plugin_blocked'] = '1';
        }
        $this->redirect_settings($args);
    }


    private function clean_smtp_password(string $password): string
    {
        // SMTP/app passwords often contain symbols that sanitize_text_field() can alter.
        // Preserve the password exactly, only removing control characters that cannot
        // be part of a valid single-line SMTP password field.
        return (string) preg_replace('/[\x00\r\n]/', '', $password);
    }

    public function save_template(): void
    {
        $this->verify('sltr_save_email_template');

        $scenario = $this->request->post_key('scenario');
        $scenarios = EmailTemplateRegistry::scenarios();

        if ($scenario === '' || !isset($scenarios[$scenario])) {
            $this->redirect(['sltr_error' => 'invalid_scenario']);
        }

        $this->settings->update([
            'email_template_' . $scenario . '_enabled' => $this->request->post_bool('enabled'),
            'email_template_' . $scenario . '_subject' => $this->request->post_text('subject'),
            'email_template_' . $scenario . '_body' => $this->request->post_html('body'),
            'email_template_' . $scenario . '_use_html' => $this->request->post_bool('use_html'),
            'email_template_' . $scenario . '_html_body' => $this->request->post_html('html_body'),
        ]);

        $this->redirect(['scenario' => $scenario, 'sltr_message' => 'saved']);
    }



    public function reset_template(): void
    {
        $this->verify('sltr_reset_email_template');

        $scenario = $this->request->post_key('scenario');
        $scenarios = EmailTemplateRegistry::scenarios();

        if ($scenario === '' || !isset($scenarios[$scenario])) {
            $this->redirect(['sltr_error' => 'invalid_scenario']);
        }

        $template_defaults = [
            'enabled' => 1,
            'subject' => (string) ($scenarios[$scenario]['default_subject'] ?? ''),
            'body' => (string) ($scenarios[$scenario]['default_body'] ?? ''),
            'use_html' => (int) ($scenarios[$scenario]['default_use_html'] ?? 0),
            'html_body' => (string) ($scenarios[$scenario]['default_html_body'] ?? ''),
        ];

        $this->settings->update([
            'email_template_' . $scenario . '_enabled' => (int) ($template_defaults['enabled'] ?? 1),
            'email_template_' . $scenario . '_subject' => (string) ($template_defaults['subject'] ?? ''),
            'email_template_' . $scenario . '_body' => (string) ($template_defaults['body'] ?? ''),
            'email_template_' . $scenario . '_use_html' => (int) ($template_defaults['use_html'] ?? 0),
            'email_template_' . $scenario . '_html_body' => (string) ($template_defaults['html_body'] ?? ''),
        ]);

        $this->redirect(['scenario' => $scenario, 'sltr_message' => 'reset']);
    }

    public function reset_all_templates(): void
    {
        $this->verify('sltr_reset_all_email_templates');

        $updates = [];
        foreach (EmailTemplateRegistry::scenarios() as $scenario_key => $scenario) {
            $prefix = 'email_template_' . $scenario_key . '_';
            $updates[$prefix . 'enabled'] = 1;
            $updates[$prefix . 'subject'] = (string) ($scenario['default_subject'] ?? '');
            $updates[$prefix . 'body'] = (string) ($scenario['default_body'] ?? '');
            $updates[$prefix . 'use_html'] = (int) ($scenario['default_use_html'] ?? 0);
            $updates[$prefix . 'html_body'] = (string) ($scenario['default_html_body'] ?? '');
        }

        if ($updates !== []) {
            $this->settings->update($updates);
        }

        $this->redirect(['sltr_message' => 'reset_all']);
    }

    public function send_test(): void
    {
        $this->verify('sltr_send_test_email');

        $scenario = $this->request->post_key('scenario');
        $to = $this->request->post_email('test_email');
        $scenarios = EmailTemplateRegistry::scenarios();

        if ($scenario === '' || !isset($scenarios[$scenario]) || $to === '' || !is_email($to)) {
            $this->redirect(['scenario' => $scenario, 'sltr_error' => 'invalid_test_email']);
        }

        $settings = $this->settings->all();
        $subject = sanitize_text_field((string) ($settings['email_template_' . $scenario . '_subject'] ?? $scenarios[$scenario]['default_subject']));
        $use_html = (int) ($settings['email_template_' . $scenario . '_use_html'] ?? 0) === 1;
        $plain_body = wp_kses_post((string) ($settings['email_template_' . $scenario . '_body'] ?? $scenarios[$scenario]['default_body']));
        $html_body = wp_kses_post((string) ($settings['email_template_' . $scenario . '_html_body'] ?? ''));
        $body = $use_html && $html_body !== '' ? $html_body : wpautop($plain_body);

        $theme_colors = $this->email_theme_colors();
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
                '{price_summary}' => $this->sample_price_summary(),
            '{theme_primary_color}' => $theme_colors['primary'],
            '{theme_primary_text_color}' => $theme_colors['primary_text'],
            '{theme_text_color}' => $theme_colors['text'],
            '{theme_muted_text_color}' => $theme_colors['muted'],
            '{theme_card_background_color}' => $theme_colors['card_bg'],
        ];

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $from_name = sanitize_text_field((string) ($settings['smtp_sender_name'] ?? ''));
        if ($from_name === '') {
            $from_name = sanitize_text_field((string) ($settings['email_from_name'] ?? get_bloginfo('name')));
        }
        $from_email = sanitize_email((string) ($settings['smtp_sender_email'] ?? ''));
        if ($from_email === '' || !is_email($from_email)) {
            $from_email = sanitize_email((string) ($settings['email_from_address'] ?? get_option('admin_email')));
        }
        if ($from_email !== '' && is_email($from_email)) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        $sent = wp_mail($to, strtr($subject, $sample), $this->wrap_html_email(strtr($body, $sample)), $headers);

        $this->redirect(['scenario' => $scenario, $sent ? 'sltr_message' : 'sltr_error' => $sent ? 'test_sent' : 'test_failed']);
    }


    public function send_smtp_test(): void
    {
        $this->verify('sltr_send_smtp_test_email');

        $to = $this->request->post_email('smtp_test_email');
        if ($to === '' || !is_email($to)) {
            $this->redirect_settings(['sltr_error' => 'invalid_smtp_test_email']);
        }

        $settings = $this->settings->all();
        $from_name = sanitize_text_field((string) ($settings['smtp_sender_name'] ?? ''));
        if ($from_name === '') {
            $from_name = sanitize_text_field((string) ($settings['email_from_name'] ?? get_bloginfo('name')));
        }
        $from_email = sanitize_email((string) ($settings['smtp_sender_email'] ?? ''));
        if ($from_email === '' || !is_email($from_email)) {
            $from_email = sanitize_email((string) ($settings['email_from_address'] ?? get_option('admin_email')));
        }
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        if ($from_email !== '' && is_email($from_email)) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        $subject = sprintf('[%s] Slotera SMTP test email', get_bloginfo('name'));
        $message = '<p>This is a test email from Slotera Booking.</p>'
            . '<p>If you received this message, your WordPress mail configuration is working with the current SMTP settings.</p>';

        $sent = wp_mail($to, $subject, $this->wrap_html_email($message), $headers);

        $this->redirect_settings([$sent ? 'sltr_message' : 'sltr_error' => $sent ? 'smtp_test_sent' : 'smtp_test_failed']);
    }



    private function email_template_factory_defaults(): array
    {
        $option_name = 'sltr_email_template_factory_defaults';
        $saved = get_option($option_name, null);
        if (is_array($saved) && !empty($saved)) {
            return $saved;
        }

        $settings = $this->settings->all();
        $defaults = [];
        foreach (EmailTemplateRegistry::scenarios() as $scenario_key => $scenario) {
            $prefix = 'email_template_' . $scenario_key . '_';
            $defaults[$scenario_key] = [
                'enabled' => (int) ($settings[$prefix . 'enabled'] ?? 1),
                'subject' => (string) ($settings[$prefix . 'subject'] ?? ($scenario['default_subject'] ?? '')),
                'body' => (string) ($settings[$prefix . 'body'] ?? ($scenario['default_body'] ?? '')),
                'use_html' => (int) ($settings[$prefix . 'use_html'] ?? ($scenario['default_use_html'] ?? 0)),
                'html_body' => (string) ($settings[$prefix . 'html_body'] ?? ($scenario['default_html_body'] ?? '')),
            ];
        }

        add_option($option_name, $defaults, '', false);
        return $defaults;
    }

    private function wrap_html_email(string $content): string
    {
        $colors = $this->email_theme_colors();

        return '<!doctype html><html><body style="margin:0;padding:0;background:' . esc_attr($colors['form_bg']) . ';">'
            . '<div style="display:none!important;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;color:transparent;">' . esc_html(wp_trim_words(wp_strip_all_tags($content), 18, '')) . '<span style="display:none!important;mso-hide:all;">&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</span></div>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:' . esc_attr($colors['form_bg']) . ';margin:0;padding:24px 12px;width:100%;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:' . esc_attr($colors['card_bg']) . ';border-radius:18px;overflow:hidden;border:1px solid ' . esc_attr($colors['card_border']) . ';">'
            . '<tr><td style="padding:22px 28px;background:' . esc_attr($colors['primary']) . ';color:' . esc_attr($colors['primary_text']) . ';font-family:Arial,sans-serif;font-size:20px;font-weight:700;"><span style="color:' . esc_attr($colors['primary_text']) . ' !important;-webkit-text-fill-color:' . esc_attr($colors['primary_text']) . ';">' . esc_html(get_bloginfo('name')) . '</span></td></tr>'
            . '<tr><td style="padding:28px;font-family:Arial,sans-serif;font-size:15px;line-height:1.6;color:' . esc_attr($colors['text']) . ';">' . wp_kses_post($content) . '</td></tr>'
            . '<tr><td style="padding:18px 28px;background:' . esc_attr($colors['footer_bg']) . ';color:' . esc_attr($colors['muted']) . ';font-family:Arial,sans-serif;font-size:12px;line-height:1.5;">' . esc_html(get_bloginfo('name')) . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private function email_theme_colors(): array
    {
        $settings = $this->settings->all();
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


    private function sample_price_summary(): string
    {
        $locale = (new TranslationService())->locale_for_group('emails');
        $labels = [
            'Package price' => __('Package price', 'slotera-booking'),
            'Package discount' => __('Package discount', 'slotera-booking'),
            'Coupon' => __('Coupon', 'slotera-booking'),
            'Total' => __('Total', 'slotera-booking'),
        ];
        if ($locale === 'et' || $locale === 'et_EE') {
            $labels = [
                'Package price' => 'Paketi hind',
                'Package discount' => 'Paketi soodustus',
                'Coupon' => 'Kupong',
                'Total' => 'Kokku',
            ];
        } elseif ($locale === 'ru_RU' || $locale === 'ru') {
            $labels = [
                'Package price' => 'Цена пакета',
                'Package discount' => 'Скидка пакета',
                'Coupon' => 'Купон',
                'Total' => 'Итого',
            ];
        }

        return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:18px 0;border-collapse:collapse;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">'
            . '<tr><td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#64748b;">' . esc_html($labels['Package price']) . '</td><td align="right" style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">100.00 EUR</td></tr>'
            . '<tr><td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#64748b;">' . esc_html($labels['Package discount']) . '</td><td align="right" style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">-20.00 EUR</td></tr>'
            . '<tr><td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#64748b;">' . esc_html($labels['Coupon']) . ' (WELCOME10)</td><td align="right" style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">-8.00 EUR</td></tr>'
            . '<tr><td style="padding:10px 12px;color:#64748b;">' . esc_html($labels['Total']) . '</td><td align="right" style="padding:10px 12px;font-weight:700;">72.00 EUR</td></tr>'
            . '</table>';
    }

    private function verify(string $action): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce($action);
    }

    private function redirect_settings(array $args = []): void
    {
        $url = add_query_arg(array_merge(['page' => 'slotera-settings', 'section' => 'email'], $args), admin_url('admin.php'));
        $url .= '#sltr-email-settings';
        wp_safe_redirect($url);
        exit;
    }

    private function redirect(array $args = []): void
    {
        $url = add_query_arg(array_merge(['page' => 'slotera-translations', 'group' => 'email_templates'], $args), admin_url('admin.php'));
        if (!isset($args['scenario'])) {
            $url .= '#sltr-email-templates';
        }
        wp_safe_redirect($url);
        exit;
    }
}
