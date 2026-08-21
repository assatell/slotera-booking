import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('early tooltip and package-card layers are canonical, not historical patches', () => {
  for (const label of ['v1.0.1 tooltip', 'v1.0.2 adaptive tooltip', 'v1.0.4 package card', 'v1.0.4 rebuild', 'v1.0.5 booking card', 'v1.0.6 package select', 'v1.0.8 package card']) {
    assert.equal(css.includes(label), false, `legacy label should be gone: ${label}`);
  }
  assert.match(css, /Canonical package tooltip base/);
  assert.match(css, /Canonical package-card vertical rhythm/);
});

test('package-card neutral hover surface has one canonical declaration owner', () => {
  const needle = 'box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);';
  const count = css.split(needle).length - 1;
  assert.ok(count >= 1, 'expected canonical neutral package-card shadow');
  const region = css.slice(css.indexOf('Canonical booking/package-card CTA alignment'), css.indexOf('Canonical Package Solo'));
  assert.equal((region.match(/\.sltr-package:hover,\s*\n\.sltr-package-card:hover/g) || []).length, 1);
});
