import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { spawnSync } from 'node:child_process';

const servicePath = 'includes/Application/Services/PromotionCampaignService.php';
const service = fs.readFileSync(servicePath, 'utf8');

test('RC66.12.2 percent-encodes Unicode promotion image URLs for email HTML', () => {
  assert.match(service, /private function email_safe_image_url\(string \$url\): string/);
  assert.ok(service.includes("preg_replace_callback('/[^\\x00-\\x7F]/u'"));
  assert.match(service, /rawurlencode\(\$match\[0\]\)/);
  assert.match(service, /email_safe_image_url\(\(string\) \$offer\['image_url'\]\)/);
  assert.match(service, /email_safe_image_url\(\$fallback\)/);
});

test('RC66.12.2 email URL helper converts Cyrillic filename without changing ASCII URL structure', (t) => {
  const php = process.env.PHP || 'php';
  const code = `
    define('ABSPATH', __DIR__ . '/');
    require ${JSON.stringify(process.cwd() + '/' + servicePath)};
    $r = new ReflectionClass('Slotera\\Application\\Services\\PromotionCampaignService');
    $o = $r->newInstanceWithoutConstructor();
    $m = $r->getMethod('email_safe_image_url');
    $m->setAccessible(true);
    echo $m->invoke($o, 'https://test2.partytime.ee/wp-content/uploads/2026/08/батут2.png');
  `;
  const run = spawnSync(php, ['-r', code], { encoding: 'utf8' });
  if (run.error?.code === 'ENOENT') {
    t.skip('PHP executable not available in this Node-only environment');
    return;
  }
  assert.equal(run.status, 0, run.stderr || run.stdout);
  assert.equal(run.stdout.trim(), 'https://test2.partytime.ee/wp-content/uploads/2026/08/%D0%B1%D0%B0%D1%82%D1%83%D1%822.png');
});

test('RC66.12.2 renders active promotion conditions on separate email lines', () => {
  assert.match(service, /'meta_lines' => array_values\(array_unique\(\$meta_lines\)\)/);
  assert.match(service, /return implode\('<br>', array_map\('esc_html', \$this->offer_meta_lines\(\$offer\)\)\);/);
  assert.match(service, /\$meta_lines\[\] = \$weekend_line;/);
  assert.match(service, /\$meta_lines\[\] = \$season_line;/);
});
