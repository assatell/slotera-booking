<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Application\Services\Translations\EmailTranslationNamespace;

if (!defined('ABSPATH')) {
    exit;
}

final class TranslationService
{
    private const OPTION_NAME = 'sltr_translations';
    private const LOCALE_OPTION_NAME = 'sltr_translation_context_locales';
    private const CUSTOM_LANGUAGE_OPTION_NAME = 'sltr_custom_languages';
    private const LOCALE_SOURCE_OPTION_NAME = 'sltr_translation_context_locale_sources';
    private const MANUAL_OVERRIDE_OPTION_NAME = 'sltr_translation_manual_overrides';

    public function get(string $key, ?string $locale = null): string
    {
        $key = EmailTranslationNamespace::normalize_key($key);
        $meta = TranslationRegistry::meta_for($key) ?? [];
        $group = (string) ($meta['group'] ?? TranslationRegistry::group_for($key));
        if ($group === '') { $group = 'frontend'; }

        // v1.0.617: Slotera's own Admin UI registry is English-only.
        // Keep the catalog for key compatibility, but ignore stored, bundled,
        // and user-locale admin translations. Frontend and email groups retain
        // their existing locale and override behavior unchanged.
        if ($group === 'admin') {
            return TranslationRegistry::default_for($key);
        }

        $locale = $this->normalize_locale($locale ?: $this->locale_for_group($group));
        $locale = $this->sanitize_locale_for_group($group, $locale);
        $custom = $this->all_custom();
        $manual = $this->manual_overrides();
        $has_manual_override = !empty($manual[$group][$locale][$key]);
        $allow_custom_override = $has_manual_override || !in_array($group, ['admin', 'emails'], true) || $locale !== TranslationRegistry::default_locale();
        if ($group === 'frontend' && in_array($locale, ['ru_RU', 'ru'], true) && !$has_manual_override) {
            $allow_custom_override = false;
        }

        if ($allow_custom_override && !empty($custom[$group][$locale][$key])) {
            $custom_value = (string) $custom[$group][$locale][$key];
            if ($has_manual_override || !$this->looks_like_bad_locale_override($custom_value, $locale)) {
                return $custom_value;
            }
        }

        // Backward compatibility with v1.0.196 storage: [locale][key].
        if (!$has_manual_override && $allow_custom_override && !empty($custom[$locale][$key])) {
            $custom_value = (string) $custom[$locale][$key];
            if (!$this->looks_like_bad_locale_override($custom_value, $locale)) {
                return $custom_value;
            }
        }

        if ($locale !== TranslationRegistry::default_locale() && !empty($meta[$locale])) {
            return (string) $meta[$locale];
        }

        return isset($meta['default']) ? (string) $meta['default'] : TranslationRegistry::default_for($key);
    }

    public function translate_default(string $default, ?string $group = null, ?string $locale = null): ?string
    {
        $key = TranslationRegistry::key_for_default($default, $group);
        if ($key === null) {
            return null;
        }

        return $this->get($key, $locale);
    }


    public function cleanup_frontend_locale_overrides(array $locales): void
    {
        $custom = $this->all_custom();
        $manual = $this->manual_overrides();
        $changed = false;
        $strings = TranslationRegistry::strings_for_group('frontend');

        foreach ($locales as $raw_locale) {
            $locale = $this->normalize_locale((string) $raw_locale);

            if (!empty($custom['frontend'][$locale]) && is_array($custom['frontend'][$locale])) {
                foreach ($custom['frontend'][$locale] as $key => $value) {
                    if (!empty($manual['frontend'][$locale][$key])) {
                        continue;
                    }

                    $default = (string) ($strings[$key]['default'] ?? '');
                    $bundled = (string) ($strings[$key][$locale] ?? '');
                    $value = is_string($value) ? trim($value) : '';

                    if ($value === '' || $value === $default || $this->looks_like_mixed_language_fallback($value, $locale)) {
                        unset($custom['frontend'][$locale][$key]);
                        $changed = true;
                        continue;
                    }

                    if ($bundled !== '' && $value === $bundled) {
                        unset($custom['frontend'][$locale][$key]);
                        $changed = true;
                    }
                }

                if (empty($custom['frontend'][$locale])) {
                    unset($custom['frontend'][$locale]);
                }
            }

            // v1.0.196 and early i18n builds also stored overrides as [locale][key].
            // Clean those flat frontend overrides too; otherwise old word-by-word
            // machine translations can keep winning over the bundled RU strings.
            if (!empty($custom[$locale]) && is_array($custom[$locale])) {
                foreach ($custom[$locale] as $key => $value) {
                    if (!isset($strings[$key])) {
                        continue;
                    }

                    $default = (string) ($strings[$key]['default'] ?? '');
                    $bundled = (string) ($strings[$key][$locale] ?? '');
                    $value = is_string($value) ? trim($value) : '';

                    if ($value === '' || $value === $default || $this->looks_like_mixed_language_fallback($value, $locale) || ($bundled !== '' && $value === $bundled)) {
                        unset($custom[$locale][$key]);
                        $changed = true;
                    }
                }

                if (empty($custom[$locale])) {
                    unset($custom[$locale]);
                }
            }
        }

        if ($changed) {
            update_option(self::OPTION_NAME, $custom, false);
        }
    }

