import test from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';

const root = resolve(import.meta.dirname, '../..');

test('RC65 SettingsRepository memoizes expensive resolution but observes same-request option changes', () => {
  const dir = mkdtempSync(join(tmpdir(), 'sltr-rc65-'));
  const script = join(dir, 'memo.php');
  const repo = join(root, 'includes/Infrastructure/Repositories/SettingsRepository.php').replaceAll('\\', '\\\\');
  writeFileSync(script, `<?php
namespace { define('ABSPATH', __DIR__ . '/'); error_reporting(E_ALL); ini_set('display_errors', 'stderr'); $GLOBALS['sltr_opt'] = ['white_label_defaults_v2_migrated'=>1, 'appearance_theme'=>'light']; function get_option($k,$d=[]){ if($k==='sltr_settings') return $GLOBALS['sltr_opt']; if($k==='admin_email') return 'admin@example.test'; return $d; } function get_bloginfo($k){ return 'Test Site'; } function update_option($k,$v,$a=false){ $GLOBALS['sltr_opt']=$v; return true; } function do_action(...$a){} function get_posts($a){return [];} function get_permalink($id){return '';} }
namespace Slotera\\Application\\Services { final class PerformanceProfiler { public static array $m=[]; public static function metric(string $n,float $d=1.0):void{self::$m[$n]=(self::$m[$n]??0)+$d;} } final class EmailTemplateRegistry { public static int $scenarioCalls=0; public static int $resolveCalls=0; public static function scenarios():array{self::$scenarioCalls++; return ['booking'=>['default_subject'=>'S']];} public static function resolve_runtime_value(string $a,string $b,?string $c):string{self::$resolveCalls++; return $c ?? 'resolved';} } }
namespace Slotera\\Application\\Security { final class SecretStore { public static int $decryptCalls=0; public static function sensitive_keys():array{return [];} public static function is_encrypted(string $v):bool{return true;} public static function encryption_available():bool{return true;} public static function encrypt_settings(array $s):array{return $s;} public static function decrypt_settings(array $s):array{self::$decryptCalls++; return $s;} } }
namespace { require '${repo}'; $a=new \\Slotera\\Infrastructure\\Repositories\\SettingsRepository(); $b=new \\Slotera\\Infrastructure\\Repositories\\SettingsRepository(); $a->all(); $b->all(); echo \\Slotera\\Application\\Security\\SecretStore::$decryptCalls . ',' . \\Slotera\\Application\\Services\\EmailTemplateRegistry::$scenarioCalls . ',' . \\Slotera\\Application\\Services\\EmailTemplateRegistry::$resolveCalls . "\\n"; $GLOBALS['sltr_opt']['payment_currency']='USD'; $v=$a->all(); echo \\Slotera\\Application\\Security\\SecretStore::$decryptCalls . ',' . ($v['payment_currency'] ?? '') . "\\n"; }
`);
  const run = spawnSync('php', [script], { encoding: 'utf8' });
  rmSync(dir, { recursive: true, force: true });
  assert.equal(run.status, 0, run.stderr || 'PHP fixture exited non-zero');
  assert.equal(run.stderr.trim(), '', `PHP fixture emitted warnings/errors on stderr:\n${run.stderr}`);
  const lines = run.stdout.trim().split(/\r?\n/);
  assert.equal(lines[0], '1,2,1', 'two repository instances should share one resolved settings result');
  assert.equal(lines[1], '2,USD', 'same-request option mutation must invalidate by fingerprint and re-resolve');
});
