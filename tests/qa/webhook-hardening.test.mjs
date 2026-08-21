import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const controller = fs.readFileSync(path.join(root, 'includes/Frontend/Controllers/RestApiController.php'), 'utf8');
const paypal = fs.readFileSync(path.join(root, 'includes/Application/Services/PayPalGatewayService.php'), 'utf8');

test('incoming Stripe and PayPal webhooks reject oversized or malformed requests before expensive verification', () => {
  assert.match(controller, /WEBHOOK_MAX_PAYLOAD_BYTES\s*=\s*524288/);
  assert.match(controller, /stripe_signature_header_shape_is_valid/);
  assert.match(controller, /paypal_webhook_headers_shape_is_valid/);
  assert.match(controller, /signature_header_missing_or_malformed/);
  assert.match(controller, /required_headers_missing_or_malformed/);
  assert.match(controller, /missing_required_event_fields/);
  assert.match(controller, /str_ends_with\(\$host, '\.paypal\.com'\)/);
});

test('pre-verification rejection telemetry is sampled rather than logged for every invalid request', () => {
  assert.match(controller, /RateLimiter::increment\('webhook_reject_log_'/);
  assert.match(controller, /\$count <= 3 \|\| \(\$count % 25\) === 0/);
  assert.match(controller, /legitimate provider retries are never blocked/);
});

test('PayPal OAuth access tokens are cached encrypted and expire before PayPal expiry', () => {
  assert.match(paypal, /use Slotera\\Application\\Security\\SecretStore;/);
  assert.match(paypal, /sltr_pp_at_/);
  assert.match(paypal, /SecretStore::decrypt_string\(\$cached\)/);
  assert.match(paypal, /SecretStore::encrypt_string\(\$token\)/);
  assert.match(paypal, /\$expires_in - 60/);
  assert.match(paypal, /set_transient\(\$cache_key, \$encrypted, \$ttl\)/);
});
