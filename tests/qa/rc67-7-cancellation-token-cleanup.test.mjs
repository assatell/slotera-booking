import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');

test('every booking cancellation clears both public action tokens', () => {
  const repository = read('includes/Infrastructure/Repositories/BookingRepository.php');
  const updateStatus = repository.match(/public function update_status[\s\S]*?public function update_payment_status/)?.[0] || '';
  const tokenCancel = repository.match(/public function cancel_by_token_atomically[\s\S]*?public function complete/)?.[0] || '';

  assert.match(updateStatus, /if \(\$status === 'cancelled'\)[\s\S]*?\$data\['cancellation_token'\] = null/);
  assert.match(updateStatus, /if \(\$status === 'cancelled'\)[\s\S]*?\$data\['reschedule_token'\] = null/);
  assert.match(tokenCancel, /SET status='cancelled',cancellation_token=NULL,reschedule_token=NULL,active_slot_hash=NULL/);
});

test('admin cancellation notifications receive the post-update token-free booking', () => {
  const transitions = read('includes/Application/Services/BookingStatusTransitionService.php');
  assert.match(
    transitions,
    /\$updated = \$this->bookings->cancel\(\$id\);[\s\S]*?\$after = \$this->bookings->get_by_id\(\$id\);[\s\S]*?notifications->booking_cancelled\(\$id, \$after\)/,
  );

  const lifecycle = read('includes/Application/Services/BookingLifecycleService.php');
  assert.match(lifecycle, /cancellation_url\(array \$booking\): string[\s\S]*?\$token !== '' \?/);
  assert.match(lifecycle, /reschedule_url\(array \$booking\): string[\s\S]*?\$token !== '' \?/);
});
