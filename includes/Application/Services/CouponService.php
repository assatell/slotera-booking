<?php
declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\CouponRepository;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class CouponService
{
    private CouponRepository $coupons;
    private BookingRepository $bookings;

    public function __construct(?CouponRepository $coupons = null, ?BookingRepository $bookings = null)
    {
        $this->coupons = $coupons ?: new CouponRepository();
        $this->bookings = $bookings ?: new BookingRepository();
    }

    public function package_sale_price(array $package): float
    {
        $price = max(0, (float) ($package['price'] ?? 0));
        $type = (string) ($package['discount_type'] ?? 'none');
        $value = max(0, (float) ($package['discount_value'] ?? 0));
        if ($type === 'percent') { return round(max(0, $price - ($price * min(100, $value) / 100)), 2); }
        if ($type === 'fixed') { return round(max(0, $price - $value), 2); }
        return round($price, 2);
    }

    public function validate_and_calculate(string $code, array $package, string $customer_email = '')
    {
        $code = $this->coupons->normalize_code($code);
        $package_price = $this->package_sale_price($package);
        if ($code === '') {
            return ['valid' => false, 'coupon' => null, 'base_amount' => $package_price, 'discount_amount' => 0.0, 'final_amount' => $package_price];
        }

        $coupon = $this->coupons->get_by_code($code);
        if (!$coupon || (int) ($coupon['is_active'] ?? 0) !== 1) {
            return new WP_Error('sltr_coupon_invalid', __('Coupon code is not valid.', 'slotera-booking'));
        }

        if (!empty($coupon['expires_at'])) {
            $expires = strtotime((string) $coupon['expires_at'] . ' 23:59:59');
            if ($expires && $expires < current_time('timestamp')) {
                return new WP_Error('sltr_coupon_expired', __('Coupon code has expired.', 'slotera-booking'));
            }
        }

        $usage_limit = (int) ($coupon['usage_limit'] ?? 0);
        if ($usage_limit > 0 && (int) ($coupon['used_count'] ?? 0) >= $usage_limit) {
            return new WP_Error('sltr_coupon_limit', __('Coupon usage limit has been reached.', 'slotera-booking'));
        }

        $allowed_packages = array_filter(array_map('absint', explode(',', (string) ($coupon['package_ids'] ?? ''))));
        $package_id = (int) ($package['id'] ?? 0);
        if ($allowed_packages && !in_array($package_id, $allowed_packages, true)) {
            return new WP_Error('sltr_coupon_package', __('Coupon is not available for this package.', 'slotera-booking'));
        }

        $min = max(0, (float) ($coupon['min_amount'] ?? 0));
        if ($min > 0 && $package_price < $min) {
            return new WP_Error('sltr_coupon_min_amount', __('Coupon minimum amount has not been reached.', 'slotera-booking'));
        }

        $per_email = (int) ($coupon['usage_limit_per_email'] ?? 0);
        if ($per_email > 0 && $customer_email !== '') {
            $used_by_email = $this->bookings->count_coupon_usage_by_email((int) $coupon['id'], $customer_email);
            if ($used_by_email >= $per_email) {
                return new WP_Error('sltr_coupon_email_limit', __('This coupon has already been used by this email.', 'slotera-booking'));
            }
        }

        $value = max(0, (float) ($coupon['discount_value'] ?? 0));
        $discount = (string) ($coupon['discount_type'] ?? 'percent') === 'percent'
            ? $package_price * min(100, $value) / 100
            : $value;
        $discount = round(min($package_price, max(0, $discount)), 2);
        $final = round(max(0, $package_price - $discount), 2);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'base_amount' => $package_price,
            'discount_amount' => $discount,
            'final_amount' => $final,
        ];
    }

    public function record_usage(int $coupon_id): void
    {
        if ($coupon_id > 0) { $this->coupons->increment_usage($coupon_id); }
    }

    public function record_usage_for_booking(array $booking): void
    {
        $booking_id = (int) ($booking['id'] ?? 0);
        $coupon_id = (int) ($booking['coupon_id'] ?? 0);
        if ($booking_id <= 0 || $coupon_id <= 0) { return; }
        if (!empty($booking['coupon_usage_recorded'])) { return; }

        $booking_status = (string) ($booking['status'] ?? '');
        $payment_status = (string) ($booking['payment_status'] ?? '');

        // Coupon usage is a financial side-effect and must only be recorded after
        // a successful payment transition. Pending holds, failed payments, refunds
        // and cancellations keep the coupon reusable until the booking is actually
        // paid/partially paid. The repository update below remains atomic so duplicate
        // webhooks/admin actions can increment the coupon at most once per booking.
        if (!in_array($booking_status, ['confirmed', 'completed'], true)) { return; }
        if (!in_array($payment_status, ['paid', 'partial'], true)) { return; }

        if ($this->bookings->mark_coupon_usage_recorded($booking_id)) {
            $this->record_usage($coupon_id);
        }
    }
}
