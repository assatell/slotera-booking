import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const js = fs.readFileSync(new URL('../../assets/js/frontend-booking-form.js', import.meta.url), 'utf8');
const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('availability messages collapse duplicate terminal punctuation', () => {
  assert.match(js, /function normalizeTerminalPunctuation\(value\)/);
  assert.match(js, /replace\(\/\(\[\.!\?\]\)\\1\+\$\/, '\$1'\)/);
  assert.match(js, /normalizeTerminalPunctuation\(sltrFormat\([\s\S]*?available times for %s\./);
  assert.equal('Доступно 11 вариантов времени на сб, 5 сент. 2026 г..'.replace(/([.!?])\1+$/, '$1'), 'Доступно 11 вариантов времени на сб, 5 сент. 2026 г.');
  assert.equal('11 available times for Sat, Sep 5, 2026.'.replace(/([.!?])\1+$/, '$1'), '11 available times for Sat, Sep 5, 2026.');
});

test('dynamic success guidance remains appearance-token driven', () => {
  const selector = '#sltr-booking .sltr-message-center .sltr-message.is-success';
  const start = css.indexOf(selector);
  assert.ok(start >= 0, 'canonical success guidance selector missing');
  const block = css.slice(start, css.indexOf('}', start) + 1);
  assert.match(block, /background:\s*var\(--sltr-card-bg/);
  assert.match(block, /border-color:\s*var\(--sltr-card-border/);
  assert.match(block, /color:\s*var\(--sltr-form-text/);
  assert.doesNotMatch(block, /rgba\(240,\s*253,\s*244/);
});
