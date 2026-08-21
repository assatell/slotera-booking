import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { applyUpdateUri, normalizeUpdateUri } from '../../tools/update-uri.mjs';

const plugin = fs.readFileSync(new URL('../../slotera-booking.php', import.meta.url), 'utf8');

test('RC66.12.8 leaves Update URI intentionally unbound until an official endpoint exists', () => {
  assert.doesNotMatch(plugin, /^ \* Update URI:/m);
  assert.match(plugin, /define\('SLTR_UPDATE_URI', ''\);/);
  assert.match(plugin, /function sltr_update_uri\(\): string/);
  assert.match(plugin, /apply_filters\('sltr_update_uri', \$uri\)/);
});

test('release tooling injects one canonical HTTPS Update URI and synchronizes runtime constant', () => {
  const fixture = `<?php\n/**\n * Plugin Name: Slotera Booking\n * Version: 1.0.1038\n */\ndefine('SLTR_UPDATE_URI', '');\n`;
  const result = applyUpdateUri(fixture, 'https://updates.slotera.example/plugin/slotera-booking');
  assert.equal(result.uri, 'https://updates.slotera.example/plugin/slotera-booking');
  assert.match(result.source, /^ \* Update URI: https:\/\/updates\.slotera\.example\/plugin\/slotera-booking$/m);
  assert.match(result.source, /define\('SLTR_UPDATE_URI', 'https:\/\/updates\.slotera\.example\/plugin\/slotera-booking'\);/);
  assert.equal((result.source.match(/^ \* Update URI:/gm) || []).length, 1);
});

test('release tooling fails closed for unsafe or non-absolute Update URI values', () => {
  for (const value of [
    'http://updates.example.test/slotera',
    '/relative/update',
    'https://user:pass@example.test/update',
    'https://example.test/update#fragment',
  ]) {
    assert.throws(() => normalizeUpdateUri(value));
  }
});
