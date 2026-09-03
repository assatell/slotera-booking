import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (p) => readFileSync(new URL(`../../${p}`, import.meta.url), 'utf8');

test('RC66.9 keeps Admin UI English-only without shipping locale payload', () => {
  const admin = read('includes/Application/Services/Translations/AdminTranslationStrings.php');
  for (const locale of ['ru_RU','de_DE','fr_FR','et','pt_BR','es_ES']) {
    assert.doesNotMatch(admin, new RegExp(`'${locale}'\\s*=>`));
  }
  assert.match(admin, /'group'\s*=>\s*'admin'/);
  assert.match(admin, /'default'\s*=>/);
});

test('RC66.9 translation lock contains only customer-facing groups', () => {
  const lock = JSON.parse(read('languages/slotera-booking.translation-keys.lock.json'));
  assert.equal(lock.groups.admin, undefined);
  assert.equal(lock.groups.frontend, 297);
  assert.equal(lock.groups.emails, 73);
  assert.equal(lock.key_count, 370);
  for (const meta of Object.values(lock.keys)) assert.notEqual(meta.group, 'admin');
});

test('RC66.9 leaves frontend/email freeze baselines unchanged', () => {
  const freeze = read('includes/config/translation-freeze.php');
  assert.match(freeze, /'items' => 383/);
  assert.match(freeze, /'items' => 154/);
});

test('RC66.9 freezes only editable translation groups', () => {
  const freeze = read('includes/Application/Services/TranslationKeyFreeze.php');
  assert.match(freeze, /array_keys\(TranslationRegistry::groups\(\)\)/);
  assert.match(freeze, /if \(!isset\(\$frozen_groups\[\$group\]\)\)/);
});
