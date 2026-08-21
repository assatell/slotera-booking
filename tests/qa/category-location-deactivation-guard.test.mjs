import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('category and location list hide Deactivate when linked packages exist', () => {
  const categories = read('includes/Admin/Views/categories-list.php');
  const locations = read('includes/Admin/Views/locations-list.php');
  assert.match(categories, /linked_package_count[\s\S]*=== 0/);
  assert.match(locations, /linked_package_count[\s\S]*=== 0/);
  assert.match(categories, /In use by %d package/);
  assert.match(locations, /In use by %d package/);
});

test('category and location controllers reject forged deactivate requests while in use', () => {
  const category = read('includes/Admin/Controllers/CategoryController.php');
  const location = read('includes/Admin/Controllers/LocationController.php');
  assert.match(category, /count_linked_packages\(\$id\) > 0[\s\S]*sltr_error=in_use/);
  assert.match(location, /count_linked_packages\(\$id\) > 0[\s\S]*sltr_error=in_use/);
});

test('linked package counts include draft packages so a relationship must be removed first', () => {
  const categoryRepo = read('includes/Infrastructure/Repositories/CategoryRepository.php');
  const locationRepo = read('includes/Infrastructure/Repositories/LocationRepository.php');
  assert.match(categoryRepo, /COUNT\(\*\)[\s\S]*category_id = %d/);
  assert.doesNotMatch(categoryRepo.match(/public function count_linked_packages[\s\S]*?\n    \}/)?.[0] ?? '', /is_active/);
  assert.match(locationRepo, /COUNT\(DISTINCT package_id\)[\s\S]*location_id = %d/);
  assert.doesNotMatch(locationRepo.match(/public function count_linked_packages[\s\S]*?\n    \}/)?.[0] ?? '', /is_active/);
});
