import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const migrations = path.join(root, 'includes/Core/Migrations');

test('versioned migrations importing Migrator are namespace-correct', () => {
  const offenders = [];
  for (const name of fs.readdirSync(migrations).filter((n) => /^Version_.*\.php$/.test(n))) {
    const source = fs.readFileSync(path.join(migrations, name), 'utf8');
    if (source.includes('Migrator::') && !source.includes('use Slotera\\Core\\Migrator;')) {
      offenders.push(name);
    }
  }
  assert.deepEqual(offenders, []);
});
