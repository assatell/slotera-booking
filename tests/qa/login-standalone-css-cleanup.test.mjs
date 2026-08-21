import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('standalone feedback surfaces use one canonical Appearance-aware layer', () => {
  assert.match(css, /\/\* Canonical frontend feedback \/ standalone utility surfaces\. \*\//);
  assert.match(css, /\.sltr-loading\s*\{[\s\S]*?border:\s*1px dashed var\(--sltr-card-border/);
  assert.match(css, /\.sltr-loading\s*\{[\s\S]*?background:\s*var\(--sltr-card-bg/);
  assert.match(css, /\.sltr-empty-state\s*\{[\s\S]*?color:\s*var\(--sltr-muted/);
  assert.equal((css.match(/\.sltr-empty-state\s*\{/g) || []).length, 1, 'empty state should have one canonical selector block');
});

test('Client Login surrounding UI uses Slotera tokens while provider colors remain branded', () => {
  assert.match(css, /Canonical Client Login social providers/);
  assert.doesNotMatch(css, /v1\.0\.352|v1\.0\.359/);
  assert.match(css, /\.sltr-login-divider\s*\{[\s\S]*?color:\s*var\(--sltr-muted/);
  assert.match(css, /\.sltr-login-divider::before,[\s\S]*?background:\s*var\(--sltr-card-border/);
  assert.match(css, /\.sltr-social-login-button--google\s*\{[\s\S]*?background:\s*#ffffff/);
  assert.match(css, /\.sltr-social-login-button--facebook\s*\{[\s\S]*?background:\s*#1877f2/);
  assert.match(css, /\.sltr-social-login-button--apple\s*\{[\s\S]*?background:\s*#111827/);
});

test('legacy muted token and hardcoded neutral account badge do not return', () => {
  assert.doesNotMatch(css, /--sltr-muted-text/);
  assert.match(css, /\.sltr-account-status\s*\{[\s\S]*?background:\s*color-mix\([\s\S]*?var\(--sltr-muted/);
});
