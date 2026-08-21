<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Events;

if (!defined('ABSPATH')) { exit; }

final class NotificationService
{
    public function booking_created(int $booking_id, ?array $booking, ?array $package = null): void
    {
        Events::dispatch(Events::BOOKING_CREATED, ['booking_id' => $booking_id, 'booking' => $booking, 'package' => $package]);
    }

    public function booking_confirmed(int $booking_id, ?array $booking, ?array $package = null): void
    {
        Events::dispatch(Events::BOOKING_CONFIRMED, ['booking_id' => $booking_id, 'booking' => $booking, 'package' => $package]);
    }

    public function booking_cancelled(int $booking_id, ?array $booking): void
    {
        Events::dispatch(Events::BOOKING_CANCELLED, ['booking_id' => $booking_id, 'booking' => $booking]);
    }

    public function booking_completed(int $booking_id, ?array $booking): void
    {
        Events::dispatch(Events::BOOKING_COMPLETED, ['booking_id' => $booking_id, 'booking' => $booking]);
    }

    public function payment_pending(int $booking_id, ?array $booking, ?array $package = null): void
    {
        Events::dispatch(Events::PAYMENT_PENDING, ['booking_id' => $booking_id, 'booking' => $booking, 'package' => $package]);
    }

    public function payment_completed(int $booking_id, ?array $booking, string $gateway = '', array $result = []): void
    {
        Events::dispatch(Events::PAYMENT_COMPLETED, ['booking_id' => $booking_id, 'booking' => $booking, 'gateway' => $gateway, 'result' => $result]);
    }

    public function payment_failed(int $booking_id, ?array $booking, string $gateway = '', array $result = [], string $error = ''): void
    {
        Events::dispatch(Events::PAYMENT_FAILED, ['booking_id' => $booking_id, 'booking' => $booking, 'gateway' => $gateway, 'result' => $result, 'error' => $error]);
    }
}
