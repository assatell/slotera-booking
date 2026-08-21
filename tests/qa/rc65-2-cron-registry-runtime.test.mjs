import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';
const root = resolve(import.meta.dirname, '../..');
const file = resolve(root,'includes/Application/Services/CronScheduleRegistry.php').replaceAll('\\','\\\\').replaceAll("'","\\'");

test('RC65.2 cron audit skips schedule scans while throttle is fresh', () => {
  const php = `
    define('ABSPATH', __DIR__); define('MINUTE_IN_SECONDS',60); define('HOUR_IN_SECONDS',3600); define('DAY_IN_SECONDS',86400);
    $calls=0;
    function get_option($k,$d=false){ if($k==='sltr_cron_schedule_audit_at') return time(); if($k==='sltr_cron_schedule_audit_schema') return 2; return $d; }
    function _get_cron_array(){ return [time()+100=>['sltr_process_email_queue'=>[],'sltr_cleanup_secure_mail_attachments'=>[],'sltr_cleanup_magic_link_options'=>[],'sltr_privacy_retention_cleanup'=>[],'sltr_process_marketing_automations'=>[],'sltr_paypal_reconcile_processing'=>[],'sltr_process_marketing_queue'=>[]]]; }
    function wp_next_scheduled($h){ global $calls; $calls++; return false; }
    function update_option($k,$v,$a=null){ return true; }
    function wp_schedule_event($a,$b,$c){ return true; }
    function wp_get_schedule($h){ return false; }
    function wp_clear_scheduled_hook($h){ return 0; }
    function absint($v){ return abs((int)$v); }
    class DummyProfiler { public static function metric($k,$v=1){} }
    class_alias('DummyProfiler','Slotera\\Application\\Services\\PerformanceProfiler');
    require '${file}';
    \\Slotera\\Application\\Services\\CronScheduleRegistry::maybe_ensure();
    echo $calls;
  `;
  const out = execFileSync('php',['-r',php],{encoding:'utf8'}).trim();
  assert.equal(out,'0');
});
