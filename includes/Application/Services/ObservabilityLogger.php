<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Application\Security\DataRedactor;

if (!defined('ABSPATH')) { exit; }

/**
 * Lightweight production-safe observability logger.
 *
 * Stores structured events in the existing activity log table so MVP installs do
 * not need another table. Sensitive values are redacted before persistence and a
 * per-request correlation id is attached to every event payload.
 */
final class ObservabilityLogger
{
    private static string $request_id = '';
    private static bool $registered = false;

    public static function request_id(): string
    {
        if (self::$request_id === '') {
            // Internal correlation IDs must be server-generated. Never trust a
            // client-supplied X-Request-ID for log correlation or diagnostics.
            self::$request_id = self::generate_request_id();
        }

        return self::$request_id;
    }

    private static function generate_request_id(): string
    {
        if (function_exists('wp_generate_uuid4')) {
            return (string) wp_generate_uuid4();
        }

        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            return uniqid('sltr_', true);
        }
    }

    public function register_hooks(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        add_action('shutdown', [$this, 'capture_shutdown_error'], 0);
        add_action('sltr_observe', [$this, 'observe_from_hook'], 10, 4);
    }

    /**
     * @param mixed $context
     */
    public function observe(string $event, string $level = 'info', string $message = '', $context = []): int
    {
        if (!is_array($context)) {
            $context = ['value' => $context];
        }

        $level = sanitize_key($level);
        if (!in_array($level, ['debug', 'info', 'warning', 'error'], true)) {
            $level = 'info';
        }

        $payload = DataRedactor::payload(array_merge($context, [
            'request_id' => self::request_id(),
            'request' => [
                'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
                'uri' => self::request_path(),
                'ajax_action' => sanitize_key((string) ($_REQUEST['action'] ?? '')),
            ],
        ]));

        return (new ActivityLogService())->log([
            'object_type' => 'observability',
            'object_id' => 0,
            'event' => sanitize_key($event),
            'status' => $level === 'error' ? 'error' : ($level === 'warning' ? 'warning' : 'info'),
            'message' => $message !== '' ? $message : $event,
            'payload' => $payload,
        ]);
    }


    private static function request_path(): string
    {
        $request_uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if ($request_uri === '') {
            return '';
        }

        // Query strings can contain bearer-equivalent booking/account tokens,
        // OAuth authorization codes and WordPress nonces. Observability only
        // needs the route path for diagnostics/correlation, never credentials.
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }

        return '/' . ltrim($path, '/');
    }

    /**
     * @param mixed $context
     */
    public function observe_from_hook(string $event, string $level = 'info', $message = '', $context = []): void
    {
        // Backward compatible with older/internal hook calls that passed
        // do_action('sltr_observe', $level, $event, $context). Never let
        // observability break user-facing flows such as booking creation.
        if (is_array($message) && $context === []) {
            $context = $message;
            $message = '';
        }

        if (!in_array($level, ['debug', 'info', 'warning', 'error'], true) && in_array($event, ['debug', 'info', 'warning', 'error'], true)) {
            $actual_level = $event;
            $event = $level;
            $level = $actual_level;
        }

        $this->observe($event, $level, is_scalar($message) ? (string) $message : '', is_array($context) ? $context : ['value' => $context]);
    }

    public function capture_shutdown_error(): void
    {
        $error = error_get_last();
        if (!is_array($error)) {
            return;
        }

        $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
        if (!in_array((int) ($error['type'] ?? 0), $fatal_types, true)) {
            return;
        }

        $file = (string) ($error['file'] ?? '');
        if ($file !== '' && defined('SLTR_PLUGIN_DIR') && strpos($file, (string) SLTR_PLUGIN_DIR) !== 0) {
            return;
        }

        $this->observe('php_fatal_error', 'error', 'Fatal PHP error captured.', [
            'type' => (int) ($error['type'] ?? 0),
            'message' => (string) ($error['message'] ?? ''),
            'file' => $file,
            'line' => (int) ($error['line'] ?? 0),
        ]);
    }
}
