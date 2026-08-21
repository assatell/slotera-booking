import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const mail = read('includes/Application/Services/MarketingEmailService.php');
const controller = read('includes/Admin/Controllers/MarketingController.php');
const settings = read('includes/Infrastructure/Repositories/SettingsRepository.php');

assert.match(mail, /automation_offer/);
assert.match(mail, /PREVIEW-OFFER/);
assert.match(controller, /A7F3K9Q2-X8M4P6RT/);
assert.doesNotMatch(controller, /THANKYOU-PREVIEW|COMEBACK-PREVIEW/);
assert.match(controller, /automation_offer_template/);
assert.match(settings, /comeback_automation_offer_discount_type/);
assert.match(settings, /after_booking_automation_offer_discount_type/);
assert.match(settings, /offer_valid_days/);
assert.match(settings, /offer_package_ids/);

console.log('automation Offer is self-contained and previewable: ok');
