<?php
declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\MarketingEmailService;
use Slotera\Application\Services\MarketingAutomationService;
use Slotera\Application\Services\PromotionCampaignService;
use Slotera\Application\Services\LicenseService;
use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\EmailTemplateRegistry;
use Slotera\Infrastructure\Repositories\CouponRepository;
use Slotera\Infrastructure\Repositories\MarketingCampaignRepository;
use Slotera\Infrastructure\Repositories\MarketingLogRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class MarketingController
{
    private RequestValidator $request;
    private MarketingCampaignRepository $repo;

    public function __construct(?RequestValidator $request = null, ?MarketingCampaignRepository $repo = null)
    {
        $this->request = $request ?: new RequestValidator();
        $this->repo = $repo ?: new MarketingCampaignRepository();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_marketing_campaign', [$this, 'save']);
        add_action('admin_post_sltr_delete_marketing_campaign', [$this, 'delete']);
        add_action('admin_post_sltr_send_marketing_test', [$this, 'send_test']);
        add_action('admin_post_sltr_send_marketing_campaign', [$this, 'queue_campaign']);
        add_action('admin_post_sltr_process_marketing_queue_now', [$this, 'process_queue_now']);
        add_action('admin_post_sltr_pause_marketing_campaign', [$this, 'pause_campaign']);
        add_action('admin_post_sltr_resume_marketing_campaign', [$this, 'resume_campaign']);
        add_action('admin_post_sltr_stop_marketing_campaign', [$this, 'stop_campaign']);
        add_action('admin_post_sltr_save_marketing_settings', [$this, 'save_settings']);
        add_action('admin_post_sltr_retry_failed_marketing_campaign', [$this, 'retry_failed']);
        add_action('admin_post_sltr_preview_marketing_campaign', [$this, 'preview']);
        add_action('admin_post_sltr_preview_marketing_automation', [$this, 'preview_automation']);
        add_action('admin_post_sltr_send_marketing_automation_test', [$this, 'send_automation_test']);
        add_action('admin_post_sltr_save_comeback_automation', [$this, 'save_comeback_automation']);
        add_action('admin_post_sltr_save_after_booking_automation', [$this, 'save_after_booking_automation']);
        add_action('admin_post_sltr_stop_marketing_automation', [$this, 'stop_marketing_automation']);
        add_action('admin_post_sltr_run_marketing_automation', [$this, 'run_marketing_automation']);
        add_action('admin_post_sltr_save_promotion_digest', [$this, 'save_promotion_digest']);
        add_action('admin_post_sltr_send_promotion_test', [$this, 'send_promotion_test']);
        add_action('admin_post_sltr_send_promotion_now', [$this, 'send_promotion_now']);
    }

    public function save(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $this->request->verify_admin_nonce('sltr_save_marketing_campaign');
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
        if (!(new LicenseService())->can_use_marketing() && $id <= 0) {
            wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns&license_limited=1'));
            exit;
        }
        $license = new LicenseService();
        $current = $id > 0 ? $this->repo->get_by_id($id) : null;
        $audience_type = isset($_POST['audience_type']) ? sanitize_key(wp_unslash((string) $_POST['audience_type'])) : 'all';
        if ($audience_type === 'advanced' && !$license->can_use_advanced_marketing()) { $audience_type = 'all'; }
        $generate_unique = !empty(wp_unslash((string) ($_POST['generate_unique_coupons'] ?? ''))) && $license->can_use_unique_coupons() ? 1 : 0;
        $campaign_source = 'coupon_bound';
        $posted_coupon_id = isset($_POST['coupon_id']) ? absint(wp_unslash((string) $_POST['coupon_id'])) : 0;
        $bound_coupon_id = $current ? absint($current['coupon_id'] ?? 0) : $posted_coupon_id;
        $bound_coupon = $bound_coupon_id > 0 ? (new CouponRepository())->get_by_id($bound_coupon_id) : null;
        if (!$bound_coupon) {
            wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns&binding_error=1'));
            exit;
        }
        $bound_package_ids = array_values(array_filter(array_map('absint', explode(',', (string) ($bound_coupon['package_ids'] ?? '')))));
        $posted_coupon_id = $bound_coupon_id;
        $posted_package_id = count($bound_package_ids) === 1 ? (int) $bound_package_ids[0] : 0;

        $data = [
            'name' => isset($_POST['name']) ? sanitize_text_field(wp_unslash((string) $_POST['name'])) : '',
            'template_key' => isset($_POST['template_key']) ? sanitize_key(wp_unslash((string) $_POST['template_key'])) : '',
            'subject_override' => isset($_POST['subject_override']) ? sanitize_text_field(wp_unslash((string) $_POST['subject_override'])) : '',
            'audience_type' => $audience_type,
            'package_id' => $posted_package_id,
            'coupon_id' => $posted_coupon_id,

            'audience_statuses' => $this->sanitize_csv_from_post('audience_statuses'),
            'audience_payment_statuses' => $this->sanitize_csv_from_post('audience_payment_statuses'),
            'audience_last_booking_mode' => isset($_POST['audience_last_booking_mode']) ? sanitize_key(wp_unslash((string) $_POST['audience_last_booking_mode'])) : 'any',
            'audience_last_booking_days' => isset($_POST['audience_last_booking_days']) ? absint(wp_unslash((string) $_POST['audience_last_booking_days'])) : 30,
            'audience_min_bookings' => isset($_POST['audience_min_bookings']) ? absint(wp_unslash((string) $_POST['audience_min_bookings'])) : 0,
            'audience_max_bookings' => isset($_POST['audience_max_bookings']) ? absint(wp_unslash((string) $_POST['audience_max_bookings'])) : 0,
            'audience_min_spent' => isset($_POST['audience_min_spent']) ? (float) wp_unslash((string) $_POST['audience_min_spent']) : 0,
            'audience_max_spent' => isset($_POST['audience_max_spent']) ? (float) wp_unslash((string) $_POST['audience_max_spent']) : 0,
            'audience_coupon_filter' => isset($_POST['audience_coupon_filter']) ? sanitize_key(wp_unslash((string) $_POST['audience_coupon_filter'])) : 'any',
            'marketing_headline' => isset($_POST['marketing_headline']) ? sanitize_text_field(wp_unslash((string) $_POST['marketing_headline'])) : '',
            'marketing_message' => isset($_POST['marketing_message']) ? wp_kses_post(wp_unslash((string) $_POST['marketing_message'])) : '',
            'marketing_submessage' => isset($_POST['marketing_submessage']) ? wp_kses_post(wp_unslash((string) $_POST['marketing_submessage'])) : '',
            'generate_unique_coupons' => $generate_unique,
            'cta_enabled' => !empty(wp_unslash((string) ($_POST['cta_enabled'] ?? ''))) ? 1 : 0,
            'cta_label' => $this->sanitize_automation_cta_label('cta_label'),
            'cta_url_type' => isset($_POST['cta_url_type']) ? sanitize_key(wp_unslash((string) $_POST['cta_url_type'])) : 'booking',
            'cta_custom_url' => isset($_POST['cta_custom_url']) ? esc_url_raw(wp_unslash((string) $_POST['cta_custom_url'])) : '',
            'source' => $campaign_source,
            'status' => $current['status'] ?? 'draft',
        ];
        if ($id > 0) { $this->repo->update($id, $data); } else { $id = $this->repo->create($data); }
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&campaign_saved=1'));
        exit;
    }

    public function delete(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
        $this->request->verify_admin_nonce('sltr_delete_marketing_campaign_' . $id);
        $campaign = $id > 0 ? $this->repo->get_by_id($id) : null;
        $source = $campaign ? (new MarketingLogRepository())->campaign_source($id, (string) ($campaign['name'] ?? '')) : 'coupon';

        if ($campaign && sanitize_key((string) ($campaign['source'] ?? '')) === 'automation') {
            $automation_type = sanitize_key((string) ($campaign['automation_type'] ?? ''));
            if (in_array($automation_type, ['come_back', 'after_booking'], true)) {
                $settings_key = $automation_type === 'after_booking'
                    ? 'after_booking_automation_enabled'
                    : 'comeback_automation_enabled';
                (new SettingsRepository())->update([$settings_key => 0]);

                foreach ($this->repo->get_all() as $automation_campaign) {
                    if (
                        sanitize_key((string) ($automation_campaign['source'] ?? '')) !== 'automation'
                        || sanitize_key((string) ($automation_campaign['automation_type'] ?? '')) !== $automation_type
                    ) {
                        continue;
                    }
                    $this->delete_campaign_record((int) ($automation_campaign['id'] ?? 0), $automation_campaign);
                }
            }
        } elseif ($id > 0) {
            $this->delete_campaign_record($id, $campaign ?: []);
        }

        if (!empty($_POST['return_coupons'])) {
            wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&campaign_deleted=1'));
        } else {
            wp_safe_redirect($this->campaign_history_url($source, 'deleted=1'));
        }
        exit;
    }

    private function delete_campaign_record(int $id, array $campaign): void
    {
        if ($id <= 0) { return; }
        (new MarketingLogRepository())->delete_for_campaign($id);
        $offer_coupon_id = absint($campaign['coupon_id'] ?? 0);
        if ($offer_coupon_id > 0 && sanitize_key((string) ($campaign['source'] ?? '')) === 'automation') {
            $offer_coupon = (new CouponRepository())->get_by_id($offer_coupon_id);
            if ($offer_coupon && str_contains((string) ($offer_coupon['description'] ?? ''), '[slotera:automation_offer_template]')) {
                (new CouponRepository())->delete($offer_coupon_id);
            }
        }
        $this->repo->delete($id);
    }

    public function send_test(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
        $this->request->verify_admin_nonce('sltr_send_marketing_test_' . $id);
        $to = isset($_POST['test_email']) ? sanitize_email(wp_unslash((string) $_POST['test_email'])) : '';
        $ok = (new MarketingEmailService())->send_test($id, $to);
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns&action=edit&id=' . $id . ($ok ? '&test_sent=1' : '&test_failed=1')));
        exit;
    }

    public function queue_campaign(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
        $this->request->verify_admin_nonce('sltr_send_marketing_campaign_' . $id);
        $result = (new MarketingEmailService())->queue_campaign($id);
        $limited = (($result['reason'] ?? '') === 'license_limited') ? '&license_limited=1' : '';
        $email_settings = (($result['reason'] ?? '') === 'email_settings_required') ? '&email_settings_required=1&email_settings_issue=' . rawurlencode((string) ($result['email_settings_issue'] ?? '')) : '';
        if (!empty($_POST['return_coupons'])) {
            $skip_details = '&skipped_already_queued=' . (int) ($result['skipped_already_queued'] ?? 0)
                . '&skipped_unsubscribed=' . (int) ($result['skipped_unsubscribed'] ?? 0)
                . '&skipped_invalid_email=' . (int) ($result['skipped_invalid_email'] ?? 0)
                . '&skipped_invalid_recipient=' . (int) ($result['skipped_invalid_recipient'] ?? 0);
            wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&campaign_queued=1&queued=' . (int) $result['queued'] . '&skipped=' . (int) $result['skipped'] . $skip_details . $limited . $email_settings));
        } else {
            wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns&action=edit&id=' . $id . '&queued=' . (int) $result['queued'] . '&skipped=' . (int) $result['skipped'] . $limited . $email_settings));
        }
        exit;
    }

    public function process_queue_now(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
        $this->request->verify_admin_nonce('sltr_process_marketing_queue_now_' . $id);
        $result = $id > 0 ? (new MarketingEmailService())->process_campaign_queue($id) : ['sent' => 0, 'failed' => 0];
        $limited = (($result['reason'] ?? '') === 'license_limited') ? '&license_limited=1' : '';
        $email_settings = (($result['reason'] ?? '') === 'email_settings_required') ? '&email_settings_required=1&email_settings_issue=' . rawurlencode((string) ($result['email_settings_issue'] ?? '')) : '';
        wp_safe_redirect($this->campaign_return_url($id, 'processed_sent=' . (int) $result['sent'] . '&processed_failed=' . (int) $result['failed'] . $limited . $email_settings));
        exit;
    }

    public function pause_campaign(): void { $this->change_status_from_post('paused', 'paused=1', ['queued', 'sending']); }
    public function resume_campaign(): void { $this->change_status_from_post('queued', 'resumed=1', ['paused']); }
    public function stop_campaign(): void { $this->change_status_from_post('stopped', 'cancelled=1', ['queued', 'sending', 'paused']); }

    public function retry_failed(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
        $this->request->verify_admin_nonce('sltr_retry_failed_marketing_campaign_' . $id);
        $retried = $id > 0 ? (new MarketingLogRepository())->retry_failed_for_campaign($id) : 0;
        if ($retried > 0) { $this->repo->update_status($id, 'queued'); (new MarketingEmailService())->ensure_scheduled(); }
        wp_safe_redirect($this->campaign_return_url($id, 'retried=' . (int) $retried));
        exit;
    }


    public function preview(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $id = isset($_GET['id']) ? absint(wp_unslash((string) $_GET['id'])) : 0;
        $this->request->verify_admin_nonce('sltr_preview_marketing_campaign_' . $id);
        $preview_email = isset($_GET['preview_email']) ? sanitize_email(wp_unslash((string) $_GET['preview_email'])) : '';
        $message = (new MarketingEmailService())->preview_message($id, $preview_email);
        if (!$message) {
            wp_die(esc_html__('Campaign preview is not available.', 'slotera-booking'));
        }
        nocache_headers();
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . esc_html__('Campaign preview', 'slotera-booking') . '</title>';
        echo '<style>body{margin:0;background:#f6f7f7;font-family:Arial,sans-serif}.sltr-preview-subject{position:sticky;top:0;z-index:2;background:#fff;border-bottom:1px solid #dcdcde;padding:12px 16px;font-size:14px}.sltr-preview-subject strong{display:inline-block;margin-right:8px}.sltr-preview-note{color:#646970;font-size:12px;margin-top:4px}</style>';
        echo '</head><body>';
        echo '<div class="sltr-preview-subject"><strong>' . esc_html__('Subject:', 'slotera-booking') . '</strong>' . esc_html((string) $message['subject']) . '<div class="sltr-preview-note">' . esc_html__('Preview only. No campaign queue, log entry, or real unique coupon is created.', 'slotera-booking') . '</div></div>';
        echo (string) $message['body'];
        echo '</body></html>';
        exit;
    }

    public function preview_automation(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $type = isset($_GET['type']) ? sanitize_key(wp_unslash((string) $_GET['type'])) : '';
        $this->request->verify_admin_nonce('sltr_preview_marketing_automation_' . $type);
        $email = isset($_GET['preview_email']) ? sanitize_email(wp_unslash((string) $_GET['preview_email'])) : '';
        $message = (new MarketingEmailService())->preview_message_for_campaign($this->automation_preview_campaign($type), $email);
        if (!$message) { wp_die(esc_html__('Unable to build automation preview.', 'slotera-booking')); }
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . esc_html__('Campaign preview', 'slotera-booking') . '</title></head><body>';
        echo '<div style="background:#fff;border-bottom:1px solid #dcdcde;padding:12px 16px"><strong>' . esc_html__('Subject:', 'slotera-booking') . '</strong> ' . esc_html((string) $message['subject']) . '</div>';
        echo (string) $message['body'];
        echo '</body></html>';
        exit;
    }

    public function send_automation_test(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $type = isset($_POST['type']) ? sanitize_key(wp_unslash((string) $_POST['type'])) : '';
        $this->request->verify_admin_nonce('sltr_send_marketing_automation_test_' . $type);
        $to = isset($_POST['test_email']) ? sanitize_email(wp_unslash((string) $_POST['test_email'])) : '';
        $ok = (new MarketingEmailService())->send_test_for_campaign($this->automation_preview_campaign($type), $to);
        $tab = $type === 'after-booking' ? 'after-booking' : 'come-back';
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=' . $tab . ($ok ? '&test_sent=1' : '&test_failed=1')));
        exit;
    }

    private function automation_preview_campaign(string $type): array
    {
        $settings = (new SettingsRepository())->all();
        $after = $type === 'after-booking';
        $prefix = $after ? 'after_booking_automation_' : 'comeback_automation_';
        return [
            'name' => $after ? __('After booking automation preview', 'slotera-booking') : __('Come back automation preview', 'slotera-booking'),
            'template_key' => sanitize_key((string) ($settings[$prefix . 'template_key'] ?? 'marketing_promo')),
            'subject_override' => (string) ($settings[$prefix . 'subject_override'] ?? ''),
            'audience_type' => 'all', 'package_id' => 0,
            'coupon_id' => 0,
            'generate_unique_coupons' => 1,
            'automation_offer' => [
                'code' => 'A7F3K9Q2-X8M4P6RT',
                'discount_type' => (string) ($settings[$prefix . 'offer_discount_type'] ?? 'percent'),
                'discount_value' => (float) ($settings[$prefix . 'offer_discount_value'] ?? 10),
                'expires_at' => wp_date('Y-m-d', current_time('timestamp') + max(1, absint($settings[$prefix . 'offer_valid_days'] ?? 14)) * DAY_IN_SECONDS),
                'package_ids' => (string) ($settings[$prefix . 'offer_package_ids'] ?? ''),
            ],
            'cta_enabled' => (int) ($settings[$prefix . 'cta_enabled'] ?? 1),
            'cta_label' => (string) ($settings[$prefix . 'cta_label'] ?? ''),
            'cta_url_type' => (string) ($settings[$prefix . 'cta_url_type'] ?? 'booking'),
            'cta_custom_url' => (string) ($settings[$prefix . 'cta_custom_url'] ?? ''),
            'marketing_headline' => (string) ($settings[$prefix . 'headline'] ?? ''),
            'marketing_message' => (string) ($settings[$prefix . 'message'] ?? ''),
            'marketing_submessage' => (string) ($settings[$prefix . 'submessage'] ?? ''),
        ];
    }

    public function save_settings(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $this->request->verify_admin_nonce('sltr_save_marketing_settings');
        $license = new LicenseService();
        if (!$license->can_manage_queue_settings()) {
            $return_tab = isset($_POST['return_marketing_tab']) ? sanitize_key(wp_unslash((string) $_POST['return_marketing_tab'])) : '';
            if (in_array($return_tab, ['come-back', 'after-booking'], true)) {
                wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=' . $return_tab . '&license_limited=1'));
                exit;
            }
            $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
            wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns' . ($id > 0 ? '&action=edit&id=' . $id : '') . '&license_limited=1'));
            exit;
        }
        (new SettingsRepository())->update([
            'marketing_emails_per_batch' => isset($_POST['marketing_emails_per_batch']) ? absint(wp_unslash((string) $_POST['marketing_emails_per_batch'])) : 10,
            'marketing_cron_interval' => isset($_POST['marketing_cron_interval']) ? absint(wp_unslash((string) $_POST['marketing_cron_interval'])) : 5,
            'marketing_max_attempts' => isset($_POST['marketing_max_attempts']) ? absint(wp_unslash((string) $_POST['marketing_max_attempts'])) : 3,
            'marketing_require_opt_out_check' => 1,
            'marketing_require_unsubscribe_link' => 1,
            'marketing_minimize_log_payload' => 1,
        ]);
        (new MarketingEmailService())->ensure_scheduled(true);
        $return_tab = isset($_POST['return_marketing_tab']) ? sanitize_key(wp_unslash((string) $_POST['return_marketing_tab'])) : '';
        if (in_array($return_tab, ['come-back', 'after-booking'], true)) {
            wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=' . $return_tab . '&settings_updated=1'));
            exit;
        }
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns' . ($id > 0 ? '&action=edit&id=' . $id : '') . '&settings_updated=1'));
        exit;
    }


    private function automation_offer_settings_from_post(string $prefix): array
    {
        $package_ids = isset($_POST[$prefix . 'offer_package_ids']) ? wp_unslash($_POST[$prefix . 'offer_package_ids']) : [];
        if (!is_array($package_ids)) { $package_ids = explode(',', (string) $package_ids); }
        $package_ids = array_values(array_unique(array_filter(array_map('absint', $package_ids))));
        return [
            $prefix . 'offer_discount_type' => isset($_POST[$prefix . 'offer_discount_type']) ? sanitize_key(wp_unslash((string) $_POST[$prefix . 'offer_discount_type'])) : 'percent',
            $prefix . 'offer_discount_value' => isset($_POST[$prefix . 'offer_discount_value']) ? max(0, (float) wp_unslash((string) $_POST[$prefix . 'offer_discount_value'])) : 10,
            $prefix . 'offer_valid_days' => isset($_POST[$prefix . 'offer_valid_days']) ? max(1, absint(wp_unslash((string) $_POST[$prefix . 'offer_valid_days']))) : 14,
            $prefix . 'offer_package_ids' => implode(',', $package_ids),
        ];
    }

    private function automation_queue_settings_from_post(): array
    {
        if (!(new LicenseService())->can_manage_queue_settings()) { return []; }
        return [
            'marketing_emails_per_batch' => isset($_POST['marketing_emails_per_batch']) ? absint(wp_unslash((string) $_POST['marketing_emails_per_batch'])) : 10,
            'marketing_cron_interval' => isset($_POST['marketing_cron_interval']) ? absint(wp_unslash((string) $_POST['marketing_cron_interval'])) : 5,
            'marketing_max_attempts' => isset($_POST['marketing_max_attempts']) ? absint(wp_unslash((string) $_POST['marketing_max_attempts'])) : 3,
            'marketing_require_opt_out_check' => 1,
            'marketing_require_unsubscribe_link' => 1,
            'marketing_minimize_log_payload' => 1,
        ];
    }

    public function save_comeback_automation(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $this->request->verify_admin_nonce('sltr_save_comeback_automation');
        (new SettingsRepository())->update(array_merge([
            'comeback_automation_enabled' => (new LicenseService())->can_use_automations() ? 1 : 0,
            'comeback_automation_inactive_days' => isset($_POST['comeback_automation_inactive_days']) ? absint(wp_unslash((string) $_POST['comeback_automation_inactive_days'])) : 30,
            'comeback_automation_repeat_days' => isset($_POST['comeback_automation_repeat_days']) ? absint(wp_unslash((string) $_POST['comeback_automation_repeat_days'])) : 90,
            'comeback_automation_template_key' => isset($_POST['comeback_automation_template_key']) ? sanitize_key(wp_unslash((string) $_POST['comeback_automation_template_key'])) : 'marketing_promo',
            'comeback_automation_subject_override' => isset($_POST['comeback_automation_subject_override']) ? sanitize_text_field(wp_unslash((string) $_POST['comeback_automation_subject_override'])) : '',
            'comeback_automation_headline' => isset($_POST['comeback_automation_headline']) ? sanitize_text_field(wp_unslash((string) $_POST['comeback_automation_headline'])) : '',
            'comeback_automation_message' => isset($_POST['comeback_automation_message']) ? wp_kses_post(wp_unslash((string) $_POST['comeback_automation_message'])) : '',
            'comeback_automation_submessage' => isset($_POST['comeback_automation_submessage']) ? wp_kses_post(wp_unslash((string) $_POST['comeback_automation_submessage'])) : '',
            'comeback_automation_cta_enabled' => !empty(wp_unslash((string) ($_POST['comeback_automation_cta_enabled'] ?? ''))) ? 1 : 0,
            'comeback_automation_cta_label' => $this->sanitize_automation_cta_label('comeback_automation_cta_label'),
            'comeback_automation_cta_url_type' => isset($_POST['comeback_automation_cta_url_type']) ? sanitize_key(wp_unslash((string) $_POST['comeback_automation_cta_url_type'])) : 'booking',
            'comeback_automation_cta_custom_url' => isset($_POST['comeback_automation_cta_custom_url']) ? esc_url_raw(wp_unslash((string) $_POST['comeback_automation_cta_custom_url'])) : '',
        ], $this->automation_offer_settings_from_post('comeback_automation_'), $this->automation_queue_settings_from_post()));
        $automation = new MarketingAutomationService();
        $automation->ensure_scheduled(true);
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=come-back&automation_saved=1'));
        exit;
    }


    public function save_after_booking_automation(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $this->request->verify_admin_nonce('sltr_save_after_booking_automation');
        (new SettingsRepository())->update(array_merge([
            'after_booking_automation_enabled' => (new LicenseService())->can_use_automations() ? 1 : 0,
            'after_booking_automation_delay_days' => isset($_POST['after_booking_automation_delay_days']) ? absint(wp_unslash((string) $_POST['after_booking_automation_delay_days'])) : 3,
            'after_booking_automation_repeat_days' => isset($_POST['after_booking_automation_repeat_days']) ? absint(wp_unslash((string) $_POST['after_booking_automation_repeat_days'])) : 30,
            'after_booking_automation_template_key' => isset($_POST['after_booking_automation_template_key']) ? sanitize_key(wp_unslash((string) $_POST['after_booking_automation_template_key'])) : 'marketing_promo',
            'after_booking_automation_subject_override' => isset($_POST['after_booking_automation_subject_override']) ? sanitize_text_field(wp_unslash((string) $_POST['after_booking_automation_subject_override'])) : '',
            'after_booking_automation_headline' => isset($_POST['after_booking_automation_headline']) ? sanitize_text_field(wp_unslash((string) $_POST['after_booking_automation_headline'])) : '',
            'after_booking_automation_message' => isset($_POST['after_booking_automation_message']) ? wp_kses_post(wp_unslash((string) $_POST['after_booking_automation_message'])) : '',
            'after_booking_automation_submessage' => isset($_POST['after_booking_automation_submessage']) ? wp_kses_post(wp_unslash((string) $_POST['after_booking_automation_submessage'])) : '',
            'after_booking_automation_cta_enabled' => !empty(wp_unslash((string) ($_POST['after_booking_automation_cta_enabled'] ?? ''))) ? 1 : 0,
            'after_booking_automation_cta_label' => $this->sanitize_automation_cta_label('after_booking_automation_cta_label'),
            'after_booking_automation_cta_url_type' => isset($_POST['after_booking_automation_cta_url_type']) ? sanitize_key(wp_unslash((string) $_POST['after_booking_automation_cta_url_type'])) : 'booking',
            'after_booking_automation_cta_custom_url' => isset($_POST['after_booking_automation_cta_custom_url']) ? esc_url_raw(wp_unslash((string) $_POST['after_booking_automation_cta_custom_url'])) : '',
        ], $this->automation_offer_settings_from_post('after_booking_automation_'), $this->automation_queue_settings_from_post()));
        $automation = new MarketingAutomationService();
        $automation->ensure_scheduled(true);
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=after-booking&after_booking_automation_saved=1'));
        exit;
    }

    private function sanitize_automation_cta_label(string $field): string
    {
        $label = isset($_POST[$field]) ? sanitize_text_field(wp_unslash((string) $_POST[$field])) : '';
        $localized_default = \sltr_t('Book now', 'emails', EmailTemplateRegistry::runtime_locale());
        return $label === $localized_default || $label === 'Book now' || $label === 'Book again' ? '' : $label;
    }

    public function stop_marketing_automation(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
        $this->request->verify_admin_nonce('sltr_marketing_automation_toggle_' . $id);
        $campaign = $id > 0 ? $this->repo->get_by_id($id) : null;
        if ($campaign && sanitize_key((string) ($campaign['source'] ?? '')) === 'automation') {
            $type = sanitize_key((string) ($campaign['automation_type'] ?? ''));
            $key = $type === 'after_booking' ? 'after_booking_automation_enabled' : 'comeback_automation_enabled';
            (new SettingsRepository())->update([$key => 0]);
            if (in_array((string) ($campaign['status'] ?? ''), ['queued', 'sending', 'paused'], true)) {
                $this->repo->update_status($id, 'stopped');
            }
        }
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=campaigns&automation_stopped=1'));
        exit;
    }

    public function run_marketing_automation(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;

        if ($id > 0) {
            $this->request->verify_admin_nonce('sltr_marketing_automation_toggle_' . $id);
            $campaign = $this->repo->get_by_id($id);
            if (!$campaign || sanitize_key((string) ($campaign['source'] ?? '')) !== 'automation') {
                wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=campaigns'));
                exit;
            }
            $type = sanitize_key((string) ($campaign['automation_type'] ?? ''));
        } else {
            $type = sanitize_key(wp_unslash((string) ($_POST['type'] ?? '')));
            if (!in_array($type, ['after_booking', 'come_back'], true)) {
                wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation'));
                exit;
            }
            $this->request->verify_admin_nonce('sltr_run_marketing_automation_' . $type);
        }

        $settings = new SettingsRepository();
        if ($type === 'after_booking') {
            $settings->update(['after_booking_automation_enabled' => 1]);
            $result = (new MarketingAutomationService())->process_after_booking(true);
        } else {
            $settings->update(['comeback_automation_enabled' => 1]);
            $result = (new MarketingAutomationService())->process(true);
        }

        if ((int) ($result['queued'] ?? 0) > 0) {
            (new MarketingEmailService())->process_campaign_queue((int) ($result['campaign_id'] ?? 0));
        }

        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=campaigns&automation_started=1'));
        exit;
    }

    public function save_promotion_digest(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $this->request->verify_admin_nonce('sltr_save_promotion_digest');
        (new PromotionCampaignService())->save_settings(wp_unslash($_POST));
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=promotions&promotion_saved=1'));
        exit;
    }

    public function send_promotion_test(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $this->request->verify_admin_nonce('sltr_send_promotion_test');
        $email = sanitize_email(wp_unslash((string) ($_POST['promotion_test_email'] ?? '')));
        $service = new PromotionCampaignService();
        (new SettingsRepository())->update(['promotion_digest_test_email' => $email]);
        $ok = $email !== '' && $service->send_test($email, wp_unslash($_POST));
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=promotions&' . ($ok ? 'promotion_test_sent=1' : 'promotion_test_failed=1')));
        exit;
    }

    public function send_promotion_now(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $this->request->verify_admin_nonce('sltr_send_promotion_now');
        $result = (new PromotionCampaignService())->send_now('manual', wp_unslash($_POST));
        $url = add_query_arg(['page' => 'slotera-marketing', 'sltr_marketing_section' => 'promotions', 'promotion_queued' => absint($result['queued'] ?? 0)], admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }

    private function sanitize_csv_from_post(string $key): string
    {
        $raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];
        if (!is_array($raw)) {
            $raw = explode(',', (string) wp_unslash($raw));
        }
        $values = [];
        foreach ($raw as $value) {
            $value = sanitize_key(wp_unslash((string) $value));
            if ($value !== '') { $values[] = $value; }
        }
        return implode(',', array_values(array_unique($values)));
    }

    private function change_status_from_post(string $status, string $flag, array $allowed_from = []): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
        $this->request->verify_admin_nonce('sltr_marketing_status_' . $id);
        $campaign = $id > 0 ? $this->repo->get_by_id($id) : null;
        if ($campaign && (empty($allowed_from) || in_array((string) ($campaign['status'] ?? ''), $allowed_from, true))) {
            $this->repo->update_status($id, $status);
        }
        wp_safe_redirect($this->campaign_return_url($id, $flag));
        exit;
    }

    private function campaign_return_url(int $id, string $query = ''): string
    {
        $campaign = $id > 0 ? $this->repo->get_by_id($id) : null;
        $source = $campaign ? (new MarketingLogRepository())->campaign_source($id, (string) ($campaign['name'] ?? '')) : 'coupon';
        $return_history = isset($_POST['return_history']) && absint(wp_unslash((string) $_POST['return_history'])) === 1;
        if ($source === 'automation' || $return_history) {
            return $this->campaign_history_url($source, $query);
        }
        $suffix = $id > 0 ? '&action=edit&id=' . $id : '';
        if ($query !== '') { $suffix .= '&' . ltrim($query, '&'); }
        return admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns' . $suffix);
    }

    private function campaign_history_url(string $source, string $query = ''): string
    {
        $url = $source === 'automation'
            ? 'admin.php?page=slotera-marketing&sltr_marketing_section=automation&sltr_marketing_tab=campaigns'
            : 'admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns';
        if ($query !== '') { $url .= '&' . ltrim($query, '&'); }
        return admin_url($url);
    }
}
