import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const registry = read('includes/Application/Services/EmailTemplateRegistry.php');
const reminders = read('includes/Application/Services/EmailReminderService.php');

assert.match(registry, /booking_completed_customer' && \$field === 'default_subject'/);
assert.match(registry, /Спасибо за ваш визит/);
assert.match(registry, /Vielen Dank für Ihren Besuch/);
assert.match(registry, /Thank you for your visit/);
assert.match(registry, /booking_completed_customer' && in_array\(\$field, \['default_body', 'default_html_body'\]/);
assert.match(registry, /Спасибо за ваш визит\. Ваше бронирование завершено\./);
assert.match(registry, /vielen Dank für Ihren Besuch\. Ihre Buchung ist nun abgeschlossen\./);

assert.match(registry, /resolve_runtime_value_for_locale/);
assert.match(registry, /known_default_values/);
assert.match(reminders, /resolve_runtime_payload_for_locale/);
assert.match(reminders, /booking_email_locale/);

console.log('Legacy stock completion templates are recognized across locales and can follow booking_locale after admin actions.');
