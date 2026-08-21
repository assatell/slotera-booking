<?php
declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\CouponRepository;
use Slotera\Infrastructure\Repositories\MarketingCampaignRepository;
use Slotera\Infrastructure\Repositories\MarketingLogRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class MarketingEmailService
{
    public const CRON_HOOK = 'sltr_process_marketing_queue';

    private SettingsRepository $settings;
    private MarketingCampaignRepository $campaigns;
    private MarketingLogRepository $logs;
    private PackageRepository $packages;
    private CouponRepository $coupons;
    private MarketingOptOutService $opt_out;
    private MarketingConsentService $consent;

    public function __construct(?SettingsRepository $settings = null, ?MarketingCampaignRepository $campaigns = null, ?MarketingLogRepository $logs = null, ?PackageRepository $packages = null, ?CouponRepository $coupons = null, ?MarketingOptOutService $opt_out = null, ?MarketingConsentService $consent = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
        $this->campaigns = $campaigns ?: new MarketingCampaignRepository();
        $this->logs = $logs ?: new MarketingLogRepository();
        $this->packages = $packages ?: new PackageRepository();
        $this->coupons = $coupons ?: new CouponRepository();
        $this->opt_out = $opt_out ?: new MarketingOptOutService();
        $this->consent = $consent ?: new MarketingConsentService();
    }

    public function audience_for_campaign(array $campaign): array
    {
        global $wpdb;
        $bookings = Database::bookings_table();
        $where = "customer_email <> ''";
        $having = [];
        $where_args = [];
        $having_args = [];
        $type = (string) ($campaign['audience_type'] ?? 'all');
        if ($type === 'advanced' && !(new LicenseService())->can_use_advanced_marketing()) {
            $type = 'all';
        }

        if (($type === 'package' || $type === 'advanced') && absint($campaign['package_id'] ?? 0) > 0) {
            $where .= ' AND package_id=%d';
            $where_args[] = absint($campaign['package_id']);
        }
        if ($type === 'completed') {
            $where .= " AND status='completed'";
        } elseif ($type === 'inactive_30') {
            $where .= ' AND customer_email NOT IN (SELECT customer_email FROM ' . $bookings . ' WHERE booking_date >= %s AND customer_email <> \'\')';
            $where_args[] = wp_date('Y-m-d', current_time('timestamp') - 30 * DAY_IN_SECONDS);
        }

        if ($type === 'advanced') {
            $statuses = $this->csv_keys((string) ($campaign['audience_statuses'] ?? ''));
            if (!empty($statuses)) {
                $where .= ' AND status IN (' . implode(',', array_fill(0, count($statuses), '%s')) . ')';
                $where_args = array_merge($where_args, $statuses);
            }
            $payment_statuses = $this->csv_keys((string) ($campaign['audience_payment_statuses'] ?? ''));
            if (!empty($payment_statuses)) {
                $where .= ' AND payment_status IN (' . implode(',', array_fill(0, count($payment_statuses), '%s')) . ')';
                $where_args = array_merge($where_args, $payment_statuses);
            }

            $coupon_filter = sanitize_key((string) ($campaign['audience_coupon_filter'] ?? 'any'));
            if ($coupon_filter === 'used_selected_coupon' && absint($campaign['coupon_id'] ?? 0) > 0) {
                $where .= ' AND coupon_id=%d';
                $where_args[] = absint($campaign['coupon_id']);
            }

            $days = max(1, absint($campaign['audience_last_booking_days'] ?? 30));
            $date = wp_date('Y-m-d', current_time('timestamp') - $days * DAY_IN_SECONDS);
            $last_mode = sanitize_key((string) ($campaign['audience_last_booking_mode'] ?? 'any'));
            if ($last_mode === 'within_days') {
                $having[] = 'last_booking_date >= %s';
                $having_args[] = $date;
            } elseif ($last_mode === 'older_than_days') {
                $having[] = 'last_booking_date < %s';
                $having_args[] = $date;
            }

            $min_bookings = absint($campaign['audience_min_bookings'] ?? 0);
            $max_bookings = absint($campaign['audience_max_bookings'] ?? 0);
            if ($min_bookings > 0) { $having[] = 'booking_count >= %d'; $having_args[] = $min_bookings; }
            if ($max_bookings > 0) { $having[] = 'booking_count <= %d'; $having_args[] = $max_bookings; }

            $min_spent = max(0, (float) ($campaign['audience_min_spent'] ?? 0));
            $max_spent = max(0, (float) ($campaign['audience_max_spent'] ?? 0));
            if ($min_spent > 0) { $having[] = 'total_spent >= %f'; $having_args[] = $min_spent; }
            if ($max_spent > 0) { $having[] = 'total_spent <= %f'; $having_args[] = $max_spent; }

            if ($coupon_filter === 'used_coupon') { $having[] = 'coupon_booking_count > 0'; }
            if ($coupon_filter === 'never_used_coupon') { $having[] = 'coupon_booking_count = 0'; }
        }

        $sql = "SELECT customer_email, MAX(customer_name) AS customer_name, MAX(package_id) AS package_id, MAX(booking_date) AS last_booking_date, COUNT(*) AS booking_count, COALESCE(SUM(total_amount),0) AS total_spent, SUM(CASE WHEN coupon_id > 0 OR coupon_code <> '' THEN 1 ELSE 0 END) AS coupon_booking_count FROM {$bookings} WHERE {$where} GROUP BY customer_email";
        if (!empty($having)) { $sql .= ' HAVING ' . implode(' AND ', $having); }
        $sql .= ' ORDER BY last_booking_date DESC';
        $args = array_merge($where_args, $having_args);
        $prepared = !empty($args) ? $wpdb->prepare($sql, $args) : $sql;
        $rows = $wpdb->get_results($prepared, ARRAY_A) ?: [];
        return array_values(array_filter($rows, function (array $customer): bool {
            return $this->consent->has_consent((string) ($customer['customer_email'] ?? ''));
        }));
    }

    private function csv_keys(string $csv): array
    {
        $values = array_filter(array_map('sanitize_key', array_map('trim', explode(',', $csv))));
        return array_values(array_unique($values));
    }

    public function send_test(int $campaign_id, string $to): bool
    {
        if (!(new LicenseService())->can_use_marketing()) { return false; }
        $to = sanitize_email($to);
        if ($to === '' || !is_email($to)) { return false; }
        $message = $this->preview_message($campaign_id, $to);
        if (!$message) { return false; }
        return wp_mail($to, '[TEST] ' . $message['subject'], $message['body'], $this->headers($to));
    }

    public function preview_message(int $campaign_id, string $preview_email = ''): ?array
    {
        $campaign = $this->campaigns->get_by_id($campaign_id);
        if (!$campaign) { return null; }
        $customer = $this->preview_customer_for_campaign($campaign, $preview_email);
        return $this->build_message($campaign, $customer, $this->preview_coupon_for_campaign($campaign));
    }

    public function preview_message_for_campaign(array $campaign, string $preview_email = ''): ?array
    {
        $customer = $this->preview_customer_for_campaign($campaign, $preview_email);
        return $this->build_message($campaign, $customer, $this->preview_coupon_for_campaign($campaign));
    }

    public function send_test_for_campaign(array $campaign, string $to): bool
    {
        if (!(new LicenseService())->can_use_marketing()) { return false; }
        $to = sanitize_email($to);
        if ($to === '' || !is_email($to)) { return false; }
        $message = $this->preview_message_for_campaign($campaign, $to);
        if (!$message) { return false; }
        return wp_mail($to, '[TEST] ' . $message['subject'], $message['body'], $this->headers($to));
    }

    private function preview_customer_for_campaign(array $campaign, string $preview_email = ''): array
    {
        $preview_email = sanitize_email($preview_email);
        $audience = $this->audience_for_campaign($campaign);
        if ($preview_email !== '') {
            foreach ($audience as $customer) {
                if (strtolower((string) ($customer['customer_email'] ?? '')) === strtolower($preview_email)) {
                    return $customer;
                }
            }
        }
        if (!empty($audience[0]) && is_array($audience[0])) {
            return $audience[0];
        }
        return [
            'customer_email' => $preview_email !== '' ? $preview_email : sanitize_email((string) get_option('admin_email')),
            'customer_name' => __('Preview Customer', 'slotera-booking'),
            'package_id' => absint($campaign['package_id'] ?? 0),
            'last_booking_date' => current_time('Y-m-d'),
            'booking_count' => 1,
            'total_spent' => 0,
        ];
    }

    public function register_queue_hooks(): void
    {
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
        add_action(self::CRON_HOOK, [$this, 'process_queue']);
    }

    public static function register_lazy_queue_hooks(): void
    {
        add_filter('cron_schedules', [self::class, 'add_cron_schedules_static']);
        add_action(self::CRON_HOOK, static function (): void {
            (new self())->process_queue();
        });
    }

    public static function add_cron_schedules_static(array $schedules): array
    {
        foreach ([1, 5, 10, 15] as $minutes) {
            $key = 'sltr_marketing_every_' . $minutes . '_minutes';
            $schedules[$key] = [
                'interval' => $minutes * MINUTE_IN_SECONDS,
                'display' => sprintf(__('Slotera marketing queue every %d minutes', 'slotera-booking'), $minutes),
            ];
        }
        return $schedules;
    }

    public static function activate(): void
    {
        add_filter('cron_schedules', [new self(), 'add_cron_schedules']);
        (new self())->ensure_scheduled(true);
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public function add_cron_schedules(array $schedules): array
    {
        foreach ([1, 5, 10, 15] as $minutes) {
            $key = 'sltr_marketing_every_' . $minutes . '_minutes';
            $schedules[$key] = [
                'interval' => $minutes * MINUTE_IN_SECONDS,
                'display' => sprintf(__('Slotera marketing queue every %d minutes', 'slotera-booking'), $minutes),
            ];
        }
        return $schedules;
    }

    public function ensure_scheduled(bool $force = false): void
    {
        $desired = $this->schedule_key();
        $current = wp_get_schedule(self::CRON_HOOK);
        if ($force || ($current !== false && $current !== $desired)) {
            wp_clear_scheduled_hook(self::CRON_HOOK);
        }
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, $desired, self::CRON_HOOK);
        }
    }

    public function send_campaign(int $campaign_id): array
    {
        return $this->queue_campaign($campaign_id);
    }

    public function queue_campaign(int $campaign_id, array $options = []): array
    {
        if (!(new LicenseService())->can_use_marketing()) { return ['queued' => 0, 'skipped' => 0, 'reason' => 'license_limited']; }
        $readiness = $this->email_delivery_readiness();
        if (!$readiness['ready']) { return ['queued' => 0, 'skipped' => 0, 'reason' => 'email_settings_required', 'email_settings_issue' => $readiness['issue']]; }
        $campaign = $this->campaigns->get_by_id($campaign_id);
        if (!$campaign) { return ['queued' => 0, 'skipped' => 0]; }

        $max_attempts = max(1, min(10, absint($this->settings->get('marketing_max_attempts', 3))));
        $automation_key = sanitize_key((string) ($options['automation'] ?? ''));
        $automation_cooldown_days = max(1, absint($options['cooldown_days'] ?? 90));
        $result = ['queued' => 0, 'skipped' => 0];
        $result = $this->queue_recipients_for_campaign($campaign_id, $this->audience_for_campaign($campaign), $options);

        $this->campaigns->update_status($campaign_id, $result['queued'] > 0 ? 'queued' : 'completed');
        $this->ensure_scheduled();
        return $result;
    }

    /**
     * Queue a custom recipient list for an existing campaign. Used by automations that
     * need their own audience query, while reusing coupons, templates, logs and queue.
     */
    public function queue_recipients_for_campaign(int $campaign_id, array $recipients, array $options = []): array
    {
        if (!(new LicenseService())->can_use_marketing()) { return ['queued' => 0, 'skipped' => count($recipients), 'reason' => 'license_limited']; }
        $readiness = $this->email_delivery_readiness();
        if (!$readiness['ready']) { return ['queued' => 0, 'skipped' => count($recipients), 'reason' => 'email_settings_required', 'email_settings_issue' => $readiness['issue']]; }
        $campaign = $this->campaigns->get_by_id($campaign_id);
        if (!$campaign) { return ['queued' => 0, 'skipped' => 0]; }

        $max_attempts = max(1, min(10, absint($this->settings->get('marketing_max_attempts', 3))));
        $automation_key = sanitize_key((string) ($options['automation'] ?? ''));
        $automation_cooldown_days = max(1, absint($options['cooldown_days'] ?? 90));
        $result = [
            'queued' => 0,
            'skipped' => 0,
            'skipped_already_queued' => 0,
            'skipped_unsubscribed' => 0,
            'skipped_invalid_email' => 0,
            'skipped_invalid_recipient' => 0,
        ];

        foreach ($recipients as $customer) {
            if (!is_array($customer)) {
                $result['skipped']++;
                $result['skipped_invalid_recipient']++;
                continue;
            }
            $email = sanitize_email((string) ($customer['customer_email'] ?? ''));
            if ($email === '' || !$this->consent->has_consent($email)) {
                $result['skipped']++;
                $result['skipped_invalid_recipient']++;
                continue;
            }
            if ($email !== '' && $this->should_check_opt_out() && $this->opt_out->is_unsubscribed($email)) {
                $result['skipped']++;
                $result['skipped_unsubscribed']++;
                if (!$this->logs->exists_for_email($campaign_id, $email)) {
                    $this->logs->create([
                        'campaign_id' => $campaign_id,
                        'customer_email' => $email,
                        'customer_name' => (string) ($customer['customer_name'] ?? ''),
                        'status' => 'skipped',
                        'error_message' => 'Recipient has unsubscribed from marketing emails.',
                        'max_attempts' => $max_attempts,
                        'payload' => ['customer' => $this->customer_payload($customer), 'suppressed' => 'marketing_opt_out', 'privacy' => $this->privacy_payload_meta()],
                    ]);
                }
                continue;
            }
            $already_queued = $automation_key !== ''
                ? $this->logs->exists_recent_automation_for_email($automation_key, $email, $automation_cooldown_days)
                : $this->logs->exists_for_email($campaign_id, $email);
            if ($email === '' || !is_email($email) || $already_queued) {
                $result['skipped']++;
                if ($already_queued) {
                    $result['skipped_already_queued']++;
                } else {
                    $result['skipped_invalid_email']++;
                }
                if ($email !== '' && !is_email($email) && !$this->logs->exists_for_email($campaign_id, $email)) {
                    $this->logs->create([
                        'campaign_id' => $campaign_id,
                        'customer_email' => $email,
                        'customer_name' => (string) ($customer['customer_name'] ?? ''),
                        'status' => 'skipped',
                        'error_message' => 'Invalid recipient email.',
                        'max_attempts' => $max_attempts,
                        'payload' => ['customer' => $this->customer_payload($customer), 'privacy' => $this->privacy_payload_meta()],
                    ]);
                }
                continue;
            }
            $recipient_coupon = $this->coupon_for_recipient($campaign, $customer);
            $message = $this->build_message($campaign, $customer, $recipient_coupon);
            $payload = ['customer' => $this->customer_payload($customer), 'privacy' => $this->privacy_payload_meta()];
            if ($automation_key !== '') {
                $payload['automation'] = $automation_key;
            }
            if (!empty($recipient_coupon)) {
                $payload['coupon'] = $recipient_coupon;
            }
            $log_id = $this->logs->create([
                'campaign_id' => $campaign_id,
                'customer_email' => $email,
                'customer_name' => (string) ($customer['customer_name'] ?? ''),
                'status' => 'pending',
                'subject' => $message['subject'],
                'max_attempts' => $max_attempts,
                'payload' => $payload,
            ]);
            if ($log_id > 0) { $result['queued']++; }
        }

        return $result;
    }

    public function process_queue(?int $limit = null): array
    {
        if (!CronResilienceService::acquire(self::CRON_HOOK, 15 * MINUTE_IN_SECONDS)) { return ['sent' => 0, 'failed' => 0, 'remaining' => 0, 'reason' => 'locked']; }
        try {
            if (!(new LicenseService())->can_use_marketing()) {
                $result = ['sent' => 0, 'failed' => 0, 'remaining' => 0, 'reason' => 'license_limited'];
                CronResilienceService::success(self::CRON_HOOK, $result);
                return $result;
            }
            $readiness = $this->email_delivery_readiness();
            if (!$readiness['ready']) {
                $result = ['sent' => 0, 'failed' => 0, 'remaining' => 0, 'reason' => 'email_settings_required', 'email_settings_issue' => $readiness['issue']];
                CronResilienceService::success(self::CRON_HOOK, $result);
                return $result;
            }
            $limit = $limit === null ? max(1, min(50, absint($this->settings->get('marketing_emails_per_batch', 10)))) : max(1, min(50, $limit));
            $license = new LicenseService();
            $batch_limit = $license->marketing_batch_limit();
            if ($batch_limit > 0) { $limit = min($limit, $batch_limit); }
            $result = ['sent' => 0, 'failed' => 0, 'remaining' => 0];
            foreach ($this->logs->get_processable($limit) as $item) {
                $processed = $this->process_queue_item($item);
                if ($processed === 'sent') { $result['sent']++; }
                if ($processed === 'failed') { $result['failed']++; }
            }
            foreach ($this->campaigns->get_processable_ids() as $campaign_id) {
                $this->update_campaign_status_after_processing($campaign_id);
            }
            CronResilienceService::success(self::CRON_HOOK, $result);
            return $result;
        } catch (\Throwable $e) {
            CronResilienceService::failure(self::CRON_HOOK, $e);
            throw $e;
        }
    }

    public function process_campaign_queue(int $campaign_id, ?int $limit = null): array
    {
        if (!(new LicenseService())->can_use_marketing()) { return ['sent' => 0, 'failed' => 0, 'reason' => 'license_limited']; }
        $readiness = $this->email_delivery_readiness();
        if (!$readiness['ready']) { return ['sent' => 0, 'failed' => 0, 'reason' => 'email_settings_required', 'email_settings_issue' => $readiness['issue']]; }
        $limit = $limit === null ? max(1, min(50, absint($this->settings->get('marketing_emails_per_batch', 10)))) : max(1, min(50, $limit));
        $batch_limit = (new LicenseService())->marketing_batch_limit();
        if ($batch_limit > 0) { $limit = min($limit, $batch_limit); }
        $result = ['sent' => 0, 'failed' => 0];
        foreach ($this->logs->get_processable_for_campaign($campaign_id, $limit) as $item) {
            $processed = $this->process_queue_item($item);
            if ($processed === 'sent') { $result['sent']++; }
            if ($processed === 'failed') { $result['failed']++; }
        }
        $this->update_campaign_status_after_processing($campaign_id);
        return $result;
    }

    public function process_queue_item(array $item): string
    {
        if (!(new LicenseService())->can_use_marketing()) { return 'failed'; }
        if (!$this->email_delivery_readiness()['ready']) { return 'failed'; }
        $id = absint($item['id'] ?? 0);
        $campaign_id = absint($item['campaign_id'] ?? 0);
        if ($id <= 0 || $campaign_id <= 0) { return 'failed'; }
        $campaign = $this->campaigns->get_by_id($campaign_id);
        if (!$campaign || !in_array((string) ($campaign['status'] ?? ''), ['queued', 'sending'], true)) { return 'failed'; }

        $this->campaigns->update_status($campaign_id, 'sending');
        $this->logs->mark_sending($id);

        $email = sanitize_email((string) ($item['customer_email'] ?? ''));
        if ($email === '' || !$this->consent->has_consent($email)) {
            $this->logs->update($id, ['status' => 'skipped', 'error_message' => 'Recipient has no explicit marketing consent.']);
            return 'failed';
        }
        if ($email !== '' && $this->should_check_opt_out() && $this->opt_out->is_unsubscribed($email)) {
            $this->logs->update($id, ['status' => 'skipped', 'error_message' => 'Recipient has unsubscribed from marketing emails.']);
            return 'failed';
        }
        if ($email === '' || !is_email($email)) {
            $this->logs->update($id, ['status' => 'skipped', 'error_message' => 'Invalid recipient email.']);
            return 'failed';
        }

        $payload = json_decode((string) ($item['payload_json'] ?? ''), true);
        $payload = is_array($payload) ? $payload : [];
        $customer = isset($payload['customer']) && is_array($payload['customer']) ? $payload['customer'] : [
            'customer_email' => $email,
            'customer_name' => (string) ($item['customer_name'] ?? ''),
            'package_id' => absint($campaign['package_id'] ?? 0),
        ];
        $recipient_coupon = isset($payload['coupon']) && is_array($payload['coupon']) ? $payload['coupon'] : [];
        $message = $this->build_message($campaign, $customer, $recipient_coupon);
        $sent = wp_mail($email, $message['subject'], $message['body'], $this->headers($email));
        if ($sent) {
            $this->logs->mark_sent($id);
            return 'sent';
        }

        $attempts = absint($item['attempts'] ?? 0) + 1;
        $max_attempts = max(1, absint($item['max_attempts'] ?? $this->settings->get('marketing_max_attempts', 3)));
        $this->logs->mark_failed($id, 'wp_mail() returned false.', $attempts, $max_attempts);
        return 'failed';
    }

    private function coupon_for_recipient(array $campaign, array $customer): array
    {
        $base_id = absint($campaign['coupon_id'] ?? 0);
        if ($base_id <= 0) { return []; }
        $base = $this->coupons->get_by_id($base_id) ?: [];
        if (empty($base)) { return []; }
        if ((int) ($campaign['generate_unique_coupons'] ?? 0) !== 1 || !(new LicenseService())->can_use_unique_coupons()) { return $base; }

        $email = sanitize_email((string) ($customer['customer_email'] ?? ''));
        $code = $this->generate_unique_coupon_code((string) ($base['code'] ?? ''));
        $id = $this->coupons->create([
            'code' => $code,
            'description' => '[slotera:generated_personal_coupon] ' . sprintf(__('Personal coupon generated by marketing campaign #%1$d for %2$s.', 'slotera-booking'), absint($campaign['id'] ?? 0), $email !== '' ? $email : __('recipient', 'slotera-booking')),
            'discount_type' => (string) ($base['discount_type'] ?? 'percent'),
            'discount_value' => (float) ($base['discount_value'] ?? 0),
            'usage_limit' => 1,
            'usage_limit_per_email' => 1,
            'expires_at' => (string) ($base['expires_at'] ?? ''),
            'package_ids' => (string) ($base['package_ids'] ?? ''),
            'min_amount' => (float) ($base['min_amount'] ?? 0),
            'is_active' => 1,
        ]);
        if ($id <= 0) { return $base; }
        return $this->coupons->get_by_id($id) ?: $base;
    }

    private function preview_coupon_for_campaign(array $campaign): array
    {
        if (!empty($campaign['automation_offer']) && is_array($campaign['automation_offer'])) {
            $offer = $campaign['automation_offer'];
            return [
                'id' => 0,
                'code' => $this->coupons->normalize_code((string) ($offer['code'] ?? 'PREVIEW-OFFER')),
                'discount_type' => in_array((string) ($offer['discount_type'] ?? 'percent'), ['percent', 'fixed'], true) ? (string) $offer['discount_type'] : 'percent',
                'discount_value' => max(0, (float) ($offer['discount_value'] ?? 10)),
                'usage_limit' => 1,
                'usage_limit_per_email' => 1,
                'used_count' => 0,
                'expires_at' => (string) ($offer['expires_at'] ?? ''),
                'package_ids' => (string) ($offer['package_ids'] ?? ''),
                'min_amount' => 0,
                'is_active' => 1,
            ];
        }
        $base_id = absint($campaign['coupon_id'] ?? 0);
        if ($base_id <= 0) { return []; }
        $base = $this->coupons->get_by_id($base_id) ?: [];
        if (empty($base) || (int) ($campaign['generate_unique_coupons'] ?? 0) !== 1 || !(new LicenseService())->can_use_unique_coupons()) { return $base; }
        $base['code'] = $this->coupons->normalize_code((string) ($base['code'] ?? 'COUPON') . '-TEST');
        return $base;
    }

    private function generate_unique_coupon_code(string $base_code): string
    {
        $base = $this->coupons->normalize_code($base_code !== '' ? $base_code : 'OFFER');
        $base = substr($base, 0, 38);
        for ($i = 0; $i < 20; $i++) {
            $suffix = strtoupper(substr(wp_generate_password(8, false, false), 0, 8));
            $suffix = preg_replace('/[^A-Z0-9]/', '', $suffix) ?: (string) wp_rand(100000, 999999);
            $code = $this->coupons->normalize_code($base . '-' . $suffix);
            if (!$this->coupons->get_by_code($code)) { return $code; }
        }
        return $this->coupons->normalize_code($base . '-' . strtoupper(substr(hash('crc32b', microtime(true) . wp_rand()), 0, 8)));
    }

    private function update_campaign_status_after_processing(int $campaign_id): void
    {
        $campaign = $this->campaigns->get_by_id($campaign_id);
        if (!$campaign || !in_array((string) ($campaign['status'] ?? ''), ['queued', 'sending'], true)) { return; }
        $counts = $this->logs->counts_for_campaign($campaign_id);
        if (($counts['pending'] ?? 0) > 0 || ($counts['sending'] ?? 0) > 0) {
            $this->campaigns->update_status($campaign_id, 'sending');
            return;
        }
        $this->campaigns->update_status($campaign_id, 'completed');
    }

    private function schedule_key(): string
    {
        $minutes = absint($this->settings->get('marketing_cron_interval', 5));
        if (!in_array($minutes, [1, 5, 10, 15], true)) { $minutes = 5; }
        return 'sltr_marketing_every_' . $minutes . '_minutes';
    }

    private function build_message(array $campaign, array $customer, array $recipient_coupon = []): array
    {
        if (sanitize_key((string) ($campaign['source'] ?? '')) === 'promotion_digest') {
            $subject = trim((string) ($campaign['subject_override'] ?? ''));
            if ($subject === '') { $subject = __('Special offers', 'slotera-booking'); }
            $content = wp_kses_post((string) ($campaign['marketing_message'] ?? ''));
            if ($this->should_require_unsubscribe_link()) {
                $content .= $this->unsubscribe_footer_html((string) ($customer['customer_email'] ?? ''));
            }
            return ['subject' => $subject, 'body' => $this->wrap_html_message($content, true)];
        }
        $scenarios = EmailTemplateRegistry::scenarios();
        $key = sanitize_key((string) ($campaign['template_key'] ?? ''));
        $definition = $scenarios[$key] ?? reset($scenarios);
        $subject = (string) ($campaign['subject_override'] ?: $this->settings->get('email_template_' . $key . '_subject', $definition['default_subject'] ?? 'Special offer'));
        $use_html = (int) $this->settings->get('email_template_' . $key . '_use_html', 0) === 1;
        $html_body = (string) $this->settings->get('email_template_' . $key . '_html_body', '');
        $plain_body = (string) $this->settings->get('email_template_' . $key . '_body', $definition['default_body'] ?? 'Hello {customer_name}');
        $content = $use_html && $html_body !== '' ? $html_body : $plain_body;
        $package = absint($customer['package_id'] ?? 0) > 0 ? ($this->packages->get_by_id(absint($customer['package_id'])) ?: []) : [];
        $coupon = !empty($recipient_coupon) ? $recipient_coupon : (absint($campaign['coupon_id'] ?? 0) > 0 ? ($this->coupons->get_by_id(absint($campaign['coupon_id'])) ?: []) : []);
        $replaced_subject = $this->replace_placeholders($subject, $customer, $package, $coupon, $campaign);
        $replaced_content = $this->replace_placeholders($content, $customer, $package, $coupon, $campaign);
        if ($this->should_require_unsubscribe_link() && strpos($content, '{unsubscribe_url}') === false) {
            $replaced_content .= "\n\n" . $this->unsubscribe_footer_html((string) ($customer['customer_email'] ?? ''));
        }
        if ((int) ($campaign['cta_enabled'] ?? 1) === 1 && strpos($content, '{cta_button}') === false) {
            $cta = $this->cta_button_html($campaign, $customer, $package);
            if ($cta !== '') {
                $replaced_content .= "

" . $cta;
            }
        }
        return ['subject' => $replaced_subject, 'body' => $this->wrap_html_message($replaced_content, $use_html && $html_body !== '')];
    }

    private function replace_placeholders(string $text, array $customer, array $package, array $coupon, array $campaign = []): string
    {
        $colors = $this->email_theme_colors();
        $booking_url = $this->booking_url($campaign, $customer, $package);
        $package_url = $this->package_url($package);
        $cta_url = $this->cta_url($campaign, $customer, $package);
        return strtr($text, [
            '{headline}' => $this->campaign_headline($campaign),
            '{message}' => (string) ($campaign['marketing_message'] ?? ''),
            '{submessage}' => (string) ($campaign['marketing_submessage'] ?? ''),
            '{customer_name}' => (string) ($customer['customer_name'] ?? ''),
            '{customer_email}' => (string) ($customer['customer_email'] ?? ''),
            '{package_title}' => (string) ($package['title'] ?? ''),
            '{site_name}' => get_bloginfo('name'),
            '{coupon_code}' => (string) ($coupon['code'] ?? ''),
            '{coupon_discount}' => $this->coupon_discount_label($coupon),
            '{coupon_expires}' => (string) ($coupon['expires_at'] ?? ''),
            '{booking_url}' => $booking_url,
            '{package_url}' => $package_url,
            '{cta_url}' => $cta_url,
            '{unsubscribe_url}' => $this->opt_out->unsubscribe_url((string) ($customer['customer_email'] ?? '')),
            '{cta_button}' => (int) ($campaign['cta_enabled'] ?? 1) === 1 ? $this->cta_button_html($campaign, $customer, $package) : '',
            '{theme_primary_color}' => $colors['primary'],
            '{theme_primary_text_color}' => $colors['primary_text'],
            '{theme_text_color}' => $colors['text'],
            '{theme_muted_text_color}' => $colors['muted'],
            '{theme_card_background_color}' => $colors['card_bg'],
        ]);
    }


    /**
     * Marketing uses the same delivery configuration as all Slotera email.
     * Privacy/consent handling remains recipient-level (opt-out/unsubscribe) and
     * is not a separate global sending gate.
     *
     * @return array{ready:bool,issue:string}
     */
    public function email_delivery_readiness(): array
    {
        if ((int) $this->settings->get('email_notifications_enabled', 1) !== 1) {
            return ['ready' => false, 'issue' => 'email_notifications_disabled'];
        }

        $from_name = trim((string) $this->settings->get('email_from_name', ''));
        $from_email = sanitize_email((string) $this->settings->get('email_from_address', ''));
        if ($from_name === '') { return ['ready' => false, 'issue' => 'from_name_missing']; }
        if ($from_email === '' || !is_email($from_email)) { return ['ready' => false, 'issue' => 'from_email_invalid']; }

        $external_mail_plugin_detected = (new ExternalMailPluginDetector())->has_external_delivery_plugin();
        if ((int) $this->settings->get('smtp_enabled', 0) === 1 && !$external_mail_plugin_detected) {
            $host = trim((string) $this->settings->get('smtp_host', ''));
            $port = (int) $this->settings->get('smtp_port', 587);
            $sender_name = trim((string) $this->settings->get('smtp_sender_name', $from_name));
            $sender_email = sanitize_email((string) $this->settings->get('smtp_sender_email', $from_email));
            if ($host === '') { return ['ready' => false, 'issue' => 'smtp_host_missing']; }
            if ($port < 1 || $port > 65535) { return ['ready' => false, 'issue' => 'smtp_port_invalid']; }
            if ($sender_name === '') { return ['ready' => false, 'issue' => 'smtp_sender_name_missing']; }
            if ($sender_email === '' || !is_email($sender_email)) { return ['ready' => false, 'issue' => 'smtp_sender_email_invalid']; }
            if ((int) $this->settings->get('smtp_auth', 1) === 1) {
                if (trim((string) $this->settings->get('smtp_username', '')) === '') { return ['ready' => false, 'issue' => 'smtp_username_missing']; }
                if ((string) $this->settings->get('smtp_password', '') === '') { return ['ready' => false, 'issue' => 'smtp_password_missing']; }
            }
        }

        return ['ready' => true, 'issue' => ''];
    }

    private function should_check_opt_out(): bool
    {
        return true;
    }

    private function should_require_unsubscribe_link(): bool
    {
        return true;
    }

    private function customer_payload(array $customer): array
    {
        return [
            'customer_email' => sanitize_email((string) ($customer['customer_email'] ?? '')),
            'customer_name' => sanitize_text_field((string) ($customer['customer_name'] ?? '')),
            'package_id' => absint($customer['package_id'] ?? 0),
            'last_booking_date' => sanitize_text_field((string) ($customer['last_booking_date'] ?? '')),
            'booking_count' => absint($customer['booking_count'] ?? 0),
        ];
    }

    private function privacy_payload_meta(): array
    {
        return [
            'opt_out_checked' => $this->should_check_opt_out() ? 1 : 0,
            'unsubscribe_required' => $this->should_require_unsubscribe_link() ? 1 : 0,
            'payload_minimized' => 1,
            'explicit_consent_required' => 1,
            'queued_at' => current_time('mysql'),
        ];
    }

    private function campaign_headline(array $campaign): string
    {
        $headline = trim((string) ($campaign['marketing_headline'] ?? ''));
        if ($headline !== '') { return $headline; }
        $subject = trim((string) ($campaign['subject_override'] ?? ''));
        return $subject !== '' ? $subject : __('Special offer', 'slotera-booking');
    }

    private function cta_button_html(array $campaign, array $customer, array $package): string
    {
        $url = $this->cta_url($campaign, $customer, $package);
        if ($url === '') { return ''; }
        $colors = $this->email_theme_colors();
        $label = trim((string) ($campaign['cta_label'] ?? ''));
        if ($label === '') { $label = \sltr_t('Book now', 'emails', EmailTemplateRegistry::runtime_locale()); }
        return '<p style="margin:26px 0 6px;text-align:center;"><a href="' . esc_url($url) . '" style="display:inline-block;background:' . esc_attr($colors['primary']) . ';color:' . esc_attr($colors['primary_text']) . ' !important;-webkit-text-fill-color:' . esc_attr($colors['primary_text']) . ';text-decoration:none;font-family:Arial,sans-serif;font-size:15px;font-weight:700;line-height:1.2;padding:13px 22px;border-radius:999px;">' . esc_html($label) . '</a></p>';
    }

    private function cta_url(array $campaign, array $customer, array $package): string
    {
        $type = sanitize_key((string) ($campaign['cta_url_type'] ?? 'booking'));
        if ($type === 'custom') {
            $url = esc_url_raw((string) ($campaign['cta_custom_url'] ?? ''));
            return $url !== '' ? $url : $this->booking_url($campaign, $customer, $package);
        }
        if ($type === 'package') {
            $url = $this->package_url($package);
            return $url !== '' ? $url : $this->booking_url($campaign, $customer, $package);
        }
        return $this->booking_url($campaign, $customer, $package);
    }

    private function booking_url(array $campaign, array $customer, array $package): string
    {
        $url = $this->settings->get_page_url('booking');
        if ($url === '') {
            $url = home_url('/');
        }
        $package_id = absint($package['id'] ?? ($customer['package_id'] ?? ($campaign['package_id'] ?? 0)));
        if ($package_id > 0) {
            $url = add_query_arg(['package_id' => $package_id], $url);
        }
        return $url;
    }

    private function package_url(array $package): string
    {
        if (empty($package['solo_page_enabled'])) { return ''; }
        $page_id = absint($package['page_id'] ?? 0);
        if ($page_id <= 0) { return ''; }
        $url = get_permalink($page_id);
        return is_string($url) ? $url : '';
    }

    private function coupon_discount_label(array $coupon): string
    {
        if (empty($coupon)) { return ''; }
        $value = (string) ($coupon['discount_value'] ?? '');
        return (string) ($coupon['discount_type'] ?? '') === 'percent' ? $value . '%' : $value . ' ' . strtoupper((string) $this->settings->get('payment_currency', 'EUR'));
    }

    private function headers(string $recipient_email = ''): array
    {
        $from_name = sanitize_text_field((string) $this->settings->get('email_from_name', get_bloginfo('name')));
        $from_email = sanitize_email((string) $this->settings->get('email_from_address', get_option('admin_email')));
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        if ($from_email !== '' && is_email($from_email)) { $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>'; }
        $unsubscribe_url = $this->should_require_unsubscribe_link() ? $this->opt_out->unsubscribe_url($recipient_email) : '';
        if ($unsubscribe_url !== '') {
            $headers[] = 'List-Unsubscribe: <' . $unsubscribe_url . '>';
            $headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
        }
        return $headers;
    }

    private function unsubscribe_footer_html(string $email): string
    {
        $url = $this->opt_out->unsubscribe_url($email);
        if ($url === '') { return ''; }
        return '<p style="margin-top:28px;font-size:12px;line-height:1.5;color:#64748b;text-align:center;">'
            . esc_html__('You are receiving this marketing email because you booked with us before.', 'slotera-booking')
            . ' <a href="' . esc_url($url) . '" style="color:inherit;text-decoration:underline;">'
            . esc_html__('Unsubscribe', 'slotera-booking')
            . '</a></p>';
    }

    private function wrap_html_message(string $message, bool $is_html_template = false): string
    {
        $content = $is_html_template ? wp_kses_post($message) : wp_kses_post(wpautop($message));
        $colors = $this->email_theme_colors();
        return '<!doctype html><html><body style="margin:0;padding:0;background:' . esc_attr($colors['form_bg']) . ';">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:' . esc_attr($colors['form_bg']) . ';margin:0;padding:24px 12px;width:100%;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:' . esc_attr($colors['card_bg']) . ';border-radius:18px;overflow:hidden;border:1px solid ' . esc_attr($colors['card_border']) . ';">'
            . '<tr><td style="padding:22px 28px;background:' . esc_attr($colors['primary']) . ';color:' . esc_attr($colors['primary_text']) . ';font-family:Arial,sans-serif;font-size:20px;font-weight:700;"><span style="color:' . esc_attr($colors['primary_text']) . ' !important;-webkit-text-fill-color:' . esc_attr($colors['primary_text']) . ';">' . esc_html(get_bloginfo('name')) . '</span></td></tr>'
            . '<tr><td style="padding:28px;font-family:Arial,sans-serif;font-size:15px;line-height:1.6;color:' . esc_attr($colors['text']) . ';">' . $content . '</td></tr>'
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
}
