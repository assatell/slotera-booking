import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (p) => fs.readFileSync(new URL(`../../${p}`, import.meta.url), 'utf8');

test('tooltip sizing is package-level and removed from global Appearance UI', () => {
  const appearance = read('includes/Admin/Views/settings/appearance.php');
  const identity = read('includes/Admin/Views/package-form/sections/identity.php');
  const repository = read('includes/Infrastructure/Repositories/PackageRepository.php');
  const schema = read('includes/Core/DatabaseSchemaInstaller.php');
  const booking = read('includes/Frontend/Views/booking-form.php');
  const packages = read('includes/Frontend/Views/packages-list.php');
  const migration = read('includes/Core/Migrations/Version_1_0_978.php');

  assert.equal(appearance.includes('name="tooltip_size_ratio"'), false);
  assert.equal(appearance.includes('name="tooltip_text_size"'), false);
  assert.equal(identity.includes('name="tooltip_size_ratio"'), true);
  assert.equal(identity.includes('name="tooltip_text_size"'), true);
  assert.equal(repository.includes("'tooltip_size_ratio'"), true);
  assert.equal(repository.includes("'tooltip_text_size'"), true);
  assert.equal(schema.includes('tooltip_size_ratio DECIMAL(4,2)'), true);
  assert.equal(schema.includes('tooltip_text_size INT UNSIGNED'), true);
  assert.equal(booking.includes("$package['tooltip_size_ratio']"), true);
  assert.equal(booking.includes("$package['tooltip_text_size']"), true);
  assert.equal(packages.includes("$package['tooltip_size_ratio']"), true);
  assert.equal(packages.includes("$package['tooltip_text_size']"), true);
  assert.equal(migration.includes("get_option('sltr_settings'"), true);
});
