import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('release metadata is committed and exact-tag packaging is read-only', () => {
  const manifest = JSON.parse(read('release-manifest.json'));
  const provenance = JSON.parse(read('build-provenance.json'));
  const builder = read('tools/build-rc.py');
  const gate = read('tools/release-gate.mjs');
  assert.equal(provenance.version, manifest.version);
  assert.equal(provenance.candidate, manifest.candidate);
  assert.equal(provenance.source.tag, manifest.source.tag);
  assert.equal(provenance.build.output, `slotera-booking-${manifest.version}-${manifest.candidate.toLowerCase()}.zip`);
  assert.match(builder, /release-metadata\.mjs', 'verify'/);
  assert.doesNotMatch(builder, /release-metadata\.mjs', 'prepare'/);
  assert.match(gate, /git', \['diff', '--exit-code'\]/);
  assert.match(gate, /tools\/qa\.php', 'qa'/);
});

test('all Python fixtures use the shared resolver', () => {
  const fixture = read('tests/qa/rc67-10-release-hardening.test.mjs');
  assert.match(fixture, /resolvePython/);
  assert.doesNotMatch(fixture, /spawnSync\(['"]python3['"]/);
});

test('Fixed day labels use a shared Unicode-safe character limiter', () => {
  const helper = read('includes/Application/Support/UnicodeText.php');
  const manager = read('includes/Application/BookingModeConfiguration/BookingModeConfigurationManager.php');
  const repository = read('includes/Infrastructure/Repositories/PackageRepository.php');
  assert.match(helper, /mb_substr/);
  assert.match(helper, /preg_match_all\('\/\.\/us'/);
  for (const source of [manager, repository]) {
    assert.match(source, /UnicodeText::limit\([^\n]+, 60\)/);
    assert.doesNotMatch(source, /substr\(sanitize_text_field\([^\n]+full_day_/);
  }
});

test('public attribution uses only the first-party Slotera domain', () => {
  const service = read('includes/Application/Services/WhiteLabelService.php');
  assert.match(service, /https:\/\/getslotera\.com\//);
  assert.doesNotMatch(service, /slotera\.app/);
});
