<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/**
 * Lightweight request-local performance profiler.
 *
 * Profiling is intentionally admin/dev-only. It can be enabled by either
 * defining SLTR_PROFILING=true in wp-config.php, enabling WP_DEBUG for an
 * administrator, or setting the sltr_profiling_mode option from Tools/System
 * status. Public visitors and anonymous AJAX requests are never profiled.
 */
final class PerformanceProfiler
{
    private const SLOW_THRESHOLD_MS = 300.0;

    /** @var array<int,array<string,mixed>> */
    private static array $entries = [];

    /** @var array<string,int|float> */
    private static array $metrics = [];

    private static ?bool $enabled_cache = null;
    private static bool $baseline_capture_registered = false;
    private const BASELINE_OPTION = 'sltr_performance_request_baselines';
    private const BASELINE_SAMPLE_LIMIT = 10;

    public static function enabled(): bool
    {
        // This method is called by every timed operation. Resolve the profiling
        // gate once per request so profiling itself does not add repeated
        // capability/option lookups to the hot path.
        if (self::$enabled_cache !== null) {
            return self::$enabled_cache;
        }

        $constant = defined('SLTR_PROFILING') && SLTR_PROFILING;
        $option = function_exists('get_option') && (int) get_option('sltr_profiling_mode', 0) === 1;
        $doing_cron = function_exists('wp_doing_cron') && wp_doing_cron();

        // Cron has no interactive administrator session. Allow explicitly enabled
        // profiling there so the baseline collector can measure real scheduled
        // requests, but never enable it merely because WP_DEBUG is on.
        if ($doing_cron) {
            return self::$enabled_cache = (bool) ($constant || $option);
        }

        if (function_exists('is_user_logged_in') && !is_user_logged_in()) {
            return self::$enabled_cache = false;
        }
        if (function_exists('current_user_can') && !current_user_can(\Slotera\Core\Capabilities::MANAGE_TOOLS)) {
            return self::$enabled_cache = false;
        }

        $debug = defined('WP_DEBUG') && WP_DEBUG;

        return self::$enabled_cache = (bool) ($constant || $option || $debug);
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public static function time(string $operation, callable $callback)
    {
        if (!self::enabled()) {
            return $callback();
        }

        $start = microtime(true);
        $start_queries = self::query_count();
        try {
            return $callback();
        } finally {
            self::record($operation, $start, $start_queries);
        }
    }

    public static function start(): array
    {
        return [microtime(true), self::query_count()];
    }

    public static function finish(string $operation, array $token): void
    {
        if (!self::enabled()) { return; }
        $started_at = isset($token[0]) ? (float) $token[0] : microtime(true);
        $start_queries = isset($token[1]) ? (int) $token[1] : self::query_count();
        self::record($operation, $started_at, $start_queries);
    }

    /** @return array<int,array<string,mixed>> */
    public static function entries(): array
    {
        return self::$entries;
    }

    public static function current_sql_count(): int
    {
        return self::query_count();
    }

    public static function metric(string $name, float $delta = 1.0): void
    {
        if (!self::enabled()) {
            return;
        }

        $key = sanitize_key(str_replace(['.', ':', ' '], '_', $name));
        if ($key === '') {
            return;
        }
        self::$metrics[$key] = (self::$metrics[$key] ?? 0) + $delta;
    }

    /** @return array<string,int|float> */
    public static function metrics(): array
    {
        return self::$metrics;
    }

    public static function register_baseline_capture(): void
    {
        if (self::$baseline_capture_registered || !self::enabled() || !function_exists('add_action')) {
            return;
        }
        self::$baseline_capture_registered = true;
        add_action('shutdown', [self::class, 'capture_request_baseline'], PHP_INT_MAX);
    }

    public static function capture_request_baseline(): void
    {
        if (!self::enabled() || !function_exists('get_option') || !function_exists('update_option')) {
            return;
        }

        $context = self::request_context();
        $entries = self::$entries;
        $plugin_init_ms = 0.0;
        foreach ($entries as $entry) {
            if (($entry['operation'] ?? '') === 'core_plugin_init') {
                $plugin_init_ms = (float) ($entry['duration_ms'] ?? 0);
                break;
            }
        }
        $slow = 0;
        foreach ($entries as $entry) {
            if ((float) ($entry['duration_ms'] ?? 0) >= self::SLOW_THRESHOLD_MS) {
                $slow++;
            }
        }

        $sample = [
            'captured_at' => function_exists('current_time') ? (string) current_time('mysql') : gmdate('Y-m-d H:i:s'),
            'sql_queries' => self::query_count(),
            'plugin_init_ms' => round($plugin_init_ms, 2),
            'services_initialized' => (int) (self::$metrics['services_initialized'] ?? 0),
            'services_skipped' => (int) (self::$metrics['services_skipped'] ?? 0),
            'settings_calls' => (int) (self::$metrics['settings_all_calls'] ?? 0),
            'settings_resolve_ms' => round((float) (self::$metrics['settings_all_resolve_ms'] ?? 0), 2),
            'profiled_operations' => count($entries),
            'slow_operations' => $slow,
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
        ];

        $all = get_option(self::BASELINE_OPTION, []);
        if (!is_array($all)) { $all = []; }
        $samples = isset($all[$context]) && is_array($all[$context]) ? $all[$context] : [];
        $samples[] = $sample;
        if (count($samples) > self::BASELINE_SAMPLE_LIMIT) {
            $samples = array_slice($samples, -self::BASELINE_SAMPLE_LIMIT);
        }
        $all[$context] = $samples;
        update_option(self::BASELINE_OPTION, $all, false);
    }

    /** @return array<string,array<string,mixed>> */
    public static function baselines(): array
    {
        if (!function_exists('get_option')) { return []; }
        $all = get_option(self::BASELINE_OPTION, []);
        if (!is_array($all)) { return []; }

        $out = [];
        foreach ($all as $context => $samples) {
            if (!is_array($samples) || $samples === []) { continue; }
            $numeric = ['sql_queries', 'plugin_init_ms', 'services_initialized', 'services_skipped', 'settings_calls', 'settings_resolve_ms', 'profiled_operations', 'slow_operations', 'peak_memory_mb'];
            $avg = [];
            foreach ($numeric as $key) {
                $values = [];
                foreach ($samples as $sample) {
                    if (is_array($sample) && isset($sample[$key]) && is_numeric($sample[$key])) {
                        $values[] = (float) $sample[$key];
                    }
                }
                $avg[$key] = $values === [] ? 0 : round(array_sum($values) / count($values), 2);
            }
            $out[(string) $context] = [
                'count' => count($samples),
                'last' => end($samples),
                'avg' => $avg,
            ];
        }
        ksort($out);
        return $out;
    }

    private static function request_context(): string
    {
        if (function_exists('wp_doing_cron') && wp_doing_cron()) { return 'cron'; }
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) { return 'ajax'; }
        if (defined('REST_REQUEST') && REST_REQUEST) { return 'rest'; }

        $script = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
        $pagenow = isset($GLOBALS['pagenow']) ? (string) $GLOBALS['pagenow'] : '';
        if ($script === 'admin-post.php' || $pagenow === 'admin-post.php') { return 'admin_post'; }
        if (function_exists('is_admin') && is_admin()) { return 'admin'; }

        if (function_exists('get_queried_object_id') && function_exists('get_post_field')) {
            $post_id = (int) get_queried_object_id();
            if ($post_id > 0) {
                $content = (string) get_post_field('post_content', $post_id);
                if (preg_match('/\\[slotera_account(?:\\s|\\]|\\/)/i', $content)) { return 'account'; }
                if (preg_match('/\\[slotera_booking(?:\\s|\\]|\\/)/i', $content)) { return 'booking'; }
            }
        }
        return 'frontend';
    }

    private static function record(string $operation, float $started_at, int $start_queries): void
    {
        $duration_ms = round((microtime(true) - $started_at) * 1000, 2);
        $queries = max(0, self::query_count() - $start_queries);
        $entry = [
            'operation' => sanitize_key(str_replace(['.', ':', ' '], '_', $operation)),
            'duration_ms' => $duration_ms,
            'sql_queries' => $queries,
            'threshold_ms' => self::SLOW_THRESHOLD_MS,
        ];
        self::$entries[] = $entry;

        if ($duration_ms >= self::SLOW_THRESHOLD_MS) {
            do_action('sltr_observe', 'performance.slow_operation', 'warning', 'Slow Slotera operation detected.', $entry);
        }
    }

    private static function query_count(): int
    {
        global $wpdb;
        return isset($wpdb) && isset($wpdb->num_queries) ? (int) $wpdb->num_queries : 0;
    }
}
