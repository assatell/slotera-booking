<?php

declare(strict_types=1);

namespace Slotera\Application\Services\BookingModes;

use Slotera\Application\Services\BookingCreationContext;
use Slotera\Application\Services\BookingLifecycleService;

if (!defined('ABSPATH')) { exit; }

abstract class AbstractBookingModeHandler
{
    protected BookingCreationContext $context;

    public function __construct(BookingCreationContext $context)
    {
        $this->context = $context;
    }

    abstract public function create(array $package, array $customer, array $data);

    protected function uses_online_payment_hold(string $gateway_id): bool
    {
        $gateway_id = sanitize_key($gateway_id);
        if ($gateway_id === '' || in_array($gateway_id, ['manual', 'bank_transfer'], true)) {
            return false;
        }
        if (strpos($gateway_id, 'custom_') === 0) {
            return false;
        }
        return true;
    }

    protected function notify_after_successful_creation(int $booking_id, ?array $booking, array $package): void
    {
        $this->context->notifications->booking_created($booking_id, $booking, $package);
        if ($booking && ($booking['status'] ?? '') === BookingLifecycleService::STATUS_CONFIRMED) {
            $this->context->notifications->booking_confirmed($booking_id, $booking, $package);
        } elseif ($booking && ($booking['payment_status'] ?? '') === 'pending') {
            $this->context->notifications->payment_pending($booking_id, $booking, $package);
        }
    }

    protected function normalize_time(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^\d{2}:\d{2}$/', $time)) { return $time . ':00'; }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) { return $time; }
        return '';
    }

    protected function is_valid_date(string $date): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date;
    }

    protected function is_past_slot(string $date, string $start): bool
    {
        try {
            $slot = new \DateTimeImmutable($date . ' ' . $start, wp_timezone());
            return $slot < new \DateTimeImmutable('now', wp_timezone());
        } catch (\Throwable $e) {
            return true;
        }
    }
}
