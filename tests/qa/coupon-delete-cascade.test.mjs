import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');
const controller = read('includes/Admin/Controllers/CouponController.php');
const list = read('includes/Admin/Views/coupons-list.php');

assert.doesNotMatch(controller, /MarketingCampaignRepository/);
assert.doesNotMatch(controller, /MarketingLogRepository/);
assert.match(controller, /\$this->repo->delete\(\$id\)/);

assert.match(list, /sltr_delete_marketing_campaign/);
assert.match(list, /Delete campaign/);
assert.match(list, /coupon will remain active until its own expiry or deactivation/);
assert.match(list, /Delete coupon/);

console.log('coupon and campaign deletion semantics are safely separated: ok');
