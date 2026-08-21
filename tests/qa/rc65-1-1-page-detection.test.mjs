import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '../..');
const repo = readFileSync(resolve(root, 'includes/Infrastructure/Repositories/SettingsRepository.php'), 'utf8');

test('RC65.1.1 system page validation does not depend on runtime shortcode registration', () => {
  assert.match(repo, /Do not depend on runtime shortcode registration here/);
  assert.match(repo, /preg_quote\(\$shortcode, '\/'\)/);
  assert.match(repo, /preg_match\(/);
  assert.doesNotMatch(repo, /if \(!has_shortcode\(\$content, \$shortcode\)\)/);
});
