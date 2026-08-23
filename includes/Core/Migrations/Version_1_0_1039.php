<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Application\Security\DataRedactor;
use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_1039 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;

        $table = Database::activity_log_table();

        // Raw network identifiers are no longer retained in dedicated columns.
        $wpdb->query(
            "UPDATE {$table}
             SET ip_address = NULL, user_agent = NULL
             WHERE COALESCE(ip_address, '') <> ''
                OR COALESCE(user_agent, '') <> ''"
        );

        // Re-run historical structured payloads through the current redactor so
        // previously stored top-level ip/user_agent audit fields are removed.
        $last_id = 0;
        $batch_size = 500;

        do {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, payload_json
                     FROM {$table}
                     WHERE id > %d
                       AND payload_json IS NOT NULL
                       AND payload_json <> ''
                     ORDER BY id ASC
                     LIMIT %d",
                    $last_id,
                    $batch_size
                ),
                ARRAY_A
            ) ?: [];

            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $last_id = $id;
                $original = (string) ($row['payload_json'] ?? '');
                $decoded = json_decode($original, true);

                if (!is_array($decoded)) {
                    continue;
                }

                $encoded = wp_json_encode(DataRedactor::payload($decoded));
                if (is_string($encoded) && $encoded !== $original) {
                    $wpdb->update(
                        $table,
                        ['payload_json' => $encoded],
                        ['id' => $id],
                        ['%s'],
                        ['%d']
                    );
                }
            }
        } while (count($rows) === $batch_size);
    }
}