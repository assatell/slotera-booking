<?php
declare(strict_types=1);
namespace Slotera\Core\Migrations;

use Slotera\Core\ActiveSlotHashBackfill;
use Slotera\Core\Database;
if (!defined('ABSPATH')) { exit; }
final class LegacyMigrations {
    public const DB_VERSION_OPTION='sltr_db_version';
    private const ACTIVE_SLOT_HASH_BACKFILL_OPTION='sltr_active_slot_hash_backfill';
    private const ACTIVE_SLOT_HASH_BATCH_SIZE=250;
    public static function migrate_to_10232(): void {
        // Clear persisted frontend overrides for el, hr and sl_SI so registry translations are used.
        $custom = get_option('sltr_translations', []);
        if (!is_array($custom)) { return; }
        $locales = ['el', 'hr', 'sl_SI', 'sl'];
        $changed = false;
        if (isset($custom['frontend']) && is_array($custom['frontend'])) {
            foreach ($locales as $locale) {
                if (isset($custom['frontend'][$locale])) { unset($custom['frontend'][$locale]); $changed = true; }
            }
        }
        foreach ($locales as $locale) {
            if (isset($custom[$locale]) && is_array($custom[$locale])) {
                foreach (array_keys($custom[$locale]) as $key) {
                    if (strpos((string) $key, 'frontend.') === 0) { unset($custom[$locale][$key]); $changed = true; }
                }
                if ($custom[$locale] === []) { unset($custom[$locale]); }
            }
        }
        if ($changed) { update_option('sltr_translations', $custom, false); }
    }

    public static function migrate_to_10231(): void {
        // Clear persisted frontend overrides for cs_CZ, sk_SK and hu_HU so registry translations are used.
        $custom = get_option('sltr_translations', []);
        if (!is_array($custom)) { return; }
        $locales = ['cs_CZ', 'cs', 'sk_SK', 'sk', 'hu_HU', 'hu'];
        $changed = false;
        if (isset($custom['frontend']) && is_array($custom['frontend'])) {
            foreach ($locales as $locale) {
                if (isset($custom['frontend'][$locale])) { unset($custom['frontend'][$locale]); $changed = true; }
            }
        }
        foreach ($locales as $locale) {
            if (isset($custom[$locale]) && is_array($custom[$locale])) {
                foreach (array_keys($custom[$locale]) as $key) {
                    if (strpos((string) $key, 'frontend.') === 0) { unset($custom[$locale][$key]); $changed = true; }
                }
                if ($custom[$locale] === []) { unset($custom[$locale]); }
            }
        }
        if ($changed) { update_option('sltr_translations', $custom, false); }
    }

    public static function migrate_to_10230(): void {
        // Clear persisted frontend overrides for sv_SE, da_DK and fi so registry translations are used.
        $custom = get_option('sltr_translations', []);
        if (!is_array($custom)) { return; }
        $locales = ['sv_SE', 'sv', 'da_DK', 'da', 'fi', 'fi_FI'];
        $changed = false;
        if (isset($custom['frontend']) && is_array($custom['frontend'])) {
            foreach ($locales as $locale) {
                if (isset($custom['frontend'][$locale])) { unset($custom['frontend'][$locale]); $changed = true; }
            }
        }
        foreach ($locales as $locale) {
            if (isset($custom[$locale]) && is_array($custom[$locale])) {
                foreach (array_keys($custom[$locale]) as $key) {
                    if (strpos((string) $key, 'frontend.') === 0) { unset($custom[$locale][$key]); $changed = true; }
                }
                if ($custom[$locale] === []) { unset($custom[$locale]); }
            }
        }
        if ($changed) { update_option('sltr_translations', $custom, false); }
    }

    public static function migrate_to_10229(): void {
        // Clear persisted frontend overrides for pt_PT, pt_BR and ro_RO so the completed registry translations are used.
        $custom = get_option('sltr_translations', []);
        if (!is_array($custom)) { return; }
        $locales = ['pt_PT', 'pt_BR', 'pt', 'ro_RO', 'ro'];
        $changed = false;
        if (isset($custom['frontend']) && is_array($custom['frontend'])) {
            foreach ($locales as $locale) {
                if (isset($custom['frontend'][$locale])) { unset($custom['frontend'][$locale]); $changed = true; }
            }
        }
        foreach ($locales as $locale) {
            if (isset($custom[$locale]) && is_array($custom[$locale])) {
                foreach (array_keys($custom[$locale]) as $key) {
                    if (strpos((string) $key, 'frontend.') === 0) { unset($custom[$locale][$key]); $changed = true; }
                }
                if ($custom[$locale] === []) { unset($custom[$locale]); }
            }
        }
        if ($changed) { update_option('sltr_translations', $custom, false); }
    }

    public static function migrate_to_10228(): void {
        // Clear persisted frontend overrides for the newly completed locales so
        // fixed registry translations are used immediately after update.
        $custom = get_option('sltr_translations', []);
        if (!is_array($custom)) {
            return;
        }

        $locales = ['de_DE', 'nl_NL', 'pl_PL', 'de', 'nl', 'pl'];
        $changed = false;

        if (isset($custom['frontend']) && is_array($custom['frontend'])) {
            foreach ($locales as $locale) {
                if (isset($custom['frontend'][$locale])) {
                    unset($custom['frontend'][$locale]);
                    $changed = true;
                }
            }
        }

        foreach ($locales as $locale) {
            if (isset($custom[$locale]) && is_array($custom[$locale])) {
                foreach (array_keys($custom[$locale]) as $key) {
                    if (strpos((string) $key, 'frontend.') === 0) {
                        unset($custom[$locale][$key]);
                        $changed = true;
                    }
                }
                if ($custom[$locale] === []) {
                    unset($custom[$locale]);
                }
            }
        }

        if ($changed) {
            update_option('sltr_translations', $custom, false);
        }
    }

