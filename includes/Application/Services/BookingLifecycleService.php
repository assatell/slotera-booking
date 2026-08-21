<?php
declare(strict_types=1);
namespace Slotera\Application\Services;
use Slotera\Core\Events;
use Slotera\Infrastructure\Repositories\BookingHistoryRepository;
use Slotera\Infrastructure\Repositories\BookingRepository;
if (!defined('ABSPATH')) { exit; }
final class BookingLifecycleService {
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';
    public static function statuses(): array {
        $statuses = [self::STATUS_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_COMPLETED];
        if (function_exists('sltr_feature_enabled') && \sltr_feature_enabled('payments')) {
            array_unshift($statuses, self::STATUS_PENDING_PAYMENT);
        }
        return $statuses;
    }
    public static function active_statuses(): array {
        // pending_payment is always treated as an active inventory hold.
        // The payments feature gate controls whether new online-payment holds can be
        // created/exposed, not whether an existing hold blocks double booking.
        return [self::STATUS_PENDING_PAYMENT, self::STATUS_CONFIRMED];
    }
    public function generate_token(): string { return wp_generate_password(48, false, false); }
    public function generate_unique_token(string $column, BookingRepository $repo): string {
        $column = $column === 'reschedule_token' ? 'reschedule_token' : 'cancellation_token';
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $token = $this->generate_token();
            if (!$repo->token_exists($column, $token)) { return $token; }
        }
        return hash('sha256', wp_generate_uuid4() . microtime(true) . wp_rand());
    }
    public function cancellation_url(array $booking): string {
        $token = (string) ($booking['cancellation_token'] ?? '');
        return $token !== '' ? (new PublicBookingActionSecurityService())->cancel_url($token) : '';
    }
    public function reschedule_url(array $booking): string {
        $token = (string) ($booking['reschedule_token'] ?? '');
        return $token !== '' ? (new PublicBookingActionSecurityService())->reschedule_url($token) : '';
    }
    public function record_history(int $booking_id, string $event, ?array $before, ?array $after, string $message='', array $payload=[]): void {
        (new BookingHistoryRepository())->create([
            'booking_id'=>$booking_id,
            'event'=>$event,
            'old_status'=>$before['status']??null,
            'new_status'=>$after['status']??null,
            'old_payment_status'=>$before['payment_status']??null,
            'new_payment_status'=>$after['payment_status']??null,
            'message'=>$message,
            'payload'=>$payload,
        ]);
    }
    public function cancel_by_token(string $token) {
        $repo=new BookingRepository();
        $booking=$repo->get_by_cancellation_token($token);
        if (!$booking) { return new \WP_Error('sltr_invalid_token', __('Invalid or expired cancellation link.', 'slotera-booking')); }
        $security = new PublicBookingActionSecurityService();
        if ($security->is_token_expired($booking)) { return new \WP_Error('sltr_expired_token', __('Invalid or expired cancellation link.', 'slotera-booking')); }
        if (($booking['status'] ?? '') === self::STATUS_CANCELLED) { return new \WP_Error('sltr_cancellation_already_used', __('This cancellation link has already been used.', 'slotera-booking')); }
        if (($booking['status'] ?? '') === self::STATUS_COMPLETED) { return new \WP_Error('sltr_completed_booking', __('Completed bookings cannot be cancelled.', 'slotera-booking')); }
        $before=$booking;
        $ok=$repo->cancel_by_token_atomically(
            (int) $booking['id'],
            $token,
            [self::STATUS_PENDING_PAYMENT, self::STATUS_CONFIRMED]
        );
        if ($ok) {
            $after=$repo->get_by_id((int)$booking['id']);
            $payload = array_merge(['source'=>'customer_token'], $security->audit_context());
            $this->record_history((int)$booking['id'],'booking_cancelled_by_customer',$before,$after,'Booking cancelled using customer cancellation link.', $payload);
            Events::dispatch(Events::BOOKING_CANCELLED, ['booking_id'=>(int)$booking['id'],'booking'=>$after,'before'=>$before,'source'=>'customer_token','payload'=>$payload]);
            return true;
        }
        return new \WP_Error('sltr_cancellation_already_used', __('This cancellation link has already been used.', 'slotera-booking'));
    }
    public function reschedule_by_token(string $token, string $date, string $start, string $end) {
        $repo = new BookingRepository();
        $booking = $repo->get_by_reschedule_token($token);
        if (!$booking) { return new \WP_Error('sltr_invalid_token', __('Invalid or expired reschedule link.', 'slotera-booking')); }
        $security = new PublicBookingActionSecurityService();
        if ($security->is_token_expired($booking)) { return new \WP_Error('sltr_expired_token', __('Invalid or expired reschedule link.', 'slotera-booking')); }
        if (($booking['status'] ?? '') === self::STATUS_CANCELLED) { return new \WP_Error('sltr_cancelled_booking', __('Cancelled bookings cannot be rescheduled.', 'slotera-booking')); }
        if (($booking['status'] ?? '') === self::STATUS_COMPLETED) { return new \WP_Error('sltr_completed_booking', __('Completed bookings cannot be rescheduled.', 'slotera-booking')); }
        if ((new BookingHistoryRepository())->count_event((int) ($booking['id'] ?? 0), 'booking_rescheduled_by_customer') > 0) { return new \WP_Error('sltr_reschedule_already_used', __('This booking has already been rescheduled from the client account.', 'slotera-booking')); }

        $date = sanitize_text_field($date);
        $package_id = absint($booking['package_id'] ?? 0);
        $resource_id = absint($booking['resource_id'] ?? 0);
        $staff_id = absint($booking['staff_id'] ?? 0);
        $package = $package_id > 0 ? (new \Slotera\Infrastructure\Repositories\PackageRepository())->get_by_id($package_id) : null;
        $configs = is_array($package) ? json_decode((string) ($package['mode_configs_json'] ?? ''), true) : [];
        $full_day_booking = is_array($package)
            && sanitize_key((string) ($package['booking_mode'] ?? '')) === 'fixed'
            && is_array($configs)
            && !empty($configs['fixed']['full_day_booking']);

        if ($full_day_booking) {
            if (!$this->is_valid_date($date)) {
                return new \WP_Error('sltr_invalid_reschedule_data', __('Invalid reschedule date or time.', 'slotera-booking'));
            }
            $old_start_date = sanitize_text_field((string) ($booking['booking_date'] ?? ''));
            $old_end_date = sanitize_text_field((string) ($booking['end_date'] ?? ''));
            if (!$this->is_valid_date($old_start_date) || !$this->is_valid_date($old_end_date) || $old_end_date <= $old_start_date) {
                return new \WP_Error('sltr_invalid_reschedule_data', __('Invalid reschedule date or time.', 'slotera-booking'));
            }
            try {
                $duration_days = max(1, (int) (new \DateTimeImmutable($old_start_date, wp_timezone()))->diff(new \DateTimeImmutable($old_end_date, wp_timezone()))->days);
                $new_end_date = (new \DateTimeImmutable($date, wp_timezone()))->modify('+' . $duration_days . ' days')->format('Y-m-d');
            } catch (\Throwable $e) {
                return new \WP_Error('sltr_invalid_reschedule_data', __('Invalid reschedule date or time.', 'slotera-booking'));
            }
            if ($this->is_past_slot($date, '00:00:00')) {
                return new \WP_Error('sltr_past_booking_date', __('Bookings cannot be rescheduled to past dates or times.', 'slotera-booking'));
            }
            if ($old_start_date === $date) { return $booking; }

            $lock_service = new BookingLockService();
            $lock = $lock_service->acquire_date_range_inventory($package_id, $resource_id, $date, $new_end_date);
            if (is_wp_error($lock)) { return $lock; }
            try {
                $available = (new \Slotera\Domain\Availability\AvailabilityService())->timed_range_is_available(
                    $package_id,
                    $date,
                    '00:00:00',
                    $new_end_date,
                    '00:00:00',
                    $resource_id,
                    $staff_id,
                    (int) ($booking['id'] ?? 0)
                );
                if (!$available) { return new \WP_Error('sltr_slot_unavailable', __('Selected time slot is no longer available.', 'slotera-booking')); }
                $before = $booking;
                $ok = $repo->update((int) $booking['id'], [
                    'booking_date' => $date,
                    'end_date' => $new_end_date,
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:00',
                    'reschedule_token' => null,
                ]);
                if (!$ok) { return new \WP_Error('sltr_reschedule_failed', __('Booking could not be rescheduled.', 'slotera-booking')); }
                $after = $repo->get_by_id((int) $booking['id']);
                $payload = array_merge([
                    'date' => $date,
                    'end_date' => $new_end_date,
                    'start' => '00:00:00',
                    'end' => '00:00:00',
                    'source' => 'customer_token',
                ], $security->audit_context());
                $this->record_history((int) $booking['id'], 'booking_rescheduled_by_customer', $before, $after, 'Booking rescheduled using customer reschedule link.', $payload);
                Events::dispatch(Events::BOOKING_RESCHEDULED, ['booking_id'=>(int)$booking['id'],'booking'=>$after,'before'=>$before,'source'=>'customer_token','payload'=>$payload]);
                return $after;
            } finally {
                $lock_service->release_date_range_inventory($package_id, $resource_id, $date, $new_end_date);
            }
        }

        $start = $this->normalize_time($start);
        $end = $this->normalize_time($end);
        if (!$this->is_valid_date($date) || $start === '' || $end === '') { return new \WP_Error('sltr_invalid_reschedule_data', __('Invalid reschedule date or time.', 'slotera-booking')); }
        if ($this->is_past_slot($date, $start)) { return new \WP_Error('sltr_past_booking_date', __('Bookings cannot be rescheduled to past dates or times.', 'slotera-booking')); }
        if ((string)($booking['booking_date'] ?? '') === $date && (string)($booking['start_time'] ?? '') === $start && (string)($booking['end_time'] ?? '') === $end) { return $booking; }
        $lock_service = new BookingLockService();
        $lock = $lock_service->acquire($package_id, $date, $start, $end, $resource_id, $staff_id);
        if (is_wp_error($lock)) { return $lock; }
        try {
            $available = false;
            foreach ((new \Slotera\Domain\Availability\AvailabilityService())->get_available_slots_for_package_date($package_id, $date, $resource_id, $staff_id, (int) ($booking['id'] ?? 0)) as $slot) {
                if ((string)($slot['start'] ?? '') === $start && (string)($slot['end'] ?? '') === $end) { $available = true; break; }
            }
            if (!$available) { return new \WP_Error('sltr_slot_unavailable', __('Selected time slot is no longer available.', 'slotera-booking')); }
            $before = $booking;
            $ok = $repo->update((int)$booking['id'], ['booking_date'=>$date,'start_time'=>$start,'end_time'=>$end,'reschedule_token'=>null]);
            if (!$ok) { return new \WP_Error('sltr_reschedule_failed', __('Booking could not be rescheduled.', 'slotera-booking')); }
            $after = $repo->get_by_id((int)$booking['id']);
            $payload=array_merge(['date'=>$date,'start'=>$start,'end'=>$end,'source'=>'customer_token'], $security->audit_context());
            $this->record_history((int)$booking['id'], 'booking_rescheduled_by_customer', $before, $after, 'Booking rescheduled using customer reschedule link.', $payload);
            Events::dispatch(Events::BOOKING_RESCHEDULED, ['booking_id'=>(int)$booking['id'],'booking'=>$after,'before'=>$before,'source'=>'customer_token','payload'=>$payload]);
            return $after;
        } finally {
            $lock_service->release($package_id, $date, $start, $end, $resource_id, $staff_id);
        }
    }
    private function normalize_time(string $time): string { $time=trim($time); if (preg_match('/^\d{2}:\d{2}$/',$time)) { return $time.':00'; } if (preg_match('/^\d{2}:\d{2}:\d{2}$/',$time)) { return $time; } return ''; }
    private function is_valid_date(string $date): bool { $dt=\DateTime::createFromFormat('Y-m-d',$date); return $dt && $dt->format('Y-m-d')===$date; }
    private function is_past_slot(string $date, string $start): bool { try { $slot=new \DateTimeImmutable($date.' '.$start, wp_timezone()); return $slot < new \DateTimeImmutable('now', wp_timezone()); } catch (\Throwable $e) { return true; } }
}
