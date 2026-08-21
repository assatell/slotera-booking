<?php

declare(strict_types=1);

namespace Slotera\Domain\Availability;

use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\WorkingHoursRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class AvailabilityService
{
    private BookingRepository $booking_repository;
    private PackageRepository $package_repository;
    private WorkingHoursRepository $working_hours_repository;
    private SettingsRepository $settings_repository;

    public function __construct(
        ?BookingRepository $booking_repository = null,
        ?PackageRepository $package_repository = null,
        ?WorkingHoursRepository $working_hours_repository = null,
        ?SettingsRepository $settings_repository = null
    ) {
        $this->booking_repository = $booking_repository ?: new BookingRepository();
        $this->package_repository = $package_repository ?: new PackageRepository();
        $this->working_hours_repository = $working_hours_repository ?: new WorkingHoursRepository();
        $this->settings_repository = $settings_repository ?: new SettingsRepository();
    }

    public function get_available_slots_for_package_date(int $package_id, string $date, int $resource_id = 0, int $staff_id = 0, int $exclude_booking_id = 0): array
    {
        $package = $this->package_repository->get_by_id($package_id);

        if (!$package || (int) ($package['is_active'] ?? 0) !== 1) {
            return [];
        }

        if ($this->is_closed_on_date($date)) {
            return [];
        }

        $weekday = $this->get_weekday_from_date($date);

        if ($weekday === 0) {
            return [];
        }

        $hours_mode = sanitize_key((string) ($package['hours_mode'] ?? 'global'));
        $working_windows = !empty($package['open_247'])
            ? [[
                'weekday' => $weekday,
                'start_time' => '00:00:00',
                'end_time' => '23:59:00',
                'is_enabled' => 1,
            ]]
            : ($hours_mode === 'custom'
            ? $this->working_hours_repository->get_for_weekday('package', $package_id, $weekday)
            : $this->working_hours_repository->get_for_weekday('global', 0, $weekday));

        if (empty($working_windows)) {
            return [];
        }

        $duration_minutes = max(1, (int) ($package['duration_minutes'] ?? 60));
        $booking_mode = sanitize_key((string) ($package['booking_mode'] ?? 'fixed'));
        $full_day_booking = $booking_mode === 'fixed' && $this->fixed_full_day_enabled($package);
        $slot_step_minutes = max(1, (int) ($package['slot_step'] ?? $duration_minutes));

        if ($full_day_booking) {
            try {
                $end_date = (new \DateTimeImmutable($date, wp_timezone()))->modify('+1 day')->format('Y-m-d');
            } catch (\Throwable $e) {
                return [];
            }
            $lead_time_minutes = max(0, (int) ($package['lead_time_minutes'] ?? 0));
            if (!$this->meets_lead_time($date, '00:00:00', $lead_time_minutes)) {
                return [];
            }
            return $this->timed_range_is_available(
                $package_id,
                $date,
                '00:00:00',
                $end_date,
                '00:00:00',
                $resource_id,
                $staff_id,
                $exclude_booking_id
            ) ? [['start' => '00:00:00', 'end' => '00:00:00']] : [];
        }

        if ($booking_mode === 'fixed' || $slot_step_minutes > $duration_minutes) {
            $slot_step_minutes = $duration_minutes;
        }

        $buffer_before = max(0, (int) ($package['buffer_before'] ?? 0));
        $buffer_after = max(0, (int) ($package['buffer_after'] ?? 0));
        $max_bookings_per_slot = max(1, (int) ($package['max_bookings_per_slot'] ?? 1));
        $lead_time_minutes = max(0, (int) ($package['lead_time_minutes'] ?? 0));
        $existing_bookings = $this->booking_repository->get_for_date($package_id, $date, max(0, $resource_id), max(0, $staff_id));
        $existing_bookings = $this->exclude_booking($existing_bookings, $exclude_booking_id);
        $available = [];

        foreach ($working_windows as $window) {
            $candidates = $this->generate_slots(
                (string) $window['start_time'],
                (string) $window['end_time'],
                $duration_minutes,
                $slot_step_minutes
            );

            foreach ($candidates as $slot) {
                if (
                    $this->meets_lead_time($date, $slot['start'], $lead_time_minutes)
                    && $this->meets_same_day_before_buffer($date, $slot['start'], $buffer_before)
                    && $this->is_slot_free($date, $slot['start'], $slot['end'], $existing_bookings, $buffer_before, $buffer_after, $max_bookings_per_slot)
                ) {
                    $available[] = $slot;
                }
            }
        }

        return $this->sort_and_unique_slots($available);
    }

    public function get_available_dates_for_package_month(int $package_id, int $year, int $month): array
    {
        if ($package_id <= 0 || $year < 2000 || $month < 1 || $month > 12) {
            return [];
        }

        try {
            $timezone = wp_timezone();
            $month_start = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month), $timezone);
            $days_in_month = (int) $month_start->format('t');
            $today = (new \DateTimeImmutable('today', $timezone))->format('Y-m-d');
        } catch (\Throwable $e) {
            return [];
        }

        $available_dates = [];
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            if ($date < $today) {
                continue;
            }
            if (!empty($this->get_available_slots_for_package_date($package_id, $date))) {
                $available_dates[] = $date;
            }
        }

        return $available_dates;
    }

    public function timed_range_is_available(int $package_id, string $start_date, string $start_time, string $end_date, string $end_time, int $resource_id = 0, int $staff_id = 0, int $exclude_booking_id = 0): bool
    {
        $package = $this->package_repository->get_by_id($package_id);
        if (!$package || (int) ($package['is_active'] ?? 0) !== 1) { return false; }

        try {
            $timezone = wp_timezone();
            $candidate_start = $this->date_time($start_date, $start_time, $timezone);
            $candidate_end = $this->date_time($end_date, $end_time, $timezone);
        } catch (\Throwable $e) {
            return false;
        }
        if ($candidate_end <= $candidate_start) { return false; }
        if ($this->range_overlaps_closure($candidate_start, $candidate_end)) { return false; }

        $buffer_before = max(0, (int) ($package['buffer_before'] ?? 0));
        $buffer_after = max(0, (int) ($package['buffer_after'] ?? 0));
        $max_bookings_per_slot = max(1, (int) ($package['max_bookings_per_slot'] ?? 1));
        $candidate_block_start = $candidate_start->modify('-' . $buffer_before . ' minutes');
        $candidate_block_end = $candidate_end->modify('+' . $buffer_after . ' minutes');

        $existing = $this->booking_repository->get_for_time_range($package_id, $start_date, $end_date, max(0, $resource_id), max(0, $staff_id));
        $existing = $this->exclude_booking($existing, $exclude_booking_id);
        $overlapping = 0;
        foreach ($existing as $booking) {
            try {
                $booking_date = (string) ($booking['booking_date'] ?? '');
                $booking_end_date = sanitize_text_field((string) ($booking['end_date'] ?? ''));
                if ($booking_end_date === '') { $booking_end_date = $booking_date; }
                $booking_start = $this->date_time($booking_date, (string) ($booking['start_time'] ?? ''), $timezone);
                $booking_end = $this->date_time($booking_end_date, (string) ($booking['end_time'] ?? ''), $timezone);
                if ($booking_end <= $booking_start && $booking_end_date === $booking_date) {
                    $booking_end = $booking_start->modify('+1 day');
                }
                $booking_block_start = $booking_start->modify('-' . $buffer_before . ' minutes');
                $booking_block_end = $booking_end->modify('+' . $buffer_after . ' minutes');
            } catch (\Throwable $e) {
                continue;
            }
            if ($candidate_block_start < $booking_block_end && $candidate_block_end > $booking_block_start) {
                $overlapping++;
                if ($overlapping >= $max_bookings_per_slot) { return false; }
            }
        }
        return true;
    }

    public function slot_is_available(int $package_id, string $date, string $start, string $end, int $resource_id = 0, int $staff_id = 0, int $exclude_booking_id = 0): bool
    {
        foreach ($this->get_available_slots_for_package_date($package_id, $date, $resource_id, $staff_id, $exclude_booking_id) as $slot) {
            if ((string) $slot['start'] === $start && (string) $slot['end'] === $end) {
                return true;
            }
        }
        return false;
    }

    public function is_closed_on_date(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return true;
        }
        foreach ((array) $this->settings_repository->get('business_closures', []) as $closure) {
            if (!is_array($closure)) {
                continue;
            }
            $start = (string) ($closure['start_date'] ?? '');
            $end = (string) ($closure['end_date'] ?? $start);
            if ($start !== '' && $date >= $start && $date <= $end) {
                return true;
            }
        }
        return false;
    }

    private function range_overlaps_closure(\DateTimeImmutable $start, \DateTimeImmutable $end): bool
    {
        $cursor = $start->setTime(0, 0, 0);
        $last = $end->setTime(0, 0, 0);
        if ($end->format('H:i:s') === '00:00:00' && $end > $start) {
            $last = $last->modify('-1 day');
        }
        while ($cursor <= $last) {
            if ($this->is_closed_on_date($cursor->format('Y-m-d'))) {
                return true;
            }
            $cursor = $cursor->modify('+1 day');
        }
        return false;
    }

    private function exclude_booking(array $existing_bookings, int $exclude_booking_id): array
    {
        $exclude_booking_id = max(0, $exclude_booking_id);

        if ($exclude_booking_id <= 0) {
            return $existing_bookings;
        }

        return array_values(array_filter($existing_bookings, static function (array $booking) use ($exclude_booking_id): bool {
            return (int) ($booking['id'] ?? 0) !== $exclude_booking_id;
        }));
    }

    private function generate_slots(string $work_start, string $work_end, int $duration_minutes, int $slot_step_minutes): array
    {
        try {
            $timezone = wp_timezone();
            $window_start = $this->time_on_anchor_date($work_start, $timezone);
            $window_end = $this->time_on_anchor_date($work_end, $timezone);
        } catch (\Throwable $e) {
            return [];
        }

        if ($window_start >= $window_end) {
            return [];
        }

        $slots = [];
        $current_start = $window_start;

        while ($current_start < $window_end) {
            $candidate_end = $current_start->modify('+' . max(1, $duration_minutes) . ' minutes');

            if ($candidate_end > $window_end) {
                break;
            }

            $slots[] = [
                'start' => $current_start->format('H:i:s'),
                'end' => $candidate_end->format('H:i:s'),
            ];

            $next_start = $current_start->modify('+' . max(1, $slot_step_minutes) . ' minutes');

            if ($next_start <= $current_start) {
                break;
            }

            $current_start = $next_start;
        }

        return $slots;
    }

    private function is_slot_free(string $date, string $slot_start, string $slot_end, array $existing_bookings, int $buffer_before, int $buffer_after, int $max_bookings_per_slot): bool
    {
        try {
            $timezone = wp_timezone();
            $candidate_start = $this->date_time($date, $slot_start, $timezone);
            $candidate_end = $this->date_time($date, $slot_end, $timezone);
            $candidate_block_start = $candidate_start->modify('-' . max(0, $buffer_before) . ' minutes');
            $candidate_block_end = $candidate_end->modify('+' . max(0, $buffer_after) . ' minutes');
        } catch (\Throwable $e) {
            return false;
        }

        if ($candidate_end <= $candidate_start) {
            return false;
        }

        $overlapping_bookings = 0;
        foreach ($existing_bookings as $booking) {
            try {
                $booking_date = (string) $booking['booking_date'];
                $booking_start = $this->date_time($booking_date, (string) $booking['start_time'], $timezone);
                $booking_end_date = sanitize_text_field((string) ($booking['end_date'] ?? ''));
                if ($booking_end_date !== '' && $booking_end_date > $booking_date) {
                    $booking_end = $this->date_time($booking_end_date, (string) $booking['end_time'], $timezone);
                } else {
                    $booking_end = $this->date_time($booking_date, (string) $booking['end_time'], $timezone);
                }
                $booking_block_start = $booking_start->modify('-' . max(0, $buffer_before) . ' minutes');
                $booking_block_end = $booking_end->modify('+' . max(0, $buffer_after) . ' minutes');
            } catch (\Throwable $e) {
                continue;
            }

            if ($booking_end <= $booking_start) {
                continue;
            }

            if ($candidate_block_start < $booking_block_end && $candidate_block_end > $booking_block_start) {
                $overlapping_bookings++;
                if ($overlapping_bookings >= $max_bookings_per_slot) {
                    return false;
                }
            }
        }

        return true;
    }

    private function fixed_full_day_enabled(array $package): bool
    {
        $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        return is_array($configs) && !empty($configs['fixed']['full_day_booking']);
    }

    private function meets_same_day_before_buffer(string $date, string $slot_start, int $buffer_before, ?\DateTimeImmutable $now = null): bool
    {
        $buffer_before = max(0, $buffer_before);
        if ($buffer_before <= 0) {
            return true;
        }

        try {
            $timezone = wp_timezone();
            $now = $now ?: new \DateTimeImmutable('now', $timezone);
            $slot = $this->date_time($date, $slot_start, $timezone);

            // Always compare the beginning of the preparation window. This is
            // intentionally date-agnostic: a tomorrow 00:30 slot with a
            // two-hour buffer already started today at 22:30.
            return $slot->modify('-' . $buffer_before . ' minutes') >= $now;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function meets_lead_time(string $date, string $slot_start, int $lead_time_minutes): bool
    {
        try {
            $timezone = wp_timezone();
            $slot = $this->date_time($date, $slot_start, $timezone);
            $minimum = (new \DateTimeImmutable('now', $timezone))->modify('+' . max(0, $lead_time_minutes) . ' minutes');
            return $slot >= $minimum;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function get_weekday_from_date(string $date): int
    {
        try {
            return (int) (new \DateTimeImmutable($date . ' 00:00:00', wp_timezone()))->format('N');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function date_time(string $date, string $time, \DateTimeZone $timezone): \DateTimeImmutable
    {
        return new \DateTimeImmutable(trim($date) . ' ' . $this->normalize_time($time), $timezone);
    }

    private function time_on_anchor_date(string $time, \DateTimeZone $timezone): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2000-01-01 ' . $this->normalize_time($time), $timezone);
    }

    private function normalize_time(string $time): string
    {
        $time = trim($time);

        if (preg_match('/^\d{1,2}:\d{2}$/', $time) === 1) {
            return $time . ':00';
        }

        return $time;
    }

    private function sort_and_unique_slots(array $slots): array
    {
        $unique = [];

        foreach ($slots as $slot) {
            if (!isset($slot['start'], $slot['end'])) {
                continue;
            }
            $unique[$slot['start'] . '-' . $slot['end']] = $slot;
        }

        uasort($unique, static function (array $a, array $b): int {
            return strcmp((string) $a['start'], (string) $b['start']);
        });

        return array_values($unique);
    }
}
