<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/**
 * Production-safe guardrails around Slotera scheduled jobs.
 *
 * Uses non-autoloaded WordPress options so the implementation works on hosts
 * without object-cache atomic primitives. Jobs acquire a short TTL lock before
 * doing work, record start/success/error timestamps, and emit observability
 * events that can be surfaced in Diagnostics.
 */
final class CronResilienceService
{
    private const STATE_PREFIX = 'sltr_cron_state_';
    private const LOCK_PREFIX = 'sltr_cron_lock_';
    private const DEFAULT_TTL_SECONDS = 900;

    /** @var array<string,int> */
    private static array $started_at = [];

    /** @return array<string,string> */
    public static function jobs(): array
    {
        return [
            EmailReminderService::CRON_HOOK => 'Email queue',
            SecureAttachmentFileService::CRON_HOOK => 'Secure attachment cleanup',
            PrivacyService::CRON_HOOK => 'Privacy retention cleanup',
            MarketingEmailService::CRON_HOOK => 'Marketing queue',
            MarketingAutomationService::CRON_HOOK => 'Marketing automations',
            PromotionCampaignService::CRON_HOOK => 'Promotion digest',
        ];
    }

    public function register_hooks(): void
    {
        foreach (self::jobs() as $hook => $label) {
            add_action($hook, function () use ($hook, $label): void {
                $this->mark_seen($hook, $label);
            }, 0);
        }
    }

    public static function acquire(string $hook, int $ttl_seconds = self::DEFAULT_TTL_SECONDS): bool
    {
        $hook = sanitize_key($hook);
        if ($hook === '') { return false; }

        $ttl_seconds = max(60, min(DAY_IN_SECONDS, $ttl_seconds));
        $now = time();
        $lock_option = self::lock_option($hook);
        $current = get_option($lock_option, null);

        if (is_array($current) && (int) ($current['expires_at'] ?? 0) > $now) {
            self::record($hook, [
                'last_skipped_at' => current_time('mysql'),
                'last_skip_reason' => 'lock_active',
                'last_lock_expires_at' => gmdate('Y-m-d H:i:s', (int) ($current['expires_at'] ?? 0)),
            ]);
            do_action('sltr_observe', 'cron_lock_skipped', 'warning', 'Cron job skipped because a previous run is still locked.', [
                'hook' => $hook,
                'lock' => $current,
            ]);
            return false;
        }

        $token = self::token();
        update_option($lock_option, [
            'token' => $token,
            'started_at' => $now,
            'expires_at' => $now + $ttl_seconds,
            'request_id' => class_exists(ObservabilityLogger::class) ? ObservabilityLogger::request_id() : '',
        ], false);

        self::$started_at[$hook] = $now;
        self::record($hook, [
            'last_started_at' => current_time('mysql'),
            'last_error' => '',
            'last_status' => 'running',
            'last_request_id' => class_exists(ObservabilityLogger::class) ? ObservabilityLogger::request_id() : '',
        ]);
        return true;
    }

    /** @param array<string,mixed> $stats */
    public static function success(string $hook, array $stats = []): void
    {
        $hook = sanitize_key($hook);
        $duration = isset(self::$started_at[$hook]) ? max(0, time() - self::$started_at[$hook]) : 0;
        self::record($hook, [
            'last_finished_at' => current_time('mysql'),
            'last_success_at' => current_time('mysql'),
            'last_status' => 'success',
            'last_duration_seconds' => $duration,
            'last_stats' => $stats,
            'last_error' => '',
        ]);
        delete_option(self::lock_option($hook));
        do_action('sltr_observe', 'cron_job_success', 'info', 'Cron job completed successfully.', ['hook' => $hook, 'duration_seconds' => $duration, 'stats' => $stats]);
    }

    public static function failure(string $hook, \Throwable $e): void
    {
        $hook = sanitize_key($hook);
        $duration = isset(self::$started_at[$hook]) ? max(0, time() - self::$started_at[$hook]) : 0;
        self::record($hook, [
            'last_finished_at' => current_time('mysql'),
            'last_status' => 'error',
            'last_duration_seconds' => $duration,
            'last_error' => $e->getMessage(),
            'last_error_class' => get_class($e),
        ]);
        delete_option(self::lock_option($hook));
        do_action('sltr_observe', 'cron_job_failed', 'error', 'Cron job failed.', ['hook' => $hook, 'duration_seconds' => $duration, 'error' => $e->getMessage(), 'class' => get_class($e)]);
    }

