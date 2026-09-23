<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class EmergencyLicenseRecoveryVerifier
{
    public const KEY_ID =
        'sha256:d9ae8a7d982be7c68ad297d5ba40aa241671ec754e038a0a0aacbb5bd2a286e4';

    private const PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAuh87yfJ+8ujQDbpACHV5
TStyUcXcGcPnug7ru6JNFCjujvgTBqsQ9ta9/kaMm1fvouY+As8q+M/mY2q7yoWR
5avmJu+6clDFhCO2XJ2/9c3LRtNCjAgAppBTA87uBNRCphX44x/05948A2Ef6ynT
MwE+iyOjSXDIwx1vab4aU4B0+B8USGxcvlBaJoHCdynpyd4znPYuwfGMdHGVX4op
oSG2AMtewPtk6l3KwayU2pvBq9K+5NBfHxjisu1MJLserpJrbnTfNSN1dV3XSA3M
U11vHP5cvNh5/UZGsDYqcgXg3xR0Q7R3QYLUWgW0yhNIdCarksn2iAvjexjlmFL9
CZb7tHw7Ry8OyivkdcPVztobsNBRmN1uFyuujl0gM925paQpYV1epQWNvEDqIEMs
GnR2tfkUMwCff59sZt+jl2Ki03zZFm/TCYQl6ta/MBEF6WyYfdB8eqKLE/kq8nLj
KQxTCNviMFugZmzVwmppUk+YPNnmYcetHM4lHCZFmLRPAgMBAAE=
-----END PUBLIC KEY-----
PEM;

    public function verify(
        array $envelope
    ): ?array {
        return (new RecoveryEnvelopeVerifier())
            ->verify(
                $envelope,
                self::KEY_ID,
                self::PUBLIC_KEY,
                'license'
            );
    }
}
