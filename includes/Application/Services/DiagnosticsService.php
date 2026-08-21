<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Database;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Infrastructure\Repositories\ActivityLogRepository;
use Slotera\Infrastructure\Repositories\PaymentTransactionRepository;
use Slotera\Application\Services\Translations\TranslationBoundaryRegistry;

if (!defined('ABSPATH')) {
    exit;
}

final class DiagnosticsService
{
    public function run(): array
    {
        $checks = [
            'system' => $this->system_checks(),
            'database' => $this->database_checks(),
            'pages' => $this->page_checks(),
            'booking' => $this->booking_checks(),
            'production_checklist' => $this->production_checklist_checks(),
            'payments' => $this->payment_checks(),
            'webhooks' => $this->webhook_checks(),
            'privacy' => $this->privacy_checks(),
            'observability' => $this->observability_checks(),
            'cron_resilience' => $this->cron_resilience_checks(),
            'performance' => $this->performance_checks(),
        ];

        return [
            'summary' => $this->summary($checks),
            'checks' => $checks,
        ];
    }

    private function summary(array $checks): array
    {
        $flat = [];
        foreach ($checks as $group) {
            foreach ($group as $check) {
                $flat[] = $check['status'] ?? 'info';
            }
        }

        return [
            'ok' => count(array_filter($flat, static fn($s) => $s === 'ok')),
            'warning' => count(array_filter($flat, static fn($s) => $s === 'warning')),
            'critical' => count(array_filter($flat, static fn($s) => $s === 'critical')),
            'info' => count(array_filter($flat, static fn($s) => $s === 'info')),
            'generated_at' => current_time('mysql'),
        ];
    }

    private function system_checks(): array
    {
        $minimum_php = defined('SLTR_MINIMUM_PHP_VERSION') ? SLTR_MINIMUM_PHP_VERSION : '8.0';

        return [
            $this->check('WordPress version', version_compare((string) get_bloginfo('version'), '6.0', '>='), 'WP ' . get_bloginfo('version'), 'Slotera requires WordPress 6.0 or newer.'),
            $this->check('PHP version', version_compare(PHP_VERSION, $minimum_php, '>='), 'PHP ' . PHP_VERSION, sprintf('Slotera requires PHP %s or newer.', $minimum_php)),
            $this->wp_cron_check(),
            $this->check('Timezone configured', (string) wp_timezone_string() !== '', wp_timezone_string(), 'Set a site timezone in WordPress Settings.'),
        ];
    }

    private function wp_cron_check(): array
    {
        $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $real_server_cron_configured = $this->real_server_cron_configured();

        if (!$cron_disabled) {
            return ['label' => 'WP Cron', 'status' => 'ok', 'detail' => 'WP pseudo-cron enabled', 'recommendation' => ''];
        }

        if ($real_server_cron_configured) {
            return ['label' => 'WP Cron', 'status' => 'ok', 'detail' => 'Real server cron configured', 'recommendation' => ''];
        }

        return [
            'label' => 'WP Cron',
            'status' => 'warning',
            'detail' => 'WP pseudo-cron disabled',
            'recommendation' => 'Configure a real server cron to run wp-cron.php, then enable “Real server cron configured” in Slotera Settings → Advanced.',
        ];
    }

    private function real_server_cron_configured(): bool
    {
        $settings = (new SettingsRepository())->all();
        return !empty($settings['real_server_cron_configured']);
    }

    private function database_checks(): array
    {
        global $wpdb;
        $tables = [
            'Packages' => Database::packages_table(),
            'Categories' => Database::categories_table(),
            'Working hours' => Database::working_hours_table(),
            'Bookings' => Database::bookings_table(),
            'Activity log' => Database::activity_log_table(),
            'Booking history' => Database::booking_history_table(),
            'Webhook events' => Database::webhook_events_table(),
            'Email queue' => Database::email_queue_table(),
            'Outgoing webhook endpoints' => Database::outgoing_webhook_endpoints_table(),
            'Outgoing webhook deliveries' => Database::outgoing_webhook_deliveries_table(),
            'Coupons' => Database::coupons_table(),
            'Marketing campaigns' => Database::marketing_campaigns_table(),
            'Marketing logs' => Database::marketing_logs_table(),
        ];
        $checks = [];
        foreach ($tables as $label => $table) {
            $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
            $checks[] = $this->check($label . ' table', $exists, $exists ? $table : 'Missing', 'Run plugin migrations by deactivating/reactivating Slotera or opening the admin once.');
        }
        return $checks;
    }

    private function page_checks(): array
    {
        $settings_repository = new SettingsRepository();
        $settings = $settings_repository->all();
        $required = [
            'booking_page_id' => ['Booking page', '[slotera_booking]', 'booking'],
            'categories_page_id' => ['Categories page', '[slotera_categories]', 'categories'],
            'thank_you_page_id' => ['Thank you page', '[slotera_thank_you]', 'thank_you'],
            'checkout_page_id' => ['Checkout page', '[slotera_checkout]', 'checkout'],
            'login_page_id' => ['Login page', '[slotera_login]', 'login'],
            'account_page_id' => ['Account page', '[slotera_account]', 'account'],
        ];
        $checks = [];
        foreach ($required as $key => [$label, $shortcode, $page_key]) {
            $configured_page_id = (int) ($settings[$key] ?? 0);
            $page_id = $configured_page_id;
            $ok = $settings_repository->is_published_page_for_key($page_id, $page_key);
            $detected = false;

            if (!$ok) {
                $detected_page_id = $settings_repository->find_page_id_by_key($page_key);
                if ($detected_page_id > 0) {
                    $page_id = $detected_page_id;
                    $ok = true;
                    $detected = $configured_page_id <= 0 || $configured_page_id !== $detected_page_id;
                }
            }

            $detail = $ok ? get_the_title($page_id) . ' #' . $page_id . ($detected ? ' (auto-detected)' : '') : 'Not configured';
            $recommendation = $detected ? 'Optional: bind this page in Slotera settings to remove auto-detection fallback.' : 'Create or bind a published page with ' . $shortcode . '.';
            $checks[] = ['label' => $label, 'status' => $ok ? 'ok' : 'critical', 'detail' => $detail, 'recommendation' => $ok ? '' : $recommendation];
        }
        return $checks;
    }

