<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\WhiteLabelService;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class WhiteLabelPage
{
    private RequestValidator $request;

    public function __construct(?RequestValidator $request = null)
    {
        $this->request = $request ?: new RequestValidator();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $settings = (new SettingsRepository())->all();
        $white_label = new WhiteLabelService();
        require SLTR_PLUGIN_DIR . 'includes/Admin/Views/white-label.php';
    }
}
