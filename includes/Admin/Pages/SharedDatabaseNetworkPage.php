<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\SharedDatabaseDiagnosticsService;

if (!defined('ABSPATH')) { exit; }

final class SharedDatabaseNetworkPage
{
    private RequestValidator $request;

    public function __construct(?RequestValidator $request = null)
    {
        $this->request = $request ?: new RequestValidator();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        if (isset($_GET['sltr_create_shared_tables'])) {
            $this->request->verify_admin_nonce('sltr_create_shared_tables');
            \Slotera\Core\Database::create_tables();
            wp_safe_redirect(admin_url('admin.php?page=slotera-shared-network&tables_created=1'));
            exit;
        }
        $report = (new SharedDatabaseDiagnosticsService())->report();
        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/shared-database-network.php';
    }
}
