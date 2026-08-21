import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (p) => fs.readFileSync(new URL('../../' + p, import.meta.url), 'utf8');

test('production PayPal webhook health replaces temporary payment debug UI and client instrumentation', () => {
  const payments = read('includes/Admin/Views/payments.php');
  const diagnostics = read('includes/Application/Services/DiagnosticsService.php');
  const bookingController = read('includes/Frontend/Controllers/BookingController.php');
  const bookingView = read('includes/Frontend/Views/booking-form.php');
  const frontendJs = read('assets/js/frontend-booking-form.js');
  assert.equal(payments.includes('Temporary Payment Debug Log'), false);
  assert.equal(bookingController.includes('sltr_payment_debug'), false);
  assert.equal(bookingView.includes('sltrPaymentDebug'), false);
  assert.equal(frontendJs.includes('sltrPaymentDebug'), false);
  for (const label of ['Last PayPal webhook received','Last PayPal webhook verified','Last PayPal webhook processed','Latest PayPal webhook failure']) {
    assert.equal(diagnostics.includes(label), true, label);
  }
});

test('temporary non-webhook payment_debug events are absent from production PHP', () => {
  const files = [
    'includes/Application/Services/PayPalGatewayService.php',
    'includes/Application/Services/PaymentService.php',
    'includes/Frontend/Controllers/RestApiController.php',
    'includes/Frontend/Controllers/BookingController.php',
  ];
  for (const file of files) {
    const text = read(file);
    const withoutWebhook = text.replace(/paypal_webhook_[a-z_]+/g, '');
    assert.equal(/payment_debug_/.test(withoutWebhook), false, file);
  }
});
