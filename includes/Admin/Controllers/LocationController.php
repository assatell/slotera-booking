<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\LocationRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class LocationController
{
    private LocationRepository $repo;
    private RequestValidator $request;

    public function __construct(?RequestValidator $request = null)
    {
        $this->repo = new LocationRepository();
        $this->request = $request ?? new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_location', [$this, 'save']);
        add_action('admin_post_sltr_deactivate_location', [$this, 'deactivate']);
        add_action('admin_post_sltr_restore_location', [$this, 'restore']);
    }

    public function save(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);
        $this->request->verify_admin_nonce('sltr_save_location');

        $id = $this->request->post_int('id');
        $existing = $id > 0 ? $this->repo->get_by_id($id) : null;
        $requested_slug = sanitize_title($this->request->post_text('slug'));
        if ($id > 0 && is_array($existing)) {
            $saved_slug = sanitize_title((string) ($existing['slug'] ?? ''));
            if ($saved_slug !== '' && $requested_slug !== '' && $requested_slug !== $saved_slug) { wp_safe_redirect(admin_url('admin.php?page=slotera-locations&action=edit&id=' . absint($id) . '&sltr_error=slug_locked')); exit; }
        } else {
            if ($requested_slug === '') { $requested_slug = sanitize_title($this->request->post_text('name')); }
            if ($requested_slug !== '' && $this->repo->slug_exists($requested_slug)) { wp_safe_redirect(admin_url('admin.php?page=slotera-locations&action=new&sltr_error=slug_exists')); exit; }
        }

        $data = [
            'name' => $this->request->post_text('name'),
            'slug' => $requested_slug,
            'description' => $this->request->post_html('description'),
            'intro_content' => $this->request->post_html('intro_content'),
            'faq' => $this->collect_faq($this->request->post_array('faq', 'html')),
            'sort_order' => $this->request->post_int('sort_order'),
            'is_active' => is_array($existing) ? (int) ($existing['is_active'] ?? 1) : 1,
        ];
        $save_ok = false;
        if ($id > 0) {
            $save_ok = $this->repo->update($id, $data);
        } else {
            $id = $this->repo->create($data);
            $save_ok = $id > 0;
        }
        if (!$save_ok) {
            global $wpdb;
            if (function_exists('error_log')) {
                error_log('Slotera location save failed: ' . (string) ($wpdb->last_error ?? 'unknown database error'));
            }
            $action = $id > 0 ? 'edit&id=' . absint($id) : 'new';
            wp_safe_redirect(admin_url('admin.php?page=slotera-locations&action=' . $action . '&sltr_error=save_failed'));
            exit;
        }
        wp_safe_redirect(admin_url('admin.php?page=slotera-locations&sltr_message=saved'));
        exit;
    }

    private function collect_faq(array $raw): array
    {
        $items = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'question' => sanitize_text_field((string) ($item['question'] ?? '')),
                'answer' => wp_kses_post((string) ($item['answer'] ?? '')),
            ];
        }
        return $items;
    }

    public function deactivate(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);
        $id = $this->request->get_int('id');
        if ($id <= 0) { wp_safe_redirect(admin_url('admin.php?page=slotera-locations')); exit; }
        $this->request->verify_admin_nonce('sltr_deactivate_location_' . $id);
        if ($this->repo->count_linked_packages($id) > 0) {
            wp_safe_redirect(admin_url('admin.php?page=slotera-locations&sltr_error=in_use'));
            exit;
        }
        $this->repo->update($id, ['is_active' => 0]);
        wp_safe_redirect(admin_url('admin.php?page=slotera-locations&sltr_message=draft'));
        exit;
    }

    public function restore(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);
        $id = $this->request->get_int('id');
        if ($id <= 0) { wp_safe_redirect(admin_url('admin.php?page=slotera-locations')); exit; }
        $this->request->verify_admin_nonce('sltr_restore_location_' . $id);
        $this->repo->update($id, ['is_active' => 1]);
        wp_safe_redirect(admin_url('admin.php?page=slotera-locations&sltr_message=restored'));
        exit;
    }
}
