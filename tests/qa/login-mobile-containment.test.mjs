import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('Client Login form and email input stay inside the account panel', () => {
  assert.match(css, /\.sltr-login-form\s*\{[\s\S]*?width:\s*100%;[\s\S]*?max-width:\s*440px;[\s\S]*?min-width:\s*0;/);
  assert.match(css, /\.sltr-login-form input,[\s\S]*?\.sltr-account-reschedule input\s*\{[\s\S]*?box-sizing:\s*border-box;[\s\S]*?width:\s*100%;[\s\S]*?max-width:\s*100%;[\s\S]*?min-width:\s*0;/);
});

test('Client Login submit button is width-contained on mobile', () => {
  assert.match(css, /Client Login containment:[\s\S]*?\.sltr-login-form > \.sltr-button,[\s\S]*?button\.sltr-button\s*\{[\s\S]*?box-sizing:\s*border-box;[\s\S]*?width:\s*100%;[\s\S]*?max-width:\s*100%;[\s\S]*?min-width:\s*0;/);
});

test('Client Account sign-in button and controls stay inside the account panel', () => {
  assert.match(css, /Client Account containment:[\s\S]*?\.sltr-account-panel \.sltr-button,[\s\S]*?\.sltr-account-panel button,[\s\S]*?\.sltr-account-panel input,[\s\S]*?\.sltr-account-panel select,[\s\S]*?\.sltr-account-panel textarea\s*\{[\s\S]*?box-sizing:\s*border-box;[\s\S]*?max-width:\s*100%;[\s\S]*?min-width:\s*0;/);
});
