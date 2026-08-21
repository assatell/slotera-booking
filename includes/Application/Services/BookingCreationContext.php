<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Domain\Availability\AvailabilityService;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;

if (!defined('ABSPATH')) { exit; }

final class BookingCreationContext
{
    public BookingRepository $booking_repository;
    public PackageRepository $package_repository;
    public AvailabilityService $availability_service;
    public BookingLockService $booking_lock_service;
    public BookingLifecycleService $lifecycle;
    public PaymentService $payment_service;
    public PaymentPolicyService $payment_policy_service;
    public CustomerService $customer_service;
    public NotificationService $notifications;
    public CouponService $coupons;
    public PricingAdjustmentService $pricing_adjustments;

    public function __construct(
        BookingRepository $booking_repository,
        PackageRepository $package_repository,
        AvailabilityService $availability_service,
        BookingLockService $booking_lock_service,
        BookingLifecycleService $lifecycle,
        PaymentService $payment_service,
        PaymentPolicyService $payment_policy_service,
        CustomerService $customer_service,
        NotificationService $notifications,
        CouponService $coupons,
        PricingAdjustmentService $pricing_adjustments
    ) {
        $this->booking_repository = $booking_repository;
        $this->package_repository = $package_repository;
        $this->availability_service = $availability_service;
        $this->booking_lock_service = $booking_lock_service;
        $this->lifecycle = $lifecycle;
        $this->payment_service = $payment_service;
        $this->payment_policy_service = $payment_policy_service;
        $this->customer_service = $customer_service;
        $this->notifications = $notifications;
        $this->coupons = $coupons;
        $this->pricing_adjustments = $pricing_adjustments;
    }
}
