import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (name) => fs.readFileSync(path.join(root, name), 'utf8');

test('RC67 builder captures VCS before release metadata mutates generated files', () => {
  const builder = read('tools/build-rc.py');
  const metadata = read('tools/release-metadata.mjs');

  assert.match(builder, /capture_vcs\(env\)/);
  assert.match(builder, /VCS-bound build requires a clean working tree/);
  assert.match(builder, /VCS-bound build requires SLTR_VCS_TAG/);

  assert.match(metadata, /capture:\s*'pre-metadata'/);
  assert.match(metadata, /SLTR_VCS_REQUIRED/);
});

test('RC67 VCS-bound build fails closed without Git metadata', () => {
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'sltr-rc67-no-git-'));

  try {
    fs.cpSync(root, temp, {
      recursive: true,
      filter: (p) =>
        !p.includes(`${path.sep}.git${path.sep}`) &&
        !p.includes(`${path.sep}node_modules${path.sep}`),
    });

    const archive = path.join(
      os.tmpdir(),
      `slotera-rc67-no-git-${process.pid}.zip`,
    );

    fs.rmSync(archive, { force: true });

    const run = spawnSync(
      process.execPath,
      [
        'tools/build-rc.mjs',
        '--output',
        archive,
        '--source-date-epoch',
        '1787325720',
      ],
      {
        cwd: temp,
        encoding: 'utf8',
        env: {
          ...process.env,
          SLTR_VCS_REQUIRED: '1',
          SLTR_VCS_TAG: 'v1.0.1038-rc67.0',
        },
      },
    );

    assert.notEqual(run.status, 0);
    assert.match(
      `${run.stdout}\n${run.stderr}`,
      /requires Git metadata/,
    );

    fs.rmSync(archive, { force: true });
  } finally {
    fs.rmSync(temp, { recursive: true, force: true });
  }
});

test('legacy sandbox builds remain source-archive compatible for reproducibility QA', () => {
  const metadata = read('tools/release-metadata.mjs');

  assert.match(metadata, /state:\s*'source-archive'/);
  assert.match(
    metadata,
    /VCS-bound build cannot use source-archive state/,
  );
});
test('RC67 Git source is direct provenance source while RC18 remains historical lineage', () => {
  const metadata = read('tools/release-metadata.mjs');

  assert.match(metadata, /type:\s*'git'/);
  assert.match(metadata, /repository:\s*manifest\.vcs\?\.repository/);
  assert.match(metadata, /lineage:\s*\{/);
  assert.match(metadata, /previous_source:\s*previousSource/);

  assert.match(
    metadata,
    /input_artifact:\s*previousSource\.artifact/,
  );

  assert.match(
    metadata,
    /input_tree_sha256:\s*previousSource\.tree_sha256/,
  );

  assert.match(
    metadata,
    /Git source commit does not match VCS provenance/,
  );

  assert.match(
    metadata,
    /Git source tag does not match VCS provenance/,
  );
});