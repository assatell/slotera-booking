import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

test('booking mode strategy module executes production payment policies', () => {
  const source = fs.readFileSync(new URL('../../assets/js/frontend-booking-modes.js', import.meta.url), 'utf8');
  const context = { window: {} };
  vm.runInNewContext(source, context, { filename: 'frontend-booking-modes.js' });
  const modes = context.window.SloteraBookingModes;
  assert.equal(modes.normalize('flexible'), 'flex');
  assert.equal(modes.normalize('<img onerror=alert(1)>'), 'simple');
  assert.deepEqual([...modes.paymentChoices('all_options')], ['deposit_payment', 'full_payment', 'booking_only']);
  assert.equal(modes.defaultPaymentChoice(modes.paymentChoices('booking_or_full')), 'full_payment');
  assert.equal(modes.requiresUnit('date_range_inventory'), true);
});
