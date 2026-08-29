import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (name) => fs.readFileSync(path.join(root, name), 'utf8');
const sha256 = (file) => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');

function copyTree(src, dst) {
  fs.cpSync(src, dst, { recursive: true, filter: (p) => !p.includes(`${path.sep}node_modules${path.sep}`) && !p.includes(`${path.sep}.git${path.sep}`) });
}

function initTaggedGit(tree) {
  const manifest = JSON.parse(fs.readFileSync(path.join(tree, 'release-manifest.json'), 'utf8'));
  const env = { ...process.env, GIT_AUTHOR_DATE: '2026-08-29T15:25:23Z', GIT_COMMITTER_DATE: '2026-08-29T15:25:23Z' };
  for (const args of [['init'], ['config','user.email','qa@example.invalid'], ['config','user.name','Slotera QA'], ['add','.'], ['commit','-m','deterministic QA fixture'], ['tag', manifest.source.tag]]) {
    const run = spawnSync('git', args, { cwd: tree, encoding: 'utf8', env });
    assert.equal(run.status, 0, run.stderr || run.stdout);
  }
}

test('RC66.10 canonical builder uses in-tree deterministic DEFLATE rather than host zlib compression', () => {
  const builder = read('tools/build-rc.py');
  const writer = read('tools/deterministic_zip.py');
  assert.match(builder, /from deterministic_zip import write_zip/);
  assert.match(builder, /write_zip\(output, entries, dt\)/);
  assert.match(builder, /sys\.dont_write_bytecode = True/);
  assert.match(builder, /'__pycache__' in path\.parts/);
  assert.match(writer, /def deflate_fixed\(data:bytes\)->bytes/);
  assert.match(writer, /BTYPE=01 fixed Huffman/);
  assert.match(writer, /method=8/);
  assert.doesNotMatch(writer, /zlib\.compress|compressobj/);
});

test('RC66.10 compressed builds are byte-for-byte repeatable and materially below the previous stored size', () => {
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'sltr-rc6610-'));
  try {
    const a = path.join(temp, 'a'); const b = path.join(temp, 'b');
    copyTree(root, a); copyTree(root, b);
    initTaggedGit(a); initTaggedGit(b);
    const outA = path.join(temp, 'one', 'slotera-booking-1.0.1038-rc66.10.zip');
    const outB = path.join(temp, 'two', 'slotera-booking-1.0.1038-rc66.10.zip');
    fs.mkdirSync(path.dirname(outA)); fs.mkdirSync(path.dirname(outB));
    const args = ['tools/build-rc.mjs', '--source-date-epoch', '1786984980'];
    const ra = spawnSync(process.execPath, [...args, '--output', outA], { cwd: a, encoding: 'utf8' });
    const rb = spawnSync(process.execPath, [...args, '--output', outB], { cwd: b, encoding: 'utf8' });
    assert.equal(ra.status, 0, ra.stderr || ra.stdout);
    assert.equal(rb.status, 0, rb.stderr || rb.stdout);
    assert.equal(sha256(outA), sha256(outB));
    assert.ok(fs.statSync(outA).size < 4 * 1024 * 1024, `compressed release unexpectedly large: ${fs.statSync(outA).size}`);
  } finally { fs.rmSync(temp, { recursive: true, force: true }); }
});
