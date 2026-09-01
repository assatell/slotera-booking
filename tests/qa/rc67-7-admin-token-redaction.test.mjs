import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const view = fs.readFileSync(
  new URL('../../includes/Admin/Views/booking-single.php', import.meta.url),
  'utf8',
);

test('admin booking details never render raw public action tokens', () => {
  assert.match(view, /\$sltr_hidden_detail_fields = \['cancellation_token', 'reschedule_token'\]/);
  assert.match(view, /in_array\(\(string\) \$k, \$sltr_hidden_detail_fields, true\)/);
  assert.doesNotMatch(view, /echo\s+esc_html\(\(string\)\s*\$booking\['(?:cancellation|reschedule)_token'\]/);
});

test('admin customer-link panel exposes status only, never tokenized URLs', () => {
  assert.doesNotMatch(view, /->cancellation_url\(/);
  assert.doesNotMatch(view, /->reschedule_url\(/);
  assert.doesNotMatch(view, /\$(?:cancel|reschedule)_url/);
  assert.match(view, /!empty\(\$booking\['cancellation_token'\]\) \? esc_html__\('Available'/);
  assert.match(view, /!empty\(\$booking\['reschedule_token'\]\) \? esc_html__\('Available'/);
});
