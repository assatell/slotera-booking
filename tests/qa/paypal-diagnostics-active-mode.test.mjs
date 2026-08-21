import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const text = fs.readFileSync('includes/Application/Services/DiagnosticsService.php', 'utf8');

test('PayPal diagnostics use active sandbox/live credentials and no legacy credential keys', () => {
  assert.match(text, /payment_paypal_sandbox_client_id/);
  assert.match(text, /payment_paypal_sandbox_client_secret/);
  assert.match(text, /payment_paypal_live_client_id/);
  assert.match(text, /payment_paypal_live_client_secret/);
  assert.match(text, /payment_paypal_webhook_id/);
  assert.doesNotMatch(text, /payment_paypal_client_id/);
  assert.doesNotMatch(text, /payment_paypal_client_secret/);
});

test('PayPal readiness check is independent from Stripe readiness block', () => {
  const stripePos = text.indexOf("if (in_array('stripe', $enabled, true))");
  const paypalPos = text.indexOf("if (in_array('paypal', $enabled, true))");
  assert.ok(stripePos >= 0 && paypalPos > stripePos, 'expected separate Stripe and PayPal blocks');
  const between = text.slice(stripePos, paypalPos);
  assert.doesNotMatch(between, /payment_paypal_/);
});
