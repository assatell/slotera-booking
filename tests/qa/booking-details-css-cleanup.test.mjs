import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('Booking Details owns one canonical Appearance-aware Step 4 layer', () => {
  assert.match(css, /Booking details: canonical form, validation, payment picker, and layout\./);
  assert.doesNotMatch(css, /v1\.0\.596 booking details layout polish/);
  assert.match(css, /\.sltr-step\[data-step="4"\] \.sltr-fields textarea[\s\S]*background: var\(--sltr-card-bg, var\(--sltr-form-bg, #ffffff\)\);/);
  assert.match(css, /\.sltr-step\[data-step="4"\] \.sltr-fields input\.sltr-field-invalid/);
  assert.doesNotMatch(css, /\.sltr-field-invalid\s*\{[\s\S]{0,180}!important/);
});
