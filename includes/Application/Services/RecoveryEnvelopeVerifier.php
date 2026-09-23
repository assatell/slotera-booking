<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class RecoveryEnvelopeVerifier
{
    public function verify(
        array $envelope,
        string $trustedKeyId,
        string $trustedPublicKey,
        string $purpose = 'license'
    ): ?array {
        if (
            !$this->validKeyId($trustedKeyId)
            || trim($trustedPublicKey) === ''
            || $purpose === ''
            || ($envelope['schema'] ?? '')
                !== 'slotera-license-recovery-envelope/v1'
            || ($envelope['algorithm'] ?? '')
                !== 'RSA-SHA256'
            || ($envelope['key_id'] ?? '')
                !== $trustedKeyId
            || !is_string(
                $envelope['payload_base64'] ?? null
            )
            || !is_string(
                $envelope['signature_base64'] ?? null
            )
            || !function_exists('openssl_verify')
        ) {
            return null;
        }

        $payloadBytes =
            base64_decode(
                $envelope['payload_base64'],
                true
            );

        $signature =
            base64_decode(
                $envelope['signature_base64'],
                true
            );

        if (
            !is_string($payloadBytes)
            || $payloadBytes === ''
            || !is_string($signature)
            || $signature === ''
        ) {
            return null;
        }

        $signedMessage =
            "slotera-license-recovery-envelope-v1\n"
            . $payloadBytes;

        if (
            openssl_verify(
                $signedMessage,
                $signature,
                $trustedPublicKey,
                OPENSSL_ALGO_SHA256
            ) !== 1
        ) {
            return null;
        }

        $payload =
            json_decode(
                $payloadBytes,
                true
            );

        if (
            !is_array($payload)
            || ($payload['schema'] ?? '')
                !== 'slotera-license-recovery-v1'
            || ($payload['purpose'] ?? '')
                !== $purpose
            || !is_int(
                $payload['recovery_sequence'] ?? null
            )
            || $payload['recovery_sequence'] < 1
            || !is_string(
                $payload['compromised_key_id']
                    ?? null
            )
            || !is_string(
                $payload['replacement_key_id']
                    ?? null
            )
            || !is_string(
                $payload['replacement_public_key']
                    ?? null
            )
            || !is_string(
                $payload['not_before']
                    ?? null
            )
        ) {
            return null;
        }

        if (
            !$this->validKeyId(
                $payload['compromised_key_id']
            )
            || !$this->validKeyId(
                $payload['replacement_key_id']
            )
            || hash_equals(
                $payload['compromised_key_id'],
                $payload['replacement_key_id']
            )
        ) {
            return null;
        }

        if (
            strtotime(
                $payload['not_before']
            ) === false
        ) {
            return null;
        }

        if (
            !$this->pemMatchesId(
                $payload['replacement_public_key'],
                $payload['replacement_key_id']
            )
        ) {
            return null;
        }

        return $payload;
    }

    private function validKeyId(
        string $keyId
    ): bool {
        return preg_match(
            '/^sha256:[a-f0-9]{64}$/',
            $keyId
        ) === 1;
    }

    private function pemMatchesId(
        string $pem,
        string $keyId
    ): bool {
        $public =
            openssl_pkey_get_public(
                $pem
            );

        if ($public === false) {
            return false;
        }

        $details =
            openssl_pkey_get_details(
                $public
            );

        if (
            !is_array($details)
            || ($details['type'] ?? null)
                !== OPENSSL_KEYTYPE_RSA
            || (int) ($details['bits'] ?? 0)
                < 3072
        ) {
            return false;
        }

        $body =
            preg_replace(
                '/-----BEGIN PUBLIC KEY-----|'
                . '-----END PUBLIC KEY-----|'
                . '\s+/',
                '',
                $pem
            );

        if (
            !is_string($body)
            || $body === ''
        ) {
            return false;
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
            return false;
        }

        return hash_equals(
            $keyId,
            'sha256:'
            . hash(
                'sha256',
                $der
            )
        );
    }
}
