import fs from 'node:fs';
import assert from 'node:assert/strict';

const read = (path) => fs.readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const catalog = read('includes/Application/Services/Translations/FrontendTranslationStrings.php');
const order = read('includes/Application/Services/Translations/TranslationStringOrder.php');
const view = read('includes/Frontend/Views/booking-form.php');
const css = read('assets/css/frontend.css');

assert.match(order, /'frontend\.marketing_consent' => 'frontend'/);
assert.match(catalog, /'frontend\.marketing_consent' =>/);
for (const locale of [
  'en_US','bg_BG','hr','cs_CZ','da_DK','nl_NL','et','fi','fr_FR','de_DE','el','hu_HU',
  'ga_IE','is_IS','it_IT','lv','lt_LT','mt_MT','no_NO','pl_PL','pt_PT','pt_BR','ro_RO',
  'sk_SK','sl_SI','es_ES','sv_SE','ru_RU'
]) {
  assert.match(catalog, new RegExp(`'${locale}' => '[^']+'`), `marketing consent translation missing for ${locale}`);
}
const consent = view.slice(view.indexOf('<div class="sltr-fields sltr-marketing-consent">'), view.indexOf('<?php if ($show_payment_unavailable_notice)', view.indexOf('<div class="sltr-fields sltr-marketing-consent">')));
assert.match(consent, /<label class="sltr-marketing-consent-card">[\s\S]*?<span class="sltr-marketing-consent-text">[\s\S]*?<\/span>\s*<input type="checkbox" id="sltr-marketing-consent" value="1">/, 'marketing consent must remain explicit, unchecked, and visually grouped');
assert.doesNotMatch(consent, /id="sltr-marketing-consent"[^>]*checked/);
assert.match(css, /sltr-marketing-consent-card\{[^}]*display:flex;[^}]*border:1px solid/);
assert.match(css, /sltr-marketing-consent-text\{[^}]*font-size:\.9em/);
assert.match(css, /sltr-marketing-consent input\[type="checkbox"\]\{[^}]*width:20px;[^}]*height:20px/);

console.log('Marketing consent is localized in every supported locale and remains an explicit unchecked compact card.');
