import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const profiler = fs.readFileSync('includes/Application/Services/PerformanceProfiler.php', 'utf8');
const diagnostics = fs.readFileSync('includes/Application/Services/DiagnosticsService.php', 'utf8');
const plugin = fs.readFileSync('includes/Core/Plugin.php', 'utf8');

test('RC65.3 captures rolling request baselines only while profiling is enabled', () => {
  assert.match(profiler, /BASELINE_OPTION\s*=\s*'sltr_performance_request_baselines'/);
  assert.match(profiler, /BASELINE_SAMPLE_LIMIT\s*=\s*10/);
  assert.match(profiler, /add_action\('shutdown', \[self::class, 'capture_request_baseline'\]/);
  assert.match(plugin, /PerformanceProfiler::register_baseline_capture\(\)/);
  assert.match(profiler, /if \(!self::enabled\(\)/);
});

test('RC65.3 distinguishes representative request contexts', () => {
  for (const context of ['cron', 'ajax', 'rest', 'admin_post', 'admin', 'account', 'booking', 'frontend']) {
    assert.match(profiler, new RegExp(`return '${context.replace('_', '_')}'`));
  }
});

test('RC65.3 diagnostics exposes baseline comparisons and localizes PayPal reconciliation time', () => {
  assert.match(diagnostics, /Request baseline —/);
  assert.match(diagnostics, /last SQL/);
  assert.match(diagnostics, /plugin_init_ms/);
  assert.match(diagnostics, /get_date_from_gmt\(\$paypal_reconcile_last, 'Y-m-d H:i:s'\)/);
  assert.doesNotMatch(diagnostics, /\$paypal_reconcile_last \. ' UTC/);
});
