<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class RedirectService
{
    private const OPTION = 'sltr_seo_redirects';
    private const MAX_REDIRECTS = 200;

    public function register_hooks(): void
    {
        add_action('sltr_data_changed', [$this, 'maybe_store_slug_redirect'], 20, 2);
        add_action('template_redirect', [$this, 'maybe_redirect_old_slug'], 1);
    }

    public function maybe_store_slug_redirect(string $event, ?array $payload = null): void
    {
        if (!in_array($event, ['package_updated', 'category_updated'], true)) {
            return;
        }

        $old = sanitize_title((string) ($payload['old_slug'] ?? ''));
        $new = sanitize_title((string) ($payload['new_slug'] ?? ''));
        $page_id = (int) ($payload['page_id'] ?? 0);

        if ($old === '' || $new === '' || $old === $new || $page_id <= 0) {
            return;
        }

        $target = get_permalink($page_id);
        if (!is_string($target) || $target === '') {
            return;
        }

        $redirects = $this->redirects();
        $redirects[$old] = [
            'target' => esc_url_raw($target),
            'created_at' => current_time('mysql'),
            'hits' => 0,
            'last_hit_at' => '',
        ];

        if (count($redirects) > self::MAX_REDIRECTS) {
            $redirects = array_slice($redirects, -self::MAX_REDIRECTS, null, true);
        }

        update_option(self::OPTION, $redirects, false);
    }

    public function maybe_redirect_old_slug(): void
    {
        if (!is_404()) {
            return;
        }

        $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        if ($path === '') {
            return;
        }

        $slug = sanitize_title(basename($path));
        if ($slug === '') {
            return;
        }

        $redirects = $this->redirects();
        if (empty($redirects[$slug]['target'])) {
            return;
        }

        $redirects[$slug]['hits'] = max(0, (int) ($redirects[$slug]['hits'] ?? 0)) + 1;
        $redirects[$slug]['last_hit_at'] = current_time('mysql');
        update_option(self::OPTION, $redirects, false);

        wp_safe_redirect(esc_url_raw((string) $redirects[$slug]['target']), 301);
        exit;
    }

    public function get_redirects(): array
    {
        return $this->redirects();
    }

    public function delete_redirect(string $slug): void
    {
        $slug = sanitize_title($slug);
        if ($slug === '') {
            return;
        }
        $redirects = $this->redirects();
        if (isset($redirects[$slug])) {
            unset($redirects[$slug]);
            update_option(self::OPTION, $redirects, false);
        }
    }

    private function redirects(): array
    {
        $value = get_option(self::OPTION, []);
        return is_array($value) ? $value : [];
    }
}
