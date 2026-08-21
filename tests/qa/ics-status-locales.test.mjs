import fs from 'node:fs';
import assert from 'node:assert/strict';

const helpers = fs.readFileSync(new URL('../../includes/helpers.php', import.meta.url), 'utf8');
const emailStrings = fs.readFileSync(new URL('../../includes/Application/Services/Translations/EmailTranslationStrings.php', import.meta.url), 'utf8');

const requiredLocales = [
  'en_US','es_ES','et','ru_RU','de_DE','fr_FR','da_DK','nl_NL','sv_SE','fi',
  'it_IT','pt_PT','pt_BR','sk_SK','pl_PL','cs_CZ','sl_SI','hr_HR','hu_HU','ro_RO',
  'bg_BG','lt_LT','lv','no_NO','is_IS','el','ga_IE','mt_MT'
];

function extractMap(functionName) {
  const start = helpers.indexOf(`function ${functionName}`);
  assert.ok(start >= 0, `missing helper ${functionName}`);
  const mapStart = helpers.indexOf('$map = [', start);
  const mapEnd = helpers.indexOf('];', mapStart);
  assert.ok(mapStart >= 0 && mapEnd > mapStart, `missing status map for ${functionName}`);
  const block = helpers.slice(mapStart, mapEnd + 2);
  const entries = [...block.matchAll(/'([^']+)'\s*=>\s*'([^']+)'/g)];
  return new Map(entries.map((m) => [m[1], m[2]]));
}

function translationRow(fullKey) {
  const marker = `'${fullKey}' => [`;
  const start = emailStrings.indexOf(marker);
  assert.ok(start >= 0, `missing translation row ${fullKey}`);
  const end = emailStrings.indexOf('],', start);
  assert.ok(end > start, `unterminated translation row ${fullKey}`);
  return emailStrings.slice(start, end + 2);
}

function assertLocalesForHelper(functionName, prefix) {
  const map = extractMap(functionName);
  assert.ok(map.size > 0, `${functionName} contains no statuses`);

  for (const [, translationKey] of map) {
    const fullKey = `emails.${translationKey}`;
    const row = translationRow(fullKey);
    for (const locale of requiredLocales) {
      assert.ok(
        row.includes(`'${locale}' =>`),
        `${fullKey} missing ${locale}`
      );
    }
  }

  // Aliases may point to the same translation key; every mapped status must still resolve.
  for (const [status, translationKey] of map) {
    assert.ok(
      emailStrings.includes(`'emails.${translationKey}' => [`),
      `${functionName} status ${status} has no email translation row`
    );
  }
}

assertLocalesForHelper('sltr_booking_status_label', 'booking');
assertLocalesForHelper('sltr_payment_status_label', 'payment');

console.log('ICS booking/payment status locale coverage: all helper statuses × all email locales');
