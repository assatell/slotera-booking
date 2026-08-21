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
  assert.match(frontend, new RegExp(`'${locale}' =>[\\s\\S]*?'items' => 376`));
}
assert.match(frontend, /'ru_RU' =>[\s\S]*?'sha256' => '1c2782540307f3135bf5e5a5bce4863a010b7cbe4f2ac8e003e8b72490e78258'/);
assert.match(frontend, /'et_EE' =>[\s\S]*?'sha256' => '5e9847bd3fef8f5babf0febfc84cd1367ab87f83c094955ee978e99fb6efbf95'/);

console.log('Frontend translation freeze baselines include the current localized frontend keys.');
