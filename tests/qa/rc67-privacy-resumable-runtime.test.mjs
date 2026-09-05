import test from 'node:test';
import assert from 'node:assert/strict';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const fixture = path.join(root, 'tests', 'runtime', 'rc67-3-privacy-resumable.php');

test('RC67.3 privacy migration processes 205 rows in exactly three bounded batches', () => {
  const php = process.env.PHP_BINARY || 'php';
  const run = spawnSync(php, [fixture], {
    cwd: root,
    encoding: 'utf8',
  });

  assert.equal(
    run.status,
    0,
    `PHP fixture failed.\nstdout=${run.stdout || ''}\nstderr=${run.stderr || ''}`
  );
  assert.match(
    run.stdout,
    /OK: RC67\.3 privacy migration processed 205 rows in 3 bounded batches/
  );
});
