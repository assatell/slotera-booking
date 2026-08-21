import fs from 'node:fs';
import assert from 'node:assert/strict';

const catalog=fs.readFileSync(new URL('../../includes/Application/Services/Translations/FrontendTranslationStrings.php',import.meta.url),'utf8');
const pricing=fs.readFileSync(new URL('../../includes/Application/Services/PricingAdjustmentService.php',import.meta.url),'utf8');
const account=fs.readFileSync(new URL('../../includes/Frontend/Views/account.php',import.meta.url),'utf8');
const checkout=fs.readFileSync(new URL('../../includes/Frontend/Views/checkout.php',import.meta.url),'utf8');
const thankyou=fs.readFileSync(new URL('../../includes/Frontend/Views/thank-you.php',import.meta.url),'utf8');
const email=fs.readFileSync(new URL('../../includes/Application/Services/EmailReminderService.php',import.meta.url),'utf8');

const key="'frontend.seasonal_offer' =>";
const start=catalog.indexOf(key);
assert.ok(start>=0,'seasonal offer frontend key missing');
const next=catalog.indexOf("\n  'frontend.",start+key.length);
const block=catalog.slice(start,next>start?next:catalog.length);
for (const locale of ['bg_BG','hr','cs_CZ','da_DK','nl_NL','et','fi','fr_FR','de_DE','el','hu_HU','it_IT','lv','lt_LT','no_NO','pl_PL','pt_PT','pt_BR','ro_RO','sk_SK','sl_SI','es_ES','sv_SE','ru_RU','ga_IE','is_IS','mt_MT']) {
  assert.ok(block.includes(`'${locale}' =>`), `seasonal offer missing ${locale}`);
}
assert.ok(block.includes("'el' => 'Εποχική προσφορά'"),'Greek seasonal translation missing');
assert.match(pricing,/sltr__\('frontend\.seasonal_offer'\)/,'seasonal pricing must use frontend translation');
assert.match(pricing,/localize_offer_label/,'persisted offer labels need runtime relocalization');
for (const source of [account,checkout,thankyou,email]) {
  assert.match(source,/localize_offer_label/,'surface must relocalize persisted offer labels');
}
console.log('dynamic offer localization: ok');
