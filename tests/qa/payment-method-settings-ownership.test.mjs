import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const view = fs.readFileSync('includes/Admin/Views/payments.php', 'utf8');
const controller = fs.readFileSync('includes/Admin/Controllers/PaymentsController.php', 'utf8');

test('Card and PayPal Checkout enable switches live in their gateway settings, not Online methods', () => {
  assert.match(view, /name="payment_stripe_enabled"/);
  assert.match(view, /Enable Card/);
  assert.match(view, /name="payment_paypal_enabled"/);
  assert.match(view, /Enable PayPal Checkout/);
  assert.doesNotMatch(view, /name="payment_enabled_gateways\[\]" value="stripe"/);
  assert.doesNotMatch(view, /name="payment_enabled_gateways\[\]" value="paypal"/);
});

test('gateway-specific saves own Card/PayPal state and general methods preserve it', () => {
  assert.match(controller, /post_bool\('payment_stripe_enabled'\)/);
  assert.match(controller, /post_bool\('payment_paypal_enabled'\)/);
  assert.match(controller, /with_gateway_enabled/);
  assert.match(controller, /in_array\(\$gateway, \['stripe', 'paypal'\], true\)/);
});
