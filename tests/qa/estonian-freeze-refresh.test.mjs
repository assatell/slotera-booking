import fs from 'node:fs';
import assert from 'node:assert/strict';

const freeze=fs.readFileSync(new URL('../../includes/config/translation-freeze.php',import.meta.url),'utf8');
assert.ok(freeze.includes("'et_EE' =>"));
assert.ok(freeze.includes("'sha256' => '34810ff02bc89b87201753dabf5299900d2818396aab80fbe15a8f6212a59178'"));
console.log('estonian freeze refresh: ok');
