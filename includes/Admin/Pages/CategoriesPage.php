<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\CategoryRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class CategoriesPage
{
    private CategoryRepository $repo;
    private RequestValidator $request;

    public function __construct(?CategoryRepository $repo = null, ?RequestValidator $request = null)
    {
        $this->repo = $repo ?: new CategoryRepository();
        $this->request = $request ?? new RequestValidator();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);

        $action = $this->request->get_key('action');

        if ($action === 'new' || $action === 'edit') {
            $id = $action === 'edit' ? $this->request->get_int('id') : 0;
            $category = $id ? $this->repo->get_by_id($id) : null;
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/category-form.php';
            return;
        }

        $categories = $this->repo->get_all();
        foreach ($categories as &$row) {
            $row['linked_package_count'] = $this->repo->count_linked_packages((int) ($row['id'] ?? 0));
        }
        unset($row);
        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/categories-list.php';
    }
}
