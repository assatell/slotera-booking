<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Infrastructure\Repositories\WorkingHoursRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class WorkingHoursController
{
    private RequestValidator $request;

    public function __construct(?RequestValidator $request = null)
    {
        $this->request = $request ?? new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_working_hours', [$this, 'save']);
    }

    public function save(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce('sltr_save_working_hours');

        (new WorkingHoursRepository())->replace_global($this->request->sanitize_hours_from_post());

        $return_to = $this->request->post_key('return_to');
        $url = admin_url('admin.php?page=slotera-settings&sltr_message=saved');

        if ($return_to !== '') {
            $url .= '#' . $return_to;
        }

        wp_safe_redirect($url);
        exit;
    }
}
