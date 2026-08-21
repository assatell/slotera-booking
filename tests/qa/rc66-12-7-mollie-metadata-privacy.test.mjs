import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const source = fs.readFileSync(new URL('../../includes/Application/Services/MollieGatewayService.php', import.meta.url), 'utf8');

test('RC66.12.7 outbound Mollie payment metadata excludes customer email', () => {
  const start = source.indexOf("'metadata' => [", source.indexOf("'webhookUrl'"));
  assert.ok(start >= 0, 'Mollie create-payment metadata block must exist');
  const end = source.indexOf("],", start);
  assert.ok(end > start, 'Mollie create-payment metadata block must close');
  const block = source.slice(start, end + 2);
  assert.match(block, /'booking_id'\s*=>\s*\$booking_id/);
  assert.match(block, /'payment_mode'\s*=>/);
  assert.doesNotMatch(block, /customer_email|\['customer_email'\]/);
});

test('RC66.12.7 Mollie webhook still binds provider metadata by booking_id', () => {
  assert.match(source, /\$provider_booking_id\s*=\s*absint\(\$payment\['metadata'\]\['booking_id'\]\s*\?\?\s*0\)/);
  assert.match(source, /\$provider_booking_id\s*!==\s*\$booking_id/);
});
