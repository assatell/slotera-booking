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
    $GLOBALS['sltr_recovered'] = false;
    $GLOBALS['sltr_recovery_apply_calls'] = 0;
    $GLOBALS['sltr_verify_calls'] = 0;

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
    ): array {
        return [
            'response' => [
                'code' => 200,
            ],
            'body' =>
                json_encode([
                    'schema' =>
                        'slotera-license-recovery-envelope/v1',
                    'algorithm' =>
                        'RSA-SHA256',
                    'key_id' =>
                        'sha256:'
                        . str_repeat(
                            'a',
                            64
                        ),
                    'payload_base64' =>
                        base64_encode(
                            '{"test":true}'
                        ),
                    'signature_base64' =>
                        base64_encode(
                            'test-signature'
                        ),
                ]),
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
                    'ordinary-license-envelope' =>
                        true,
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
    final class EmergencyLicenseRecoveryService
    {
        public function apply(
            array $envelope
        ): bool {
            ++$GLOBALS[
                'sltr_recovery_apply_calls'
            ];

            /*
             * This stub represents a successfully verified
             * emergency recovery that installs the replacement
             * normal license signing key.
             */
            $GLOBALS[
                'sltr_recovered'
            ] = true;

            return true;
        }
    }

    final class LicenseCertificateVerifier
    {
        public function verify(
            array $envelope,
            string $expectedRootHost
        ): ?array {
            ++$GLOBALS[
                'sltr_verify_calls'
            ];

            /*
             * The ordinary response may be accepted only if
             * emergency recovery happened first.
             */
            if (
                empty(
                    $GLOBALS[
                        'sltr_recovered'
                    ]
                )
            ) {
                return null;
            }

            return [
                'schema' =>
                    'slotera-license-state-v1',
                'license_public_id' =>
                    'lic_recovery_order_001',
                'plan' =>
                    'monthly',
                'state' =>
                    'active',
                'state_version' =>
                    4,
                'root_host' =>
                    'example.test',
                'expires_at' =>
                    '2026-11-23T12:00:00+00:00',
                'issued_at' =>
                    '2026-09-23T12:00:00+00:00',
                'key_id' =>
                    'sha256:'
                    . str_repeat(
                        'b',
                        64
                    ),
                'algorithm' =>
                    'RSA-SHA256',
            ];
        }
    }
}

namespace {
    require_once ABSPATH
        . 'includes/Application/Services/'
        . 'LicenseService.php';

    use Slotera\Application\Services\LicenseService;

    $GLOBALS[
        'sltr_test_options'
    ] = [
        LicenseService::OPTION_NAME => [
            'license_key' =>
                'enc:sltr_'
                . str_repeat(
                    'a',
                    32
                ),
            'licensed_domain' =>
                'example.test',
            'license_status' =>
                'active',
            'license_plan' =>
                'monthly',
            'license_id' =>
                'lic_recovery_order_001',
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
        ],
    ];

    $service =
        new LicenseService();

    $result =
        $service->refresh();

    $saved =
        get_option(
            LicenseService::OPTION_NAME,
            []
        );

    assert_runtime(
        $result === true,
        'Refresh must succeed after recovery installs replacement trust.'
    );

    assert_runtime(
        $GLOBALS[
            'sltr_recovery_apply_calls'
        ] === 1,
        'Emergency recovery must be applied exactly once.'
    );

    assert_runtime(
        $GLOBALS[
            'sltr_verify_calls'
        ] === 1,
        'Ordinary license envelope must be verified exactly once.'
    );

    assert_runtime(
        $GLOBALS[
            'sltr_recovered'
        ] === true,
        'Recovery must complete before normal verification.'
    );

    assert_runtime(
        ($saved['license_state_version'] ?? null)
            === 4,
        'Post-recovery normal signed state must be stored.'
    );

    assert_runtime(
        ($saved['license_status'] ?? '')
            === 'active',
        'Post-recovery valid license must remain active.'
    );

    assert_runtime(
        ($saved['license_last_check_result'] ?? '')
            === 'verified',
        'Successful post-recovery verification must be recorded.'
    );

    assert_runtime(
        ($saved['certificate_envelope'] ?? null)
            === [
                'ordinary-license-envelope' => true,
            ],
        'Post-recovery ordinary certificate must be stored.'
    );

    fwrite(
        STDOUT,
        "OK: emergency recovery runs before ordinary license verification\n"
    );
}
