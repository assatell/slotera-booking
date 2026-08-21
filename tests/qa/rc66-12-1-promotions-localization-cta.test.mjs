import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const service = fs.readFileSync('includes/Application/Services/PromotionCampaignService.php', 'utf8');
const frontend = fs.readFileSync('includes/Application/Services/Translations/FrontendTranslationStrings.php', 'utf8');

test('RC66.12.1 Promotions reuses frozen Weekend/Seasonal translations', () => {
  assert.match(frontend, /'frontend\.weekend_offer'/);
  assert.match(frontend, /'frontend\.seasonal_offer'/);
  assert.match(service, /translated_offer_label\('Weekend offer'\)/);
  assert.match(service, /translated_offer_label\('Seasonal offer'\)/);
  assert.match(service, /sltr_t\(\$default, 'frontend', \$locale\)/);
  assert.doesNotMatch(service, /\$validity\[\]\s*=\s*'Weekends'/);
  assert.doesNotMatch(service, /\$validity\[\]\s*=\s*'Until '/);
});

test('RC66.12.1 promotion CTA cannot wrap localized labels', () => {
  assert.match(service, /font-weight:700;white-space:nowrap;/);
  assert.match(service, /sltr_t\('Book now', 'emails', EmailTemplateRegistry::runtime_locale\(\)\)/);
});
