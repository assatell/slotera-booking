import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('RC67.3 supersedes old synchronous privacy repair', () => {
  const registry = read('includes/Core/Migrations/MigrationRegistry.php');
  assert.match(registry, /'1\.0\.1039'\s*=>\s*null/);
  assert.match(registry, /'1\.0\.1042'\s*=>\s*null/);
  assert.match(registry, /'1\.0\.1043'\s*=>\s*Version_1_0_1043::class/);
});

test('RC67.3 holds DB version until completion', () => {
  const registry = read('includes/Core/Migrations/MigrationRegistry.php');
  const migrator = read('includes/Core/Migrator.php');
  assert.match(registry, /run\(string \$current_version\): bool/);
  assert.match(registry, /method_exists\(\$migration, 'is_complete'\)/);
  assert.match(migrator, /if \(!MigrationRegistry::run\(\$old\)\)\s*\{\s*return;\s*\}/s);
});

test('RC67.14 repair uses an indexed per-row invariant and redacts malformed payloads', () => {
  const migration = read('includes/Core/Migrations/Version_1_0_1043.php');
  const schema = read('includes/Core/DatabaseSchemaInstaller.php');
  const migrator = read('includes/Core/Migrator.php');
  assert.match(migration, /BATCH_SIZE = 100/);
  assert.match(migration, /sltr_migration_1043_activity_log_cursor/);
  assert.doesNotMatch(migration, /payload_json (?:NOT )?LIKE/);
  assert.doesNotMatch(migration, /WHERE id > %d/);
  assert.match(migration, /redaction_schema_version <> %d/);
  assert.match(schema, /redaction_schema_version TINYINT UNSIGNED NOT NULL DEFAULT 0/);
  assert.match(schema, /KEY sltr_activity_redaction_schema \(redaction_schema_version,id\)/);
  assert.match(migration, /malformed_legacy_payload/);
  assert.match(migration, /DataRedactor::activity_payload/);
  assert.match(migration, /sltr_migration_1043_activity_log_lease/);
  assert.match(migration, /sltr_migration_1043_activity_log_diagnostics/);
  assert.match(migration, /wp_schedule_single_event/);
  assert.match(migration, /MAX_BATCH_SECONDS = 2\.0/);
  assert.match(migration, /is_background_context/);
  assert.match(migration, /wp_doing_cron\(\)/);
  assert.match(migrator, /sltr_activity_log_redaction_batch/);
  assert.match(migration, /\$updates\['ip_address'\] = null/);
  assert.match(migration, /\$updates\['user_agent'\] = null/);
  assert.match(migration, /\$updates\['redaction_schema_version'\]/);
  assert.match(migration, /return \$unsafe === null/);
});

test('RC67.13 revalidates the payload redaction schema for upgraded sites', () => {
  const registry = read('includes/Core/Migrations/MigrationRegistry.php');
  const migration = read('includes/Core/Migrations/Version_1_0_1053.php');
  const redactor = read('includes/Application/Security/DataRedactor.php');
  const repository = read('includes/Infrastructure/Repositories/ActivityLogRepository.php');
  assert.match(registry, /'1\.0\.1053'\s*=>\s*Version_1_0_1053::class/);
  assert.match(migration, /Version_1_0_1043::apply\(\)/);
  assert.match(redactor, /ACTIVITY_PAYLOAD_SCHEMA_VERSION = 2/);
  assert.match(repository, /DataRedactor::activity_payload/);
});

test('RC67.14 structurally rejects non-integer or nested schema lookalikes', () => {
  const redactor = read('includes/Application/Security/DataRedactor.php');
  assert.match(redactor, /array_key_exists\(self::ACTIVITY_PAYLOAD_SCHEMA_KEY, \$payload\)/);
  assert.match(redactor, /\$payload\[self::ACTIVITY_PAYLOAD_SCHEMA_KEY\] === self::ACTIVITY_PAYLOAD_SCHEMA_VERSION/);
  assert.doesNotMatch(redactor, /\(int\) \(\$payload\[self::ACTIVITY_PAYLOAD_SCHEMA_KEY\]/);
});
