import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('RC55+ keeps Coupons under Marketing Emails; RC66.12.5 removes deferred-license PRO menu badges', () => {
  const menu = read('includes/Admin/AdminServiceProvider.php');
  const marketing = read('includes/Admin/Pages/MarketingPage.php');
  const shell = read('includes/Admin/Views/marketing-shell-tabs.php');
  const adminCss = read('assets/css/admin.css');

  assert.match(menu, /add_submenu_page\(null, 'Coupons'/);
  assert.match(menu, /'Marketing Emails', 'Marketing Emails'/);
  assert.match(menu, /'Payments', 'Payments'/);
  assert.doesNotMatch(menu, /⭐/);
  assert.match(marketing, /\$section === ''\) \{ \$section = 'coupons'; \}/);
  assert.match(marketing, /new CouponsPage\(\)\)->render\(true\)/);
  assert.match(shell, /esc_html_e\('Coupons', 'slotera-booking'\)/);
  assert.match(shell, /esc_html_e\('Marketing automation', 'slotera-booking'\)/);
  assert.doesNotMatch(adminCss, /\.sltr-menu-pro-badge/);
});

test('RC55 White Label applies admin-wide brand text and logo without renaming technical identifiers', () => {
  const service = read('includes/Application/Services/WhiteLabelService.php');
  const view = read('includes/Admin/Views/white-label.php');

  assert.match(service, /add_filter\('gettext', \[\$this, 'filter_admin_brand_text'\]/);
  assert.match(service, /str_replace\('Slotera Booking', \$product, \$translation\)/);
  assert.match(service, /str_replace\('Slotera', \$brand, \$translation\)/);
  assert.match(service, /print_admin_branding_script/);
  assert.match(service, /title\.insertBefore\(badge, title\.firstChild\)/);
  assert.match(view, /The built-in Slotera logo is used by default/);
  assert.doesNotMatch(service, /SLTR_PLUGIN_BASENAME\s*=/);
});
