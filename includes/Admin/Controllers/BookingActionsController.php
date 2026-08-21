<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\BookingService;
use Slotera\Application\Services\PaymentService;
use Slotera\Application\Services\RequestValidator;

if (!defined('ABSPATH')) { exit; }

final class BookingActionsController
{
    private BookingService $bookings;
    private PaymentService $payments;
    private RequestValidator $request;

    public function __construct(?RequestValidator $request = null, ?BookingService $bookings = null, ?PaymentService $payments = null)
    {
        $this->request = $request ?? new RequestValidator();
        $this->bookings = $bookings ?? new BookingService();
        $this->payments = $payments ?? new PaymentService();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_booking_confirm', [$this, 'confirm']);
        add_action('admin_post_sltr_booking_cancel', [$this, 'cancel']);
        add_action('admin_post_sltr_booking_complete', [$this, 'complete']);
        add_action('admin_post_sltr_booking_mark_paid', [$this, 'paid']);
        add_action('admin_post_sltr_booking_mark_unpaid', [$this, 'unpaid']);
    }

    public function confirm(): void
    {
        $id = $this->verify('confirm');
        $this->bookings->confirm_booking($id);
        $this->back();
    }

    public function cancel(): void
    {
        $id = $this->verify('cancel');
        $this->bookings->cancel_booking($id);
        $this->back();
    }

    public function complete(): void
    {
        $id = $this->verify('complete');
        $this->bookings->complete_booking($id);
        $this->back();
    }

    public function paid(): void
    {
        $id = $this->verify('mark_paid');
        $this->payments->mark_paid($id);
        $this->back();
    }

    public function unpaid(): void
    {
        $id = $this->verify('mark_unpaid');
        $this->payments->mark_unpaid($id);
        $this->back();
    }

    private function verify(string $action): int
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_BOOKINGS);
        $id = $this->request->get_int('booking_id');
        if ($id <= 0) { wp_die(esc_html__('Invalid booking.', 'slotera-booking')); }
        $this->request->verify_admin_nonce('sltr_booking_' . $action . '_' . $id);
        return $id;
    }

    private function back(): void
    {
        $redirect = wp_get_referer();
        if (!$redirect) { $redirect = admin_url('admin.php?page=slotera-bookings&sltr_message=updated'); }
        wp_safe_redirect(add_query_arg('sltr_message', 'updated', $redirect));
        exit;
    }
}
