<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

define(
    'ABSPATH',
    dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR
);

function fail_recovery_test(
    string $message
): never {
    fwrite(
        STDERR,
        "FAIL: {$message}\n"
    );

    exit(1);
}

function assert_recovery_test(
    bool $condition,
    string $message
): void {
    if (!$condition) {
        fail_recovery_test(
            $message
        );
    }
}

function recovery_test_key_pair(): array
{
    $options = [
        'private_key_bits' => 3072,
        'private_key_type' =>
            OPENSSL_KEYTYPE_RSA,
    ];

    $config =
        getenv('OPENSSL_CONF');

    if (
        (!is_string($config)
            || $config === '')
        && DIRECTORY_SEPARATOR === '\\'
    ) {
        $candidate =
            dirname(PHP_BINARY)
            . DIRECTORY_SEPARATOR
            . 'extras'
            . DIRECTORY_SEPARATOR
            . 'ssl'
            . DIRECTORY_SEPARATOR
            . 'openssl.cnf';

        if (is_file($candidate)) {
            $options['config'] =
                $candidate;
        }
    } elseif (
        is_string($config)
        && $config !== ''
        && is_file($config)
    ) {
        $options['config'] =
            $config;
    }

    $private =
        openssl_pkey_new(
            $options
        );

    if ($private === false) {
        fail_recovery_test(
            'Unable to create TEST RSA key.'
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
        fail_recovery_test(
            'Unable to export TEST public key.'
        );
    }

    $publicPem =
        $details['key'];

    $body =
        preg_replace(
            '/-----BEGIN PUBLIC KEY-----|'
            . '-----END PUBLIC KEY-----|'
            . '\s+/',
            '',
            $publicPem
        );

    if (
        !is_string($body)
        || $body === ''
    ) {
        fail_recovery_test(
            'Unable to normalize TEST public key.'
        );
    }

    $der =
        base64_decode(
            $body,
            true
        );

    if (
        !is_string($der)
        || $der === ''
    ) {
        fail_recovery_test(
            'Unable to decode TEST public key.'
        );
    }

    return [
        'private' => $private,
        'public' => $publicPem,
        'id' =>
            'sha256:'
            . hash(
                'sha256',
                $der
            ),
    ];
}

require_once ABSPATH
    . 'includes/Application/Services/'
    . 'RecoveryEnvelopeVerifier.php';

use Slotera\Application\Services\RecoveryEnvelopeVerifier;

$recoveryRoot =
    recovery_test_key_pair();

$compromised =
    recovery_test_key_pair();

$replacement =
    recovery_test_key_pair();

$payload = [
    'schema' =>
        'slotera-license-recovery-v1',
    'purpose' =>
        'license',
    'recovery_sequence' =>
        1,
    'compromised_key_id' =>
        $compromised['id'],
    'replacement_key_id' =>
        $replacement['id'],
    'replacement_public_key' =>
        $replacement['public'],
    'not_before' =>
        gmdate(
            'c',
            time() - 60
        ),
];

$payloadBytes =
    json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR
    );

$signedMessage =
    "slotera-license-recovery-envelope-v1\n"
    . $payloadBytes;

$signature = '';

assert_recovery_test(
    openssl_sign(
        $signedMessage,
        $signature,
        $recoveryRoot['private'],
        OPENSSL_ALGO_SHA256
    ),
    'TEST recovery envelope signing failed.'
);

$envelope = [
    'schema' =>
        'slotera-license-recovery-envelope/v1',
    'algorithm' =>
        'RSA-SHA256',
    'key_id' =>
        $recoveryRoot['id'],
    'payload_base64' =>
        base64_encode(
            $payloadBytes
        ),
    'signature_base64' =>
        base64_encode(
            $signature
        ),
];

$verifier =
    new RecoveryEnvelopeVerifier();

$verified =
    $verifier->verify(
        $envelope,
        $recoveryRoot['id'],
        $recoveryRoot['public'],
        'license'
    );

assert_recovery_test(
    is_array($verified),
    'Valid TEST-root recovery envelope must verify.'
);

assert_recovery_test(
    $verified === $payload,
    'Verified recovery payload must match signed payload.'
);

$tampered =
    $envelope;

$tampered['signature_base64'] =
    base64_encode(
        str_repeat(
            "\0",
            strlen($signature)
        )
    );

assert_recovery_test(
    $verifier->verify(
        $tampered,
        $recoveryRoot['id'],
        $recoveryRoot['public'],
        'license'
    ) === null,
    'Tampered recovery signature must be rejected.'
);

$wrongRoot =
    recovery_test_key_pair();

assert_recovery_test(
    $verifier->verify(
        $envelope,
        $wrongRoot['id'],
        $wrongRoot['public'],
        'license'
    ) === null,
    'Envelope from another recovery root must be rejected.'
);

$wrongPurpose =
    $verifier->verify(
        $envelope,
        $recoveryRoot['id'],
        $recoveryRoot['public'],
        'update'
    );

assert_recovery_test(
    $wrongPurpose === null,
    'License recovery envelope must not authorize update purpose.'
);

$badReplacement =
    $payload;

$badReplacement['replacement_key_id'] =
    'sha256:'
    . str_repeat(
        '0',
        64
    );

$badBytes =
    json_encode(
        $badReplacement,
        JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR
    );

$badSignature = '';

assert_recovery_test(
    openssl_sign(
        "slotera-license-recovery-envelope-v1\n"
        . $badBytes,
        $badSignature,
        $recoveryRoot['private'],
        OPENSSL_ALGO_SHA256
    ),
    'Invalid replacement TEST envelope signing failed.'
);

$badEnvelope = [
    'schema' =>
        'slotera-license-recovery-envelope/v1',
    'algorithm' =>
        'RSA-SHA256',
    'key_id' =>
        $recoveryRoot['id'],
    'payload_base64' =>
        base64_encode(
            $badBytes
        ),
    'signature_base64' =>
        base64_encode(
            $badSignature
        ),
];

assert_recovery_test(
    $verifier->verify(
        $badEnvelope,
        $recoveryRoot['id'],
        $recoveryRoot['public'],
        'license'
    ) === null,
    'Replacement key fingerprint mismatch must be rejected.'
);

fwrite(
    STDOUT,
    "OK: recovery envelope verifier TEST-root tests passed\n"
);
