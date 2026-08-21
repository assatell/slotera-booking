import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

function countExact(selector) {
  const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return (css.match(new RegExp(`(^|\\n)${escaped}\\s*\\{`, 'g')) || []).length;
}

test('Booking heading and input geometry live in their canonical base owners', () => {
  assert.equal(countExact('.sltr-step h3'), 1);
  assert.equal(countExact('.sltr-step input'), 1);
  assert.match(css, /\.sltr-step h3\s*\{[^}]*text-align:\s*center;/s);
  assert.match(css, /\.sltr-step input\s*\{[^}]*margin:\s*10px auto;[^}]*text-align:\s*center;/s);
});

test('calendar centering is owned by the canonical calendar block', () => {
  assert.match(css, /\.sltr-calendar\s*\{[^}]*margin-left:\s*auto;[^}]*margin-right:\s*auto;/s);
  assert.doesNotMatch(css, /\.sltr-calendar,\s*\n\.sltr-step input\s*\{/);
});
