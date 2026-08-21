import fs from 'node:fs';
import assert from 'node:assert/strict';

const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');
const notification = read('includes/Application/Services/EmailNotificationService.php');
const reminder = read('includes/Application/Services/EmailReminderService.php');

for (const source of [notification, reminder]) {
  assert.match(source, /booking_created_customer/);
  assert.match(source, /booking_created_admin/);
}

assert.match(reminder, /apply_email_display_preferences\(\$text, \$preferences, \$recipient_type, \$scenario\)/);
assert.match(reminder, /in_array\(\$scenario, \['booking_created_customer', 'booking_created_admin'\], true\)/);
assert.match(reminder, /email_preheader\(\$booking_data, \$package, \$recipient_type, \$body, \$scenario\)/);

assert.match(notification, /email_preheader\(\$booking, \$package, \$recipient_type, \$message, \$scenario\)/);
assert.match(notification, /in_array\(\$scenario, \['booking_created_customer', 'booking_created_admin'\], true\)/);

console.log('Booking Request no-date/time contact notice is limited to initial created emails.');
