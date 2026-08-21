import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const diagnostics = fs.readFileSync(path.join(root, 'includes/Application/Services/DiagnosticsService.php'), 'utf8');

test('production checklist permits supported payments/webhooks while gating public REST booking', () => {
  assert.match(diagnostics, /\$feature_flags_safe = empty\(\$flag_states\['public_rest_booking'\]\)/);
  assert.match(diagnostics, /'label' => 'Production feature flags'/);
  assert.match(diagnostics, /Disable Public REST booking until its dedicated production security review is complete\./);
  assert.doesNotMatch(diagnostics, /Disable dormant production features before launch\./);
});
