import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (p) => fs.readFileSync(new URL('../../' + p, import.meta.url), 'utf8');
const rest = read('includes/Frontend/Controllers/RestApiController.php');
const stripe = read('includes/Application/Services/StripeGatewayService.php');
const diagnostics = read('includes/Application/Services/DiagnosticsService.php');

test('Stripe incoming webhook telemetry covers receipt, verification, matching and processing', () => {
  for (const marker of [
    'stripe_webhook_received',
    'stripe_webhook_invalid_payload',
    'stripe_webhook_verify_error',
    'stripe_webhook_verified',
    'stripe_webhook_process_error',
    'stripe_webhook_processed',
  ]) assert.match(rest, new RegExp(marker));
  assert.doesNotMatch(rest, /stripe_webhook_verify_start/);
  for (const marker of [
    'stripe_webhook_booking_match',
    'stripe_webhook_duplicate',
    'stripe_webhook_payment_applied',
    'stripe_webhook_failure_applied',
    'stripe_webhook_ignored',
  ]) assert.match(stripe, new RegExp(marker));
});

test('Stripe webhook health is production-safe and visible in diagnostics', () => {
  for (const label of [
    'Stripe incoming webhook endpoint',
    'Last Stripe webhook received',
    'Last Stripe webhook verified',
    'Last Stripe webhook booking match',
    'Last Stripe webhook processed',
    'Latest Stripe webhook failure',
  ]) assert.match(diagnostics, new RegExp(label));

  const combined = rest + '\n' + stripe;
  assert.doesNotMatch(combined, /['"]stripe_signature['"]\s*=>/);
  assert.doesNotMatch(combined, /['"]webhook_secret['"]\s*=>/);
  assert.doesNotMatch(combined, /['"]payment_stripe_webhook_secret['"]\s*=>/);
});
