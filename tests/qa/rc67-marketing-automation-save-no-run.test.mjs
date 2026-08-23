import fs from 'node:fs';
import assert from 'node:assert/strict';

const controller = fs.readFileSync(
  new URL('../../includes/Admin/Controllers/MarketingController.php', import.meta.url),
  'utf8'
);

const comeback = controller
  .split('public function save_comeback_automation(): void')[1]
  .split('public function save_after_booking_automation(): void')[0];

const afterBooking = controller
  .split('public function save_after_booking_automation(): void')[1]
  .split('private function sanitize_automation_cta_label')[0];

for (const block of [comeback, afterBooking]) {
  assert.match(block, /ensure_scheduled\(true\)/);
  assert.doesNotMatch(block, /->process\(true\)/);
  assert.doesNotMatch(block, /->process_after_booking\(true\)/);
  assert.doesNotMatch(block, /process_campaign_queue/);
}

assert.match(comeback, /automation_saved=1/);
assert.match(afterBooking, /after_booking_automation_saved=1/);

console.log('Marketing automation Save schedules future processing without running campaigns immediately.');
