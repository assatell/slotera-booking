<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Domain\Availability\AvailabilityService;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class BookingService
{
    private BookingRepository $booking_repository;
    private PackageRepository $package_repository;
    private CustomerService $customer_service;
    private BookingStatusTransitionService $status_transitions;
    private BookingModeRouter $booking_mode_router;

    public function __construct(
        ?BookingRepository $booking_repository = null,
        ?PackageRepository $package_repository = null,
        ?AvailabilityService $availability_service = null,
        ?BookingLockService $booking_lock_service = null,
        ?BookingLifecycleService $lifecycle = null,
        ?PaymentService $payment_service = null,
        ?CustomerService $customer_service = null,
        ?NotificationService $notifications = null,
        ?CouponService $coupons = null
    ) {
        $this->booking_repository = $booking_repository ?: new BookingRepository();
        $this->package_repository = $package_repository ?: new PackageRepository();
        $availability_service = $availability_service ?: new AvailabilityService();
        $booking_lock_service = $booking_lock_service ?: new BookingLockService();
        $lifecycle = $lifecycle ?: new BookingLifecycleService();
        $payment_service = $payment_service ?: new PaymentService(null, $this->booking_repository);
        $payment_policy_service = new PaymentPolicyService();
        $this->customer_service = $customer_service ?: new CustomerService();
        $notifications = $notifications ?: new NotificationService();
        $coupons = $coupons ?: new CouponService();
        $pricing_adjustments = new PricingAdjustmentService();

        $context = new BookingCreationContext(
            $this->booking_repository,
            $this->package_repository,
            $availability_service,
            $booking_lock_service,
            $lifecycle,
            $payment_service,
            $payment_policy_service,
            $this->customer_service,
            $notifications,
            $coupons,
            $pricing_adjustments
        );

        $this->booking_mode_router = new BookingModeRouter($context);
        $this->status_transitions = new BookingStatusTransitionService($this->booking_repository, $lifecycle, $notifications);
    }

    public function create_booking(array $data)
    {
        $package_id = absint($data['package_id'] ?? 0);
        $package = $this->package_repository->get_by_id($package_id);
        if (!$package || (int) ($package['is_active'] ?? 0) !== 1) {
            return new WP_Error('sltr_invalid_package', __('Selected package is not available.', 'slotera-booking'));
        }

        $customer = $this->customer_service->sanitize_booking_data($data);
        $customer_validation = $this->customer_service->validate_booking_fields($customer);
        if (is_wp_error($customer_validation)) {
            return $customer_validation;
        }

        $booking_mode = sanitize_key((string) ($package['booking_mode'] ?? 'fixed'));
        return $this->booking_mode_router->create($booking_mode, $package, $customer, $data);
    }

    public function confirm_booking(int $id)
    {
        return $this->status_transitions->confirm_booking($id);
    }

    public function cancel_booking(int $id)
    {
        return $this->status_transitions->cancel_booking($id);
    }

    public function complete_booking(int $id)
    {
        return $this->status_transitions->complete_booking($id);
    }
}
