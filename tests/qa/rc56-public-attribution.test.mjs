import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('RC57 public Slotera attribution is centered and vendor suppression works independently', () => {
  const view = read('includes/Frontend/Views/booking-form.php');
  const service = read('includes/Application/Services/WhiteLabelService.php');
  const css = read('assets/css/frontend.css');
  const admin = read('includes/Admin/Views/white-label.php');

  assert.match(view, /public_attribution_visible\(\)/);
  assert.match(view, /Powered by <strong>Slotera<\/strong>/);
  assert.match(view, /target="_blank" rel="noopener noreferrer"/);
  assert.match(service, /public function public_attribution_visible\(\): bool/);
  assert.match(service, /return !\$this->hide_vendor_branding\(\);/);
  assert.match(service, /apply_filters\('sltr_platform_url', \$default\)/);
  assert.match(service, /https:\/\/slotera\.app\//);
  assert.match(css, /\.sltr-platform-attribution \{[^}]*text-align:center;[^}]*font-size:12px;/);
  assert.match(service, /public function hide_vendor_branding\(\): bool[\s\S]*?return \(int\) \(\$this->settings\(\)\['white_label_hide_vendor_branding'\] \?\? 0\) === 1;/);
  assert.doesNotMatch(service, /hide_vendor_branding\(\): bool[\s\S]{0,180}\$this->enabled\(\)/);
  assert.match(admin, /public booking-form attribution/);
  assert.match(admin, /works independently of custom White Label branding/);
});
