import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const registry = fs.readFileSync(new URL('../../includes/Core/Migrations/MigrationRegistry.php', import.meta.url), 'utf8');
const migration = fs.readFileSync(new URL('../../includes/Core/Migrations/Version_1_0_1050.php', import.meta.url), 'utf8');

test('RC67.10 rechecks privacy completion on already-upgraded installations', () => {
  assert.match(registry, /'1\.0\.1050'\s*=>\s*Version_1_0_1050::class/);
  assert.match(migration, /Version_1_0_1043::apply\(\)/);
  assert.match(migration, /return Version_1_0_1043::is_complete\(\)/);
});
