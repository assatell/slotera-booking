import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const order = readFileSync(resolve(root, 'includes/Application/Services/Translations/TranslationStringOrder.php'), 'utf8');

const required = [
  "'frontend.booking.status.pending_payment' => 'frontend'",
  "'frontend.payment.status.processing' => 'frontend'",
  "'emails.booking.status.pending_payment' => 'emails'",
  "'emails.payment.status.processing' => 'emails'",
  "'Payment received. PayPal is processing the payment.' => 'frontend'",
  "'Booking received / awaiting payment confirmation' => 'frontend'",
];

test('PayPal processing-state translations are runtime-indexed', () => {
  for (const needle of required) {
    assert.ok(order.includes(needle), `missing runtime translation index: ${needle}`);
  }
});
