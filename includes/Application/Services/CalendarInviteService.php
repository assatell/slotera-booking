<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class CalendarInviteService
{
    private SettingsRepository $settings;

    public function __construct(?SettingsRepository $settings = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
    }

    public function enabled(): bool
    {
        return (int) $this->settings->get('email_attach_ics_invites', 0) === 1;
    }

    public function should_attach_for_scenario(string $scenario, string $recipient_type): bool
    {
        if (!$this->enabled() || $recipient_type !== 'customer') { return false; }
        return in_array($scenario, [
            'booking_created_customer',
            'booking_confirmed_customer',
            'booking_rescheduled_customer',
            'booking_reminder_24h_customer',
            'booking_reminder_2h_customer',
            'booking_cancelled_customer',
        ], true);
    }

    /**
     * Create a temporary .ics file for wp_mail attachments.
     * The caller should delete the returned file after wp_mail() completes.
     */
    public function create_temp_file(array $booking, array $package = [], string $method = 'REQUEST'): string
    {
        $content = $this->generate($booking, $package, $method);
        if ($content === '') { return ''; }

        $method = strtoupper($method) === 'CANCEL' ? 'cancel' : 'request';
        return (new SecureAttachmentFileService())->create('ics', $content, 'booking-' . $method);
    }

    public function generate(array $booking, array $package = [], string $method = 'REQUEST'): string
    {
        $method = strtoupper($method) === 'CANCEL' ? 'CANCEL' : 'REQUEST';
        $start = $this->timestamp($booking, 'start_time');
        $end = $this->timestamp($booking, 'end_time');
        if ($start <= 0) { return ''; }
        if ($end <= $start) { $end = $start + HOUR_IN_SECONDS; }

        $booking_id = absint($booking['id'] ?? 0);
        $uid = 'slotera-booking-' . ($booking_id > 0 ? (string) $booking_id : md5(wp_json_encode($booking))) . '@' . wp_parse_url(home_url(), PHP_URL_HOST);
        $sequence = max(0, absint($booking['updated_at'] ?? 0));
        $status = $method === 'CANCEL' || (string) ($booking['status'] ?? '') === 'cancelled' ? 'CANCELLED' : 'CONFIRMED';
        $summary = trim((string) ($package['title'] ?? ''));
        if ($summary === '') { $summary = __('Booking', 'slotera-booking'); }
        $customer = trim((string) ($booking['customer_name'] ?? ''));
        if ($customer !== '') { $summary .= ' — ' . $customer; }

        $email_locale = EmailTemplateRegistry::runtime_locale();
        $booking_status = function_exists('sltr_booking_status_label')
            ? sltr_booking_status_label((string) ($booking['status'] ?? ''), 'emails', $email_locale)
            : ucwords(str_replace('_', ' ', sanitize_key((string) ($booking['status'] ?? ''))));
        $payment_status = function_exists('sltr_payment_status_label')
            ? sltr_payment_status_label((string) ($booking['payment_status'] ?? ''), 'emails', $email_locale)
            : ucwords(str_replace('_', ' ', sanitize_key((string) ($booking['payment_status'] ?? ''))));

        $description_lines = array_filter([
            __('Booking', 'slotera-booking') . ' #' . ($booking_id > 0 ? (string) $booking_id : ''),
            __('Service', 'slotera-booking') . ': ' . (string) ($package['title'] ?? ''),
            __('Customer', 'slotera-booking') . ': ' . (string) ($booking['customer_name'] ?? ''),
            __('Email', 'slotera-booking') . ': ' . (string) ($booking['customer_email'] ?? ''),
            __('Phone', 'slotera-booking') . ': ' . (string) ($booking['customer_phone'] ?? ''),
            __('Status', 'slotera-booking') . ': ' . $booking_status,
            __('Payment', 'slotera-booking') . ': ' . $payment_status,
        ], static fn($line) => trim((string) $line) !== '' && substr((string) $line, -2) !== ': ');

        $location = trim((string) ($booking['location'] ?? ($package['location'] ?? '')));
        $organizer_email = sanitize_email((string) $this->settings->get('email_from_address', get_option('admin_email')));
        if ($organizer_email === '' || !is_email($organizer_email)) { $organizer_email = sanitize_email((string) get_option('admin_email')); }
        $organizer_name = sanitize_text_field((string) $this->settings->get('email_from_name', get_bloginfo('name')));
        $attendee_email = sanitize_email((string) ($booking['customer_email'] ?? ''));
        $attendee_name = sanitize_text_field((string) ($booking['customer_name'] ?? ''));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Slotera Booking//Slotera//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:' . $method,
            'BEGIN:VEVENT',
            'UID:' . $this->escape($uid),
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            'DTSTART:' . gmdate('Ymd\THis\Z', $start),
            'DTEND:' . gmdate('Ymd\THis\Z', $end),
            'SUMMARY:' . $this->escape($summary),
            'DESCRIPTION:' . $this->escape(implode("\n", $description_lines)),
            'STATUS:' . $status,
            'SEQUENCE:' . $sequence,
        ];

        if ($location !== '') { $lines[] = 'LOCATION:' . $this->escape($location); }
        if ($organizer_email !== '') { $lines[] = 'ORGANIZER;CN=' . $this->escape_param($organizer_name) . ':MAILTO:' . $organizer_email; }
        if ($attendee_email !== '') { $lines[] = 'ATTENDEE;CN=' . $this->escape_param($attendee_name) . ';ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=FALSE:MAILTO:' . $attendee_email; }
        if ($method !== 'CANCEL') {
            $lines[] = 'BEGIN:VALARM';
            $lines[] = 'TRIGGER:-PT1H';
            $lines[] = 'ACTION:DISPLAY';
            $lines[] = 'DESCRIPTION:' . $this->escape(__('Booking reminder', 'slotera-booking'));
            $lines[] = 'END:VALARM';
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return $this->fold(implode("\r\n", $lines) . "\r\n");
    }

    private function timestamp(array $booking, string $time_key): int
    {
        $date = sanitize_text_field((string) ($booking['booking_date'] ?? ''));
        $time = sanitize_text_field((string) ($booking[$time_key] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}/', $time)) { return 0; }
        try {
            $timezone = wp_timezone();
            $dt = new \DateTimeImmutable($date . ' ' . substr($time, 0, 5), $timezone);
            return $dt->getTimestamp();
        } catch (\Exception $e) {
            return strtotime($date . ' ' . substr($time, 0, 5)) ?: 0;
        }
    }

    private function escape(string $value): string
    {
        $value = str_replace(["\\", ";", ",", "\r\n", "\r", "\n"], ["\\\\", "\\;", "\\,", "\\n", "\\n", "\\n"], $value);
        return $value;
    }

    private function escape_param(string $value): string
    {
        return str_replace(['\\', '"', ';', ','], ['\\\\', '\\"', '\\;', '\\,'], $value);
    }

    private function fold(string $content): string
    {
        $output = [];
        foreach (preg_split('/\r\n/', $content) as $line) {
            while (strlen($line) > 73) {
                $output[] = substr($line, 0, 73);
                $line = ' ' . substr($line, 73);
            }
            $output[] = $line;
        }
        return implode("\r\n", $output);
    }
}
