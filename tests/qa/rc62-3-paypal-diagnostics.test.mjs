import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const paypal = fs.readFileSync('includes/Application/Services/PayPalGatewayService.php', 'utf8');
const diagnostics = fs.readFileSync('includes/Application/Services/DiagnosticsService.php', 'utf8');
const payments = fs.readFileSync('includes/Admin/Controllers/PaymentsController.php', 'utf8');
const view = fs.readFileSync('includes/Admin/Views/diagnostics.php', 'utf8');

test('RC62.3 persists reconciliation state and exposes scheduling diagnostics', () => {
  assert.match(paypal, /RECONCILE_STATE_OPTION/);
  assert.match(paypal, /update_option\(self::RECONCILE_STATE_OPTION/);
  assert.match(diagnostics, /PayPal reconciliation scheduled/);
  assert.match(diagnostics, /Last PayPal reconciliation run/);
  assert.match(diagnostics, /PayPal reconciliation result/);
});

test('RC62.3 checks PAYMENT.CAPTURE.PENDING subscription', () => {
  assert.match(diagnostics, /'PAYMENT\.CAPTURE\.PENDING'/);
});

test('RC62.3 provides capability and nonce protected manual reconciliation', () => {
  assert.match(payments, /admin_post_sltr_paypal_reconcile_now/);
  assert.match(payments, /Capabilities::MANAGE_TOOLS/);
  assert.match(payments, /check_admin_referer\('sltr_paypal_reconcile_now'\)/);
  assert.match(view, /Reconcile PayPal now/);
  assert.match(view, /wp_nonce_field\('sltr_paypal_reconcile_now'\)/);
});
