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
    $GLOBALS['sltr_recovery_response'] = null;
    $GLOBALS['sltr_recovery_apply_calls'] = 0;
    $GLOBALS['sltr_recovery_apply_result'] = false;

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
            . (
                $path !== ''
                    ? '/' . ltrim($path, '/')
                    : ''
            );
    }

    function wp_parse_url(
        string $url,
        int $component = -1
    ) {
        return parse_url(
            $url,
            $component
        );
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
        $json =
            json_encode(
                $value
            );

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

    function sltr_license_api_recovery_url(): string
    {
        return 'https://license.example.test/v1/license/recovery';
    }

    function wp_safe_remote_get(
        string $url,
        array $args = []
    ) {
        return $GLOBALS[
            'sltr_recovery_response'
        ];
    }

    function wp_safe_remote_post(
        string $url,
        array $args
    ): array {
        return [
            'response' => [
                'code' => 200,
            ],
            'body' =>
                json_encode([
                    'normal-license-envelope' => true,
                ]),
        ];
    }

    function wp_remote_retrieve_response_code(
        $response
    ): int {
        if (!is_array($response)) {
            return 0;
        }

        return (int) (
            $response['response']['code']
                ?? 0
        );
    }

    function wp_remote_retrieve_body(
        $response
    ): string {
        if (!is_array($response)) {
            return '';
        }

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

    final class EmergencyLicenseRecoveryService
    {
        public function apply(
            array $envelope
        ): bool {
            ++$GLOBALS[
                'sltr_recovery_apply_calls'
            ];

            return (bool) (
                $GLOBALS[
                    'sltr_recovery_apply_result'
                ] ?? false
            );
        }
    }
}

namespace {
    require_once ABSPATH
        . 'includes/Application/Services/'
        . 'LicenseService.php';

    use Slotera\Application\Services\LicenseService;

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
                'lic_runtime_recovery_001',
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

    function valid_normal_payload(): array
    {
        return [
            'schema' =>
                'slotera-license-state-v1',
            'license_public_id' =>
                'lic_runtime_recovery_001',
            'plan' =>
                'monthly',
            'state' =>
                'active',
            'state_version' =>
                4,
            'root_host' =>
                'example.test',
            'expires_at' =>
                '2026-11-21T12:00:00+00:00',
            'issued_at' =>
                '2026-09-23T12:00:00+00:00',
            'key_id' =>
                'sha256:'
                . str_repeat('a', 64),
            'algorithm' =>
                'RSA-SHA256',
        ];
    }

    function recovery_response(
        int $status,
        string $body
    ): array {
        return [
            'response' => [
                'code' => $status,
            ],
            'body' => $body,
        ];
    }

    function run_case(
        string $name,
        $recoveryResponse,
        int $expectedApplyCalls
    ): void {
        $initial =
            base_state();

        $GLOBALS[
            'sltr_test_options'
        ] = [
            LicenseService::OPTION_NAME =>
                $initial,
        ];

        $GLOBALS[
            'sltr_test_payload'
        ] =
            valid_normal_payload();

        $GLOBALS[
            'sltr_recovery_response'
        ] =
            $recoveryResponse;

        $GLOBALS[
            'sltr_recovery_apply_calls'
        ] = 0;

        $GLOBALS[
            'sltr_recovery_apply_result'
        ] = false;

        $service =
            new LicenseService();

        /*
         * Snapshot immediately before the full refresh.
         * Any recovery failure is required to leave these fields
         * untouched until the normal signed license response is
         * accepted.
         */
        $before =
            get_option(
                LicenseService::OPTION_NAME,
                []
            );

        $result =
            $service->refresh();

        $saved =
            get_option(
                LicenseService::OPTION_NAME,
                []
            );

        assert_runtime(
            $result === true,
            "{$name}: ordinary refresh must continue after recovery failure."
        );

        assert_runtime(
            $GLOBALS[
                'sltr_recovery_apply_calls'
            ] === $expectedApplyCalls,
            "{$name}: unexpected recovery apply call count."
        );

        assert_runtime(
            ($saved['license_state_version'] ?? null)
                === 4,
            "{$name}: normal higher state_version must still be stored."
        );

        assert_runtime(
            ($saved['license_status'] ?? '')
                === 'active',
            "{$name}: normal valid lifecycle state must remain active."
        );

        assert_runtime(
            ($saved['license_last_check_result'] ?? '')
                === 'verified',
            "{$name}: recovery failure must not replace normal verified result."
        );

        assert_runtime(
            ($saved['certificate_envelope'] ?? null)
                === [
                    'normal-license-envelope' => true,
                ],
            "{$name}: normal certificate envelope must be stored."
        );

        /*
         * These fields prove the recovery path itself did not perform
         * a fail-closed mutation before normal refresh processing.
         * If it had, the final valid response would not recreate the
         * original activation timestamp or trial marker semantics.
         */
        assert_runtime(
            ($saved['license_activated_at'] ?? '')
                === ($before['license_activated_at'] ?? ''),
            "{$name}: recovery failure must not alter activation timestamp."
        );

        assert_runtime(
            ($saved['trial_started_at'] ?? '')
                === ($before['trial_started_at'] ?? ''),
            "{$name}: recovery failure must not alter trial state."
        );
    }

    run_case(
        'WP_Error',
        new WP_Error(),
        0
    );

    run_case(
        '404',
        recovery_response(
            404,
            '{"error":{"code":"not_found"}}'
        ),
        0
    );

    run_case(
        '500',
        recovery_response(
            500,
            '{"error":{"code":"internal_error"}}'
        ),
        0
    );

    run_case(
        'invalid JSON',
        recovery_response(
            200,
            '{"schema":'
        ),
        0
    );

    run_case(
        'invalid signed envelope',
        recovery_response(
            200,
            json_encode([
                'schema' =>
                    'slotera-license-recovery-envelope/v1',
                'algorithm' =>
                    'RSA-SHA256',
                'key_id' =>
                    'sha256:'
                    . str_repeat(
                        '0',
                        64
                    ),
                'payload_base64' =>
                    base64_encode(
                        '{}'
                    ),
                'signature_base64' =>
                    base64_encode(
                        'invalid'
                    ),
            ])
        ),
        1
    );

    fwrite(
        STDOUT,
        "OK: license recovery transport fail-open tests passed\n"
    );
}
