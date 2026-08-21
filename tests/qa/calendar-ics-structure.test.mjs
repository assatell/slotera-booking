import fs from 'node:fs';
import assert from 'node:assert/strict';

const source = fs.readFileSync(new URL('../../includes/Application/Services/CalendarInviteService.php', import.meta.url), 'utf8');
for (const token of [
  'BEGIN:VCALENDAR','VERSION:2.0','CALSCALE:GREGORIAN','METHOD:',
  'BEGIN:VEVENT','UID:','DTSTAMP:','DTSTART:','DTEND:',
  'SUMMARY:','STATUS:','SEQUENCE:','END:VEVENT','END:VCALENDAR'
]) {
  assert.ok(source.includes(token), `stable ICS structure missing ${token}`);
}
assert.match(source, /EmailTemplateRegistry::runtime_locale\(\)/);
assert.ok(source.includes("sltr_booking_status_label((string) ($booking['status'] ?? ''), 'emails', $email_locale)"));
assert.ok(source.includes("sltr_payment_status_label((string) ($booking['payment_status'] ?? ''), 'emails', $email_locale)"));
assert.doesNotMatch(source, /__\('Status'.*\$booking\['status'\]/s);
assert.doesNotMatch(source, /__\('Payment'.*\$booking\['payment_status'\]/s);
console.log('calendar ICS structure + localized status values: ok');
