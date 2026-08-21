import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('Phase 26 keeps one canonical step appearance owner', () => {
  const matches = css.match(/\.sltr-step\s*\{[^}]*background:\s*var\(--sltr-form-bg/gs) || [];
  assert.equal(matches.length, 1);
});

test('Phase 26 keeps one canonical popular badge surface owner', () => {
  const matches = css.match(/\.sltr-badge-popular\s*\{[^}]*background:\s*transparent\s*!important;[^}]*--sltr-featured-icon-color/gs) || [];
  assert.equal(matches.length, 1);
});
