<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validates the frozen public translation-key contract.
 *
 * The lock file is intentionally separate from the runtime registry so build/CI
 * can detect accidental key removals, group moves, default-text rewrites, or
 * placeholder changes before a release silently breaks bundled RU/ET catalogs
 * or existing custom translations saved in WordPress options.
 */
final class TranslationKeyFreeze
{
    public const LOCK_RELATIVE_PATH = 'languages/slotera-booking.translation-keys.lock.json';
    public const SCHEMA_VERSION = 1;

    public static function lock_path(): string
    {
        if (defined('SLTR_PLUGIN_DIR')) {
            return rtrim((string) SLTR_PLUGIN_DIR, '/\\') . '/' . self::LOCK_RELATIVE_PATH;
        }

        return dirname(__DIR__, 3) . '/' . self::LOCK_RELATIVE_PATH;
    }

    /**
     * @return array<int, string> Human-readable validation errors.
     */
    public static function validate(?string $lock_path = null): array
    {
        $lock_path = $lock_path ?: self::lock_path();
        if (!is_readable($lock_path)) {
            return ['Translation key lock file is missing or unreadable: ' . $lock_path];
        }

        $raw = file_get_contents($lock_path);
        $lock = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($lock)) {
            return ['Translation key lock file is not valid JSON: ' . $lock_path];
        }

        $locked_keys = isset($lock['keys']) && is_array($lock['keys']) ? $lock['keys'] : [];
        $current = self::snapshot();
        $errors = [];

        foreach ($locked_keys as $key => $locked_meta) {
            if (!isset($current[$key])) {
                $errors[] = 'Removed frozen translation key: ' . $key;
                continue;
            }

            if (!is_array($locked_meta)) {
                $errors[] = 'Invalid lock metadata for key: ' . $key;
                continue;
            }

            foreach (['group', 'default_sha1', 'placeholder_signature'] as $field) {
                $locked_value = (string) ($locked_meta[$field] ?? '');
                $current_value = (string) ($current[$key][$field] ?? '');
                if ($locked_value !== $current_value) {
                    $errors[] = sprintf(
                        'Changed frozen translation key %s field %s: %s -> %s',
                        $key,
                        $field,
                        $locked_value,
                        $current_value
                    );
                }
            }
        }

        foreach ($current as $key => $_meta) {
            if (!isset($locked_keys[$key])) {
                $errors[] = 'New translation key not added to lock file: ' . $key;
            }
        }

        return $errors;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function snapshot(): array
    {
        $snapshot = [];
        $frozen_groups = array_fill_keys(array_keys(TranslationRegistry::groups()), true);
        foreach (TranslationRegistry::strings() as $key => $meta) {
            $group = (string) ($meta['group'] ?? '');
            if (!isset($frozen_groups[$group])) {
                continue;
            }
            $default = (string) ($meta['default'] ?? '');
            $snapshot[(string) $key] = [
                'group' => $group,
                'default_sha1' => sha1($default),
                'placeholder_signature' => self::placeholder_signature($default),
            ];
        }
        ksort($snapshot);

        return $snapshot;
    }

    public static function placeholder_signature(string $text): string
    {
        preg_match_all('/%(?:\d+\$)?[bcdeEfFgGosuxX]/', $text, $matches);
        $placeholders = $matches[0] ?? [];
        return implode('|', $placeholders);
    }
}
