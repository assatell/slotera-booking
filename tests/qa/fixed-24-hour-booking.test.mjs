import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const fixed = read('includes/Admin/Views/package-form/booking-blocks/fixed.php');
const manager = read('includes/Application/BookingModeConfiguration/BookingModeConfigurationManager.php');
const controller = read('includes/Admin/Controllers/PackageController.php');
const availability = read('includes/Domain/Availability/AvailabilityService.php');
const handler = read('includes/Application/Services/BookingModes/FixedBookingModeHandler.php');
const repo = read('includes/Infrastructure/Repositories/PackageRepository.php');
const bookingRepo = read('includes/Infrastructure/Repositories/BookingRepository.php');
const form = read('includes/Frontend/Views/booking-form.php');
const js = read('assets/js/frontend-booking-form.js');
const bookingController = read('includes/Frontend/Controllers/BookingController.php');

assert.match(fixed, /24-hour \/ multi-day booking/);
assert.match(fixed, /mode_config\[fixed\]\[full_day_booking\]/);
assert.match(fixed, /Each additional 24-hour day adds the package price/);
assert.match(fixed, /type="hidden" name="mode_config\[fixed\]\[full_day_booking\]" value="0"/);

assert.match(manager, /\$duration = 1440/);
assert.match(manager, /\$full_day_booking \? 60 : \$slot_step/);
assert.match(controller, /\$fixed_full_day_booking \? 1440/);
assert.match(controller, /\$fixed_full_day_booking \? 60/);
assert.match(repo, /'full_day_booking' => \$mode === 'fixed'/);

assert.match(availability, /fixed_full_day_enabled/);
assert.match(availability, /timed_range_is_available/);
assert.match(bookingRepo, /get_for_time_range/);

assert.match(handler, /\$requested_end_date/);
assert.match(handler, /\$full_day_days/);
assert.match(handler, /package\['price'\].*\$full_day_days/);
assert.match(handler, /acquire_date_range_inventory/);
assert.match(handler, /release_date_range_inventory/);
assert.match(handler, /'end_date' => \$full_day_booking \? \$full_day_end_date/);

assert.match(form, /data-full-day-booking/);
assert.match(form, /sltr-fixed-end-date/);
assert.match(js, /fullDayBooking/);
assert.match(js, /fixedFullDayDays/);
assert.match(js, /prepareFixedFullDayEndDate/);
assert.match(js, /selectedSlot = \{ start: '00:00:00', end: '00:00:00' \}/);
assert.match(js, /Select end date/);
assert.match(js, /sltr-fixed-full-day-continue/);
assert.match(js, /booking_days/);
assert.match(bookingController, /\$booking_days/);

console.log('Fixed supports start time plus multi-day end date and per-day pricing.');

assert.doesNotMatch(js, /selectedPackageMeta\.fullDayBooking[\s\S]{0,120}loadSlots\(selectedDate\)/);
assert.match(availability, /return \$this->timed_range_is_available/);
assert.match(handler, /\$start = '00:00:00'/);
assert.match(handler, /\$end = '00:00:00'/);
