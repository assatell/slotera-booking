import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const migrations = fs.readFileSync(new URL('../../includes/Core/Migrations/LegacyMigrations.php', import.meta.url), 'utf8');
const diagnostics = fs.readFileSync(new URL('../../includes/Application/Services/DiagnosticsService.php', import.meta.url), 'utf8');
const settingsView = fs.readFileSync(new URL('../../includes/Admin/Views/settings/system-pages.php', import.meta.url), 'utf8');
const settingsController = fs.readFileSync(new URL('../../includes/Admin/Controllers/SettingsController.php', import.meta.url), 'utf8');
const frontend = fs.readFileSync(new URL('../../includes/Frontend/Shortcodes/BookingShortcode.php', import.meta.url), 'utf8');

test('Packages frontend page is legacy-only and is not exposed as a system page', () => {
  const ensureStart = migrations.indexOf('public static function ensure_required_shortcode_pages');
  assert.notEqual(ensureStart, -1, 'required shortcode page migration must exist');
  const privateStart = migrations.indexOf('    private static function', ensureStart);
  const ensureBody = migrations.slice(ensureStart, privateStart > -1 ? privateStart : migrations.length);

  assert.doesNotMatch(ensureBody, /'packages_page_id'\s*=>\s*\['Slotera Packages'/, 'installer must not auto-create Slotera Packages');
  assert.doesNotMatch(diagnostics, /'packages_page_id'\s*=>\s*\['Packages page'/, 'diagnostics must not require a separate Packages page');
  assert.doesNotMatch(settingsView, /packages_page_id|Packages page \(optional\)|\[slotera_packages\]/, 'Packages page binding must not be exposed in System pages UI');
  assert.doesNotMatch(settingsController, /post_int\('packages_page_id'\)/, 'System pages save must not accept a Packages page binding');
  assert.match(frontend, /add_shortcode\('slotera_packages'/, 'legacy shortcode must remain registered for backward compatibility');
});
