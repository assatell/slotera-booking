import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const js = fs.readFileSync(new URL('../../assets/js/frontend-booking-form.js', import.meta.url), 'utf8');
const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');
const view = fs.readFileSync(new URL('../../includes/Frontend/Views/booking-form.php', import.meta.url), 'utf8');
const translations = fs.readFileSync(new URL('../../includes/Application/Services/Translations/FrontendTranslationStrings.php', import.meta.url), 'utf8');

test('Add extras is localized rather than hard-coded in rendered extras headings', () => {
  assert.match(js, /sltrT\('Add extras'\)/);
  assert.doesNotMatch(js, /<h4>Add extras<\/h4>/);
  assert.match(translations, /'frontend\.add_extras'/);
  for (const locale of ['en_US','et_EE','ru_RU','de_DE','fi','fr_FR','sv_SE','es_ES','it_IT','pl_PL','lv','lt_LT']) {
    assert.match(translations, new RegExp("'" + locale.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + "'\\s*=>"));
  }
});

test('extra row renders name then price then checkbox and details extras use canonical field width', () => {
  assert.match(js, /<span>' \+ escapeHtml\(item\.name[^;]+<strong>[^;]+<input type="checkbox"/);
  assert.match(css, /grid-template-columns:\s*minmax\(0,\s*1fr\)\s+auto\s+auto/);
  assert.match(css, /#sltr-details-extra-services\s*\{[^}]*max-width:\s*var\(--sltr-booking-inner-max-width,\s*520px\)/s);
});

test('Discount=None cannot be inferred from a dynamic price difference as package discount', () => {
  assert.match(view, /if \(\$discount_type === 'fixed' && \$discount_value > 0\)/);
  assert.match(view, /return '';\s*\n\};/);
  assert.match(css, /#sltr-summary-dynamic-wrap\s*\{[^}]*grid-column:\s*1 \/ -1[^}]*max-width:\s*var\(--sltr-booking-inner-max-width,\s*520px\)[^}]*margin-left:\s*auto[^}]*margin-right:\s*auto/s);
});
