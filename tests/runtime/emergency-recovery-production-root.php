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

function fail_root_test(
    string $message
): never {
    fwrite(
        STDERR,
        "FAIL: {$message}\n"
    );

    exit(1);
}

function assert_root_test(
    bool $condition,
    string $message
): void {
    if (!$condition) {
        fail_root_test(
            $message
        );
    }
}

require_once ABSPATH
    . 'includes/Application/Services/'
    . 'RecoveryEnvelopeVerifier.php';

require_once ABSPATH
    . 'includes/Application/Services/'
    . 'EmergencyLicenseRecoveryVerifier.php';

use Slotera\Application\Services\EmergencyLicenseRecoveryVerifier;

$reflection =
    new ReflectionClass(
        EmergencyLicenseRecoveryVerifier::class
    );

$pem =
    $reflection->getConstant(
        'PUBLIC_KEY'
    );

assert_root_test(
    is_string($pem)
        && $pem !== '',
    'Embedded production recovery public key unavailable.'
);

$public =
    openssl_pkey_get_public(
        $pem
    );

assert_root_test(
    $public !== false,
    'Embedded production recovery public key is invalid.'
);

$details =
    openssl_pkey_get_details(
        $public
    );

assert_root_test(
    is_array($details)
        && ($details['type'] ?? null)
            === OPENSSL_KEYTYPE_RSA
        && (int) ($details['bits'] ?? 0)
            >= 3072,
    'Production recovery root must be RSA-3072 or stronger.'
);

$body =
    preg_replace(
        '/-----BEGIN PUBLIC KEY-----|'
        . '-----END PUBLIC KEY-----|'
        . '\s+/',
        '',
        $pem
    );

assert_root_test(
    is_string($body)
        && $body !== '',
    'Embedded production recovery public key cannot be normalized.'
);

$der =
    base64_decode(
        $body,
        true
    );

assert_root_test(
    is_string($der)
        && $der !== '',
    'Embedded production recovery public key cannot be decoded.'
);

$calculatedId =
    'sha256:'
    . hash(
        'sha256',
        $der
    );

assert_root_test(
    hash_equals(
        EmergencyLicenseRecoveryVerifier::KEY_ID,
        $calculatedId
    ),
    'Production recovery root fingerprint does not match KEY_ID.'
);

fwrite(
    STDOUT,
    "OK: production recovery root integrity test passed\n"
);