    private function booking_checks(): array
    {
        global $wpdb;
        $packages = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Database::packages_table() . ' WHERE is_active=1');
        $hours = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Database::working_hours_table() . " WHERE scope_type='global' AND is_enabled=1");
        return [
            $this->check('Active packages', $packages > 0, (string) $packages, 'Create at least one active package.'),
            $this->check('Global working hours', $hours > 0, (string) $hours, 'Configure working hours or set packages to 24/7/custom hours.'),
        ];
    }



    private function production_checklist_checks(): array
    {
        $flags = ['payments', 'webhooks', 'public_rest_booking'];
        $flag_details = [];
        $flag_states = [];
        foreach ($flags as $flag) {
            $enabled = function_exists('sltr_feature_enabled') ? \sltr_feature_enabled($flag) : false;
            $flag_states[$flag] = $enabled;
            $flag_details[] = $flag . '=' . ($enabled ? 'on' : 'off');
        }

        // Payments and incoming webhooks are supported production features.
        // Public REST booking is the only release-gated feature in this build and
        // must remain disabled until its dedicated security review is completed.
        $feature_flags_safe = empty($flag_states['public_rest_booking']);

        $db_version = (string) get_option(\Slotera\Core\Migrator::DB_VERSION_OPTION, '0.0.0');
        $target_version = defined('SLTR_VERSION') ? (string) SLTR_VERSION : 'unknown';
        $migrations_current = $target_version === 'unknown' || version_compare($db_version, $target_version, '>=');

        $cron_hooks = CronResilienceService::jobs();
        $missing_cron = [];
        foreach ($cron_hooks as $hook => $label) {
            if (!wp_next_scheduled((string) $hook)) {
                $missing_cron[] = $label;
            }
        }

        $wp_debug = defined('WP_DEBUG') && WP_DEBUG;
        $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $real_server_cron_configured = $this->real_server_cron_configured();
        $cron_transport_ok = !$cron_disabled || $real_server_cron_configured;
        $cron_detail = $missing_cron === [] ? 'Core Slotera jobs scheduled' : 'Missing: ' . implode(', ', $missing_cron);
        if ($cron_disabled) {
            $cron_detail = $real_server_cron_configured ? 'Real server cron configured; ' . $cron_detail : 'WP pseudo-cron disabled';
        }

        return [
            ['label' => 'Production feature flags', 'status' => $feature_flags_safe ? 'ok' : 'critical', 'detail' => implode(', ', $flag_details), 'recommendation' => $feature_flags_safe ? '' : 'Disable Public REST booking until its dedicated production security review is complete.'],
            ['label' => 'WP_DEBUG off', 'status' => !$wp_debug ? 'ok' : 'critical', 'detail' => $wp_debug ? 'Enabled' : 'Disabled', 'recommendation' => $wp_debug ? 'Set WP_DEBUG to false in production.' : ''],
            ['label' => 'Cron health', 'status' => ($cron_transport_ok && $missing_cron === []) ? 'ok' : 'warning', 'detail' => $cron_detail, 'recommendation' => !$cron_transport_ok ? 'Configure a real server cron for wp-cron.php and enable “Real server cron configured” in Slotera Settings → Advanced.' : ($missing_cron === [] ? '' : 'Open Slotera admin once or reactivate the plugin to register scheduled jobs.')],
            ['label' => 'DB migration status', 'status' => $migrations_current ? 'ok' : 'critical', 'detail' => 'DB ' . $db_version . ' / Code ' . $target_version, 'recommendation' => $migrations_current ? '' : 'Open the Slotera admin or reactivate the plugin so migrations can complete.'],
        ];
    }

    private function performance_checks(): array
    {
        $enabled = class_exists('\Slotera\Application\Services\PerformanceProfiler') && PerformanceProfiler::enabled();
        $sql_count = class_exists('\Slotera\Application\Services\PerformanceProfiler') ? PerformanceProfiler::current_sql_count() : 0;
        $entries = class_exists('\Slotera\Application\Services\PerformanceProfiler') ? PerformanceProfiler::entries() : [];
        $metrics = class_exists('\Slotera\Application\Services\PerformanceProfiler') ? PerformanceProfiler::metrics() : [];
        $slow = array_values(array_filter($entries, static fn($entry) => (float) ($entry['duration_ms'] ?? 0) >= 300));
        $settings_calls = (int) ($metrics['settings_all_calls'] ?? 0);
        $settings_hits = (int) ($metrics['settings_all_cache_hits'] ?? 0);
        $settings_misses = (int) ($metrics['settings_all_cache_misses'] ?? 0);
        $settings_resolve_ms = round((float) ($metrics['settings_all_resolve_ms'] ?? 0), 2);
        $services_initialized = (int) ($metrics['services_initialized'] ?? 0);
        $services_skipped = (int) ($metrics['services_skipped'] ?? 0);
        $cron_audit_runs = (int) ($metrics['cron_schedule_audit_run'] ?? 0);
        $cron_audit_skips = (int) ($metrics['cron_schedule_audit_skipped'] ?? 0);
        $cron_self_healed = (int) ($metrics['cron_schedule_self_healed'] ?? 0);
        $request_baselines = class_exists('\\Slotera\\Application\\Services\\PerformanceProfiler') ? PerformanceProfiler::baselines() : [];
        $service_entries = array_values(array_filter($entries, static fn($entry) => strpos((string) ($entry['operation'] ?? ''), 'service_register_') === 0));
        usort($service_entries, static fn($a, $b) => ((float) ($b['duration_ms'] ?? 0)) <=> ((float) ($a['duration_ms'] ?? 0)));
        $top_services = array_slice($service_entries, 0, 5);
        $top_service_detail = $top_services === []
            ? 'No service timings captured'
            : implode('; ', array_map(static function ($entry): string {
                $name = preg_replace('/^service_register_/', '', (string) ($entry['operation'] ?? 'service'));
                return $name . ' ' . number_format((float) ($entry['duration_ms'] ?? 0), 2, '.', '') . ' ms / ' . (int) ($entry['sql_queries'] ?? 0) . ' SQL';
            }, $top_services));

        $checks = [
            ['label' => 'Profiling mode', 'status' => $enabled ? 'warning' : 'ok', 'detail' => $enabled ? 'Enabled for current admin/dev request' : 'Disabled', 'recommendation' => $enabled ? 'Disable profiling after measuring performance on production.' : 'Enable temporarily from Tools/System status when diagnosing admin performance.'],
            ['label' => 'SQL queries in this diagnostics request', 'status' => $sql_count > 150 ? 'warning' : 'info', 'detail' => (string) $sql_count, 'recommendation' => $sql_count > 150 ? 'Review high-query admin pages and availability calls.' : ''],
            ['label' => 'Profiled operations this request', 'status' => $slow !== [] ? 'warning' : 'info', 'detail' => count($entries) . ' timed, ' . count($slow) . ' slow', 'recommendation' => $slow !== [] ? 'Open Slotera activity/observability logs and inspect performance.slow_operation events.' : ''],
            ['label' => 'Service bootstrap this request', 'status' => 'info', 'detail' => $services_initialized . ' initialized / ' . $services_skipped . ' skipped by request context', 'recommendation' => 'Compare ordinary frontend, booking, account, REST/AJAX and Slotera admin requests before expanding lazy initialization.'],
            ['label' => 'Slowest service registrations', 'status' => 'info', 'detail' => $top_service_detail, 'recommendation' => 'Prioritize only consistently expensive services for later lazy-loading work.'],
            ['label' => 'Settings resolution this request', 'status' => 'info', 'detail' => $settings_calls . ' calls — ' . $settings_hits . ' memoized / ' . $settings_misses . ' resolved — ' . $settings_resolve_ms . ' ms resolving', 'recommendation' => $settings_calls > 1 && $settings_hits === 0 ? 'Repeated settings reads are not being memoized; inspect request-local settings mutations.' : ''],
            ['label' => 'Cron schedule audit this request', 'status' => 'info', 'detail' => $cron_audit_runs > 0 ? 'AUDIT RUN' : ($cron_audit_skips > 0 ? 'THROTTLED — self-heal check active' . ($cron_self_healed > 0 ? ' — restored ' . $cron_self_healed : '') : 'Not observed'), 'recommendation' => 'A full persistent cron schedule audit is limited to once per 10 minutes; missing required jobs are still self-healed immediately.'],
        ];
        foreach ($request_baselines as $context => $baseline) {
            $last = is_array($baseline['last'] ?? null) ? $baseline['last'] : [];
            $avg = is_array($baseline['avg'] ?? null) ? $baseline['avg'] : [];
            $checks[] = [
                'label' => 'Request baseline — ' . str_replace('_', ' ', (string) $context),
                'status' => 'info',
                'detail' => 'n=' . (int) ($baseline['count'] ?? 0)
                    . ' — last SQL ' . (int) ($last['sql_queries'] ?? 0) . ' / avg ' . number_format((float) ($avg['sql_queries'] ?? 0), 1, '.', '')
                    . ' — init ' . number_format((float) ($last['plugin_init_ms'] ?? 0), 2, '.', '') . ' ms / avg ' . number_format((float) ($avg['plugin_init_ms'] ?? 0), 2, '.', '') . ' ms'
                    . ' — services ' . (int) ($last['services_initialized'] ?? 0) . '/' . (int) ($last['services_skipped'] ?? 0)
                    . ' — peak ' . number_format((float) ($last['peak_memory_mb'] ?? 0), 1, '.', '') . ' MB'
                    . (!empty($last['captured_at']) ? ' — ' . (string) $last['captured_at'] : ''),
                'recommendation' => 'Use logged-in admin visits to representative frontend/booking/account pages while profiling is enabled; profiling remains disabled by default on normal installations.',
            ];
        }
        return $checks;
    }

    private function payment_checks(): array
    {
        if (function_exists('sltr_feature_enabled') && !\sltr_feature_enabled('payments')) {
            return [
                ['label' => 'Payment feature gate', 'status' => 'ok', 'detail' => 'Disabled for MVP', 'recommendation' => 'No online payment setup is required for the current production stage.'],
            ];
        }

        $settings = (new SettingsRepository())->all();
        $enabled = array_filter(array_map('trim', explode(',', (string) ($settings['payment_enabled_gateways'] ?? ''))));
        $payment_on = !empty($settings['payment_mode_enabled']) || !empty($settings['prepayment_mode_enabled']);
        $checks = [
            $this->check('Payment mode', !$payment_on || !empty($enabled), $payment_on ? implode(', ', $enabled) : 'Booking-only / pay later', 'Enable at least one payment gateway when payment/prepayment mode is active.'),
        ];
        if (in_array('stripe', $enabled, true)) {
            $stripe_mode = sanitize_key((string) ($settings['payment_stripe_mode'] ?? 'test'));
            $stripe_publishable = $stripe_mode === 'live' ? ($settings['payment_stripe_live_publishable_key'] ?? '') : ($settings['payment_stripe_test_publishable_key'] ?? '');
            $stripe_secret = $stripe_mode === 'live' ? ($settings['payment_stripe_live_secret_key'] ?? '') : ($settings['payment_stripe_test_secret_key'] ?? '');
            $checks[] = $this->check('Stripe keys', !empty($stripe_publishable) && !empty($stripe_secret), strtoupper($stripe_mode) . ' — Stripe enabled', 'Add the Stripe publishable and secret keys for the active mode.');
            $checks[] = $this->check('Stripe webhook secret', !empty($settings['payment_stripe_webhook_secret']), !empty($settings['payment_stripe_webhook_secret']) ? 'Configured' : 'Missing', 'Add the Stripe webhook signing secret.');
        }
        if (in_array('paypal', $enabled, true)) {
            $paypal_mode = sanitize_key((string) ($settings['payment_paypal_mode'] ?? 'sandbox'));
            $paypal_client = $paypal_mode === 'live' ? ($settings['payment_paypal_live_client_id'] ?? '') : ($settings['payment_paypal_sandbox_client_id'] ?? '');
            $paypal_secret = $paypal_mode === 'live' ? ($settings['payment_paypal_live_client_secret'] ?? '') : ($settings['payment_paypal_sandbox_client_secret'] ?? '');
            $paypal_webhook_id = $settings['payment_paypal_webhook_id'] ?? '';
            $paypal_ready = !empty($paypal_client) && !empty($paypal_secret) && !empty($paypal_webhook_id);
            $checks[] = $this->check('PayPal credentials', $paypal_ready, strtoupper($paypal_mode) . ' — PayPal enabled', 'Add the PayPal client ID, client secret and webhook ID for the active mode.');
        }
        return $checks;
    }

    private function event_timestamp(?array $event): int
    {
        if (!$event) { return 0; }
        $value = trim((string) ($event['created_at'] ?? ''));
        if ($value === '') { return 0; }
        $timestamp = strtotime($value);
        return $timestamp === false ? 0 : $timestamp;
    }

    private function failure_state(?array $failure, ?array $success): array
    {
        if (!$failure) {
            return ['status' => 'ok', 'detail' => 'No recorded failures', 'active' => false];
        }

        $failure_detail = (string) ($failure['created_at'] ?? '') . ' — ' . (string) ($failure['event'] ?? '');
        $failure_ts = $this->event_timestamp($failure);
        $success_ts = $this->event_timestamp($success);
        if ($success_ts > 0 && $success_ts > $failure_ts) {
            return [
                'status' => 'info',
                'detail' => 'Historical — ' . $failure_detail . '; newer success at ' . (string) ($success['created_at'] ?? ''),
                'active' => false,
            ];
        }

        return ['status' => 'warning', 'detail' => $failure_detail, 'active' => true];
    }

    private function webhook_checks(): array
    {
        if (function_exists('sltr_feature_enabled') && !\sltr_feature_enabled('webhooks')) {
            return [
                ['label' => 'Webhook feature gate', 'status' => 'ok', 'detail' => 'Disabled for MVP', 'recommendation' => 'No webhook setup is required for the current production stage.'],
            ];
        }

        global $wpdb;
        $endpoints = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Database::outgoing_webhook_endpoints_table() . ' WHERE is_active=1');
        $unsigned = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . Database::outgoing_webhook_endpoints_table() . " WHERE is_active=1 AND (secret IS NULL OR secret='')");
        $failed = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . Database::outgoing_webhook_deliveries_table() . " WHERE status IN ('failed','failed_permanent')");
        $settings = (new SettingsRepository())->all();
        $stripe_mode = sanitize_key((string) ($settings['payment_stripe_mode'] ?? 'test'));
        $stripe_webhook_secret = trim((string) ($settings['payment_stripe_webhook_secret'] ?? ''));
        $paypal_mode = sanitize_key((string) ($settings['payment_paypal_mode'] ?? 'sandbox'));
        $paypal_webhook_id = trim((string) ($settings['payment_paypal_webhook_id'] ?? ''));
        $repo = new ActivityLogRepository();

        $stripe_latest_received = $repo->get_by_events(['stripe_webhook_received'], 1, 0)[0] ?? null;
        $stripe_latest_verified = $repo->get_by_events(['stripe_webhook_verified'], 1, 0)[0] ?? null;
        $stripe_latest_matched = $repo->get_by_events(['stripe_webhook_booking_match'], 1, 0)[0] ?? null;
        $stripe_latest_processed = $repo->get_by_events(['stripe_webhook_payment_applied', 'stripe_webhook_failure_applied', 'stripe_webhook_processed'], 1, 0)[0] ?? null;
        $stripe_latest_failure = $repo->get_by_events([
            'stripe_webhook_invalid_payload',
            'stripe_webhook_verify_error',
            'stripe_webhook_process_error',
        ], 1, 0)[0] ?? null;

        $stripe_received_detail = $stripe_latest_received ? (string) ($stripe_latest_received['created_at'] ?? '') : 'Never received';
        $stripe_verified_detail = $stripe_latest_verified ? (string) ($stripe_latest_verified['created_at'] ?? '') : 'Never verified';
        $stripe_matched_detail = $stripe_latest_matched ? ((string) ($stripe_latest_matched['created_at'] ?? '') . ' — booking #' . (string) ($stripe_latest_matched['object_id'] ?? '0')) : 'Never matched';
        $stripe_processed_detail = $stripe_latest_processed ? ((string) ($stripe_latest_processed['created_at'] ?? '') . ' — ' . (string) ($stripe_latest_processed['event'] ?? '')) : 'Never processed';
        $stripe_failure_state = $this->failure_state($stripe_latest_failure, $stripe_latest_processed ?: $stripe_latest_verified);

        $latest_received = $repo->get_by_events(['paypal_webhook_received'], 1, 0)[0] ?? null;
        $latest_verified = $repo->get_by_events(['paypal_webhook_verified'], 1, 0)[0] ?? null;
        $latest_capture_processed = $repo->get_by_events(['paypal_webhook_capture_processed'], 1, 0)[0] ?? null;
        $latest_processed = $repo->get_by_events(['paypal_webhook_processed', 'paypal_webhook_capture_processed'], 1, 0)[0] ?? null;
        $latest_failure = $repo->get_by_events([
            'paypal_webhook_invalid_payload',
            'paypal_webhook_verify_error',
            'paypal_webhook_verify_transport_error',
            'paypal_webhook_process_error',
        ], 1, 0)[0] ?? null;

        $paypal_processing = (new PaymentTransactionRepository())->search([
            'gateway' => 'paypal',
            'status' => 'processing',
        ], 1, 0)[0] ?? null;
        $paypal_processing_detail = 'No PayPal payment is currently recorded as processing.';
        if ($paypal_processing) {
            $processing_metadata = json_decode((string) ($paypal_processing['metadata_json'] ?? ''), true);
            $processing_metadata = is_array($processing_metadata) ? $processing_metadata : [];
            $processing_parts = [
                (string) ($paypal_processing['updated_at'] ?? $paypal_processing['created_at'] ?? ''),
                'booking #' . (string) ($paypal_processing['booking_id'] ?? '0'),
            ];
            $processing_capture_id = sanitize_text_field((string) ($processing_metadata['capture_id'] ?? $paypal_processing['external_parent_id'] ?? ''));
            $processing_order_id = sanitize_text_field((string) ($paypal_processing['external_id'] ?? ''));
            $processing_capture_status = strtoupper(sanitize_text_field((string) ($processing_metadata['capture_status'] ?? 'PENDING')));
            $processing_pending_reason = strtoupper(sanitize_text_field((string) ($processing_metadata['pending_reason'] ?? '')));
            if ($processing_capture_id !== '') { $processing_parts[] = 'capture ' . $processing_capture_id; }
            if ($processing_order_id !== '') { $processing_parts[] = 'order ' . $processing_order_id; }
            $processing_parts[] = 'status ' . ($processing_capture_status !== '' ? $processing_capture_status : 'PENDING');
            if ($processing_pending_reason !== '') { $processing_parts[] = 'reason ' . $processing_pending_reason; }
            $paypal_processing_detail = implode(' — ', array_filter($processing_parts));
        }

        $received_detail = $latest_received ? (string) ($latest_received['created_at'] ?? '') : 'Never received';
        $verified_detail = $latest_verified ? (string) ($latest_verified['created_at'] ?? '') : 'Never verified';
        $processed_detail = $latest_capture_processed
            ? ((string) ($latest_capture_processed['created_at'] ?? '') . ' — booking #' . (string) ($latest_capture_processed['object_id'] ?? '0'))
            : ($latest_processed ? (string) ($latest_processed['created_at'] ?? '') : 'Never processed');

        $capture_detail = 'No completed PayPal capture has been applied yet.';
        if ($latest_capture_processed) {
            $capture_payload = json_decode((string) ($latest_capture_processed['payload_json'] ?? ''), true);
            $capture_payload = is_array($capture_payload) ? $capture_payload : [];
            $capture_parts = [
                (string) ($latest_capture_processed['created_at'] ?? ''),
                'booking #' . (string) ($latest_capture_processed['object_id'] ?? '0'),
                sanitize_text_field((string) ($capture_payload['event_type'] ?? 'PAYMENT.CAPTURE.COMPLETED')),
            ];
            $capture_id = sanitize_text_field((string) ($capture_payload['capture_id'] ?? ''));
            $order_id = sanitize_text_field((string) ($capture_payload['order_id'] ?? ''));
            if ($capture_id !== '') { $capture_parts[] = 'capture ' . $capture_id; }
            if ($order_id !== '') { $capture_parts[] = 'order ' . $order_id; }
            $capture_detail = implode(' — ', array_filter($capture_parts));
        }
        $paypal_failure_state = $this->failure_state($latest_failure, $latest_capture_processed ?: $latest_processed ?: $latest_verified);

        $paypal_health = (new PayPalGatewayService())->webhook_registration_health();
        $paypal_api_status = !empty($paypal_health['api_authenticated']) ? 'ok' : (!empty($paypal_health['configured']) ? 'warning' : 'info');
        $paypal_api_detail = !empty($paypal_health['api_authenticated']) ? strtoupper((string) ($paypal_health['mode'] ?? $paypal_mode)) . ' — authenticated with current Slotera credentials' : ((string) ($paypal_health['error_message'] ?? '') ?: 'Not checked');
        $paypal_found = !empty($paypal_health['webhook_found']);
        $paypal_url_matches = !empty($paypal_health['url_matches']);
        $subscriptions = is_array($paypal_health['subscriptions'] ?? null) ? $paypal_health['subscriptions'] : [];
        $required_paypal_events = [
            'PAYMENT.CAPTURE.COMPLETED',
            'PAYMENT.CAPTURE.DECLINED',
            'PAYMENT.CAPTURE.DENIED',
            'PAYMENT.CAPTURE.PENDING',
            'PAYMENT.CAPTURE.REVERSED',
            'CHECKOUT.ORDER.VOIDED',
        ];
        $subscription_checks = [];
        foreach ($required_paypal_events as $event_name) {
            $event_status = strtoupper((string) ($subscriptions[$event_name] ?? 'MISSING'));
            $enabled_event = $event_status === 'ENABLED';
            $subscription_checks[] = [
                'label' => 'PayPal subscription — ' . $event_name,
                'status' => $paypal_found ? ($enabled_event ? 'ok' : 'critical') : 'info',
                'detail' => $paypal_found ? $event_status : 'Not checked — configured Webhook ID was not found for this app',
                'recommendation' => $paypal_found && !$enabled_event ? 'Enable this event for the configured PayPal webhook.' : '',
            ];
        }
        $paypal_reconcile_next = wp_next_scheduled(PayPalGatewayService::RECONCILE_CRON_HOOK);
        $paypal_reconcile_state = (new PayPalGatewayService())->reconciliation_state();
        $paypal_reconcile_last = sanitize_text_field((string) ($paypal_reconcile_state['finished_at'] ?? ''));
        $paypal_reconcile_counts = sprintf(
            'Checked %d — completed %d — pending %d — failed %d — errors %d',
            absint($paypal_reconcile_state['checked'] ?? 0),
            absint($paypal_reconcile_state['completed'] ?? 0),
            absint($paypal_reconcile_state['pending'] ?? 0),
            absint($paypal_reconcile_state['failed'] ?? 0),
            absint($paypal_reconcile_state['errors'] ?? 0)
        );
        $paypal_reconcile_checks = [
            [
                'label' => 'PayPal reconciliation scheduled',
                'status' => $paypal_reconcile_next ? 'ok' : 'critical',
                'detail' => $paypal_reconcile_next ? ('YES — next run ' . wp_date('Y-m-d H:i:s', (int) $paypal_reconcile_next)) : 'NO',
                'recommendation' => $paypal_reconcile_next ? '' : 'Visit the site after activating Slotera or verify WP-Cron; PayPal processing payments otherwise rely only on webhooks.',
            ],
            [
                'label' => 'Last PayPal reconciliation run',
                'status' => $paypal_reconcile_last !== '' ? 'ok' : 'info',
                'detail' => $paypal_reconcile_last !== '' ? ((function_exists('get_date_from_gmt') ? get_date_from_gmt($paypal_reconcile_last, 'Y-m-d H:i:s') : $paypal_reconcile_last) . ' — ' . $paypal_reconcile_counts) : 'Never run',
                'recommendation' => $paypal_reconcile_last !== '' ? '' : 'Use “Reconcile PayPal now” or wait for the scheduled reconciliation run.',
            ],
        ];
        foreach ((array) ($paypal_reconcile_state['results'] ?? []) as $reconcile_result) {
            if (!is_array($reconcile_result)) { continue; }
            $booking_id = absint($reconcile_result['booking_id'] ?? 0);
            $capture_id = sanitize_text_field((string) ($reconcile_result['capture_id'] ?? ''));
            $status = strtoupper(sanitize_text_field((string) ($reconcile_result['status'] ?? '')));
            $reason = strtoupper(sanitize_text_field((string) ($reconcile_result['reason'] ?? '')));
            $outcome = sanitize_key((string) ($reconcile_result['outcome'] ?? ''));
            $parts = ['booking #' . $booking_id];
            if ($capture_id !== '') { $parts[] = 'capture ' . $capture_id; }
            $parts[] = 'PayPal status ' . ($status !== '' ? $status : 'UNKNOWN');
            if ($reason !== '') { $parts[] = 'reason ' . $reason; }
            if ($outcome !== '') { $parts[] = 'outcome ' . $outcome; }
            $paypal_reconcile_checks[] = [
                'label' => 'PayPal reconciliation result',
                'status' => $outcome === 'error' ? 'warning' : ($outcome === 'pending' ? 'info' : 'ok'),
                'detail' => implode(' — ', $parts),
                'recommendation' => $outcome === 'error' ? 'Review PayPal API credentials/network access and retry reconciliation.' : '',
            ];
        }

        return [
            ['label' => 'Stripe incoming webhook endpoint', 'status' => $stripe_webhook_secret !== '' ? 'ok' : 'warning', 'detail' => strtoupper($stripe_mode) . ' — ' . rest_url('slotera/v1/payments/stripe/webhook'), 'recommendation' => $stripe_webhook_secret !== '' ? '' : 'Configure the Stripe webhook signing secret for the active Stripe mode.'],
            ['label' => 'Last Stripe webhook received', 'status' => $stripe_latest_received ? 'ok' : 'info', 'detail' => $stripe_received_detail, 'recommendation' => $stripe_latest_received ? '' : 'No incoming Stripe webhook has reached Slotera yet. Confirm the event destination is subscribed to checkout.session.completed.'],
            ['label' => 'Last Stripe webhook verified', 'status' => $stripe_latest_verified ? 'ok' : 'info', 'detail' => $stripe_verified_detail, 'recommendation' => $stripe_latest_verified ? '' : 'Waiting for a Stripe webhook that passes signing-secret verification.'],
            ['label' => 'Last Stripe webhook booking match', 'status' => $stripe_latest_matched ? 'ok' : 'info', 'detail' => $stripe_matched_detail, 'recommendation' => $stripe_latest_matched ? '' : 'No verified Stripe webhook has been matched to a Slotera booking yet.'],
            ['label' => 'Last Stripe webhook processed', 'status' => $stripe_latest_processed ? 'ok' : 'info', 'detail' => $stripe_processed_detail, 'recommendation' => $stripe_latest_processed ? '' : 'No Stripe webhook has updated a booking payment state yet.'],
            ['label' => 'Latest Stripe webhook failure', 'status' => $stripe_failure_state['status'], 'detail' => $stripe_failure_state['detail'], 'recommendation' => $stripe_failure_state['active'] ? 'Review the Stripe webhook event in Logs and the Stripe event-destination delivery response.' : ''],
            ['label' => 'PayPal incoming webhook endpoint', 'status' => $paypal_webhook_id !== '' ? 'ok' : 'warning', 'detail' => strtoupper($paypal_mode) . ' — ' . rest_url('slotera/v1/payments/paypal/webhook'), 'recommendation' => $paypal_webhook_id !== '' ? '' : 'Configure the PayPal Webhook ID for the active PayPal mode.'],
            ['label' => 'PayPal API app authentication', 'status' => $paypal_api_status, 'detail' => $paypal_api_detail, 'recommendation' => $paypal_api_status === 'warning' ? 'Check the active PayPal client credentials and outbound HTTPS access to PayPal.' : ''],
            ['label' => 'Configured PayPal Webhook ID belongs to current app', 'status' => $paypal_health['api_authenticated'] ? ($paypal_found ? 'ok' : 'critical') : 'info', 'detail' => $paypal_found ? 'YES — found among ' . (string) ($paypal_health['webhooks_count'] ?? 0) . ' webhook(s) for this app' : 'NO — not found for the OAuth app used by Slotera', 'recommendation' => $paypal_health['api_authenticated'] && !$paypal_found ? 'Create/select the webhook under the same PayPal REST app whose client credentials are configured in Slotera.' : ''],
            ['label' => 'PayPal webhook listener URL matches', 'status' => $paypal_found ? ($paypal_url_matches ? 'ok' : 'critical') : 'info', 'detail' => $paypal_found ? ($paypal_url_matches ? 'YES — ' . (string) ($paypal_health['actual_url'] ?? '') : 'NO — PayPal has ' . (string) ($paypal_health['actual_url'] ?? '')) : 'Not checked', 'recommendation' => $paypal_found && !$paypal_url_matches ? 'Update the PayPal webhook URL to the Slotera endpoint shown above.' : ''],
            ...$subscription_checks,
            ...$paypal_reconcile_checks,
            ['label' => 'Last PayPal completed payment', 'status' => $latest_capture_processed ? 'ok' : 'info', 'detail' => $capture_detail, 'recommendation' => $latest_capture_processed ? '' : 'Complete one PayPal Sandbox/Live payment to confirm the capture webhook is applied to a booking.'],
            ['label' => 'Current PayPal processing payment', 'status' => $paypal_processing ? 'info' : 'ok', 'detail' => $paypal_processing_detail, 'recommendation' => ''],
            ['label' => 'Last PayPal webhook received', 'status' => $latest_received ? 'ok' : 'info', 'detail' => $received_detail, 'recommendation' => $latest_received ? '' : 'No incoming PayPal webhook has reached Slotera yet. Browser-return payments can still succeed, but webhook redundancy is not yet confirmed.'],
            ['label' => 'Last PayPal webhook verified', 'status' => $latest_verified ? 'ok' : 'info', 'detail' => $verified_detail, 'recommendation' => $latest_verified ? '' : 'Waiting for a real PayPal webhook that passes signature verification.'],
            ['label' => 'Last PayPal webhook processed', 'status' => $latest_processed ? 'ok' : 'info', 'detail' => $processed_detail, 'recommendation' => $latest_processed ? '' : 'No verified PayPal webhook has been applied yet.'],
            ['label' => 'Latest PayPal webhook failure', 'status' => $paypal_failure_state['status'], 'detail' => $paypal_failure_state['detail'], 'recommendation' => $paypal_failure_state['active'] ? 'Review the PayPal webhook event in Logs and the PayPal delivery status.' : ''],
            ['label' => 'Active outgoing webhooks', 'status' => $endpoints > 0 ? 'ok' : 'info', 'detail' => (string) $endpoints, 'recommendation' => $endpoints > 0 ? '' : 'No outgoing webhooks configured. This is fine if you do not need integrations.'],
            ['label' => 'Active webhooks without signing secret', 'status' => $unsigned > 0 ? 'critical' : 'ok', 'detail' => (string) $unsigned, 'recommendation' => $unsigned > 0 ? 'Edit and save each endpoint with a signing secret before enabling it.' : ''],
            ['label' => 'Failed webhook deliveries', 'status' => $failed > 0 ? 'warning' : 'ok', 'detail' => (string) $failed, 'recommendation' => $failed > 0 ? 'Review Webhooks and Logs for failed deliveries.' : ''],
        ];
    }


    private function observability_checks(): array
    {
        $repo = new ActivityLogRepository();
        $recent_errors = $repo->search([
            'object_type' => 'observability',
            'has_error' => 1,
        ], 5, 0);
        $recent_events = $repo->search([
            'object_type' => 'observability',
        ], 20, 0);

        $last_event = $recent_events[0] ?? null;
        $last_detail = $last_event ? ((string) ($last_event['event'] ?? '') . ' at ' . (string) ($last_event['created_at'] ?? '')) : 'No events yet';
        $now = current_time('timestamp');
        $active_window = HOUR_IN_SECONDS;
        $active_errors = array_values(array_filter($recent_errors, function (array $event) use ($now, $active_window): bool {
            $timestamp = $this->event_timestamp($event);
            return $timestamp > 0 && ($now - $timestamp) <= $active_window;
        }));

        $error_status = 'ok';
        $error_detail = '0';
        $error_recommendation = '';
        if ($active_errors !== []) {
            $error_status = 'warning';
            $error_detail = count($active_errors) . ' in the last 60 minutes';
            $error_recommendation = 'Review recent observability events below and server PHP logs.';
        } elseif ($recent_errors !== []) {
            $latest_error = $recent_errors[0];
            $error_status = 'info';
            $error_detail = count($recent_errors) . ' historical — latest ' . (string) ($latest_error['event'] ?? 'error') . ' at ' . (string) ($latest_error['created_at'] ?? '');
        }

        return [
            ['label' => 'Observability logger', 'status' => class_exists(ObservabilityLogger::class) ? 'ok' : 'critical', 'detail' => class_exists(ObservabilityLogger::class) ? 'Enabled' : 'Missing', 'recommendation' => class_exists(ObservabilityLogger::class) ? '' : 'Restore ObservabilityLogger.'],
            ['label' => 'Correlation/request ID', 'status' => ObservabilityLogger::request_id() !== '' ? 'ok' : 'critical', 'detail' => ObservabilityLogger::request_id(), 'recommendation' => ''],
            ['label' => 'Recent observability errors', 'status' => $error_status, 'detail' => $error_detail, 'recommendation' => $error_recommendation],
            ['label' => 'Latest observability event', 'status' => $last_event ? 'info' : 'ok', 'detail' => $last_detail, 'recommendation' => ''],
        ];
    }


    private function cron_resilience_checks(): array
    {
        $health = CronResilienceService::health();
        $checks = [
            ['label' => 'Cron resilience service', 'status' => class_exists(CronResilienceService::class) ? 'ok' : 'critical', 'detail' => class_exists(CronResilienceService::class) ? 'Enabled' : 'Missing', 'recommendation' => class_exists(CronResilienceService::class) ? '' : 'Restore CronResilienceService.'],
        ];

        $now = time();
        foreach ($health as $state) {
            $label = (string) ($state['label'] ?? $state['hook'] ?? 'Cron job');
            $next = (int) ($state['next_run'] ?? 0);
            $locked = !empty($state['locked']);
            $last_status = (string) ($state['last_status'] ?? 'waiting');
            $last_success_raw = (string) ($state['last_success_at'] ?? '');
            $last_error = (string) ($state['last_error'] ?? '');
            $last_success_ts = $last_success_raw !== '' ? strtotime($last_success_raw) : 0;
            $stale = $last_success_ts > 0 && ($now - $last_success_ts) > (2 * DAY_IN_SECONDS);

            $status = 'ok';
            $recommendation = '';
            if ($next <= 0) {
                $status = 'warning';
                $recommendation = 'Open Slotera admin or reactivate the plugin to schedule this job.';
            } elseif ($last_status === 'error') {
                $status = 'critical';
                $recommendation = 'Review the last cron error and server/PHP logs.';
            } elseif ($locked) {
                $status = 'warning';
                $recommendation = 'A previous run is still locked. If it stays locked after its TTL, investigate slow or fatal cron execution.';
            } elseif ($stale) {
                $status = 'warning';
                $recommendation = $label === 'Privacy retention cleanup' ? 'This scheduled cleanup has not executed recently. Verify the hook registration, recurrence and last execution time.' : 'Verify WP-Cron or server cron is actually executing scheduled events.';
            }

            $detail = 'next=' . ($next > 0 ? wp_date('Y-m-d H:i:s', $next) : 'not scheduled') . '; last_success=' . ($last_success_raw !== '' ? $last_success_raw : 'never') . '; status=' . $last_status;
            if ($last_error !== '') { $detail .= '; error=' . $last_error; }
            $checks[] = ['label' => $label, 'status' => $status, 'detail' => $detail, 'recommendation' => $recommendation];
        }

        return $checks;
    }


    public function translation_quality_checks(): array
    {
        return (new TranslationQualityReportService())->checks();
    }

    public function translation_quality_report(): array
    {
        return (new TranslationQualityReportService())->report();
    }

    public function recent_observability_events(int $limit = 10): array
    {
        
        $repo = new ActivityLogRepository();
        $events = $repo->search(['object_type' => 'observability', 'has_error' => 1], max(1, min(50, $limit)), 0);
        if ($events === []) { $events = $repo->search(['object_type' => 'observability'], max(1, min(50, $limit * 2)), 0); }
        $seen = []; $unique = [];
        foreach ($events as $event) {
            $payload = json_decode((string)($event['payload_json'] ?? '{}'), true);
            $request = is_array($payload) ? (string)($payload['request_id'] ?? '') : '';
            $fingerprint = implode('|', [(string)($event['event'] ?? ''),(string)($event['message'] ?? ''),$request,(string)($event['created_at'] ?? '')]);
            if (isset($seen[$fingerprint])) { continue; }
            $seen[$fingerprint] = true; $unique[] = $event;
            if (count($unique) >= $limit) { break; }
        }
        return $unique;
    }

    private function privacy_checks(): array
    {
        $settings = (new SettingsRepository())->all();
        return [
            ['label' => 'Remove data on uninstall', 'status' => !empty($settings['privacy_remove_data_on_uninstall']) ? 'warning' : 'ok', 'detail' => !empty($settings['privacy_remove_data_on_uninstall']) ? 'Enabled' : 'Disabled', 'recommendation' => !empty($settings['privacy_remove_data_on_uninstall']) ? 'Data will be removed when the plugin is uninstalled.' : 'Data is preserved on uninstall.'],
            $this->check('Activity log retention', (int) ($settings['privacy_activity_log_retention_days'] ?? 0) > 0, (string) ($settings['privacy_activity_log_retention_days'] ?? 0) . ' days', 'Set a retention period for activity logs.'),
            ['label' => 'Booking anonymization', 'status' => (int) ($settings['privacy_anonymize_completed_bookings_days'] ?? 365) > 0 ? 'ok' : 'warning', 'detail' => (int) ($settings['privacy_anonymize_completed_bookings_days'] ?? 365) > 0 ? (string) ($settings['privacy_anonymize_completed_bookings_days'] ?? 365) . ' days' : 'Disabled', 'recommendation' => (int) ($settings['privacy_anonymize_completed_bookings_days'] ?? 365) > 0 ? '' : 'Automatic anonymization is disabled. Configure a retention period if required by your privacy policy.'],
        ];
    }

    private function check(string $label, bool $ok, string $detail, string $recommendation): array
    {
        return [
            'label' => $label,
            'status' => $ok ? 'ok' : 'critical',
            'detail' => $detail,
            'recommendation' => $ok ? '' : $recommendation,
        ];
    }
}
