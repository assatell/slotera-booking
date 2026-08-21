import fs from 'node:fs';
import assert from 'node:assert/strict';

const js = fs.readFileSync(
  new URL('../../assets/js/frontend-booking-form.js', import.meta.url),
  'utf8'
);

const start = js.indexOf('function loadSlots(dateValue)');
const end = js.indexOf('function formatMoney', start);
const block = js.slice(start, end);

assert.ok(start >= 0 && end > start);
assert.ok(!block.includes("data.data.length === 1 &&"));
assert.ok(!block.includes("Only one time is available, so we selected it for you."));
assert.match(block, /data\.data\.forEach\(function \(slot\)/);
assert.match(block, /container\.appendChild\(button\)/);

console.log('A single available time remains visible for explicit customer selection.');
