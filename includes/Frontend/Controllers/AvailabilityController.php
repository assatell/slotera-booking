<?php

declare(strict_types=1);

namespace Slotera\Frontend\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Domain\Availability\AvailabilityService;
use Slotera\Application\Services\DateRangeInventoryService;
use Slotera\Application\Services\PricingAdjustmentService;
use Slotera\Application\Services\PerformanceProfiler;
use Slotera\Infrastructure\Http\ClientIpResolver;
use Slotera\Infrastructure\Http\RateLimiter;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class AvailabilityController
{
    private const AVAILABILITY_LOOKAHEAD_DAYS = 366;
    private const AVAILABILITY_LOOKAHEAD_MONTHS = 12;

    private RequestValidator $request;
    private AvailabilityService $availability;

    public function __construct(?RequestValidator $request = null, ?AvailabilityService $availability = null)
    {
        $this->request = $request ?? new RequestValidator();
        $this->availability = $availability ?? new AvailabilityService();
    }

    public function register(): void
    {
        add_action('wp_ajax_sltr_get_slots', [$this, 'get_slots']);
        add_action('wp_ajax_nopriv_sltr_get_slots', [$this, 'get_slots']);
        add_action('wp_ajax_sltr_get_available_dates', [$this, 'get_available_dates']);
        add_action('wp_ajax_nopriv_sltr_get_available_dates', [$this, 'get_available_dates']);
        add_action('wp_ajax_sltr_get_date_range_units', [$this, 'get_date_range_units']);
        add_action('wp_ajax_nopriv_sltr_get_date_range_units', [$this, 'get_date_range_units']);
        add_action('wp_ajax_sltr_get_scheduled_events', [$this, 'get_scheduled_events']);
        add_action('wp_ajax_nopriv_sltr_get_scheduled_events', [$this, 'get_scheduled_events']);
    }

    public function get_slots(): void
    {
        $this->request->verify_ajax_nonce('sltr_frontend_booking');
        $this->enforce_public_availability_rate_limit();
        $package_id = $this->request->post_int('package_id');
        $date = $this->request->post_text('date');
        $resource_id = $this->request->post_int('resource_id');
        $staff_id = $this->request->post_int('staff_id');

        $package = (new PackageRepository())->get_by_id($package_id);
        if (!$package || (int) ($package['is_active'] ?? 0) !== 1 || !$this->is_valid_date($date) || !$this->is_availability_date_in_range($date)) {
            wp_send_json_error(sltr_t('Invalid request.'));
        }

        $slots = PerformanceProfiler::time('ajax.availability.get_slots', function () use ($package_id, $date, $resource_id, $staff_id): array {
            return $this->availability->get_available_slots_for_package_date($package_id, $date, $resource_id, $staff_id);
        });
        wp_send_json_success($slots);
    }

    public function get_available_dates(): void
    {
        $this->request->verify_ajax_nonce('sltr_frontend_booking');
        $this->enforce_public_availability_rate_limit();
        $package_id = $this->request->post_int('package_id');
        $year = $this->request->post_int('year');
        $month = $this->request->post_int('month');

        $package = (new PackageRepository())->get_by_id($package_id);
        if (!$package || (int) ($package['is_active'] ?? 0) !== 1 || $year < 2000 || $month < 1 || $month > 12 || !$this->is_availability_month_in_range($year, $month)) {
            wp_send_json_error(sltr_t('Invalid request.'));
        }

        $dates = PerformanceProfiler::time('ajax.availability.get_available_dates', function () use ($package_id, $year, $month): array {
            return $this->availability->get_available_dates_for_package_month($package_id, $year, $month);
        });
        wp_send_json_success(['dates' => $dates]);
    }


    public function get_date_range_units(): void
    {
        $this->request->verify_ajax_nonce('sltr_frontend_booking');
        $this->enforce_public_availability_rate_limit();
        $package_id = $this->request->post_int('package_id');
        $start_date = $this->request->post_text('start_date');
        $end_date = $this->request->post_text('end_date');
        $package = (new PackageRepository())->get_by_id($package_id);
        if (!$package || (int) ($package['is_active'] ?? 0) !== 1 || !$this->is_valid_date($start_date) || !$this->is_valid_date($end_date) || !$this->is_availability_date_in_range($start_date) || !$this->is_availability_date_in_range($end_date)) {
            wp_send_json_error(sltr_t('Invalid request.'));
        }
        $service = new DateRangeInventoryService();
        $validation = $service->validate_range($package, $start_date, $end_date);
        if (is_wp_error($validation)) { wp_send_json_error($validation->get_error_message()); }
        $mode_configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        $date_range_config = is_array($mode_configs) && isset($mode_configs['date_range_inventory']) && is_array($mode_configs['date_range_inventory']) ? $mode_configs['date_range_inventory'] : [];
        $date_range_payment = $this->date_range_payment_policy($package, $date_range_config);
        wp_send_json_success([
            'units' => array_map(static function (array $unit) use ($package, $start_date, $end_date): array {
                if (!empty($unit['quote']) && is_array($unit['quote'])) {
                    $unit['quote'] = (new PricingAdjustmentService())->apply_to_quote($package, $unit['quote'], ['start_date' => $start_date, 'end_date' => $end_date]);
                }
                $left = (int) ($unit['available_units'] ?? $unit['seats_left'] ?? 0);
                $threshold = max(1, min(99, (int) ($package['low_availability_threshold'] ?? 5)));
                $unit['availability_label'] = sltr_places_left_label($left, $left > 0 && $left <= $threshold);
                return $unit;
            }, $service->availability_for_range($package, $start_date, $end_date)),
            'extras' => $service->extras($package),
            'included_services' => (string) ($package['included_services'] ?? ''),
            'payment_policy' => $date_range_payment['payment_policy'],
            'deposit_type' => $date_range_payment['deposit_type'],
            'deposit_value' => $date_range_payment['deposit_value'],
            'low_availability_notice_enabled' => array_key_exists('low_availability_notice_enabled', $package) ? (int) $package['low_availability_notice_enabled'] : (int) ($date_range_config['low_availability_notice_enabled'] ?? 0),
            'low_availability_threshold' => max(1, min(99, (int) (array_key_exists('low_availability_threshold', $package) ? $package['low_availability_threshold'] : ($date_range_config['low_availability_threshold'] ?? 5)))),
        ]);
    }



    public function get_scheduled_events(): void
    {
        $this->request->verify_ajax_nonce('sltr_frontend_booking');
        $this->enforce_public_availability_rate_limit();
        $package_id = $this->request->post_int('package_id');
        $package = (new PackageRepository())->get_by_id($package_id);
        if (!$package || (int) ($package['is_active'] ?? 0) !== 1) {
            wp_send_json_error(sltr_t('Invalid request.'));
        }
        $service = new DateRangeInventoryService();
        if (!$service->is_admin_scheduled($package)) {
            wp_send_json_error(sltr_t('This package does not use scheduled events.'));
        }
        $scheduled_options = $service->scheduled_event_options($package);
        $scheduled_payment = !empty($scheduled_options[0]) && is_array($scheduled_options[0]) ? $scheduled_options[0] : [];
        wp_send_json_success([
            'events' => array_map(static function (array $event) use ($package): array {
                if (!empty($event['quote']) && is_array($event['quote'])) {
                    $event['quote'] = (new PricingAdjustmentService())->apply_to_quote($package, $event['quote'], ['start_date' => (string) ($event['start_date'] ?? ''), 'end_date' => (string) ($event['end_date'] ?? '')]);
                }
                $left = (int) ($event['seats_left'] ?? 0);
                $threshold = max(1, min(99, (int) ($package['low_availability_threshold'] ?? 5)));
                $event['availability_label'] = sltr_places_left_label($left, $left > 0 && $left <= $threshold);
                return $event;
            }, $scheduled_options),
            'extras' => $service->extras($package),
            'included_services' => (string) ($package['included_services'] ?? ''),
            'payment_policy' => (string) ($scheduled_payment['payment_policy'] ?? 'booking_only'),
            'deposit_type' => (string) ($scheduled_payment['deposit_type'] ?? 'percent'),
            'deposit_value' => (float) ($scheduled_payment['deposit_value'] ?? 30),
        ]);
    }


    /**
     * @return array{payment_policy:string,deposit_type:string,deposit_value:float}
     */
    private function date_range_payment_policy(array $package, array $config): array
    {
        $payment_policy = sanitize_key((string) ($config['payment_policy'] ?? $package['payment_policy'] ?? 'booking_only'));
        if (!in_array($payment_policy, ['booking_only', 'full_payment', 'deposit_payment', 'full_or_deposit', 'booking_or_full', 'booking_or_deposit', 'all_options'], true)) {
            $payment_policy = 'booking_only';
        }

        $deposit_type = sanitize_key((string) ($config['deposit_type'] ?? $package['deposit_type'] ?? 'percent'));
        if (!in_array($deposit_type, ['percent', 'fixed'], true)) {
            $deposit_type = 'percent';
        }

        return [
            'payment_policy' => $payment_policy,
            'deposit_type' => $deposit_type,
            'deposit_value' => max(0, (float) ($config['deposit_value'] ?? $package['deposit_value'] ?? 30)),
        ];
    }


    private function enforce_public_availability_rate_limit(): void
    {
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            return;
        }

        $settings = (new SettingsRepository())->all();
        if (array_key_exists('security_availability_rate_limit_enabled', $settings) && empty($settings['security_availability_rate_limit_enabled'])) {
            return;
        }

        $limit = max(1, (int) ($settings['security_availability_rate_limit_attempts'] ?? 120));
        $window = max(1, (int) ($settings['security_rate_limit_window_minutes'] ?? 15)) * MINUTE_IN_SECONDS;
        $ip = ClientIpResolver::get_client_ip();
        $identity = $ip !== '' ? $ip : 'unknown';
        $attempts = RateLimiter::increment('ajax_availability', 'ip_' . md5($identity), $window);

        if ($attempts > $limit) {
            wp_send_json_error(sltr_t('Too many availability requests. Please try again later.'), 429);
        }
    }

    private function is_availability_date_in_range(string $date): bool
    {
        try {
            $timezone = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('UTC');
            $today = new \DateTimeImmutable(function_exists('current_time') ? current_time('Y-m-d') : 'today', $timezone);
            $requested = new \DateTimeImmutable($date, $timezone);
            $max = $today->modify('+' . self::AVAILABILITY_LOOKAHEAD_DAYS . ' days');
            return $requested >= $today && $requested <= $max;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function is_availability_month_in_range(int $year, int $month): bool
    {
        try {
            $timezone = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('UTC');
            $today = new \DateTimeImmutable(function_exists('current_time') ? current_time('Y-m-01') : 'first day of this month', $timezone);
            $requested = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), $timezone);
            $max = $today->modify('+' . self::AVAILABILITY_LOOKAHEAD_MONTHS . ' months');
            return $requested >= $today && $requested <= $max;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function is_valid_date(string $date): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date;
    }
}
