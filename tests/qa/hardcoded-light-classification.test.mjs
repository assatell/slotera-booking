import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('payment option UI stays Appearance-token owned', () => {
  const block = css.match(/\.sltr-payment-section-title[\s\S]*?@media \(max-width: 640px\)/)?.[0] || '';
  assert.match(block, /color:\s*var\(--sltr-form-text/);
  assert.match(block, /border:\s*1px solid var\(--sltr-card-border/);
  assert.match(block, /background:\s*var\(--sltr-card-bg/);
  assert.match(block, /\.sltr-payment-card-body small[\s\S]*?var\(--sltr-muted/);
  assert.doesNotMatch(block, /(?:background|color):\s*#(?:fff(?:fff)?|0f172a|64748b)\b/i);
});

test('local FAQ surface stays Appearance-token owned', () => {
  const block = css.match(/\.sltr-local-faq-item\s*\{[\s\S]*?\n\}/)?.[0] || '';
  assert.match(block, /border:\s*1px solid var\(--sltr-card-border/);
  assert.match(block, /background:\s*var\(--sltr-card-bg/);
  assert.match(block, /color:\s*var\(--sltr-form-text/);
});

test('package slider hover does not restore hardcoded white surface', () => {
  const block = css.match(/\.sltr-package-slider-arrow:hover,[\s\S]*?\n\}/)?.[0] || '';
  assert.match(block, /background:\s*var\(--sltr-card-bg/);
  assert.doesNotMatch(block, /background:\s*#(?:fff|ffffff)\b/i);
});
