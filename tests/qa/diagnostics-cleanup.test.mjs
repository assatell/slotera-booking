import fs from 'node:fs';
import assert from 'node:assert';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const diagnostics = fs.readFileSync(path.join(root, 'includes/Application/Services/DiagnosticsService.php'), 'utf8');
const paymentDebug = fs.readFileSync(path.join(root, 'tests/qa/payment-debug-regression.test.mjs'), 'utf8');

assert(paymentDebug.includes("fileURLToPath(import.meta.url)"), 'Node QA must convert import.meta.url with fileURLToPath');
assert(!paymentDebug.includes('new URL(import.meta.url).pathname'), 'Node QA must not use URL pathname as a filesystem path');
assert(diagnostics.includes("'summary' => $this->summary($checks)"), 'Diagnostics summary must reuse precomputed checks');
assert(diagnostics.includes('private function summary(array $checks): array'), 'Diagnostics summary must accept precomputed checks');
assert(!diagnostics.includes("'system' => 'system_checks'"), 'Diagnostics summary must not call check methods a second time');
assert(diagnostics.includes('private function failure_state(?array $failure, ?array $success): array'), 'Webhook diagnostics must distinguish active from historical failures');
assert(diagnostics.includes("'status' => 'info'"), 'Historical webhook failures must be informational');
assert(diagnostics.includes("$active_window = HOUR_IN_SECONDS"), 'Observability warning window must be bounded');
assert(diagnostics.includes("' historical — latest '"), 'Old observability errors must be reported as historical information');
console.log('diagnostics cleanup regression: PASS');
