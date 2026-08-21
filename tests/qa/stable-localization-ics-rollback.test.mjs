import fs from 'node:fs';
import assert from 'node:assert/strict';

const calendar=fs.readFileSync(new URL('../../includes/Application/Services/CalendarInviteService.php',import.meta.url),'utf8');
const registry=fs.readFileSync(new URL('../../includes/Application/Services/TranslationRegistry.php',import.meta.url),'utf8');
const unicode=fs.readFileSync(new URL('./unicode-slug-generation.test.mjs',import.meta.url),'utf8');

assert.ok(calendar.includes("'DTSTART:' . gmdate("));
assert.ok(calendar.includes("'DTEND:' . gmdate("));
assert.ok(!calendar.includes('X-WR-TIMEZONE:'));
assert.ok(!calendar.includes('DTSTART;TZID='));
assert.ok(!calendar.includes('BEGIN:VTIMEZONE'));

for (const locale of ['ga_IE','is_IS','mt_MT']) {
  assert.ok(registry.includes(`'${locale}' =>`), `${locale} translations must remain bundled`);
}
assert.match(registry,/hidden_languages\(\)[\s\S]*ga_IE[\s\S]*is_IS[\s\S]*mt_MT/);
for (const sample of ['Greek','Cyrillic','Polish','Icelandic','Maltese','Latin']) {
  assert.ok(unicode.includes(sample), `Unicode suite missing ${sample}`);
}
console.log('stable localization + ICS rollback: ok');
