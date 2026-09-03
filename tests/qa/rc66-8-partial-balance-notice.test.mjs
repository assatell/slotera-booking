import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (p) => fs.readFileSync(new URL('../../' + p, import.meta.url), 'utf8');
const frontend = read('includes/Application/Services/Translations/FrontendTranslationStrings.php');
const emails = read('includes/Application/Services/Translations/EmailTranslationStrings.php');
const account = read('includes/Frontend/Views/account.php');
const thankYou = read('includes/Frontend/Views/thank-you.php');
const pdf = read('includes/Application/Services/PdfInvoiceService.php');
const emailService = read('includes/Application/Services/EmailNotificationService.php');
const freeze = read('includes/config/translation-freeze.php');

const locales = ['en_US','et','ru_RU','de_DE','fr_FR','da_DK','nl_NL','sv_SE','fi','es_ES','it_IT','pt_PT','pt_BR','pl_PL','cs_CZ','sk_SK','sl_SI','hr_HR','ro_RO','hu_HU','bg_BG','lt_LT','lv','no_NO','is_IS','el','ga_IE','mt_MT'];

test('RC66.8 localizes the remaining-balance-on-site notice in every supported locale catalog', () => {
  assert.match(frontend, /frontend\.remaining_balance_paid_on_site/);
  assert.match(emails, /emails\.remaining_balance_paid_on_site/);
  assert.match(frontend, /'ru_RU' => 'Остаток суммы оплачивается на месте\.'/);
  assert.match(emails, /'ru_RU' => 'Остаток суммы оплачивается на месте\.'/);
  for (const locale of locales) {
    assert.ok(frontend.includes(`'${locale}' =>`), `frontend locale missing: ${locale}`);
    assert.ok(emails.includes(`'${locale}' =>`), `email locale missing: ${locale}`);
  }
});

test('RC66.8 shows the notice only for partial payments across customer surfaces', () => {
  assert.match(account, /payment_status === 'partial'[\s\S]*frontend\.remaining_balance_paid_on_site/);
  assert.match(thankYou, /payment_status[^\n]*partial[\s\S]*frontend\.remaining_balance_paid_on_site/);
  assert.match(pdf, /payment_status[^\n]*partial[\s\S]*frontend\.remaining_balance_paid_on_site/);
  assert.match(emailService, /recipient_type === 'customer'[\s\S]*payment_status[^\n]*partial[\s\S]*emails\.remaining_balance_paid_on_site/);
});

test('RC66.8 refreshes strict frontend and email freeze item baselines', () => {
  const frontendStart = freeze.indexOf("'frontend' =>", freeze.indexOf("'sections' =>"));
  const emailStart = freeze.indexOf("'emails' =>", frontendStart);
  const templateStart = freeze.indexOf("'email_templates' =>", emailStart);
  const f = freeze.slice(frontendStart, emailStart);
  const e = freeze.slice(emailStart, templateStart);
  assert.match(f, /'items' => 383/);
  assert.match(e, /'items' => 154/);
});
