<?php
declare(strict_types=1);
namespace Slotera\Application\Services;
if (!defined('ABSPATH')) { exit; }

final class UpdateEnvelopeVerifier
{
    public const KEY_ID = 'sha256:c3272d14be785233f096bd11ef478a85b12b889bb3e7b7930993c440b1275603';
    private const PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAyXNr/jAiD1cifTbGaDN5
i/eNsLQHddKcPa8pI8ltG4U33FWlWbp8dOkhQqvjVbVSto/XsFy0xJCBM/urgqOD
oH9G21Yh9CgM42h6kRc1wYPCWzBQPYbGzDxRUm99h27hcjNxIW12mwmixympIWkT
qnRcNCyxD8M1ZL7piT1Oi+u+PmjHDmofGLIdn15MAIeCn9bckw/cgITM9PxS8SCt
W19wjOJ9gGGXi/SEM3T5j+iMICQiNeP3UTt5oLrWJD8qpygJbxtPO76KJDc5ezGP
WzASo/Wo7SqAQJjlO+bEZPx+vYXt4We/ZQBy59IwR/Co710mjeaxocFduviCdELc
G2K5e48KhYvT5loDU3RvsZX87U4ABBGUdQ7R6Ar5z5yD49c/Vx1OAOgcVi7dLb/7
cPPZpMSVGzG66kxNAA2Gk4GYeex17dRstLSC2sEbtC/R+g7rwV9T/0Ej+a71HIj8
XjBffDZhULDCOQLbC36UKlQJZpnyLVzhhTEuG7BSqfvBAgMBAAE=
-----END PUBLIC KEY-----
PEM;

    public function verify(array $envelope): ?array
    {
        if (($envelope['schema'] ?? '') !== 'slotera-signed-update-envelope/v1'
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
            || ($payload['schema'] ?? '') !== 'slotera-update/v1'
            || ($payload['plugin'] ?? '') !== 'slotera-booking'
            || ($payload['channel'] ?? '') !== 'stable'
            || !preg_match('/^\d+\.\d+\.\d+$/', (string) ($payload['version'] ?? ''))
            || !preg_match('/^[a-f0-9]{64}$/', (string) ($payload['package_sha256'] ?? ''))
            || !is_string($payload['package_url'] ?? null)
            || !$this->safePackageUrl((string) $payload['package_url'])) { return null; }
        return $payload;
    }

    private function safePackageUrl(string $url): bool
    {
        $parts = wp_parse_url($url);
        if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || isset($parts['user']) || isset($parts['pass'])) { return false; }
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        return $host === 'getslotera.com' || str_ends_with($host, '.getslotera.com');
    }
}
