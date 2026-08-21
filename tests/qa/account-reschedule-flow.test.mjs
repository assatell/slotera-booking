import fs from 'node:fs';
import assert from 'node:assert/strict';

const view = fs.readFileSync(new URL('../../includes/Frontend/Views/account.php', import.meta.url), 'utf8');
const controller = fs.readFileSync(new URL('../../includes/Frontend/Controllers/AccountController.php', import.meta.url), 'utf8');

assert.match(view, /reschedule_url\(\$booking\)/, 'normal account reschedule must reuse the secure public reschedule flow');
assert.match(view, /\$is_date_range_inventory/, 'account view must distinguish date-range inventory bookings');
assert.match(view, /elseif \(\$reschedule_url !== ''\)/, 'normal bookings must render a reschedule link instead of manual time inputs');
assert.match(controller, /\$booking_mode !== 'date_range_inventory'/, 'manual account reschedule POST must be rejected for non-date-range bookings');

console.log('account reschedule flow: ok');
