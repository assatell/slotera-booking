import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('horizontal gallery keeps one canonical aspect-ratio owner', () => {
  const matches = css.match(/\.sltr-package-gallery-horizontal \.sltr-package-gallery-item\s*\{[^}]*aspect-ratio:\s*16\s*\/\s*9/gs) || [];
  assert.equal(matches.length, 1);
});

test('mobile horizontal gallery width is owned by canonical gallery item containment', () => {
  assert.doesNotMatch(css, /@media\s*\(max-width:\s*640px\)[\s\S]*?\.sltr-package-gallery-horizontal \.sltr-package-gallery-item\s*\{\s*width:\s*100%/);
  assert.match(css, /\.sltr-package-gallery-item\s*\{[^}]*width:\s*100%\s*!important/);
});
