import fs from 'node:fs';
import assert from 'node:assert/strict';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');
const marker = 'Booking date calendar: canonical Appearance-aware surface and geometry.';

assert.equal(css.split(marker).length - 1, 1, 'Booking date calendar must have one canonical marker');
assert.ok(css.includes('background: var(--sltr-calendar-bg, var(--sltr-card-bg, #f8fafc));'), 'calendar surface must inherit Appearance tokens');
assert.ok(css.includes('border: 1px solid var(--sltr-calendar-border, var(--sltr-card-border, #dbe3ef));'), 'calendar border must inherit Appearance tokens');
assert.ok(css.includes('color: var(--sltr-calendar-text, var(--sltr-form-text, #0f172a));'), 'calendar text must inherit Appearance tokens');
assert.ok(css.includes('background: var(--sltr-calendar-disabled-bg, #f1f5f9);'), 'disabled dates must use calendar disabled token');
assert.ok(css.includes('color: var(--sltr-calendar-disabled-text, #94a3b8);'), 'disabled date text must use calendar disabled token');
assert.equal((css.match(/\.sltr-calendar \{\n/g) || []).length, 2, 'calendar should keep only canonical base plus mobile media rule');
console.log('booking date CSS cleanup regression guard: ok');
