<?php
declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\BusinessValidator;
use Slotera\Infrastructure\Repositories\CouponRepository;

if (!defined('ABSPATH')) { exit; }

final class CouponController
{
    private RequestValidator $request;
    private CouponRepository $repo;

    public function __construct(?RequestValidator $request = null, ?CouponRepository $repo = null)
    {
        $this->request = $request ?: new RequestValidator();
        $this->repo = $repo ?: new CouponRepository();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_coupon', [$this, 'save']);
        add_action('admin_post_sltr_delete_coupon', [$this, 'delete']);
    }

    public function save(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $this->request->verify_admin_nonce('sltr_save_coupon');
        $id = isset($_POST['id']) ? absint(wp_unslash((string) $_POST['id'])) : 0;
        $data = [
            'code' => isset($_POST['code']) ? sanitize_text_field(wp_unslash((string) $_POST['code'])) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field(wp_unslash((string) $_POST['description'])) : '',
            'discount_type' => isset($_POST['discount_type']) ? sanitize_key(wp_unslash((string) $_POST['discount_type'])) : 'percent',
            'discount_value' => BusinessValidator::money($_POST['discount_value'] ?? 0),
            'usage_limit' => isset($_POST['usage_limit']) ? absint(wp_unslash((string) $_POST['usage_limit'])) : 0,
            'usage_limit_per_email' => isset($_POST['usage_limit_per_email']) ? absint(wp_unslash((string) $_POST['usage_limit_per_email'])) : 0,
            'expires_at' => BusinessValidator::date($_POST['expires_at'] ?? ''),
            'package_ids' => isset($_POST['package_ids']) ? implode(',', array_map('absint', (array) wp_unslash($_POST['package_ids']))) : '',
            'min_amount' => BusinessValidator::money($_POST['min_amount'] ?? 0),
            'is_active' => !empty(wp_unslash((string) ($_POST['is_active'] ?? ''))) ? 1 : 0,
        ];
        if ($id > 0) { $this->repo->update($id, $data); } else { $id = $this->repo->create($data); }
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns&action=new&coupon_id=' . $id . '&coupon_saved=1'));
        exit;
    }

    public function delete(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_MARKETING);
        $id = isset($_GET['id']) ? absint(wp_unslash((string) $_GET['id'])) : 0;
        $this->request->verify_admin_nonce('sltr_delete_coupon_' . $id);
        if ($id > 0) { $this->repo->delete($id); }
        wp_safe_redirect(admin_url('admin.php?page=slotera-marketing&deleted=1'));
        exit;
    }
}
