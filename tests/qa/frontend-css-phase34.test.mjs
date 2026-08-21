import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('early package price fallback owners stay removed', () => {
  assert.doesNotMatch(css, /\.sltr-package-price del\s*\{\s*color:\s*var\(--sltr-muted, #94a3b8\);\s*font-size:\s*14px;/s);
  assert.doesNotMatch(css, /\.sltr-package-price b\s*\{\s*color:\s*var\(--sltr-price-new, var\(--sltr-form-text, #0f172a\)\);\s*font-size:\s*20px;/s);
  assert.match(css, /\.sltr-package-price del\s*\{[^}]*--sltr-price-old[^}]*--sltr-price-old-ratio/s);
  assert.match(css, /\.sltr-package-price b\s*\{[^}]*--sltr-price-new[^}]*--sltr-featured-icon-size/s);
});

test('package card heading size remains in canonical rhythm ownership', () => {
  assert.doesNotMatch(css, /\.sltr-package-card h3\s*\{\s*margin:\s*10px 0 4px;/s);
  assert.match(css, /Canonical package-card vertical rhythm[\s\S]*?\.sltr-package-card h3\s*\{\s*font-size:\s*var\(--sltr-featured-icon-size, 24px\);\s*\}/);
});
