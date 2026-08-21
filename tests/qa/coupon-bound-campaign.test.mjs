import fs from 'node:fs';
import assert from 'node:assert/strict';

const read = (rel) => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const page = read('includes/Admin/Pages/CouponsPage.php');
const form = read('includes/Admin/Views/marketing-form/campaign-fields.php');
const controller = read('includes/Admin/Controllers/MarketingController.php');
const couponController = read('includes/Admin/Controllers/CouponController.php');

assert.match(couponController, /action=new&coupon_id=/);

assert.match(page, /campaign_requires_coupon=1/);
assert.match(page, /campaign_coupon_missing=1/);
assert.match(page, /'source' => 'coupon_bound'/);
assert.match(page, /\$campaign\['source'\] = 'coupon_bound'/);
assert.match(page, /count\(\$sltr_bound_coupon_package_ids\) === 1/);

assert.match(form, /name="campaign_source"/);
assert.match(form, /name="package_id"/);
assert.match(form, /name="coupon_id"/);
assert.match(form, /Inherited from this coupon and cannot be changed in the campaign/);
assert.match(form, /Promotion cannot be changed here/);
assert.doesNotMatch(form, /id="package_id"/);
assert.doesNotMatch(form, /id="coupon_id"/);
assert.doesNotMatch(form, /No promotion \(information campaign\)/);

assert.match(controller, /\$campaign_source = 'coupon_bound'/);
assert.match(controller, /\$bound_coupon_id = \$current \? absint\(\$current\['coupon_id'\]/);
assert.match(controller, /\(new CouponRepository\(\)\)->get_by_id/);
assert.match(controller, /\$posted_coupon_id = \$bound_coupon_id/);
assert.match(controller, /count\(\$bound_package_ids\) === 1 \? \(int\) \$bound_package_ids\[0\] : 0/);
assert.match(controller, /'source' => \$campaign_source/);

console.log('strict coupon -> campaign binding: ok');