    public function save_locale(string $group, string $locale, array $values): void
    {
        $group = sanitize_key($group);
        $groups = TranslationRegistry::groups();
        if (!isset($groups[$group])) {
            return;
        }

        $locale = $this->normalize_locale($locale);
        $languages = TranslationRegistry::languages_for_group($group);
        if (!isset($languages[$locale])) {
            return;
        }

        $strings = TranslationRegistry::strings_for_group($group);
        $custom = $this->all_custom();
        $manual = $this->manual_overrides();
        $custom[$group] = $custom[$group] ?? [];
        $custom[$group][$locale] = $custom[$group][$locale] ?? [];
        $manual[$group] = $manual[$group] ?? [];
        $manual[$group][$locale] = $manual[$group][$locale] ?? [];
        $is_custom_locale = $this->is_custom_locale($locale);

        foreach ($strings as $key => $meta) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            $raw = is_string($values[$key]) ? wp_unslash($values[$key]) : '';
            $value = !empty($meta['textarea']) ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
            $bundled_value = $is_custom_locale ? '' : (string) ($meta[$locale] ?? $meta['default']);

            if ($value === '' || (!$is_custom_locale && $value === $bundled_value)) {
                unset($custom[$group][$locale][$key], $manual[$group][$locale][$key]);
                continue;
            }

            // Manual edits intentionally override the frozen/bundled registry value.
            // The manual marker protects this value from automatic translation
            // maintenance cleanups until the field is cleared or reset to bundled.
            $custom[$group][$locale][$key] = $value;
            $manual[$group][$locale][$key] = 1;
        }

        if (empty($custom[$group][$locale])) {
            unset($custom[$group][$locale]);
        }
        if (empty($custom[$group])) {
            unset($custom[$group]);
        }
        if (empty($manual[$group][$locale])) {
            unset($manual[$group][$locale]);
        }
        if (empty($manual[$group])) {
            unset($manual[$group]);
        }

