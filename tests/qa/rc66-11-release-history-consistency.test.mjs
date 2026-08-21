import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const readText = (name) => fs.readFileSync(path.join(root, name), 'utf8');

function compareRc(a, b) {
  const ap = a.replace(/^RC/, '').split('.').map(Number);
  const bp = b.replace(/^RC/, '').split('.').map(Number);
  const width = Math.max(ap.length, bp.length);
  for (let i = 0; i < width; i += 1) {
    const delta = (ap[i] || 0) - (bp[i] || 0);
    if (delta !== 0) return delta;
  }
  return 0;
}

test('RC66 changelog is continuous with manifest release history through current candidate', () => {
  const manifest = JSON.parse(readText('release-manifest.json'));
  const declared = manifest.release_changes
    .map((line) => line.match(/\b(RC66(?:\.\d+){1,2})\b/)?.[1])
    .filter(Boolean);
  const uniqueDeclared = [...new Set(declared)].sort(compareRc);
  const current = manifest.candidate;
  assert.match(current, /^RC66(?:\.\d+){1,2}$/);
  assert.ok(uniqueDeclared.includes('RC66.4'), 'manifest must retain RC66.4 in release history');
  assert.equal(uniqueDeclared.at(-1), current, 'current RC66 candidate must be last declared RC66 release');

  const changelogIds = [...readText('CHANGELOG.md').matchAll(/^##\s+1\.0\.1038\s+(RC66(?:\.\d+){1,2})\b/gm)].map((m) => m[1]);
  const counts = new Map(changelogIds.map((id) => [id, changelogIds.filter((x) => x === id).length]));
  for (const id of uniqueDeclared) {
    assert.equal(counts.get(id), 1, `${id} must appear exactly once in CHANGELOG.md`);
  }

  const expectedDescending = [...uniqueDeclared].sort((a, b) => compareRc(b, a));
  assert.deepEqual(changelogIds.slice(0, expectedDescending.length), expectedDescending, 'RC66 changelog entries must be newest-first without omissions');
});
