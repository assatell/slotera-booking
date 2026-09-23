<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class EmergencyLicenseRecoveryService
{
    public function apply(array $envelope): bool
    {
        $payload =
            (new EmergencyLicenseRecoveryVerifier())
                ->verify($envelope);

        if ($payload === null) {
            return false;
        }

        return (new SigningKeyRing())
            ->acceptRecovery(
                'license',
                $payload,
                EmergencyLicenseRecoveryVerifier::KEY_ID,
                LicenseCertificateVerifier::KEY_ID
            );
    }
}
