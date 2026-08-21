import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('Phase 33 removes the obsolete early booking progress owner', () => {
  assert.doesNotMatch(css, /\.sltr-progress\s*\{\s*display:\s*flex;\s*gap:\s*8px;\s*margin:\s*0 0 14px;/);
  assert.doesNotMatch(css, /\.sltr-progress span\s*\{\s*width:\s*28px;\s*height:\s*28px;/);
  assert.match(css, /\/\* Canonical booking flow motion and interaction polish\. \*\/[\s\S]*?\.sltr-progress\s*\{[\s\S]*?justify-content:\s*center;[\s\S]*?gap:\s*10px;/);
  assert.match(css, /\/\* Canonical booking progress and availability feedback components\. \*\/[\s\S]*?\.sltr-progress span\s*\{[\s\S]*?width:\s*auto;[\s\S]*?min-width:\s*92px;/);
});
