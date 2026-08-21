import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const rest = fs.readFileSync(new URL('../../includes/Frontend/Controllers/RestApiController.php', import.meta.url), 'utf8');
const paypal = fs.readFileSync(new URL('../../includes/Application/Services/PayPalGatewayService.php', import.meta.url), 'utf8');

test('PayPal production telemetry is limited to incoming webhook health phases', () => {
  assert.doesNotMatch(rest, /payment_debug_paypal_return_/);
  assert.doesNotMatch(paypal, /payment_debug_paypal_capture_/);
  for (const marker of ['paypal_webhook_received','paypal_webhook_verified','paypal_webhook_processed']) assert.match(rest, new RegExp(marker));
  for (const marker of ['paypal_webhook_verify_response','paypal_webhook_capture_processed']) assert.match(paypal, new RegExp(marker));
});
