import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import crypto from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { resolvePython } from '../../tools/python-runtime.mjs';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (p) => fs.readFileSync(path.join(root, p), 'utf8');

test('RC66.3 SEO redirect trace is path-only and hardened', () => {
  const source = read('includes/Application/Services/SEOService.php');
  assert.match(source, /wp_parse_url\([^\n]+REQUEST_URI[^\n]+PHP_URL_PATH/);
  assert.doesNotMatch(source, /home_url\(add_query_arg\(\[\],\s*\(string\) \(\$_SERVER\['REQUEST_URI'\]/);
  assert.match(source, /'secure'\s*=>\s*is_ssl\(\)/);
  assert.match(source, /'httponly'\s*=>\s*true/);
  assert.match(source, /'samesite'\s*=>\s*'Lax'/);
  assert.match(source, /'path'\s*=>\s*'\/'/);
});

test('release lineage includes RC66.2 through RC66.5 and release notes identify current candidate', () => {
  const manifest = JSON.parse(read('release-manifest.json'));
  assert.ok(manifest.release_changes.some((v) => v.includes('RC66.2:')));
  assert.ok(manifest.release_changes.some((v) => v.includes('RC66.3:')));
  assert.ok(manifest.release_changes.some((v) => v.includes('RC66.4:')));
  assert.ok(manifest.release_changes.some((v) => v.includes('RC66.5:')));
  assert.match(manifest.release_notes, new RegExp(manifest.candidate.replace('.', '\\.')));
});

test('RC66.4 provenance generation is host-independent and canonical command is executable-shaped', () => {
  const metadata = read('tools/release-metadata.mjs');
  const builder = read('tools/build-rc.py');
  assert.doesNotMatch(metadata, /platform:\s*process\.platform/);
  assert.doesNotMatch(metadata, /arch:\s*process\.arch/);
  assert.match(metadata, /observed host runtime intentionally excluded/);
  assert.match(builder, /node tools\/build-rc\.mjs --output \.\.\//);
  assert.match(builder, /sys\.stdout\.buffer\.write\(payload\)/);
  assert.doesNotMatch(builder, /print\(f'ARCHIVE=\{output\}'\)/);
});

test('RC66.5 repeated builds use sandbox copies and leave the source tree unchanged', () => {
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'sltr-rc665-repro-'));
  const treeHash = (base) => {
    const rows = [];
    const walk = (dir) => {
      for (const item of fs.readdirSync(dir, { withFileTypes: true })) {
        if (item.name === '.git' || item.name === 'node_modules') continue;
        const abs = path.join(dir, item.name);
        if (item.isDirectory()) walk(abs);
        else rows.push([path.relative(base, abs).replaceAll('\\', '/'), crypto.createHash('sha256').update(fs.readFileSync(abs)).digest('hex')]);
      }
    };
    walk(base);
    rows.sort((a,b) => Buffer.compare(Buffer.from(a[0]), Buffer.from(b[0])));
    return crypto.createHash('sha256').update(rows.map((r) => `${r[0]}\0${r[1]}\n`).join('')).digest('hex');
  };
  const before = treeHash(root);
  try {
    const aRoot = path.join(temp, 'tree-a');
    const bRoot = path.join(temp, 'tree-b');
    fs.cpSync(root, aRoot, { recursive: true, filter: (src) => !src.includes(`${path.sep}node_modules${path.sep}`) && !src.includes(`${path.sep}.git${path.sep}`) });
    fs.cpSync(root, bRoot, { recursive: true, filter: (src) => !src.includes(`${path.sep}node_modules${path.sep}`) && !src.includes(`${path.sep}.git${path.sep}`) });
    const name = 'slotera-booking-repro.zip';
    const epoch = '1786882380';
    const python = resolvePython();
    const run = (cwd, out) => spawnSync(python.command, [...python.prefix, 'tools/build-rc.py', '--output', out, '--source-date-epoch', epoch], { cwd, encoding: 'utf8' });
    const outA = path.join(temp, 'a', name);
    const outB = path.join(temp, 'b', name);
    fs.mkdirSync(path.dirname(outA)); fs.mkdirSync(path.dirname(outB));
    const r1 = run(aRoot, outA);
    assert.equal(r1.status, 0, r1.stderr || r1.stdout);
    const r2 = run(bRoot, outB);
    assert.equal(r2.status, 0, r2.stderr || r2.stdout);
    const h = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
    assert.equal(h(outA), h(outB));
  } finally {
    assert.equal(treeHash(root), before, 'mandatory Node QA must not mutate the release tree');
    fs.rmSync(temp, { recursive: true, force: true });
  }
});


test('RC66.4 declares portable Python and canonicalizes cross-host ZIP metadata/order', () => {
  const manifest = JSON.parse(read('release-manifest.json'));
  const qa = read('QA.md');
  const builder = read('tools/build-rc.py');
  const metadata = read('tools/release-metadata.mjs');
  assert.match(manifest.builder.runtime, /CPython 3\.9\+/);
  assert.match(qa, /CPython 3\.9 or newer/);
  assert.match(builder, /relative_to\(ROOT\)\.as_posix\(\)/);
  assert.match(builder, /rel\.encode\('utf-8'\)/);
  assert.match(read('tools/deterministic_zip.py'), /made_by=\(3<<8\)\|20/);
  assert.match(read('tools/deterministic_zip.py'), /made_by=\(3<<8\)\|20/);
  assert.match(read('tools/deterministic_zip.py'), /0x02014b50/);
  assert.match(builder, /write_zip\(output, entries, dt\)/);
  assert.match(metadata, /utf8PathCompare/);
  assert.doesNotMatch(metadata, /localeCompare\(b\.path/);
});
