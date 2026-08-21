import fs from 'node:fs';
import assert from 'node:assert/strict';

const solo=fs.readFileSync(new URL('../../includes/Admin/Views/package-form/sections/solo-content.php',import.meta.url),'utf8');
const admin=fs.readFileSync(new URL('../../assets/css/admin.css',import.meta.url),'utf8');
const front=fs.readFileSync(new URL('../../assets/css/frontend.css',import.meta.url),'utf8');
const js=fs.readFileSync(new URL('../../assets/js/admin-package-editor.js',import.meta.url),'utf8');

assert.ok(solo.includes('Replace image'));
assert.ok(solo.includes('Use default'));
assert.ok(solo.includes('sltr-contact-image-preview-frame'));
assert.match(admin,/\.sltr-contact-image-preview-frame\s*\{[\s\S]*width:\s*260px[\s\S]*aspect-ratio:\s*16\s*\/\s*9/);
assert.ok(js.includes('sltr-use-default-contact-image'));
assert.ok(js.includes("input.value = '0'"));
assert.match(front,/#sltr-booking[\s\S]*\.sltr-select-button\s*\{[\s\S]*margin-top:\s*auto !important/);
assert.ok(front.includes('Booking service cards: canonical Appearance-aware geometry.'));
assert.match(front,/#sltr-booking[\s\S]*column-gap:\s*35px !important/);
console.log('contact preview + booking card alignment: ok');
