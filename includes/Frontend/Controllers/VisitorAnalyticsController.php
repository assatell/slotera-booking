<?php

declare(strict_types=1);

namespace Slotera\Frontend\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\VisitorAnalyticsService;
use Slotera\Infrastructure\Http\ClientIpResolver;
use Slotera\Infrastructure\Http\RateLimiter;

if (!defined('ABSPATH')) { exit; }

final class VisitorAnalyticsController
{
    private const RATE_LIMIT_WINDOW = 300;
    private const RATE_LIMIT_PER_IP = 30;
    private const RATE_LIMIT_PER_SESSION = 10;

    private RequestValidator $request;
    private VisitorAnalyticsService $analytics;

    public function __construct(?RequestValidator $request = null, ?VisitorAnalyticsService $analytics = null)
    {
        $this->request = $request ?? new RequestValidator();
        $this->analytics = $analytics ?? new VisitorAnalyticsService();
    }

    public function register(): void
    {
        add_action('wp_ajax_sltr_track_visitor_event', [$this, 'track']);
        add_action('wp_ajax_nopriv_sltr_track_visitor_event', [$this, 'track']);
    }

    public function track(): void
    {
        $this->request->verify_ajax_nonce('sltr_frontend_booking');
        if (!$this->analytics->is_collection_allowed()) {
            wp_send_json_error(['message' => __('Visitor analytics collection is disabled.', 'slotera-booking')], 403);
        }

        $session_id = '';
        if ($this->analytics->is_session_collection_allowed()) {
            $session_id = substr($this->request->post_text('session_id'), 0, 96);
            if (!preg_match('/^[A-Za-z0-9_-]{8,96}$/', $session_id)) {
                wp_send_json_error(['message' => __('Invalid analytics session.', 'slotera-booking')], 400);
            }
        }

        // IP is used only as an ephemeral abuse-control key. It is not written to analytics storage.
        $ip = ClientIpResolver::get_client_ip();
        $ip_attempts = RateLimiter::increment('visitor_analytics_ip', $ip !== '' ? $ip : 'unknown', self::RATE_LIMIT_WINDOW);
        $session_attempts = $session_id !== ''
            ? RateLimiter::increment('visitor_analytics_session', $session_id, self::RATE_LIMIT_WINDOW)
            : 0;
        if ($ip_attempts > self::RATE_LIMIT_PER_IP || $session_attempts > self::RATE_LIMIT_PER_SESSION) {
            wp_send_json_error(['message' => __('Too many analytics events.', 'slotera-booking')], 429);
        }

        $page_url = substr($this->request->post_text('page_url'), 0, 2048);
        if (!$this->is_same_site_url($page_url)) {
            wp_send_json_error(['message' => __('Invalid analytics page URL.', 'slotera-booking')], 400);
        }

        $payload = [
            'session_id' => $session_id,
            'page_url' => $page_url,
            'page_title' => substr($this->request->post_text('page_title'), 0, 255),
            'page_type' => $this->request->post_text('page_type'),
            'package_id' => $this->request->post_int('package_id'),
            'post_id' => $this->request->post_int('post_id'),
            'referrer' => substr($this->request->post_text('referrer'), 0, 2048),
            'utm_source' => substr($this->request->post_text('utm_source'), 0, 100),
            'utm_medium' => substr($this->request->post_text('utm_medium'), 0, 100),
            'utm_campaign' => substr($this->request->post_text('utm_campaign'), 0, 150),
            'duration_seconds' => $this->request->post_int('duration_seconds'),
            'viewport_events' => $this->request->post_int('viewport_events'),
            'booking_started' => $this->request->post_int('booking_started'),
            'booking_created' => $this->request->post_int('booking_created'),
            'final_event' => 1,
        ];
        $ok = $this->analytics->record($payload);
        wp_send_json_success(['recorded' => (bool) $ok]);
    }

    private function is_same_site_url(string $url): bool
    {
        $candidate = wp_parse_url($url);
        $home = wp_parse_url(home_url('/'));
        if (!is_array($candidate) || !is_array($home)) {
            return false;
        }

        $scheme = strtolower((string) ($candidate['scheme'] ?? ''));
        $home_scheme = strtolower((string) ($home['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($candidate['host'] ?? ''), '.'));
        $home_host = strtolower(rtrim((string) ($home['host'] ?? ''), '.'));
        if ($scheme === '' || $host === '' || $scheme !== $home_scheme || !hash_equals($home_host, $host)) {
            return false;
        }

        $candidate_port = (int) ($candidate['port'] ?? ($scheme === 'https' ? 443 : 80));
        $home_port = (int) ($home['port'] ?? ($home_scheme === 'https' ? 443 : 80));
        return $candidate_port === $home_port;
    }
}
