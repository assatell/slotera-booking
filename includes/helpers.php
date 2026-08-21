<?php

declare(strict_types=1);

use Slotera\Application\Services\TranslationService;
use Slotera\Application\Services\TranslationRegistry;

if (!defined('ABSPATH')) {
    exit;
}



if (!function_exists('sltr_view_file_exists')) {
    /**
     * Guard admin/frontend view partial includes against broken release paths.
     *
     * This function is intentionally tiny and scope-neutral: callers still run
     * require/include in their own scope, but only after the path has been
     * verified. A missing view partial should create an admin-visible notice and
     * a PHP error_log entry instead of a fatal error that hides the whole screen.
     */
    function sltr_view_file_exists(string $file): bool
    {
        $normalized = str_replace('\\', '/', $file);
        $root = defined('SLTR_PLUGIN_DIR') ? rtrim(str_replace('\\', '/', SLTR_PLUGIN_DIR), '/') . '/' : '';

        if ($file === '' || !is_file($file)) {
            $message = sprintf('Slotera view include missing: %s', $normalized);
            if (function_exists('error_log')) {
                error_log($message);
            }
            if (function_exists('is_admin') && is_admin() && function_exists('esc_html')) {
                echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
            }
            return false;
        }

        $real = realpath($file);
        if ($real === false) {
            return false;
        }
        $realNormalized = str_replace('\\', '/', $real);
        $rootBoundary = rtrim($root, '/') . '/';
        if ($root !== '' && !str_starts_with($realNormalized, $rootBoundary)) {
            $message = sprintf('Slotera blocked view include outside plugin root: %s', $realNormalized);
            if (function_exists('error_log')) {
                error_log($message);
            }
            if (function_exists('is_admin') && is_admin() && function_exists('esc_html')) {
                echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
            }
            return false;
        }

        return true;
    }
}

if (!function_exists('sltr__')) {
    function sltr__(string $key, ?string $locale = null): string
    {
        static $service = null;
        if ($service === null) {
            $service = new TranslationService();
        }
        return $service->get($key, $locale);
    }
}

if (!function_exists('sltr_t')) {
    function sltr_t(string $default, string $group = 'frontend', ?string $locale = null): string
    {
        static $service = null;
        if ($service === null) {
            $service = new TranslationService();
        }
        return $service->translate_default($default, $group, $locale) ?? $default;
    }
}


if (!function_exists('sltr_account_t')) {
    /** Translate Customer Account and magic-link UI strictly through canonical frontend keys. */
    function sltr_account_t(string $key, ?string $locale = null): string
    {
        $key = str_starts_with($key, 'frontend.') ? $key : 'frontend.' . ltrim($key, '.');
        $value = sltr__($key, $locale);
        return $value !== '' ? $value : $key;
    }
}

if (!function_exists('sltr_esc_html_e')) {
    function sltr_esc_html_e(string $key, ?string $locale = null): void
    {
        echo esc_html(sltr__($key, $locale));
    }
}

if (!function_exists('sltr_esc_attr_e')) {
    function sltr_esc_attr_e(string $key, ?string $locale = null): void
    {
        echo esc_attr(sltr__($key, $locale));
    }
}



if (!function_exists('sltr_booking_status_label')) {
    function sltr_booking_status_label(string $status, string $group = 'frontend', ?string $locale = null): string
    {
        $status = sanitize_key($status);
        $map = [
            'created' => 'booking.status.created',
            'confirmed' => 'booking.status.confirmed',
            'pending' => 'booking.status.pending',
            'pending_payment' => 'booking.status.pending_payment',
            'pending_confirmation' => 'booking.status.pending_confirmation',
            'cancelled' => 'booking.status.cancelled',
            'canceled' => 'booking.status.cancelled',
            'completed' => 'booking.status.completed',
            'failed' => 'booking.status.failed',
        ];
        if (isset($map[$status])) {
            return sltr__((sanitize_key($group) === 'emails' ? 'emails.' : 'frontend.') . $map[$status], $locale);
        }
        return ucwords(str_replace('_', ' ', $status));
    }
}

