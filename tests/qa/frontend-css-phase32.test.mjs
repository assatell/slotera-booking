import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('Phase 32 keeps Packages CTA sizing in the canonical owner only', () => {
  assert.doesNotMatch(css, /\.sltr-packages-list \.sltr-package-card \.sltr-select-button\s*\{\s*width:\s*100%\s*!important;\s*max-width:\s*none\s*!important;\s*margin:\s*0\s*!important;\s*min-height:\s*48px\s*\}/);
  assert.match(css, /\.sltr-packages-list \.sltr-package-card \.sltr-select-button,\s*#sltr-booking[\s\S]*?width:\s*auto\s*!important;[\s\S]*?max-width:\s*100%\s*!important;[\s\S]*?min-width:\s*0\s*!important;/);
});
