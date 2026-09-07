<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Application\Security\DataRedactor;
use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_1043 implements MigrationInterface
{
    private const BATCH_SIZE = 100;
    private const CURSOR_OPTION = 'sltr_migration_1043_activity_log_cursor';
    private const COMPLETE_OPTION = 'sltr_migration_1043_activity_log_complete';
    private const DIAGNOSTICS_OPTION = 'sltr_migration_1043_activity_log_diagnostics';
    private const LEASE_OPTION = 'sltr_migration_1043_activity_log_lease';
    private const LEASE_TTL_SECONDS = 120;

    public static function apply(): void
    {
        if (self::is_complete()) {
            return;
        }

        $lease = self::acquire_lease();
        if ($lease === '') {
            return;
        }

        try {
            self::process_batch();
        } finally {
            self::release_lease($lease);
        }
    }

    private static function process_batch(): void
    {
        global $wpdb;

        $table = Database::activity_log_table();
        $schema_fragment = '%"' . DataRedactor::ACTIVITY_PAYLOAD_SCHEMA_KEY . '":' . DataRedactor::ACTIVITY_PAYLOAD_SCHEMA_VERSION . '%';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, payload_json, ip_address, user_agent
                 FROM {$table}
                 WHERE (ip_address IS NOT NULL AND CAST(ip_address AS CHAR) <> '')
                    OR (user_agent IS NOT NULL AND CAST(user_agent AS CHAR) <> '')
                    OR (payload_json IS NOT NULL AND payload_json <> '' AND payload_json NOT LIKE %s)
                 ORDER BY id ASC
                 LIMIT %d",
                $schema_fragment,
                self::BATCH_SIZE
            ),
            ARRAY_A
        );

        if (!is_array($rows)) {
            self::record_diagnostics(0, 0, 0, 1, 0, false);
            return;
        }

        $last_id = 0;
        $redacted_count = 0;
        $malformed_count = 0;

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= $last_id) {
                continue;
            }

            $updates = [];
            $formats = [];

            if ((string) ($row['ip_address'] ?? '') !== '') {
                $updates['ip_address'] = null;
                $formats[] = '%s';
            }

            if ((string) ($row['user_agent'] ?? '') !== '') {
                $updates['user_agent'] = null;
                $formats[] = '%s';
            }

            $original = (string) ($row['payload_json'] ?? '');
            if ($original !== '') {
                $decoded = json_decode($original, true);
                if (is_array($decoded)) {
                    $encoded = wp_json_encode(DataRedactor::activity_payload($decoded));
                } else {
                    $encoded = wp_json_encode(DataRedactor::activity_payload(['malformed_legacy_payload' => true]));
                    ++$malformed_count;
                }

                if (!is_string($encoded) || $encoded === '') {
                    self::record_diagnostics(count($rows), $redacted_count, $malformed_count, 1, $last_id, false);
                    return;
                }

                if ($encoded !== $original) {
                    $updates['payload_json'] = $encoded;
                    $formats[] = '%s';
                }
            }

            if ($updates !== []) {
                $updated = $wpdb->update($table, $updates, ['id' => $id], $formats, ['%d']);
                if ($updated === false) {
                    self::record_diagnostics(count($rows), $redacted_count, $malformed_count, 1, $last_id, false);
                    return;
                }
                ++$redacted_count;
            }

            $last_id = $id;
        }

        if (count($rows) === self::BATCH_SIZE) {
            self::record_diagnostics(count($rows), $redacted_count, $malformed_count, 0, $last_id, false);
            return;
        }

        if (!self::corpus_is_safe()) {
            delete_option(self::CURSOR_OPTION);
            delete_option(self::COMPLETE_OPTION);
            self::record_diagnostics(count($rows), $redacted_count, $malformed_count, 0, $last_id, false);
            return;
        }

        update_option(self::COMPLETE_OPTION, '1', false);
        // Remove the cursor used by pre-schema releases. Selection is now based
        // on the per-row redaction schema, so late low-ID rows cannot be missed.
        delete_option(self::CURSOR_OPTION);
        self::record_diagnostics(count($rows), $redacted_count, $malformed_count, 0, $last_id, true);
    }

    public static function is_complete(): bool
    {
        return (string) get_option(self::COMPLETE_OPTION, '') === '1' && self::corpus_is_safe();
    }

    private static function corpus_is_safe(): bool
    {
        global $wpdb;

        $table = Database::activity_log_table();
        $schema_fragment = '%"' . DataRedactor::ACTIVITY_PAYLOAD_SCHEMA_KEY . '":' . DataRedactor::ACTIVITY_PAYLOAD_SCHEMA_VERSION . '%';
        $unsafe = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id
                 FROM {$table}
                 WHERE (ip_address IS NOT NULL AND CAST(ip_address AS CHAR) <> '')
                    OR (user_agent IS NOT NULL AND CAST(user_agent AS CHAR) <> '')
                    OR (payload_json IS NOT NULL AND payload_json <> '' AND payload_json NOT LIKE %s)
                 ORDER BY id ASC
                 LIMIT 1",
                $schema_fragment
            ),
            ARRAY_A
        );

        return is_array($unsafe) && $unsafe === [];
    }

    private static function acquire_lease(): string
    {
        $now = time();
        try {
            $token = bin2hex(random_bytes(16));
        } catch (\Throwable $error) {
            $token = hash('sha256', uniqid('sltr-privacy-', true));
        }
        $value = wp_json_encode(['token' => $token, 'expires' => $now + self::LEASE_TTL_SECONDS]);
        if (!is_string($value)) {
            return '';
        }
        if (add_option(self::LEASE_OPTION, $value, '', false)) {
            return $token;
        }

        $current = json_decode((string) get_option(self::LEASE_OPTION, ''), true);
        if (!is_array($current) || (int) ($current['expires'] ?? 0) < $now) {
            delete_option(self::LEASE_OPTION);
            if (add_option(self::LEASE_OPTION, $value, '', false)) {
                return $token;
            }
        }

        return '';
    }

    private static function release_lease(string $token): void
    {
        $current = json_decode((string) get_option(self::LEASE_OPTION, ''), true);
        if (is_array($current) && hash_equals((string) ($current['token'] ?? ''), $token)) {
            delete_option(self::LEASE_OPTION);
        }
    }

    private static function record_diagnostics(
        int $scanned,
        int $redacted,
        int $malformed,
        int $failures,
        int $last_id,
        bool $complete
    ): void {
        $previous = get_option(self::DIAGNOSTICS_OPTION, []);
        $previous = is_array($previous) ? $previous : [];
        update_option(self::DIAGNOSTICS_OPTION, [
            'schema_version' => DataRedactor::ACTIVITY_PAYLOAD_SCHEMA_VERSION,
            'scanned' => (int) ($previous['scanned'] ?? 0) + $scanned,
            'redacted' => (int) ($previous['redacted'] ?? 0) + $redacted,
            'malformed' => (int) ($previous['malformed'] ?? 0) + $malformed,
            'failures' => (int) ($previous['failures'] ?? 0) + $failures,
            'last_id' => $last_id,
            'complete' => $complete ? 1 : 0,
            'updated_at' => gmdate('c'),
        ], false);
    }
}
