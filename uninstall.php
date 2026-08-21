<?php
/**
 * Slotera Booking uninstall handler.
 *
 * By default this file keeps business data intact. A full cleanup is performed only when the
 * site owner explicitly enables "Remove data on uninstall" in Slotera settings.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }

global $wpdb;

$settings = get_option('sltr_settings', []);
$remove_data = is_array($settings) && !empty($settings['privacy_remove_data_on_uninstall']);

/*
 * Scheduled callbacks must always be removed, even when business data is retained. Scan the
 * actual cron array as well as the known hooks so one-off events and future Slotera hooks cannot
 * become orphaned after the plugin files disappear.
 */
$cron_hooks = [
    'sltr_process_email_queue',
    'sltr_process_marketing_queue',
    'sltr_process_marketing_automations',
    'sltr_reconcile_pending_payments',
    'sltr_cleanup_pending_payments',
    'sltr_outgoing_webhook_retry',
    'sltr_privacy_retention_cleanup',
    'sltr_active_slot_hash_backfill_batch',
    'sltr_cleanup_secure_mail_attachments',
    'sltr_cleanup_magic_link_options',
];

if (function_exists('_get_cron_array')) {
    $cron_array = _get_cron_array();
    if (is_array($cron_array)) {
        foreach ($cron_array as $timestamp => $hooks) {
            if (!is_array($hooks)) { continue; }
            foreach ($hooks as $hook => $events) {
                if (!is_string($hook) || strpos($hook, 'sltr_') !== 0) { continue; }

                $cron_hooks[] = $hook;

                /*
                 * One-off events can carry arguments (for example booking ID 25). Remove each
                 * concrete event with its exact timestamp and arguments before clearing the hook.
                 */
                if (is_array($events)) {
                    foreach ($events as $event) {
                        $args = isset($event['args']) && is_array($event['args']) ? $event['args'] : [];
                        wp_unschedule_event((int) $timestamp, $hook, $args);
                    }
                }
            }
        }
    }
}

foreach (array_unique($cron_hooks) as $hook) {
    wp_clear_scheduled_hook($hook);
}

/*
 * As a final safeguard, remove any remaining Slotera events directly from the cron option. This
 * covers malformed/stale entries that WordPress' scheduling API cannot match by arguments.
 */
$cron_option = get_option('cron', []);
if (is_array($cron_option)) {
    $cron_changed = false;
    foreach ($cron_option as $timestamp => $hooks) {
        if (!is_array($hooks)) { continue; }
        foreach (array_keys($hooks) as $hook) {
            if (is_string($hook) && strpos($hook, 'sltr_') === 0) {
                unset($cron_option[$timestamp][$hook]);
                $cron_changed = true;
            }
        }
        if (isset($cron_option[$timestamp]) && is_array($cron_option[$timestamp]) && $cron_option[$timestamp] === []) {
            unset($cron_option[$timestamp]);
        }
    }
    if ($cron_changed) {
        update_option('cron', $cron_option);
    }
}

if (!$remove_data) {
    return;
}

/*
 * Permanently remove WordPress pages created by Slotera. Collect IDs before dropping custom
 * tables/options, because package/category page bindings and system-page settings live there.
 * User-authored pages that were merely bound to Slotera are preserved unless they carry the
 * explicit _sltr_created_by_plugin marker.
 */
$slotera_page_ids = [];

// Backward compatibility: recognize required pages created by older Slotera versions before
// ownership metadata existed. Only standard title + expected shortcode combinations qualify.
$legacy_required_pages = [
    'packages_page_id' => ['titles' => ['Slotera Packages'], 'shortcode' => 'slotera_packages'],
    'booking_page_id' => ['titles' => ['Slotera Booking'], 'shortcode' => 'slotera_booking'],
    'thank_you_page_id' => ['titles' => ['Slotera Thank You'], 'shortcode' => 'slotera_thank_you'],
    'checkout_page_id' => ['titles' => ['Slotera Checkout'], 'shortcode' => 'slotera_checkout'],
    'login_page_id' => ['titles' => ['Slotera Login'], 'shortcode' => 'slotera_login'],
    'account_page_id' => ['titles' => ['Slotera Account', 'Client Account'], 'shortcode' => 'slotera_account'],
];
foreach ($legacy_required_pages as $setting_key => $definition) {
    $page_id = (int) ($settings[$setting_key] ?? 0);
    $post = $page_id > 0 ? get_post($page_id) : null;
    if (!$post || $post->post_type !== 'page') {
        continue;
    }
    if (in_array((string) $post->post_title, $definition['titles'], true)
        && has_shortcode((string) $post->post_content, $definition['shortcode'])) {
        $slotera_page_ids[$page_id] = true;
    }
}

