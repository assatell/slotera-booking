import fs from 'node:fs';
import assert from 'node:assert/strict';

const read = p => fs.readFileSync(new URL(`../../${p}`, import.meta.url), 'utf8');
const catalog = read('includes/Application/Services/Translations/FrontendTranslationStrings.php');
const order = read('includes/Application/Services/Translations/TranslationStringOrder.php');
const locales = ['bg_BG','cs_CZ','da_DK','de_DE','el_GR','es_ES','et','fi','fr_FR','ga_IE','hr_HR','hu_HU','is_IS','it_IT','lt_LT','lv','mt_MT','nl_NL','no_NO','pl_PL','pt_BR','pt_PT','ro_RO','ru_RU','sk_SK','sl_SI','sv_SE'];
const newKeys = [
'frontend.booking_access_link_is_invalid_or_expired','frontend.checkout','frontend.review_booking_discounts_taxes_payment',
'frontend.booking_summary','frontend.price_summary','frontend.subtotal','frontend.special_offer','frontend.vat_tax',
'frontend.amount_due_now','frontend.remaining_balance','frontend.continue_to_payment','frontend.confirm_and_continue',
'frontend.booking_temporarily_unavailable'
];
for (const key of newKeys) {
  const start=catalog.indexOf(`'${key}' =>`);
  assert.ok(start >= 0, `${key} missing`);
  assert.ok(order.includes(`'${key}' => 'frontend'`), `${key} missing from runtime order`);
  const next=catalog.indexOf("\n  'frontend.", start+key.length+5);
  const block=catalog.slice(start,next>start?next:catalog.length);
  for (const locale of locales) assert.ok(block.includes(`'${locale}' =>`), `${key}: missing ${locale}`);
}

const audited=[
'includes/Frontend/Views/booking-form.php','includes/Frontend/Views/checkout.php',
'includes/Frontend/Views/thank-you.php','includes/Frontend/Views/account.php'
];
for (const file of audited) {
  const source=read(file);
  assert.doesNotMatch(source, /esc_html_e\('[^']+'\s*,\s*'slotera-booking'\)/, `${file}: raw frontend esc_html_e remains`);
}
console.log('translation audit cleanup: ok');