if (!function_exists('sltr_payment_status_label')) {
    function sltr_payment_status_label(string $status, string $group = 'frontend', ?string $locale = null): string
    {
        $status = sanitize_key($status);
        $map = [
            'unpaid' => 'payment.status.unpaid',
            'pending' => 'payment.status.pending',
            'processing' => 'payment.status.processing',
            'paid' => 'payment.status.paid',
            'partial' => 'payment.status.partial',
            'partially_paid' => 'payment.status.partial',
            'refunded' => 'payment.status.refunded',
            'failed' => 'payment.status.failed',
            'cancelled' => 'payment.status.cancelled',
            'canceled' => 'payment.status.cancelled',
            'authorized' => 'payment.status.authorized',
            'requires_action' => 'payment.status.requires_action',
            'pay_on_arrival' => 'payment.status.pay_on_arrival',
        ];
        if (isset($map[$status])) {
            return sltr__((sanitize_key($group) === 'emails' ? 'emails.' : 'frontend.') . $map[$status], $locale);
        }
        return ucwords(str_replace('_', ' ', $status));
    }
}



if (!function_exists('sltr_places_left_label')) {
    /** Return a locale-aware availability label using WordPress plural rules. */
    function sltr_places_left_label(int $count, bool $low = false): string
    {
        $count = max(0, $count);
        if ($count <= 0) {
            return sltr_t('Sold out');
        }
        if ($count === 1) {
            $template = sltr__('frontend.1_place_left');
            if ($template === '' || $template === '1 place left') {
                $template = _n('%d place left', '%d places left', 1, 'slotera-booking');
                return sprintf($template, 1);
            }
            return $template;
        }

        $key = $low ? 'frontend.only_value_spots_left' : 'frontend.value_places_left';
        $template = sltr__($key);
        if ($template === '' || $template === ($low ? 'Only %d spots left' : '%d places left')) {
            $singular = $low ? 'Only %d place left' : '%d place left';
            $plural = $low ? 'Only %d places left' : '%d places left';
            $template = _n($singular, $plural, $count, 'slotera-booking');
        }

        return sprintf($template, $count);
    }
}

