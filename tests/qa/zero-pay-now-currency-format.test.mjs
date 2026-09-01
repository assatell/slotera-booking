import fs from 'node:fs';
import assert from 'node:assert/strict';

const frontend = fs.readFileSync(
  new URL('../../assets/js/frontend-booking-form.js', import.meta.url),
  'utf8'
);

assert.match(
  frontend,
  /nowEl\.textContent = formatMoney\(decision\.dueNow\);/,
  'The booking summary must format a zero amount with the configured currency.'
);
assert.match(
  frontend,
  /escapeHtml\(formatMoney\(decision\.dueNow\)\)/,
  'Payment-choice cards must format a zero amount with the configured currency.'
);
assert.doesNotMatch(
  frontend,
  /decision\.dueNow > 0 \? formatMoney\(decision\.dueNow\) : '0'/,
  'Zero due-now amounts must not bypass the canonical money formatter.'
);

console.log('Zero pay-now amounts use canonical currency formatting.');
