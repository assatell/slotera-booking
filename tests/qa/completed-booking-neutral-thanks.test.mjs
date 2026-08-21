import fs from 'node:fs';
import assert from 'node:assert/strict';
const registry = fs.readFileSync(new URL('../../includes/Application/Services/EmailTemplateRegistry.php', import.meta.url), 'utf8');
const translationData = fs.readFileSync(new URL('../../includes/Application/Services/EmailTemplateTranslationData.php', import.meta.url), 'utf8');
const catalog = registry + translationData;

assert.match(catalog, /'default_subject' => 'Thank you for choosing us\.'/);
assert.match(catalog, /Thank you for choosing us\. Your booking is now completed\./);
assert.match(catalog, /'default_subject' => 'Спасибо, что выбрали нас\.'/);
assert.match(catalog, /Спасибо, что выбрали нас\. Ваше бронирование завершено\./);

const legacySectionStart = registry.indexOf('private static function legacy_default_values');
const currentTemplates = registry.slice(registry.indexOf('private static function base_scenarios'), legacySectionStart > 0 ? legacySectionStart : registry.length) + translationData;
for (const legacy of [
  'Thank you for your visit',
  'Спасибо за ваш визит',
  'Vielen Dank für Ihren Besuch',
  'Merci pour votre visite',
  'Gracias por tu visita',
  'Grazie per la tua visita'
]) {
  assert.ok(!currentTemplates.includes(legacy), `legacy visit wording remains in current templates: ${legacy}`);
}

for (const expected of [
  'Σας ευχαριστούμε που μας επιλέξατε.',
  'Paldies, ka izvēlējāties mūs.',
  'Takk for at du valgte oss.',
  'Köszönjük, hogy minket választott.',
  'Hvala, ker ste izbrali nas.',
  'Ďakujeme, že ste si vybrali nás.',
  "Vă mulțumim că ne-ați ales.",
  'Agradecemos por nos escolher.',
  'Děkujeme, že jste si vybrali nás.',
  'Dziękujemy, że wybrali Państwo nas.',
  'Grazie per averci scelto.',
  'Gracias por elegirnos.',
  'Kiitos, että valitsit meidät.',
  'Tack för att du valde oss.',
  'Merci de nous avoir choisis.',
  'Благодарим ви, че ни избрахте.',
  'Dėkojame, kad pasirinkote mus.',
  'Täname, et valisite meid.',
  'Tak, fordi du valgte os.',
  'Bedankt dat u voor ons heeft gekozen.',
  'Vielen Dank, dass Sie sich für uns entschieden haben.',
  'Hvala što ste nas odabrali.'
]) {
  assert.ok(catalog.includes(expected), `neutral completion copy missing: ${expected}`);
}
console.log('Completed-booking customer template uses neutral thank-you wording in all official locales.');
