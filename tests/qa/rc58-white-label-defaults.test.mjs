import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../..');
const read = (p) => fs.readFileSync(path.join(root, p), 'utf8');
const desc = 'Complete WordPress booking platform with payments, packages, coupons, marketing automation, analytics and customer management.';

test('RC58 White Label ships visible Slotera defaults and preserves custom values', () => {
  const repo = read('includes/Infrastructure/Repositories/SettingsRepository.php');
  const view = read('includes/Admin/Views/white-label.php');
  const service = read('includes/Application/Services/WhiteLabelService.php');
  const plugin = read('slotera-booking.php');
  assert.match(repo, /'white_label_admin_footer_text'\s*=>\s*'Powered by Slotera'/);
  assert.ok(repo.includes(`'white_label_plugin_description' => '${desc}'`));
  assert.match(repo, /white_label_defaults_v2_migrated/);
  assert.match(repo, /trim\(\(string\) \$settings\['white_label_admin_footer_text'\]\) === ''/);
  assert.ok(view.includes('Powered by Slotera'));
  assert.ok(view.includes(desc));
  assert.ok(plugin.includes(`Description: ${desc}`));
  assert.match(service, /if \(\$this->hide_vendor_branding\(\)\) \{ return ''; \}/);
  assert.doesNotMatch(service, /!\$this->enabled\(\) \|\| !\$this->hide_vendor_branding\(\)/);
});
