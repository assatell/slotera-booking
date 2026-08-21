import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const paypal = fs.readFileSync('includes/Application/Services/PayPalGatewayService.php', 'utf8');
const diagnostics = fs.readFileSync('includes/Application/Services/DiagnosticsService.php', 'utf8');

test('PayPal capture processing logs booking and provider identifiers for diagnostics', () => {
  assert.match(paypal, /paypal_webhook_capture_processed/);
  assert.match(paypal, /'capture_id'\s*=>\s*sanitize_text_field/);
  assert.match(paypal, /'order_id'\s*=>\s*\$order_id/);
});

test('PayPal diagnostics use local processed capture instead of remote recent-event counts', () => {
  assert.match(diagnostics, /Last PayPal completed payment/);
  assert.match(diagnostics, /paypal_webhook_capture_processed/);
  assert.match(diagnostics, /booking #/);
  assert.match(diagnostics, /capture_id/);
  assert.match(diagnostics, /order_id/);
  assert.doesNotMatch(diagnostics, /Recent PayPal PAYMENT\.CAPTURE\.COMPLETED events/);
});
