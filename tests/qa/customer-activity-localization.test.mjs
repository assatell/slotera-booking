import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (p) => fs.readFileSync(new URL(`../../${p}`, import.meta.url), 'utf8');

test('Standard booking activity events use the shared customer-facing translation registry', () => {
  const helpers = read('includes/helpers.php');
  assert.match(helpers, /'simple_booking_created'\s*=>\s*'frontend\.booking_created'/);
  assert.match(helpers, /'booking_confirmed_by_admin'\s*=>\s*'frontend\.booking_confirmed'/);
  assert.match(helpers, /'booking_cancelled_by_customer'\s*=>\s*'frontend\.booking_cancelled'/);
  assert.match(helpers, /'booking_rescheduled_by_customer'\s*=>\s*'frontend\.booking_rescheduled'/);
  assert.match(helpers, /'payment_initialization_failed'\s*=>\s*'frontend\.payment_failed'/);
  assert.match(helpers, /'deposit_paid'\s*=>\s*'frontend\.payment_partially_paid'/);
});

test('Customer account activity resolves labels using the booking locale and hides internal notification reservations', () => {
  const account = read('includes/Frontend/Views/account.php');
  assert.match(account, /sltr_activity_event_label\([^;]+\$selected_booking\['booking_locale'\]/s);
  assert.match(account, /payment_completed_notified/);
  assert.match(account, /array_filter\(\(array\) \$selected_history/);
  assert.match(account, /\$sltr_customer_history/);
});

test('Core customer activity labels have translations in every official runtime locale', () => {
  const registry = read('includes/Application/Services/Translations/FrontendTranslationStrings.php');
  const locales = [
    'bg_BG','cs_CZ','da_DK','de_DE','el_GR','es_ES','et','fi','fr_FR','ga_IE','hr_HR','hu_HU','is_IS',
    'it_IT','lt_LT','lv','mt_MT','nl_NL','no_NO','pl_PL','pt_BR','pt_PT','ro_RO','ru_RU','sk_SK','sl_SI','sv_SE'
  ];
  for (const key of ['frontend.booking_created','frontend.booking_confirmed','frontend.booking_cancelled','frontend.booking_rescheduled','frontend.payment_received','frontend.payment_partially_paid','frontend.payment_marked_as_unpaid']) {
    const start = registry.indexOf(`'${key}' =>`);
    assert.ok(start >= 0, `${key} must exist`);
    const next = registry.indexOf("\n  'frontend.", start + key.length + 5);
    const block = registry.slice(start, next > start ? next : registry.length);
    for (const locale of locales) {
      assert.match(block, new RegExp(`'${locale.replace(/[.*+?^${}()|[\\]\\]/g, '\\$&')}'\\s*=>`), `${key}: missing ${locale}`);
    }
  }
});
