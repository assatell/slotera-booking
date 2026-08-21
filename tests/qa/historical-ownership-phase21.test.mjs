import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

const retiredLabels = [
  'v1.0.51 solo package media shortcodes',
  'v1.0.53 package cards, More info link, down content and gallery layout',
  'v1.0.54: make only real links/buttons clickable on package cards',
  'v1.0.55: booking package cards are not clickable; only buttons/links are clickable',
  'v1.0.56 solo layouts and animated media lightbox',
  'v1.0.57 mobile solo page horizontal overflow fix',
];

test('foundational Package Solo/media rules use canonical ownership, not version patches', () => {
  for (const label of retiredLabels) assert.ok(!css.includes(label), `retired label returned: ${label}`);
  assert.match(css, /Canonical Package Solo media: slider, gallery, text block, and media lightbox/);
  assert.match(css, /Canonical Package Solo stacked layout and animated gallery lightbox/);
  assert.match(css, /Canonical Package Solo overflow containment and responsive width ownership/);
});

test('gallery lightbox has one canonical base owner before state rules', () => {
  const fullOwners = css.match(/\.sltr-gallery-lightbox\s*\{[^}]*position:\s*fixed;[^}]*opacity:\s*0;[^}]*transition:/gs) || [];
  assert.equal(fullOwners.length, 1, 'duplicate full gallery lightbox base rules must stay removed');
  assert.match(css, /\.sltr-gallery-lightbox\s*\{[^}]*opacity:\s*0;[^}]*transition:/s);
  assert.match(css, /\.sltr-gallery-lightbox\.is-open\s*\{/);
});
