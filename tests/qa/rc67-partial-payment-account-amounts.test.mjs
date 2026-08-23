import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const account = fs.readFileSync(new URL('../../includes/Frontend/Views/account.php', import.meta.url), 'utf8');

test('RC67 partial-payment booking details show paid and remaining amounts', () => {
  assert.match(account, /payment_status[^\n]*=== 'partial'/);
  assert.match(account, /\$selected_paid_amount\s*=\s*max\(0\.0,\s*\(float\)\s*\(\$selected_booking\['paid_amount'\]/);
  assert.match(account, /array_key_exists\('remaining_amount',\s*\$selected_booking\)/);
  assert.match(account, /frontend\.payment\.status\.paid/);
  assert.match(account, /frontend\.remaining_balance/);
  assert.match(account, /number_format_i18n\(\$selected_paid_amount,\s*2\)/);
  assert.match(account, /number_format_i18n\(\$selected_remaining_amount,\s*2\)/);
  assert.match(account, /frontend\.remaining_balance_paid_on_site/);
});