    public static function migrate_to_10227(): void {
        // v1.0.223-1.0.226 accidentally allowed draft/mixed frontend translations
        // to be saved in the database. Stored custom values override the fixed
        // registry, so clear only frontend custom translations for the locales
        // that were edited in those builds. Admin/email template content is not touched.
        $custom = get_option('sltr_translations', []);
        if (!is_array($custom)) {
            return;
        }

        $locales = ['et', 'lv', 'lt_LT', 'fr_FR', 'es_ES', 'it_IT'];
        $changed = false;

        if (isset($custom['frontend']) && is_array($custom['frontend'])) {
            foreach ($locales as $locale) {
                if (isset($custom['frontend'][$locale])) {
                    unset($custom['frontend'][$locale]);
                    $changed = true;
                }
            }
        }

        // Backward compatibility storage used in older versions: [locale][key].
        foreach ($locales as $locale) {
            if (isset($custom[$locale]) && is_array($custom[$locale])) {
                foreach (array_keys($custom[$locale]) as $key) {
                    if (strpos((string) $key, 'frontend.') === 0) {
                        unset($custom[$locale][$key]);
                        $changed = true;
                    }
                }
                if ($custom[$locale] === []) {
                    unset($custom[$locale]);
                }
            }
        }

        if ($changed) {
            update_option('sltr_translations', $custom, false);
        }
    }

    public static function migrate_to_10177(): void {
        global $wpdb;
        $table = Database::bookings_table();
        $wpdb->query("UPDATE {$table} SET status='pending_payment' WHERE status='pending_confirmation'");
    }

    public static function migrate_to_10167(): void {
        self::maybe_add_column(Database::packages_table(), 'low_availability_notice_enabled', "TINYINT(1) NOT NULL DEFAULT 1 AFTER max_bookings_per_slot");
        self::maybe_add_column(Database::packages_table(), 'low_availability_threshold', "INT UNSIGNED NOT NULL DEFAULT 5 AFTER low_availability_notice_enabled");
        self::backfill_low_availability_package_columns();
    }

    public static function backfill_low_availability_package_columns(): void {
        global $wpdb;
        $table = Database::packages_table();
        $rows = $wpdb->get_results("SELECT id, booking_mode, mode_configs_json FROM {$table}", ARRAY_A) ?: [];
        foreach ($rows as $row) {
            $mode = sanitize_key((string) ($row['booking_mode'] ?? 'simple'));
            if ($mode === 'flexible') { $mode = 'flex'; }
            $configs = json_decode((string) ($row['mode_configs_json'] ?? ''), true);
            if (!is_array($configs) || !isset($configs[$mode]) || !is_array($configs[$mode])) { continue; }
            $cfg = $configs[$mode];
            $enabled = array_key_exists('low_availability_notice_enabled', $cfg) ? (!empty($cfg['low_availability_notice_enabled']) ? 1 : 0) : 1;
            $threshold = max(1, min(99, (int) ($cfg['low_availability_threshold'] ?? 5)));
            $wpdb->update($table, [
                'low_availability_notice_enabled' => $enabled,
                'low_availability_threshold' => $threshold,
            ], ['id' => (int) $row['id']]);
        }
    }

    public static function migrate_to_020(): void { self::maybe_add_columns(); }
    public static function migrate_to_040(): void {
        self::maybe_add_column(Database::packages_table(),'page_id',"BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER is_active");
        self::maybe_add_column(Database::categories_table(),'page_id',"BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER is_active");
        self::maybe_add_index(Database::packages_table(),'page_id',"KEY page_id (page_id)");
        self::maybe_add_index(Database::categories_table(),'page_id',"KEY page_id (page_id)");
    }
    public static function migrate_to_092(): void {
        self::maybe_add_column(Database::packages_table(),'is_popular',"TINYINT(1) NOT NULL DEFAULT 0 AFTER max_bookings_per_slot");
        self::maybe_add_index(Database::packages_table(),'is_popular',"KEY is_popular (is_popular)");
    }
    public static function migrate_to_1016(): void {
        self::maybe_add_column(Database::bookings_table(),'cancellation_token',"VARCHAR(64) NULL AFTER source");
        self::maybe_add_column(Database::bookings_table(),'reschedule_token',"VARCHAR(64) NULL AFTER cancellation_token");
        self::maybe_add_column(Database::bookings_table(),'cancelled_at',"DATETIME NULL AFTER reschedule_token");
        self::maybe_add_column(Database::bookings_table(),'completed_at',"DATETIME NULL AFTER cancelled_at");
        self::maybe_add_index(Database::bookings_table(),'cancellation_token',"KEY cancellation_token (cancellation_token)");
        self::maybe_add_index(Database::bookings_table(),'reschedule_token',"KEY reschedule_token (reschedule_token)");
    }

    public static function migrate_to_1017(): void {
        self::maybe_add_column(Database::bookings_table(),'payment_gateway',"VARCHAR(50) NOT NULL DEFAULT '' AFTER payment_status");
        self::maybe_add_column(Database::bookings_table(),'external_payment_id',"VARCHAR(191) NULL AFTER payment_gateway");
        self::maybe_add_column(Database::bookings_table(),'payment_redirect_url',"TEXT NULL AFTER external_payment_id");
        self::maybe_add_column(Database::bookings_table(),'paid_at',"DATETIME NULL AFTER payment_redirect_url");
        self::maybe_add_column(Database::bookings_table(),'refunded_at',"DATETIME NULL AFTER paid_at");
        self::maybe_add_index(Database::bookings_table(),'payment_gateway',"KEY payment_gateway (payment_gateway)");
        self::maybe_add_index(Database::bookings_table(),'external_payment_id',"KEY external_payment_id (external_payment_id)");
    }
    public static function migrate_to_1019(): void {
        self::maybe_add_column(Database::bookings_table(),'city',"VARCHAR(191) NULL AFTER customer_phone");
        self::maybe_add_column(Database::bookings_table(),'state',"VARCHAR(191) NULL AFTER city");
        self::maybe_add_column(Database::bookings_table(),'address',"VARCHAR(255) NULL AFTER state");
        self::maybe_add_column(Database::bookings_table(),'company',"VARCHAR(191) NULL AFTER address");
        self::maybe_add_index(Database::bookings_table(),'city',"KEY city (city)");
        self::maybe_add_index(Database::bookings_table(),'state',"KEY state (state)");
        self::maybe_add_index(Database::bookings_table(),'company',"KEY company (company)");
    }

