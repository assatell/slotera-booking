import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const form = read('includes/Admin/Views/marketing-form.php');
const fields = read('includes/Admin/Views/marketing-form/campaign-fields.php');
const header = read('includes/Admin/Views/marketing-form/header.php');
const coupons = read('includes/Admin/Views/coupons-list.php');
const page = read('includes/Admin/Pages/CouponsPage.php');
const controller = read('includes/Admin/Controllers/MarketingController.php');

assert.doesNotMatch(form, /queue-settings\.php/);
assert.doesNotMatch(form, /preview-test\.php/);
assert.doesNotMatch(form, /queue-controls-log\.php/);
assert.doesNotMatch(fields, /Queue progress/);
assert.doesNotMatch(fields, /Campaign status/);
assert.match(fields, /Create campaign/);
assert.match(header, /Back to Coupons/);

assert.match(page, /\$coupon_campaigns = \[\]/);
assert.match(coupons, /esc_html_e\('View'/);
assert.match(coupons, /esc_html_e\('Send now'/);
assert.match(coupons, /sltr_delete_marketing_campaign/);
assert.match(coupons, /return_coupons/);
assert.match(coupons, /Create campaign/);

assert.match(controller, /campaign_saved=1/);
assert.match(controller, /campaign_queued=1/);
assert.match(controller, /campaign_deleted=1/);

console.log('simplified coupon campaign create/view/send flow: ok');
