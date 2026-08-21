import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const qa = fs.readFileSync(path.join(root, 'tools/qa.php'), 'utf8');

test('RC66.1/RC66.2 PHP lint avoids shell command quoting', () => {
  assert.doesNotMatch(qa, /exec\(escapeshellarg\(PHP_BINARY\).* -l /);
});

test('RC66.1 default contact image ships as compact WebP only', () => {
  const webp = path.join(root, 'assets/images/contact-block-default.webp');
  const png = path.join(root, 'assets/images/contact-block-default.png');
  assert.equal(fs.existsSync(webp), true);
  assert.equal(fs.existsSync(png), false);
  assert.ok(fs.statSync(webp).size < 150 * 1024, 'default WebP should remain comfortably below 150 KB');
  for (const file of [
    'includes/Admin/Views/package-form/sections/solo-content.php',
    'includes/Frontend/Views/package-detail.php',
  ]) {
    const source = fs.readFileSync(path.join(root, file), 'utf8');
    assert.match(source, /contact-block-default\.webp/);
    assert.doesNotMatch(source, /contact-block-default\.png/);
  }
});

test('RC66.1 runtime fixture uses an isolated temp directory with guaranteed cleanup', () => {
  const source = fs.readFileSync(path.join(root, 'tests/qa/rc63-1-simple-extras-runtime.test.mjs'), 'utf8');
  assert.match(source, /mkdtempSync/);
  assert.match(source, /finally \{/);
  assert.match(source, /rmSync\(fixtureDir, \{ recursive: true, force: true \}\)/);
  assert.doesNotMatch(source, /tests\/qa\/\.tmp-rc631\.php/);
});