/*
 * Login/account pages can survive an older incomplete uninstall and then be reused by a later
 * installation before ownership metadata is restored. Discover the two canonical automatic
 * pages independently of sltr_settings so they are still removed during a confirmed full cleanup.
 * Require the standard slug, standard title and expected shortcode together to avoid deleting a
 * user-authored page that was merely assigned in settings.
 */
$canonical_account_pages = [
    ['slug' => 'slotera-login', 'titles' => ['Slotera Login'], 'shortcode' => 'slotera_login'],
    ['slug' => 'client-account', 'titles' => ['Client Account', 'Slotera Account'], 'shortcode' => 'slotera_account'],
];
foreach ($canonical_account_pages as $definition) {
    $post = get_page_by_path($definition['slug'], OBJECT, 'page');
    if (!$post || $post->post_type !== 'page') {
        continue;
    }
    if (in_array((string) $post->post_title, $definition['titles'], true)
        && has_shortcode((string) $post->post_content, $definition['shortcode'])) {
        $slotera_page_ids[(int) $post->ID] = true;
    }
}

$marked_page_ids = get_posts([
    'post_type' => 'page',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'no_found_rows' => true,
    'meta_key' => '_sltr_created_by_plugin',
    'meta_value' => '1',
]);
foreach ((array) $marked_page_ids as $page_id) {
    $slotera_page_ids[(int) $page_id] = true;
}

foreach (['sltr_packages', 'sltr_categories'] as $binding_table_suffix) {
    $binding_table = $wpdb->prefix . $binding_table_suffix;
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $binding_table));
    if ($table_exists === $binding_table) {
        $bound_ids = $wpdb->get_col("SELECT page_id FROM `{$binding_table}` WHERE page_id IS NOT NULL AND page_id > 0");
        foreach ((array) $bound_ids as $page_id) {
            $page_id = (int) $page_id;
            if ($page_id > 0) {
                // Package/category pages are generated and maintained by Slotera itself.
                $slotera_page_ids[$page_id] = true;
            }
        }
    }
}

/*
 * Delete each owned page through WordPress first so normal cleanup hooks, revisions, comments,
 * post meta and term relationships are handled. Some hosting stacks/plugins can prevent or
 * short-circuit wp_delete_post() during uninstall, so verify the result and use a narrowly
 * scoped database fallback only for the exact page IDs already proven to belong to Slotera.
 */
foreach (array_keys($slotera_page_ids) as $page_id) {
    $page_id = (int) $page_id;
    if ($page_id <= 0) {
        continue;
    }

    $post = get_post($page_id);
    if (!$post || $post->post_type !== 'page') {
        continue;
    }

    wp_delete_post($page_id, true);

    // Flush object cache before checking whether the page still exists.
    clean_post_cache($page_id);
    if (!get_post($page_id)) {
        continue;
    }

    // Remove revisions/autosaves belonging to this exact page before the direct fallback.
    $child_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type IN ('revision', 'attachment')",
        $page_id
    ));
    foreach ((array) $child_ids as $child_id) {
        $child_id = (int) $child_id;
        if ($child_id <= 0) {
            continue;
        }
        $wpdb->delete($wpdb->postmeta, ['post_id' => $child_id], ['%d']);
        $wpdb->delete($wpdb->term_relationships, ['object_id' => $child_id], ['%d']);
        $wpdb->delete($wpdb->posts, ['ID' => $child_id], ['%d']);
        clean_post_cache($child_id);
    }

    // Comments are uncommon on Slotera pages, but remove them completely if present.
    $comment_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d",
        $page_id
    ));
    foreach ((array) $comment_ids as $comment_id) {
        $comment_id = (int) $comment_id;
        if ($comment_id <= 0) {
            continue;
        }
        $wpdb->delete($wpdb->commentmeta, ['comment_id' => $comment_id], ['%d']);
        $wpdb->delete($wpdb->comments, ['comment_ID' => $comment_id], ['%d']);
    }

    $wpdb->delete($wpdb->postmeta, ['post_id' => $page_id], ['%d']);
    $wpdb->delete($wpdb->term_relationships, ['object_id' => $page_id], ['%d']);
    $wpdb->delete($wpdb->posts, ['ID' => $page_id], ['%d']);
    clean_post_cache($page_id);
}

