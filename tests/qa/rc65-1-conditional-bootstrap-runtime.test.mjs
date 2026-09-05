import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';
import { resolvePhpExecutable } from '../../tools/php-runtime.mjs';

const root = resolve(import.meta.dirname, '../..');
const plugin = resolve(root, 'includes/Core/Plugin.php').replaceAll('\\', '\\\\').replaceAll("'", "\\'");
const phpExecutable = resolvePhpExecutable();

function probe(admin, ajax, cron) {
  const php = `<?php
  define('ABSPATH', __DIR__);
  function is_admin(){ return ${admin ? 'true' : 'false'}; }
  function wp_doing_ajax(){ return ${ajax ? 'true' : 'false'}; }
  function wp_doing_cron(){ return ${cron ? 'true' : 'false'}; }
  require '${plugin}';
  $p = new \\Slotera\\Core\\Plugin();
  $a = new ReflectionMethod($p, 'should_register_admin_components');
  $a->setAccessible(true);
  $f = new ReflectionMethod($p, 'should_register_frontend_components');
  $f->setAccessible(true);
  echo json_encode(['admin'=>$a->invoke($p),'frontend'=>$f->invoke($p)]);
  `;
  return JSON.parse(execFileSync(phpExecutable, ['-r', php.replace(/^<\?php\s*/, '')], { encoding: 'utf8' }));
}

test('RC65.1 runtime context gates preserve frontend, AJAX and admin needs', () => {
  assert.deepEqual(probe(false, false, false), { admin: false, frontend: true });
  assert.deepEqual(probe(true, false, false), { admin: true, frontend: false });
  assert.deepEqual(probe(true, true, false), { admin: true, frontend: true });
  assert.deepEqual(probe(false, false, true), { admin: false, frontend: false });
});
