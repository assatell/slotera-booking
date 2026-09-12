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

    public function verify(array $envelope, string $siteHost, string $lastIssuedAt = ''): ?array
    {
        if (($envelope['schema'] ?? '') !== 'slotera-signed-envelope/v1'
            || ($envelope['key_id'] ?? '') !== self::KEY_ID
            || ($envelope['algorithm'] ?? '') !== 'RSA-SHA256') { return null; }
        $payload64 = $envelope['payload'] ?? null;
        $signature64 = $envelope['signature'] ?? null;
        if (!is_string($payload64) || !is_string($signature64) || !function_exists('openssl_verify')) { return null; }
        $bytes = base64_decode($payload64, true);
        $signature = base64_decode($signature64, true);
        if (!is_string($bytes) || !is_string($signature)
            || openssl_verify($bytes, $signature, self::PUBLIC_KEY, OPENSSL_ALGO_SHA256) !== 1) { return null; }
        $payload = json_decode($bytes, true);
        if (!is_array($payload)
            || ($payload['schema'] ?? '') !== 'slotera-license-certificate/v1'
            || ($payload['plugin'] ?? '') !== 'slotera-booking'
            || !in_array($payload['state'] ?? '', ['active', 'trial', 'revoked'], true)
            || !in_array($payload['plan'] ?? '', ['monthly', 'yearly', 'lifetime', 'trial'], true)
            || !is_string($payload['license_id'] ?? null)
            || !is_string($payload['licensed_root'] ?? null)
            || !is_string($payload['issued_at'] ?? null)
            || !is_string($payload['expires_at'] ?? null)
            || !is_string($payload['trial_started_at'] ?? null)) { return null; }
        try {
            $issuedDate = new \DateTimeImmutable((string) $payload['issued_at']);
            $issued = (float) $issuedDate->format('U.u');
            $lastIssued = $lastIssuedAt !== '' ? (float) (new \DateTimeImmutable($lastIssuedAt))->format('U.u') : 0.0;
        } catch (\Throwable $error) {
            return null;
        }
        if ($issued < $lastIssued || $issued > microtime(true) + 300) { return null; }
        $expires = (string) $payload['expires_at'];
        if (($payload['plan'] ?? '') !== 'lifetime' && ($expires === '' || strtotime($expires) === false)) { return null; }
        if (($payload['plan'] ?? '') === 'lifetime' && $expires !== '') { return null; }
        if (($payload['plan'] ?? '') === 'trial' && strtotime((string) $payload['trial_started_at']) === false) { return null; }
        $host = strtolower(rtrim($siteHost, '.'));
        $root = strtolower(rtrim((string) $payload['licensed_root'], '.'));
        if ($host === '' || $root === '' || ($host !== $root && !str_ends_with($host, '.' . $root))) { return null; }
        return $payload;
    }
}
