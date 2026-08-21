import fs from 'node:fs';
import assert from 'node:assert';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const booking = fs.readFileSync(path.join(root, 'includes/Frontend/Controllers/BookingController.php'), 'utf8');
const view = fs.readFileSync(path.join(root, 'includes/Frontend/Views/booking-form.php'), 'utf8');
const js = fs.readFileSync(path.join(root, 'assets/js/frontend-booking-form.js'), 'utf8');
const paypal = fs.readFileSync(path.join(root, 'includes/Application/Services/PayPalGatewayService.php'), 'utf8');
const paymentsView = fs.readFileSync(path.join(root, 'includes/Admin/Views/payments.php'), 'utf8');

assert(!booking.includes('sltr_payment_debug'), 'temporary payment debug AJAX endpoint must stay removed');
assert(!view.includes('sltrPaymentDebug'), 'temporary client payment debug helper must stay removed');
assert(!js.includes('sltrPaymentDebug'), 'temporary frontend payment debug calls must stay removed');
assert(!paymentsView.includes('Temporary Payment Debug Log'), 'temporary payment debug admin notice must stay removed');
assert(!paypal.includes('payment_debug_paypal_'), 'temporary PayPal payment debug events must stay removed');
assert(!paypal.includes("'access_token' =>"), 'PayPal code must not log access tokens');

const accessTokenBody = paypal.match(/private function access_token\(array \$settings\)[\s\S]*?\n    }\n\n    private function client_id/);
assert(accessTokenBody, 'PayPal access_token helper missing');
assert(!accessTokenBody[0].includes('$linked_booking_id'), 'access_token helper must not reference booking-specific $linked_booking_id');
assert(paypal.includes('paypal_webhook_verify_transport_error'), 'production webhook verification transport logging missing');
console.log('payment debug cleanup regression: PASS');
