import fs from 'node:fs';
import assert from 'node:assert/strict';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8').replace(/\r\n/g, '\n');
const marker = 'Booking time slots: canonical Appearance-aware surface, states and layout.';

assert.equal(css.split(marker).length - 1, 1, 'Booking time slots must have one canonical marker');
assert.ok(css.includes('border: 1px solid var(--sltr-card-border, #dbe3ef);'), 'time slots must inherit Appearance border token');
assert.ok(css.includes('background: var(--sltr-card-bg, #fff);'), 'time slots must inherit Appearance card background');
assert.ok(css.includes('color: var(--sltr-form-text, #0f172a);'), 'time slots must inherit Appearance text token');
assert.ok(css.includes('.sltr-slot.is-selected {\n    background: var(--sltr-primary, #2563eb);'), 'selected time slot must use primary Appearance token');
assert.ok(css.includes('.sltr-time-layout-grid .sltr-slots {\n    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));'), 'grid time layout geometry must be preserved');
assert.ok(css.includes('.sltr-time-layout-list .sltr-slots {\n    grid-template-columns: 1fr;'), 'list time layout geometry must be preserved');
assert.equal((css.match(/^\.sltr-slot \{/gm) || []).length, 1, 'time slot base surface must have one canonical rule');
assert.equal((css.match(/^\.sltr-slots \{/gm) || []).length, 1, 'time slots base container must have one canonical rule');
console.log('booking time CSS cleanup regression guard: ok');
