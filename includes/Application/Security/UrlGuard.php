<?php

declare(strict_types=1);

namespace Slotera\Application\Security;

if (!defined('ABSPATH')) { exit; }

final class UrlGuard
{
    /**
     * Validates administrator-provided external URLs before they are stored or used for outbound HTTP.
     * Blocks SSRF targets such as localhost, link-local, loopback, private, reserved, multicast and IPv6 ULA ranges.
     *
     * @return true|\WP_Error
     */
    public static function validate_public_https_url(string $url, string $context = 'url')
    {
        $policy = self::public_https_policy($url, $context);
        return $policy instanceof \WP_Error ? $policy : true;
    }

    /**
     * Sends a POST request to an administrator-provided public HTTPS URL with SSRF protections applied
     * at both policy-validation time and HTTP transport time.
     *
     * The URL is validated immediately before dispatch. Delivery is refused unless the cURL HTTPS
     * transport and CURLOPT_RESOLVE are available. The connection is pinned to one of the public IPs
     * returned by validation so a later DNS answer cannot silently rebind the host to private infrastructure.
     *
     * @return array|\WP_Error
     */
    public static function wp_remote_post_public_https(string $url, array $args = [], string $context = 'url')
    {
        $url = esc_url_raw(trim($url));
        $policy = self::public_https_policy($url, $context);
        if ($policy instanceof \WP_Error) {
            return $policy;
        }

        $host = $policy['host'];
        $port = (int) ($policy['port'] ?: 443);
        $pinned_ip = (string) ($policy['ips'][0] ?? '');
        if ($pinned_ip === '') {
            return new \WP_Error('sltr_unresolvable_host', __('URL host could not be resolved.', 'slotera-booking'));
        }
        if (!self::curl_pinning_available()) {
            return new \WP_Error('sltr_secure_http_transport_unavailable', __('Secure webhook delivery requires the cURL HTTPS transport with DNS pinning support.', 'slotera-booking'));
        }
        $resolve_ip = strpos($pinned_ip, ':') !== false ? '[' . $pinned_ip . ']' : $pinned_ip;

        $args['redirection'] = 0;
        $args['reject_unsafe_urls'] = true;

        $preflight = static function ($preempt, $request_args, $request_url) use ($context) {
            $result = self::validate_public_https_url((string) $request_url, $context . '_transport');
            return $result === true ? $preempt : $result;
        };

        $curl_pin = static function ($handle, $request_args, $request_url) use ($host, $port, $resolve_ip, $context): void {
            $result = self::validate_public_https_url((string) $request_url, $context . '_curl_transport');
            if ($result !== true) {
                throw new \RuntimeException('Slotera blocked an unsafe webhook URL immediately before transport dispatch.');
            }
            if (!is_resource($handle) && !(class_exists('CurlHandle') && $handle instanceof \CurlHandle)) {
                throw new \RuntimeException('Slotera webhook delivery did not receive a cURL transport handle.');
            }
            if (curl_setopt($handle, CURLOPT_RESOLVE, [$host . ':' . $port . ':' . $resolve_ip]) !== true) {
                throw new \RuntimeException('Slotera could not apply DNS pinning to the webhook transport.');
            }
        };

        add_filter('pre_http_request', $preflight, 10, 3);
        add_action('http_api_curl', $curl_pin, 10, 3);
        try {
            try {
                return wp_remote_post($url, $args);
            } catch (\Throwable $e) {
                return new \WP_Error('sltr_secure_http_transport_failed', __('Secure webhook delivery could not be initialized.', 'slotera-booking'), ['reason' => $e->getMessage()]);
            }
        } finally {
            remove_filter('pre_http_request', $preflight, 10);
            remove_action('http_api_curl', $curl_pin, 10);
        }
    }

