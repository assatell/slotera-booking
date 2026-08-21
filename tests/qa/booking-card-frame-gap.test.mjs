import fs from 'node:fs';
import assert from 'node:assert/strict';

const css=fs.readFileSync(new URL('../../assets/css/frontend.css',import.meta.url),'utf8');
assert.match(css,/\/\* Booking service cards: canonical Appearance-aware geometry\. \*\//,'booking cards must have one canonical geometry layer');
assert.match(css,/--sltr-card-frame-edge:\s*15px/,'desktop frame edge must remain 15px');
assert.match(css,/column-gap:\s*35px !important/,'15px + 5px visible gap + 15px requires 35px grid gap');
assert.match(css,/row-gap:\s*35px !important/);
assert.ok(!css.includes('v1.0.984 booking card UX'),'historical v1.0.984 booking frame patch must stay folded');
console.log('booking card frame gap geometry: ok');
