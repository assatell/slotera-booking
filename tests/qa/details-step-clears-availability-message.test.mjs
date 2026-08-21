import fs from 'node:fs';
import assert from 'node:assert/strict';

const js = fs.readFileSync(new URL('../../assets/js/frontend-booking-form.js', import.meta.url), 'utf8');

const start = js.indexOf('function showStep(step)');
assert.ok(start >= 0);
const block = js.slice(start, js.indexOf('function updateCouponSummary', start));

assert.match(block, /Number\(step\) === 4/);
assert.match(block, /getMessage\('sltr-date-message'\)/);
assert.match(block, /getMessage\('sltr-slot-message'\)/);
assert.match(block, /getMessage\('sltr-global-message'\)/);
assert.match(block, /inlineAvailability\.textContent = ''/);

console.log('Entering Your details clears stale date/time availability guidance.');
