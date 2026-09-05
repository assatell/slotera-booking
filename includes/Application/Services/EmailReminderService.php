<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Events;
use Slotera\Infrastructure\Repositories\ActivityLogRepository;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\EmailQueueRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class EmailReminderService
{
    public const CRON_HOOK = 'sltr_process_email_queue';
    public const CRON_SCHEDULE = 'sltr_every_five_minutes';

    private SettingsRepository $settings;
    private BookingRepository $bookings;
    private PackageRepository $packages;
    private EmailQueueRepository $queue;
    private ActivityLogRepository $logs;

    public function __construct(?SettingsRepository $settings = null, ?BookingRepository $bookings = null, ?PackageRepository $packages = null, ?EmailQueueRepository $queue = null, ?ActivityLogRepository $logs = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
        $this->bookings = $bookings ?: new BookingRepository();
        $this->packages = $packages ?: new PackageRepository();
        $this->queue = $queue ?: new EmailQueueRepository();
        $this->logs = $logs ?: new ActivityLogRepository();
    }

    public function register_hooks(): void
    {
        add_filter('cron_schedules', [$this, 'add_cron_schedule']);
        add_action(self::CRON_HOOK, [$this, 'process_queue']);
        add_action(Events::BOOKING_CREATED, [$this, 'handle_booking_created'], 30, 1);
        add_action(Events::BOOKING_CONFIRMED, [$this, 'handle_booking_confirmed'], 30, 1);
        add_action(Events::BOOKING_CANCELLED, [$this, 'handle_booking_cancelled'], 30, 1);
        add_action(Events::BOOKING_RESCHEDULED, [$this, 'handle_booking_rescheduled'], 30, 1);
        add_action(Events::BOOKING_COMPLETED, [$this, 'handle_booking_completed'], 30, 1);
        add_action(Events::BOOKING_PACKAGE_CHANGED, [$this, 'handle_booking_package_changed'], 30, 1);
        add_action(Events::PAYMENT_PENDING, [$this, 'handle_payment_pending'], 30, 1);
        add_action(Events::PAYMENT_COMPLETED, [$this, 'handle_payment_completed'], 30, 1);
        add_action(Events::PAYMENT_FAILED, [$this, 'handle_payment_failed'], 30, 1);
        add_action(Events::PAYMENT_REFUNDED, [$this, 'handle_payment_refunded'], 30, 1);
        add_action(Events::INVOICE_CREATED, [$this, 'handle_invoice_created'], 30, 1);
    }

    public static function activate(): void
    {
        add_filter('cron_schedules', [new self(), 'add_cron_schedule']);
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public function add_cron_schedule(array $schedules): array
    {
        if (!isset($schedules[self::CRON_SCHEDULE])) {
            $schedules[self::CRON_SCHEDULE] = [
                'interval' => 5 * MINUTE_IN_SECONDS,
                'display' => __('Every 5 minutes', 'slotera-booking'),
            ];
        }
        return $schedules;
    }

    public function ensure_scheduled(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    public function handle_booking_created(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);

        $this->enqueue_template('booking_created_customer', $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), current_time('mysql'));
        $this->enqueue_template('booking_created_admin', $booking, $package, 'admin', (string) $this->settings->get('admin_notification_email', get_option('admin_email')), current_time('mysql'));
        $this->schedule_reminders($booking, $package);
    }

    public function handle_booking_confirmed(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);
        $this->schedule_reminders($booking, $package);
    }

    public function handle_booking_cancelled(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);
        $this->enqueue_template('booking_cancelled_customer', $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), current_time('mysql'), false);
        $this->enqueue_template('booking_cancelled_admin', $booking, $package, 'admin', (string) $this->settings->get('admin_notification_email', get_option('admin_email')), current_time('mysql'), false);
    }


    public function handle_booking_rescheduled(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);
        $this->enqueue_template('booking_rescheduled_customer', $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), current_time('mysql'), false);
        $this->enqueue_template('booking_rescheduled_admin', $booking, $package, 'admin', (string) $this->settings->get('admin_notification_email', get_option('admin_email')), current_time('mysql'), false);
        $this->schedule_reminders($booking, $package);
    }

    public function handle_payment_completed(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);
        $this->enqueue_template('payment_received_customer', $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), current_time('mysql'), false);
        $this->enqueue_template('payment_received_admin', $booking, $package, 'admin', (string) $this->settings->get('admin_notification_email', get_option('admin_email')), current_time('mysql'), false);
    }

    public function handle_booking_completed(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);
        $this->enqueue_template('booking_completed_customer', $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), current_time('mysql'), false);
        $this->enqueue_template('booking_completed_admin', $booking, $package, 'admin', (string) $this->settings->get('admin_notification_email', get_option('admin_email')), current_time('mysql'), false);
    }

    public function handle_booking_package_changed(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);
        $this->enqueue_template('package_changed_customer', $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), current_time('mysql'), false);
        $this->enqueue_template('package_changed_admin', $booking, $package, 'admin', (string) $this->settings->get('admin_notification_email', get_option('admin_email')), current_time('mysql'), false);
    }

    public function handle_payment_pending(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);
        $this->enqueue_template('payment_pending_customer', $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), current_time('mysql'), false);
        $this->enqueue_template('payment_pending_admin', $booking, $package, 'admin', (string) $this->settings->get('admin_notification_email', get_option('admin_email')), current_time('mysql'), false);
    }

    public function handle_payment_failed(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);
        $this->enqueue_template('payment_failed_customer', $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), current_time('mysql'), false);
        $this->enqueue_template('payment_failed_admin', $booking, $package, 'admin', (string) $this->settings->get('admin_notification_email', get_option('admin_email')), current_time('mysql'), false);
    }

    public function handle_payment_refunded(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);
        $this->enqueue_template('payment_refunded_customer', $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), current_time('mysql'), false);
        $this->enqueue_template('payment_refunded_admin', $booking, $package, 'admin', (string) $this->settings->get('admin_notification_email', get_option('admin_email')), current_time('mysql'), false);
    }

    public function handle_invoice_created(array $payload): void
    {
        if (!$this->enabled()) { return; }
        $booking = $this->payload_booking($payload);
        if (!$booking) { return; }
        $package = $this->payload_package($payload, $booking);
        $this->enqueue_template('invoice_created_customer', $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), current_time('mysql'), false);
        $this->enqueue_template('invoice_created_admin', $booking, $package, 'admin', (string) $this->settings->get('admin_notification_email', get_option('admin_email')), current_time('mysql'), false);
    }

    public function schedule_reminders(array $booking, array $package = []): void
    {
        $mode = sanitize_key((string) ($booking['booking_mode'] ?? $package['booking_mode'] ?? 'simple'));
        if ($mode === 'simple') { return; }
        if (!$this->booking_can_receive_reminders($booking)) { return; }
        $start_timestamp = $this->booking_start_timestamp($booking);
        if ($start_timestamp <= 0) { return; }

        $reminders = [
            'booking_reminder_24h_customer' => 24 * HOUR_IN_SECONDS,
            'booking_reminder_2h_customer' => 2 * HOUR_IN_SECONDS,
        ];

        foreach ($reminders as $scenario => $offset) {
            $send_timestamp = $start_timestamp - $offset;
            if ($send_timestamp <= current_time('timestamp')) { continue; }
            $send_at = wp_date('Y-m-d H:i:s', $send_timestamp);
            $this->enqueue_template($scenario, $booking, $package, 'customer', (string) ($booking['customer_email'] ?? ''), $send_at, true);
        }
    }

    public function process_queue(int $limit = 25): void
    {
        if (!CronResilienceService::acquire(self::CRON_HOOK, 10 * MINUTE_IN_SECONDS)) { return; }
        $processed = 0;
        try {
            foreach ($this->queue->get_due($limit) as $item) {
                $this->process_queue_item($item);
                $processed++;
            }
            CronResilienceService::success(self::CRON_HOOK, ['processed' => $processed, 'limit' => $limit]);
        } catch (\Throwable $e) {
            CronResilienceService::failure(self::CRON_HOOK, $e);
            throw $e;
        }
    }

    public function process_queue_item(array $item): void
    {
        $id = absint($item['id'] ?? 0);
        if ($id <= 0) { return; }
        $this->queue->mark_processing($id);

        $booking = $this->bookings->get_by_id(absint($item['booking_id'] ?? 0));
        if ($booking && !$this->queue_item_still_valid($item, $booking)) {
            $this->queue->update($id, ['status' => 'skipped', 'last_error' => 'Booking status no longer matches email scenario.']);
            $this->log_email($booking, 'email_skipped', 'info', 'Email skipped because booking status changed.', ['queue_id' => $id, 'scenario' => $item['scenario'] ?? '']);
            return;
        }

        $to = sanitize_email((string) ($item['recipient_email'] ?? ''));
        if ($to === '' || !is_email($to)) {
            $this->queue->mark_failed($id, 'Invalid recipient email.', absint($item['attempts'] ?? 0) + 1, absint($item['max_attempts'] ?? 3));
            return;
        }

        $payload = json_decode((string) ($item['payload_json'] ?? ''), true);
        $payload = is_array($payload) ? $payload : [];
        $package = isset($payload['package']) && is_array($payload['package']) ? $payload['package'] : [];
        $booking_data = $booking ?: (isset($payload['booking']) && is_array($payload['booking']) ? $payload['booking'] : []);
        $recipient_type = (string) ($item['recipient_type'] ?? 'customer');
        $scenario = sanitize_key((string) ($item['scenario'] ?? ''));
        $subject = (string) ($item['subject'] ?? '');
        $body = (string) ($item['body'] ?? '');
        $is_html_template = !empty($payload['is_html_template']);
        $email_locale = $this->booking_email_locale($booking_data, (string) ($payload['email_locale'] ?? ''));

        // Queue rows created by older releases may contain an empty body even
        // though the localized template is valid. Re-resolve the current saved
        // template immediately before delivery so both existing and new queue
        // items recover without requiring a database cleanup.
        if ($this->is_effectively_empty_email_body($body) && $scenario !== '') {
            $stored_settings = get_option(SettingsRepository::OPTION_NAME, []);
            $stored_settings = is_array($stored_settings) ? $stored_settings : [];
            $use_html = (int) ($stored_settings['email_template_' . $scenario . '_use_html'] ?? $this->settings->get('email_template_' . $scenario . '_use_html', 0)) === 1;
            $runtime_template = EmailTemplateRegistry::resolve_runtime_payload_for_locale(
                $scenario,
                array_key_exists('email_template_' . $scenario . '_subject', $stored_settings) ? (string) $stored_settings['email_template_' . $scenario . '_subject'] : null,
                array_key_exists('email_template_' . $scenario . '_body', $stored_settings) ? (string) $stored_settings['email_template_' . $scenario . '_body'] : null,
                array_key_exists('email_template_' . $scenario . '_html_body', $stored_settings) ? (string) $stored_settings['email_template_' . $scenario . '_html_body'] : null,
                $use_html,
                $email_locale
            );
            $subject = $this->replace_placeholders((string) $runtime_template['subject'], $booking_data, $package, $recipient_type, $scenario, $email_locale);
            $body = $this->replace_placeholders((string) $runtime_template['body'], $booking_data, $package, $recipient_type, $scenario, $email_locale);
            $is_html_template = (bool) $runtime_template['is_html_template'];
            $payload['is_html_template'] = $is_html_template;
            $this->queue->update($id, [
                'subject' => $subject,
                'body' => $body,
                'payload_json' => wp_json_encode($payload),
            ]);
        }

        $attachments = $this->calendar_invite_attachments($scenario, $recipient_type, $booking_data, $package);
        $preheader = $this->email_preheader($booking_data, $package, $recipient_type, $body, $scenario);
        $message = $this->wrap_html_message($body, $is_html_template, $preheader);
        $sent = wp_mail($to, $subject, $message, $this->headers(), $attachments);

        if (!$sent && !empty($attachments)) {
            $this->log_email($booking ?: [], 'email_attachment_retry', 'warning', 'Email with calendar invite failed. Retrying without ICS attachment.', ['queue_id' => $id, 'scenario' => $item['scenario'] ?? '', 'to' => $to]);
            $sent = wp_mail($to, $subject, $message, $this->headers(), []);
        }

        $this->cleanup_temp_files($attachments);
        if ($sent) {
            $this->queue->mark_sent($id);
            $this->log_email($booking ?: [], 'email_sent', 'success', 'Queued email sent.', ['queue_id' => $id, 'scenario' => $item['scenario'] ?? '', 'to' => $to, 'ics_attached' => !empty($attachments)]);
            Events::dispatch(Events::EMAIL_SENT, ['booking_id' => absint($item['booking_id'] ?? 0), 'booking' => $booking ?: [], 'scenario' => $item['scenario'] ?? '', 'to' => $to]);
            return;
        }

        $attempts = absint($item['attempts'] ?? 0) + 1;
        $max_attempts = absint($item['max_attempts'] ?? 3);
        $error = !empty($attachments) ? 'wp_mail() returned false with and without ICS attachment.' : 'wp_mail() returned false.';
        $this->queue->mark_failed($id, $error, $attempts, $max_attempts);
        $this->log_email($booking ?: [], 'email_failed', 'error', 'Queued email failed.', ['queue_id' => $id, 'scenario' => $item['scenario'] ?? '', 'attempts' => $attempts], $error);
        Events::dispatch(Events::EMAIL_FAILED, ['booking_id' => absint($item['booking_id'] ?? 0), 'booking' => $booking ?: [], 'scenario' => $item['scenario'] ?? '', 'to' => $to, 'error' => $error]);
    }

    private function enqueue_template(string $scenario, array $booking, array $package, string $recipient_type, string $to, string $send_at, bool $unique = true): int
    {
        $email_locale = $this->booking_email_locale($booking);
        $scenarios = EmailTemplateRegistry::scenarios_for_locale($email_locale);
        if (!isset($scenarios[$scenario])) { return 0; }
        if ((int) $this->settings->get('email_template_' . $scenario . '_enabled', 1) !== 1) { return 0; }

        $to = sanitize_email($to);
        if ($to === '' || !is_email($to)) { return 0; }

        $definition = $scenarios[$scenario];
        $stored_settings = get_option(SettingsRepository::OPTION_NAME, []);
        $stored_settings = is_array($stored_settings) ? $stored_settings : [];
        $use_html = (int) ($stored_settings['email_template_' . $scenario . '_use_html'] ?? $this->settings->get('email_template_' . $scenario . '_use_html', 0)) === 1;
        $runtime_template = EmailTemplateRegistry::resolve_runtime_payload_for_locale(
            $scenario,
            array_key_exists('email_template_' . $scenario . '_subject', $stored_settings) ? (string) $stored_settings['email_template_' . $scenario . '_subject'] : null,
            array_key_exists('email_template_' . $scenario . '_body', $stored_settings) ? (string) $stored_settings['email_template_' . $scenario . '_body'] : null,
            array_key_exists('email_template_' . $scenario . '_html_body', $stored_settings) ? (string) $stored_settings['email_template_' . $scenario . '_html_body'] : null,
            $use_html,
            $email_locale
        );
        $booking_request_template = $this->booking_request_template($scenario, $booking, $package, $recipient_type, $email_locale);
        if ($booking_request_template !== null) {
            $runtime_template['subject'] = $booking_request_template['subject'];
            $runtime_template['body'] = $booking_request_template['body'];
            $runtime_template['is_html_template'] = false;
        }
        $subject = $this->replace_placeholders((string) $runtime_template['subject'], $booking, $package, $recipient_type, $scenario, $email_locale);
        $body = $this->replace_placeholders((string) $runtime_template['body'], $booking, $package, $recipient_type, $scenario, $email_locale);
        $use_html = (bool) $runtime_template['is_html_template'];

        $id = $this->queue->enqueue([
            'booking_id' => absint($booking['id'] ?? 0),
            'scenario' => $scenario,
            'recipient_type' => $recipient_type,
            'recipient_email' => $to,
            'subject' => $subject,
            'body' => $body,
            'send_at' => $send_at,
            'max_attempts' => absint($this->settings->get('email_retry_max_attempts', 3)),
            'payload' => ['booking' => $booking, 'package' => $package, 'is_html_template' => $use_html, 'email_locale' => $email_locale],
        ], $unique);

        if ($id > 0) {
            $this->log_email($booking, 'email_queued', 'info', 'Email queued.', ['queue_id' => $id, 'scenario' => $scenario, 'send_at' => $send_at, 'to' => $to]);
            if ($this->should_send_immediately($send_at)) {
                $this->send_immediate_queue_items();
            }
        }

        return $id;
    }


    private function send_immediate_queue_items(): void
    {
        try {
            $this->process_queue(10);
        } catch (\Throwable $e) {
            $this->logs->create([
                'object_type' => 'email_queue',
                'object_id' => 0,
                'event' => 'email_queue_immediate_send_failed',
                'actor_type' => 'system',
                'actor_id' => 0,
                'status' => 'warning',
                'message' => 'Immediate email queue processing failed; scheduled retry will be used.',
                'payload' => ['error' => $e->getMessage()],
            ]);
        }

        $args = [25];
        if (!wp_next_scheduled(self::CRON_HOOK, $args)) {
            wp_schedule_single_event(time() + 60, self::CRON_HOOK, $args);
        }
    }

    private function should_send_immediately(string $send_at): bool
    {
        $send_timestamp = strtotime($send_at);
        if (!$send_timestamp) { return false; }
        return $send_timestamp <= current_time('timestamp') + 60;
    }

    private function calendar_invite_attachments(string $scenario, string $recipient_type, array $booking, array $package): array
    {
        if (empty($booking)) { return []; }
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

    private function enabled(): bool
    {
        return (int) $this->settings->get('email_notifications_enabled', 1) === 1;
    }

    private function payload_booking(array $payload): ?array
    {
        if (isset($payload['booking']) && is_array($payload['booking']) && !empty($payload['booking'])) {
            return $payload['booking'];
        }
        $booking_id = absint($payload['booking_id'] ?? 0);
        return $booking_id > 0 ? $this->bookings->get_by_id($booking_id) : null;
    }

    private function payload_package(array $payload, array $booking): array
    {
        if (isset($payload['package']) && is_array($payload['package'])) { return $payload['package']; }
        $package_id = absint($booking['package_id'] ?? 0);
        return $package_id > 0 ? ($this->packages->get_by_id($package_id) ?: []) : [];
    }

    private function booking_start_timestamp(array $booking): int
    {
        $date = sanitize_text_field((string) ($booking['booking_date'] ?? ''));
        $time = sanitize_text_field((string) ($booking['start_time'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}/', $time)) { return 0; }
        return strtotime($date . ' ' . substr($time, 0, 5)) ?: 0;
    }

    private function booking_can_receive_reminders(array $booking): bool
    {
        return in_array((string) ($booking['status'] ?? ''), ['confirmed'], true);
    }

    private function queue_item_still_valid(array $item, array $booking): bool
    {
        $scenario = (string) ($item['scenario'] ?? '');
        if (strpos($scenario, 'booking_reminder_') === 0) { return $this->booking_can_receive_reminders($booking); }
        if (strpos($scenario, 'booking_cancelled_') === 0) { return (string) ($booking['status'] ?? '') === 'cancelled'; }
        if (strpos($scenario, 'payment_pending_') === 0) { return in_array((string) ($booking['payment_status'] ?? ''), ['pending', 'unpaid', 'partial'], true); }
        if (strpos($scenario, 'payment_received_') === 0) { return (string) ($booking['payment_status'] ?? '') === 'paid'; }
        if (strpos($scenario, 'payment_failed_') === 0) { return (string) ($booking['payment_status'] ?? '') === 'failed'; }
        if (strpos($scenario, 'payment_refunded_') === 0) { return (string) ($booking['payment_status'] ?? '') === 'refunded'; }
        if (strpos($scenario, 'booking_completed_') === 0) { return (string) ($booking['status'] ?? '') === 'completed'; }
        return true;
    }

    /**
     * Booking Request uses lifecycle copy that does not imply a scheduled visit.
     *
     * @return array{subject:string,body:string}|null
     */
    private function booking_request_template(string $scenario, array $booking, array $package, string $recipient_type, ?string $email_locale = null): ?array
    {
        $mode = sanitize_key((string) ($booking['booking_mode'] ?? $booking['pricing_mode'] ?? $package['booking_mode'] ?? ''));
        if ($mode !== 'simple') { return null; }

        return \Slotera\Application\Services\Translations\BookingRequestTranslations::template(
            $scenario,
            $email_locale ?: EmailTemplateRegistry::runtime_locale()
        );
    }

    private function replace_placeholders(string $text, array $booking, array $package, string $recipient_type = 'customer', string $scenario = '', ?string $email_locale = null): string
    {
        $theme_colors = $this->email_theme_colors();
        $preferences = $this->email_display_preferences($booking, $package);
        $text = $this->apply_email_display_preferences($text, $preferences, $recipient_type, $scenario);
        $email_locale = $email_locale ?: $this->booking_email_locale($booking);
        $display = function_exists('sltr_booking_display_data')
            ? sltr_booking_display_data($booking, $package)
            : ['date' => sltr_format_localized_date((string) ($booking['booking_date'] ?? ''), $email_locale), 'time' => ''];
        $display_time = (string) ($display['time'] ?? '');
        $display_start_time = $display_time;
        $display_end_time = '';
        if ($display_time !== '' && preg_match('/^(.+?)\s+[–-]\s+(.+)$/u', $display_time, $time_match)) {
            $display_start_time = trim((string) $time_match[1]);
            $display_end_time = trim((string) $time_match[2]);
        }
        $price = $preferences['hide_price'] ? [
            'base_amount' => '',
            'package_discount' => '',
            'coupon_code' => '',
            'coupon_discount' => '',
            'coupon_expires' => '',
            'discount_amount' => '',
            'final_amount' => '',
            'total_amount' => '',
            'tax_amount' => '',
            'price_summary' => '',
        ] : $this->booking_price_placeholders($booking, $package, $email_locale);

        return strtr($text, [
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
            '{status}' => $this->booking_status_label((string) ($booking['status'] ?? ''), $email_locale),
            '{payment_status}' => $this->payment_status_label((string) ($booking['payment_status'] ?? ''), $email_locale),
            '{status_raw}' => (string) ($booking['status'] ?? ''),
            '{payment_status_raw}' => (string) ($booking['payment_status'] ?? ''),
            '{status_label}' => $this->booking_status_label((string) ($booking['status'] ?? ''), $email_locale),
            '{payment_status_label}' => $this->payment_status_label((string) ($booking['payment_status'] ?? ''), $email_locale),
            '{site_name}' => get_bloginfo('name'),
            '{magic_link}' => '',
            '{cancellation_url}' => (new BookingLifecycleService())->cancellation_url($booking),
            '{reschedule_url}' => !empty($preferences['scheduled_event']) ? '' : (new BookingLifecycleService())->reschedule_url($booking),
            '{base_amount}' => $price['base_amount'],
            '{package_discount}' => $price['package_discount'],
            '{coupon_code}' => $price['coupon_code'],
            '{coupon_discount}' => $price['coupon_discount'],
            '{coupon_expires}' => $price['coupon_expires'],
            '{discount_amount}' => $price['discount_amount'],
            '{final_amount}' => $price['final_amount'],
            '{total_amount}' => $price['total_amount'],
            '{tax_amount}' => $price['tax_amount'],
            '{price_summary}' => $price['price_summary'],
            '{theme_primary_color}' => $theme_colors['primary'],
            '{theme_primary_text_color}' => $theme_colors['primary_text'],
            '{theme_text_color}' => $theme_colors['text'],
            '{theme_muted_text_color}' => $theme_colors['muted'],
            '{theme_card_background_color}' => $theme_colors['card_bg'],
        ]);
    }

    private function booking_price_placeholders(array $booking, array $package, string $locale): array
    {
        $base = (float) ($booking['base_amount'] ?? ($package['price'] ?? $booking['total_amount'] ?? 0));
        $package_discount = (float) ($booking['package_discount_amount'] ?? 0);
        $pricing_adjustment = (float) ($booking['pricing_adjustment_amount'] ?? 0);
        $pricing_adjustment_discount = $pricing_adjustment < 0 ? abs($pricing_adjustment) : 0.0;
        $pricing_adjustment_label = trim((string) ($booking['pricing_adjustment_label'] ?? ''));
        $coupon_discount = (float) ($booking['coupon_discount_amount'] ?? 0);
        $gross = (float) ($booking['gross_amount'] ?? 0);
        $due_now = (float) ($booking['amount_due_now'] ?? 0);
        $paid = (float) ($booking['paid_amount'] ?? 0);
        $remaining = (float) ($booking['remaining_amount'] ?? 0);
        $final = $gross > 0 ? $gross : (float) ($booking['total_amount'] ?? max(0, $base - $package_discount - $coupon_discount));
        $coupon_code = trim((string) ($booking['coupon_code'] ?? ''));
        $discount_total = max(0, $pricing_adjustment_discount + $package_discount + $coupon_discount);
        $tax_amount = (float) ($booking['tax_amount'] ?? 0);
        if ($tax_amount <= 0 && !empty($package['tax_enabled'])) {
            $rate = max(0.0, (float) ($package['tax_rate'] ?? 0));
            $mode = (string) ($package['tax_mode'] ?? 'exclusive');
            if ($rate > 0) {
                $tax_amount = $mode === 'inclusive' ? round($final - ($final / (1 + ($rate / 100))), 2) : round(max(0, $final - max(0, $base - $pricing_adjustment_discount - $package_discount - $coupon_discount)), 2);
            }
        }
        $tax_label = trim((string) ($package['tax_label'] ?? 'VAT'));
        if ($tax_label === '') { $tax_label = sltr__('emails.tax', $locale); }

        $rows = [
            $this->email_price_label('package_price', $locale) => $this->format_money($base),
        ];
        $selected_extras = json_decode((string) ($booking['selected_extras_json'] ?? '[]'), true);
        if (is_array($selected_extras)) {
            foreach ($selected_extras as $extra) {
                if (!is_array($extra)) { continue; }
                $name = trim((string) ($extra['name'] ?? ''));
                if ($name === '') { $name = sltr_t('Extra service'); }
                $rows[$name] = '+' . $this->format_money((float) ($extra['line_amount'] ?? $extra['price'] ?? 0));
            }
        }
        if ($pricing_adjustment_discount > 0) {
            $dynamic_label = $pricing_adjustment_label !== '' ? (new PricingAdjustmentService())->localize_offer_label($pricing_adjustment_label) : sltr_t('Special offer');
            $rows[$dynamic_label] = '-' . $this->format_money($pricing_adjustment_discount);
        }
        if ($package_discount > 0) {
            $rows[$this->email_price_label('package_discount', $locale)] = '-' . $this->format_money($package_discount);
        }
        if ($coupon_discount > 0 || $coupon_code !== '') {
            $label = $this->email_price_label('coupon', $locale);
            if ($coupon_code !== '') {
                $label .= ' (' . $coupon_code . ')';
            }
            $rows[$label] = '-' . $this->format_money($coupon_discount);
        }
        if ($tax_amount > 0) {
            $rows[$tax_label] = $this->format_money($tax_amount);
        }
        $rows[$this->email_price_label('total', $locale)] = $this->format_money($final);
        if ($due_now > 0) { $rows[$this->email_price_label('pay_now', $locale)] = $this->format_money($due_now); }
        if ($remaining > 0) { $rows[$this->email_price_label('pay_later', $locale)] = $this->format_money($remaining); }
        if ($paid > 0) { $rows[$this->email_price_label('paid', $locale)] = $this->format_money($paid); }

        $colors = $this->email_theme_colors();
        $card_background = esc_attr($colors['card_bg']);
        $card_border = esc_attr($colors['card_border']);
        $text_color = esc_attr($colors['text']);
        $muted_color = esc_attr($colors['muted']);
        $summary = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" bgcolor="' . $card_background . '" style="margin:18px 0;border-collapse:collapse;border:1px solid ' . $card_border . ';border-radius:12px;overflow:hidden;background:' . $card_background . ';color:' . $text_color . ';">';
        foreach ($rows as $label => $value) {
            $strong = $label === $this->email_price_label('total', $locale);
            $summary .= '<tr style="background:' . $card_background . ';color:' . $text_color . ';"><td bgcolor="' . $card_background . '" style="padding:10px 12px;border-bottom:1px solid ' . $card_border . ';background:' . $card_background . ';color:' . $muted_color . ';font-family:Arial,sans-serif;font-size:14px;">' . esc_html($label) . '</td>'
                . '<td align="right" bgcolor="' . $card_background . '" style="padding:10px 12px;border-bottom:1px solid ' . $card_border . ';background:' . $card_background . ';color:' . $text_color . ';font-family:Arial,sans-serif;font-size:14px;font-weight:' . ($strong ? '700' : '400') . ';">' . esc_html($value) . '</td></tr>';
        }
        $summary .= '</table>';

        return [
            'base_amount' => $this->format_money($base),
            'package_discount' => $this->format_money($package_discount),
            'coupon_code' => $coupon_code,
            'coupon_discount' => $this->format_money($coupon_discount),
            'coupon_expires' => '',
            'discount_amount' => $this->format_money($discount_total),
            'final_amount' => $this->format_money($final),
            'total_amount' => $this->format_money($final),
            'tax_amount' => $this->format_money($tax_amount),
            'price_summary' => $summary,
        ];
    }



    private function email_price_label(string $key, string $locale): string
    {
        $translation_keys = [
            'package_price' => 'emails.package_price',
            'package_discount' => 'emails.package_discount',
            'coupon' => 'emails.coupon',
            'total' => 'emails.total',
            'pay_now' => 'emails.pay_now',
            'pay_later' => 'emails.pay_later',
            'paid' => 'emails.paid',
        ];

        return isset($translation_keys[$key]) ? sltr__($translation_keys[$key], $locale) : $key;
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

        $hide_time = false;
        if ($mode === 'date_range_inventory' && !empty($booking['resource_id'])) {
            try {
                $event = (new DateRangeInventoryService())->find_scheduled_event($package, absint($booking['resource_id']));
                $hide_time = is_array($event) && empty($event['use_time']);
                if (is_array($event) && !$hide_time) {
                    $event_start_time = substr((string) ($event['start_time'] ?? ''), 0, 5);
                    $event_end_time = substr((string) ($event['end_time'] ?? ''), 0, 5);
                    if ($event_start_time !== '' && $event_start_time === $event_end_time) {
                        $active['display_start_time_only'] = 1;
                    }
                }
            } catch (\Throwable $e) {
                $hide_time = false;
            }
        }

        $display = function_exists('sltr_booking_display_data')
            ? sltr_booking_display_data($booking, $package)
            : ['date' => '', 'time' => ''];
        $scheduled_event = $mode === 'date_range_inventory' && !empty($booking['resource_id']);

        return [
            'hide_price' => !empty($active['hide_price_on_frontend']),
            'start_time_only' => ($mode === 'flex' || $mode === 'date_range_inventory') && !empty($active['display_start_time_only']),
            'no_datetime' => $mode === 'simple',
            'hide_time' => $hide_time || !empty($display['date_only']),
            'date_only' => !empty($display['date_only']),
            'scheduled_event' => $scheduled_event,
            'scheduled_multiday' => $scheduled_event && (string) ($display['time'] ?? '') === '' && strpos((string) ($display['date'] ?? ''), '→') !== false,
        ];
    }

    private function apply_email_display_preferences(string $text, array $preferences, string $recipient_type = 'customer', string $scenario = ''): string
    {
        if (!empty($preferences['scheduled_event'])) {
            $text = preg_replace('/<(p|div|tr|li|section)\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\{reschedule_url\}[^<]*(?:<[^>]+>[^<]*)*<\/\1>/isu', '', $text) ?? $text;
            $text = preg_replace('/^.*\{reschedule_url\}.*(?:\R|$)/imu', '', $text) ?? $text;
            $text = str_replace('{reschedule_url}', '', $text);

            if ($recipient_type === 'admin') {
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
        if (!empty($preferences['start_time_only'])) {
            $text = preg_replace('/\{start_time\}\s*(?:-|–|—|&ndash;|&mdash;)\s*\{end_time\}/iu', '{start_time}', $text) ?? $text;
        }
        if (!empty($preferences['hide_time'])) {
            $time_tokens = '(?:start_time|end_time)';
            $text = preg_replace('/<(p|div|tr|li|section|table)\b[^>]*>[^<]*(?:<[^>]+>[^<]*)*\{' . $time_tokens . '\}[^<]*(?:<[^>]+>[^<]*)*<\/\1>/isu', '', $text) ?? $text;
            $text = preg_replace('/^.*\{' . $time_tokens . '\}.*(?:\R|$)/imu', '', $text) ?? $text;
            $text = preg_replace('/\{' . $time_tokens . '\}/iu', '', $text) ?? $text;
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
            EmailTemplateRegistry::runtime_locale(),
            $recipient_type
        );
    }

    private function booking_email_locale(array $booking, string $fallback = ''): string
    {
        $locale = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($booking['booking_locale'] ?? '')) ?: '';
        if ($locale === '') {
            $locale = preg_replace('/[^A-Za-z0-9_-]/', '', $fallback) ?: '';
        }
        if ($locale === '') {
            return EmailTemplateRegistry::runtime_locale();
        }
        $locale = str_replace('-', '_', $locale);
        $aliases = [
            'et' => 'et_EE', 'fi' => 'fi_FI', 'hr' => 'hr_HR', 'lv' => 'lv_LV',
            'el' => 'el_GR', 'nb' => 'no_NO', 'nb_NO' => 'no_NO', 'no' => 'no_NO',
            'de' => 'de_DE', 'ru' => 'ru_RU', 'fr' => 'fr_FR',
        ];
        $locale = $aliases[$locale] ?? $locale;
        return TranslationRegistry::is_visible_locale($locale) ? $locale : EmailTemplateRegistry::runtime_locale();
    }

    private function booking_status_label(string $status, ?string $locale = null): string
    {
        return function_exists('sltr_booking_status_label')
            ? sltr_booking_status_label($status, 'emails', $locale)
            : ucwords(str_replace('_', ' ', $status));
    }

    private function payment_status_label(string $status, ?string $locale = null): string
    {
        return function_exists('sltr_payment_status_label')
            ? sltr_payment_status_label($status, 'emails', $locale)
            : ucwords(str_replace('_', ' ', $status));
    }

    private function format_money(float $amount): string
    {
        $currency = strtoupper((string) $this->settings->get('payment_currency', 'EUR'));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) { $currency = 'EUR'; }
        return number_format($amount, 2, '.', '') . ' ' . $currency;
    }

    private function is_effectively_empty_email_body(string $value): bool
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(['\\n', '\\r', '\\t'], ' ', $value);
        $value = preg_replace('/<(?:br|hr)\s*\/?\s*>/iu', ' ', $value) ?? $value;
        $value = wp_strip_all_tags($value, true);
        $value = preg_replace('/[\s\x{00A0}]+/u', '', $value) ?? $value;
        return $value === '';
    }

    private function headers(): array
    {
        $from_name = sanitize_text_field((string) $this->settings->get('email_from_name', get_bloginfo('name')));
        $from_email = sanitize_email((string) $this->settings->get('email_from_address', get_option('admin_email')));
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        if ($from_email !== '' && is_email($from_email)) { $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>'; }
        return $headers;
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

    private function log_email(array $booking, string $event, string $status, string $message, array $payload = [], string $error = ''): void
    {
        $this->logs->create([
            'object_type' => 'booking',
            'object_id' => absint($booking['id'] ?? 0),
            'event' => $event,
            'actor_type' => 'system',
            'actor_id' => 0,
            'status' => $status,
            'message' => $message,
            'error_message' => $error,
            'payload' => $payload,
        ]);
    }
}
