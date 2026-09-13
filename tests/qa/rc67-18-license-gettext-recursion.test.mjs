import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');

test('RC67.18 license policy is safe inside gettext filters', () => {
  const policy = read('includes/Application/Services/LicenseFeaturePolicy.php');
  const whiteLabel = read('includes/Application/Services/WhiteLabelService.php');

  const allowsMatch = policy.match(
    /public function allows\(string \$feature\): bool\s*\{([\s\S]*?)\n    \}\n\n    public function status/
  );

  assert.ok(allowsMatch, 'LicenseFeaturePolicy::allows() must be detectable');

  const allowsBody = allowsMatch[1];

  // White Label calls enabled() from its gettext filter, so allows() must not
  // call LicenseService::status(): status() builds translated labels via __()
  // and would re-enter gettext indefinitely.
  assert.ok(whiteLabel.includes("add_filter('gettext'"));
  assert.ok(whiteLabel.includes('$this->enabled()'));

  assert.ok(
    !allowsBody.includes('$this->license->status()'),
    'allows() must not call translated LicenseService::status()'
  );

  assert.ok(
    allowsBody.includes('$this->license->data()'),
    'allows() must read the raw stored signed license state'
  );

  assert.ok(
    allowsBody.includes("['license_status']"),
    'allows() must use raw license_status'
  );

  assert.ok(
    allowsBody.includes("['active', 'trial', 'grace']"),
    'licensed-state semantics must remain unchanged'
  );
});