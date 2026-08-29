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

    public static function apply(): void
    {
        if (self::is_complete()) {
            return;
        }

        global $wpdb;

        $table = Database::activity_log_table();
        $cursor = max(0, (int) get_option(self::CURSOR_OPTION, 0));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, payload_json, ip_address, user_agent
                 FROM {$table}
                 WHERE id > %d
                 ORDER BY id ASC
                 LIMIT %d",
                $cursor,
                self::BATCH_SIZE
            ),
            ARRAY_A
        );

        if (!is_array($rows)) {
            return;
        }

        $last_id = $cursor;

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
                $encoded = is_array($decoded)
                    ? wp_json_encode(DataRedactor::payload($decoded))
                    : wp_json_encode(['malformed_legacy_payload' => true]);

                if (!is_string($encoded) || $encoded === '') {
                    return;
                }

                if ($encoded !== $original) {
                    $updates['payload_json'] = $encoded;
                    $formats[] = '%s';
                }
            }

            if ($updates !== []) {
                $updated = $wpdb->update(
                    $table,
                    $updates,
                    ['id' => $id],
                    $formats,
                    ['%d']
                );

                if ($updated === false) {
                    return;
                }
            }

            $last_id = $id;
        }

        if ($last_id !== $cursor) {
            update_option(self::CURSOR_OPTION, $last_id, false);
        }

        if (count($rows) === self::BATCH_SIZE) {
            return;
        }

        $remaining = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE id > %d ORDER BY id ASC LIMIT 1",
                $last_id
            )
        );

        if ($remaining !== null) {
            return;
        }

        update_option(self::COMPLETE_OPTION, '1', false);
        delete_option(self::CURSOR_OPTION);
    }

    public static function is_complete(): bool
    {
        return (string) get_option(self::COMPLETE_OPTION, '') === '1';
    }
}