    public static function migrate_to_1020(): void {
        Database::create_tables();
    }

    public static function migrate_to_1022(): void {
        self::maybe_add_column(Database::activity_log_table(),'gateway',"VARCHAR(50) NOT NULL DEFAULT '' AFTER status");
        self::maybe_add_column(Database::activity_log_table(),'error_message',"TEXT NULL AFTER message");
        self::maybe_add_column(Database::activity_log_table(),'ip_address',"VARCHAR(100) NULL AFTER payload_json");
        self::maybe_add_column(Database::activity_log_table(),'user_agent',"VARCHAR(255) NULL AFTER ip_address");
        self::maybe_add_index(Database::activity_log_table(),'gateway',"KEY gateway (gateway)");
    }

    public static function migrate_to_1024(): void {
        // Performance indexes for booking growth.
        self::maybe_add_index(Database::bookings_table(),'booking_date',"KEY booking_date (booking_date)");
        self::maybe_add_index(Database::bookings_table(),'booking_time',"KEY booking_time (start_time)");
        self::maybe_add_index(Database::bookings_table(),'end_time',"KEY end_time (end_time)");
        self::maybe_add_index(Database::bookings_table(),'package_id',"KEY package_id (package_id)");
        self::maybe_add_index(Database::bookings_table(),'customer_email',"KEY customer_email (customer_email)");
        self::maybe_add_index(Database::bookings_table(),'status',"KEY status (status)");
        self::maybe_add_index(Database::bookings_table(),'payment_status',"KEY payment_status (payment_status)");

        // Compound indexes used by availability checks, customer lookups and payment admin filters.
        self::maybe_add_index(Database::bookings_table(),'date_time_lookup',"KEY date_time_lookup (booking_date,start_time,end_time)");
        self::maybe_add_index(Database::bookings_table(),'package_date_status',"KEY package_date_status (package_id,booking_date,status)");
        self::maybe_add_index(Database::bookings_table(),'package_date_time_status',"KEY package_date_time_status (package_id,booking_date,start_time,end_time,status)");
        self::maybe_add_index(Database::bookings_table(),'customer_email_status',"KEY customer_email_status (customer_email,status)");
        self::maybe_add_index(Database::bookings_table(),'payment_status_gateway',"KEY payment_status_gateway (payment_status,payment_gateway)");

        // Slotera supports max_bookings_per_slot > 1, so a hard UNIQUE slot index would be unsafe.
        // Race protection is handled by BookingLockService; this compound index keeps the locked re-check fast.
    }

    public static function migrate_to_1028(): void {
        self::maybe_add_column(Database::bookings_table(),'resource_id',"BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER package_id");
        self::maybe_add_column(Database::bookings_table(),'staff_id',"BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER resource_id");
        self::maybe_add_index(Database::bookings_table(),'resource_id',"KEY resource_id (resource_id)");
        self::maybe_add_index(Database::bookings_table(),'staff_id',"KEY staff_id (staff_id)");
        self::maybe_add_index(Database::bookings_table(),'package_resource_date_status',"KEY package_resource_date_status (package_id,resource_id,booking_date,status)");
        self::maybe_add_index(Database::bookings_table(),'package_staff_date_status',"KEY package_staff_date_status (package_id,staff_id,booking_date,status)");
    }


    public static function migrate_to_1035(): void {
        self::prepare_booking_tokens_for_unique_indexes();
        self::maybe_add_index(Database::bookings_table(), 'cancellation_token_unique', 'UNIQUE KEY cancellation_token_unique (cancellation_token)');
        self::maybe_add_index(Database::bookings_table(), 'reschedule_token_unique', 'UNIQUE KEY reschedule_token_unique (reschedule_token)');
    }


    public static function migrate_to_1046(): void {
        self::maybe_add_column(Database::bookings_table(), 'active_slot_hash', "CHAR(64) NULL AFTER reschedule_token");
        self::queue_active_slot_hash_backfill('1.0.46');
    }


    public static function migrate_to_1048(): void {
        self::maybe_add_column(Database::packages_table(), 'info_tooltip', "TEXT NULL AFTER description");
    }

    public static function migrate_to_1049(): void {
        self::maybe_add_column(Database::packages_table(), 'description_font_family', "VARCHAR(120) NOT NULL DEFAULT '' AFTER info_tooltip");
        self::maybe_add_column(Database::packages_table(), 'description_font_size', "INT UNSIGNED NOT NULL DEFAULT 18 AFTER description_font_family");
    }

