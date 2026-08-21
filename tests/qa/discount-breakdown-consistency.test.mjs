import fs from 'node:fs';
import assert from 'node:assert/strict';

const bookingView = fs.readFileSync(new URL('../../includes/Frontend/Views/booking-form.php', import.meta.url), 'utf8');
const bookingJs = fs.readFileSync(new URL('../../assets/js/frontend-booking-form.js', import.meta.url), 'utf8');
const checkout = fs.readFileSync(new URL('../../includes/Frontend/Views/checkout.php', import.meta.url), 'utf8');
const emailReminder = fs.readFileSync(new URL('../../includes/Application/Services/EmailReminderService.php', import.meta.url), 'utf8');
const repo = fs.readFileSync(new URL('../../includes/Infrastructure/Repositories/BookingRepository.php', import.meta.url), 'utf8');
const registry = fs.readFileSync(new URL('../../includes/Core/Migrations/MigrationRegistry.php', import.meta.url), 'utf8');

assert.match(bookingView, /data-package-discount-label=/, 'booking cards must expose the package discount to the summary');
assert.match(bookingView, /Discounts/, 'booking summary must use a discounts breakdown label');
assert.match(bookingJs, /discountBreakdown = \[packageDiscountLabel, dynamicLabel\]/, 'booking summary must combine package and dynamic discounts');
assert.match(checkout, /pricing_adjustment_amount/, 'checkout must show persisted dynamic pricing adjustment');
assert.match(emailReminder, /pricing_adjustment_amount/, 'email price summary must show persisted dynamic pricing adjustment');
assert.match(emailReminder, /pricing_adjustment_label/, 'email price summary must use the persisted dynamic offer label');
assert.match(repo, /pricing_adjustment_label/, 'booking repository must persist the dynamic pricing label');
assert.match(registry, /1\.0\.1001.*Version_1_0_1001/, '1.0.1029 database migration must be registered');

console.log('discount breakdown consistency: ok');
