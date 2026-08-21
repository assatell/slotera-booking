import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
const read = (p) => fs.readFileSync(p, 'utf8');

test('Global working hours is second and closures third in General Settings', () => {
  const view = read('includes/Admin/Views/settings-working-hours.php');
  const availability = view.indexOf("settings/booking-availability.php");
  const hours = view.indexOf("settings/working-hours.php");
  const closures = view.indexOf("settings/closures.php");
  const basics = view.indexOf("settings/booking-basics.php");
  assert.ok(availability >= 0 && availability < hours && hours < closures && closures < basics);
});

test('Closures override every availability mode', () => {
  const availability = read('includes/Domain/Availability/AvailabilityService.php');
  assert.match(availability, /if \(\$this->is_closed_on_date\(\$date\)\) \{\s*return \[\];/);
  assert.match(availability, /range_overlaps_closure\(\$candidate_start, \$candidate_end\)/);
  assert.match(availability, /business_closures/);
});

test('Closures have an authenticated settings action and bounded storage', () => {
  const controller = read('includes/Admin/Controllers/SettingsController.php');
  const repo = read('includes/Infrastructure/Repositories/SettingsRepository.php');
  assert.match(controller, /admin_post_sltr_save_closures/);
  assert.match(controller, /verify\('sltr_save_closures'\)/);
  assert.match(controller, /array_slice\(\$closures, 0, 200\)/);
  assert.match(repo, /'business_closures' => \[\]/);
});
