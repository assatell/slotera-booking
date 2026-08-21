import fs from 'node:fs';
import assert from 'node:assert/strict';

const css=fs.readFileSync(new URL('../../assets/css/frontend.css',import.meta.url),'utf8');
const view=fs.readFileSync(new URL('../../includes/Frontend/Views/package-detail.php',import.meta.url),'utf8');

assert.ok(css.includes('Canonical stacked Solo DOM order.'));
assert.ok(!css.includes('v1.0.1027 Stacked Solo DOM order'));
assert.match(css,/sltr-package-landing-layout-stacked\.sltr-package-landing-has-top-content[\s\S]*sltr-package-landing-down-content[\s\S]*grid-row:\s*auto !important/);

const contentPos=view.indexOf('class="sltr-package-landing-content"');
const downPos=view.indexOf('class="sltr-package-landing-down-content');
assert.ok(contentPos >= 0 && downPos > contentPos);
console.log('stacked Solo order: ok');
