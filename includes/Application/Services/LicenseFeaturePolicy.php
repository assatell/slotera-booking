<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/**
 * Central policy for features that require a currently usable signed license.
 *
 * LicenseService remains authoritative for fail-open behavior: an unreachable
 * licensing server preserves the last verified signed state, while a verified
 * expired/revoked state locks licensed features.
 */
final class LicenseFeaturePolicy
{
    public const MARKETING = 'marketing';
    public const PAYMENTS = 'payments';
    public const SHARED_NETWORK = 'shared_network';
    public const ANALYTICS = 'analytics';
    public const WHITE_LABEL = 'white_label';

    private LicenseService $license;

    public function __construct(?LicenseService $license = null)
    {
        $this->license = $license ?: new LicenseService();
    }

    public function allows(string $feature): bool
    {
        if (!in_array($feature, $this->features(), true)) {
            return false;
        }

        // This method is called from WhiteLabelService's gettext filter.
        // Do not call LicenseService::status() here: status() builds translated
        // labels via __(), which re-enters gettext and causes infinite recursion.
        $data = $this->license->data();
        $state = (string) ($data['license_status'] ?? 'unverified');

        return in_array($state, ['active', 'trial', 'grace'], true);
    }

    public function status(): array
    {
        return $this->license->status();
    }

    public function state(): string
    {
        return (string) ($this->status()['state'] ?? 'unverified');
    }

    public function label(): string
    {
        return (string) ($this->status()['label'] ?? __('Not activated', 'slotera-booking'));
    }

    public function locked_message(string $featureLabel): string
    {
        return sprintf(
            __('%1$s is unavailable because the current license status is %2$s. Bookings continue to work.', 'slotera-booking'),
            $featureLabel,
            $this->label()
        );
    }

    /** @return array<int,string> */
    private function features(): array
    {
        return [
            self::MARKETING,
            self::PAYMENTS,
            self::SHARED_NETWORK,
            self::ANALYTICS,
            self::WHITE_LABEL,
        ];
    }
}