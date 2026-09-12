<?php
declare(strict_types=1);
namespace Slotera\Application\Services;
if (!defined('ABSPATH')) { exit; }

final class UpdateService
{
    private const API_URL = 'https://license-api-test.getslotera.com/wp-json/slotera/v1/update';
    private const CACHE_KEY = 'sltr_verified_update_v1';

    public function register_hooks(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'injectUpdate']);
        add_filter('upgrader_pre_download', [$this, 'verifyDownload'], 10, 4);
    }

    public function injectUpdate($transient)
    {
        if (!is_object($transient) || empty($transient->checked) || !isset($transient->checked[SLTR_PLUGIN_BASENAME])) { return $transient; }
        $payload = $this->fetchVerified();
        if ($payload === null || version_compare((string) $payload['version'], SLTR_VERSION, '<=')) { return $transient; }
        $item = new \stdClass();
        $item->id = sltr_update_uri();
        $item->slug = 'slotera-booking';
        $item->plugin = SLTR_PLUGIN_BASENAME;
        $item->new_version = (string) $payload['version'];
        $item->url = 'https://getslotera.com/';
        $item->package = (string) $payload['package_url'];
        $item->requires = (string) ($payload['requires_wp'] ?? SLTR_MINIMUM_WP_VERSION);
        $item->requires_php = (string) ($payload['requires_php'] ?? SLTR_MINIMUM_PHP_VERSION);
        $item->tested = (string) ($payload['tested_wp'] ?? '');
        $transient->response[SLTR_PLUGIN_BASENAME] = $item;
        return $transient;
    }

    public function verifyDownload($reply, string $package, $upgrader, array $hookExtra)
    {
        if ($reply !== false || (string) ($hookExtra['plugin'] ?? '') !== SLTR_PLUGIN_BASENAME) { return $reply; }
        $payload = get_site_transient(self::CACHE_KEY);
        if (!is_array($payload) || !hash_equals((string) ($payload['package_url'] ?? ''), $package)) {
            return new \WP_Error('sltr_update_metadata_missing', __('Verified Slotera update metadata is unavailable.', 'slotera-booking'));
        }
        if (!function_exists('download_url')) { require_once ABSPATH . 'wp-admin/includes/file.php'; }
        $file = download_url($package, 300);
        if (is_wp_error($file)) { return $file; }
        $actual = hash_file('sha256', $file);
        if (!is_string($actual) || !hash_equals((string) $payload['package_sha256'], strtolower($actual))) {
            wp_delete_file($file);
            return new \WP_Error('sltr_update_hash_mismatch', __('Slotera update archive verification failed.', 'slotera-booking'));
        }
        return $file;
    }

    private function fetchVerified(): ?array
    {
        $cached = get_site_transient(self::CACHE_KEY);
        if (is_array($cached)) { return $cached; }
        $response = wp_safe_remote_post(self::API_URL, [
            'timeout' => 10, 'redirection' => 0, 'limit_response_size' => 65536,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'plugin' => 'slotera-booking', 'channel' => 'stable', 'version' => SLTR_VERSION,
                'wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION,
            ]),
            'data_format' => 'body',
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) { return null; }
        $envelope = json_decode((string) wp_remote_retrieve_body($response), true);
        $payload = is_array($envelope) ? (new UpdateEnvelopeVerifier())->verify($envelope) : null;
        if ($payload === null) { return null; }
        set_site_transient(self::CACHE_KEY, $payload, 12 * HOUR_IN_SECONDS);
        return $payload;
    }
}
