import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const rest = fs.readFileSync(new URL('../../includes/Frontend/Controllers/RestApiController.php', import.meta.url), 'utf8');
const paypal = fs.readFileSync(new URL('../../includes/Application/Services/PayPalGatewayService.php', import.meta.url), 'utf8');

test('PayPal incoming webhook debug covers receipt, verification, matching and processing', () => {
  for (const marker of [
    'paypal_webhook_received',
    'paypal_webhook_invalid_payload',
    'paypal_webhook_verify_error',
    'paypal_webhook_verified',
    'paypal_webhook_process_error',
    'paypal_webhook_processed',
  ]) assert.match(rest, new RegExp(marker));
  assert.doesNotMatch(rest, /paypal_webhook_verify_start/);
  for (const marker of [
    'paypal_webhook_verify_transport_error',
    'paypal_webhook_verify_response',
    'paypal_webhook_booking_match',
    'paypal_webhook_duplicate',
    'paypal_webhook_capture_processed',
    'paypal_webhook_ignored',
  ]) assert.match(paypal, new RegExp(marker));
});

test('PayPal webhook verification does not reference capture-only booking variables', () => {
  const body = paypal.match(/public function verify_webhook_event\(array \$headers, array \$event\)[\s\S]*?\n    }\n\n    public function handle_webhook_event/);
  assert.ok(body, 'verify_webhook_event method missing');
  assert.doesNotMatch(body[0], /\$linked_booking_id/);
  assert.doesNotMatch(body[0], /payment_debug_paypal_capture_/);
});

test('webhook debug never logs PayPal signature or access token values', () => {
  const combined = rest + '\n' + paypal;
  assert.doesNotMatch(combined, /['"]transmission_sig['"]\s*=>\s*\$headers/);
  assert.doesNotMatch(combined, /['"]access_token['"]\s*=>/);
});
