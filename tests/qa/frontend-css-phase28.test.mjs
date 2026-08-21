import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('shared public shell owns thank-you and categories transparency exactly once', () => {
  const shared = css.match(/\.sltr-booking,\s*\.sltr-packages-list,[\s\S]*?\.sltr-account\s*\{\s*background:\s*transparent\s*!important;\s*\}/);
  assert.ok(shared, 'shared canonical public-shell transparency owner must remain');
  assert.doesNotMatch(css, /\.sltr-thank-you\s*\{\s*background:\s*transparent\s*!important;\s*\}/);
  assert.doesNotMatch(css, /\.sltr-categories-page\s*\{\s*background:\s*transparent\s*!important;\s*\}/);
});
