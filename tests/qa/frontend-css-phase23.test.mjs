import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('frontend.css no longer uses version-labelled ownership comments', () => {
  assert.doesNotMatch(css, /\/\*[^*]*v1\.0\.\d+/i);
});

test('remaining live feature owners are explicitly canonical', () => {
  for (const marker of [
    'Canonical coupon components.',
    'Canonical checkout payment-choice components.',
    'Canonical booking progress and availability feedback components.',
    'Canonical Dark preset frontend token refinements.',
    'Canonical mobile booking service-card containment.'
  ]) {
    assert.ok(css.includes(marker), `missing canonical ownership marker: ${marker}`);
  }
});