    public function mark_seen(string $hook, string $label = ''): void
    {
        $hook = sanitize_key($hook);
        if ($hook === '') { return; }
        self::record($hook, [
            'hook' => $hook,
            'label' => $label !== '' ? $label : $hook,
            'last_seen_at' => current_time('mysql'),
        ]);
    }

    /** @return array<string,array<string,mixed>> */
    public static function health(): array
    {
        $out = [];
        foreach (self::jobs() as $hook => $label) {
            $state = get_option(self::state_option($hook), []);
            $state = is_array($state) ? $state : [];
            $lock = get_option(self::lock_option($hook), null);
            $out[$hook] = array_merge([
                'hook' => $hook,
                'label' => $label,
                'next_run' => function_exists('wp_next_scheduled') ? (int) wp_next_scheduled($hook) : 0,
                'locked' => is_array($lock) && (int) ($lock['expires_at'] ?? 0) > time(),
                'lock_expires_at' => is_array($lock) ? (int) ($lock['expires_at'] ?? 0) : 0,
            ], $state);
        }
        return $out;
    }



    /** @return array<int,array<string,mixed>> */
    public static function due_preview(): array
    {
        $now = time();
        $health = self::health();
        $rows = [];
        foreach (self::jobs() as $hook => $label) {
            $next = function_exists('wp_next_scheduled') ? (int) wp_next_scheduled($hook) : 0;
            if ($next <= 0 || $next > $now) { continue; }
            $state = $health[$hook] ?? [];
            $rows[] = [
                'hook' => $hook,
                'label' => $label,
                'next_run' => $next,
                'overdue_seconds' => max(0, $now - $next),
                'locked' => !empty($state['locked']),
                'lock_expires_at' => (int) ($state['lock_expires_at'] ?? 0),
                'last_status' => (string) ($state['last_status'] ?? 'waiting'),
                'last_success_at' => (string) ($state['last_success_at'] ?? ''),
                'estimate' => self::estimate($hook),
            ];
        }
        return $rows;
    }

    /** @return array{label:string,count:int|null,detail:string} */
    private static function estimate(string $hook): array
    {
        global $wpdb;
        $hook = sanitize_key($hook);
        $count = null;
        $detail = 'Due WordPress cron hook. Exact work amount is calculated by the job at run time.';
        $label = 'Items';

        try {
            if ($hook === EmailReminderService::CRON_HOOK && self::table_exists(\Slotera\Core\Database::email_queue_table())) {
                $label = 'Queued emails';
                $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . \Slotera\Core\Database::email_queue_table() . " WHERE status IN ('queued','retry')");
                $detail = 'Emails waiting in the Slotera email queue.';
            } elseif ($hook === MarketingEmailService::CRON_HOOK && self::table_exists(\Slotera\Core\Database::marketing_logs_table())) {
                $label = 'Marketing queue rows';
                $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . \Slotera\Core\Database::marketing_logs_table() . " WHERE status IN ('pending','failed','sending') AND attempts < max_attempts");
                $detail = 'Marketing email queue rows waiting to be processed or retried.';
            } elseif ($hook === SecureAttachmentFileService::CRON_HOOK) {
                $label = 'Expired files';
                $detail = 'Secure attachment files older than their retention window; count is resolved inside the file cleanup job.';
            } elseif ($hook === PrivacyService::CRON_HOOK) {
                $label = 'Retention candidates';
                $detail = 'Privacy retention cleanup candidates; exact counts depend on retention settings.';
            } elseif ($hook === MarketingAutomationService::CRON_HOOK) {
                $label = 'Automation candidates';
                $detail = 'Marketing automation candidates; exact counts depend on campaign settings.';
            }
        } catch (\Throwable $e) {
            $detail = 'Preview estimate failed: ' . $e->getMessage();
        }

        return ['label' => $label, 'count' => $count, 'detail' => $detail];
    }

    private static function table_exists(string $table): bool
    {
        global $wpdb;
        if ($table === '') { return false; }
        return (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    /** @param array<string,mixed> $patch */
    private static function record(string $hook, array $patch): void
    {
        $option = self::state_option($hook);
        $state = get_option($option, []);
        $state = is_array($state) ? $state : [];
        update_option($option, array_merge($state, $patch, ['updated_at' => current_time('mysql')]), false);
    }

    private static function state_option(string $hook): string
    {
        return self::STATE_PREFIX . sanitize_key($hook);
    }

    private static function lock_option(string $hook): string
    {
        return self::LOCK_PREFIX . sanitize_key($hook);
    }

    private static function token(): string
    {
        try { return bin2hex(random_bytes(16)); } catch (\Throwable $e) { return wp_generate_password(32, false, false); }
    }
}
