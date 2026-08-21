import fs from 'node:fs';
import assert from 'node:assert';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const diagnostics = fs.readFileSync(path.join(root, 'includes/Application/Services/DiagnosticsService.php'), 'utf8');

assert(diagnostics.includes('PaymentTransactionRepository'), 'Diagnostics must read PayPal transaction state without changing payment logic');
assert(diagnostics.includes("'gateway' => 'paypal'"), 'Diagnostics must scope processing lookup to PayPal');
assert(diagnostics.includes("'status' => 'processing'"), 'Diagnostics must inspect currently processing PayPal transactions');
assert(diagnostics.includes("['capture_id']"), 'Diagnostics must expose the PayPal capture id');
assert(diagnostics.includes("['capture_status']"), 'Diagnostics must expose the PayPal capture status');
assert(diagnostics.includes("['pending_reason']"), 'Diagnostics must expose the PayPal pending reason');
assert(diagnostics.includes("'Current PayPal processing payment'"), 'Diagnostics must render a dedicated PayPal processing row');
assert(diagnostics.includes("'reason ' . $processing_pending_reason"), 'Diagnostics must render PayPal status_details.reason when present');
console.log('paypal processing diagnostics regression: PASS');
