import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

test('Phase 31 keeps booking active/selected state ownership canonical', () => {
  assert.equal((css.match(/^\.sltr-progress span\.is-active\s*\{/gm) || []).length, 1,
    'progress active state should have one non-responsive canonical owner');
  assert.equal((css.match(/^\.sltr-package\.is-selected\s*\{/gm) || []).length, 1,
    'selected booking package should have one non-responsive canonical owner');
});