if (!function_exists('sltr_activity_event_label')) {
    /**
     * Translate customer-facing activity log event keys into readable labels.
     * Activity events are stored as machine keys, for example booking_created.
     */
    function sltr_activity_event_label(string $event, string $group = 'frontend', ?string $locale = null): string
    {
        $raw_event = trim($event);
        $raw_key = strtolower($raw_event);
        // Activity rows may contain either a short machine event (booking_created)
        // or a full runtime translation key (frontend.booking_created). Preserve
        // the known full key before normalizing punctuation so it can never leak
        // to the customer as a raw label.
        $direct_keys = [
            'frontend.booking_created', 'frontend.booking_confirmed',
            'frontend.booking_cancelled', 'frontend.booking_completed',
            'frontend.booking_rescheduled', 'frontend.service_changed',
            'frontend.payment_pending', 'frontend.payment_received',
            'frontend.payment_completed', 'frontend.payment_partially_paid',
            'frontend.payment_failed', 'frontend.payment_refunded',
            'frontend.payment_marked_as_unpaid', 'frontend.invoice_created',
            'frontend.customer_login', 'frontend.email_sent', 'frontend.email_failed',
        ];
        $direct_key = in_array($raw_key, $direct_keys, true) ? $raw_key : '';
        $event = sanitize_key(str_replace([' ', '-', '.', ':'], '_', $raw_key));
        $event = preg_replace('/_+/', '_', $event) ?: sanitize_key($raw_event);
        $event = trim($event, '_');

        $map = [
            'booking_created' => 'frontend.booking_created',
            'simple_booking_created' => 'frontend.booking_created',
            'frontend_booking_created' => 'frontend.booking_created',
            'date_range_booking_created' => 'frontend.booking_created',
            'bookingcreated' => 'frontend.booking_created',
            'booking_confirmed' => 'frontend.booking_confirmed',
            'booking_confirmed_by_admin' => 'frontend.booking_confirmed',
            'frontend_booking_confirmed' => 'frontend.booking_confirmed',
            'booking_cancelled' => 'frontend.booking_cancelled',
            'frontend_booking_cancelled' => 'frontend.booking_cancelled',
            'booking_canceled' => 'frontend.booking_cancelled',
            'booking_cancelled_by_customer' => 'frontend.booking_cancelled',
            'booking_canceled_by_customer' => 'frontend.booking_cancelled',
            'booking_completed' => 'frontend.booking_completed',
            'frontend_booking_completed' => 'frontend.booking_completed',
            'booking_rescheduled' => 'frontend.booking_rescheduled',
            'frontend_booking_rescheduled' => 'frontend.booking_rescheduled',
            'booking_rescheduled_by_customer' => 'frontend.booking_rescheduled',
            'booking_package_changed' => 'frontend.service_changed',
            'payment_pending' => 'frontend.payment_pending',
            'frontend_payment_pending' => 'frontend.payment_pending',
            'payment_paid' => 'frontend.payment_received',
            'payment_marked_paid' => 'frontend.payment_received',
            'frontend_payment_marked_paid' => 'frontend.payment_received',
            'payment_received' => 'frontend.payment_received',
            'frontend_payment_received' => 'frontend.payment_received',
            'payment_completed' => 'frontend.payment_completed',
            'frontend_payment_completed' => 'frontend.payment_completed',
            'payment_partial' => 'frontend.payment_partially_paid',
            'deposit_paid' => 'frontend.payment_partially_paid',
            'payment_failed' => 'frontend.payment_failed',
            'payment_initialization_failed' => 'frontend.payment_failed',
            'frontend_payment_failed' => 'frontend.payment_failed',
            'payment_refunded' => 'frontend.payment_refunded',
            'frontend_payment_refunded' => 'frontend.payment_refunded',
            'payment_unpaid' => 'frontend.payment_marked_as_unpaid',
            'payment_marked_unpaid' => 'frontend.payment_marked_as_unpaid',
            'frontend_payment_marked_unpaid' => 'frontend.payment_marked_as_unpaid',
            'invoice_created' => 'frontend.invoice_created',
            'customer_login' => 'frontend.customer_login',
            'email_sent' => 'frontend.email_sent',
            'email_failed' => 'frontend.email_failed',
        ];

        if ($direct_key !== '') {
            $key = $direct_key;
        } elseif (isset($map[$event])) {
            $key = $map[$event];
        } else {
            // Never expose namespace-like runtime keys in the customer account.
            $human_event = preg_replace('/^(frontend|admin|emails)[._-]+/i', '', $raw_event) ?: $event;
            return ucwords(str_replace(['_', '.', '-'], ' ', $human_event));
        }
        $service = new TranslationService();
        $active_locale = $locale ?: $service->locale_for_group(sanitize_key($group));
        $active_locale = str_replace('-', '_', trim((string) $active_locale));

        // Use the bundled registry value directly for customer activity labels.
        // This prevents stale English manual overrides from leaking into the
        // timeline while still respecting Slotera's selected frontend locale.
        $aliases = [
            'et_EE' => 'et', 'fi_FI' => 'fi', 'el_GR' => 'el', 'hr_HR' => 'hr',
            'lv_LV' => 'lv', 'nb_NO' => 'no_NO', 'nb' => 'no_NO', 'cs' => 'cs_CZ',
            'sk' => 'sk_SK', 'de' => 'de_DE', 'pl' => 'pl_PL', 'ru' => 'ru_RU',
        ];
        $registry_locale = $aliases[$active_locale] ?? $active_locale;
        $meta = TranslationRegistry::meta_for($key) ?? [];
        if (!empty($meta[$registry_locale])) {
            return (string) $meta[$registry_locale];
        }

        // Fallback to the normal service for custom languages and then gettext.
        $translated = $service->get($key, $active_locale);
        $default = (string) ($meta['default'] ?? ucwords(str_replace('_', ' ', $event)));
        if ($translated !== '' && $translated !== $default) {
            return $translated;
        }
        $gettext_label = translate($default, 'slotera-booking');
        return $gettext_label !== '' ? $gettext_label : $default;
    }
}

if (!function_exists('sltr_feature_hard_disabled')) {
    /**
     * MVP hard-disable switch for unfinished attack-surface-heavy modules.
     *
     * Public REST booking remains disabled until reviewed. Stripe webhooks are registered separately.
     * This is not controlled by wp_options, filters, or debug constants, so an
     * old setting, broad filter, or environment flag cannot re-enable routes,
     * admin screens, or checkout flows accidentally in production.
     */
    function sltr_feature_hard_disabled(string $feature): bool
    {
        $feature = sanitize_key($feature);
        return in_array($feature, ['public_rest_booking'], true);
    }
}

if (!function_exists('sltr_dormant_payments_sandbox_allowed')) {
    /**
     * Backward-compatible helper kept for older internal calls.
     *
     * Earlier builds allowed a local sandbox opt-in. The current MVP build is a
     * strict booking-only release: online payments and webhooks always stay off.
     */
    function sltr_dormant_payments_sandbox_allowed(): bool
    {
        return false;
    }
}

if (!function_exists('sltr_mvp_online_payments_disabled')) {
    /**
     * Partytime.ee / Slotera MVP ships without production online payments.
     */
    function sltr_mvp_online_payments_disabled(): bool
    {
        return false;
    }
}

