import fs from 'node:fs';
import assert from 'node:assert/strict';

const source = fs.readFileSync(new URL('../../includes/Application/Services/CalendarInviteService.php', import.meta.url), 'utf8');
assert.ok(source.includes("'DTSTART:' . gmdate("), 'stable ICS DTSTART must be UTC');
assert.ok(source.includes("'DTEND:' . gmdate("), 'stable ICS DTEND must be UTC');
assert.ok(!source.includes('DTSTART;TZID='), 'stable ICS must not emit TZID');
assert.ok(!source.includes('BEGIN:VTIMEZONE'), 'stable ICS must not emit VTIMEZONE');
assert.ok(!source.includes('X-WR-TIMEZONE:'), 'stable ICS must not emit X-WR-TIMEZONE');
console.log('calendar ICS stable rollback: ok');
