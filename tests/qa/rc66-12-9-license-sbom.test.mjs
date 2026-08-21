import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

const root = path.resolve(import.meta.dirname, '../..');
const read = (p) => fs.readFileSync(path.join(root, p), 'utf8');

test('RC66.12.9 publishes GPL-2.0-or-later project license metadata', () => {
  const plugin = read('slotera-booking.php');
  assert.match(plugin, /\* License: GPL v2 or later/);
  assert.match(plugin, /\* License URI: https:\/\/www\.gnu\.org\/licenses\/gpl-2\.0\.html/);
  const license = read('LICENSE');
  assert.match(license, /either version 2 of the License, or \(at your option\)\s+any later version/);
  assert.match(license, /GNU GENERAL PUBLIC LICENSE/);
  assert.equal(JSON.parse(read('composer.json')).license, 'GPL-2.0-or-later');
  assert.equal(JSON.parse(read('package.json')).license, 'GPL-2.0-or-later');
});

test('RC66.12.9 documents bundled Noto Sans and emits matching CycloneDX SBOM hash', () => {
  const notices = read('THIRD-PARTY-NOTICES.md');
  assert.match(notices, /Noto Sans Regular 2\.015/);
  assert.match(notices, /OFL-1\.1/);
  assert.match(notices, /assets\/fonts\/OFL-1\.1\.txt/);
  const bom = JSON.parse(read('sbom.cdx.json'));
  assert.equal(bom.bomFormat, 'CycloneDX');
  assert.equal(bom.specVersion, '1.5');
  assert.equal(bom.metadata.component.licenses[0].license.id, 'GPL-2.0-or-later');
  const font = fs.readFileSync(path.join(root, 'assets/fonts/NotoSans-Slotera.ttf'));
  const expected = crypto.createHash('sha256').update(font).digest('hex');
  const component = bom.components.find((c) => c.name === 'NotoSans-Slotera.ttf');
  assert.ok(component);
  assert.equal(component.licenses[0].license.id, 'OFL-1.1');
  assert.equal(component.hashes.find((h) => h.alg === 'SHA-256').content, expected);
});
