<?php
declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Database;
use Slotera\Infrastructure\Repositories\CouponRepository;
use Slotera\Infrastructure\Repositories\MarketingCampaignRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class MarketingAutomationService
{
    public const CRON_HOOK = 'sltr_process_marketing_automations';
    public const COME_BACK_KEY = 'come_back';
    public const AFTER_BOOKING_KEY = 'after_booking';

    private SettingsRepository $settings;
    private MarketingCampaignRepository $campaigns;
    private MarketingEmailService $marketing;
    private CouponRepository $coupons;

    public function __construct(?SettingsRepository $settings = null, ?MarketingCampaignRepository $campaigns = null, ?MarketingEmailService $marketing = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
        $this->campaigns = $campaigns ?: new MarketingCampaignRepository();
        $this->coupons = new CouponRepository();
        $this->marketing = $marketing ?: new MarketingEmailService($this->settings, $this->campaigns, null, null, $this->coupons);
    }

    public function register_hooks(): void
    {
        add_action(self::CRON_HOOK, [$this, 'process_all']);
    }

    public static function activate(): void
    {
        (new self())->ensure_scheduled(true);
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public function ensure_scheduled(bool $force = false): void
    {
        if ($force) { wp_clear_scheduled_hook(self::CRON_HOOK); }
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 120, 'hourly', self::CRON_HOOK);
        }
    }

    public function process_all(bool $force = false): array
    {
        if (!CronResilienceService::acquire(self::CRON_HOOK, 15 * MINUTE_IN_SECONDS)) { return ['come_back' => ['queued' => 0, 'skipped' => 0, 'reason' => 'locked'], 'after_booking' => ['queued' => 0, 'skipped' => 0, 'reason' => 'locked']]; }
        try {
            if (!(new LicenseService())->can_use_automations()) {
                $result = ['come_back' => ['queued' => 0, 'skipped' => 0, 'reason' => 'license_limited'], 'after_booking' => ['queued' => 0, 'skipped' => 0, 'reason' => 'license_limited']];
                CronResilienceService::success(self::CRON_HOOK, $result);
                return $result;
            }
            $result = [
                'come_back' => $this->process($force),
                'after_booking' => $this->process_after_booking($force),
            ];
            CronResilienceService::success(self::CRON_HOOK, $result);
            return $result;
        } catch (\Throwable $e) {
            CronResilienceService::failure(self::CRON_HOOK, $e);
            throw $e;
        }
    }

    public function process(bool $force = false): array
    {
        if (!(new LicenseService())->can_use_automations()) { return ['queued' => 0, 'skipped' => 0, 'campaign_id' => 0, 'reason' => 'license_limited']; }
        $readiness = $this->marketing->email_delivery_readiness();
        if (!$readiness['ready']) { return ['queued' => 0, 'skipped' => 0, 'campaign_id' => 0, 'reason' => 'email_settings_required', 'email_settings_issue' => $readiness['issue']]; }
        if ((int) $this->settings->get('comeback_automation_enabled', 0) !== 1) {
            return ['queued' => 0, 'skipped' => 0, 'campaign_id' => 0, 'reason' => 'disabled'];
        }

        $today = wp_date('Y-m-d', current_time('timestamp'));
        $last_run = (string) $this->settings->get('comeback_automation_last_run', '');
        if (!$force && strpos($last_run, $today) === 0) {
            return ['queued' => 0, 'skipped' => 0, 'campaign_id' => 0, 'reason' => 'already_run_today'];
        }

        $inactive_days = max(1, min(3650, absint($this->settings->get('comeback_automation_inactive_days', 30))));
        $repeat_days = max(1, min(3650, absint($this->settings->get('comeback_automation_repeat_days', 90))));
        $offer_coupon_id = $this->create_offer_template('comeback_automation_');
        if ($offer_coupon_id <= 0) {
            return ['queued' => 0, 'skipped' => 0, 'campaign_id' => 0, 'reason' => 'offer_create_failed'];
        }
        $campaign_id = $this->campaigns->create([
            'name' => sprintf(__('Come back automation — %s', 'slotera-booking'), wp_date('Y-m-d H:i')),
            'template_key' => sanitize_key((string) $this->settings->get('comeback_automation_template_key', 'marketing_promo')),
            'subject_override' => (string) $this->settings->get('comeback_automation_subject_override', ''),
            'audience_type' => 'advanced',
            'package_id' => 0,
            'coupon_id' => $offer_coupon_id,
            'generate_unique_coupons' => 1,
            'cta_enabled' => (int) $this->settings->get('comeback_automation_cta_enabled', 1),
            'cta_label' => $this->automation_cta_label('comeback_automation_cta_label'),
            'cta_url_type' => (string) $this->settings->get('comeback_automation_cta_url_type', 'booking'),
            'cta_custom_url' => (string) $this->settings->get('comeback_automation_cta_custom_url', ''),
            'audience_statuses' => 'completed',
            'audience_payment_statuses' => '',
            'audience_last_booking_mode' => 'older_than_days',
            'audience_last_booking_days' => $inactive_days,
            'audience_min_bookings' => 1,
            'audience_max_bookings' => 0,
            'audience_min_spent' => 0,
            'audience_max_spent' => 0,
            'audience_coupon_filter' => 'any',
            'marketing_headline' => (string) $this->settings->get('comeback_automation_headline', ''),
            'marketing_message' => (string) $this->settings->get('comeback_automation_message', ''),
            'marketing_submessage' => (string) $this->settings->get('comeback_automation_submessage', ''),
            'source' => 'automation',
            'automation_type' => self::COME_BACK_KEY,
            'status' => 'draft',
        ]);

        if ($campaign_id <= 0) {
            $this->coupons->delete($offer_coupon_id);
            return ['queued' => 0, 'skipped' => 0, 'campaign_id' => 0, 'reason' => 'campaign_create_failed'];
        }

        $result = $this->marketing->queue_campaign($campaign_id, [
            'automation' => self::COME_BACK_KEY,
            'cooldown_days' => $repeat_days,
        ]);

        $this->settings->update(['comeback_automation_last_run' => current_time('mysql')]);
        if ((int) ($result['queued'] ?? 0) <= 0) {
            $this->campaigns->update_status($campaign_id, 'completed');
        }

        return array_merge($result, ['campaign_id' => $campaign_id, 'reason' => (int) ($result['queued'] ?? 0) > 0 ? 'processed' : 'no_eligible_recipients']);
    }

    public function process_after_booking(bool $force = false): array
    {
        if (!(new LicenseService())->can_use_automations()) { return ['queued' => 0, 'skipped' => 0, 'campaign_id' => 0, 'reason' => 'license_limited']; }
        $readiness = $this->marketing->email_delivery_readiness();
        if (!$readiness['ready']) { return ['queued' => 0, 'skipped' => 0, 'campaign_id' => 0, 'reason' => 'email_settings_required', 'email_settings_issue' => $readiness['issue']]; }
        if ((int) $this->settings->get('after_booking_automation_enabled', 0) !== 1) {
            return ['queued' => 0, 'skipped' => 0, 'campaign_id' => 0, 'reason' => 'disabled'];
        }

        $today = wp_date('Y-m-d', current_time('timestamp'));
        $last_run = (string) $this->settings->get('after_booking_automation_last_run', '');
        if (!$force && strpos($last_run, $today) === 0) {
            return ['queued' => 0, 'skipped' => 0, 'campaign_id' => 0, 'reason' => 'already_run_today'];
        }

        $delay_days = max(0, min(3650, absint($this->settings->get('after_booking_automation_delay_days', 3))));
        $repeat_days = max(1, min(3650, absint($this->settings->get('after_booking_automation_repeat_days', 30))));
        $recipients = $this->after_booking_recipients($delay_days);
        $offer_coupon_id = $this->create_offer_template('after_booking_automation_');
        if ($offer_coupon_id <= 0) {
            return ['queued' => 0, 'skipped' => count($recipients), 'campaign_id' => 0, 'reason' => 'offer_create_failed'];
        }

        $campaign_id = $this->campaigns->create([
            'name' => sprintf(__('After booking automation — %s', 'slotera-booking'), wp_date('Y-m-d H:i')),
            'template_key' => sanitize_key((string) $this->settings->get('after_booking_automation_template_key', 'marketing_promo')),
            'subject_override' => (string) $this->settings->get('after_booking_automation_subject_override', ''),
            'audience_type' => 'advanced',
            'package_id' => 0,
            'coupon_id' => $offer_coupon_id,
            'generate_unique_coupons' => 1,
            'cta_enabled' => (int) $this->settings->get('after_booking_automation_cta_enabled', 1),
            'cta_label' => $this->automation_cta_label('after_booking_automation_cta_label'),
            'cta_url_type' => (string) $this->settings->get('after_booking_automation_cta_url_type', 'booking'),
            'cta_custom_url' => (string) $this->settings->get('after_booking_automation_cta_custom_url', ''),
            'audience_statuses' => 'completed',
            'audience_payment_statuses' => '',
            'audience_last_booking_mode' => 'any',
            'audience_last_booking_days' => $delay_days,
            'audience_min_bookings' => 1,
            'audience_max_bookings' => 0,
            'audience_min_spent' => 0,
            'audience_max_spent' => 0,
            'audience_coupon_filter' => 'any',
            'marketing_headline' => (string) $this->settings->get('after_booking_automation_headline', ''),
            'marketing_message' => (string) $this->settings->get('after_booking_automation_message', ''),
            'marketing_submessage' => (string) $this->settings->get('after_booking_automation_submessage', ''),
            'source' => 'automation',
            'automation_type' => self::AFTER_BOOKING_KEY,
            'status' => 'draft',
        ]);

        if ($campaign_id <= 0) {
            $this->coupons->delete($offer_coupon_id);
            return ['queued' => 0, 'skipped' => count($recipients), 'campaign_id' => 0, 'reason' => 'campaign_create_failed'];
        }

        $result = $this->marketing->queue_recipients_for_campaign($campaign_id, $recipients, [
            'automation' => self::AFTER_BOOKING_KEY,
            'cooldown_days' => $repeat_days,
        ]);

        $this->campaigns->update_status($campaign_id, (int) ($result['queued'] ?? 0) > 0 ? 'queued' : 'completed');
        $this->marketing->ensure_scheduled();
        $this->settings->update(['after_booking_automation_last_run' => current_time('mysql')]);

        return array_merge($result, ['campaign_id' => $campaign_id, 'reason' => (int) ($result['queued'] ?? 0) > 0 ? 'processed' : 'no_eligible_recipients']);
    }

    private function create_offer_template(string $prefix): int
    {
        $type = sanitize_key((string) $this->settings->get($prefix . 'offer_discount_type', 'percent'));
        if (!in_array($type, ['percent', 'fixed'], true)) { $type = 'percent'; }
        $value = max(0, (float) $this->settings->get($prefix . 'offer_discount_value', 10));
        $valid_days = max(1, min(3650, absint($this->settings->get($prefix . 'offer_valid_days', 14))));
        $package_ids = implode(',', array_values(array_unique(array_filter(array_map(
            'absint',
            explode(',', (string) $this->settings->get($prefix . 'offer_package_ids', ''))
        )))));
        $code = '';
        for ($i = 0; $i < 20; $i++) {
            $candidate = strtoupper(substr(wp_generate_password(8, false, false), 0, 8));
            $candidate = preg_replace('/[^A-Z0-9]/', '', $candidate) ?: strtoupper(substr(hash('crc32b', microtime(true) . wp_rand()), 0, 8));
            $candidate = $this->coupons->normalize_code($candidate);
            if ($candidate !== '' && !$this->coupons->get_by_code($candidate)) {
                $code = $candidate;
                break;
            }
        }
        if ($code === '') {
            $code = $this->coupons->normalize_code(strtoupper(substr(hash('sha256', microtime(true) . wp_rand()), 0, 12)));
        }

        return $this->coupons->create([
            'code' => $code,
            'description' => '[slotera:automation_offer_template]',
            'discount_type' => $type,
            'discount_value' => $value,
            'usage_limit' => 0,
            'usage_limit_per_email' => 0,
            'expires_at' => wp_date('Y-m-d', current_time('timestamp') + $valid_days * DAY_IN_SECONDS),
            'package_ids' => $package_ids,
            'min_amount' => 0,
            'is_active' => 1,
        ]);
    }

    private function after_booking_recipients(int $delay_days): array
    {
        global $wpdb;
        $bookings = Database::bookings_table();
        $target = wp_date('Y-m-d', current_time('timestamp') - $delay_days * DAY_IN_SECONDS);
        $sql = "SELECT customer_email, MAX(customer_name) AS customer_name, MAX(package_id) AS package_id, MAX(booking_date) AS last_booking_date, COUNT(*) AS booking_count, COALESCE(SUM(total_amount),0) AS total_spent, SUM(CASE WHEN coupon_id > 0 OR coupon_code <> '' THEN 1 ELSE 0 END) AS coupon_booking_count FROM {$bookings} WHERE customer_email <> '' AND status='completed' AND DATE(COALESCE(completed_at, booking_date))=%s GROUP BY customer_email ORDER BY last_booking_date DESC";
        return $wpdb->get_results($wpdb->prepare($sql, $target), ARRAY_A) ?: [];
    }

    private function automation_cta_label(string $key): string
    {
        $custom = trim((string) $this->settings->get($key, ''));
        return $custom !== '' ? $custom : \sltr_t('Book now', 'emails', EmailTemplateRegistry::runtime_locale());
    }

}
