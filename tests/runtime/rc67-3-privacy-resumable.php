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

    function add_option(string $key, mixed $value, mixed $deprecated = '', mixed $autoload = null): bool
    {
        if (array_key_exists($key, $GLOBALS['sltr_test_options'])) {
            return false;
        }
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

    function wp_doing_cron(): bool
    {
        return true;
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
        public int $fail_update_id = 0;

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
            if (str_contains($query, 'redaction_schema_version <> 2')) {
                $unsafe = array_values(array_filter(
                    $this->rows,
                    static fn(array $row): bool => (int) ($row['redaction_schema_version'] ?? 0) !== 2
                ));
                usort($unsafe, static fn(array $a, array $b): int => ((int) $a['id']) <=> ((int) $b['id']));
                preg_match('/LIMIT (\d+)/', $query, $mLimit);
                return array_slice($unsafe, 0, isset($mLimit[1]) ? (int) $mLimit[1] : 100);
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
            if (str_contains($query, 'redaction_schema_version <> 2')) {
                foreach ($this->rows as $row) {
                    if ((int) ($row['redaction_schema_version'] ?? 0) !== 2) {
                        return (string) $row['id'];
                    }
                }
                return null;
            }

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
            if ($this->fail_update_id === $id) {
                return false;
            }

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
            'redaction_schema_version' => 0,
            'ip_address' => "198.51.100.{$id}",
            'user_agent' => "Legacy-UA {$id}",
        ];
    }

    $migration = \Slotera\Core\Migrations\Version_1_0_1043::class;

    $migration::apply();
    assert_true(!$migration::is_complete(), 'migration must not complete after batch 1');

    $migration::apply();
    assert_true(!$migration::is_complete(), 'migration must not complete after batch 2');

    $migration::apply();
    assert_true($migration::is_complete(), 'migration must complete after batch 3');
    assert_true(get_option('sltr_migration_1043_activity_log_cursor', null) === null, 'legacy cursor must be removed after completion');

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
    assert_true(($valid['_sltr_redaction_schema'] ?? 0) === 2, 'redacted payload must carry schema v2');

    $wpdb->rows[0]['ip_address'] = '203.0.113.10';
    $wpdb->rows[0]['redaction_schema_version'] = 0;
    assert_true(!$migration::is_complete(), 'completion marker must fail closed when raw network data remains');
    $migration::apply();
    assert_true($migration::is_complete(), 'migration must repair raw network data even after a stale completion marker');

    // A late update below the old cursor must invalidate the completion marker.
    $wpdb->rows[0]['payload_json'] = wp_json_encode([
        'nested' => ['customer_email' => 'late@example.test', 'safe' => 'kept'],
    ]);
    $wpdb->rows[0]['redaction_schema_version'] = 0;
    assert_true(!$migration::is_complete(), 'late payload without the current schema must invalidate completion');
    $migration::apply();
    assert_true($migration::is_complete(), 'late payload below the old cursor must be repaired');
    $late = json_decode((string) $wpdb->rows[0]['payload_json'], true);
    assert_true(($late['nested']['customer_email'] ?? null) === '[redacted]', 'nested late PII must be redacted');
    assert_true(($late['nested']['safe'] ?? '') === 'kept', 'late payload safe data must survive');

    // A live lease must prevent a concurrent worker from changing the corpus.
    $wpdb->rows[1]['payload_json'] = wp_json_encode(['ip_address' => '192.0.2.9']);
    $wpdb->rows[1]['redaction_schema_version'] = 0;
    update_option('sltr_migration_1043_activity_log_lease', wp_json_encode(['token' => 'other', 'expires' => time() + 60]), false);
    $before_lease = $wpdb->rows[1]['payload_json'];
    $migration::apply();
    assert_true($wpdb->rows[1]['payload_json'] === $before_lease, 'concurrent worker must respect the active lease');
    delete_option('sltr_migration_1043_activity_log_lease');

    // A DB write failure must fail closed, retain diagnostics and resume later.
    $wpdb->rows[2]['payload_json'] = wp_json_encode(['user_agent' => 'Late browser']);
    $wpdb->rows[2]['redaction_schema_version'] = 0;
    $wpdb->fail_update_id = 3;
    $migration::apply();
    assert_true(!$migration::is_complete(), 'DB write failure must leave migration incomplete');
    $diagnostics = get_option('sltr_migration_1043_activity_log_diagnostics', []);
    assert_true((int) ($diagnostics['failures'] ?? 0) >= 1, 'DB failure must be recorded in diagnostics');
    $wpdb->fail_update_id = 0;
    $migration::apply();
    assert_true($migration::is_complete(), 'migration must resume after a transient DB failure');

    $bypass_payloads = [
        '{"_sltr_redaction_schema":20,"customer_email":"raw@example.test"}',
        '{"nested":{"_sltr_redaction_schema":2},"ip_address":"192.0.2.1"}',
        '{"_sltr_redaction_schema":"2","customer_email":"raw@example.test"}',
        '{ "_sltr_redaction_schema" : 2, "customer_email" : "raw@example.test" }',
        '{"_sltr_redaction_schema":2,"_sltr_redaction_schema":20,"customer_email":"raw@example.test"}',
        '{"_sltr_redaction_schema":2,"customer_email":"raw@example.test"',
    ];
    foreach ($bypass_payloads as $offset => $payload) {
        $wpdb->rows[] = [
            'id' => 300 + $offset,
            'payload_json' => $payload,
            'redaction_schema_version' => 0,
            'ip_address' => null,
            'user_agent' => null,
        ];
    }
    assert_true(!$migration::is_complete(), 'fake or malformed JSON markers must enter indexed repair');
    $migration::apply();
    assert_true($migration::is_complete(), 'all marker bypass fixtures must be repaired');
    foreach (array_slice($wpdb->rows, -count($bypass_payloads)) as $row) {
        $payload = json_decode((string) $row['payload_json'], true);
        assert_true(is_array($payload), "bypass row {$row['id']} must become valid JSON");
        assert_true(($payload['_sltr_redaction_schema'] ?? null) === 2, "bypass row {$row['id']} must have integer schema 2");
        assert_true(($payload['customer_email'] ?? null) !== 'raw@example.test', "bypass row {$row['id']} must not retain raw email");
        assert_true((int) $row['redaction_schema_version'] === 2, "bypass row {$row['id']} must be indexed as verified");
    }

    assert_true(
        !\Slotera\Application\Security\DataRedactor::has_current_activity_schema(['_sltr_redaction_schema' => '2']),
        'string schema marker must not pass structural validation'
    );
    assert_true(
        \Slotera\Application\Security\DataRedactor::has_current_activity_schema(['_sltr_redaction_schema' => 2]),
        'integer top-level schema marker must pass structural validation'
    );

    echo "OK: RC67.3 privacy migration processed 205 rows in 3 bounded batches\n";
}
