import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const systemPages = read('includes/Admin/Views/settings/system-pages.php');
const settingsController = read('includes/Admin/Controllers/SettingsController.php');
const legacy = read('includes/Core/Migrations/LegacyMigrations.php');
const fresh = read('includes/Core/Migrations/FreshInstallSetup.php');
const migration = read('includes/Core/Migrations/Version_1_0_1038.php');
const registry = read('includes/Core/Migrations/MigrationRegistry.php');
const layout = read('includes/Admin/Views/settings/package-layout.php');
const solo = read('includes/Admin/Views/package-form/sections/solo-content.php');
const repo = read('includes/Infrastructure/Repositories/PackageRepository.php');
const frontend = read('includes/Frontend/Shortcodes/BookingShortcode.php');
const settingsRepo = read('includes/Infrastructure/Repositories/SettingsRepository.php');
const bookingForm = read('includes/Frontend/Views/booking-form.php');

assert.doesNotMatch(systemPages, /Create \/ bind missing pages/);
assert.match(systemPages, /creates the required booking pages automatically during installation/);
assert.doesNotMatch(systemPages, /Packages page \(optional\)|packages_page_id|\[slotera_packages\]/);
assert.doesNotMatch(settingsController, /post_int\('packages_page_id'\)/);
assert.match(systemPages, /place the corresponding Slotera shortcode/);
assert.doesNotMatch(settingsController, /sltr_create_required_pages/);

for (const code of [
  'slotera_booking','slotera_categories','slotera_thank_you',
  'slotera_checkout','slotera_login','slotera_account'
]) {
  assert.match(legacy, new RegExp(`\\[${code}\\]`));
}
assert.doesNotMatch(legacy, /'packages_page_id'\s*=>\s*\['Slotera Packages'/);
assert.match(frontend, /add_shortcode\('slotera_packages'/);
assert.match(fresh, /migrate_to_10141/);
assert.match(migration, /ensure_required_shortcode_pages/);
assert.match(registry, /'1\.0\.1038' => Version_1_0_1038::class/);

assert.doesNotMatch(layout, /Slider and gallery image display/);
assert.match(solo, /Slider display/);
assert.match(solo, /name="media_fit_mode"/);
assert.match(repo, /'media_fit_mode'/);
assert.match(frontend, /\$package\['media_fit_mode'\]/);

assert.match(settingsRepo, /'select_time_layout' => 'grid'/);
assert.match(bookingForm, /\$select_time_layout = 'grid'/);
assert.match(layout, /select_time_layout[\s\S]*\?\? 'grid'/);

console.log('System pages, package Slider display and Grid time-layout defaults are wired correctly.');
