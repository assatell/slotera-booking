<?php

declare(strict_types=1);

namespace Slotera\Infrastructure\Http;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolves the real client IP without trusting spoofable proxy headers from
 * direct client requests.
 */
final class ClientIpResolver
{
    /**
     * Returns the best client IP for the current request.
     *
     * Proxy headers are used only when REMOTE_ADDR belongs to a trusted proxy
     * range. Otherwise, REMOTE_ADDR is the only trusted source.
     */
    public static function get_client_ip(): string
    {
        $remote_addr = self::sanitize_ip((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote_addr === '') {
            return '';
        }

        if (!self::is_trusted_proxy($remote_addr)) {
            return $remote_addr;
        }

        foreach (self::proxy_header_candidates() as $candidate) {
            $ip = self::sanitize_ip($candidate);
            if ($ip !== '') {
                return $ip;
            }
        }

        return $remote_addr;
    }

    public static function is_trusted_proxy(string $ip): bool
    {
        $ip = self::sanitize_ip($ip);
        if ($ip === '') {
            return false;
        }

        foreach (self::trusted_proxy_ranges() as $range) {
            $range = trim((string) $range);
            if ($range === '') {
                continue;
            }

            if (self::ip_matches_range($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private static function proxy_header_candidates(): array
    {
        $candidates = [];

        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $candidates[] = (string) wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']);
        }

        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $candidates[] = (string) wp_unslash($_SERVER['HTTP_X_REAL_IP']);
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = (string) wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']);
            $resolved = self::resolve_forwarded_for($forwarded);
            if ($resolved !== '') {
                $candidates[] = $resolved;
            }
        }

        /**
         * Allows hosts to add custom proxy headers. Values are only consulted
         * after REMOTE_ADDR has been verified as a trusted proxy.
         *
         * @param string[] $candidates Candidate IP values from proxy headers.
         */
        $filtered = apply_filters('slotera_client_ip_proxy_header_candidates', $candidates);
        return is_array($filtered) ? array_map('strval', $filtered) : $candidates;
    }


    /**
     * Resolves X-Forwarded-For as a proxy chain, not as a client-controlled
     * list. Proxies append addresses from left to right, so the chain must be
     * evaluated from the right (the hop nearest REMOTE_ADDR) towards the
     * original client. Trusted proxy hops are skipped and the first untrusted
     * address is returned.
     *
     * This prevents a client from bypassing IP-based controls by prepending a
     * forged address when the edge proxy preserves an incoming header.
     */
    private static function resolve_forwarded_for(string $forwarded): string
    {
        $chain = [];
        foreach (explode(',', $forwarded) as $part) {
            $ip = self::sanitize_ip($part);
            if ($ip !== '') {
                $chain[] = $ip;
            }
        }

        if ($chain === []) {
            return '';
        }

        for ($index = count($chain) - 1; $index >= 0; --$index) {
            if (!self::is_trusted_proxy($chain[$index])) {
                return $chain[$index];
            }
        }

        // Every forwarded hop is trusted. Return the farthest hop rather than
        // falling back to REMOTE_ADDR, which is known to be the edge proxy.
        return $chain[0];
    }

    /**
     * @return string[]
     */
    private static function trusted_proxy_ranges(): array
    {
        $defaults = [
            '127.0.0.1',
            '::1',
        ];

        $configured = self::configured_trusted_proxy_ranges();
        $ranges = array_values(array_unique(array_merge($defaults, $configured)));

        /**
         * Filters trusted proxy IPs/CIDR ranges. Add your load balancer,
         * reverse proxy, CDN or ingress IP ranges here. Proxy headers such as
         * X-Forwarded-For are ignored unless REMOTE_ADDR matches one of these
         * ranges.
         *
         * @param string[] $ranges Trusted proxy IPs/CIDR ranges.
         */
        $filtered = apply_filters('slotera_trusted_proxies', $ranges);
        return is_array($filtered) ? array_values(array_unique(array_map('strval', $filtered))) : $ranges;
    }

    /**
     * @return string[]
     */
    private static function configured_trusted_proxy_ranges(): array
    {
        try {
            $settings = (new SettingsRepository())->all();
        } catch (\Throwable $e) {
            return [];
        }

        $raw = (string) ($settings['security_trusted_proxies'] ?? '');
        if ($raw === '') {
            return [];
        }

        $ranges = [];
        foreach ((array) preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim((string) $line);
            if ($line !== '' && self::is_valid_ip_or_cidr($line)) {
                $ranges[] = $line;
            }
        }

        return $ranges;
    }

    private static function is_valid_ip_or_cidr(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (strpos($value, '/') === false) {
            return self::sanitize_ip($value) !== '';
        }

        [$subnet, $mask] = array_pad(explode('/', $value, 2), 2, '');
        $subnet = self::sanitize_ip($subnet);
        if ($subnet === '' || !is_numeric($mask)) {
            return false;
        }

        $bits = strpos($subnet, ':') !== false ? 128 : 32;
        $mask = (int) $mask;
        return $mask >= 0 && $mask <= $bits;
    }

    private static function sanitize_ip(string $ip): string
    {
        $ip = trim(sanitize_text_field($ip));
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    private static function ip_matches_range(string $ip, string $range): bool
    {
        $range = trim($range);
        if ($range === '') {
            return false;
        }

        if (strpos($range, '/') === false) {
            return self::sanitize_ip($range) === $ip;
        }

        [$subnet, $mask] = array_pad(explode('/', $range, 2), 2, '');
        $subnet = self::sanitize_ip($subnet);
        if ($subnet === '' || !is_numeric($mask)) {
            return false;
        }

        $ip_bin = @inet_pton($ip);
        $subnet_bin = @inet_pton($subnet);
        if ($ip_bin === false || $subnet_bin === false || strlen($ip_bin) !== strlen($subnet_bin)) {
            return false;
        }

        $bits = strlen($ip_bin) * 8;
        $mask = (int) $mask;
        if ($mask < 0 || $mask > $bits) {
            return false;
        }

        $full_bytes = intdiv($mask, 8);
        $remaining_bits = $mask % 8;

        if ($full_bytes > 0 && substr($ip_bin, 0, $full_bytes) !== substr($subnet_bin, 0, $full_bytes)) {
            return false;
        }

        if ($remaining_bits === 0) {
            return true;
        }

        $byte_mask = (0xFF << (8 - $remaining_bits)) & 0xFF;
        return (ord($ip_bin[$full_bytes]) & $byte_mask) === (ord($subnet_bin[$full_bytes]) & $byte_mask);
    }
}
