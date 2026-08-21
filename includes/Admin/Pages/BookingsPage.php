<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\ActivityLogRepository;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\BookingHistoryRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class BookingsPage
{
    private BookingRepository $repo;
    private RequestValidator $request;

    public function __construct(?BookingRepository $repo = null, ?RequestValidator $request = null)
    {
        $this->repo = $repo ?: new BookingRepository();
        $this->request = $request ?? new RequestValidator();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_BOOKINGS);

        $tab = $this->request->get_key('tab', 'bookings');
        if ($tab === 'customers') {
            (new CustomersPage())->render_embedded();
            return;
        }

        $booking_id = $this->request->get_int('booking_id');

        if ($booking_id) {
            $booking = $this->repo->get_by_id($booking_id);
            $logs = (new ActivityLogRepository())->get_by_object('booking', $booking_id);
            $history = (new BookingHistoryRepository())->get_by_booking($booking_id);
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/booking-single.php';
            return;
        }

        $filters = [
            'search' => $this->request->get_text('s'),
            'upcoming' => $this->request->get_text('upcoming') !== '',
            'status' => $this->request->get_key('status', 'all'),
            'payment_status' => $this->request->get_key('payment_status', 'all'),
            'date_from' => $this->request->get_text('date_from'),
            'date_to' => $this->request->get_text('date_to'),
        ];

        $per_page = 20;
        $page = max(1, $this->request->get_int('paged', 1));
        $bookings = $this->repo->search($filters, $per_page, ($page - 1) * $per_page);
        $pagination = [
            'page' => $page,
            'total_pages' => (int) ceil($this->repo->count_search($filters) / $per_page),
        ];

        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/bookings.php';
    }
}
