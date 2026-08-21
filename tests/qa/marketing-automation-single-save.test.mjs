import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');
const editor = read('includes/Admin/Views/marketing-automation-editor.php');
const controller = read('includes/Admin/Controllers/MarketingController.php');

assert.doesNotMatch(editor, /name="<\?php echo esc_attr\(\$sltr_prefix\); \?>enabled"/);
assert.doesNotMatch(editor, /Run now/);
assert.match(editor, /Preview and test/);
assert.match(editor, /submit_button\(\$sltr_save_label/);
assert.match(editor, /form' => 'sltr-automation-settings-form'/);

assert.match(controller, /'comeback_automation_enabled' => \(new LicenseService\(\)\)->can_use_automations\(\) \? 1 : 0/);
assert.match(controller, /'after_booking_automation_enabled' => \(new LicenseService\(\)\)->can_use_automations\(\) \? 1 : 0/);
assert.match(controller, /\$automation->process\(true\)/);
assert.match(controller, /\$automation->process_after_booking\(true\)/);
assert.match(controller, /\? 'campaigns' : 'come-back'/);
assert.match(controller, /\? 'campaigns' : 'after-booking'/);
assert.doesNotMatch(controller, /admin_post_sltr_run_comeback_automation_now/);
assert.doesNotMatch(controller, /admin_post_sltr_run_after_booking_automation_now/);

console.log('Marketing automation uses one Save action that activates and opens Automation campaigns.');
