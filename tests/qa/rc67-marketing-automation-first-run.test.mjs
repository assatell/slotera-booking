import fs from 'node:fs';
import assert from 'node:assert/strict';

const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const controller = read('includes/Admin/Controllers/MarketingController.php');
const editor = read('includes/Admin/Views/marketing-automation-editor.php');

assert.match(editor, /name="action" value="sltr_run_marketing_automation"/);
assert.match(editor, /name="id" value="0"/);
assert.match(editor, /name="type" value="<\?php echo esc_attr\(\$sltr_automation_type\); \?>"/);
assert.match(editor, /wp_nonce_field\('sltr_run_marketing_automation_' \. \$sltr_automation_type\)/);
assert.match(editor, /Run now/);

assert.match(controller, /if \(\$id > 0\)/);
assert.match(controller, /\$_POST\['type'\]/);
assert.match(controller, /\$posted_type === 'after-booking'/);
assert.match(controller, /\$posted_type === 'come-back'/);
assert.match(controller, /'after_booking'/);
assert.match(controller, /'come_back'/);
assert.match(controller, /in_array\(\$type, \['after_booking', 'come_back'\], true\)/);
assert.match(controller, /sltr_run_marketing_automation_' \. \$posted_type/);
assert.match(controller, /process_after_booking\(true\)/);
assert.match(controller, /->process\(true\)/);

console.log('Marketing automation supports first manual Run now without an existing campaign ID.');
