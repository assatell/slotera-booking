import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const text = fs.readFileSync('includes/Application/Services/DiagnosticsService.php', 'utf8');

test('Stripe diagnostics use active test/live credentials and no legacy credential keys', () => {
  assert.match(text, /payment_stripe_test_publishable_key/);
  assert.match(text, /payment_stripe_test_secret_key/);
  assert.match(text, /payment_stripe_live_publishable_key/);
  assert.match(text, /payment_stripe_live_secret_key/);
  assert.match(text, /payment_stripe_mode/);
  assert.doesNotMatch(text, /settings\['payment_stripe_publishable_key'\]/);
  assert.doesNotMatch(text, /settings\['payment_stripe_secret_key'\]/);
});
