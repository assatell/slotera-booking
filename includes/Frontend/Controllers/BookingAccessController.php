<?php

declare(strict_types=1);

namespace Slotera\Frontend\Controllers;

use Slotera\Application\Services\BookingAccessTokenService;
use Slotera\Infrastructure\Repositories\BookingRepository;

if (!defined('ABSPATH')) { exit; }

final class BookingAccessController
{
    public function register(): void
    {
        add_action('template_redirect', [$this, 'exchange_url_credential'], 1);
    }

    public function exchange_url_credential(): void
    {
        $booking_id = isset($_GET['booking_id']) ? absint(wp_unslash((string) $_GET['booking_id'])) : 0;
        $code = isset($_GET['sltr_access_code']) ? sanitize_text_field((string) wp_unslash($_GET['sltr_access_code'])) : '';
        $legacy = isset($_GET['access_token']) ? sanitize_text_field((string) wp_unslash($_GET['access_token'])) : '';
        if ($legacy === '' && isset($_GET['sltr_access_token'])) {
            $legacy = sanitize_text_field((string) wp_unslash($_GET['sltr_access_token']));
        }
        if ($booking_id <= 0 || ($code === '' && $legacy === '')) { return; }

        $booking = (new BookingRepository())->get_by_id($booking_id);
        $service = new BookingAccessTokenService();
        $ok = $booking && ($code !== '' ? $service->exchange_code($booking, $code) : $service->exchange_legacy_token($booking, $legacy));

        $request_uri = (string) wp_unslash($_SERVER['REQUEST_URI'] ?? '/');
        $clean = remove_query_arg(['sltr_access_code', 'access_token', 'sltr_access_token'], home_url($request_uri));
        if (!$ok) { $clean = add_query_arg('sltr_access', 'denied', $clean); }
        wp_safe_redirect($clean, 303);
        exit;
    }
}
