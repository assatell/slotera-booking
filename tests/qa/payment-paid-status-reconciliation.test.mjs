import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const src = fs.readFileSync(new URL('../../includes/Application/Services/PaymentService.php', import.meta.url), 'utf8');

test('idempotent paid callbacks reconcile pending_payment booking status', () => {
  assert.match(src, /if \(\$before_payment_status === \$target_status\)[\s\S]*?STATUS_PENDING_PAYMENT[\s\S]*?update_status\(\$booking_id, BookingLifecycleService::STATUS_CONFIRMED\)/);
});

test('compare-and-set races also reconcile pending_payment booking status', () => {
  assert.match(src, /if \(!\$updated\)[\s\S]*?\$current[\s\S]*?STATUS_PENDING_PAYMENT[\s\S]*?update_status\(\$booking_id, BookingLifecycleService::STATUS_CONFIRMED\)/);
});
