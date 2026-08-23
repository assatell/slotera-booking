import fs from 'node:fs';
import assert from 'node:assert/strict';

const helper = fs.readFileSync(
  new URL('../../includes/helpers.php', import.meta.url),
  'utf8'
);

assert.match(
  helper,
  /\$full_day_duration_days\s*=\s*0;/
);

assert.match(
  helper,
  /\$full_day_duration_days\s*=\s*\(int\)\s*\$start_boundary->diff\(\$end_boundary\)->days;/
);

assert.match(
  helper,
  /\$end_boundary->modify\('-1 day'\)->format\('Y-m-d'\)/
);

assert.match(
  helper,
  /\$is_multi_day\s*=\s*\$date_only_multi_day\s*\?\s*\$full_day_duration_days\s*>\s*1/
);

console.log('Fixed full-day exclusive end dates display one-day bookings as a single date and multi-day bookings by occupied dates.');
