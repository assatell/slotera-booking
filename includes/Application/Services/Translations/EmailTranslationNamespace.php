<?php

declare(strict_types=1);

namespace Slotera\Application\Services\Translations;

if (!defined('ABSPATH')) { exit; }

final class EmailTranslationNamespace
{
    /** Legacy admin keys retained as read-only aliases for compatibility. */
    private const LEGACY_KEY_MAP = [
        'admin.admin_notification_email' => 'emails.settings.admin_notification_email',
        'admin.campaign_preview' => 'emails.campaign_preview',
        'admin.email' => 'emails.settings.email',
        'admin.email_queue_retention' => 'emails.settings.email_queue_retention',
        'admin.email_template' => 'emails.templates.email_template',
        'admin.emails' => 'emails.settings.emails',
        'admin.emails_per_batch' => 'emails.settings.emails_per_batch',
        'admin.none' => 'emails.settings.none',
        'admin.preview_and_test' => 'emails.preview.preview_and_test',
        'admin.preview_in_popup' => 'emails.preview.preview_in_popup',
        'admin.preview_opens_in_a_popup_and_uses_real_matching_customer_data_when_the' => 'emails.preview.preview_opens_in_popup',
        'admin.preview_test_as_email' => 'emails.preview.preview_test_as_email',
        'admin.template' => 'emails.templates.template',
        'admin.test_email_failed' => 'emails.test.test_email_failed',
        'admin.test_email_sent' => 'emails.test.test_email_sent',
    ];

    public static function normalize_key(string $key): string
    {
        return self::LEGACY_KEY_MAP[$key] ?? $key;
    }

    public static function legacy_key_map(): array
    {
        return self::LEGACY_KEY_MAP;
    }
}
