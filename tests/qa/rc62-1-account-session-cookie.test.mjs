import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../..');
const service = fs.readFileSync(path.join(root, 'includes/Application/Services/AccountMagicLinkService.php'), 'utf8');

test('account and confirmation cookies are host-only and valid across the public site', () => {
  const setters = [...service.matchAll(/setcookie\((?:self::CONFIRM_COOKIE|self::COOKIE),[\s\S]*?\]\);/g)].map(m => m[0]);
  assert.equal(setters.length, 2);
  for (const setter of setters) {
    assert.match(setter, /'path'\s*=>\s*'\/'/);
    assert.doesNotMatch(setter, /'domain'\s*=>/);
    assert.match(setter, /'httponly'\s*=>\s*true/);
  }
});

test('account cookies stay Secure when the canonical home URL is HTTPS behind a proxy', () => {
  assert.match(service, /'secure'\s*=>\s*is_ssl\(\)\s*\|\|\s*wp_parse_url\(home_url\(\), PHP_URL_SCHEME\) === 'https'/);
});
