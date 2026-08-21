import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '../..');
const plugin = resolve(root, 'includes/Core/Plugin.php').replaceAll('\\', '\\\\').replaceAll("'", "\\'");

function probe(action, script = '/wp-admin/admin-post.php') {
  const actionLiteral = JSON.stringify(action);
  const scriptLiteral = JSON.stringify(script);
  const php = `
    define('ABSPATH', __DIR__);
    $_SERVER['SCRIPT_NAME'] = ${scriptLiteral};
    $_REQUEST['action'] = ${actionLiteral};
    $GLOBALS['pagenow'] = 'admin-post.php';
    function is_admin(){ return true; }
    function wp_doing_ajax(){ return false; }
    function wp_doing_cron(){ return false; }
    function wp_unslash($value){ return $value; }
    function sanitize_key($key){ return strtolower(preg_replace('/[^a-z0-9_\\-]/', '', (string) $key)); }
    require '${plugin}';
    $p = new \\Slotera\\Core\\Plugin();
    $m = new ReflectionMethod($p, 'current_frontend_admin_post_action');
    $m->setAccessible(true);
    echo $m->invoke($p);
  `;
  return execFileSync('php', ['-r', php], { encoding: 'utf8' }).trim();
}

test('RC65.1.2 recognizes all frontend admin-post actions needed by account/contact flows', () => {
  for (const action of [
    'sltr_request_magic_link',
    'sltr_consume_magic_link',
    'sltr_account_logout',
    'sltr_account_cancel_booking',
    'sltr_account_reschedule_booking',
    'sltr_account_invoice_pdf',
    'sltr_contact_form_submit',
  ]) {
    assert.equal(probe(action), action, action);
  }
});

test('RC65.1.2 does not classify unrelated admin-post actions as frontend actions', () => {
  assert.equal(probe('sltr_save_package'), '');
  assert.equal(probe('evil<script>'), '');
});