if (!function_exists('sltr_feature_enabled')) {
    /**
     * Central feature gate for dormant modules.
     *
     * Hard-disabled features return false before reading options or filters.
     */
    function sltr_feature_enabled(string $feature): bool
    {
        $feature = sanitize_key($feature);

        if (function_exists('sltr_feature_hard_disabled') && sltr_feature_hard_disabled($feature)) {
            return false;
        }

        $defaults = [
            'payments' => true,
            'webhooks' => true,
            'public_rest_booking' => false,
        ];

        $features = get_option('sltr_features', []);
        if (!is_array($features)) {
            $features = [];
        }

        $enabled = array_key_exists($feature, $features)
            ? filter_var($features[$feature], FILTER_VALIDATE_BOOLEAN)
            : (bool) ($defaults[$feature] ?? false);

        return (bool) apply_filters('sltr_feature_enabled', $enabled, $feature);
    }
}



if (!function_exists('sltr_public_rest_booking_security_reviewed')) {
    /**
     * Public REST booking is intentionally release-gated. It must stay dormant
     * until an explicit security review confirms nonce/site-form mode, HMAC
     * mode, rate limits and replay protection for the target deployment.
     */
    function sltr_public_rest_booking_security_reviewed(): bool
    {
        $settings = get_option('sltr_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $reviewed = !empty($settings['security_public_rest_booking_security_reviewed']);

        return (bool) apply_filters('sltr_public_rest_booking_security_reviewed', $reviewed);
    }
}

if (!function_exists('sltr_booking_statuses')) {
    /**
     * Booking lifecycle statuses exposed by the current build.
     * MVP is intentionally confirmed/cancelled/completed only; pending_payment
     * appears only when the payments feature is enabled in a future release.
     *
     * @return string[]
     */
    function sltr_booking_statuses(): array
    {
        $statuses = ['confirmed', 'cancelled', 'completed'];
        if (function_exists('sltr_feature_enabled') && sltr_feature_enabled('payments')) {
            array_unshift($statuses, 'pending_payment');
        }
        return $statuses;
    }
}

if (!function_exists('sltr_active_booking_statuses')) {
    /**
     * Statuses that reserve inventory. pending_payment remains active even when
     * online payments are hidden/disabled, so existing holds cannot be overbooked.
     *
     * @return string[]
     */
    function sltr_active_booking_statuses(): array
    {
        return ['pending_payment', 'confirmed'];
    }
}

if (!function_exists('sltr_format_localized_date')) {
    /**
     * Format a stored ISO booking date for the selected Slotera locale.
     */
    function sltr_format_localized_date(string $date, ?string $locale = null): string
    {
        $date = trim($date);
        if ($date === '' || $date === '0000-00-00' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));
        if (!checkdate($month, $day, $year)) {
            return $date;
        }

        $locale = trim((string) $locale);
        if ($locale === '') {
            $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        }

        try {
            $timezone = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('UTC');
            $value = new \DateTimeImmutable($date . ' 12:00:00', $timezone);
            if (class_exists('IntlDateFormatter')) {
                $formatter = new \IntlDateFormatter(
                    str_replace('_', '-', $locale),
                    \IntlDateFormatter::LONG,
                    \IntlDateFormatter::NONE,
                    $timezone->getName()
                );
                $formatted = $formatter->format($value);
                if (is_string($formatted) && trim($formatted) !== '') {
                    return $formatted;
                }
            }

            $normalized = strtolower(str_replace('-', '_', $locale));
            if ($normalized === 'en_us') {
                return $value->format('m/d/Y');
            }
            return $value->format('d/m/Y');
        } catch (\Throwable $e) {
            return $date;
        }
    }
}

