import fs from 'node:fs';
import assert from 'node:assert/strict';

const freeze = fs.readFileSync(new URL('../../includes/config/translation-freeze.php', import.meta.url), 'utf8');
const frontendStart = freeze.indexOf("'frontend' =>", freeze.indexOf("'sections' =>"));
const emailsStart = freeze.indexOf("'emails' =>", frontendStart);
const frontend = freeze.slice(frontendStart, emailsStart);

for (const locale of [
  'et_EE','de_DE','fr_FR','da_DK','nl_NL','sv_SE','fi_FI','ru_RU','es_ES','it_IT',
  'pt_PT','pt_BR','pl_PL','cs_CZ','sk_SK','sl_SI','hr_HR','ro_RO','hu_HU','bg_BG',
  'lt_LT','lv_LV','no_NO','el_GR'
]) {
  assert.match(frontend, new RegExp(`'${locale}' =>[\\s\\S]*?'items' => 383`));
}
assert.match(frontend, /'ru_RU' =>[\s\S]*?'sha256' => 'adad2888875579c8a7a8070b9cba2cc1786bcadebee5bf684a9dd462e7d971bc'/);
assert.match(frontend, /'et_EE' =>[\s\S]*?'sha256' => '34810ff02bc89b87201753dabf5299900d2818396aab80fbe15a8f6212a59178'/);

console.log('Frontend translation freeze baselines include the current localized frontend keys.');
