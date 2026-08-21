import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const paypal = fs.readFileSync('includes/Application/Services/PayPalGatewayService.php', 'utf8');
const diagnostics = fs.readFileSync('includes/Application/Services/DiagnosticsService.php', 'utf8');

test('PayPal webhook registration diagnostics are read-only, app-scoped and cached', () => {
  assert.match(paypal, /webhook_registration_health/);
  assert.match(paypal, /\/v1\/notifications\/webhooks/);
  assert.match(paypal, /\/event-types/);
  assert.doesNotMatch(paypal, /\/v1\/notifications\/webhooks-events/, 'registration diagnostics must not rely on PayPal event-history visibility');
  assert.match(paypal, /set_transient\([^;]+5 \* MINUTE_IN_SECONDS/);
  const healthBody = paypal.slice(paypal.indexOf('public function webhook_registration_health'), paypal.indexOf('public function verify_webhook_event'));
  assert.doesNotMatch(healthBody, /wp_remote_post\s*\(/, 'registration health must not mutate PayPal state');
  assert.doesNotMatch(healthBody, /\/simulate-event|\/webhooks-lookup/, 'registration health must not create simulator events or webhook lookups');
});

test('Diagnostics reports app ownership, URL match, required subscriptions and the last locally processed payment', () => {
  for (const label of [
    'PayPal API app authentication',
    'Configured PayPal Webhook ID belongs to current app',
    'PayPal webhook listener URL matches',
    'Last PayPal completed payment',
  ]) assert.match(diagnostics, new RegExp(label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
  for (const event of ['PAYMENT.CAPTURE.COMPLETED','PAYMENT.CAPTURE.DECLINED','PAYMENT.CAPTURE.DENIED','PAYMENT.CAPTURE.REVERSED','CHECKOUT.ORDER.VOIDED']) {
    assert.match(diagnostics, new RegExp(event.replace(/\./g, '\\.')));
  }
});
