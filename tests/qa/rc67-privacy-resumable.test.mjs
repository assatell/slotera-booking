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

test('RC67.3 repair is bounded and redacts malformed payloads', () => {
  const migration = read('includes/Core/Migrations/Version_1_0_1043.php');
  assert.match(migration, /BATCH_SIZE = 100/);
  assert.match(migration, /sltr_migration_1043_activity_log_cursor/);
  assert.match(migration, /payload_json NOT LIKE %s/);
  assert.doesNotMatch(migration, /WHERE id > %d/);
  assert.match(migration, /malformed_legacy_payload/);
  assert.match(migration, /DataRedactor::activity_payload/);
  assert.match(migration, /payload_json NOT LIKE %s/);
  assert.match(migration, /sltr_migration_1043_activity_log_lease/);
  assert.match(migration, /sltr_migration_1043_activity_log_diagnostics/);
  assert.match(migration, /\$updates\['ip_address'\] = null/);
  assert.match(migration, /\$updates\['user_agent'\] = null/);
  assert.match(migration, /ip_address IS NOT NULL/);
  assert.match(migration, /user_agent IS NOT NULL/);
  assert.match(migration, /return is_array\(\$unsafe\) && \$unsafe === \[\]/);
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
