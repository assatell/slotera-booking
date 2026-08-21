import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');
const shortcode = fs.readFileSync(new URL('../../includes/Frontend/Shortcodes/BookingShortcode.php', import.meta.url), 'utf8');

test('RC66.12.6 marketing consent card inherits canonical theme tokens', () => {
  const rule = css.match(/\.sltr-step\[data-step="4"\] \.sltr-marketing-consent \.sltr-marketing-consent-card\{[^}]+\}/)?.[0] || '';
  assert.match(rule, /background:var\(--sltr-form-bg,#fff\)/);
  assert.match(rule, /color:var\(--sltr-form-text,#0f172a\)/);
  assert.match(rule, /border:1px solid var\(--sltr-card-border,#d8dee8\)/);
  assert.doesNotMatch(rule, /--sltr-surface|--sltr-border/);
});

test('RC66.12.6 Custom theme maps Form background into --sltr-form-bg', () => {
  assert.match(shortcode, /'--sltr-form-bg'\s*=>\s*'form_background_color'/);
  assert.match(shortcode, /'--sltr-form-text'\s*=>\s*'form_text_color'/);
});
