<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\CategoryRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\LocationRepository;
use Slotera\Infrastructure\Repositories\WorkingHoursRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class PackagesPage
{
    private PackageRepository $repository;
    private RequestValidator $request;

    public function __construct(?PackageRepository $repository = null, ?RequestValidator $request = null)
    {
        $this->repository = $repository ?: new PackageRepository();
        $this->request = $request ?? new RequestValidator();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);

        $action = $this->request->get_key('action');

        if ($action === 'edit' || $action === 'new') {
            $id = $action === 'edit' ? $this->request->get_int('id') : 0;
            $package = $id > 0 ? $this->repository->get_by_id($id) : null;
            $categories = (new CategoryRepository())->get_active();
            $location_repository = new LocationRepository();
            $locations = $location_repository->get_active();
            $selected_location_ids = $id > 0 ? $location_repository->get_ids_for_package($id) : [];
            $package_location_relations = $id > 0 ? $location_repository->get_relations_for_package($id) : [];
            $package_hours = $id > 0 ? (new WorkingHoursRepository())->get_for_scope('package', $id) : [];
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/package-form.php';
            return;
        }

        $packages = $this->repository->get_all();
        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/packages-list.php';
    }
}