    private static function curl_pinning_available(): bool
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt') || !defined('CURLOPT_RESOLVE')) {
            return false;
        }

        $version = curl_version();
        $protocols = is_array($version) && isset($version['protocols']) && is_array($version['protocols'])
            ? array_map('strtolower', $version['protocols'])
            : [];

        return in_array('https', $protocols, true);
    }

    public static function sanitize_public_https_url(string $url, string $context = 'url'): string
    {
        $url = esc_url_raw(trim($url));
        return self::validate_public_https_url($url, $context) === true ? $url : '';
    }


    /**
     * @return array{host:string,port:int,ips:string[]}|\WP_Error
     */
    private static function public_https_policy(string $url, string $context = 'url')
    {
        $url = trim($url);
        if ($url === '') {
            return new \WP_Error('sltr_empty_url', __('URL is required.', 'slotera-booking'));
        }

        if (!wp_http_validate_url($url)) {
            return new \WP_Error('sltr_invalid_url', __('Enter a valid absolute URL.', 'slotera-booking'));
        }

        $parts = wp_parse_url($url);
        if (!is_array($parts)) {
            return new \WP_Error('sltr_invalid_url', __('Enter a valid absolute URL.', 'slotera-booking'));
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== 'https') {
            return new \WP_Error('sltr_insecure_url', __('Only HTTPS URLs are allowed.', 'slotera-booking'));
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return new \WP_Error('sltr_url_credentials', __('URLs with embedded credentials are not allowed.', 'slotera-booking'));
        }

        $host = strtolower(trim((string) ($parts['host'] ?? ''), "[] \t\n\r\0\x0B."));
        if ($host === '') {
            return new \WP_Error('sltr_missing_host', __('URL host is missing.', 'slotera-booking'));
        }

        if (self::is_blocked_hostname($host)) {
            return new \WP_Error('sltr_blocked_host', __('Local, private, reserved and internal hosts are not allowed.', 'slotera-booking'));
        }

        $ips = self::resolve_host_ips($host);
        if ($ips === []) {
            return new \WP_Error('sltr_unresolvable_host', __('URL host could not be resolved.', 'slotera-booking'));
        }

        foreach ($ips as $ip) {
            if (self::is_blocked_ip($ip)) {
                return new \WP_Error('sltr_blocked_ip', __('URL resolves to a local, private, reserved or internal address.', 'slotera-booking'));
            }
        }

        /**
         * Allows advanced deployments to enforce stricter URL policies.
         * Return a WP_Error from this filter to block the URL.
         */
        $filtered = apply_filters('slotera_validate_external_url', true, $url, $context, $host, $ips);
        if ($filtered instanceof \WP_Error) {
            return $filtered;
        }
        if ($filtered === false) {
            return new \WP_Error('sltr_url_rejected', __('URL was rejected by site policy.', 'slotera-booking'));
        }

        return [
            'host' => $host,
            'port' => isset($parts['port']) ? (int) $parts['port'] : 443,
            'ips' => $ips,
        ];
    }

    private static function is_blocked_hostname(string $host): bool
    {
        if ($host === 'localhost' || substr($host, -10) === '.localhost' || substr($host, -6) === '.local' || substr($host, -9) === '.internal') {
            return true;
        }

        $decoded = function_exists('idn_to_ascii') ? idn_to_ascii($host, defined('IDNA_DEFAULT') ? IDNA_DEFAULT : 0, defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 1) : false;
        if (is_string($decoded) && $decoded !== '' && $decoded !== $host) {
            return self::is_blocked_hostname($decoded);
        }

        return self::is_blocked_ip($host);
    }

    /** @return string[] */
    private static function resolve_host_ips(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];
        $records = function_exists('dns_get_record') ? @dns_get_record($host, DNS_A + DNS_AAAA) : false;
        if (is_array($records)) {
            foreach ($records as $record) {
                if (!empty($record['ip'])) { $ips[] = (string) $record['ip']; }
                if (!empty($record['ipv6'])) { $ips[] = (string) $record['ipv6']; }
            }
        }

        if ($ips === []) {
            $fallback = @gethostbynamel($host);
            if (is_array($fallback)) { $ips = array_merge($ips, $fallback); }
        }

        return array_values(array_unique(array_filter($ips)));
    }

    private static function is_blocked_ip(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        if (strpos($ip, ':') !== false) {
            $packed = @inet_pton($ip);
            if ($packed === false) { return true; }
            $hex = bin2hex($packed);
            // IPv6 loopback/unspecified, IPv4-mapped, unique-local fc00::/7, link-local fe80::/10, multicast ff00::/8.
            if ($ip === '::1' || $ip === '::' || strpos($hex, '00000000000000000000ffff') === 0) { return true; }
            $first = hexdec(substr($hex, 0, 2));
            $second = hexdec(substr($hex, 2, 2));
            if (($first & 0xfe) === 0xfc || ($first === 0xfe && ($second & 0xc0) === 0x80) || $first === 0xff) { return true; }
        }

        return false;
    }
}