        update_option(self::OPTION_NAME, $custom, false);
        update_option(self::MANUAL_OVERRIDE_OPTION_NAME, $manual, false);
    }

    /**
     * Return keys that currently have protected manual overrides for a locale.
     *
     * @return array<string, bool>
     */
    public function manual_override_keys(string $group, string $locale): array
    {
        $group = sanitize_key($group);
        $locale = $this->normalize_locale($locale);
        $manual = $this->manual_overrides();
        $stored = $manual[$group][$locale] ?? [];
        if (!is_array($stored)) {
            return [];
        }

        $keys = [];
        foreach ($stored as $key => $enabled) {
            if (!empty($enabled)) {
                $keys[(string) $key] = true;
            }
        }

        return $keys;
    }

    public function save_group_locale(string $group, string $locale): void
    {
        $group = sanitize_key($group);
        $groups = TranslationRegistry::groups();
        if (!isset($groups[$group])) {
            return;
        }

        $locale = $this->normalize_locale($locale);
        $languages = TranslationRegistry::languages_for_group($group);
        if (!isset($languages[$locale])) {
            return;
        }

        $locales = $this->context_locales();
        $locales[$group] = $locale;
        update_option(self::LOCALE_OPTION_NAME, $locales, false);

        $sources = get_option(self::LOCALE_SOURCE_OPTION_NAME, []);
        $sources = is_array($sources) ? $sources : [];
        $sources[$group] = 'manual';
        update_option(self::LOCALE_SOURCE_OPTION_NAME, $sources, false);
    }


    public function add_custom_language(string $locale, string $label): string
    {
        $locale = $this->normalize_custom_locale_code($locale);
        $label = trim(sanitize_text_field($label));

        if ($locale === '' || $label === '') {
            return '';
        }

        $languages = TranslationRegistry::languages();
        if (isset($languages[$locale])) {
            return $locale;
        }

        $custom_languages = get_option(self::CUSTOM_LANGUAGE_OPTION_NAME, []);
        $custom_languages = is_array($custom_languages) ? $custom_languages : [];
        $custom_languages[$locale] = $label;
        update_option(self::CUSTOM_LANGUAGE_OPTION_NAME, $custom_languages, false);

        $custom = $this->all_custom();
        foreach (TranslationRegistry::groups() as $group => $_group_label) {
            $custom[$group] = $custom[$group] ?? [];
            $custom[$group][$locale] = $custom[$group][$locale] ?? [];
            foreach (TranslationRegistry::strings_for_group((string) $group) as $key => $_meta) {
                if (!isset($custom[$group][$locale][$key])) {
                    $custom[$group][$locale][$key] = '';
                }
            }
        }
        update_option(self::OPTION_NAME, $custom, false);

        return $locale;
    }

    public function is_custom_locale(string $locale): bool
    {
        $locale = $this->normalize_custom_locale_code($locale);
        $custom_languages = get_option(self::CUSTOM_LANGUAGE_OPTION_NAME, []);
        return is_array($custom_languages) && isset($custom_languages[$locale]);
    }

    public function locale_for_group(string $group): string
    {
        $group = sanitize_key($group);
        $locales = $this->context_locales();
        return $this->normalize_locale((string) ($locales[$group] ?? TranslationRegistry::default_locale()));
    }

    public function context_locales(): array
    {
        $stored = get_option(self::LOCALE_OPTION_NAME, []);
        $stored = is_array($stored) ? $stored : [];

        return array_merge($this->wordpress_default_context_locales(), $stored);
    }

    public function wordpress_default_context_locales(): array
    {
        $admin_locale = TranslationRegistry::default_locale();
        if (function_exists('get_user_locale')) {
            $admin_locale = $this->normalize_locale((string) get_user_locale());
        } elseif (function_exists('determine_locale')) {
            $admin_locale = $this->normalize_locale((string) determine_locale());
        }

        $site_locale = TranslationRegistry::default_locale();
        if (function_exists('get_locale')) {
            $site_locale = $this->normalize_locale((string) get_locale());
        } elseif (function_exists('determine_locale')) {
            $site_locale = $this->normalize_locale((string) determine_locale());
        }

        return [
            'frontend' => $site_locale,
            'emails' => $site_locale,
        ];
    }

    public function initialize_context_locales_from_wordpress(bool $overwrite_empty_only = true): void
    {
        $this->sync_context_locales_from_wordpress(!$overwrite_empty_only);
    }

    /**
     * Synchronize Slotera's translation context languages with WordPress.
     *
     * Existing non-English/manual choices are preserved. Empty values, old
     * default English values and previously auto-synced values follow the
     * current WordPress admin/site locales. This fixes upgrades from older
     * Slotera versions where en_US was persisted before WordPress locale
     * awareness existed.
     */
    public function sync_context_locales_from_wordpress(bool $force = false): void
    {
        $stored = get_option(self::LOCALE_OPTION_NAME, []);
        $stored = is_array($stored) ? $stored : [];
        $sources = get_option(self::LOCALE_SOURCE_OPTION_NAME, []);
        $sources = is_array($sources) ? $sources : [];
        $defaults = $this->wordpress_default_context_locales();
        $changed = false;
        $sources_changed = false;

        foreach ($defaults as $group => $locale) {
            $current = isset($stored[$group]) ? $this->normalize_locale((string) $stored[$group]) : '';
            $source = isset($sources[$group]) ? (string) $sources[$group] : '';
            $is_auto = $source === '' || $source === 'wordpress';
            $is_old_default = $current === '' || $current === TranslationRegistry::default_locale();

            if ($force || ($is_auto && ($is_old_default || $source === 'wordpress'))) {
                if ($current !== $locale) {
                    $stored[$group] = $locale;
                    $changed = true;
                }
                if (($sources[$group] ?? '') !== 'wordpress') {
                    $sources[$group] = 'wordpress';
                    $sources_changed = true;
                }
            }
        }

        if ($changed) {
            update_option(self::LOCALE_OPTION_NAME, $stored, false);
        }

        if ($sources_changed) {
            update_option(self::LOCALE_SOURCE_OPTION_NAME, $sources, false);
        }
    }

    public function values_for_locale(string $group, string $locale): array
    {
        $group = sanitize_key($group);
        $locale = $this->sanitize_locale_for_group($group, $this->normalize_locale($locale));
        $custom = $this->all_custom();
        $manual = $this->manual_overrides();
        $values = [];
        $is_custom_locale = $this->is_custom_locale($locale);
        foreach (TranslationRegistry::strings_for_group($group) as $key => $meta) {
            $has_manual_override = !empty($manual[$group][$locale][$key]);
            $allow_custom_override = $has_manual_override || !in_array($group, ['admin', 'emails'], true) || $locale !== TranslationRegistry::default_locale();
            if ($group === 'frontend' && in_array($locale, ['ru_RU', 'ru'], true) && !$has_manual_override) {
                $allow_custom_override = false;
            }
            if ($allow_custom_override && isset($custom[$group][$locale][$key])) {
                $values[$key] = (string) $custom[$group][$locale][$key];
                continue;
            }
            if (!$has_manual_override && $allow_custom_override && isset($custom[$locale][$key])) {
                $values[$key] = (string) $custom[$locale][$key];
                continue;
            }
            $values[$key] = $is_custom_locale ? '' : (string) ($meta[$locale] ?? $meta['default']);
        }
        return $values;
    }

    private function all_custom(): array
    {
        $stored = get_option(self::OPTION_NAME, []);
        return is_array($stored) ? $stored : [];
    }

    private function manual_overrides(): array
    {
        $stored = get_option(self::MANUAL_OVERRIDE_OPTION_NAME, []);
        return is_array($stored) ? $stored : [];
    }


    private function looks_like_bad_locale_override(string $value, string $locale): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (preg_match('/^\[(HI|KO|JA|ZH)\]\s*/u', $value)) {
            return true;
        }

        if ($this->looks_like_mixed_language_fallback($value, $locale)) {
            return true;
        }

        $known_bad_fragments = [
            'Доступное время for',
            'Доступные даты this',
            'На %2$s доступно %1$d вариантов времени.',
            'Доступно %1$d вариантов времени на %2$s.',
            'Для %2$s доступно времён: %1$d.',
            'Only %d spots осталось',
            'Only 1 spot left',
            'ваш имя',
            'Enter the email',
            'Sign in with a secure',
            'This Ссылка для входа',
            'secure Ссылка для входа',
            'Ваше бронирование history',
            'upcoming бронирования',
            'we will отправить',
            'please запрос',
            'The Ссылка для входа expires',
            'ваш аккаунт session stays',
            'stays активный',
            '14 дни',
            '30 минуты',
            'вы are signed in',
            'вы have been logged out',
            'View детали',
            'отменить this бронирование',
            'is недействительно or истекло',
            'a новый one',
            'Vara nyt',
        ];
        foreach ($known_bad_fragments as $fragment) {
            if ((function_exists('mb_stripos') ? mb_stripos($value, $fragment) : stripos($value, $fragment)) !== false) {
                return true;
            }
        }

        return false;
    }


    private function looks_like_mixed_language_fallback(string $value, string $locale): bool
    {
        if ($locale === TranslationRegistry::default_locale()) {
            return false;
        }

        $english_fragments = ['enter', 'email', 'used', 'send', 'secure', 'link', 'login', 'sign', 'signed', 'view', 'details', 'history', 'manage', 'upcoming', 'bookings', 'your', 'you', 'are', 'have', 'been', 'logged', 'out', 'we', 'will', 'please', 'request', 'new', 'one', 'choose', 'booking', 'cancel', 'payment', 'available', 'loading', 'selected', 'option', 'date', 'time', 'month', 'this', 'for', 'try', 'contact', 'method', 'now', 'unavailable', 'failed', 'successful', 'redirecting', 'additional', 'notes', 'wishes', 'tell', 'special', 'requests', 'weekend', 'offer', 'expires', 'expired', 'invalid', 'minutes', 'account', 'session', 'stays', 'active', 'days', 'after', 'action', 'confirmation', 'error', 'refresh', 'page', 'back', 'all', 'past', 'no', 'open', 'my', 'price', 'on', 'package', 'information', 'included', 'total', 'show', 'rooms', 'previous', 'next', 'services', 'apply', 'coupon', 'pay', 'full', 'prepay', 'content', 'area', 'could', 'not', 'load', 'scheduled', 'events', 'preferred', 'both', 'first', 'checking', 'check', 'applied', 'complete', 'steps', 'required', 'fields', 'close', 'image', 'preview', 'confirmed', 'deposit', 'slots'];
        $lower = strtolower($value);
        $hits = 0;
        $hit_words = [];
        foreach ($english_fragments as $fragment) {
            if (preg_match('/\b' . preg_quote($fragment, '/') . '\b/u', $lower)) {
                $hits++;
                $hit_words[] = $fragment;
            }
        }

        if ($hits >= 2) {
            return true;
        }

        // Russian legacy overrides were created by replacing individual words
        // inside English sentences (for example "вы are signed in" or
        // "View детали"). A single high-signal English UI word next to
        // Cyrillic text is enough to reject that override and fall back to the
        // bundled full-sentence Russian translation.
        if (in_array($locale, ['ru_RU', 'ru'], true) && preg_match('/[А-Яа-яЁё]/u', $value)) {
            $allowed_singletons = ['email', 'sms', 'google', 'whatsapp', 'url', 'api'];
            foreach ($hit_words as $word) {
                if (!in_array($word, $allowed_singletons, true)) {
                    return true;
                }
            }
        }

        if ($locale === 'bg_BG' && preg_match('/[А-Яа-я]/u', $value) && $hits >= 1) {
            return true;
        }

        return false;
    }



    public function cleanup_admin_email_builtin_i18n(): void
    {
        $custom = $this->all_custom();
        $manual = $this->manual_overrides();
        $custom_languages = get_option(self::CUSTOM_LANGUAGE_OPTION_NAME, []);
        $custom_languages = is_array($custom_languages) ? $custom_languages : [];
        $custom_codes = array_map(fn($code) => $this->normalize_custom_locale_code((string) $code), array_keys($custom_languages));
        $custom_code_lookup = array_fill_keys($custom_codes, true);
        $changed = false;

        foreach (['admin', 'emails'] as $group) {
            if (!empty($custom[$group]) && is_array($custom[$group])) {
                foreach (array_keys($custom[$group]) as $locale) {
                    $normalized = $this->normalize_custom_locale_code((string) $locale);
                    if (!empty($manual[$group][$locale])) {
                        continue;
                    }
                    if ($normalized === TranslationRegistry::default_locale() || !isset($custom_code_lookup[$normalized])) {
                        unset($custom[$group][$locale]);
                        $changed = true;
                    }
                }
                if (empty($custom[$group])) {
                    unset($custom[$group]);
                }
            }
        }

        // Remove legacy flat overrides for admin/email keys from bundled/default locales.
        foreach (array_keys($custom) as $locale) {
            if (!is_array($custom[$locale] ?? null) || in_array((string) $locale, ['admin', 'frontend', 'emails'], true)) {
                continue;
            }
            $normalized = $this->normalize_custom_locale_code((string) $locale);
            if (isset($custom_code_lookup[$normalized])) {
                continue;
            }
            foreach (array_keys($custom[$locale]) as $key) {
                if (strpos((string) $key, 'admin.') === 0 || strpos((string) $key, 'emails.') === 0) {
                    unset($custom[$locale][$key]);
                    $changed = true;
                }
            }
            if (empty($custom[$locale])) {
                unset($custom[$locale]);
            }
        }

        if ($changed) {
            update_option(self::OPTION_NAME, $custom, false);
        }

        $locales = $this->context_locales();
        $locale_changed = false;
        foreach (['admin', 'emails'] as $group) {
            $current = (string) ($locales[$group] ?? TranslationRegistry::default_locale());
            $sanitized = $this->sanitize_locale_for_group($group, $current);
            if ($current !== $sanitized) {
                $locales[$group] = $sanitized;
                $locale_changed = true;
            }
        }
        if ($locale_changed) {
            update_option(self::LOCALE_OPTION_NAME, $locales, false);
        }
    }

    private function sanitize_locale_for_group(string $group, string $locale): string
    {
        // All contexts can now use the bundled language list. Missing registry
        // strings still fall back to WordPress gettext/default English safely.
        $group = sanitize_key($group);
        return $this->normalize_locale($locale);
    }

    private function normalize_custom_locale_code(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));
        $locale = preg_replace('/[^A-Za-z0-9_]/', '', $locale);
        return $locale !== null ? $locale : '';
    }

    private function normalize_locale(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));
        $languages = TranslationRegistry::all_languages();
        $aliases = [
            'bg' => 'bg_BG',
            'bg_bg' => 'bg_BG',
            'no' => 'no_NO',
            'nb' => 'no_NO',
            'nb_no' => 'no_NO',
        ];
        $alias_key = strtolower($locale);
        if (isset($aliases[$alias_key])) {
            return $aliases[$alias_key];
        }

        if (isset($languages[$locale])) {
            return $locale;
        }

        $custom_locale = $this->normalize_custom_locale_code($locale);
        if ($custom_locale !== '' && isset($languages[$custom_locale])) {
            return $custom_locale;
        }

        $prefix = strtolower(substr($locale, 0, 2));
        foreach (array_keys($languages) as $code) {
            if (strtolower(substr($code, 0, 2)) === $prefix) {
                return $code;
            }
        }

        return TranslationRegistry::default_locale();
    }
}
