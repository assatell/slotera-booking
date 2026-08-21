import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
const root = resolve(import.meta.dirname, '../..');
const plugin = readFileSync(resolve(root,'includes/Core/Plugin.php'),'utf8');
const marketing = readFileSync(resolve(root,'includes/Application/Services/MarketingEmailService.php'),'utf8');
const registry = readFileSync(resolve(root,'includes/Application/Services/CronScheduleRegistry.php'),'utf8');

test('RC65.2 marketing bootstrap registers a lazy cron callback instead of constructing MarketingEmailService eagerly', () => {
  assert.match(plugin, /MarketingEmailService::register_lazy_queue_hooks\(\)/);
  assert.doesNotMatch(plugin, /new \\Slotera\\Application\\Services\\MarketingEmailService\(\)\)->register_queue_hooks/);
  assert.match(marketing, /public static function register_lazy_queue_hooks/);
  assert.match(marketing, /add_action\(self::CRON_HOOK, static function \(\): void \{\s*\(new self\(\)\)->process_queue\(\)/s);
});

test('RC65.2 central cron registry throttles persistent schedule audits', () => {
  assert.match(registry, /AUDIT_INTERVAL = 10 \* MINUTE_IN_SECONDS/);
  assert.match(registry, /cron_schedule_audit_skipped/);
  assert.match(registry, /EmailReminderService::CRON_HOOK/);
  assert.match(registry, /MarketingEmailService::CRON_HOOK/);
  assert.match(registry, /PayPalGatewayService::RECONCILE_CRON_HOOK/);
  assert.match(plugin, /CronScheduleRegistry::maybe_ensure\(\)/);
});
