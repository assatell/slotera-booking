import fs from 'node:fs';
import assert from 'node:assert/strict';

const view = fs.readFileSync(new URL('../../includes/Frontend/Views/booking-form.php', import.meta.url), 'utf8');

for (const label of ['Service','Date','Time','Total','Discounts','Tax','Pay now','Pay later']) {
  const escaped = label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  assert.match(view, new RegExp(`sltr_t\\('${escaped}'\\)\\); \\?>:</span>`));
}
console.log('Booking summary labels include an explicit separator before their values.');
