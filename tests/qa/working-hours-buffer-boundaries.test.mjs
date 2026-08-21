import fs from 'node:fs';
import assert from 'node:assert/strict';

const availability = fs.readFileSync(
  new URL('../../includes/Domain/Availability/AvailabilityService.php', import.meta.url),
  'utf8'
);

const generateStart = availability.indexOf('private function generate_slots');
const generateEnd = availability.indexOf('private function is_slot_free', generateStart);
const generateBlock = availability.slice(generateStart, generateEnd);

assert.match(
  generateBlock,
  /\$current_start = \$window_start;/
);
assert.match(
  generateBlock,
  /\$candidate_end = \$current_start->modify\('\+' \. max\(1, \$duration_minutes\) \. ' minutes'\)/
);
assert.match(
  generateBlock,
  /if \(\$candidate_end > \$window_end\)/
);
assert.ok(!generateBlock.includes('$buffer_before'));
assert.ok(!generateBlock.includes('$buffer_after'));

const freeStart = availability.indexOf('private function is_slot_free');
const freeBlock = availability.slice(freeStart);
assert.match(
  freeBlock,
  /\$candidate_block_start = \$candidate_start->modify\('-' \. max\(0, \$buffer_before\) \. ' minutes'\)/
);
assert.match(
  freeBlock,
  /\$candidate_block_end = \$candidate_end->modify\('\+' \. max\(0, \$buffer_after\) \. ' minutes'\)/
);
assert.match(
  freeBlock,
  /\$booking_block_start = \$booking_start->modify\('-' \. max\(0, \$buffer_before\) \. ' minutes'\)/
);
assert.match(
  freeBlock,
  /\$booking_block_end = \$booking_end->modify\('\+' \. max\(0, \$buffer_after\) \. ' minutes'\)/
);

console.log('Buffers constrain spacing between bookings but do not shrink an otherwise empty working-hours window.');
