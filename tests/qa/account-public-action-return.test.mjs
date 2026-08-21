import fs from 'node:fs';
import assert from 'node:assert/strict';

const account = fs.readFileSync(new URL('../../includes/Frontend/Views/account.php', import.meta.url), 'utf8');
const controller = fs.readFileSync(new URL('../../includes/Frontend/Controllers/BookingController.php', import.meta.url), 'utf8');
const reschedule = fs.readFileSync(new URL('../../includes/Frontend/Views/public-token/reschedule-form.php', import.meta.url), 'utf8');
const cancel = fs.readFileSync(new URL('../../includes/Frontend/Views/public-token/cancel-confirmation.php', import.meta.url), 'utf8');

assert.match(account, /add_query_arg\('sltr_return', 'account', \$cancel_url\)/, 'account cancel link must carry account return context');
assert.match(account, /add_query_arg\('sltr_return', 'account', \$reschedule_url\)/, 'account reschedule link must carry account return context');
assert.match(account, /cancellation_url\(\$booking\)/, 'account cancellation must open the dedicated cancellation page');
assert.match(controller, /public_action_return_url\(\)/, 'public action pages must resolve contextual return URLs');
assert.match(controller, /public_action_context_url/, 'action URLs must preserve account context');
assert.match(reschedule, /name="sltr_return" value="account"/, 'reschedule forms must preserve account context');
assert.match(cancel, /name="sltr_return" value="account"/, 'cancel form must preserve account context');

console.log('account public action return context: ok');
