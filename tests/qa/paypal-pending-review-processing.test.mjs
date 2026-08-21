import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

test('PayPal pending captures stay processing until completed webhook', () => {
  const gateway = fs.readFileSync(new URL('../../includes/Application/Services/PayPalGatewayService.php', import.meta.url), 'utf8');
  const payment = fs.readFileSync(new URL('../../includes/Application/Services/PaymentService.php', import.meta.url), 'utf8');
  const rest = fs.readFileSync(new URL('../../includes/Frontend/Controllers/RestApiController.php', import.meta.url), 'utf8');
  const view = fs.readFileSync(new URL('../../includes/Frontend/Views/thank-you.php', import.meta.url), 'utf8');
  const frontend = fs.readFileSync(new URL('../../includes/Application/Services/Translations/FrontendTranslationStrings.php', import.meta.url), 'utf8');
  assert.match(gateway, /capture_status === 'PENDING'/);
  assert.match(gateway, /mark_processing\(/);
  assert.match(gateway, /capture_status !== 'COMPLETED'/);
  assert.match(gateway, /PAYMENT\.CAPTURE\.COMPLETED/);
  assert.match(payment, /function mark_processing/);
  assert.match(rest, /redirect_state.*processing/);
  assert.match(view, /Payment received\. PayPal is processing the payment\./);
  assert.match(view, /Booking received \/ awaiting payment confirmation/);
  assert.match(frontend, /frontend\.payment\.status\.processing/);
});
