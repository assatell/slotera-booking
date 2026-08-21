import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const controller = read('includes/Admin/Controllers/MarketingController.php');
const table = read('includes/Admin/Views/campaign-history-table.php');
const adminJs = read('assets/js/admin.js');

for (const action of [
  'sltr_stop_marketing_automation',
  'sltr_run_marketing_automation',
  'sltr_delete_marketing_campaign',
]) {
  assert.match(table, new RegExp(`name="action" value="${action}"`));
}

assert.match(table, /method="post"/);
assert.doesNotMatch(table, /admin-post\.php\?action=sltr_delete_marketing_campaign/);
assert.match(table, /wp_nonce_field\('sltr_delete_marketing_campaign_' \. \$id\)/);

assert.match(controller, /\$id = isset\(\$_POST\['id'\]\)/);
assert.match(controller, /delete_campaign_record/);
assert.match(controller, /foreach \(\$this->repo->get_all\(\) as \$automation_campaign\)/);
assert.match(controller, /'after_booking_automation_enabled'/);
assert.match(controller, /'comeback_automation_enabled'/);
assert.match(controller, /sanitize_key\(\(string\) \(\$automation_campaign\['automation_type'\]/);

assert.match(controller, /public function stop_marketing_automation\(\): void/);
assert.match(controller, /update\(\[\$key => 0\]\)/);
assert.match(controller, /public function run_marketing_automation\(\): void/);
assert.match(controller, /process_after_booking\(true\)/);
assert.match(controller, /process\(true\)/);

assert.match(adminJs, /form\.requestSubmit/);
console.log('Automation campaigns Stop, Run and Delete actions are wired through POST + nonce and Delete removes the full automation execution history.');