if (!function_exists('sltr_booking_display_data')) {
    /**
     * Build consistent public date/time display data for a booking.
     *
     * @return array{no_datetime:bool,start_time_only:bool,scheduled_event:bool,date:string,time:string,notice:string}
     */
    function sltr_booking_display_data(?array $booking, ?array $package = null): array
    {
        $booking = is_array($booking) ? $booking : [];
        if ($package === null) {
            $package_id = absint($booking['package_id'] ?? 0);
            if ($package_id > 0 && class_exists('Slotera\\Infrastructure\\Repositories\\PackageRepository')) {
                $loaded = (new \Slotera\Infrastructure\Repositories\PackageRepository())->get_by_id($package_id);
                $package = is_array($loaded) ? $loaded : [];
            } else {
                $package = [];
            }
        }

        $mode = sanitize_key((string) ($booking['booking_mode'] ?? $package['booking_mode'] ?? 'simple'));
        $configs = [];
        $raw = $package['mode_configs_json'] ?? '';
        if (is_array($raw)) {
            $configs = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $configs = is_array($decoded) ? $decoded : [];
        }
        $active = isset($configs[$mode]) && is_array($configs[$mode]) ? $configs[$mode] : [];
        $no_datetime = $mode === 'simple';
        $date_only_multi_day = $mode === 'fixed' && !empty($active['full_day_booking']);
        $start_time_only = $mode === 'flex' && !empty($active['display_start_time_only']);

        $valid_date = static function (string $value): string {
            $value = trim($value);
            if ($value === '' || $value === '0000-00-00' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return '';
            }
            [$year, $month, $day] = array_map('intval', explode('-', $value));
            return checkdate($month, $day, $year) ? $value : '';
        };
        $valid_time = static function (string $value): string {
            $value = trim($value);
            if ($value === '' || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value)) {
                return '';
            }
            return substr($value, 0, 5);
        };

        $start_date = $valid_date((string) ($booking['booking_date'] ?? ''));
        $end_date = $valid_date((string) ($booking['end_date'] ?? ''));
        $event_use_time = null;
        if ($mode === 'date_range_inventory' && !empty($booking['resource_id']) && class_exists('Slotera\Application\Services\DateRangeInventoryService')) {
            try {
                $event = (new \Slotera\Application\Services\DateRangeInventoryService())->find_scheduled_event($package, absint($booking['resource_id']));
                if (is_array($event)) {
                    $event_start = $valid_date((string) ($event['start_date'] ?? ''));
                    $event_end = $valid_date((string) ($event['end_date'] ?? ''));
                    if ($event_start !== '') { $start_date = $event_start; }
                    if ($event_end !== '') { $end_date = $event_end; }
                    $event_use_time = !empty($event['use_time']);
                    if ($event_use_time) {
                        $booking['start_time'] = (string) ($event['start_time'] ?? '');
                        $booking['end_time'] = (string) ($event['end_time'] ?? '');
                    } else {
                        $booking['start_time'] = '';
                        $booking['end_time'] = '';
                    }
                }
            } catch (\Throwable $e) {
                // Fall back to the persisted booking values.
            }
        }
        $frontend_locale = class_exists('Slotera\Application\Services\TranslationService')
            ? (new \Slotera\Application\Services\TranslationService())->locale_for_group('frontend')
            : (function_exists('determine_locale') ? determine_locale() : get_locale());
        $display_start_date = $start_date !== '' ? sltr_format_localized_date($start_date, $frontend_locale) : '';
        $display_end_date = $end_date !== '' ? sltr_format_localized_date($end_date, $frontend_locale) : '';
        $start_time = $valid_time((string) ($booking['start_time'] ?? ''));
        $end_time = $valid_time((string) ($booking['end_time'] ?? ''));

        $is_multi_day = $start_date !== '' && $end_date !== '' && $end_date !== $start_date;
        $is_scheduled_event = $event_use_time !== null;
        $date = $display_start_date;
        $time = '';
        if (!$no_datetime && $is_multi_day) {
            if (!$date_only_multi_day && $event_use_time !== false && $start_time !== '') {
                $date .= ' ' . $start_time;
            }
            $date .= ' → ' . $display_end_date;
            if (!$date_only_multi_day && $event_use_time !== false && $end_time !== '') {
                $date .= ' ' . $end_time;
            }
        } elseif (!$no_datetime && $is_scheduled_event) {
            // One-day Scheduled Events are easier to scan when date and time use
            // their own fields in account/booking summaries.
            if ($event_use_time === true && $start_time !== '') {
                $time = $start_time;
                if ($end_time !== '' && $end_time !== $start_time) {
                    $time .= ' – ' . $end_time;
                }
            }
        } else {
            $time = $date_only_multi_day ? '' : $start_time;
            if (!$no_datetime && !$start_time_only && $start_time !== '' && $end_time !== '' && $end_time !== $start_time) {
                $time .= ' – ' . $end_time;
            }
        }

        $notice_locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        $notice = class_exists('Slotera\Application\Services\Translations\BookingRequestTranslations')
            ? \Slotera\Application\Services\Translations\BookingRequestTranslations::notice((string) $notice_locale, 'customer')
            : __('The date and time will be agreed separately. We will contact you shortly by email or phone if a phone number was provided.', 'slotera-booking');

        return [
            'no_datetime' => $no_datetime,
            'start_time_only' => $start_time_only,
            'date_only' => $date_only_multi_day,
            'scheduled_event' => $is_scheduled_event,
            'use_time' => !$date_only_multi_day && $event_use_time !== false,
            'date' => $no_datetime ? '' : $date,
            'time' => $no_datetime ? '' : $time,
            'notice' => $notice,
        ];
    }
}
