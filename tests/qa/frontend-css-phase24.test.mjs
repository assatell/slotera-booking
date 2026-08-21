import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('frontend.css no longer uses any version-labelled ownership comments', () => {
  assert.doesNotMatch(css, /\/\*[^*]*\bv\d+\.\d+(?:\.\d+)?/i);
});

test('booking shell has one canonical non-responsive base owner', () => {
  const marker = '/* Canonical booking shell and package-card grid foundation. */';
  const start = css.indexOf(marker);
  const end = css.indexOf('@media (max-width: 900px)', start);
  assert.ok(start >= 0 && end > start);
  const owner = css.slice(start, end);
  const matches = owner.match(/\.sltr-booking\s*\{/g) || [];
  assert.equal(matches.length, 1);
  assert.match(owner, /\.sltr-booking\s*\{[^}]*font-family:\s*inherit;/s);
  assert.doesNotMatch(css, /\.sltr-booking\s*\{\s*margin-left:\s*auto;\s*margin-right:\s*auto;\s*\}/s);
});
