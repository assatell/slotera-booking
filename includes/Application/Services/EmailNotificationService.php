<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Events;
use Slotera\Infrastructure\Repositories\ActivityLogRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class EmailNotificationService
{
    private SettingsRepository $settings;
    private ActivityLogRepository $log_repository;

    public function __construct(?SettingsRepository $settings = null, ?ActivityLogRepository $log_repository = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
        $this->log_repository = $log_repository ?: new ActivityLogRepository();
    }

    public function register_hooks(): void
    {
        // v1.0.23: lifecycle emails are queued and retried by EmailReminderService.
    }

    public function handle_booking_created(array $payload): void
    {
        if ((int) $this->settings->get('email_notifications_enabled', 1) !== 1) {
            return;
        }

        $booking = isset($payload['booking']) && is_array($payload['booking']) ? $payload['booking'] : [];
        $package = isset($payload['package']) && is_array($payload['package']) ? $payload['package'] : [];

        if (empty($booking)) {
            return;
        }

        if ((int) $this->settings->get('email_template_booking_created_customer_enabled', 1) === 1) {
            $this->send_template_email('booking_created_customer', sanitize_email((string) ($booking['customer_email'] ?? '')), $booking, $package, 'customer');
        }

        if ((int) $this->settings->get('email_template_booking_created_admin_enabled', 1) === 1) {
            $this->send_template_email('booking_created_admin', sanitize_email((string) $this->settings->get('admin_notification_email', get_option('admin_email'))), $booking, $package, 'admin');
        }
    }

    private function send_template_email(string $scenario, string $to, array $booking, array $package, string $recipient_type): void
    {
        $scenarios = EmailTemplateRegistry::scenarios();
        $definition = $scenarios[$scenario] ?? null;

        if (!$definition) {
            return;
        }

        if ($to === '' || !is_email($to)) {
            $this->log_email_event('email_failed', $booking, ucfirst($recipient_type) . ' email is missing or invalid.', $recipient_type);
            return;
        }

        $stored_settings = get_option(SettingsRepository::OPTION_NAME, []);
        $stored_settings = is_array($stored_settings) ? $stored_settings : [];
        $use_html = (int) ($stored_settings['email_template_' . $scenario . '_use_html'] ?? $this->settings->get('email_template_' . $scenario . '_use_html', 0)) === 1;
        $runtime_template = EmailTemplateRegistry::resolve_runtime_payload(
            $scenario,
            array_key_exists('email_template_' . $scenario . '_subject', $stored_settings) ? (string) $stored_settings['email_template_' . $scenario . '_subject'] : null,
            array_key_exists('email_template_' . $scenario . '_body', $stored_settings) ? (string) $stored_settings['email_template_' . $scenario . '_body'] : null,
            array_key_exists('email_template_' . $scenario . '_html_body', $stored_settings) ? (string) $stored_settings['email_template_' . $scenario . '_html_body'] : null,
            $use_html
        );
        $mode = sanitize_key((string) ($booking['booking_mode'] ?? $booking['pricing_mode'] ?? $package['booking_mode'] ?? ''));
        if ($mode === 'simple') {
            $request_template = \Slotera\Application\Services\Translations\BookingRequestTranslations::template(
                $scenario,
                EmailTemplateRegistry::runtime_locale()
            );
            if ($request_template !== null) {
                $runtime_template['subject'] = $request_template['subject'];
                $runtime_template['body'] = $request_template['body'];
                $runtime_template['is_html_template'] = false;
            }
        }
        $subject = $this->replace_placeholders((string) $runtime_template['subject'], $booking, $package, $recipient_type, $scenario);
        $message = $this->replace_placeholders((string) $runtime_template['body'], $booking, $package, $recipient_type, $scenario);
        $use_html = (bool) $runtime_template['is_html_template'];
        $attachments = [];
        if ($scenario === 'booking_created_customer' && (int) $this->settings->get('invoice_pdf_enabled', 1) === 1) {
            $invoice_path = (new PdfInvoiceService($this->settings))->generate_file($booking, $package);
            if (is_string($invoice_path) && file_exists($invoice_path)) {
                $attachments[] = $invoice_path;
            }
        }

        $calendar_attachments = $this->calendar_invite_attachments($scenario, $recipient_type, $booking, $package);
        $attachments = array_merge($attachments, $calendar_attachments);
        $preheader = $this->email_preheader($booking, $package, $recipient_type, $message, $scenario);
        $sent = wp_mail($to, $subject, $this->wrap_html_message($message, $use_html, $preheader), $this->headers(), $attachments);
        $this->cleanup_temp_files($attachments);

        $this->log_email_event(
            $sent ? 'email_sent' : 'email_failed',
            $booking,
            $sent ? ucfirst($recipient_type) . ' email sent.' : ucfirst($recipient_type) . ' email failed.',
            $recipient_type,
            ['to' => $to, 'subject' => $subject, 'scenario' => $scenario]
        );
    }

    private function calendar_invite_attachments(string $scenario, string $recipient_type, array $booking, array $package): array
    {
        $mode = sanitize_key((string) ($booking['booking_mode'] ?? $package['booking_mode'] ?? 'simple'));
        if ($mode === 'simple') { return []; }
        $calendar = new CalendarInviteService($this->settings);
        if (!$calendar->should_attach_for_scenario($scenario, $recipient_type)) { return []; }
        $method = $scenario === 'booking_cancelled_customer' ? 'CANCEL' : 'REQUEST';
        $path = $calendar->create_temp_file($booking, $package, $method);
        return $path !== '' && file_exists($path) ? [$path] : [];
    }

    private function cleanup_temp_files(array $files): void
    {
        (new SecureAttachmentFileService())->cleanup($files);
    }

    private function headers(): array
    {
        $from_name = sanitize_text_field((string) $this->settings->get('email_from_name', get_bloginfo('name')));
        $from_email = sanitize_email((string) $this->settings->get('email_from_address', get_option('admin_email')));
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        if ($from_email !== '' && is_email($from_email)) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        return $headers;
    }

    private function replace_placeholders(string $text, array $booking, array $package, string $recipient_type = 'customer', string $scenario = ''): string
    {
        $theme_colors = $this->email_theme_colors();
        $preferences = $this->email_display_preferences($booking, $package);
        $text = $this->apply_email_display_preferences($text, $preferences, $recipient_type, $scenario);
        $display = function_exists('sltr_booking_display_data')
            ? sltr_booking_display_data($booking, $package)
            : ['date' => sltr_format_localized_date((string) ($booking['booking_date'] ?? ''), EmailTemplateRegistry::runtime_locale()), 'time' => ''];
        $display_time = (string) ($display['time'] ?? '');
        $display_start_time = $display_time;
        $display_end_time = '';
        if ($display_time !== '' && preg_match('/^(.+?)\s+[–-]\s+(.+)$/u', $display_time, $time_match)) {
            $display_start_time = trim((string) $time_match[1]);
            $display_end_time = trim((string) $time_match[2]);
        }
        $rendered = strtr($text, [
            '{booking_id}' => (string) ($booking['id'] ?? ''),
            '{customer_name}' => (string) ($booking['customer_name'] ?? ''),
            '{customer_email}' => (string) ($booking['customer_email'] ?? ''),
            '{customer_phone}' => (string) ($booking['customer_phone'] ?? ''),
            '{package_title}' => (string) ($package['title'] ?? ('#' . (string) ($booking['package_id'] ?? ''))),
            '{booking_date}' => (string) ($display['date'] ?? ''),
            '{start_time}' => $display_start_time,
            '{end_time}' => $preferences['start_time_only'] ? '' : $display_end_time,
            // Backward compatibility: older saved email templates may still use
            // {status}/{payment_status}. In email output these must be human labels,
            // not raw state-machine values like "confirmed" or "unpaid".
            '{status}' => $this->booking_status_label((string) ($booking['status'] ?? '')),
            '{payment_status}' => $this->payment_status_label((string) ($booking['payment_status'] ?? '')),
            '{status_raw}' => (string) ($booking['status'] ?? ''),
            '{payment_status_raw}' => (string) ($booking['payment_status'] ?? ''),
            '{status_label}' => $this->booking_status_label((string) ($booking['status'] ?? '')),
            '{payment_status_label}' => $this->payment_status_label((string) ($booking['payment_status'] ?? '')),
            '{site_name}' => get_bloginfo('name'),
            '{magic_link}' => '',
            '{cancellation_url}' => (new BookingLifecycleService())->cancellation_url($booking),
            '{reschedule_url}' => (new BookingLifecycleService())->reschedule_url($booking),
            '{theme_primary_color}' => $theme_colors['primary'],
            '{theme_primary_text_color}' => $theme_colors['primary_text'],
            '{theme_text_color}' => $theme_colors['text'],
            '{theme_muted_text_color}' => $theme_colors['muted'],
            '{theme_card_background_color}' => $theme_colors['card_bg'],
        ]);
        if ($recipient_type === 'customer' && sanitize_key((string) ($booking['payment_status'] ?? '')) === 'partial') {
            $balance_note = sltr__('emails.remaining_balance_paid_on_site', EmailTemplateRegistry::runtime_locale());
            if ($balance_note !== '' && stripos(wp_strip_all_tags($rendered), wp_strip_all_tags($balance_note)) === false) {
                $rendered = rtrim($rendered) . "\n<p>" . esc_html($balance_note) . "</p>";
            }
        }
        return $rendered;
    }



    private function email_display_preferences(array $booking, array $package): array
    {
        $mode = sanitize_key((string) ($booking['booking_mode'] ?? $package['booking_mode'] ?? 'simple'));
        if (!in_array($mode, ['simple', 'fixed', 'flex', 'date_range_inventory'], true)) {
            $mode = 'simple';
        }

        $configs = [];
        $raw = $package['mode_configs_json'] ?? '';
        if (is_array($raw)) {
            $configs = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $configs = $decoded;
            }
        }

        $active = isset($configs[$mode]) && is_array($configs[$mode]) ? $configs[$mode] : [];

        $display = function_exists('sltr_booking_display_data')
            ? sltr_booking_display_data($booking, $package)
            : ['date' => '', 'time' => ''];
        $scheduled_event = $mode === 'date_range_inventory' && !empty($booking['resource_id']);

        return [
            'hide_price' => !empty($active['hide_price_on_frontend']),
            'start_time_only' => $mode === 'flex' && !empty($active['display_start_time_only']),
            'no_datetime' => $mode === 'simple',
            'date_only' => !empty($display['date_only']),
            'scheduled_event' => $scheduled_event,
            'scheduled_multiday' => $scheduled_event && (string) ($display['time'] ?? '') === '' && strpos((string) ($display['date'] ?? ''), '→') !== false,
        ];
    }

    private function apply_email_display_preferences(string $text, array $preferences, string $recipient_type = 'customer', string $scenario = ''): string
    {
        if (!empty($preferences['scheduled_event'])) {
            // Scheduled events have fixed dates and cannot be rescheduled by clients.
            $text = preg_replace('/<(p|div|tr|li|section)\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\{reschedule_url\}[^<]*(?:<[^>]+>[^<]*)*<\/\1>/isu', '', $text) ?? $text;
            $text = preg_replace('/^.*\{reschedule_url\}.*(?:\R|$)/imu', '', $text) ?? $text;
            $text = str_replace('{reschedule_url}', '', $text);

            if ($recipient_type === 'admin') {
                // The admin notification already contains the service and booking number;
                // omit the scheduled-event period and detailed pricing from this compact notification.
                $datetime_tokens = '(?:booking_date|start_time|end_time)';
                $text = preg_replace('/<(p|div|tr|li|section|table)\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\{' . $datetime_tokens . '\}[^<]*(?:<[^>]+>[^<]*)*<\/\1>/isu', '', $text) ?? $text;
                $text = preg_replace('/^.*\{' . $datetime_tokens . '\}.*(?:\R|$)/imu', '', $text) ?? $text;
                $text = preg_replace('/\{' . $datetime_tokens . '\}/iu', '', $text) ?? $text;
                $text = preg_replace('/(?:^|\R)[^\r\n<>]{1,160}:\s*\R[^\r\n]*\{price_summary\}[^\r\n]*(?:\R|$)/iu', "\n", $text) ?? $text;
                $text = preg_replace('/<(p|div|h[1-6]|strong)\b[^>]*>.*?:\s*<\/\1>\s*<(p|div|tr|li|section|table)\b[^>]*>.*?\{price_summary\}.*?<\/\2>/isu', '', $text) ?? $text;
                $text = preg_replace('/<(p|div|tr|li|section|table)\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\{price_summary\}[^<]*(?:<[^>]+>[^<]*)*<\/\1>/isu', '', $text) ?? $text;
                $text = preg_replace('/^.*\{price_summary\}.*(?:\R|$)/imu', '', $text) ?? $text;
                $text = str_replace('{price_summary}', '', $text);
            } elseif (!empty($preferences['scheduled_multiday'])) {
                // For multi-day scheduled events the full localized start/end period
                // is already included in {booking_date}; remove the duplicate time row.
                $time_tokens = '(?:start_time|end_time)';
                $text = preg_replace('/<(p|div|tr|li|section|table)\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\{' . $time_tokens . '\}[^<]*(?:<[^>]+>[^<]*)*<\/\1>/isu', '', $text) ?? $text;
                $text = preg_replace('/^.*\{' . $time_tokens . '\}.*(?:\R|$)/imu', '', $text) ?? $text;
                $text = preg_replace('/\{' . $time_tokens . '\}/iu', '', $text) ?? $text;
            }
        }
        if (!empty($preferences['no_datetime'])) {
            $datetime_tokens = '(?:booking_date|start_time|end_time)';
            $text = preg_replace('/<(p|div|tr|li|section|table)\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\{' . $datetime_tokens . '\}[^<]*(?:<[^>]+>[^<]*)*<\/\1>/isu', '', $text) ?? $text;
            $text = preg_replace('/^.*\{' . $datetime_tokens . '\}.*(?:\R|$)/imu', '', $text) ?? $text;
            $text = preg_replace('/\{' . $datetime_tokens . '\}/iu', '', $text) ?? $text;
            $text = preg_replace('/<(p|div|tr|li|section)\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\{reschedule_url\}[^<]*(?:<[^>]+>[^<]*)*<\/\1>/isu', '', $text) ?? $text;
            $text = preg_replace('/^.*\{reschedule_url\}.*(?:\R|$)/imu', '', $text) ?? $text;
            $text = str_replace('{reschedule_url}', '', $text);

            if (in_array($scenario, ['booking_created_customer', 'booking_created_admin'], true)) {
                $notice = $this->simple_booking_contact_notice($recipient_type);
                if ($notice !== '' && stripos(wp_strip_all_tags($text), wp_strip_all_tags($notice)) === false) {
                    $text = rtrim($text) . "\n\n" . $notice;
                }
            }
        }
        if (!empty($preferences['date_only'])) {
            $time_tokens = '(?:start_time|end_time)';
            $text = preg_replace('/<(p|div|tr|li|section|table)\\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\\{' . $time_tokens . '\\}[^<]*(?:<[^>]+>[^<]*)*<\\/\\1>/isu', '', $text) ?? $text;
            $text = preg_replace('/^.*\\{' . $time_tokens . '\\}.*(?:\\R|$)/imu', '', $text) ?? $text;
            $text = preg_replace('/\\{' . $time_tokens . '\\}/iu', '', $text) ?? $text;
        }
        if (!empty($preferences['start_time_only'])) {
            $text = preg_replace('/\\{start_time\\}\\s*(?:-|–|—|&ndash;|&mdash;)\\s*\\{end_time\\}/iu', '{start_time}', $text) ?? $text;
        }

        if (empty($preferences['hide_price'])) {
            return $text;
        }

        // Remove localized price-summary headings structurally, without relying on
        // language-specific keywords. A heading immediately followed by a pricing
        // placeholder belongs to the same conditional section.
        $price_tokens = '(?:price_summary|base_amount|package_discount|coupon_code|coupon_discount|coupon_expires|discount_amount|final_amount|total_amount|tax_amount)';
        $text = preg_replace('/(?:^|\R)[^\r\n<>]{1,160}:\s*\R[^\r\n]*\{' . $price_tokens . '\}[^\r\n]*(?:\R|$)/iu', "\n", $text) ?? $text;
        $text = preg_replace('/<(p|div|h[1-6]|strong)\b[^>]*>.*?:\s*<\/\1>\s*<(p|div|tr|li|section|table)\b[^>]*>.*?\{' . $price_tokens . '\}.*?<\/\2>/isu', '', $text) ?? $text;

        // Remove complete HTML blocks that contain pricing placeholders first.
        $text = preg_replace('/<(p|div|tr|li|section|table)\\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\\{' . $price_tokens . '\\}[^<]*(?:<[^>]+>[^<]*)*<\\/\\1>/isu', '', $text) ?? $text;

        // Remove plain-text lines containing individual pricing placeholders.
        $text = preg_replace('/^.*\\{' . $price_tokens . '\\}.*(?:\\R|$)/imu', '', $text) ?? $text;


        // Hide payment status together with pricing information. This removes both
        // default and custom-template rows such as "Payment: {payment_status_label}".
        $payment_tokens = '(?:payment_status|payment_status_raw|payment_status_label)';
        $text = preg_replace('/<(p|div|tr|li|section|table)\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\{' . $payment_tokens . '\}[^<]*(?:<[^>]+>[^<]*)*<\/\1>/isu', '', $text) ?? $text;
        $text = preg_replace('/^.*\{' . $payment_tokens . '\}.*(?:\R|$)/imu', '', $text) ?? $text;

        // Safety: no pricing or payment-status placeholder should survive.
        $text = preg_replace('/\{' . $price_tokens . '\}/iu', '', $text) ?? $text;
        $text = preg_replace('/\{' . $payment_tokens . '\}/iu', '', $text) ?? $text;

        return trim($text);
    }

    private function simple_booking_contact_notice(string $recipient_type = 'customer'): string
    {
        return \Slotera\Application\Services\Translations\BookingRequestTranslations::notice(
            \Slotera\Application\Services\EmailTemplateRegistry::runtime_locale(),
            $recipient_type
        );
    }

    private function booking_status_label(string $status): string
    {
        return function_exists('sltr_booking_status_label')
            ? sltr_booking_status_label($status, 'emails')
            : ucwords(str_replace('_', ' ', sanitize_key($status)));
    }

    private function payment_status_label(string $status): string
    {
        return function_exists('sltr_payment_status_label')
            ? sltr_payment_status_label($status, 'emails')
            : ucwords(str_replace('_', ' ', sanitize_key($status)));
    }

    private function wrap_html_message(string $message, bool $is_html_template = false, string $preheader = ''): string
    {
        $content = $is_html_template ? wp_kses_post($message) : wp_kses_post(wpautop($message));
        $colors = $this->email_theme_colors();

        return '<!doctype html><html><body style="margin:0;padding:0;background:' . esc_attr($colors['form_bg']) . ';">'
            . '<div style="position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important;font-size:0!important;line-height:0!important;opacity:0!important;color:transparent!important;background:transparent!important;mso-hide:all!important;">' . esc_html($preheader !== '' ? $preheader : $this->fallback_preheader($content)) . '</div>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:' . esc_attr($colors['form_bg']) . ';margin:0;padding:24px 12px;width:100%;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:' . esc_attr($colors['card_bg']) . ';border-radius:18px;overflow:hidden;border:1px solid ' . esc_attr($colors['card_border']) . ';">'
            . '<tr><td style="padding:22px 28px;background:' . esc_attr($colors['primary']) . ';color:' . esc_attr($colors['primary_text']) . ';font-family:Arial,sans-serif;font-size:20px;font-weight:700;"><span style="color:' . esc_attr($colors['primary_text']) . ' !important;-webkit-text-fill-color:' . esc_attr($colors['primary_text']) . ';">' . esc_html(get_bloginfo('name')) . '</span></td></tr>'
            . '<tr><td style="padding:28px;font-family:Arial,sans-serif;font-size:15px;line-height:1.6;color:' . esc_attr($colors['text']) . ';">' . $content . '</td></tr>'
            . '<tr><td style="padding:18px 28px;background:' . esc_attr($colors['footer_bg']) . ';color:' . esc_attr($colors['muted']) . ';font-family:Arial,sans-serif;font-size:12px;line-height:1.5;">' . esc_html(get_bloginfo('name')) . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private function email_preheader(array $booking, array $package, string $recipient_type, string $message, string $scenario = ''): string
    {
        $preferences = $this->email_display_preferences($booking, $package);
        if (!empty($preferences['no_datetime']) && in_array($scenario, ['booking_created_customer', 'booking_created_admin'], true)) {
            return \Slotera\Application\Services\Translations\BookingRequestTranslations::notice(
                EmailTemplateRegistry::runtime_locale(),
                $recipient_type === 'admin' ? 'admin' : 'customer'
            );
        }
        return $this->fallback_preheader($message);
    }

    private function fallback_preheader(string $content): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($content)) ?? '');
        if ($text === '') { return ''; }
        if (preg_match('/^(.{1,150}?[.!?])(?:\s|$)/u', $text, $match)) {
            return trim($match[1]);
        }
        return function_exists('mb_substr') ? mb_substr($text, 0, 150) : substr($text, 0, 150);
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

    private function log_email_event(string $event, array $booking, string $message, string $recipient_type, array $extra = []): void
    {
        $this->log_repository->create([
            'object_type' => 'booking',
            'object_id' => (int) ($booking['id'] ?? 0),
            'event' => $event,
            'actor_type' => 'system',
            'actor_id' => 0,
            'status' => $event === 'email_failed' ? 'error' : 'success',
            'message' => $message,
            'payload' => array_merge(['recipient_type' => $recipient_type, 'booking_id' => (int) ($booking['id'] ?? 0)], $extra),
        ]);
    }
}
