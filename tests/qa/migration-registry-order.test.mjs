import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const registry = fs.readFileSync(new URL('../../includes/Core/Migrations/MigrationRegistry.php', import.meta.url), 'utf8');

test('migration registry stays in ascending semantic-version order', () => {
  const versions = [...registry.matchAll(/^\s*'(\d+\.\d+\.\d+)'\s*=>/gm)].map((match) => match[1]);
  assert.ok(versions.length > 0);
  const numeric = (value) => value.split('.').map(Number);
  for (let index = 1; index < versions.length; index += 1) {
    const previous = numeric(versions[index - 1]);
    const current = numeric(versions[index]);
    const comparison = current[0] - previous[0] || current[1] - previous[1] || current[2] - previous[2];
    assert.ok(comparison > 0, `${versions[index]} must follow ${versions[index - 1]}`);
  }
});
