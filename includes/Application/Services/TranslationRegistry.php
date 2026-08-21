<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Application\Services\Translations\AdminTranslationStrings;
use Slotera\Application\Services\Translations\EmailTranslationStrings;
use Slotera\Application\Services\Translations\FrontendTranslationStrings;
use Slotera\Application\Services\Translations\TranslationStringOrder;

if (!defined('ABSPATH')) {
    exit;
}

final class TranslationRegistry
{

    /** @var array<string,array<string,array<string,mixed>>> */
    private static array $group_strings_cache = [];

    /** @var array<string,array<string,mixed>>|null */
    private static ?array $all_strings_cache = null;

    /** @var array<string,string>|null */
    private static ?array $key_group_index = null;

    /** @var array<string,string> */
    private static array $key_default_index = [];

    /** @var array<string,array<string,string>> */
    private static array $default_key_indexes = [];

    /** @var array<string,bool> */
    private static array $indexed_groups = [];

    public static function all_languages(): array
    {
        $languages = [
            'en_US' => 'English',
            'bg_BG' => 'Български',
            'hr' => 'Hrvatski',
            'cs_CZ' => 'Čeština',
            'da_DK' => 'Dansk',
            'nl_NL' => 'Nederlands',
            'et' => 'Eesti',
            'fi' => 'Suomi',
            'fr_FR' => 'Français',
            'de_DE' => 'Deutsch',
            'el' => 'Ελληνικά',
            'hu_HU' => 'Magyar',
            'ga_IE' => 'Gaeilge',
            'is_IS' => 'Íslenska',
            'it_IT' => 'Italiano',
            'lv' => 'Latviešu',
            'lt_LT' => 'Lietuvių',
            'mt_MT' => 'Malti',
            'no_NO' => 'Norsk',
            'pl_PL' => 'Polski',
            'pt_PT' => 'Português',
            'pt_BR' => 'Português (Brasil)',
            'ro_RO' => 'Română',
            'sk_SK' => 'Slovenčina',
            'sl_SI' => 'Slovenščina',
            'es_ES' => 'Español',
            'sv_SE' => 'Svenska',
            'ru_RU' => 'Русский',
        ];

        $custom = get_option('sltr_custom_languages', []);
        if (is_array($custom)) {
            foreach ($custom as $code => $label) {
                $code = self::normalize_custom_locale_code((string) $code);
                $label = is_string($label) ? trim($label) : '';
                if ($code !== '' && $label !== '') {
                    $languages[$code] = $label;
                }
            }
        }

        return $languages;
    }

    public static function languages(): array
    {
        return array_diff_key(self::all_languages(), self::hidden_languages());
    }

    public static function hidden_languages(): array
    {
        return [
            'ga_IE' => 'Gaeilge',
            'is_IS' => 'Íslenska',
            'mt_MT' => 'Malti',
        ];
    }

    public static function is_visible_locale(string $locale): bool
    {
        $locale = str_replace('-', '_', trim($locale));
        $aliases = [
            'et_EE' => 'et',
            'fi_FI' => 'fi',
            'el_GR' => 'el',
            'hr_HR' => 'hr',
            'lv_LV' => 'lv',
            'nb_NO' => 'no_NO',
            'nb' => 'no_NO',
        ];
        $locale = $aliases[$locale] ?? $locale;

        return isset(self::languages()[$locale]);
    }

    private static function normalize_custom_locale_code(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));
        $locale = preg_replace('/[^A-Za-z0-9_]/', '', $locale);
        return $locale !== null ? $locale : '';
    }

    public static function languages_for_group(string $group): array
    {
        $group = sanitize_key($group);
        return self::languages();
    }

    public static function default_locale(): string
    {
        return 'en_US';
    }

    public static function groups(): array
    {
        return [
            'frontend' => 'Frontend UI',
            'emails' => 'Email settings',
        ];
    }

    public static function strings(): array
    {
        if (self::$all_strings_cache !== null) {
            return self::$all_strings_cache;
        }

        $strings = [];
        $catalogs = [];
        foreach (self::key_group_index() as $key => $group) {
            if (!isset($catalogs[$group])) {
                $catalogs[$group] = self::strings_for_group($group);
            }
            if (isset($catalogs[$group][$key])) {
                $strings[$key] = $catalogs[$group][$key];
            }
        }

        self::$all_strings_cache = $strings;
        return self::$all_strings_cache;
    }

    /**
     * Reserved for offline translation QA tooling. Do not call this from strings().
     * Runtime translation lookup must stay cheap and deterministic.
     */
    private static function sanitize_catalog(array $strings): array
    {
        return $strings;
    }

    public static function strings_for_group(string $group): array
    {
        $group = sanitize_key($group);
        if (array_key_exists($group, self::$group_strings_cache)) {
            return self::$group_strings_cache[$group];
        }

        if ($group === 'admin') {
            $strings = AdminTranslationStrings::strings();
        } elseif ($group === 'frontend') {
            $strings = FrontendTranslationStrings::strings();
        } elseif ($group === 'emails') {
            $strings = EmailTranslationStrings::strings();
        } else {
            $strings = [];
        }

        self::$group_strings_cache[$group] = $strings;
        self::index_group($group, $strings);
        return self::$group_strings_cache[$group];
    }

    public static function group_for(string $key): string
    {
        return (string) (self::key_group_index()[$key] ?? '');
    }

    /** @return array<string,mixed>|null */
    public static function meta_for(string $key): ?array
    {
        $group = self::group_for($key);
        if ($group === '') { return null; }
        $strings = self::strings_for_group($group);
        return isset($strings[$key]) && is_array($strings[$key]) ? $strings[$key] : null;
    }

    public static function default_for(string $key): string
    {
        $group = self::group_for($key);
        if ($group === '') { return $key; }
        self::strings_for_group($group);
        return array_key_exists($key, self::$key_default_index) ? self::$key_default_index[$key] : $key;
    }

    public static function key_for_default(string $default, ?string $group = null): ?string
    {
        if ($group !== null) {
            $group = sanitize_key($group);
            self::strings_for_group($group);
            return self::$default_key_indexes[$group][$default] ?? null;
        }

        if (!isset(self::$default_key_indexes['*'])) {
            $index = [];
            // Preserve the canonical registry order when identical defaults
            // exist in more than one group.
            foreach (self::key_group_index() as $key => $key_group) {
                self::strings_for_group($key_group);
                $value = self::$key_default_index[$key] ?? null;
                if ($value !== null && !array_key_exists($value, $index)) {
                    $index[$value] = $key;
                }
            }
            self::$default_key_indexes['*'] = $index;
        }

        return self::$default_key_indexes['*'][$default] ?? null;
    }

    /** @return array<string,string> */
    private static function key_group_index(): array
    {
        if (self::$key_group_index === null) {
            self::$key_group_index = TranslationStringOrder::key_to_group();
        }
        return self::$key_group_index;
    }

    /** @param array<string,array<string,mixed>> $strings */
    private static function index_group(string $group, array $strings): void
    {
        if (!empty(self::$indexed_groups[$group])) { return; }

        $default_index = [];
        foreach (self::key_group_index() as $key => $key_group) {
            if ($key_group !== $group || !isset($strings[$key]) || !is_array($strings[$key])) {
                continue;
            }
            $default = (string) ($strings[$key]['default'] ?? $key);
            self::$key_default_index[$key] = $default;
            if (!array_key_exists($default, $default_index)) {
                $default_index[$default] = $key;
            }
        }

        self::$default_key_indexes[$group] = $default_index;
        self::$indexed_groups[$group] = true;
    }
}
