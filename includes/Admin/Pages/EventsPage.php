<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\EventRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class EventsPage
{
    private EventRepository $repo;
    private RequestValidator $request;

    public function __construct(?EventRepository $repo = null, ?RequestValidator $request = null)
    {
        $this->repo = $repo ?: new EventRepository();
        $this->request = $request ?: new RequestValidator();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);
        $action = $this->request->get_key('action');
        $packages = (new PackageRepository())->get_all();

        $package_id = $this->request->get_int('package_id');
        $return_package_id = $this->request->get_int('return_package_id');
        if ($return_package_id <= 0) { $return_package_id = $package_id; }

        if ($action === 'new' || $action === 'edit') {
            $id = $action === 'edit' ? $this->request->get_int('id') : 0;
            $event = $id ? $this->repo->get_by_id($id) : null;
            if (!$event && $package_id > 0) {
                $event = $this->repo->get_first_for_package($package_id);
            }
            if (!$event && $package_id > 0) {
                $event = ['package_id' => $package_id];
            }
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/event-form.php';
            return;
        }

        // Package workflow always opens the single event editor directly.
        if ($package_id > 0) {
            $event = $this->repo->get_first_for_package($package_id) ?: ['package_id' => $package_id];
            require SLTR_PLUGIN_DIR . 'includes/Admin/Views/event-form.php';
            return;
        }

        $events = $package_id > 0 ? $this->repo->get_for_package($package_id) : $this->repo->get_all();
        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/events-list.php';
    }
}