    public static function migrate_to_1050(): void {
        self::maybe_add_column(Database::packages_table(), 'solo_content', "LONGTEXT NULL AFTER description");
    }
    public static function migrate_to_1051(): void {
        self::maybe_add_column(Database::packages_table(), 'slider_image_ids', "TEXT NULL AFTER solo_content");
        self::maybe_add_column(Database::packages_table(), 'slider_speed', "INT UNSIGNED NOT NULL DEFAULT 4000 AFTER slider_image_ids");
        self::maybe_add_column(Database::packages_table(), 'gallery_image_ids', "TEXT NULL AFTER slider_speed");
        self::maybe_add_column(Database::packages_table(), 'right_block_title', "VARCHAR(191) NOT NULL DEFAULT '' AFTER gallery_image_ids");
        self::maybe_add_column(Database::packages_table(), 'right_block_text', "LONGTEXT NULL AFTER right_block_title");
        self::maybe_add_column(Database::packages_table(), 'right_block_font_family', "VARCHAR(120) NOT NULL DEFAULT '' AFTER right_block_text");
        self::maybe_add_column(Database::packages_table(), 'right_block_font_size', "INT UNSIGNED NOT NULL DEFAULT 18 AFTER right_block_font_family");
    }


    public static function migrate_to_1053(): void {
        self::maybe_add_column(Database::packages_table(), 'solo_down_content', "LONGTEXT NULL AFTER solo_content");
        self::maybe_add_column(Database::packages_table(), 'show_more_info', "TINYINT(1) NOT NULL DEFAULT 1 AFTER solo_down_content");
        self::maybe_add_column(Database::packages_table(), 'solo_page_enabled', "TINYINT(1) NOT NULL DEFAULT 1 AFTER show_more_info");
        self::maybe_add_column(Database::packages_table(), 'gallery_layout', "VARCHAR(20) NOT NULL DEFAULT 'grid' AFTER gallery_image_ids");
    }

    public static function migrate_to_1056(): void {
        self::maybe_add_column(Database::packages_table(), 'solo_layout', "VARCHAR(20) NOT NULL DEFAULT 'classic' AFTER gallery_layout");
    }

    public static function migrate_to_10129(): void {
        // Rebuild active slot hashes using the same input set as BookingRepository::active_slot_hash_from_data().
        // On large booking tables this is intentionally queued instead of running in one request.
        self::maybe_add_column(Database::bookings_table(), 'active_slot_hash', "CHAR(64) NULL AFTER reschedule_token");
        self::queue_active_slot_hash_backfill('1.0.129');
    }

    public static function migrate_to_10137(): void {
        // Safety net for sites that upgraded through 1.0.129-1.0.136, where the rebuild was eager.
        // Future large-table installs continue via the same batched path.
        self::maybe_add_column(Database::bookings_table(), 'active_slot_hash', "CHAR(64) NULL AFTER reschedule_token");
        if (!self::is_active_slot_hash_backfill_complete()) {
            self::queue_active_slot_hash_backfill('1.0.137');
        }
    }

    public static function migrate_to_10141(): void {
        self::ensure_required_shortcode_pages();
    }

    public static function ensure_required_shortcode_pages(): void {
        $settings = get_option('sltr_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $pages = [
            'booking_page_id' => ['Slotera Booking', 'slotera-booking', '[slotera_booking]'],
            'categories_page_id' => ['Slotera Categories', 'slotera-categories', '[slotera_categories]'],
            'thank_you_page_id' => ['Slotera Thank You', 'slotera-thank-you', '[slotera_thank_you]'],
            'checkout_page_id' => ['Slotera Checkout', 'slotera-checkout', '[slotera_checkout]'],
            'login_page_id' => ['Slotera Login', 'slotera-login', '[slotera_login]'],
            'account_page_id' => ['Client Account', 'client-account', '[slotera_account]'],
        ];

        $changed = false;
        foreach ($pages as $key => [$title, $slug, $shortcode]) {
            $page_id = self::ensure_shortcode_page($title, $slug, $shortcode, absint($settings[$key] ?? 0));
            if ($page_id > 0 && absint($settings[$key] ?? 0) !== $page_id) {
                $settings[$key] = $page_id;
                $changed = true;
            }
        }

        if ($changed) {
            update_option('sltr_settings', $settings, false);
        }
    }

    private static function ensure_shortcode_page(string $title, string $slug, string $shortcode, int $configured_id): int {
        if ($configured_id > 0) {
            $post = get_post($configured_id);
            if ($post && $post->post_type === 'page' && $post->post_status === 'publish' && has_shortcode((string) $post->post_content, trim($shortcode, '[]'))) {
                return $configured_id;
            }
        }

        $found = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $shortcode,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        foreach ((array) $found as $page_id) {
            $post = get_post((int) $page_id);
            if ($post && has_shortcode((string) $post->post_content, trim($shortcode, '[]'))) {
                return (int) $page_id;
            }
        }

        $existing = get_page_by_path($slug, OBJECT, 'page');
        if ($existing && $existing->post_type === 'page') {
            if ($existing->post_status !== 'publish') {
                wp_update_post(['ID' => (int) $existing->ID, 'post_status' => 'publish']);
            }
            if (!has_shortcode((string) $existing->post_content, trim($shortcode, '[]'))) {
                wp_update_post(['ID' => (int) $existing->ID, 'post_content' => $shortcode]);
            }

            // Canonical login/account pages reused after an older incomplete uninstall still
            // belong to Slotera. Restore ownership metadata so future full cleanup can remove them.
            if (in_array($slug, ['slotera-login', 'client-account'], true)
                && in_array((string) $existing->post_title, ['Slotera Login', 'Client Account', 'Slotera Account'], true)) {
                update_post_meta((int) $existing->ID, '_sltr_created_by_plugin', '1');
                update_post_meta((int) $existing->ID, '_sltr_page_role', trim($shortcode, '[]'));
            }
            return (int) $existing->ID;
        }

        $page_id = wp_insert_post([
            'post_title' => $title,
            'post_name' => $slug,
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => $shortcode,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ], true);

        if (is_wp_error($page_id)) {
            return 0;
        }

        $page_id = (int) $page_id;
        update_post_meta($page_id, '_sltr_created_by_plugin', '1');
        update_post_meta($page_id, '_sltr_page_role', trim($shortcode, '[]'));
        return $page_id;
    }

