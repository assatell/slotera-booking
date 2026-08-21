import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '../..');
const repo = resolve(root, 'includes/Infrastructure/Repositories/SettingsRepository.php').replaceAll('\\', '\\\\').replaceAll("'", "\\'");

function probe(content, key) {
  const encoded = Buffer.from(content).toString('base64');
  const php = `
    define('ABSPATH', __DIR__);
    function get_post($id) { return (object) ['post_type'=>'page','post_status'=>'publish','post_content'=>base64_decode('${encoded}')]; }
    require '${repo}';
    $r = new \\Slotera\\Infrastructure\\Repositories\\SettingsRepository();
    echo $r->is_published_page_for_key(12, '${key}') ? '1' : '0';
  `;
  return execFileSync('php', ['-r', php], { encoding: 'utf8' }).trim() === '1';
}

test('RC65.1.1 detects system-page shortcode markup without add_shortcode/has_shortcode runtime', () => {
  assert.equal(probe('[slotera_account]', 'account'), true);
  assert.equal(probe('[slotera_checkout]', 'checkout'), true);
  assert.equal(probe('[slotera_booking]', 'booking'), true);
  assert.equal(probe('[slotera_booking package_id="9"]', 'booking'), false);
  assert.equal(probe('plain page', 'account'), false);
});
