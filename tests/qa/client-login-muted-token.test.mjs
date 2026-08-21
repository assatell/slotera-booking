import fs from 'node:fs';
import assert from 'node:assert/strict';

const css=fs.readFileSync(new URL('../../assets/css/frontend.css',import.meta.url),'utf8');

assert.ok(!css.includes('var(--sltr-muted-text'), 'legacy --sltr-muted-text token must not return');
assert.ok(css.includes('.sltr-login-divider'), 'Client Login divider selector must exist');
assert.ok(css.includes('color: var(--sltr-muted, #64748b);'), 'Client Login divider must use canonical --sltr-muted');
console.log('client login muted token: ok');
