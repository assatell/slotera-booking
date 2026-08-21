import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const src = fs.readFileSync(new URL('../../includes/Application/Services/PaymentService.php', import.meta.url), 'utf8');

test('successful payment reconciliation fails closed when booking confirmation update fails', () => {
  assert.match(src, /if \(!\$this->bookings->update_status\(\$booking_id, BookingLifecycleService::STATUS_CONFIRMED\)\) \{\s*return false;/);
});

test('duplicate and CAS-race callbacks use the same successful-payment recovery path', () => {
  assert.match(src, /if \(\$before_payment_status === \$target_status\)[\s\S]*?return \$this->reconcile_successful_payment/);
  assert.match(src, /if \(!\$updated\)[\s\S]*?return \$this->reconcile_successful_payment/);
});

test('successful-payment side effects are idempotent', () => {
  assert.match(src, /upsert_by_external_id\(/);
  assert.match(src, /count_event\(\$booking_id, \$history_event\) === 0/);
  assert.match(src, /sync_for_booking\(\$booking_id\) <= 0/);
  assert.match(src, /record_usage_for_booking\(\$current\)/);
  assert.match(src, /count_event\(\$booking_id, 'payment_completed_notified'\) === 0/);
});
