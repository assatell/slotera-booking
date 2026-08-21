<?php

declare(strict_types=1);

namespace Slotera\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class Capabilities
{
    public const MANAGE = 'slotera_manage';
    public const MANAGE_BOOKINGS = 'slotera_manage_bookings';
    public const MANAGE_PACKAGES = 'slotera_manage_packages';
    public const MANAGE_SETTINGS = 'slotera_manage_settings';
    public const MANAGE_PAYMENTS = 'slotera_manage_payments';
    public const MANAGE_MARKETING = 'slotera_manage_marketing';
    public const MANAGE_WEBHOOKS = 'slotera_manage_webhooks';
    public const MANAGE_TOOLS = 'slotera_manage_tools';
    public const VIEW_LOGS = 'slotera_view_logs';

    public static function all(): array
    {
        return [
            self::MANAGE,
            self::MANAGE_BOOKINGS,
            self::MANAGE_PACKAGES,
            self::MANAGE_SETTINGS,
            self::MANAGE_PAYMENTS,
            self::MANAGE_MARKETING,
            self::MANAGE_WEBHOOKS,
            self::MANAGE_TOOLS,
            self::VIEW_LOGS,
        ];
    }

    public static function install(): void
    {
        $role = get_role('administrator');
        if (!$role) {
            return;
        }

        foreach (self::all() as $capability) {
            $role->add_cap($capability);
        }
    }
}
