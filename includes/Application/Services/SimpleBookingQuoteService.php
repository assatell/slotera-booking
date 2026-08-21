<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\PackageRepository;

if (!defined('ABSPATH')) { exit; }

final class SimpleBookingQuoteService
{
    private PricingAdjustmentService $pricing;
    private CouponService $coupons;

    public function __construct(?PricingAdjustmentService $pricing = null, ?CouponService $coupons = null)
    {
        $this->pricing = $pricing ?? new PricingAdjustmentService();
        $this->coupons = $coupons ?? new CouponService();
    }

    /**
     * @return array|\WP_Error
     */
    public function quote(array $package, array $extra_ids = [], string $coupon_code = '', string $customer_email = '')
    {
        $selected = $this->selected_extras($package, $extra_ids);
        $extras_amount = array_sum(array_map(static fn(array $item): float => (float) ($item['line_amount'] ?? 0), $selected));
        $package_base = max(0, (float) ($package['price'] ?? 0));
        $pricing_base = round($package_base + $extras_amount, 2);

        $dynamic = $this->pricing->apply_dynamic($package, $pricing_base, ['booking_date' => current_time('Y-m-d')]);
        $dynamic_amount = (float) ($dynamic['dynamic_amount'] ?? $pricing_base);
        $package_for_pricing = array_merge($package, ['price' => $dynamic_amount]);
        $sale_price = $this->coupons->package_sale_price($package_for_pricing);
        $package_discount_amount = round(max(0, $dynamic_amount - $sale_price), 2);

        $coupon_code = sanitize_text_field($coupon_code);
        $coupon_id = 0;
        $coupon_discount_amount = 0.0;
        $after_coupon = $sale_price;
        if ($coupon_code !== '') {
            $coupon_result = $this->coupons->validate_and_calculate($coupon_code, $package_for_pricing, sanitize_email($customer_email));
            if (is_wp_error($coupon_result)) { return $coupon_result; }
            if (is_array($coupon_result) && !empty($coupon_result['valid'])) {
                $coupon = $coupon_result['coupon'] ?? [];
                $coupon_id = (int) ($coupon['id'] ?? 0);
                $coupon_code = (string) ($coupon['code'] ?? $coupon_code);
                $coupon_discount_amount = (float) ($coupon_result['discount_amount'] ?? 0);
                $after_coupon = (float) ($coupon_result['final_amount'] ?? $sale_price);
            } else {
                $coupon_code = '';
            }
        }

        $tax = $this->pricing->apply_tax($package, $after_coupon);
        $tax_amount = (float) ($tax['tax_amount'] ?? 0);
        $final_amount = (float) ($tax['total_amount'] ?? $after_coupon);

        return [
            'package_base_amount' => round($package_base, 2),
            'pricing_base_amount' => round($pricing_base, 2),
            'extras_amount' => round($extras_amount, 2),
            'selected_extras' => $selected,
            'dynamic_adjustment_amount' => (float) ($dynamic['dynamic_adjustment_amount'] ?? 0),
            'dynamic_label' => (string) ($dynamic['dynamic_label'] ?? ''),
            'package_discount_amount' => $package_discount_amount,
            'coupon_id' => $coupon_id,
            'coupon_code' => $coupon_code,
            'coupon_discount_amount' => round($coupon_discount_amount, 2),
            'tax_amount' => round($tax_amount, 2),
            'final_amount' => round($final_amount, 2),
        ];
    }

    public function extras(array $package): array
    {
        $json = (string) ($package['extra_services_json'] ?? '');
        if ($json === '') {
            $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
            if (is_array($configs)) {
                $json = (string) ($configs['simple']['extra_services_json'] ?? '');
            }
        }
        $items = json_decode($json, true);
        if (!is_array($items)) { return []; }
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['active'])) { continue; }
            $id = absint($item['id'] ?? 0);
            $name = sanitize_text_field((string) ($item['name'] ?? ''));
            if ($id <= 0 || $name === '') { continue; }
            $type = sanitize_key((string) ($item['price_type'] ?? 'once'));
            if (!in_array($type, ['once', 'per_guest'], true)) { $type = 'once'; }
            $out[] = [
                'id' => $id,
                'name' => $name,
                'description' => sanitize_text_field((string) ($item['description'] ?? '')),
                'price' => max(0, (float) ($item['price'] ?? 0)),
                'price_type' => $type,
            ];
        }
        return $out;
    }

    private function selected_extras(array $package, array $extra_ids): array
    {
        $wanted = array_values(array_unique(array_filter(array_map('absint', $extra_ids))));
        if (!$wanted) { return []; }
        $selected = [];
        foreach ($this->extras($package) as $item) {
            if (!in_array((int) $item['id'], $wanted, true)) { continue; }
            // Booking Request currently has no guest-count field, so per_guest is one unit.
            $quantity = 1;
            $item['quantity'] = $quantity;
            $item['line_amount'] = round((float) $item['price'] * $quantity, 2);
            $selected[] = $item;
        }
        return $selected;
    }
}
