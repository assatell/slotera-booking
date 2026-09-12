<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/**
 * Request/runtime enforcement for licensed feature boundaries.
 *
 * UI checks are not security boundaries. This class blocks protected admin
 * actions before their controllers run and disables background marketing work
 * when the last verified signed state is not usable.
 */
final class LicenseFeatureEnforcer
{
    private LicenseFeaturePolicy $policy;

    /** @var array<string,string> */
    private array $adminActionFeatures = [
        // Coupons and manual marketing campaigns.
        'sltr_save_coupon' => LicenseFeaturePolicy::MARKETING,
        'sltr_save_marketing_campaign' => LicenseFeaturePolicy::MARKETING,
        'sltr_send_marketing_test' => LicenseFeaturePolicy::MARKETING,
        'sltr_send_marketing_campaign' => LicenseFeaturePolicy::MARKETING,
        'sltr_process_marketing_queue_now' => LicenseFeaturePolicy::MARKETING,
        'sltr_resume_marketing_campaign' => LicenseFeaturePolicy::MARKETING,
        'sltr_save_marketing_settings' => LicenseFeaturePolicy::MARKETING,
        'sltr_retry_failed_marketing_campaign' => LicenseFeaturePolicy::MARKETING,
        'sltr_preview_marketing_campaign' => LicenseFeaturePolicy::MARKETING,

        // Marketing automations.
        'sltr_preview_marketing_automation' => LicenseFeaturePolicy::MARKETING,
        'sltr_send_marketing_automation_test' => LicenseFeaturePolicy::MARKETING,
        'sltr_save_comeback_automation' => LicenseFeaturePolicy::MARKETING,
        'sltr_save_after_booking_automation' => LicenseFeaturePolicy::MARKETING,
        'sltr_run_marketing_automation' => LicenseFeaturePolicy::MARKETING,

        // Promotion digest.
        'sltr_save_promotion_digest' => LicenseFeaturePolicy::MARKETING,
        'sltr_send_promotion_test' => LicenseFeaturePolicy::MARKETING,
        'sltr_send_promotion_now' => LicenseFeaturePolicy::MARKETING,

        // Payments: support/history operations intentionally remain available.
        'sltr_save_payment_settings' => LicenseFeaturePolicy::PAYMENTS,

        // White label.
        'sltr_save_white_label_settings' => LicenseFeaturePolicy::WHITE_LABEL,
    ];

    public function __construct(?LicenseFeaturePolicy $policy = null)
    {
        $this->policy = $policy ?: new LicenseFeaturePolicy();
    }

    public function register_hooks(): void
    {
        add_action('admin_init', [$this, 'enforce_admin_request'], 1);
        add_action('admin_notices', [$this, 'print_blocked_notice'], 1);
        add_action('wp_loaded', [$this, 'disable_unlicensed_runtime_hooks'], 9999);
    }

    public function enforce_admin_request(): void
    {
        $action = isset($_REQUEST['action']) ? sanitize_key((string) wp_unslash($_REQUEST['action'])) : '';
        if ($action !== '' && isset($this->adminActionFeatures[$action])) {
            $feature = $this->adminActionFeatures[$action];
            if (!$this->policy->allows($feature)) {
                $this->block_request($feature);
            }
        }

        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';

        // Legacy/hidden direct Coupons URL must not bypass the Marketing shell lock.
        if ($page === 'slotera-coupons' && !$this->policy->allows(LicenseFeaturePolicy::MARKETING)) {
            wp_safe_redirect(add_query_arg([
                'page' => 'slotera-marketing',
                'sltr_marketing_section' => 'coupons',
                'sltr_license_limited' => '1',
                'sltr_license_feature' => LicenseFeaturePolicy::MARKETING,
            ], admin_url('admin.php')));
            exit;
        }

        // Shared table creation is a GET+nonce action on the page itself.
        if (
            $page === 'slotera-shared-network'
            && isset($_GET['sltr_create_shared_tables'])
            && !$this->policy->allows(LicenseFeaturePolicy::SHARED_NETWORK)
        ) {
            $this->block_request(LicenseFeaturePolicy::SHARED_NETWORK);
        }
    }

    public function disable_unlicensed_runtime_hooks(): void
    {
        if ($this->policy->allows(LicenseFeaturePolicy::MARKETING)) {
            return;
        }

        // Existing scheduled events may remain registered. Removing callbacks for
        // this request prevents any queued campaign/automation/digest from sending.
        remove_all_actions(MarketingEmailService::CRON_HOOK);
        remove_all_actions(MarketingAutomationService::CRON_HOOK);
        remove_all_actions(PromotionCampaignService::CRON_HOOK);
    }

    public function print_blocked_notice(): void
    {
        if (empty($_GET['sltr_license_limited'])) {
            return;
        }

        $feature = isset($_GET['sltr_license_feature'])
            ? sanitize_key((string) wp_unslash($_GET['sltr_license_feature']))
            : '';
        $label = $this->feature_label($feature);

        echo '<div class="notice notice-warning"><p><strong>'
            . esc_html($this->policy->locked_message($label))
            . '</strong> <a href="'
            . esc_url(admin_url('admin.php?page=slotera-license'))
            . '">'
            . esc_html__('Open License', 'slotera-booking')
            . '</a></p></div>';
    }

    private function block_request(string $feature): void
    {
        $message = $this->policy->locked_message($this->feature_label($feature));

        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            wp_send_json_error(['message' => $message, 'reason' => 'license_limited'], 403);
        }

        $redirect = wp_get_referer();
        if (!is_string($redirect) || strpos($redirect, admin_url()) !== 0) {
            $redirect = admin_url('admin.php?page=slotera-license');
        }

        $redirect = add_query_arg([
            'sltr_license_limited' => '1',
            'sltr_license_feature' => $feature,
        ], $redirect);

        wp_safe_redirect($redirect);
        exit;
    }

    private function feature_label(string $feature): string
    {
        $labels = [
            LicenseFeaturePolicy::MARKETING => __('Marketing', 'slotera-booking'),
            LicenseFeaturePolicy::PAYMENTS => __('Payments', 'slotera-booking'),
            LicenseFeaturePolicy::SHARED_NETWORK => __('Shared Database Network', 'slotera-booking'),
            LicenseFeaturePolicy::ANALYTICS => __('Analytics', 'slotera-booking'),
            LicenseFeaturePolicy::WHITE_LABEL => __('White Label', 'slotera-booking'),
        ];

        return (string) ($labels[$feature] ?? __('This feature', 'slotera-booking'));
    }
}
