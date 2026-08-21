import fs from 'node:fs';
import assert from 'node:assert/strict';

const css=fs.readFileSync(new URL('../../assets/css/frontend.css',import.meta.url),'utf8');
assert.ok(css.includes('Booking service cards: canonical Appearance-aware geometry.'));
assert.ok(css.includes('column-gap: 35px !important;'));
assert.ok(css.includes('row-gap: 35px !important;'));
assert.ok(css.includes('width: 100% !important;'));
assert.ok(css.includes('margin: 0 !important;'));
assert.ok(!css.includes('v1.0.1024 Booking card CTA alignment'));
assert.ok(!css.includes('v1.0.1026 Booking card visible frame spacing'));
console.log('booking card canonical spacing: ok');
