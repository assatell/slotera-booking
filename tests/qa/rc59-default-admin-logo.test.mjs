import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('RC59 ships a visible default Slotera admin logo with portable White Label replacement', () => {
  const service = read('includes/Application/Services/WhiteLabelService.php');
  const view = read('includes/Admin/Views/white-label.php');
  const controller = read('includes/Admin/Controllers/WhiteLabelController.php');
  const logo = read('assets/images/slotera-admin-logo.svg');
  assert.match(service, /default_admin_logo_url\(\)/);
  assert.match(service, /assets\/images\/slotera-admin-logo\.svg/);
  assert.match(service, /if \(\$this->enabled\(\) && \$custom !== ''\) \{ return \$custom; \}/);
  assert.match(service, /if \(\$this->hide_vendor_branding\(\)\) \{ return ''; \}/);
  assert.match(service, /add_action\('admin_footer', \[\$this, 'print_admin_branding_script'\], 5\)/);
  assert.match(service, /public function print_admin_branding_script\(\): void/);
  assert.match(service, /\.sltr-page-header__title, \.sltr-admin-wrap h1, #wpbody-content \.wrap h1, #wpbody-content h1/);
  assert.match(service, /title\.insertBefore\(badge, title\.firstChild\)/);
  assert.match(service, /width:142px;height:34px/);
  assert.match(view, /The built-in Slotera logo is used by default/);
  assert.match(controller, /if \(\$admin_logo_url === \$default_logo_url\) \{ \$admin_logo_url = ''; \}/);
  assert.match(logo, />Slotera<\/text>/);
});
