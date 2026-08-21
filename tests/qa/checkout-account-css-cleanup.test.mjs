import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('Checkout and Thank You are owned by one canonical Appearance layer', () => {
  assert.match(css, /\/\* Canonical Checkout \/ Thank You Appearance layer\. \*\//);
  assert.doesNotMatch(css, /v1\.0\.985/);
  assert.match(css, /\.sltr-thank-you-card\s*\{[\s\S]*?background:\s*var\(--sltr-form-bg, #ffffff\)/);
  assert.match(css, /\.sltr-thank-you-lead\s*\{[\s\S]*?font-size:\s*16px/);
  assert.match(css, /\.sltr-checkout dt\s*\{[\s\S]*?var\(--sltr-muted, #64748b\)/);
});

test('Client Account no longer depends on the v1.0.368 Appearance patch', () => {
  assert.match(css, /\/\* Canonical Client Account \/ account-auth components\. \*\//);
  assert.doesNotMatch(css, /v1\.0\.368/);
  assert.match(css, /\.sltr-login-form input,[\s\S]*?\.sltr-account-reschedule input\s*\{[\s\S]*?background:\s*var\(--sltr-card-bg, #ffffff\)/);
  assert.match(css, /\.sltr-account-booking,[\s\S]*?\.sltr-account-detail\s*\{[\s\S]*?color-mix\(in srgb, var\(--sltr-card-bg/);
});

test('Public page shell ownership is canonical rather than version-patched', () => {
  assert.match(css, /\/\* Canonical public page shells: WordPress owns page background; Slotera Appearance owns components\. \*\//);
  assert.doesNotMatch(css, /v1\.0\.986/);
});
