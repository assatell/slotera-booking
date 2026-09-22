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
    $GLOBALS['sltr_test_payload'] = [];

    final class WP_Error {}

    function is_wp_error($value): bool
    {
        return $value instanceof WP_Error;
    }

    function sanitize_text_field($value): string
    {
        return trim((string) $value);
    }

    function home_url(string $path = ''): string
    {
        return 'https://example.test'
            . ($path !== ''
                ? '/' . ltrim($path, '/')
                : '');
    }

    function wp_parse_url(
        string $url,
        int $component = -1
    ) {
        return parse_url($url, $component);
    }

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

    function wp_json_encode($value): string
    {
        $json = json_encode($value);

        return is_string($json)
            ? $json
            : '';
    }

    function sltr_license_api_operation_url(
        string $operation
    ): string {
        return 'https://license.example.test/v1/license/'
            . $operation;
    }

    function wp_safe_remote_post(
        string $url,
        array $args
    ): array {
        return [
            'response' => [
                'code' => 200,
            ],
            'body' => json_encode([
                'stub' => true,
            ]),
        ];
    }

    function wp_remote_retrieve_response_code(
        array $response
    ): int {
        return (int) (
            $response['response']['code']
                ?? 0
        );
    }

    function wp_remote_retrieve_body(
        array $response
    ): string {
        return (string) (
            $response['body'] ?? ''
        );
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
}

namespace Slotera\Application\Security {
    final class SecretStore
    {
        public static function encryption_available(): bool
        {
            return true;
        }

        public static function is_current_encrypted(
            string $value
        ): bool {
            return str_starts_with(
                $value,
                'enc:'
            );
        }

        public static function is_encrypted(
            string $value
        ): bool {
            return str_starts_with(
                $value,
                'enc:'
            );
        }

        public static function encrypt_string(
            string $value
        ): string {
            return 'enc:' . $value;
        }

        public static function decrypt_string(
            string $value
        ): string {
            return str_starts_with(
                $value,
                'enc:'
            )
                ? substr($value, 4)
                : $value;
        }

        public static function mask(
            string $value
        ): string {
            return $value;
        }
    }
}

namespace Slotera\Application\Services {
    final class LicenseCertificateVerifier
    {
        public function verify(
            array $envelope,
            string $expectedRootHost
        ): ?array {
            $payload =
                $GLOBALS[
                    'sltr_test_payload'
                ] ?? null;

            return is_array($payload)
                ? $payload
                : null;
        }
    }
}

namespace {
    require_once ABSPATH
        . 'includes/Application/Services/'
        . 'LicenseService.php';

    function base_state(): array
    {
        return [
            'license_key' =>
                'enc:sltr_'
                . str_repeat('a', 32),
            'licensed_domain' =>
                'example.test',
            'license_status' =>
                'active',
            'license_plan' =>
                'monthly',
            'license_id' =>
                'lic_runtime_001',
            'license_activated_at' =>
                '2026-09-21T12:00:00+00:00',
            'license_expires_at' =>
                '2026-10-21T12:00:00+00:00',
            'trial_started_at' =>
                '',
            'license_last_checked_at' =>
                '2026-09-21T12:00:00+00:00',
            'license_last_check_result' =>
                'verified',
            'certificate_envelope' => [
                'old' => true,
            ],
            'certificate_issued_at' =>
                '2026-09-21T12:00:00+00:00',
            'license_state_version' =>
                3,
        ];
    }

    function payload(
        int $version,
        string $state = 'active',
        string $issuedAt =
            '2026-09-22T12:00:00+00:00'
    ): array {
        return [
            'schema' =>
                'slotera-license-state-v1',
            'license_public_id' =>
                'lic_runtime_001',
            'plan' =>
                'monthly',
            'state' =>
                $state,
            'state_version' =>
                $version,
            'root_host' =>
                'example.test',
            'expires_at' =>
                '2026-10-21T12:00:00+00:00',
            'issued_at' =>
                $issuedAt,
            'key_id' =>
                'sha256:'
                . str_repeat('a', 64),
            'algorithm' =>
                'RSA-SHA256',
        ];
    }

    function run_refresh(
        array $stored,
        array $incoming
    ): array {
        $GLOBALS['sltr_test_options'] = [
            \Slotera\Application\Services\LicenseService::OPTION_NAME
                => $stored,
        ];

        $GLOBALS['sltr_test_payload'] =
            $incoming;

        $service =
            new \Slotera\Application\Services\LicenseService();

        $result = $service->refresh();

        $saved = get_option(
            \Slotera\Application\Services\LicenseService::OPTION_NAME,
            []
        );

        return [
            $result,
            $saved,
        ];
    }

    /*
     * Lower state_version must be rejected.
     */
    [$result, $saved] = run_refresh(
        base_state(),
        payload(
            2,
            'revoked',
            '2026-09-22T13:00:00+00:00'
        )
    );

    assert_runtime(
        $result === false,
        'Lower state_version must be rejected.'
    );

    assert_runtime(
        ($saved['license_state_version'] ?? null)
            === 3,
        'Lower state_version must not replace the stored version.'
    );

    assert_runtime(
        ($saved['license_status'] ?? '')
            === 'active',
        'Lower state_version must preserve the last valid lifecycle state.'
    );

    assert_runtime(
        ($saved['license_last_check_result'] ?? '')
            === 'replay_rejected',
        'Lower state_version must be recorded as replay_rejected.'
    );

    /*
     * Higher state_version must be accepted, including
     * a restrictive signed lifecycle state.
     */
    [$result, $saved] = run_refresh(
        base_state(),
        payload(
            4,
            'suspended',
            '2026-09-22T14:00:00+00:00'
        )
    );

    assert_runtime(
        $result === true,
        'Higher state_version must be accepted.'
    );

    assert_runtime(
        ($saved['license_state_version'] ?? null)
            === 4,
        'Higher state_version must replace the stored version.'
    );

    assert_runtime(
        ($saved['license_status'] ?? '')
            === 'suspended',
        'Higher signed restrictive state must be stored.'
    );

    assert_runtime(
        ($saved['license_last_check_result'] ?? '')
            === 'verified',
        'Accepted higher state_version must be recorded as verified.'
    );

    /*
     * Same state_version with lifecycle mutation must
     * be rejected.
     */
    [$result, $saved] = run_refresh(
        base_state(),
        payload(
            3,
            'revoked',
            '2026-09-22T15:00:00+00:00'
        )
    );

    assert_runtime(
        $result === false,
        'Same state_version with lifecycle mutation must be rejected.'
    );

    assert_runtime(
        ($saved['license_state_version'] ?? null)
            === 3,
        'Rejected same-version mutation must preserve the stored version.'
    );

    assert_runtime(
        ($saved['license_status'] ?? '')
            === 'active',
        'Rejected same-version mutation must preserve the stored state.'
    );

    assert_runtime(
        ($saved['license_last_check_result'] ?? '')
            === 'replay_rejected',
        'Same-version lifecycle mutation must be recorded as replay_rejected.'
    );

    fwrite(
        STDOUT,
        "OK: license state_version anti-replay runtime tests passed\n"
    );
}