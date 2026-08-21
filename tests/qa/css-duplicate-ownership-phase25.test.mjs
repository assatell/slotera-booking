import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

function exactSelectorCount(selector) {
  const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return (css.match(new RegExp(`(?:^|\\})\\s*${escaped}\\s*\\{`, 'gm')) || []).length;
}

test('common feedback selectors have one canonical owner', () => {
  assert.equal(exactSelectorCount('.sltr-message'), 1);
  assert.equal(exactSelectorCount('.sltr-loading'), 1);
});

test('gallery lightbox base interaction belongs to its canonical owner', () => {
  assert.equal(exactSelectorCount('.sltr-gallery-lightbox'), 2); // canonical base + intentional <=640px responsive owner
  assert.match(css, /\.sltr-gallery-lightbox\s*\{[^}]*touch-action:\s*none;/s);
});

test('package more-info base sizing is not split into a supplemental exact owner', () => {
  // Later grouped localisation safety rules are intentional; the exact base selector is singular.
  assert.equal(exactSelectorCount('.sltr-more-info-link'), 1);
  assert.match(css, /\.sltr-more-info-link\s*\{[^}]*white-space:\s*nowrap;[^}]*flex:\s*0 0 auto;/s);
});
