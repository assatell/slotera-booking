import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('Package Solo presentation is owned by one canonical base layer', () => {
  assert.match(css, /\/\* Canonical Package Solo: landing shell, hero, content, and description popover\. \*\//);
  assert.match(css, /\.sltr-package-landing \{[\s\S]*?max-width: 1100px;[\s\S]*?grid-template-columns: minmax\(260px, 33\.333%\) minmax\(0, 66\.667%\);[\s\S]*?gap: 32px;/);
  assert.match(css, /\.sltr-package-landing-hero \{[\s\S]*?background: var\(--sltr-card-bg, var\(--sltr-form-bg, #fff\)\);[\s\S]*?border: 1px solid var\(--sltr-card-border, #dbe3ef\);/);
  assert.match(css, /\.sltr-package-landing-content \{[\s\S]*?background: var\(--sltr-form-bg, #fff\);[\s\S]*?color: var\(--sltr-form-text, #0f172a\);/);
});

test('legacy Solo base patch layers are removed while frozen mobile hotfix remains', () => {
  assert.doesNotMatch(css, /v1\.0\.(10|48|49|50) (?:package landing|solo package)/);
  assert.match(css, /Canonical mobile Package Solo \/ shellless layout/);
  assert.doesNotMatch(css, /v1\.0\.416 package catalog\/solo pages:/);
  assert.doesNotMatch(css, /v1\.0\.417 mobile package pages:/);
});
