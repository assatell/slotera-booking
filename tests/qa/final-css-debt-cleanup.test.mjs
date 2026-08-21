import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync('assets/css/frontend.css', 'utf8');

test('legacy booking and package base surfaces use Appearance tokens', () => {
  const start = css.indexOf('.sltr-step {');
  const end = css.indexOf('/* Canonical frontend feedback / standalone utility surfaces. */');
  assert.ok(start >= 0 && end > start);
  const base = css.slice(start, end);

  assert.match(base, /\.sltr-step \{[\s\S]*?background: var\(--sltr-form-bg,/);
  assert.match(base, /\.sltr-package,[\s\S]*?background: var\(--sltr-card-bg,/);
  assert.match(base, /\.sltr-step input \{[\s\S]*?background: var\(--sltr-form-bg,/);
  assert.match(base, /#sltr-submit \{[\s\S]*?background: var\(--sltr-primary,/);
  assert.match(base, /\.sltr-back \{[\s\S]*?background: var\(--sltr-card-bg,/);
  assert.doesNotMatch(base, /(?:background|color|border-color):\s*#(?:fff(?:fff)?|0f172a|64748b|f8fafc|475569|94a3b8)\s*;/i);
});

test('canonical package ownership no longer carries historical repair labels', () => {
  assert.doesNotMatch(css, /v1\.0\.276 button layout hotfix/);
  assert.doesNotMatch(css, /v1\.0\.987:/);
  assert.doesNotMatch(css, /v1\.0\.988:/);
  assert.doesNotMatch(css, /v1\.0\.992 package card alignment polish/);
  assert.match(css, /Canonical package action\/button layout/);
  assert.match(css, /Canonical Package\/Categories width ownership/);
  assert.match(css, /Canonical Packages page Appearance shell/);
  assert.match(css, /Canonical package card title alignment/);
});

test('legacy muted-text Appearance variable stays forbidden', () => {
  assert.doesNotMatch(css, /--sltr-muted-text\b/);
});
