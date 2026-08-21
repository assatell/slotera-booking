import fs from 'node:fs';
import assert from 'node:assert/strict';

const availability = fs.readFileSync(
  new URL('../../includes/Domain/Availability/AvailabilityService.php', import.meta.url),
  'utf8'
);

assert.match(
  availability,
  /\$this->meets_same_day_before_buffer\(\$date, \$slot\['start'\], \$buffer_before\)/
);

const start = availability.indexOf('private function meets_same_day_before_buffer');
const end = availability.indexOf('private function meets_lead_time', start);
const block = availability.slice(start, end);

assert.match(block, /if \(\$buffer_before <= 0\)/);
assert.doesNotMatch(block, /\$slot->format\('Y-m-d'\) !== \$now->format\('Y-m-d'\)/);
assert.match(block, /return \$slot->modify\('-' \. \$buffer_before \. ' minutes'\) >= \$now;/);

console.log('Availability requires the full Before buffer even when it crosses midnight.');
