import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const editor = read('includes/Admin/Views/marketing-automation-editor.php');
const service = read('includes/Application/Services/MarketingAutomationService.php');
const controller = read('includes/Admin/Controllers/MarketingController.php');

assert.match(editor, /\$package\['title'\]/);
assert.doesNotMatch(editor, /\$package\['name'\]/);

assert.doesNotMatch(service, /THANKYOU|COMEBACK|'-OFFER-'/);
assert.match(service, /wp_generate_password\(8/);
assert.match(service, /get_by_code\(\$candidate\)/);
assert.match(service, /normalize_code\(\$candidate\)/);

assert.match(controller, /A7F3K9Q2-X8M4P6RT/);
assert.doesNotMatch(controller, /THANKYOU-PREVIEW|COMEBACK-PREVIEW/);

console.log('Marketing automation package labels and coupon codes are language-neutral.');
