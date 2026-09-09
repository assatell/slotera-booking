import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('Contact is the seventh and last required system page', () => {
  const view = read('includes/Admin/Views/settings/system-pages.php');
  const migration = read('includes/Core/Migrations/LegacyMigrations.php');
  const controller = read('includes/Admin/Controllers/SettingsController.php');
  const repository = read('includes/Infrastructure/Repositories/SettingsRepository.php');
  const diagnostics = read('includes/Application/Services/DiagnosticsService.php');
  const plugin = read('includes/Core/Plugin.php');

  const accountAt = view.indexOf('name\' => \'account_page_id\'');
  const contactAt = view.indexOf('name\' => \'contact_page_id\'');
  const tableEndAt = view.indexOf('</tbody>', contactAt);
  assert.ok(accountAt >= 0 && contactAt > accountAt && tableEndAt > contactAt);
  assert.match(view, /Contact page[\s\S]*?\[slotera_contact\]/);
  assert.doesNotMatch(view.slice(view.indexOf('Additional shortcodes')), /Contact form[\s\S]*?\[slotera_contact\]/);

  assert.match(migration, /'contact_page_id'\s*=>\s*\['Slotera Contact', 'slotera-contact', '\[slotera_contact\]'\]/);
  assert.match(migration, /ensure_contact_system_page/);
  assert.match(controller, /post_int\('contact_page_id'\)/);
  assert.match(repository, /'contact'\s*=>\s*'slotera_contact'/);
  assert.match(repository, /'contact_page_id'\s*=>\s*0/);
  assert.match(diagnostics, /'contact_page_id'\s*=>\s*\['Contact page', '\[slotera_contact\]', 'contact'\]/);
  assert.match(plugin, /\$settings\['contact_page_id'\]/);
  assert.match(plugin, /add_action\('init', \[\$this, 'maybe_ensure_contact_system_page'\], 5\)/);
  const migrationsAt = plugin.indexOf('public function maybe_run_migrations');
  const contactSetupAt = plugin.indexOf('public function maybe_ensure_contact_system_page');
  assert.ok(migrationsAt >= 0 && contactSetupAt > migrationsAt);
  assert.doesNotMatch(plugin.slice(migrationsAt, contactSetupAt), /ensure_contact_system_page/);
});

test('Contact page settings mirror the approved Solo contact controls', () => {
  const view = read('includes/Admin/Views/settings/system-pages.php');
  const controller = read('includes/Admin/Controllers/SettingsController.php');
  const repository = read('includes/Infrastructure/Repositories/SettingsRepository.php');
  const admin = read('includes/Admin/AdminServiceProvider.php');
  const js = read('assets/js/admin-contact-page.js');

  for (const field of ['contact_page_image_id', 'contact_page_map', 'contact_page_details_json']) {
    assert.match(view, new RegExp(`name="${field}"`));
    assert.match(controller, new RegExp(`'${field}'`));
    assert.match(repository, new RegExp(`'${field}'`));
  }
  for (const platform of ['instagram', 'facebook', 'linkedin', 'x', 'youtube', 'tiktok']) {
    assert.match(view, new RegExp(`'${platform}'`));
  }
  assert.match(repository, /sanitize_google_maps_link/);
  assert.match(repository, /sanitize_contact_details_json/);
  assert.match(admin, /wp_enqueue_media\(\)/);
  assert.match(admin, /assets\/js\/admin-contact-page\.js/);
  assert.match(js, /wp\.media/);
  assert.match(js, /serializeDetails/);
});

test('Standalone Contact uses global details while Solo pages keep package details', () => {
  const shortcode = read('includes/Frontend/Shortcodes/BookingShortcode.php');
  const contactView = read('includes/Frontend/Views/contact-page.php');
  const packageView = read('includes/Frontend/Views/package-detail.php');
  const css = read('assets/css/frontend.css');

  assert.match(shortcode, /\$GLOBALS\['sltr_current_package'\]/);
  assert.match(shortcode, /contact_page_details_json/);
  assert.match(shortcode, /contact_page_image_id/);
  assert.match(shortcode, /contact_page_map/);
  assert.match(shortcode, /includes\/Frontend\/Views\/contact-page\.php/);
  assert.match(contactView, /sltr-contact-page-block/);
  assert.match(contactView, />Google Maps<\/a>/);
  assert.match(contactView, /target="_blank" rel="noopener noreferrer"/);
  assert.match(contactView, /sltr-contact-page-block[\s\S]*?style="<\?php echo esc_attr\(\$style\); \?>"/);
  assert.match(shortcode, /--sltr-booking-form-max-width/);
  assert.match(css, /\.sltr-contact-page-block\s*\{[\s\S]*?max-width:\s*var\(--sltr-booking-form-max-width, 1280px\) !important/);
  assert.doesNotMatch(css, /--sltr-booking-width/);
  assert.match(packageView, /solo_contact_details_json/);
  assert.match(packageView, /solo_contact_image_id/);
  assert.match(packageView, /solo_contact_map/);
});
