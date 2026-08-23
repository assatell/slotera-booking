import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const list = read('includes/Admin/Views/marketing-list.php');
const editor = read('includes/Admin/Views/marketing-automation-editor.php');
const controller = read('includes/Admin/Controllers/MarketingController.php');
const service = read('includes/Application/Services/MarketingAutomationService.php');
const history = read('includes/Admin/Views/campaign-history-table.php');
const page = read('includes/Admin/Pages/MarketingPage.php');
const coupons = read('includes/Infrastructure/Repositories/CouponRepository.php');

assert.match(list, /Automation campaigns/);
assert.match(list, /marketing-automation-editor\.php/);
assert.doesNotMatch(list, /Campaign queue controls/);
assert.equal(fs.existsSync(new URL('../../includes/Admin/Views/marketing-automation-workflow.php', import.meta.url)), false);

assert.match(editor, /Offer/);
assert.match(editor, /offer_discount_value/);
assert.match(editor, /offer_valid_days/);
assert.match(editor, /offer_package_ids/);
assert.match(editor, /One time per recipient/);
assert.match(editor, /Marketing queue settings/);
assert.match(editor, /Preview and test/);
assert.match(editor, /Run now/);
assert.doesNotMatch(editor, /Enabled/);
assert.match(editor, /'form' => 'sltr-automation-settings-form'/);
assert.doesNotMatch(editor, /automation_coupon_id/);
assert.doesNotMatch(editor, /Generate unique coupon per recipient/);

assert.match(service, /create_offer_template/);
assert.match(service, /generate_unique_coupons' => 1/);
assert.match(service, /\[slotera:automation_offer_template\]/);
assert.match(coupons, /\[slotera:automation_offer_template\]/);

assert.match(controller, /automation_offer_settings_from_post/);
assert.match(controller, /automation_queue_settings_from_post/);
assert.match(controller, /sltr_stop_marketing_automation/);
assert.match(controller, /sltr_run_marketing_automation/);
assert.match(controller, /sltr_marketing_tab=come-back&automation_saved=1/);
assert.match(controller, /sltr_marketing_tab=after-booking&after_booking_automation_saved=1/);
assert.doesNotMatch(controller, /run_comeback_automation_now|run_after_booking_automation_now/);

assert.match(page, /latest_by_type/);
assert.match(history, /Next run/);
assert.match(history, /sltr_stop_marketing_automation/);
assert.match(history, /sltr_run_marketing_automation/);
assert.match(history, /esc_html_e\('Stop'/);
assert.match(history, /esc_html_e\('Run'/);

console.log('unified automation offer/config/preview/run/history flow: ok');