    public static function register_hooks(): void {
        ActiveSlotHashBackfill::register_hooks();
    }

    private static function prepare_active_slot_hashes_for_unique_index(): void {
        ActiveSlotHashBackfill::queue('legacy-wrapper');
    }

    /**
     * Backward-compatible wrapper used by older legacy migrations.
     *
     * Newer builds moved the implementation to ActiveSlotHashBackfill, but fresh
     * installs still replay every historical migration from 0.0.0. Keep this
     * method so old migration callbacks remain callable on first activation.
     */
    private static function queue_active_slot_hash_backfill(string $source): void {
        ActiveSlotHashBackfill::queue($source);
    }

    /**
     * Backward-compatible status check used by older legacy migrations.
     */
    private static function is_active_slot_hash_backfill_complete(): bool {
        $state = (array) get_option(self::ACTIVE_SLOT_HASH_BACKFILL_OPTION, []);
        return ($state['status'] ?? '') === 'complete';
    }

    public static function run_active_slot_hash_backfill_batch(): void {
        ActiveSlotHashBackfill::run_batch();
    }

    public static function print_active_slot_hash_backfill_notice(): void {
        ActiveSlotHashBackfill::print_admin_notice();
    }

    public static function ajax_run_active_slot_hash_backfill_batch(): void {
        ActiveSlotHashBackfill::ajax_run_batch();
    }

    private static function prepare_booking_tokens_for_unique_indexes(): void {
        global $wpdb;
        $table = Database::bookings_table();

        // Empty strings are valid duplicate values for MySQL UNIQUE indexes. Normalize old rows
        // without customer-management tokens to NULL first; MySQL allows multiple NULL values.
        $wpdb->query("UPDATE {$table} SET cancellation_token=NULL WHERE cancellation_token='' ");
        $wpdb->query("UPDATE {$table} SET reschedule_token=NULL WHERE reschedule_token='' ");

        self::deduplicate_booking_token_column($table, 'cancellation_token');
        self::deduplicate_booking_token_column($table, 'reschedule_token');
    }

    private static function deduplicate_booking_token_column(string $table, string $column): void {
        global $wpdb;
        $column = $column === 'reschedule_token' ? 'reschedule_token' : 'cancellation_token';
        $rows = $wpdb->get_results("SELECT id, {$column} AS token FROM {$table} WHERE {$column} IS NOT NULL AND {$column} <> '' ORDER BY id ASC", ARRAY_A);
        if (!$rows) { return; }

        $seen = [];
        foreach ($rows as $row) {
            $id = absint($row['id'] ?? 0);
            $token = (string) ($row['token'] ?? '');
            if ($id <= 0 || $token === '') { continue; }
            if (!isset($seen[$token])) { $seen[$token] = true; continue; }

            $new_token = self::generate_unique_booking_token($table, $column, $seen);
            $wpdb->update($table, [$column => $new_token], ['id' => $id], ['%s'], ['%d']);
            $seen[$new_token] = true;
        }
    }

    private static function generate_unique_booking_token(string $table, string $column, array $seen): string {
        global $wpdb;
        $column = $column === 'reschedule_token' ? 'reschedule_token' : 'cancellation_token';
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $token = wp_generate_password(48, false, false);
            if (isset($seen[$token])) { continue; }
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE {$column}=%s LIMIT 1", $token));
            if (!$exists) { return $token; }
        }

