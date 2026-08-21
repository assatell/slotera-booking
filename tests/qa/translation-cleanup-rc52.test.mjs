import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const frontend = fs.readFileSync(new URL('../../includes/Application/Services/Translations/FrontendTranslationStrings.php', import.meta.url), 'utf8');
const emails = fs.readFileSync(new URL('../../includes/Application/Services/Translations/EmailTranslationStrings.php', import.meta.url), 'utf8');
const templates = fs.readFileSync(new URL('../../includes/Application/Services/EmailTemplateTranslationData.php', import.meta.url), 'utf8');
const order = fs.readFileSync(new URL('../../includes/Application/Services/Translations/TranslationStringOrder.php', import.meta.url), 'utf8');

const brokenFrontendFragments = [
  'PDF invoices are désactivé.', 'veuillez sign in to manage this réservation.', 'Previous mois', 'Creating réservation...',
  'PDF invoices are disabilitato.', 'Street indirizzo', 'Previous mese', 'Creating prenotazione...',
  'PDF invoices are desactivado.', 'Street dirección', 'Previous mes', 'Creating reserva...',
  'невалидно or missing REST nonce.'
];

const brokenEmailFragments = [
  'Kasuta HTML mall jaoks this e-post',
  'These seaded control sender details ja global e-post delivery.',
  'Ebaõnnestus queued e-kirjad on retried automatically by WP-Cron.'
];

test('RC52 translation cleanup removes known mixed-language regressions', () => {
  for (const fragment of brokenFrontendFragments) assert.ok(!frontend.includes(fragment), fragment);
  for (const fragment of brokenEmailFragments) assert.ok(!emails.includes(fragment), fragment);
  assert.ok(!templates.includes('Hej {customer_name},\n\nYour booking starts soon.'));
  assert.match(order, /'frontend\.recommended'\s*=>\s*'frontend'/);
});
