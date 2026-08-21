<?php
declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\EmailTemplateRegistry;
use Slotera\Application\Services\MarketingEmailService;
use Slotera\Application\Services\LicenseService;
use Slotera\Infrastructure\Repositories\CouponRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\MarketingCampaignRepository;
use Slotera\Infrastructure\Repositories\MarketingLogRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class CouponsPage
{
    private RequestValidator $request;
    private CouponRepository $repo;

    public function __construct(?RequestValidator $request = null, ?CouponRepository $repo = null)
    {
        $this->request = $request ?: new RequestValidator();
        $this->repo = $repo ?: new CouponRepository();
    }

    public function render(bool $embedded_in_marketing = false): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $sltr_embedded_in_marketing = $embedded_in_marketing;
        $action = $this->request->get_key('action');
        $tab = $this->request->get_key('sltr_coupon_tab');
        if ($tab === 'campaigns') {
            $campaign_repo = new MarketingCampaignRepository();
            $log_repo = new MarketingLogRepository();
            if ($action === 'new' || $action === 'edit') {
                $id = $action === 'edit' ? $this->request->get_int('id') : 0;
                $campaign = $id > 0 ? $campaign_repo->get_by_id($id) : null;
                $sltr_bound_coupon = null;
                $sltr_bound_coupon_package_ids = [];
                $sltr_bound_coupon_package_labels = [];

                if ($id <= 0) {
                    $prefill_coupon_id = $this->request->get_int('coupon_id');
                    $sltr_bound_coupon = $prefill_coupon_id > 0 ? $this->repo->get_by_id($prefill_coupon_id) : null;
                    if (!$sltr_bound_coupon) {
                        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&action=new&campaign_requires_coupon=1'));
                        exit;
                    }
                    $sltr_bound_coupon_package_ids = array_values(array_filter(array_map('absint', explode(',', (string) ($sltr_bound_coupon['package_ids'] ?? '')))));
                    $campaign = [
                        'coupon_id' => $prefill_coupon_id,
                        'package_id' => count($sltr_bound_coupon_package_ids) === 1 ? (int) $sltr_bound_coupon_package_ids[0] : 0,
                        'template_key' => 'marketing_promo',
                        'source' => 'coupon_bound',
                    ];
                } elseif ($campaign) {
                    $sltr_bound_coupon = $this->repo->get_by_id((int) ($campaign['coupon_id'] ?? 0));
                    if (!$sltr_bound_coupon) {
                        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns&campaign_coupon_missing=1'));
                        exit;
                    }
                    $sltr_bound_coupon_package_ids = array_values(array_filter(array_map('absint', explode(',', (string) ($sltr_bound_coupon['package_ids'] ?? '')))));
                    $campaign['source'] = 'coupon_bound';
                    $campaign['package_id'] = count($sltr_bound_coupon_package_ids) === 1 ? (int) $sltr_bound_coupon_package_ids[0] : 0;
                }
                if ($id > 0 && (!$campaign || $log_repo->is_automation_campaign($id, (string) ($campaign['name'] ?? '')))) {
                    wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns'));
                    exit;
                }
                $templates = EmailTemplateRegistry::scenarios();
                $packages = (new PackageRepository())->get_all();
                if ($sltr_bound_coupon) {
                    $sltr_package_titles = [];
                    foreach ($packages as $sltr_package_row) {
                        $sltr_package_titles[(int) ($sltr_package_row['id'] ?? 0)] = (string) ($sltr_package_row['title'] ?? '');
                    }
                    foreach ($sltr_bound_coupon_package_ids as $sltr_bound_package_id) {
                        if (isset($sltr_package_titles[$sltr_bound_package_id])) {
                            $sltr_bound_coupon_package_labels[] = $sltr_package_titles[$sltr_bound_package_id];
                        }
                    }
                }
                $coupons = $this->repo->get_marketing_templates();
                $logs = $id > 0 ? $log_repo->get_for_campaign($id) : [];
                $counts = $id > 0 ? $log_repo->counts_for_campaign($id) : ['pending'=>0,'sending'=>0,'sent'=>0,'failed'=>0,'skipped'=>0];
                $stats = $id > 0 ? $log_repo->stats_for_campaign($id) : ['total'=>0,'done'=>0,'percent'=>0,'last_activity'=>''];
                $next_run = wp_next_scheduled(MarketingEmailService::CRON_HOOK);
                $audience_count = $campaign ? count((new MarketingEmailService())->audience_for_campaign($campaign)) : 0;
                $settings = (new SettingsRepository())->all();
                $license_status = (new LicenseService())->status();
                $sltr_campaign_context = 'coupon';
                require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-form.php';
                return;
            }
            $campaigns = array_values(array_filter($campaign_repo->get_all(), static function (array $campaign) use ($log_repo): bool {
                return !$log_repo->is_automation_campaign((int) ($campaign['id'] ?? 0), (string) ($campaign['name'] ?? ''));
            }));
            foreach ($campaigns as &$campaign) { $campaign['stats'] = $log_repo->stats_for_campaign((int) $campaign['id']); }
            unset($campaign);
            $coupon_codes = [];
            foreach ($this->repo->get_all() as $coupon_row) { $coupon_codes[(int) ($coupon_row['id'] ?? 0)] = (string) ($coupon_row['code'] ?? ''); }
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/coupon-campaigns-list.php';
            return;
        }
        if ($action === 'new' || $action === 'edit') {
            $id = $action === 'edit' ? $this->request->get_int('id') : 0;
            $coupon = $id > 0 ? $this->repo->get_by_id($id) : null;
            $packages = (new PackageRepository())->get_all();
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/coupon-form.php';
            return;
        }
        $coupons = $this->repo->get_marketing_templates();
        $coupon_campaigns = [];
        foreach ((new MarketingCampaignRepository())->get_all() as $campaign_row) {
            if ((string) ($campaign_row['source'] ?? '') === 'automation') { continue; }
            $campaign_coupon_id = (int) ($campaign_row['coupon_id'] ?? 0);
            if ($campaign_coupon_id <= 0 || isset($coupon_campaigns[$campaign_coupon_id])) { continue; }
            $coupon_campaigns[$campaign_coupon_id] = $campaign_row;
        }
        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/coupons-list.php';
    }
}