        return hash('sha256', wp_generate_uuid4() . microtime(true) . wp_rand());
    }

    public static function migrate_to_1060(): void {
        Database::create_tables();
        self::maybe_add_column(Database::bookings_table(), 'base_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
        self::maybe_add_column(Database::bookings_table(), 'package_discount_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER base_amount");
        self::maybe_add_column(Database::bookings_table(), 'coupon_id', "BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER package_discount_amount");
        self::maybe_add_column(Database::bookings_table(), 'coupon_code', "VARCHAR(50) NOT NULL DEFAULT '' AFTER coupon_id");
        self::maybe_add_column(Database::bookings_table(), 'coupon_discount_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER coupon_code");
        self::maybe_add_index(Database::bookings_table(), 'coupon_id', 'KEY coupon_id (coupon_id)');
        self::maybe_add_index(Database::bookings_table(), 'coupon_code', 'KEY coupon_code (coupon_code)');
    }

    public static function migrate_to_1063(): void {
        Database::create_tables();
        self::maybe_add_column(Database::marketing_logs_table(), 'attempts', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER sent_at");
        self::maybe_add_column(Database::marketing_logs_table(), 'max_attempts', "INT UNSIGNED NOT NULL DEFAULT 3 AFTER attempts");
        self::maybe_add_column(Database::marketing_logs_table(), 'last_try', "DATETIME NULL AFTER max_attempts");
        self::maybe_add_index(Database::marketing_logs_table(), 'attempts', 'KEY attempts (attempts)');
        self::maybe_add_index(Database::marketing_logs_table(), 'last_try', 'KEY last_try (last_try)');
    }

    public static function migrate_to_1065(): void {
        Database::create_tables();
        self::maybe_add_column(Database::marketing_campaigns_table(), 'generate_unique_coupons', "TINYINT(1) NOT NULL DEFAULT 0 AFTER coupon_id");
        self::maybe_add_index(Database::marketing_campaigns_table(), 'generate_unique_coupons', 'KEY generate_unique_coupons (generate_unique_coupons)');
    }

    public static function migrate_to_1066(): void {
        Database::create_tables();
        self::maybe_add_column(Database::marketing_campaigns_table(), 'cta_enabled', "TINYINT(1) NOT NULL DEFAULT 1 AFTER generate_unique_coupons");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'cta_label', "VARCHAR(120) NOT NULL DEFAULT 'Book now' AFTER cta_enabled");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'cta_url_type', "VARCHAR(30) NOT NULL DEFAULT 'booking' AFTER cta_label");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'cta_custom_url', "TEXT NULL AFTER cta_url_type");
        self::maybe_add_index(Database::marketing_campaigns_table(), 'cta_enabled', 'KEY cta_enabled (cta_enabled)');
    }


    public static function migrate_to_1067(): void {
        Database::create_tables();
        self::maybe_add_column(Database::marketing_campaigns_table(), 'audience_statuses', "TEXT NULL AFTER cta_custom_url");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'audience_payment_statuses', "TEXT NULL AFTER audience_statuses");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'audience_last_booking_mode', "VARCHAR(30) NOT NULL DEFAULT 'any' AFTER audience_payment_statuses");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'audience_last_booking_days', "INT UNSIGNED NOT NULL DEFAULT 30 AFTER audience_last_booking_mode");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'audience_min_bookings', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER audience_last_booking_days");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'audience_max_bookings', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER audience_min_bookings");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'audience_min_spent', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER audience_max_bookings");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'audience_max_spent', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER audience_min_spent");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'audience_coupon_filter', "VARCHAR(30) NOT NULL DEFAULT 'any' AFTER audience_max_spent");
        self::maybe_add_index(Database::marketing_campaigns_table(), 'audience_last_booking_mode', 'KEY audience_last_booking_mode (audience_last_booking_mode)');
        self::maybe_add_index(Database::marketing_campaigns_table(), 'audience_coupon_filter', 'KEY audience_coupon_filter (audience_coupon_filter)');
    }


    public static function migrate_to_1069(): void {
        Database::create_tables();
        self::maybe_add_column(Database::marketing_campaigns_table(), 'marketing_headline', "VARCHAR(255) NOT NULL DEFAULT '' AFTER audience_coupon_filter");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'marketing_message', "TEXT NULL AFTER marketing_headline");
        self::maybe_add_column(Database::marketing_campaigns_table(), 'marketing_submessage', "TEXT NULL AFTER marketing_message");
    }


    public static function migrate_to_1086(): void {
        self::maybe_add_column(Database::bookings_table(), 'coupon_usage_recorded', "TINYINT(1) NOT NULL DEFAULT 0 AFTER coupon_discount_amount");
        self::maybe_add_index(Database::bookings_table(), 'coupon_usage_recorded', 'KEY coupon_usage_recorded (coupon_usage_recorded)');
        self::backfill_coupon_usage_recorded();
    }



    public static function migrate_to_1092(): void {
        Database::create_tables();
        self::maybe_add_column(Database::packages_table(), 'price_unit', "VARCHAR(20) NOT NULL DEFAULT 'fixed' AFTER lead_time_minutes");
        self::maybe_add_column(Database::packages_table(), 'hourly_price', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price_unit");
        self::maybe_add_column(Database::packages_table(), 'checkin_time', "TIME NOT NULL DEFAULT '15:00:00' AFTER hourly_price");
        self::maybe_add_column(Database::packages_table(), 'checkout_time', "TIME NOT NULL DEFAULT '11:00:00' AFTER checkin_time");
        self::maybe_add_column(Database::packages_table(), 'min_nights', "INT UNSIGNED NOT NULL DEFAULT 1 AFTER checkout_time");
        self::maybe_add_column(Database::packages_table(), 'max_nights', "INT UNSIGNED NOT NULL DEFAULT 30 AFTER min_nights");
        self::maybe_add_column(Database::packages_table(), 'inventory_units_json', "LONGTEXT NULL AFTER max_nights");
        self::maybe_add_column(Database::packages_table(), 'date_inventory_json', "LONGTEXT NULL AFTER inventory_units_json");
        self::maybe_add_column(Database::packages_table(), 'included_services', "LONGTEXT NULL AFTER date_inventory_json");
        self::maybe_add_column(Database::packages_table(), 'extra_services_json', "LONGTEXT NULL AFTER included_services");
        self::maybe_add_column(Database::packages_table(), 'payment_policy', "VARCHAR(30) NOT NULL DEFAULT 'booking_only' AFTER extra_services_json");
        self::maybe_add_column(Database::packages_table(), 'deposit_type', "VARCHAR(20) NOT NULL DEFAULT 'percent' AFTER payment_policy");
        self::maybe_add_column(Database::packages_table(), 'deposit_value', "DECIMAL(10,2) NOT NULL DEFAULT 30.00 AFTER deposit_type");
        self::maybe_add_column(Database::bookings_table(), 'end_date', "DATE NULL AFTER booking_date");
        self::maybe_add_column(Database::bookings_table(), 'deposit_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
        self::maybe_add_column(Database::bookings_table(), 'extras_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER deposit_amount");
        self::maybe_add_column(Database::bookings_table(), 'selected_extras_json', "LONGTEXT NULL AFTER extras_amount");
        self::maybe_add_column(Database::bookings_table(), 'pricing_mode', "VARCHAR(20) NOT NULL DEFAULT 'fixed' AFTER selected_extras_json");
        self::maybe_add_column(Database::bookings_table(), 'payment_choice', "VARCHAR(30) NOT NULL DEFAULT '' AFTER pricing_mode");
        self::maybe_add_index(Database::bookings_table(), 'end_date', 'KEY end_date (end_date)');
    }


    public static function migrate_to_1093(): void {
        Database::create_tables();
        self::maybe_add_column(Database::bookings_table(), 'gross_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
        self::maybe_add_column(Database::bookings_table(), 'amount_due_now', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER gross_amount");
        self::maybe_add_column(Database::bookings_table(), 'paid_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount_due_now");
        self::maybe_add_column(Database::bookings_table(), 'remaining_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER paid_amount");
        self::maybe_add_column(Database::bookings_table(), 'payment_policy_snapshot_json', "LONGTEXT NULL AFTER payment_choice");
        self::backfill_payment_policy_amounts();
    }

    public static function migrate_to_1094(): void {
        Database::create_tables();
        self::maybe_add_column(Database::packages_table(), 'open_247', "TINYINT(1) NOT NULL DEFAULT 0 AFTER hours_mode");
        self::maybe_add_index(Database::packages_table(), 'open_247', 'KEY open_247 (open_247)');
    }

    public static function migrate_to_1096(): void {
        Database::create_tables();
        self::maybe_add_column(Database::packages_table(), 'mode_configs_json', "LONGTEXT NULL AFTER booking_mode");
    }

    public static function migrate_to_10101(): void {
        Database::create_tables();
        self::maybe_add_column(Database::packages_table(), 'date_flow', "VARCHAR(30) NOT NULL DEFAULT 'customer_choice' AFTER date_inventory_json");
        self::maybe_add_column(Database::packages_table(), 'scheduled_events_json', "LONGTEXT NULL AFTER date_flow");
    }

    public static function migrate_to_10120(): void {
        Database::create_tables();
        self::maybe_add_column(Database::packages_table(), 'show_duration_frontend', "TINYINT(1) NOT NULL DEFAULT 0 AFTER duration_minutes");
    }

    public static function migrate_to_10123(): void {
        Database::create_tables();
        self::maybe_add_column(Database::packages_table(), 'dynamic_pricing_enabled', "TINYINT(1) NOT NULL DEFAULT 0 AFTER discount_value");
        self::maybe_add_column(Database::packages_table(), 'dynamic_weekend_percent', "DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER dynamic_pricing_enabled");
        self::maybe_add_column(Database::packages_table(), 'dynamic_season_start', "DATE NULL AFTER dynamic_weekend_percent");
        self::maybe_add_column(Database::packages_table(), 'dynamic_season_end', "DATE NULL AFTER dynamic_season_start");
        self::maybe_add_column(Database::packages_table(), 'dynamic_season_percent', "DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER dynamic_season_end");
        self::maybe_add_column(Database::packages_table(), 'tax_enabled', "TINYINT(1) NOT NULL DEFAULT 0 AFTER dynamic_season_percent");
        self::maybe_add_column(Database::packages_table(), 'tax_label', "VARCHAR(50) NOT NULL DEFAULT 'VAT' AFTER tax_enabled");
        self::maybe_add_column(Database::packages_table(), 'tax_rate', "DECIMAL(6,2) NOT NULL DEFAULT 0.00 AFTER tax_label");
        self::maybe_add_column(Database::packages_table(), 'tax_mode', "VARCHAR(20) NOT NULL DEFAULT 'exclusive' AFTER tax_rate");
    }


    public static function migrate_to_10124(): void {
        Database::create_tables();
        self::maybe_add_column(Database::bookings_table(), 'tax_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER coupon_discount_amount");
    }


    public static function backfill_coupon_usage_recorded(): void {
        global $wpdb;
        $table = Database::bookings_table();
        $wpdb->query("UPDATE {$table} SET coupon_usage_recorded=1 WHERE coupon_id > 0 AND coupon_usage_recorded=0 AND status IN ('confirmed','completed') AND payment_status <> 'failed'");
    }


    public static function backfill_payment_policy_amounts(): void {
        global $wpdb;
        $table = Database::bookings_table();
        $wpdb->query("UPDATE {$table} SET gross_amount=total_amount WHERE gross_amount=0 AND total_amount > 0");
        $wpdb->query("UPDATE {$table} SET amount_due_now=total_amount WHERE amount_due_now=0 AND total_amount > 0");
        $wpdb->query("UPDATE {$table} SET remaining_amount=GREATEST(gross_amount-total_amount,0) WHERE remaining_amount=0 AND gross_amount > total_amount");
    }

    public static function migrate_to_10128(): void {
        Database::create_tables();
        self::maybe_add_column(Database::packages_table(), 'campaign_note', "VARCHAR(255) NOT NULL DEFAULT '' AFTER discount_value");
    }


    public static function maybe_add_columns(): void {
        self::maybe_add_column(Database::bookings_table(),'user_id',"BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER id");
        self::maybe_add_column(Database::packages_table(),'category_id',"BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER id");
        self::maybe_add_column(Database::packages_table(),'discount_type',"VARCHAR(20) NOT NULL DEFAULT 'none' AFTER price");
        self::maybe_add_column(Database::packages_table(),'discount_value',"DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER discount_type");
        self::maybe_add_column(Database::packages_table(),'campaign_note',"VARCHAR(255) NOT NULL DEFAULT '' AFTER discount_value");
        self::maybe_add_column(Database::packages_table(),'dynamic_pricing_enabled',"TINYINT(1) NOT NULL DEFAULT 0 AFTER discount_value");
        self::maybe_add_column(Database::packages_table(),'dynamic_weekend_percent',"DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER dynamic_pricing_enabled");
        self::maybe_add_column(Database::packages_table(),'dynamic_season_start',"DATE NULL AFTER dynamic_weekend_percent");
        self::maybe_add_column(Database::packages_table(),'dynamic_season_end',"DATE NULL AFTER dynamic_season_start");
        self::maybe_add_column(Database::packages_table(),'dynamic_season_percent',"DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER dynamic_season_end");
        self::maybe_add_column(Database::packages_table(),'tax_enabled',"TINYINT(1) NOT NULL DEFAULT 0 AFTER dynamic_season_percent");
        self::maybe_add_column(Database::packages_table(),'tax_label',"VARCHAR(50) NOT NULL DEFAULT 'VAT' AFTER tax_enabled");
        self::maybe_add_column(Database::packages_table(),'tax_rate',"DECIMAL(6,2) NOT NULL DEFAULT 0.00 AFTER tax_label");
        self::maybe_add_column(Database::bookings_table(),'tax_amount',"DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER coupon_discount_amount");
        self::maybe_add_column(Database::packages_table(),'tax_mode',"VARCHAR(20) NOT NULL DEFAULT 'exclusive' AFTER tax_rate");
        self::maybe_add_column(Database::packages_table(),'booking_mode',"VARCHAR(20) NOT NULL DEFAULT 'fixed' AFTER lead_time_minutes");
        self::maybe_add_column(Database::packages_table(),'mode_configs_json',"LONGTEXT NULL AFTER booking_mode");
        self::maybe_add_column(Database::packages_table(),'checkout_mode',"VARCHAR(30) NOT NULL DEFAULT 'booking_only' AFTER booking_mode");
        self::maybe_add_column(Database::packages_table(),'show_duration_frontend',"TINYINT(1) NOT NULL DEFAULT 0 AFTER duration_minutes");
        self::maybe_add_column(Database::packages_table(),'card_image_id',"BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER show_more_info");
        self::maybe_add_column(Database::packages_table(),'booking_card_image_id',"BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER card_image_id");
        self::maybe_add_column(Database::packages_table(),'popular_icon',"VARCHAR(20) NOT NULL DEFAULT '' AFTER is_popular");
        self::maybe_add_column(Database::packages_table(),'popular_icon_color',"VARCHAR(20) NOT NULL DEFAULT '#7c3aed' AFTER popular_icon");
        self::maybe_add_column(Database::packages_table(),'popular_icon_size',"SMALLINT UNSIGNED NOT NULL DEFAULT 24 AFTER popular_icon_color");
        self::maybe_add_index(Database::bookings_table(),'user_id',"KEY user_id (user_id)");
        self::maybe_add_index(Database::packages_table(),'category_id',"KEY category_id (category_id)");
    }

    public static function maybe_add_column(string $table,string $column,string $definition): void {
        global $wpdb;
        $table = self::sql_table_identifier($table);
        $column_name = self::sql_identifier_name($column);
        $column = self::sql_identifier($column_name);
        $exists=$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column_name));
        if (!$exists) { $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}"); }
    }

    public static function maybe_add_index(string $table,string $name,string $definition): void {
        global $wpdb;
        $table = self::sql_table_identifier($table);
        $name = self::sql_identifier_name($name);
        $exists=$wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$table} WHERE Key_name=%s",$name));
        if (!$exists) { $wpdb->query("ALTER TABLE {$table} ADD {$definition}"); }
    }

    private static function sql_table_identifier(string $table): string {
        return self::sql_identifier($table);
    }

    private static function sql_identifier(string $identifier): string {
        return '`' . str_replace('`', '``', self::sql_identifier_name($identifier)) . '`';
    }

    private static function sql_identifier_name(string $identifier): string {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new \InvalidArgumentException('Unsafe SQL identifier in Slotera migration.');
        }
        return $identifier;
    }
    public static function migrate_to_10233(): void {
        // Clear persisted frontend overrides for no/nb, is and ga so registry translations are used.
        $custom = get_option('sltr_translations', []);
        if (!is_array($custom)) { return; }
        $locales = ['no_NO', 'no', 'nb_NO', 'nb', 'is_IS', 'is', 'ga', 'ga_IE'];
        $changed = false;
        if (isset($custom['frontend']) && is_array($custom['frontend'])) {
            foreach ($locales as $locale) {
                if (isset($custom['frontend'][$locale])) { unset($custom['frontend'][$locale]); $changed = true; }
            }
        }
        foreach ($locales as $locale) {
            if (isset($custom[$locale]) && is_array($custom[$locale])) {
                foreach (array_keys($custom[$locale]) as $key) {
                    if (strpos((string) $key, 'frontend.') === 0) { unset($custom[$locale][$key]); $changed = true; }
                }
                if ($custom[$locale] === []) { unset($custom[$locale]); }
            }
        }
        if ($changed) { update_option('sltr_translations', $custom, false); }
    }


    public static function migrate_to_10234(): void {
        // Registering these locales in the language selector: clear any old persisted frontend overrides again.
        $custom = get_option('sltr_translations', []);
        if (!is_array($custom)) { return; }
        $locales = ['no_NO', 'no', 'nb_NO', 'nb', 'is_IS', 'is', 'ga_IE', 'ga'];
        $changed = false;
        if (isset($custom['frontend']) && is_array($custom['frontend'])) {
            foreach ($locales as $locale) {
                if (isset($custom['frontend'][$locale])) { unset($custom['frontend'][$locale]); $changed = true; }
            }
        }
        foreach ($locales as $locale) {
            if (isset($custom[$locale]) && is_array($custom[$locale])) {
                foreach (array_keys($custom[$locale]) as $key) {
                    if (strpos((string) $key, 'frontend.') === 0) { unset($custom[$locale][$key]); $changed = true; }
                }
                if ($custom[$locale] === []) { unset($custom[$locale]); }
            }
        }
        if ($changed) { update_option('sltr_translations', $custom, false); }
    }


    public static function migrate_to_10829(): void {
        self::maybe_add_column(Database::packages_table(), 'card_image_focus', "VARCHAR(15) NOT NULL DEFAULT '50,50' AFTER booking_card_image_id");
        self::maybe_add_column(Database::packages_table(), 'booking_card_image_focus', "VARCHAR(15) NOT NULL DEFAULT '50,50' AFTER card_image_focus");
        self::maybe_add_column(Database::packages_table(), 'slider_image_focus_json', "LONGTEXT NULL AFTER slider_image_ids");
        self::maybe_add_column(Database::packages_table(), 'gallery_image_focus', "VARCHAR(15) NOT NULL DEFAULT '50,50' AFTER gallery_image_ids");
    }

}
