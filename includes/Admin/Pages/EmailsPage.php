<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Application\Services\EmailTemplateRegistry;
use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\TranslationRegistry;
use Slotera\Application\Services\TranslationService;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class EmailsPage
{
    private SettingsRepository $settings;
    private RequestValidator $request;

    public function __construct(?SettingsRepository $settings = null, ?RequestValidator $request = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
        $this->request = $request ?? new RequestValidator();
    }

    public function render(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $args = ['page' => 'slotera-translations', 'group' => 'email_templates'];
        $scenario = $this->request->get_key('scenario');
        if ($scenario !== '') {
            $args['scenario'] = $scenario;
        }
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
