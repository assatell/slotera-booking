import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const repo = read('includes/Infrastructure/Repositories/CouponRepository.php');
const service = read('includes/Application/Services/MarketingEmailService.php');

assert.match(service, /\[slotera:generated_personal_coupon\]/);
assert.match(repo, /\[slotera:generated_personal_coupon\]/);
assert.match(repo, /Legacy generated coupons can outlive a deleted campaign\/log/);
assert.match(repo, /usage_limit.*=== 1/);
assert.match(repo, /usage_limit_per_email.*=== 1/);
assert.match(repo, /preg_match\('\/\^\.\+-\[A-Z0-9\]\{6,8\}\$\/'/);
assert.match(repo, /legacy_recipient/);
assert.match(repo, /if \(\$legacy_one_use && \$legacy_suffix && \$legacy_recipient\) \{ return false; \}/);

console.log('generated personal coupon hiding survives campaign/log deletion: ok');
