<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\BookingRepository;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

/**
 * Owns manual booking status transitions, history recording and notifications.
 */
final class BookingStatusTransitionService
{
    private BookingRepository $bookings;
    private BookingLifecycleService $lifecycle;
    private NotificationService $notifications;

    public function __construct(
        BookingRepository $bookings,
        BookingLifecycleService $lifecycle,
        NotificationService $notifications
    ) {
        $this->bookings = $bookings;
        $this->lifecycle = $lifecycle;
        $this->notifications = $notifications;
    }

    public function confirm_booking(int $id)
    {
        $booking = $this->bookings->get_by_id($id);
        if (!$booking) { return new WP_Error('sltr_booking_not_found', __('Booking not found.', 'slotera-booking')); }
        if (($booking['payment_status'] ?? '') === 'pending') { return true; }
        if (in_array((string) ($booking['status'] ?? ''), [BookingLifecycleService::STATUS_CANCELLED, BookingLifecycleService::STATUS_COMPLETED], true)) {
            return new WP_Error('sltr_booking_not_confirmable', __('Cancelled or completed bookings cannot be confirmed manually.', 'slotera-booking'));
        }

        $updated = $this->bookings->update_status($id, BookingLifecycleService::STATUS_CONFIRMED);
        if ($updated) {
            $after = $this->bookings->get_by_id($id);
            $this->lifecycle->record_history($id, 'booking_confirmed_by_admin', $booking, $after, 'Booking confirmed by admin.');
            $this->notifications->booking_confirmed($id, $after);
        }
        return $updated;
    }

    public function cancel_booking(int $id)
    {
        $booking = $this->bookings->get_by_id($id);
        if (!$booking) { return new WP_Error('sltr_booking_not_found', __('Booking not found.', 'slotera-booking')); }
        if (($booking['status'] ?? '') === BookingLifecycleService::STATUS_COMPLETED) { return new WP_Error('sltr_completed_booking', __('Completed bookings cannot be cancelled.', 'slotera-booking')); }

        $updated = $this->bookings->cancel($id);
        if ($updated) {
            $after = $this->bookings->get_by_id($id);
            $this->lifecycle->record_history($id, 'booking_cancelled', $booking, $after, 'Booking cancelled.');
            $this->notifications->booking_cancelled($id, $after);
        }
        return $updated;
    }

    public function complete_booking(int $id)
    {
        $booking = $this->bookings->get_by_id($id);
        if (!$booking) { return new WP_Error('sltr_booking_not_found', __('Booking not found.', 'slotera-booking')); }
        if (($booking['status'] ?? '') === BookingLifecycleService::STATUS_CANCELLED) { return new WP_Error('sltr_cancelled_booking', __('Cancelled bookings cannot be completed.', 'slotera-booking')); }

        $updated = $this->bookings->complete($id);
        if ($updated) {
            $after = $this->bookings->get_by_id($id);
            $this->lifecycle->record_history($id, 'booking_completed', $booking, $after, 'Booking completed.');
            $this->notifications->booking_completed($id, $after);
        }
        return $updated;
    }
}
