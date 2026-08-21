<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\ProFeatureRegistry;
use Slotera\Application\Services\RequestValidator;

if (!defined('ABSPATH')) { exit; }

final class ProFeaturesPage
{
    private RequestValidator $request;

    public function __construct(?RequestValidator $request = null)
    {
        $this->request = $request ?: new RequestValidator();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $features = (new ProFeatureRegistry())->features();
        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/pro-features.php';
    }
}
