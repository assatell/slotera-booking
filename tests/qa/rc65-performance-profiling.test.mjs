import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
const root = resolve(import.meta.dirname, '../..');
const profiler = readFileSync(resolve(root,'includes/Application/Services/PerformanceProfiler.php'),'utf8');
const plugin = readFileSync(resolve(root,'includes/Core/Plugin.php'),'utf8');
const diagnostics = readFileSync(resolve(root,'includes/Application/Services/DiagnosticsService.php'),'utf8');
test('RC65 profiling gate is request-cached and Plugin init is timed',()=>{
  assert.match(profiler,/private static \?bool \$enabled_cache = null/);
  assert.match(plugin,/PerformanceProfiler::finish\('core\.plugin\.init'/);
});
test('RC65 diagnostics exposes settings memoization metrics',()=>{
  assert.match(diagnostics,/Settings resolution this request/);
  assert.match(diagnostics,/settings_all_cache_hits/);
  assert.match(diagnostics,/settings_all_resolve_ms/);
});
