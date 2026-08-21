<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\CategoryRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Smart cache invalidation for Slotera content/settings changes.
 *
 * Uses targeted URL/page purges for package/category/booking changes and falls
 * back to a full public cache purge only for global settings or unknown changes.
 * Runtime auth, magic-link and payment state are never deleted here.
 */
final class CacheInvalidationService
{
    public const VERSION_OPTION = 'sltr_cache_version';
    public const LAST_PURGE_OPTION = 'sltr_cache_last_purge';

    /** @var array<string,bool> */
    private static $purged_keys_this_request = [];

    public function register_hooks(): void
    {
        add_action('sltr_cache_purge', [$this, 'purge'], 10, 2);
        add_action('sltr_data_changed', [$this, 'purge'], 10, 2);

        add_action('updated_option', [$this, 'maybe_purge_for_updated_option'], 10, 3);
        add_action('added_option', [$this, 'maybe_purge_for_added_option'], 10, 2);
        add_action('deleted_option', [$this, 'maybe_purge_for_deleted_option'], 10, 1);

        add_action('add_attachment', [$this, 'purge_media_cache'], 10, 1);
        add_action('edit_attachment', [$this, 'purge_media_cache'], 10, 1);
        add_action('delete_attachment', [$this, 'purge_media_cache'], 10, 1);

        add_action('save_post_page', [$this, 'maybe_purge_for_page'], 10, 3);
        add_action('delete_post', [$this, 'maybe_purge_for_deleted_post'], 10, 1);
    }

    /**
     * @param string|mixed $reason
     * @param array<string,mixed> $context
     */
    public function purge($reason = 'slotera_change', array $context = []): void
    {
        $reason = sanitize_key((string) $reason);
        if ($reason === '') {
            $reason = 'slotera_change';
        }

        $targets = $this->targets_for_change($reason, $context);
        $is_full = !empty($targets['full']);
        $urls = $this->normalize_urls((array) ($targets['urls'] ?? []));
        $post_ids = $this->normalize_ids((array) ($targets['post_ids'] ?? []));

        $guard_key = $is_full ? 'full' : md5(wp_json_encode([$reason, $urls, $post_ids]));
        if (isset(self::$purged_keys_this_request[$guard_key])) {
            return;
        }
        self::$purged_keys_this_request[$guard_key] = true;

        $this->bump_cache_version($reason, $is_full ? 'full' : 'smart', $urls, $post_ids);
        $this->purge_wordpress_object_cache($is_full, $post_ids);

        if ($is_full) {
            $this->purge_common_cache_plugins_full($reason);
        } else {
            $this->purge_common_cache_plugins_smart($urls, $post_ids, $reason);
        }

        /**
         * @param string $reason
         * @param array<string,mixed> $targets
         */
        do_action('sltr_after_cache_purge', $reason, [
            'mode' => $is_full ? 'full' : 'smart',
            'urls' => $urls,
            'post_ids' => $post_ids,
            'context' => $context,
        ]);
    }

    public function maybe_purge_for_updated_option(string $option, $old_value, $value): void
    {
        if ($old_value === $value || !$this->is_slotera_option($option)) {
            return;
        }

        $this->purge('option_' . $option, ['option' => $option]);
    }

    public function maybe_purge_for_added_option(string $option, $value): void
    {
        if ($this->is_slotera_option($option)) {
            $this->purge('option_' . $option, ['option' => $option]);
        }
    }

    public function maybe_purge_for_deleted_option(string $option): void
    {
        if ($this->is_slotera_option($option)) {
            $this->purge('option_' . $option, ['option' => $option]);
        }
    }

    public function purge_media_cache(int $attachment_id): void
    {
        $post_id = wp_get_post_parent_id($attachment_id);
        $context = ['attachment_id' => $attachment_id];
        if ($post_id > 0) {
            $context['post_ids'] = [$post_id];
            $context['urls'] = [get_permalink($post_id)];
            $this->purge('media_changed', $context);
            return;
        }

        // Media can be used in package cards/solo pages without a parent.
        $this->purge('media_changed', ['full' => true, 'attachment_id' => $attachment_id]);
    }

    public function maybe_purge_for_page(int $post_id, \WP_Post $post, bool $update): void
    {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        if ($this->page_contains_slotera_shortcode($post)) {
            $this->purge('slotera_page_changed', [
                'post_ids' => [$post_id],
                'urls' => [get_permalink($post_id)],
            ]);
        }
    }

    public function maybe_purge_for_deleted_post(int $post_id): void
    {
        if (get_post_type($post_id) === 'page') {
            $this->purge('page_deleted', ['post_ids' => [$post_id], 'full' => true]);
        }
    }

