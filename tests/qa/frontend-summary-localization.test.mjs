import fs from 'node:fs';
import assert from 'node:assert/strict';

const strings = fs.readFileSync(new URL('../../includes/Application/Services/Translations/FrontendTranslationStrings.php', import.meta.url), 'utf8');
const order = fs.readFileSync(new URL('../../includes/Application/Services/Translations/TranslationStringOrder.php', import.meta.url), 'utf8');
const booking = fs.readFileSync(new URL('../../includes/Frontend/Views/booking-form.php', import.meta.url), 'utf8');
const checkout = fs.readFileSync(new URL('../../includes/Frontend/Views/checkout.php', import.meta.url), 'utf8');

for (const key of ['frontend.back_to_client_account','frontend.discounts','frontend.package_discount']) {
  assert.ok(strings.includes(`'${key}' =>`), `${key} missing from catalog`);
  assert.ok(order.includes(`'${key}' => 'frontend'`), `${key} missing from runtime order`);
}
assert.ok(strings.includes("'de_DE' => 'Zurück zum Kundenkonto'"));
assert.ok(strings.includes("'de_DE' => 'Rabatte'"));
assert.ok(strings.includes("'de_DE' => 'Paketrabatt'"));
assert.match(booking, /sltr_t\('Discounts'\)/);
assert.match(booking, /sltr_t\('Package discount'\)/);
assert.match(checkout, /sltr_t\('Package discount'\)/);
console.log('frontend summary localization: ok');
