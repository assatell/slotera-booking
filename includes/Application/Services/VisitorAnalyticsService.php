<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Database;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

/**
 * Privacy-first first-party analytics for Slotera pages.
 *
 * Basic analytics stores page/funnel events without a visitor identifier, browser
 * storage identifier, or IP-derived analytics value. Optional session analytics
 * may store only an HMAC of the browser session id after the consent gate passes.
 */
final class VisitorAnalyticsService
{
    private const UNKNOWN_CITY = 'Unknown city';

    public function is_collection_allowed(): bool
    {
        $settings = (new SettingsRepository())->all();
        if (empty($settings['privacy_visitor_analytics_enabled'])) {
            return false;
        }
        if (empty($settings['privacy_visitor_analytics_require_consent'])) {
            return true;
        }
        return $this->consent_granted($settings);
    }

    public function is_session_collection_allowed(): bool
    {
        $settings = (new SettingsRepository())->all();
        if (empty($settings['privacy_visitor_analytics_enabled']) || empty($settings['privacy_visitor_session_analytics_enabled'])) {
            return false;
        }

        // Session analytics always requires an explicit opt-in signal from the site's consent manager.
        return $this->consent_granted($settings);
    }

    /** @param array<string,mixed> $settings */
    private function consent_granted(array $settings): bool
    {
        return (bool) apply_filters(
            'slotera_visitor_analytics_consent_granted',
            false,
            $settings
        );
    }

    /** @param array<string,mixed> $payload */
    public function record(array $payload): bool
    {
        global $wpdb;

        $duration = max(0, min(86400, (int) ($payload['duration_seconds'] ?? 0)));
        $viewport_events = max(0, min(1000, (int) ($payload['viewport_events'] ?? 0)));
        $package_id = max(0, (int) ($payload['package_id'] ?? 0));
        $post_id = max(0, (int) ($payload['post_id'] ?? 0));
        $booking_started = !empty($payload['booking_started']) ? 1 : 0;
        $booking_created = !empty($payload['booking_created']) ? 1 : 0;
        $final_event = !empty($payload['final_event']) ? 1 : 0;
        $bounced = $duration < 10 && $booking_started === 0 && $booking_created === 0 ? 1 : 0;
        $geo = $this->geo_from_server();
        $now = current_time('mysql', true);

        // Anonymous basic analytics has no cross-page identifier. Session analytics
        // stores only a one-way HMAC and is enabled only after its consent gate.
        $session_hash = '';
        if ($this->is_session_collection_allowed()) {
            $session_hash = $this->hash_value((string) ($payload['session_id'] ?? ''));
        }

        $page_url = $this->strip_url_query((string) ($payload['page_url'] ?? ''));
        if ($page_url === '') {
            $page_url = home_url('/');
        }

        $page_type = sanitize_key((string) ($payload['page_type'] ?? 'page'));
        if (!in_array($page_type, ['service', 'booking', 'category', 'page'], true)) {
            $page_type = 'page';
        }

        return false !== $wpdb->insert(Database::visitor_events_table(), [
            'session_hash' => $session_hash,
            // Kept for schema compatibility only. Analytics never stores an IP hash.
            'ip_prefix_hash' => '',
            'city' => $geo['city'],
            'country' => $geo['country'],
            'page_url' => $page_url,
            'page_title' => sanitize_text_field((string) ($payload['page_title'] ?? '')),
            'page_type' => $page_type,
            'package_id' => $package_id,
            'post_id' => $post_id,
            'referrer' => $this->sanitize_referrer((string) ($payload['referrer'] ?? '')),
            'utm_source' => sanitize_text_field((string) ($payload['utm_source'] ?? '')),
            'utm_medium' => sanitize_text_field((string) ($payload['utm_medium'] ?? '')),
            'utm_campaign' => sanitize_text_field((string) ($payload['utm_campaign'] ?? '')),
            'duration_seconds' => $duration,
            'viewport_events' => $viewport_events,
            'booking_started' => $booking_started,
            'booking_created' => $booking_created,
            'bounced' => $bounced,
            'exited' => $final_event,
            'created_at' => $now,
            'updated_at' => $now,
        ], ['%s','%s','%s','%s','%s','%s','%s','%d','%d','%s','%s','%s','%s','%d','%d','%d','%d','%d','%d','%s','%s']);
    }

    /** @return array<string,string> */
    private function geo_from_server(): array
    {
        $city = '';
        foreach (['HTTP_CF_IPCITY', 'HTTP_X_APPENGINE_CITY', 'GEOIP_CITY', 'MM_CITY_NAME'] as $key) {
            if (!empty($_SERVER[$key])) {
                $city = sanitize_text_field((string) wp_unslash($_SERVER[$key]));
                break;
            }
        }
        $country = '';
        foreach (['HTTP_CF_IPCOUNTRY', 'HTTP_X_APPENGINE_COUNTRY', 'GEOIP_COUNTRY_CODE', 'MM_COUNTRY_CODE'] as $key) {
            if (!empty($_SERVER[$key])) {
                $country = strtoupper(substr(sanitize_text_field((string) wp_unslash($_SERVER[$key])), 0, 2));
                break;
            }
        }
        return [
            'city' => $city !== '' ? $city : self::UNKNOWN_CITY,
            'country' => $country,
        ];
    }

    private function strip_url_query(string $url): string
    {
        $parts = wp_parse_url(esc_url_raw($url));
        if (!is_array($parts) || empty($parts['host'])) { return ''; }
        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (!in_array($scheme, ['http', 'https'], true)) { return ''; }
        $host = strtolower(rtrim((string) $parts['host'], '.'));
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = (string) ($parts['path'] ?? '/');
        return esc_url_raw($scheme . '://' . $host . $port . ($path !== '' ? $path : '/'));
    }

    private function sanitize_referrer(string $url): string
    {
        $parts = wp_parse_url(esc_url_raw($url));
        if (!is_array($parts) || empty($parts['host'])) { return ''; }
        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (!in_array($scheme, ['http', 'https'], true)) { return ''; }
        $host = strtolower(rtrim((string) $parts['host'], '.'));
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

        $home = wp_parse_url(home_url('/'));
        $home_host = is_array($home) ? strtolower(rtrim((string) ($home['host'] ?? ''), '.')) : '';
        $path = $host === $home_host ? (string) ($parts['path'] ?? '/') : '/';
        return esc_url_raw($scheme . '://' . $host . $port . ($path !== '' ? $path : '/'));
    }

    private function hash_value(string $value): string
    {
        if ($value === '') { return ''; }
        return hash_hmac('sha256', $value, wp_salt('nonce'));
    }
}
