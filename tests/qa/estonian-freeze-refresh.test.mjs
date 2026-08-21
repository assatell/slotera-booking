import fs from 'node:fs';
import assert from 'node:assert/strict';

const freeze=fs.readFileSync(new URL('../../includes/config/translation-freeze.php',import.meta.url),'utf8');
assert.ok(freeze.includes("'et_EE' =>"));
assert.ok(freeze.includes("'sha256' => '5e9847bd3fef8f5babf0febfc84cd1367ab87f83c094955ee978e99fb6efbf95'"));
console.log('estonian freeze refresh: ok');
