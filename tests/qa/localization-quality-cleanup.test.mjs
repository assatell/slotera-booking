import fs from 'node:fs';
import assert from 'node:assert/strict';
const catalog=fs.readFileSync(new URL('../../includes/Application/Services/Translations/FrontendTranslationStrings.php',import.meta.url),'utf8');
const freeze=fs.readFileSync(new URL('../../includes/config/translation-freeze.php',import.meta.url),'utf8');
const start=catalog.indexOf("'frontend.subtotal' =>");
const next=catalog.indexOf("\n  'frontend.",start+20);
const block=catalog.slice(start,next);
for (const [locale,value] of Object.entries({
  da_DK:'Delsum',es_ES:'Suma parcial',mt_MT:'Total parzjali',
  pt_BR:'Subtotal parcial',pt_PT:'Subtotal parcial',ro_RO:'Sumă parțială'
})) {
  assert.ok(block.includes(`'${locale}' => '${value}'`), `Subtotal quality fix missing for ${locale}`);
}
assert.ok(!block.includes("'da_DK' => 'Subtotal'"));
assert.match(freeze, /'frontend'\s*=>[\s\S]*?'items'\s*=>\s*\d+/);
console.log('localization quality cleanup: ok');
