import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';
import { resolvePhpExecutable } from '../../tools/php-runtime.mjs';

const root = resolve(import.meta.dirname, '../..');
const runtime = resolve(
  root,
  'tests/runtime/license-state-version-anti-replay.php'
);
const phpExecutable = resolvePhpExecutable();

test(
  'license state_version anti-replay rules hold at runtime',
  () => {
    const output = execFileSync(
      phpExecutable,
      [runtime],
      {
        encoding: 'utf8',
        cwd: root,
      }
    ).trim();

    assert.equal(
      output,
      'OK: license state_version anti-replay runtime tests passed'
    );
  }
);