import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const source = await readFile(new URL('../../includes/Application/Services/PayPalGatewayService.php', import.meta.url), 'utf8');

test('PayPal capture asks for a full order representation', () => {
  assert.match(source, /'Prefer'\s*=>\s*'return=representation'/);
});

test('PayPal capture does not reject a linked booking only because a minimal response omits custom_id', () => {
  assert.match(source, /\$paypal_booking_id > 0 && \$paypal_booking_id !== \$booking_id/);
  assert.doesNotMatch(source, /\$booking_id <= 0 \|\| \$paypal_booking_id !== \$booking_id/);
});
