import fs from 'node:fs';
import assert from 'node:assert/strict';

const solo=fs.readFileSync(new URL('../../includes/Admin/Views/package-form/sections/solo-content.php',import.meta.url),'utf8');
const controller=fs.readFileSync(new URL('../../includes/Admin/Controllers/PackageController.php',import.meta.url),'utf8');
const js=fs.readFileSync(new URL('../../assets/js/admin-package-editor.js',import.meta.url),'utf8');
const view=fs.readFileSync(new URL('../../includes/Frontend/Views/package-detail.php',import.meta.url),'utf8');

assert.ok(solo.includes('solo_top_text_active'));
assert.ok(solo.includes('sltr-activate-text-block'));
assert.ok(controller.includes("post_bool('solo_top_text_active')"));
assert.ok(js.includes("field.value = '[slotera_package_text_block]'"));
assert.ok(js.includes('sltr-replace-contact-image'));
assert.ok(solo.includes("SLTR_PLUGIN_URL . 'assets/images/contact-block-default.webp'"));
assert.ok(view.includes("SLTR_PLUGIN_URL . 'assets/images/contact-block-default.webp'"));
console.log('fixed Solo module hotfix: ok');
