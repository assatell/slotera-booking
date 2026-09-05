<?php

declare(strict_types=1);

namespace {
    define('ABSPATH', __DIR__);
    define('ARRAY_A', 'ARRAY_A');

    $GLOBALS['sltr_test_options'] = [];

    function get_option(string $key, mixed $default = false): mixed
    {
        return $GLOBALS['sltr_test_options'][$key] ?? $default;
    }

    function update_option(string $key, mixed $value, mixed $autoload = null): bool
    {
        $GLOBALS['sltr_test_options'][$key] = $value;
        return true;
    }

    function delete_option(string $key): bool
    {
        unset($GLOBALS['sltr_test_options'][$key]);
        return true;
    }

    function wp_json_encode(mixed $value): string|false
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

namespace Slotera\Core {
    final class Database
    {
        public static function activity_log_table(): string
        {
            return 'wp_sltr_activity_log';
        }
    }
}

namespace {
    final class Rc673FakeWpdb
    {
        /** @var array<int,array<string,mixed>> */
        public array $rows = [];

        public function prepare(string $query, mixed ...$args): string
        {
            foreach ($args as $arg) {
                $replacement = is_int($arg) ? (string) $arg : "'" . addslashes((string) $arg) . "'";
                $query = preg_replace('/%[dsf]/', $replacement, $query, 1) ?? $query;
            }
            return $query;
        }

        public function get_results(string $query, string $output): array
        {
            if (str_contains($query, 'ip_address IS NOT NULL') && str_contains($query, 'user_agent IS NOT NULL')) {
                foreach ($this->rows as $row) {
                    if ((string) ($row['ip_address'] ?? '') !== '' || (string) ($row['user_agent'] ?? '') !== '') {
                        return [['id' => $row['id']]];
                    }
                }
                return [];
            }

            preg_match('/WHERE id > (\d+)/', $query, $mCursor);
            preg_match('/LIMIT (\d+)/', $query, $mLimit);
            $cursor = isset($mCursor[1]) ? (int) $mCursor[1] : 0;
            $limit = isset($mLimit[1]) ? (int) $mLimit[1] : 100;

            $rows = array_values(array_filter(
                $this->rows,
                static fn(array $row): bool => (int) $row['id'] > $cursor
            ));
            usort($rows, static fn(array $a, array $b): int => ((int) $a['id']) <=> ((int) $b['id']));

            return array_slice($rows, 0, $limit);
        }

        public function get_var(string $query): mixed
        {
            preg_match('/WHERE id > (\d+)/', $query, $mCursor);
            $cursor = isset($mCursor[1]) ? (int) $mCursor[1] : 0;

            foreach ($this->rows as $row) {
                if ((int) $row['id'] > $cursor) {
                    return (string) $row['id'];
                }
            }

            return null;
        }

        public function update(
            string $table,
            array $data,
            array $where,
            array $format = [],
            array $whereFormat = []
        ): int|false {
            $id = (int) ($where['id'] ?? 0);

            foreach ($this->rows as &$row) {
                if ((int) $row['id'] !== $id) {
                    continue;
                }

                foreach ($data as $key => $value) {
                    $row[$key] = $value;
                }
                unset($row);
                return 1;
            }
            unset($row);

            return false;
        }
    }

    function assert_true(bool $condition, string $message): void
    {
        if (!$condition) {
            fwrite(STDERR, "FAIL: {$message}\n");
            exit(1);
        }
    }

    require dirname(__DIR__, 2) . '/includes/Core/Migrations/MigrationInterface.php';
    require dirname(__DIR__, 2) . '/includes/Application/Security/DataRedactor.php';
    require dirname(__DIR__, 2) . '/includes/Core/Migrations/Version_1_0_1043.php';

    $wpdb = new Rc673FakeWpdb();
    $GLOBALS['wpdb'] = $wpdb;

    for ($id = 1; $id <= 205; $id++) {
        if ($id === 17) {
            $payload = '{"broken":';
        } else {
            $payload = wp_json_encode([
                'event' => 'booking_updated',
                'ip_address' => "192.0.2.{$id}",
                'user_agent' => "Browser {$id}",
                'safe' => "row-{$id}",
            ]);
        }

        $wpdb->rows[] = [
            'id' => $id,
            'payload_json' => $payload,
            'ip_address' => "198.51.100.{$id}",
            'user_agent' => "Legacy-UA {$id}",
        ];
    }

    $migration = \Slotera\Core\Migrations\Version_1_0_1043::class;

    $migration::apply();
    assert_true(!$migration::is_complete(), 'migration must not complete after batch 1');
    assert_true((int) get_option('sltr_migration_1043_activity_log_cursor', 0) === 100, 'cursor must be 100 after batch 1');

    $migration::apply();
    assert_true(!$migration::is_complete(), 'migration must not complete after batch 2');
    assert_true((int) get_option('sltr_migration_1043_activity_log_cursor', 0) === 200, 'cursor must be 200 after batch 2');

    $migration::apply();
    assert_true($migration::is_complete(), 'migration must complete after batch 3');
    assert_true(get_option('sltr_migration_1043_activity_log_cursor', null) === null, 'cursor must be removed after completion');

    foreach ($wpdb->rows as $row) {
        assert_true($row['ip_address'] === null, "row {$row['id']} raw ip_address must be cleared");
        assert_true($row['user_agent'] === null, "row {$row['id']} raw user_agent must be cleared");
    }

    $malformed = json_decode((string) $wpdb->rows[16]['payload_json'], true);
    assert_true(
        is_array($malformed) && ($malformed['malformed_legacy_payload'] ?? false) === true,
        'malformed payload must be replaced with privacy-safe marker'
    );

    $valid = json_decode((string) $wpdb->rows[0]['payload_json'], true);
    assert_true(is_array($valid), 'valid payload must remain valid JSON');
    assert_true(($valid['ip_address'] ?? null) === '[redacted]', 'valid payload must redact ip_address');
    assert_true(($valid['user_agent'] ?? null) === '[redacted]', 'valid payload must redact user_agent');
    assert_true(($valid['safe'] ?? '') === 'row-1', 'non-sensitive payload data must be preserved');

    $wpdb->rows[0]['ip_address'] = '203.0.113.10';
    assert_true(!$migration::is_complete(), 'completion marker must fail closed when raw network data remains');
    $migration::apply();
    assert_true($migration::is_complete(), 'migration must repair raw network data even after a stale completion marker');

    echo "OK: RC67.3 privacy migration processed 205 rows in 3 bounded batches\n";
}
