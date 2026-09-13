import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..', '..');

function read(relative) {
  return fs.readFileSync(path.join(root, relative), 'utf8');
}

test('production API endpoints are default and test endpoints require explicit safe override', () => {
  const helpers = read('includes/helpers.php');
  const license = read('includes/Application/Services/LicenseService.php');
  const update = read('includes/Application/Services/UpdateService.php');

  assert.match(
    helpers,
    /https:\/\/api\.getslotera\.com\/wp-json\/slotera\/v1\/license/
  );

  assert.match(
    helpers,
    /https:\/\/api\.getslotera\.com\/wp-json\/slotera\/v1\/update/
  );

  assert.match(helpers, /SLTR_LICENSE_API_URL/);
  assert.match(helpers, /SLTR_UPDATE_API_URL/);

  assert.match(
    helpers,
    /str_ends_with\(\$host, '\.getslotera\.com'\)/
  );

  assert.doesNotMatch(license, /license-api-test\.getslotera\.com/);
  assert.doesNotMatch(update, /license-api-test\.getslotera\.com/);

  assert.match(license, /sltr_license_api_url\(\)/);
  assert.match(update, /sltr_update_api_url\(\)/);
});
