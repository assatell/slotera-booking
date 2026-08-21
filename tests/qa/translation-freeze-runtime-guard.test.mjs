import fs from 'node:fs';
import assert from 'node:assert/strict';

const qa = fs.readFileSync(new URL('../../tools/qa.php', import.meta.url), 'utf8');
assert.match(qa, /new \\Slotera\\Application\\Services\\TranslationFreezeService\(\)/);
assert.match(qa, /\['frontend', 'emails', 'email_templates'\]/);
assert.match(qa, /\$freezeService->verify\(\$freezeSection, \(string\) \$freezeLocale\)/);
assert.match(qa, /translation freeze drift:/);
assert.match(qa, /translation freeze baselines verified against production registries/);
console.log('translation freeze runtime QA guard: ok');