    /**
     * @param array<string,mixed> $context
     * @return array{full?:bool,urls?:array<int,string>,post_ids?:array<int,int>}
     */
    private function targets_for_change(string $reason, array $context): array
    {
        if (!empty($context['full'])) {
            return ['full' => true];
        }

        if (strpos($reason, 'option_') === 0 || strpos($reason, 'settings_') === 0) {
            return ['full' => true];
        }

        if (strpos($reason, 'package_') === 0) {
            return $this->package_targets($context);
        }

        if (strpos($reason, 'category_') === 0) {
            return $this->category_targets($context);
        }

        if (strpos($reason, 'booking_') === 0 || strpos($reason, 'availability_') === 0 || strpos($reason, 'working_hours_') === 0) {
            return $this->availability_targets($context);
        }

        if (strpos($reason, 'coupon_') === 0) {
            return $this->commerce_targets($context);
        }

        if (!empty($context['urls']) || !empty($context['post_ids'])) {
            return [
                'urls' => (array) ($context['urls'] ?? []),
                'post_ids' => (array) ($context['post_ids'] ?? []),
            ];
        }

        return ['full' => true];
    }

    /** @param array<string,mixed> $context */
    private function package_targets(array $context): array
    {
        $urls = [];
        $post_ids = [];
        $package = $this->package_from_context($context);

        if ($package) {
            $page_id = absint($package['page_id'] ?? 0);
            if ($page_id > 0) {
                $post_ids[] = $page_id;
                $urls[] = get_permalink($page_id);
            }
            if (!empty($package['category_id'])) {
                $category = (new CategoryRepository())->get_by_id(absint($package['category_id']));
                if ($category && !empty($category['page_id'])) {
                    $post_ids[] = absint($category['page_id']);
                    $urls[] = get_permalink(absint($category['page_id']));
                }
            }
        }

        $urls = array_merge($urls, $this->core_page_urls(['packages', 'booking']));
        return ['urls' => $urls, 'post_ids' => $post_ids];
    }

    /** @param array<string,mixed> $context */
    private function category_targets(array $context): array
    {
        $urls = $this->core_page_urls(['packages', 'booking']);
        $post_ids = [];
        $category_id = absint($context['category_id'] ?? $context['id'] ?? 0);
        if ($category_id > 0) {
            $category = (new CategoryRepository())->get_by_id($category_id);
            if ($category && !empty($category['page_id'])) {
                $post_ids[] = absint($category['page_id']);
                $urls[] = get_permalink(absint($category['page_id']));
            }
        }

        return ['urls' => $urls, 'post_ids' => $post_ids];
    }

    /** @param array<string,mixed> $context */
    private function availability_targets(array $context): array
    {
        $targets = $this->package_targets($context);
        $targets['urls'] = array_merge((array) ($targets['urls'] ?? []), $this->core_page_urls(['checkout']));
        return $targets;
    }

    /** @param array<string,mixed> $context */
    private function commerce_targets(array $context): array
    {
        if (!empty($context['package_id'])) {
            return $this->package_targets($context);
        }

        if (!empty($context['package_ids']) && is_array($context['package_ids'])) {
            $urls = [];
            $post_ids = [];
            foreach ($context['package_ids'] as $package_id) {
                $targets = $this->package_targets(['package_id' => absint($package_id)]);
                $urls = array_merge($urls, (array) ($targets['urls'] ?? []));
                $post_ids = array_merge($post_ids, (array) ($targets['post_ids'] ?? []));
            }
            $urls = array_merge($urls, $this->core_page_urls(['packages', 'booking', 'checkout']));
            return ['urls' => $urls, 'post_ids' => $post_ids];
        }

        // Coupons may apply globally, so public booking/package pages can change.
        return ['urls' => $this->core_page_urls(['packages', 'booking', 'checkout'])];
    }

    /** @param array<string,mixed> $context */
    private function package_from_context(array $context): ?array
    {
        if (!empty($context['package']) && is_array($context['package'])) {
            return $context['package'];
        }

        $package_id = absint($context['package_id'] ?? $context['id'] ?? 0);
        if ($package_id <= 0) {
            return null;
        }

        return (new PackageRepository())->get_by_id($package_id);
    }

    /** @param array<int,string> $keys */
    private function core_page_urls(array $keys): array
    {
        $repo = new SettingsRepository();
        $urls = [];
        foreach ($keys as $key) {
            $url = $repo->get_page_url($key);
            if ($url !== '') {
                $urls[] = $url;
            }
        }
        return $urls;
    }

