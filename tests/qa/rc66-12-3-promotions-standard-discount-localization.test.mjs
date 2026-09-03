import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (p) => fs.readFileSync(new URL(`../../${p}`, import.meta.url), 'utf8');

test('RC66.12.3 localizes the standard package discount label in Promotions', () => {
  const service = read('includes/Application/Services/PromotionCampaignService.php');
  assert.match(service, /translated_offer_label\('Standard discount'\)\s*\.\s*' -'/);
  assert.match(service, /\$discount_line = \$this->translated_offer_label\('Standard discount'\);/);
  assert.doesNotMatch(service, /\$discount_line = 'Discount -'/);
  assert.doesNotMatch(service, /\$meta_lines\[\] = 'Discount'/);
});

test('RC66.12.3 provides Standard discount across every declared locale variant', () => {
  const frontend = read('includes/Application/Services/Translations/FrontendTranslationStrings.php');
  const entry = frontend.match(/'frontend\.standard_discount'\s*=>\s*array\s*\(([\s\S]*?)\n\s*\),/);
  assert.ok(entry, 'frontend.standard_discount must exist');
  for (const locale of [
    'en_US','bg_BG','hr','hr_HR','cs_CZ','cs','da_DK','nl_NL','et','et_EE','fi',
    'fr_FR','de_DE','el_GR','el','hu_HU','hu','ga_IE','ga','is_IS','is','it_IT',
    'lv','lt_LT','mt_MT','no_NO','no','nb_NO','nb','pl_PL','pt_PT','pt_BR',
    'ro_RO','sk_SK','sk','sl_SI','sl','es_ES','sv_SE','ru_RU','ru'
  ]) {
    assert.match(entry[1], new RegExp(`'${locale}'\\s*=>\\s*'[^']+'`), `missing ${locale}`);
  }
  assert.match(entry[1], /'ru_RU'\s*=>\s*'Стандартная скидка'/);
});

test('RC66.12.3 refreshes the customer-facing translation freeze baseline', () => {
  const freeze = read('includes/config/translation-freeze.php');
  const lock = JSON.parse(read('languages/slotera-booking.translation-keys.lock.json'));
  assert.match(freeze, /'ru_RU'\s*=>[\s\S]*?'items'\s*=>\s*383[\s\S]*?'sha256'\s*=>\s*'adad2888875579c8a7a8070b9cba2cc1786bcadebee5bf684a9dd462e7d971bc'/);
  assert.equal(lock.keys['frontend.standard_discount'].group, 'frontend');
});
