import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { resolvePhpExecutable } from '../../tools/php-runtime.mjs';

const root = resolve(import.meta.dirname, '../..');
const servicePath = resolve(root, 'includes/Application/Services/AccountMagicLinkService.php');
const service = readFileSync(servicePath, 'utf8');
const phpExecutable = resolvePhpExecutable();

test('RC65.1.3 account page detection no longer depends on has_shortcode runtime registration', () => {
  assert.doesNotMatch(service, /if \(!has_shortcode\(\(string\) \$post->post_content, \$shortcode\)\)/);
  assert.match(service, /post_contains_shortcode/);
});

test('RC65.1.3 configured account page permalink resolves without registered shortcodes', () => {
  const svc = servicePath.replaceAll('\\', '\\\\').replaceAll("'", "\\'");
  const php = `
    define('ABSPATH', __DIR__);
    define('DAY_IN_SECONDS', 86400); define('MINUTE_IN_SECONDS', 60); define('HOUR_IN_SECONDS', 3600);
    function get_post($id) { return (int)$id === 12 ? (object)['post_type'=>'page','post_status'=>'publish','post_content'=>'[slotera_account]'] : null; }
    function get_permalink($id){ return (int)$id === 12 ? 'https://example.test/client-account/' : ''; }
    require '${svc}';
    $r = new ReflectionClass('Slotera\\Application\\Services\\AccountMagicLinkService');
    $s = $r->newInstanceWithoutConstructor();
    $m = $r->getMethod('permalink_for_page_with_shortcode');
    $m->setAccessible(true);
    echo $m->invoke($s, 12, 'slotera_account');
  `;
  const out = execFileSync(phpExecutable, ['-r', php], { encoding: 'utf8' }).trim();
  assert.equal(out, 'https://example.test/client-account/');
});
