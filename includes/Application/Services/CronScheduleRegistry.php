<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

/**
 * Request-light scheduler audit for persistent Slotera cron events.
 *
 * WordPress cron events persist in the database, so checking every hook on every
 * request is unnecessary. This registry performs one throttled audit and lets
 * individual services focus on registering callbacks only.
 */
final class CronScheduleRegistry
{
    private const AUDIT_OPTION = 'sltr_cron_schedule_audit_at';
    private const AUDIT_SCHEMA_OPTION = 'sltr_cron_schedule_audit_schema';
    private const AUDIT_SCHEMA_VERSION = 2;
    private const AUDIT_INTERVAL = 10 * MINUTE_IN_SECONDS;

    public static function maybe_ensure(bool $force = false): void
    {
        $now = time();
        $last = (int) get_option(self::AUDIT_OPTION, 0);
        $schema = (int) get_option(self::AUDIT_SCHEMA_OPTION, 0);
        if (!$force && $schema === self::AUDIT_SCHEMA_VERSION && $last > 0 && ($now - $last) < self::AUDIT_INTERVAL) {
            PerformanceProfiler::metric('cron_schedule_audit_skipped');
            self::self_heal_missing($now);
            return;
        }

        PerformanceProfiler::metric('cron_schedule_audit_run');
        self::ensure(EmailReminderService::CRON_HOOK, $now + 60, EmailReminderService::CRON_SCHEDULE);
        self::ensure(SecureAttachmentFileService::CRON_HOOK, $now + HOUR_IN_SECONDS, 'daily');
        self::ensure(AccountMagicLinkService::CRON_HOOK, $now + HOUR_IN_SECONDS, 'hourly');
        self::ensure(PrivacyService::CRON_HOOK, $now + DAY_IN_SECONDS, 'daily');
        self::ensure(MarketingAutomationService::CRON_HOOK, $now + 120, 'hourly');
        self::ensure(PromotionCampaignService::CRON_HOOK, $now + 180, 'hourly');
        self::ensure(PayPalGatewayService::RECONCILE_CRON_HOOK, $now + 5 * MINUTE_IN_SECONDS, PayPalGatewayService::RECONCILE_CRON_SCHEDULE);

        $settings = new SettingsRepository();
        $minutes = absint($settings->get('marketing_cron_interval', 5));
        if (!in_array($minutes, [1, 5, 10, 15], true)) { $minutes = 5; }
        $desired = 'sltr_marketing_every_' . $minutes . '_minutes';
        $current = wp_get_schedule(MarketingEmailService::CRON_HOOK);
        if ($current !== false && $current !== $desired) {
            wp_clear_scheduled_hook(MarketingEmailService::CRON_HOOK);
        }
        self::ensure(MarketingEmailService::CRON_HOOK, $now + 60, $desired);

        update_option(self::AUDIT_OPTION, $now, false);
        update_option(self::AUDIT_SCHEMA_OPTION, self::AUDIT_SCHEMA_VERSION, false);
    }

    /**
     * Cheap safety net used while the full schedule audit is throttled.
     *
     * Reads WordPress' persistent cron array once and only schedules hooks that
     * are actually absent. This keeps the common request path light while
     * ensuring a deleted/lost event cannot remain missing for ten minutes.
     */
    private static function self_heal_missing(int $now): void
    {
        $hooks = self::scheduled_hooks();
        $fixed = [
            'sltr_process_email_queue' => [$now + 60, 'sltr_every_five_minutes'],
            'sltr_cleanup_secure_mail_attachments' => [$now + HOUR_IN_SECONDS, 'daily'],
            'sltr_cleanup_magic_link_options' => [$now + HOUR_IN_SECONDS, 'hourly'],
            'sltr_privacy_retention_cleanup' => [$now + DAY_IN_SECONDS, 'daily'],
            'sltr_process_marketing_automations' => [$now + 120, 'hourly'],
            'sltr_process_promotion_digest' => [$now + 180, 'hourly'],
            'sltr_paypal_reconcile_processing' => [$now + 5 * MINUTE_IN_SECONDS, 'sltr_every_fifteen_minutes'],
        ];

        foreach ($fixed as $hook => [$timestamp, $schedule]) {
            if (isset($hooks[$hook])) {
                continue;
            }
            wp_schedule_event($timestamp, $schedule, $hook);
            PerformanceProfiler::metric('cron_schedule_self_healed');
        }

        if (!isset($hooks['sltr_process_marketing_queue'])) {
            $settings = new SettingsRepository();
            $minutes = absint($settings->get('marketing_cron_interval', 5));
            if (!in_array($minutes, [1, 5, 10, 15], true)) { $minutes = 5; }
            wp_schedule_event($now + 60, 'sltr_marketing_every_' . $minutes . '_minutes', 'sltr_process_marketing_queue');
            PerformanceProfiler::metric('cron_schedule_self_healed');
        }
    }

    /** @return array<string, true> */
    private static function scheduled_hooks(): array
    {
        if (function_exists('_get_cron_array')) {
            $cron = _get_cron_array();
            $hooks = [];
            if (is_array($cron)) {
                foreach ($cron as $events) {
                    if (!is_array($events)) { continue; }
                    foreach ($events as $hook => $_instances) {
                        if (is_string($hook) && $hook !== '') { $hooks[$hook] = true; }
                    }
                }
            }
            return $hooks;
        }

        $hooks = [];
        foreach ([
            'sltr_process_email_queue',
            'sltr_cleanup_secure_mail_attachments',
            'sltr_cleanup_magic_link_options',
            'sltr_privacy_retention_cleanup',
            'sltr_process_marketing_automations',
            'sltr_process_promotion_digest',
            'sltr_paypal_reconcile_processing',
            'sltr_process_marketing_queue',
        ] as $hook) {
            if (wp_next_scheduled($hook)) { $hooks[$hook] = true; }
        }
        return $hooks;
    }

    private static function ensure(string $hook, int $timestamp, string $schedule): void
    {
        if (!wp_next_scheduled($hook)) {
            wp_schedule_event($timestamp, $schedule, $hook);
        }
    }
}
