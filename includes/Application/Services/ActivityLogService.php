<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Events;
use Slotera\Infrastructure\Repositories\ActivityLogRepository;

if (!defined('ABSPATH')) { exit; }

final class ActivityLogService
{
    private ActivityLogRepository $repo;

    public function __construct(?ActivityLogRepository $repo = null)
    {
        $this->repo = $repo ?: new ActivityLogRepository();
    }

    public function register_hooks(): void
    {
        $map = [
            Events::BOOKING_CREATED => ['booking_created', 'success', 'Booking created.'],
            Events::BOOKING_CONFIRMED => ['booking_confirmed', 'success', 'Booking confirmed.'],
            Events::BOOKING_CANCELLED => ['booking_cancelled', 'warning', 'Booking cancelled.'],
            Events::BOOKING_COMPLETED => ['booking_completed', 'success', 'Booking completed.'],
            Events::BOOKING_RESCHEDULED => ['booking_rescheduled', 'info', 'Booking rescheduled.'],
            Events::BOOKING_PACKAGE_CHANGED => ['booking_package_changed', 'info', 'Booking package changed.'],
            Events::PAYMENT_PENDING => ['payment_pending', 'info', 'Payment pending.'],
            Events::PAYMENT_PAID => ['payment_paid', 'success', 'Payment paid.'],
            Events::PAYMENT_COMPLETED => ['payment_completed', 'success', 'Payment completed.'],
            Events::PAYMENT_REFUNDED => ['payment_refunded', 'info', 'Payment refunded.'],
            Events::PAYMENT_FAILED => ['payment_failed', 'error', 'Payment failed.'],
            Events::INVOICE_CREATED => ['invoice_created', 'success', 'Invoice created.'],
            Events::CUSTOMER_LOGIN => ['customer_login', 'info', 'Customer login.'],
            Events::EMAIL_SENT => ['email_sent', 'success', 'Email sent.'],
            Events::EMAIL_FAILED => ['email_failed', 'error', 'Email failed.'],
        ];

        foreach ($map as $hook => $meta) {
            add_action($hook, function (array $payload) use ($meta): void {
                [$event, $status, $message] = $meta;
                $this->log([
                    'object_type' => 'booking',
                    'object_id' => (int) ($payload['booking_id'] ?? ($payload['booking']['id'] ?? 0)),
                    'event' => $event,
                    'status' => $status,
                    'message' => $message,
                    'gateway' => (string) ($payload['gateway'] ?? ($payload['booking']['payment_gateway'] ?? '')),
                    'error_message' => (string) ($payload['error'] ?? ''),
                    'payload' => $payload,
                ]);
            }, 10, 1);
        }
    }

    public function log(array $data): int
    {
        $data['actor_type'] = $data['actor_type'] ?? (is_user_logged_in() ? 'user' : 'system');
        $data['actor_id'] = $data['actor_id'] ?? get_current_user_id();
        return $this->repo->create($data);
    }

    public function payment(int $booking_id, string $event, string $gateway, string $status, string $message, array $payload = [], string $error = ''): int
    {
        return $this->log([
            'object_type' => 'booking',
            'object_id' => $booking_id,
            'event' => $event,
            'status' => $status,
            'gateway' => $gateway,
            'message' => $message,
            'error_message' => $error,
            'payload' => $payload,
        ]);
    }
}
