import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('calendar month controls use centered SVG chevrons', () => {
  const view = read('includes/Frontend/Views/booking-form.php');
  const css = read('assets/css/frontend.css');
  assert.match(view, /class="sltr-calendar-prev"[\s\S]{0,200}<svg viewBox="0 0 24 24"/);
  assert.match(view, /class="sltr-calendar-next"[\s\S]{0,200}<svg viewBox="0 0 24 24"/);
  assert.match(css, /\.sltr-calendar \.sltr-calendar-prev,\s*\.sltr-calendar \.sltr-calendar-next\s*{[^}]*display: inline-flex;[^}]*align-items: center;[^}]*justify-content: center;[^}]*padding: 0;/s);
});

test('Fixed multi-day labels are configurable without translation edits', () => {
  const admin = read('includes/Admin/Views/package-form/booking-blocks/fixed.php');
  const manager = read('includes/Application/BookingModeConfiguration/BookingModeConfigurationManager.php');
  const repo = read('includes/Infrastructure/Repositories/PackageRepository.php');
  const view = read('includes/Frontend/Views/booking-form.php');
  const js = read('assets/js/frontend-booking-form.js');
  for (const key of ['full_day_singular_label', 'full_day_plural_label']) {
    assert.match(admin, new RegExp(`mode_config\\[fixed\\]\\[${key}\\]`));
    assert.ok(manager.includes(`'${key}'`));
    assert.ok(repo.includes(`'${key}'`));
  }
  assert.match(view, /data-full-day-singular-label=/);
  assert.match(view, /data-full-day-plural-label=/);
  assert.match(js, /fullDaySingularLabel/);
  assert.match(js, /fullDayPluralLabel/);
});

test('obsolete Package page image control is hidden without clearing legacy data', () => {
  const identity = read('includes/Admin/Views/package-form/sections/identity.php');
  const controller = read('includes/Admin/Controllers/PackageController.php');
  assert.doesNotMatch(identity, /Package page image/);
  assert.doesNotMatch(identity, /name="card_image_id"/);
  assert.match(controller, /post_has\('card_image_id'\)/);
  assert.match(controller, /\$existing_package\['card_image_id'\]/);
  assert.match(controller, /post_has\('card_image_focus'\)/);
});

test('Solo contact frontend uses Google Maps text and icon-only social links', () => {
  const view = read('includes/Frontend/Views/package-detail.php');
  const css = read('assets/css/frontend.css');
  assert.match(view, />Google Maps<\/a>/);
  assert.doesNotMatch(view, /Open in Google Maps/);
  assert.doesNotMatch(view, /esc_html_e\('Open', 'slotera-booking'\)/);
  assert.match(view, /class="sltr-package-social-link"/);
  assert.match(view, /aria-label="<\?php echo esc_attr\(\$sltr_social_labels/);
  assert.match(css, /\.sltr-package-contact-socials \.sltr-package-social-link/);
});
