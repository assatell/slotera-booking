import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../..');
const script = fs.readFileSync(path.join(root, 'tools/build-release.ps1'), 'utf8');

test('official release builder supplies the manifest VCS tag', () => {
  assert.match(
    script,
    /\$expectedVcsTag = \[string\]\$manifest\.source\.tag/,
    'builder must read the expected tag from release-manifest.json',
  );
  assert.match(
    script,
    /\$env:SLTR_VCS_TAG = \$expectedVcsTag/,
    'builder must pass the expected tag to the VCS-bound archive builder',
  );

  const tagAssignment = script.indexOf('$env:SLTR_VCS_TAG = $expectedVcsTag');
  const archiveBuild = script.indexOf("'build-rc.mjs'");
  assert.ok(tagAssignment >= 0 && archiveBuild > tagAssignment, 'tag must be set before archive packaging');
});