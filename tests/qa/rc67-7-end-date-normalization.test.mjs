import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');

test('RC67.7 stores missing ordinary-booking end dates as NULL', () => {
  const repository = read('includes/Infrastructure/Repositories/BookingRepository.php');
  assert.match(repository, /'end_date'\s*=>\s*\$this->normalize_end_date\(\$data\['end_date'\] \?\? null\)/);
  assert.match(repository, /case 'end_date':\s*\$allowed\[\$column\] = \$this->normalize_end_date\(\$value\)/s);

  const php = process.env.PHP_BINARY || 'php';
  const fixture = path.join(root, 'tests', 'runtime', 'rc67-7-end-date-normalization.php');
  const run = spawnSync(php, [fixture], { cwd: root, encoding: 'utf8' });
  assert.equal(run.status, 0, `PHP fixture failed.\nstdout=${run.stdout || ''}\nstderr=${run.stderr || ''}`);
  assert.match(run.stdout, /OK: booking end dates normalize to nullable valid dates/);
});

test('RC67.7 migrates legacy zero dates before advancing DB version', () => {
  const registry = read('includes/Core/Migrations/MigrationRegistry.php');
  const migration = read('includes/Core/Migrations/Version_1_0_1047.php');
  assert.match(registry, /'1\.0\.1047'\s*=>\s*Version_1_0_1047::class/);
  assert.match(migration, /SET end_date = NULL/);
  assert.match(migration, /CAST\(end_date AS CHAR\) = '0000-00-00'/);
  assert.match(migration, /public static function is_complete\(\): bool/);
});