    private function page_contains_slotera_shortcode(\WP_Post $post): bool
    {
        $content = (string) $post->post_content;
        foreach ($this->slotera_shortcodes() as $shortcode) {
            if (has_shortcode($content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    /** @return array<int,string> */
    private function slotera_shortcodes(): array
    {
        return [
            'slotera_booking',
            'slotera_category',
            'slotera_packages',
            'slotera_thank_you',
            'slotera_checkout',
            'slotera_contact',
            'slotera_login',
            'slotera_account',
            'slotera_package_slider',
            'slotera_package_media',
            'slotera_package_image',
            'slotera_package_text_block',
        ];
    }

    private function is_slotera_option(string $option): bool
    {
        if (strpos($option, 'sltr_') !== 0) {
            return false;
        }

        if (in_array($option, [self::VERSION_OPTION, self::LAST_PURGE_OPTION], true)) {
            return false;
        }

        $ignored_prefixes = [
            'sltr_lock_',
            'sltr_booking_lock_',
            'sltr_payment_url_error_',
            'sltr_magic_link_',
            'sltr_magic_',
            'sltr_magic_login_',
            'sltr_customer_session_',
            'sltr_payment_intent_',
        ];

        foreach ($ignored_prefixes as $prefix) {
            if (strpos($option, $prefix) === 0) {
                return false;
            }
        }

        return true;
    }

    /** @param array<int,string> $urls @param array<int,int> $post_ids */
    private function bump_cache_version(string $reason, string $mode, array $urls, array $post_ids): void
    {
        update_option(self::VERSION_OPTION, (string) time(), false);
        update_option(self::LAST_PURGE_OPTION, [
            'time' => current_time('mysql'),
            'reason' => $reason,
            'mode' => $mode,
            'urls' => array_slice($urls, 0, 20),
            'post_ids' => array_slice($post_ids, 0, 20),
        ], false);
    }

    /** @param array<int,int> $post_ids */
    private function purge_wordpress_object_cache(bool $full, array $post_ids): void
    {
        if ($full) {
            if (function_exists('wp_cache_flush_runtime')) {
                wp_cache_flush_runtime();
                return;
            }
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            return;
        }

        foreach ($post_ids as $post_id) {
            clean_post_cache($post_id);
        }
    }

    private function purge_common_cache_plugins_full(string $reason): void
    {
        if (class_exists('LiteSpeed\\Purge')) {
            \LiteSpeed\Purge::purge_all();
        } elseif (defined('LSCWP_V')) {
            do_action('litespeed_purge_all');
        }

        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        if (class_exists('autoptimizeCache')) {
            \autoptimizeCache::clearall();
        }
        if (function_exists('sg_cachepress_purge_cache')) {
            sg_cachepress_purge_cache();
        }
        if (class_exists('Breeze_PurgeCache')) {
            \Breeze_PurgeCache::breeze_cache_flush();
        }
        if (class_exists('Hummingbird\\WP_Hummingbird')) {
            do_action('wphb_clear_page_cache');
        }

        do_action('sltr_purge_external_cache', ['mode' => 'full', 'reason' => $reason]);
    }

    /** @param array<int,string> $urls @param array<int,int> $post_ids */
    private function purge_common_cache_plugins_smart(array $urls, array $post_ids, string $reason): void
    {
        foreach ($post_ids as $post_id) {
            if (function_exists('wp_cache_post_change')) {
                wp_cache_post_change($post_id);
            }
        }

        if ($urls !== [] && function_exists('rocket_clean_files')) {
            rocket_clean_files($urls);
        }

        foreach ($urls as $url) {
            if (class_exists('LiteSpeed\\Purge') && method_exists('LiteSpeed\\Purge', 'purge_url')) {
                \LiteSpeed\Purge::purge_url($url);
            } elseif (defined('LSCWP_V')) {
                do_action('litespeed_purge_url', $url);
            }

            if (function_exists('w3tc_flush_url')) {
                w3tc_flush_url($url);
            }
            if (function_exists('sg_cachepress_purge_url')) {
                sg_cachepress_purge_url($url);
            }
        }

        do_action('sltr_purge_external_cache', [
            'mode' => 'smart',
            'reason' => $reason,
            'urls' => $urls,
            'post_ids' => $post_ids,
        ]);
    }

    /** @param array<int,mixed> $urls @return array<int,string> */
    private function normalize_urls(array $urls): array
    {
        $normalized = [];
        foreach ($urls as $url) {
            $url = esc_url_raw((string) $url);
            if ($url !== '' && strpos($url, 'http') === 0) {
                $normalized[] = $url;
            }
        }
        return array_values(array_unique($normalized));
    }

    /** @param array<int,mixed> $ids @return array<int,int> */
    private function normalize_ids(array $ids): array
    {
        $normalized = [];
        foreach ($ids as $id) {
            $id = absint($id);
            if ($id > 0) {
                $normalized[] = $id;
            }
        }
        return array_values(array_unique($normalized));
    }
}
