import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

const provider = fs.readFileSync(new URL('../../includes/Admin/AdminServiceProvider.php', import.meta.url), 'utf8');

test('packages admin management remains visible in Slotera submenu', () => {
  assert.match(
    provider,
    /add_submenu_page\('slotera', 'Packages', 'Packages', Capabilities::MANAGE_PACKAGES, 'slotera-packages', \[new PackagesPage\(\), 'render'\]\);/,
  );
  assert.doesNotMatch(
    provider,
    /add_submenu_page\(null, 'Packages', 'Packages', Capabilities::MANAGE_PACKAGES, 'slotera-packages'/,
  );
});
