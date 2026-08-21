import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('legacy package detail page CSS stays removed', () => {
  assert.ok(!css.includes('v1.0.9 package detail page'));
  for (const selector of [
    '.sltr-package-detail {',
    '.sltr-package-detail-card {',
    '.sltr-package-detail-meta {',
    '.sltr-package-detail-description {',
    '.sltr-package-detail-button {'
  ]) {
    assert.ok(!css.includes(selector), `legacy selector returned: ${selector}`);
  }
});
