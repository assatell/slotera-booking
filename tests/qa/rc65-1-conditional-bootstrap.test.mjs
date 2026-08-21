import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '../..');
const plugin = readFileSync(resolve(root, 'includes/Core/Plugin.php'), 'utf8');
const diagnostics = readFileSync(resolve(root, 'includes/Application/Services/DiagnosticsService.php'), 'utf8');

test('RC65.1 conditionally skips frontend bundle on cron and normal admin requests', () => {
  assert.match(plugin, /private function should_register_frontend_components\(\): bool/);
  assert.match(plugin, /wp_doing_cron\(\).*return false/s);
  assert.match(plugin, /is_admin\(\).*wp_doing_ajax\(\)/s);
  assert.match(plugin, /frontend_booking_shortcode.*\$register_frontend/s);
  assert.match(plugin, /frontend_rest_api.*\$register_frontend/s);
});

test('RC65.1 registers admin provider only in admin context', () => {
  assert.match(plugin, /admin_provider.*should_register_admin_components\(\)/s);
  assert.match(plugin, /private function should_register_admin_components\(\): bool[\s\S]*is_admin\(\)/);
});

test('RC65.1 profiles individual bootstrap components and exposes diagnostics', () => {
  assert.match(plugin, /service\.register\.' \.[ ]*\$name/);
  assert.match(plugin, /services_initialized/);
  assert.match(plugin, /services_skipped/);
  assert.match(diagnostics, /Service bootstrap this request/);
  assert.match(diagnostics, /Slowest service registrations/);
});
