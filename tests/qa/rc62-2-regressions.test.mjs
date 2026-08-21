import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const magic = fs.readFileSync('includes/Application/Services/AccountMagicLinkService.php','utf8');
const paypal = fs.readFileSync('includes/Application/Services/PayPalGatewayService.php','utf8');
const plugin = fs.readFileSync('includes/Core/Plugin.php','utf8');

test('magic confirmation cookie is Lax, host-only and site-wide', () => {
  const block = magic.slice(magic.indexOf('private function set_confirmation_cookie'), magic.indexOf('private function clear_confirmation_cookie'));
  assert.match(block, /'path'\s*=>\s*'\/'/);
  assert.match(block, /'samesite'\s*=>\s*'Lax'/);
  assert.doesNotMatch(block, /'domain'\s*=>/);
});

test('PayPal reconciliation is scheduled and fetches capture status', () => {
  assert.match(paypal, /sltr_paypal_reconcile_processing/);
  assert.match(paypal, /15 \* MINUTE_IN_SECONDS/);
  assert.match(paypal, /\/v2\/payments\/captures\//);
  assert.match(paypal, /paypal_reconciliation/);
  assert.match(plugin, /PayPalGatewayService\(\)\)->register_hooks/);
});

test('PayPal reversed capture webhook is handled', () => {
  assert.match(paypal, /PAYMENT\.CAPTURE\.REVERSED/);
});
