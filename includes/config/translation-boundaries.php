<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return [
    'admin' => [
        'label' => 'Admin UI',
        'mode' => 'english_only_target',
        'protected' => false,
    ],
    'frontend' => [
        'label' => 'Frontend UI',
        'mode' => 'localized',
        'protected' => true,
    ],
    'email_settings' => [
        'label' => 'Email settings',
        'mode' => 'localized',
        'protected' => true,
    ],
    'email_templates' => [
        'label' => 'Email templates',
        'mode' => 'localized',
        'protected' => true,
    ],
    'internal' => [
        'label' => 'Internal',
        'mode' => 'not_user_facing',
        'protected' => false,
    ],
];
