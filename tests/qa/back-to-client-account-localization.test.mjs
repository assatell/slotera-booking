import fs from 'node:fs';
import assert from 'node:assert/strict';

const registry = fs.readFileSync(new URL('../../includes/Application/Services/Translations/FrontendTranslationStrings.php', import.meta.url), 'utf8');
const key = 'frontend.back_to_client_account';
const start = registry.indexOf(`'${key}' =>`);
assert.ok(start >= 0, `${key} must exist`);
const next = registry.indexOf("\n  'frontend.", start + key.length + 5);
const block = registry.slice(start, next > start ? next : registry.length);
const locales = [
  'bg_BG','cs_CZ','da_DK','de_DE','el_GR','es_ES','et','fi','fr_FR','ga_IE','hr_HR','hu_HU','is_IS',
  'it_IT','lt_LT','lv','mt_MT','nl_NL','no_NO','pl_PL','pt_BR','pt_PT','ro_RO','ru_RU','sk_SK','sl_SI','sv_SE'
];
for (const locale of locales) {
  assert.match(block, new RegExp(`'${locale.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}'\\s*=>`), `${key}: missing ${locale}`);
}
console.log('back to client account localization: ok');
