<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\TranslationRegistry;
use Slotera\Application\Services\TranslationService;
use Slotera\Application\Services\TranslationQualityReportService;
use Slotera\Application\Services\TranslationQualityLintService;
use Slotera\Admin\Support\TranslationQualityReportExporter;
use Slotera\Application\Services\EmailTemplateRegistry;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class TranslationsPage
{
    public function render(): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_SETTINGS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        $service = new TranslationService();
        $qualityService = new TranslationQualityReportService();
        $translation_report = $qualityService->report();
        $translation_checks = $qualityService->checks($translation_report);

        $export = isset($_GET['sltr_translation_export']) ? sanitize_key((string) $_GET['sltr_translation_export']) : '';
        if (in_array($export, ['json', 'csv'], true)) {
            check_admin_referer('sltr_translation_export');
            (new TranslationQualityReportExporter())->output($export, $translation_report);
        }
        $groups = TranslationRegistry::groups();
        $groups['email_templates'] = 'Email Templates';
        $context_locales = $service->context_locales();
        $active_group = isset($_GET['group']) ? sanitize_key(wp_unslash((string) $_GET['group'])) : 'frontend';
        if (!isset($groups[$active_group])) {
            $active_group = 'frontend';
        }
        if ($active_group === 'email_templates') {
            $settings = (new SettingsRepository())->all();
            $scenarios = EmailTemplateRegistry::scenarios();
            $placeholders = EmailTemplateRegistry::placeholders();
            $email_languages = TranslationRegistry::languages_for_group('frontend');
            $email_default_locale = $service->locale_for_group('emails');
            if (!isset($email_languages[$email_default_locale])) {
                $email_default_locale = TranslationRegistry::default_locale();
            }
            $scenario_key = isset($_GET['scenario']) ? sanitize_key(wp_unslash((string) $_GET['scenario'])) : '';
            if ($scenario_key !== '' && isset($scenarios[$scenario_key])) {
                $scenario = $scenarios[$scenario_key];
                require SLTR_PLUGIN_DIR . 'includes/Admin/Views/emails-edit.php';
                return;
            }
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/translations.php';
            return;
        }

        $languages = TranslationRegistry::languages_for_group($active_group);

        $locale = isset($_GET['locale']) ? sanitize_text_field(wp_unslash((string) $_GET['locale'])) : $service->locale_for_group($active_group);
        $locale = str_replace('-', '_', $locale);
        if (!isset($languages[$locale])) {
            $locale = TranslationRegistry::default_locale();
        }

        $strings = TranslationRegistry::strings_for_group($active_group);
        $values = $service->values_for_locale($active_group, $locale);
        $manual_override_keys = $service->manual_override_keys($active_group, $locale);
        $workspace_filter = isset($_GET['workspace_filter']) ? sanitize_key(wp_unslash((string) $_GET['workspace_filter'])) : 'all';
        if (!in_array($workspace_filter, ['all', 'missing', 'translated', 'duplicates', 'quality', 'overrides', 'unsaved'], true)) {
            $workspace_filter = 'all';
        }

        $translation_sections = [
            'all' => __('All strings', 'slotera-booking'),
            'booking' => __('Booking flow', 'slotera-booking'),
            'payments' => __('Payments', 'slotera-booking'),
        ];
        $active_section = isset($_GET['translation_section']) ? sanitize_key(wp_unslash((string) $_GET['translation_section'])) : 'all';
        if (!isset($translation_sections[$active_section])) {
            $active_section = 'all';
        }

        $section_matcher = static function (array $meta, string $key, string $section): bool {
            if ($section === 'all') {
                return true;
            }

            $text = strtolower($key . ' ' . (string) ($meta['label'] ?? '') . ' ' . (string) ($meta['default'] ?? '') . ' ' . (string) ($meta['group'] ?? ''));
            if ($section === 'payments') {
                return strpos($text, 'payment') !== false || strpos($text, 'pay ') !== false || strpos($text, 'paid') !== false || strpos($text, 'stripe') !== false || strpos($text, 'paypal') !== false || strpos($text, 'arrival') !== false || strpos($text, 'deposit') !== false || strpos($text, 'coupon') !== false;
            }
            if ($section === 'booking') {
                return strpos($text, 'booking') !== false || strpos($text, 'book ') !== false || strpos($text, 'date') !== false || strpos($text, 'time') !== false || strpos($text, 'slot') !== false || strpos($text, 'availability') !== false || strpos($text, 'customer') !== false || strpos($text, 'client') !== false || strpos($text, 'name') !== false || strpos($text, 'phone') !== false || strpos($text, 'email') !== false;
            }

            return true;
        };

        if ($active_section !== 'all') {
            $strings = array_filter($strings, static function (array $meta, string $key) use ($active_section, $section_matcher): bool {
                return $section_matcher($meta, $key, $active_section);
            }, ARRAY_FILTER_USE_BOTH);
        }

        $total_strings = count(TranslationRegistry::strings_for_group($active_group));
        $section_total = count($strings);
        $all_strings = TranslationRegistry::strings();
        $default_counts = [];
        foreach ($all_strings as $dup_key => $dup_meta) {
            $default = trim((string) ($dup_meta['default'] ?? ''));
            if ($default === '') {
                continue;
            }
            $default_counts[$default][] = $dup_key;
        }
        $duplicate_defaults = array_filter($default_counts, static fn(array $keys): bool => count($keys) > 1);
        $duplicate_keys = [];
        foreach ($duplicate_defaults as $default => $keys) {
            foreach ($keys as $dup_key) {
                $duplicate_keys[$dup_key] = [
                    'default' => $default,
                    'keys' => $keys,
                    'count' => count($keys),
                ];
            }
        }

        $search = isset($_GET['translation_search']) ? sanitize_text_field(wp_unslash((string) $_GET['translation_search'])) : '';
        if ($search !== '') {
            $needle = function_exists('mb_strtolower') ? mb_strtolower($search) : strtolower($search);
            $strings = array_filter($strings, static function (array $meta, string $key) use ($needle, $values): bool {
                $haystack = implode(' ', [
                    $key,
                    (string) ($meta['label'] ?? ''),
                    (string) ($meta['default'] ?? ''),
                    (string) ($values[$key] ?? ''),
                ]);
                $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack) : strtolower($haystack);
                return strpos($haystack, $needle) !== false;
            }, ARRAY_FILTER_USE_BOTH);
        }


        $quality_lint = new TranslationQualityLintService();
        $quality_lint_results = [];
        foreach ($strings as $quality_key => $quality_meta) {
            $quality_result = $quality_lint->analyze(
                (string) ($quality_meta['default'] ?? ''),
                preg_replace('~\{[A-Za-z0-9_.:-]+\}|https?://\S+|\b\S+@\S+\b~u', ' ', (string) ($values[$quality_key] ?? '')) ?? (string) ($values[$quality_key] ?? ''),
                $locale
            );
            if (!$quality_result['valid']) {
                $quality_lint_results[$quality_key] = $quality_result;
            }
        }

        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/translations.php';
    }
}
