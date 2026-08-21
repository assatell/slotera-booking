import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');
const categories = fs.readFileSync(new URL('../../includes/Frontend/Views/categories-list.php', import.meta.url), 'utf8');

test('categories catalog presentation is owned by canonical frontend CSS', () => {
  assert.match(css, /\/\* Canonical Packages \/ Categories catalog \*\//);
  assert.match(css, /\.sltr-categories-page \{[\s\S]*?width: var\(--sltr-booking-form-width, 100%\);[\s\S]*?max-width: var\(--sltr-booking-form-max-width, 1280px\);/);
  assert.match(css, /\.sltr-categories-page \.sltr-category-packages\.sltr-packages-list \{[\s\S]*?grid-template-columns: repeat\(var\(--sltr-package-columns-desktop, 3\), minmax\(0, 1fr\)\);/);
  assert.doesNotMatch(categories, /sltr-categories-catalog-inline-css/);
  assert.doesNotMatch(categories, /<style[\s>]/i);
  const canonicalStart = css.indexOf('/* Canonical Packages / Categories catalog */');
  const canonicalEnd = css.indexOf('/* Local SEO content */', canonicalStart);
  assert.ok(canonicalStart >= 0 && canonicalEnd > canonicalStart);
  assert.doesNotMatch(css.slice(canonicalStart, canonicalEnd), /!important/);
});

test('categories preserve dynamic Appearance and grid variables on wrapper', () => {
  assert.match(categories, /style="<\?php echo esc_attr\(\$style_vars\); \?>"/);
  assert.match(categories, /--sltr-package-columns-desktop:/);
  assert.match(categories, /--sltr-package-columns-tablet:/);
  assert.match(categories, /--sltr-package-columns-mobile:/);
});

test('mobile Package Solo shellless behavior is owned by one canonical block', () => {
  assert.match(css, /\/\* Canonical mobile Package Solo \/ shellless layout\. \*\//);
  assert.doesNotMatch(css, /v1\.0\.415 mobile package pages:/);
  assert.doesNotMatch(css, /v1\.0\.416 package catalog\/solo pages:/);
  assert.doesNotMatch(css, /v1\.0\.417 mobile package pages:/);
  assert.match(css, /@media \(max-width: 780px\) \{[\s\S]*?\.sltr-packages-list-shellless,[\s\S]*?width: calc\(100vw - 4px\) !important;[\s\S]*?gap: 14px !important;/);
  assert.match(css, /\.sltr-package-page-shellless \.sltr-package-slider,[\s\S]*?width: calc\(100vw - 4px\) !important;/);
});

test('legacy category catalog CSS is removed and current categories page owns responsive grid', () => {
  assert.doesNotMatch(css, /v1\.0\.329 category catalog shortcode/);
  assert.doesNotMatch(css, /\.sltr-categories-catalog\b/);
  assert.match(css, /@media \(max-width: 900px\) \{[\s\S]*?\.sltr-categories-page \.sltr-category-packages\.sltr-packages-list \{[\s\S]*?--sltr-package-columns-tablet/);
  assert.match(css, /@media \(max-width: 640px\) \{[\s\S]*?\.sltr-categories-page \.sltr-category-packages\.sltr-packages-list \{[\s\S]*?--sltr-package-columns-mobile/);
});

test('Package Solo 780px gallery and shellless behavior share one canonical media layer', () => {
  const blocks = css.match(/@media \(max-width: 780px\) \{/g) || [];
  const canonicalStart = css.indexOf('/* Canonical mobile Package Solo / shellless layout. */');
  const canonicalEnd = css.indexOf('.sltr-package-slider-slide,', canonicalStart);
  const canonical = css.slice(canonicalStart, canonicalEnd);
  assert.match(canonical, /aspect-ratio: 4 \/ 3 !important;/);
  assert.match(canonical, /\.sltr-packages-list-shellless,/);
  assert.match(canonical, /aspect-ratio: 16 \/ 10 !important;/);
  assert.ok(blocks.length >= 1);
});
