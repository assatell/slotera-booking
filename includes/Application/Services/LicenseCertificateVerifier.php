<?php
declare(strict_types=1);
namespace Slotera\Application\Services;
if (!defined('ABSPATH')) { exit; }

final class LicenseCertificateVerifier
{
    public const KEY_ID = 'sha256:ecadf72a744b506b38c64b3898df0d5d97a7e15fcf46ba356c083f2d9583917c';
    private const PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAw7/6pVcdmlbdY11o0nio
JLFTqaG7atUbgxvV67WOmzLk1d0Tk7OlujGyvBQLemjggGKgSYnaI+eiLRaTfYyT
u95WgoVRY1YJizKN59xPG3Nucnspq7W6H3GeStijGUGLhQrY6lGSylJ4oKQPZnFT
250B5DV+Awr49Z7m6KBJGo+PAgTC85W4aWzVRU3hCE1mIPbQllMs91gg/1JTrN7k
aos/6txggrluNTC79hVwEIbiFu3EWxDTnZ/LoVdsHCcEQeLbZWAWZ1G3MnApO2S1
a/z4YWCcgEnInnbHccylXkLVmTkr3u/IQS157jSR08DIMolYNyNx1E1Go5UNGkG+
soHMwXUCxsTKICxUJiB3j+G27eUb7KsJu1V2U+3olym5VtlIeKBg3c16/4f9Gb/w
Dx2WnW6PPLwrruh1e6mz/4RkS/gZvF+Miw1ev4qmeuW00qx54N/sQf/DdJK7GZDX
f2uH8zWf1dlEMYAqs0lCogktcXARgGD1NszN2ra4hwE7AgMBAAE=
-----END PUBLIC KEY-----
PEM;

    public function verify(
        array $envelope,
        string $siteHost
    ): ?array {
        $keyId = is_string(
            $envelope['key_id'] ?? null
        )
            ? $envelope['key_id']
            : '';

        if (
            ($envelope['envelope_schema'] ?? '')
                !== 'slotera-license-envelope-v1'
            || ($envelope['payload_schema'] ?? '')
                !== 'slotera-license-state-v1'
            || ($envelope['algorithm'] ?? '')
                !== 'RSA-SHA256'
            || !is_string($envelope['issued_at'] ?? null)
        ) {
            return null;
        }

        $pem = (new SigningKeyRing())->resolve(
            'license',
            $keyId,
            self::KEY_ID,
            self::PUBLIC_KEY
        );

        if ($pem === null) {
            return null;
        }

        $payload64 =
            $envelope['payload_base64'] ?? null;

        $signature64 =
            $envelope['signature_base64'] ?? null;

        if (
            !is_string($payload64)
            || !is_string($signature64)
            || !function_exists('openssl_verify')
        ) {
            return null;
        }

        $bytes = base64_decode(
            $payload64,
            true
        );

        $signature = base64_decode(
            $signature64,
            true
        );

        if (
            !is_string($bytes)
            || !is_string($signature)
        ) {
            return null;
        }

        $signedMessage =
            "slotera-license-response-v1\n"
            . $bytes;

        if (
            openssl_verify(
                $signedMessage,
                $signature,
                $pem,
                OPENSSL_ALGO_SHA256
            ) !== 1
        ) {
            return null;
        }

        $payload = json_decode(
            $bytes,
            true
        );

        if (
            !is_array($payload)
            || ($payload['schema'] ?? '')
                !== 'slotera-license-state-v1'
            || !in_array(
                $payload['state'] ?? '',
                [
                    'active',
                    'trial',
                    'expired',
                    'suspended',
                    'revoked',
                ],
                true
            )
            || !in_array(
                $payload['plan'] ?? '',
                [
                    'monthly',
                    'yearly',
                    'lifetime',
                    'trial',
                ],
                true
            )
            || !is_string(
                $payload['license_public_id']
                    ?? null
            )
            || !is_string(
                $payload['root_host']
                    ?? null
            )
            || !is_int(
                $payload['state_version']
                    ?? null
            )
            || $payload['state_version'] < 1
            || !is_string(
                $payload['issued_at']
                    ?? null
            )
            || !is_string(
                $payload['key_id']
                    ?? null
            )
            || !is_string(
                $payload['algorithm']
                    ?? null
            )
            || !array_key_exists(
                'expires_at',
                $payload
            )
            || (
                $payload['expires_at'] !== null
                && !is_string(
                    $payload['expires_at']
                )
            )
        ) {
            return null;
        }

        if (
            !hash_equals(
                $keyId,
                $payload['key_id']
            )
            || !hash_equals(
                (string) $envelope[
                    'payload_schema'
                ],
                (string) $payload['schema']
            )
            || !hash_equals(
                (string) $envelope[
                    'algorithm'
                ],
                (string) $payload['algorithm']
            )
            || !hash_equals(
                (string) $envelope[
                    'issued_at'
                ],
                (string) $payload['issued_at']
            )
        ) {
            return null;
        }

        try {
            $issuedDate =
                new \DateTimeImmutable(
                    $payload['issued_at']
                );

            $issued =
                (float) $issuedDate->format(
                    'U.u'
                );


        } catch (\Throwable $error) {
            return null;
        }

        if (
            $issued > microtime(true) + 300
        ) {
            return null;
        }

        $expires =
            $payload['expires_at'];

        if (
            $expires !== null
            && strtotime($expires) === false
        ) {
            return null;
        }

        if (
            $payload['plan'] === 'lifetime'
            && $expires !== null
        ) {
            return null;
        }

        if (
            $payload['plan'] !== 'lifetime'
            && $expires === null
        ) {
            return null;
        }

        $host = strtolower(
            rtrim($siteHost, '.')
        );

        $root = strtolower(
            rtrim(
                $payload['root_host'],
                '.'
            )
        );

        if (
            $host === ''
            || $root === ''
            || (
                $host !== $root
                && !str_ends_with(
                    $host,
                    '.' . $root
                )
            )
        ) {
            return null;
        }

        (new SigningKeyRing())->acceptTransition(
            'license',
            $payload,
            $keyId
        );

        return $payload;
    }
}
