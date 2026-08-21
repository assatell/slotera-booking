<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class RequestValidator
{
    public function require_admin(string $capability = \Slotera\Core\Capabilities::MANAGE): void
    {
        if (!current_user_can($capability)) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'slotera-booking'));
        }
    }

    public function verify_admin_nonce(string $action, string $query_arg = '_wpnonce'): void
    {
        check_admin_referer($action, $query_arg);
    }

    public function verify_ajax_nonce(string $action, string $query_arg = 'nonce'): void
    {
        check_ajax_referer($action, $query_arg);
    }

    public function post_text(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash((string) $_POST[$key])) : $default;
    }

    public function post_key(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? sanitize_key(wp_unslash((string) $_POST[$key])) : $default;
    }

    public function post_email(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? sanitize_email(wp_unslash((string) $_POST[$key])) : $default;
    }

    public function post_html(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? wp_kses_post(wp_unslash((string) $_POST[$key])) : $default;
    }

    public function post_textarea(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash((string) $_POST[$key])) : $default;
    }

    public function post_int(string $key, int $default = 0): int
    {
        return isset($_POST[$key]) ? absint(wp_unslash((string) $_POST[$key])) : $default;
    }

    public function post_float(string $key, float $default = 0.0): float
    {
        return isset($_POST[$key]) ? (float) sanitize_text_field(wp_unslash((string) $_POST[$key])) : $default;
    }

    public function post_bool(string $key): int
    {
        return !empty(wp_unslash((string) ($_POST[$key] ?? ''))) ? 1 : 0;
    }



    /**
     * Check whether a POST key was submitted without exposing superglobals to callers.
     */
    public function post_has(string $key): bool
    {
        return array_key_exists($key, $_POST);
    }

    /**
     * Return an unslashed POST scalar/array value for higher-level validators.
     *
     * This intentionally does not sanitize because some callers need to preserve
     * nested structures before applying domain-specific sanitizers.
     *
     * @return mixed
     */
    public function post_raw(string $key, $default = null)
    {
        return array_key_exists($key, $_POST) ? wp_unslash($_POST[$key]) : $default;
    }

    /**
     * Return a raw, unslashed POST array or an empty array.
     *
     * @return array<mixed>
     */
    public function post_raw_array(string $key): array
    {
        $value = $this->post_raw($key, []);
        return is_array($value) ? $value : [];
    }

    /**
     * Read a submitted truthy checkbox/toggle value.
     */
    public function post_truthy(string $key): bool
    {
        if (!$this->post_has($key)) {
            return false;
        }
        $value = $this->post_raw($key, '');
        if (is_array($value)) {
            $value = end($value);
        }
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Decode a JSON object/array submitted in POST and sanitize it recursively.
     *
     * @return array<mixed>
     */
    public function post_json_array(string $key, string $sanitize = 'text'): array
    {
        $value = $this->post_raw($key, '');
        if (is_array($value)) {
            return [];
        }
        $decoded = json_decode((string) $value, true);
        if (!is_array($decoded)) {
            return [];
        }
        return $this->sanitize_array_deep($decoded, $sanitize);
    }

    /**
     * Read split hour/minute fields or a fallback minute field from POST.
     */
    public function post_duration_minutes(string $base, string $minutes_key, int $default = 0, int $min = 0, int $max = 1440): int
    {
        $hours_key = $base . '_hours';
        $mins_key = $base . '_mins';
        if ($this->post_has($hours_key) || $this->post_has($mins_key)) {
            return BusinessValidator::duration_from_hours_minutes(
                $this->post_raw($hours_key, 0),
                $this->post_raw($mins_key, 0),
                $default,
                $min,
                $max
            );
        }
        return BusinessValidator::duration_minutes($this->post_int($minutes_key, $default), $default, $min, $max);
    }

    public function get_int(string $key, int $default = 0): int
    {
        return isset($_GET[$key]) ? absint(wp_unslash((string) $_GET[$key])) : $default;
    }

    public function get_key(string $key, string $default = ''): string
    {
        return isset($_GET[$key]) ? sanitize_key(wp_unslash((string) $_GET[$key])) : $default;
    }

    public function get_text(string $key, string $default = ''): string
    {
        return isset($_GET[$key]) ? sanitize_text_field(wp_unslash((string) $_GET[$key])) : $default;
    }

    /**
     * Return a deeply unslashed and sanitized POST array.
     *
     * @param string $key      POST key.
     * @param string $sanitize Sanitizer for scalar values: text, textarea, html, key, email, url, int, float.
     */
    public function post_array(string $key, string $sanitize = 'text'): array
    {
        if (!isset($_POST[$key])) {
            return [];
        }

        $value = wp_unslash($_POST[$key]);
        if (!is_array($value)) {
            return [];
        }

        return $this->sanitize_array_deep($value, $sanitize);
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private function sanitize_array_deep(array $value, string $sanitize): array
    {
        $clean = [];

        foreach ($value as $item_key => $item_value) {
            $clean[$item_key] = is_array($item_value)
                ? $this->sanitize_array_deep($item_value, $sanitize)
                : $this->sanitize_scalar($item_value, $sanitize);
        }

        return $clean;
    }

    /**
     * @param mixed $value
     * @return int|float|string
     */
    private function sanitize_scalar($value, string $sanitize)
    {
        $value = (string) $value;

        switch ($sanitize) {
            case 'html':
                return wp_kses_post($value);
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'key':
                return sanitize_key($value);
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'int':
                return absint($value);
            case 'float':
                return (float) sanitize_text_field($value);
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }

    public function sanitize_hours_from_post(): array
    {
        $hours = [];
        $starts = $this->post_array('start');
        $ends = $this->post_array('end');
        $enabled = $this->post_array('enabled');

        foreach ($starts as $day => $start) {
            $day_index = absint((string) $day);
            $hours[$day_index] = [
                'start_time' => BusinessValidator::time((string) $start, '09:00'),
                'end_time' => BusinessValidator::time((string) ($ends[$day] ?? ''), '17:00'),
                'is_enabled' => !empty($enabled[$day]) ? 1 : 0,
            ];
        }

        return $hours;
    }
}
