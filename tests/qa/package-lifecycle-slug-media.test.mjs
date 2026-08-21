import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (path) => fs.readFileSync(new URL('../../' + path, import.meta.url), 'utf8');
const packageController = read('includes/Admin/Controllers/PackageController.php');
const categoryController = read('includes/Admin/Controllers/CategoryController.php');
const locationController = read('includes/Admin/Controllers/LocationController.php');
const packageRepo = read('includes/Infrastructure/Repositories/PackageRepository.php');
const categoryRepo = read('includes/Infrastructure/Repositories/CategoryRepository.php');
const locationRepo = read('includes/Infrastructure/Repositories/LocationRepository.php');
const packageIdentity = read('includes/Admin/Views/package-form/sections/identity.php');
const categoryForm = read('includes/Admin/Views/category-form.php');
const locationForm = read('includes/Admin/Views/location-form.php');
const packageList = read('includes/Admin/Views/packages-list.php');
const categoryList = read('includes/Admin/Views/categories-list.php');
const locationList = read('includes/Admin/Views/locations-list.php');
const solo = read('includes/Admin/Views/package-form/sections/solo-content.php');
const editor = read('assets/js/admin-package-editor.js');
const shortcode = read('includes/Frontend/Shortcodes/BookingShortcode.php');

test('package/category/location slugs are unique and immutable after first save', () => {
  for (const repo of [packageRepo, categoryRepo, locationRepo]) {
    assert.match(repo, /public function slug_exists\(/);
    assert.doesNotMatch(repo, /function unique_slug\(/);
  }
  assert.doesNotMatch(packageController, /sltr_error=slug_locked/);
  assert.match(packageController, /\$requested_slug = \$saved_slug/);
  assert.match(packageController, /slug_exists\(\$requested_slug, \$id\)/);
  for (const controller of [categoryController, locationController]) {
    assert.match(controller, /sltr_error=slug_locked/);
    assert.match(controller, /sltr_error=slug_exists/);
  }
  assert.match(packageController, /sltr_error=slug_exists/);
  for (const form of [packageIdentity, categoryForm, locationForm]) {
    assert.match(form, /readonly aria-readonly=/);
    assert.match(form, /Generated/);
    assert.match(form, /slug must be unique/i);
  }
});

test('entity lifecycle is Active to Draft to Restore with no Delete list action', () => {
  for (const controller of [packageController, categoryController, locationController]) {
    assert.match(controller, /admin_post_sltr_deactivate_/);
    assert.match(controller, /admin_post_sltr_restore_/);
    assert.doesNotMatch(controller, /admin_post_sltr_delete_(?:package|category|location)/);
  }
  for (const list of [packageList, categoryList, locationList]) {
    assert.match(list, /Deactivate/);
    assert.match(list, /Restore/);
    assert.match(list, /Draft/);
    assert.doesNotMatch(list, /sltr_delete_(?:package|category|location)/);
  }
});

test('solo right content enforces image-slider versus video exclusivity and supports autoplay', () => {
  assert.match(solo, /id="sltr-activate-package-images"/);
  assert.match(solo, /id="sltr-activate-package-video"/);
  assert.match(solo, /id="sltr-package-media-video-autoplay"/);
  assert.match(editor, /activateImages\.disabled = hasVideo/);
  assert.match(editor, /activateVideo\.disabled = hasImages/);
  assert.match(editor, /autoplay:\s*!!\(videoAutoplay/);
  assert.match(packageRepo, /if \(\$type === 'video'\)[\s\S]*?\$image_ids = ''/);
  assert.match(packageRepo, /'autoplay' => \$type === 'video'/);
  assert.match(shortcode, /\$video_autoplay = !empty\(\$media\['autoplay'\]\)/);
  assert.match(shortcode, /autoplay muted/);
});
