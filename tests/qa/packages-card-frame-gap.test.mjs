import fs from 'node:fs';
import assert from 'node:assert/strict';

const css=fs.readFileSync(new URL('../../assets/css/frontend.css',import.meta.url),'utf8');

assert.ok(!css.includes('v1.0.1028 Packages card visible frame spacing'));
assert.match(css,/\.sltr-packages-page \.sltr-packages-list\s*\{[\s\S]*column-gap:\s*35px;[\s\S]*row-gap:\s*35px;/);
assert.match(css,/\.sltr-packages-page \.sltr-package-card\s*\{[\s\S]*width:\s*100%;[\s\S]*margin:\s*0;/);
assert.match(css,/@media \(max-width: 640px\) \{[\s\S]*?\.sltr-packages-page \.sltr-packages-list\s*\{[\s\S]*?column-gap:\s*21px !important;[\s\S]*?row-gap:\s*21px !important;/);
console.log('packages canonical frame gap: ok');
