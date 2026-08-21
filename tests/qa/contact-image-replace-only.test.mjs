import fs from 'node:fs';
import assert from 'node:assert/strict';

const solo=fs.readFileSync(new URL('../../includes/Admin/Views/package-form/sections/solo-content.php',import.meta.url),'utf8');
const js=fs.readFileSync(new URL('../../assets/js/admin-package-editor.js',import.meta.url),'utf8');

assert.ok(solo.includes('Replace image'));
assert.ok(!solo.includes("esc_html_e('Use default image'"));
assert.ok(!solo.includes("esc_html_e('Select image'"));
const contactStart=solo.indexOf("esc_html_e('Contact block image'");
const mapStart=solo.indexOf("esc_html_e('Google Map'", contactStart);
const contactImageBlock=solo.slice(contactStart, mapStart > contactStart ? mapStart : solo.length);
assert.ok(!contactImageBlock.includes('sltr-media-clear'));
assert.ok(solo.includes('sltr-contact-image-preview'));
assert.ok(js.includes('sltr-replace-contact-image'));
assert.ok(js.includes('wp.media({'));
assert.ok(js.includes('multiple: false'));
assert.ok(js.includes('preview.src ='));
console.log('contact image replace-only UI: ok');
