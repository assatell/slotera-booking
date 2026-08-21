import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const here = dirname(fileURLToPath(import.meta.url));
const root = join(here, '..', '..');
const read = (path) => readFileSync(join(root, path), 'utf8');

test('admin logo is injected centrally on every Slotera admin screen', () => {
  const service = read('includes/Application/Services/WhiteLabelService.php');
  assert.match(service, /is_slotera_admin_context\(\)/);
  assert.match(service, /add_action\('admin_footer', \[\$this, 'print_admin_branding_script'\], 5\)/);
  assert.match(service, /document\.querySelector\([\s\S]*?#wpbody-content h1/);
  assert.match(service, /title\.querySelector\('\.sltr-white-label-logo'\)/);
  assert.match(service, /title\.insertBefore\(badge, title\.firstChild\)/);
});