$tables = [
    $wpdb->prefix . 'sltr_payment_invoices',
    $wpdb->prefix . 'sltr_payment_transactions',
    $wpdb->prefix . 'sltr_visitor_events',
    $wpdb->prefix . 'sltr_rest_hmac_nonces',
    $wpdb->prefix . 'sltr_outgoing_webhook_deliveries',
    $wpdb->prefix . 'sltr_outgoing_webhook_endpoints',
    $wpdb->prefix . 'sltr_email_queue',
    $wpdb->prefix . 'sltr_rate_limits',
    $wpdb->prefix . 'sltr_webhook_events',
    $wpdb->prefix . 'sltr_marketing_logs',
    $wpdb->prefix . 'sltr_marketing_campaigns',
    $wpdb->prefix . 'sltr_coupons',
    $wpdb->prefix . 'sltr_booking_history',
    $wpdb->prefix . 'sltr_activity_log',
    $wpdb->prefix . 'sltr_bookings',
    $wpdb->prefix . 'sltr_working_hours',
    $wpdb->prefix . 'sltr_events',
    $wpdb->prefix . 'sltr_package_locations',
    $wpdb->prefix . 'sltr_packages',
    $wpdb->prefix . 'sltr_locations',
    $wpdb->prefix . 'sltr_categories',
];

foreach ($tables as $table) {
    $wpdb->query('DROP TABLE IF EXISTS `' . esc_sql($table) . '`');
}

/*
 * Remove every option owned by Slotera, including migration/build/cache/cron-state markers,
 * locks, rate-limit entries and transients. The setting that enabled this cleanup is included.
 */
$options_table = $wpdb->options;
$option_patterns = [
    'sltr\\_%',
    '_sltr\\_%',
    '_transient\\_sltr\\_%',
    '_transient\\_timeout\\_sltr\\_%',
    '_site_transient\\_sltr\\_%',
    '_site_transient\\_timeout\\_sltr\\_%',
    '_transient\\_%sltr\\_%',
    '_transient\\_timeout\\_%sltr\\_%',
];
foreach ($option_patterns as $pattern) {
    $wpdb->query($wpdb->prepare("DELETE FROM {$options_table} WHERE option_name LIKE %s", $pattern));
}

/* Remove network-level Slotera metadata when uninstalling from multisite. */
if (is_multisite() && !empty($wpdb->sitemeta)) {
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
        'sltr\\_%',
        '_site_transient\\_%sltr\\_%'
    ));
}

/* Remove capabilities added to WordPress roles. */
$capabilities = [
    'slotera_manage',
    'slotera_manage_bookings',
    'slotera_manage_packages',
    'slotera_manage_settings',
    'slotera_manage_payments',
    'slotera_manage_marketing',
    'slotera_manage_webhooks',
    'slotera_manage_tools',
    'slotera_view_logs',
];
if (function_exists('wp_roles')) {
    $roles = wp_roles();
    if ($roles && is_array($roles->roles)) {
        foreach (array_keys($roles->roles) as $role_name) {
            $role = get_role($role_name);
            if (!$role) { continue; }
            foreach ($capabilities as $capability) {
                $role->remove_cap($capability);
            }
        }
    }
}

/* Remove temporary secure mail attachments created exclusively by Slotera. */
$upload = wp_upload_dir(null, false);
if (empty($upload['error']) && !empty($upload['basedir'])) {
    $directory = trailingslashit($upload['basedir']) . 'slotera-secure-mail-attachments';
    if (is_dir($directory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($directory);
    }
}
