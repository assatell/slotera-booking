import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('simple booking contact notice is appended only to created-request emails', () => {
  const service = read('includes/Application/Services/EmailNotificationService.php');
  assert.match(service, /apply_email_display_preferences\(\$text, \$preferences, \$recipient_type, \$scenario\)/);
  assert.match(service, /in_array\(\$scenario, \['booking_created_customer', 'booking_created_admin'\], true\)/);
});

test('cancelled request translations do not contain the simple-booking follow-up notice', () => {
  const translations = read('includes/Application/Services/Translations/BookingRequestTranslations.php');
  const ru = translations.match(/'ru_RU' => \[(.*?)\],\n\s*\];/s)?.[1] || translations;
  assert.match(translations, /'cancelled_customer_body' => 'Здравствуйте, \{customer_name\}!\\n\\nВаша заявка на «\{package_title\}» отменена\.\\n\\nНомер заявки: #\{booking_id\}'/);
});
