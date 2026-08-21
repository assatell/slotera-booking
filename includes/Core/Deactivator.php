<?php
declare(strict_types=1);
namespace Slotera\Core;
if (!defined('ABSPATH')) { exit; }

final class Deactivator
{
    public static function deactivate(): void
    {
        if (class_exists('Slotera\\Application\\Services\\EmailReminderService')) { \Slotera\Application\Services\EmailReminderService::deactivate(); }
        if (class_exists('Slotera\\Application\\Services\\MarketingEmailService')) { \Slotera\Application\Services\MarketingEmailService::deactivate(); }
        if (class_exists('Slotera\\Application\\Services\\MarketingAutomationService')) { \Slotera\Application\Services\MarketingAutomationService::deactivate(); }
        if (class_exists('Slotera\\Application\\Services\\PrivacyService')) { \Slotera\Application\Services\PrivacyService::deactivate(); }
        if (class_exists('Slotera\\Application\\Services\\AccountMagicLinkService')) { \Slotera\Application\Services\AccountMagicLinkService::deactivate(); }
        do_action('sltr_deactivate');
    }
}
