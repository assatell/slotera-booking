<?php

declare(strict_types=1);

namespace {
    if (PHP_SAPI !== 'cli') {
        exit(1);
    }

    define(
        'ABSPATH',
        dirname(__DIR__, 2) . '/'
    );

    $GLOBALS['sltr_test_options'] = [];

    function get_option(
        string $key,
        $default = false
    ) {
        return $GLOBALS[
            'sltr_test_options'
        ][$key] ?? $default;
    }

    function update_option(
        string $key,
        $value,
        $autoload = null
    ): bool {
        $GLOBALS[
            'sltr_test_options'
        ][$key] = $value;

        return true;
    }

    function assert_runtime(
        bool $condition,
        string $message
    ): void {
        if (!$condition) {
            fwrite(
                STDERR,
                "FAIL: {$message}\n"
            );

            exit(1);
        }
    }

    function public_key_fixture(): array
    {
        $private = openssl_pkey_new([
            'private_key_bits' => 3072,
            'private_key_type' =>
                OPENSSL_KEYTYPE_RSA,
        ]);

        if ($private === false) {
            throw new RuntimeException(
                'Unable to create RSA fixture.'
            );
        }

        $details =
            openssl_pkey_get_details(
                $private
            );

        if (
            !is_array($details)
            || !is_string(
                $details['key'] ?? null
            )
        ) {
            throw new RuntimeException(
                'Unable to export public key fixture.'
            );
        }

        $pem = $details['key'];

        $body = preg_replace(
            '/-----BEGIN PUBLIC KEY-----|'
            . '-----END PUBLIC KEY-----|'
            . '\s+/',
            '',
            $pem
        );

        if (!is_string($body)) {
            throw new RuntimeException(
                'Unable to normalize public key fixture.'
            );
        }

        $der = base64_decode(
            $body,
            true
        );

        if (!is_string($der)) {
            throw new RuntimeException(
                'Unable to decode public key fixture.'
            );
        }

        return [
            'pem' => $pem,
            'id' =>
                'sha256:'
                . hash(
                    'sha256',
                    $der
                ),
        ];
    }
}

namespace Slotera\Application\Services {
    require_once ABSPATH
        . 'includes/Application/Services/'
        . 'SigningKeyRing.php';
}

namespace {
    use Slotera\Application\Services\SigningKeyRing;

    $old = public_key_fixture();
    $replacement = public_key_fixture();
    $later = public_key_fixture();
    $recovery = public_key_fixture();

    $ring = new SigningKeyRing();

    assert_runtime(
        $ring->resolve(
            'license',
            $old['id'],
            $old['id'],
            $old['pem']
        ) === $old['pem'],
        'Built-in key must initially resolve.'
    );

    $payload = [
        'schema' =>
            'slotera-license-recovery-v1',
        'purpose' =>
            'license',
        'recovery_sequence' =>
            1,
        'compromised_key_id' =>
            $old['id'],
        'replacement_key_id' =>
            $replacement['id'],
        'replacement_public_key' =>
            $replacement['pem'],
        'not_before' =>
            gmdate(
                'c',
                time() - 60
            ),
    ];

    assert_runtime(
        $ring->acceptRecovery(
            'license',
            $payload,
            $recovery['id'],
            $old['id']
        ) === true,
        'First recovery transition must be accepted.'
    );

    assert_runtime(
        $ring->resolve(
            'license',
            $old['id'],
            $old['id'],
            $old['pem']
        ) === null,
        'Compromised built-in key must never resolve again.'
    );

    assert_runtime(
        $ring->resolve(
            'license',
            $replacement['id'],
            $old['id'],
            $old['pem']
        ) === $replacement['pem'],
        'Recovery replacement key must resolve.'
    );

    assert_runtime(
        $ring->acceptRecovery(
            'license',
            $payload,
            $recovery['id'],
            $old['id']
        ) === false,
        'Recovery sequence replay must be rejected.'
    );

    $older = $payload;
    $older['recovery_sequence'] = 0;

    assert_runtime(
        $ring->acceptRecovery(
            'license',
            $older,
            $recovery['id'],
            $old['id']
        ) === false,
        'Older recovery sequence must be rejected.'
    );

    $ring->acceptTransition(
        'license',
        [
            'next_key' => [
                'schema' =>
                    'slotera-next-signing-key/v1',
                'purpose' =>
                    'license',
                'signed_by' =>
                    $old['id'],
                'key_id' =>
                    $later['id'],
                'public_key' =>
                    $later['pem'],
                'not_before' =>
                    gmdate(
                        'c',
                        time() - 60
                    ),
                'retire_current_after' =>
                    gmdate(
                        'c',
                        time() + 3600
                    ),
            ],
        ],
        $old['id']
    );

    assert_runtime(
        $ring->resolve(
            'license',
            $later['id'],
            $old['id'],
            $old['pem']
        ) === null,
        'Compromised key must not authorize next_key.'
    );

    $GLOBALS[
        'sltr_test_options'
    ]['sltr_license'] = [
        'license_state_version' => 0,
        'license_status' =>
            'unverified',
    ];

    assert_runtime(
        $ring->resolve(
            'license',
            $replacement['id'],
            $old['id'],
            $old['pem']
        ) === $replacement['pem'],
        'Recovery trust state must survive local license deactivation.'
    );

    fwrite(
        STDOUT,
        "OK: emergency signing key recovery trust-state tests passed\n"
    );
}
