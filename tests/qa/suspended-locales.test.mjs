import fs from 'node:fs';
import assert from 'node:assert/strict';

const registry = fs.readFileSync(new URL('../../includes/Application/Services/TranslationRegistry.php', import.meta.url), 'utf8');
const service = fs.readFileSync(new URL('../../includes/Application/Services/TranslationService.php', import.meta.url), 'utf8');
const freeze = fs.readFileSync(new URL('../../includes/config/translation-freeze.php', import.meta.url), 'utf8');

for (const locale of ['ga_IE','is_IS','mt_MT']) {
  assert.ok(registry.includes(`'${locale}' =>`), `${locale} translations must remain bundled`);
}
assert.match(registry, /hidden_languages\(\).*ga_IE[\s\S]*is_IS[\s\S]*mt_MT/s, 'suspended locales must be hidden');
assert.doesNotMatch(service, /'ga'\s*=>\s*'ga_IE'/);
assert.doesNotMatch(service, /'is'\s*=>\s*'is_IS'/);
assert.doesNotMatch(service, /'mt'\s*=>\s*'mt_MT'/);

const frozenStart = freeze.indexOf("'frozen_locales'");
const aliasesStart = freeze.indexOf("'aliases'", frozenStart);
const frozen = freeze.slice(frozenStart, aliasesStart);
for (const locale of ['ga_IE','is_IS','mt_MT']) {
  assert.ok(!frozen.includes(`'${locale}'`), `${locale} must not be release-frozen while suspended`);
}

console.log('suspended locales: ok');
