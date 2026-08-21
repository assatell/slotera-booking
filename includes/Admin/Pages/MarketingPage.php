<?php
declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\EmailTemplateRegistry;
use Slotera\Application\Services\MarketingEmailService;
use Slotera\Application\Services\LicenseService;
use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\MarketingCampaignRepository;
use Slotera\Infrastructure\Repositories\MarketingLogRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class MarketingPage
{
    private RequestValidator $request;
    private MarketingCampaignRepository $campaigns;

    public function __construct(?RequestValidator $request = null, ?MarketingCampaignRepository $campaigns = null)
    {
        $this->request = $request ?: new RequestValidator();
        $this->campaigns = $campaigns ?: new MarketingCampaignRepository();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $section = $this->request->get_key('sltr_marketing_section');
        if ($section === '') { $section = 'coupons'; }
        if (!in_array($section, ['coupons', 'automation', 'promotions'], true)) { $section = 'coupons'; }

        if ($section === 'coupons') {
            (new CouponsPage())->render(true);
            return;
        }

        if ($section === 'promotions') {
            $promotion_service = new \Slotera\Application\Services\PromotionCampaignService();
            $promotion_settings = $promotion_service->settings();
            $promotion_offers = $promotion_service->active_offers();
            $promotion_recipients = $promotion_service->eligible_recipient_count();
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-promotions.php';
            return;
        }

        $action = $this->request->get_key('action');
        if ($action === 'new' || $action === 'edit') {
            $id = $action === 'edit' ? $this->request->get_int('id') : 0;
            $target = 'admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns' . ($action === 'new' ? '&action=new' : '&action=edit&id=' . $id);
            wp_safe_redirect(admin_url($target));
            exit;
        }
        $campaigns = $this->campaigns->get_all();
        $templates = EmailTemplateRegistry::scenarios();
        $packages = (new PackageRepository())->get_active(200, 0);
        $settings = (new SettingsRepository())->all();
        $license = new LicenseService();
        $license_status = $license->status();
        $automation_next_run = wp_next_scheduled(\Slotera\Application\Services\MarketingAutomationService::CRON_HOOK);
        $log_repo = new MarketingLogRepository();
        $campaigns = array_values(array_filter($campaigns, static function (array $campaign) use ($log_repo): bool {
            return $log_repo->is_automation_campaign((int) ($campaign['id'] ?? 0), (string) ($campaign['name'] ?? ''));
        }));
        $latest_by_type = [];
        foreach ($campaigns as $campaign_row) {
            $type = sanitize_key((string) ($campaign_row['automation_type'] ?? ''));
            if (!in_array($type, ['come_back', 'after_booking'], true)) { continue; }
            if (!isset($latest_by_type[$type])) { $latest_by_type[$type] = $campaign_row; }
        }
        $campaigns = array_values($latest_by_type);
        foreach ($campaigns as &$campaign) {
            $campaign['stats'] = $log_repo->stats_for_campaign((int) $campaign['id']);
            $automation_type = sanitize_key((string) ($campaign['automation_type'] ?? ''));
            $campaign['automation_next_run'] = (int) ($automation_next_run ?: 0);
            $campaign['automation_enabled'] = $automation_type === 'after_booking'
                ? (int) ($settings['after_booking_automation_enabled'] ?? 0)
                : (int) ($settings['comeback_automation_enabled'] ?? 0);
            $campaign['automation_rule_days'] = $automation_type === 'after_booking'
                ? (int) ($settings['after_booking_automation_delay_days'] ?? 3)
                : (int) ($settings['comeback_automation_inactive_days'] ?? 30);
        }
        unset($campaign);
        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-list.php';
    }
}
