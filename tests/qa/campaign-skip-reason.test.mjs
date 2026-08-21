import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const service = read('includes/Application/Services/MarketingEmailService.php');
const controller = read('includes/Admin/Controllers/MarketingController.php');
const list = read('includes/Admin/Views/coupons-list.php');

assert.match(service, /skipped_already_queued/);
assert.match(service, /skipped_unsubscribed/);
assert.match(service, /skipped_invalid_email/);
assert.match(service, /skipped_invalid_recipient/);
assert.match(service, /\$already_queued[\s\S]*\$result\['skipped_already_queued'\]\+\+/);
assert.match(controller, /skipped_already_queued=/);
assert.match(controller, /skipped_unsubscribed=/);
assert.match(list, /Campaign not sent/);
assert.match(list, /already queued\/sent/);
assert.match(list, /unsubscribed from marketing emails/);
assert.match(list, /invalid or missing email/);

console.log('campaign skipped-recipient reasons are surfaced on Coupons: ok');
