import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('light preset calendars keep month chevrons visible', () => {
  const css = read('assets/css/frontend.css');
  for (const color of ['#0f172a', '#431407', '#111827']) {
    assert.match(css, new RegExp(`--sltr-calendar-nav-color: ${color}`));
  }
  assert.match(css, /color: var\(--sltr-calendar-nav-color/);
  assert.match(css, /\.sltr-calendar-prev svg[^}]*stroke: currentColor !important/);
});

test('package cards use stable semantic rows and put info beside the CTA', () => {
  const booking = read('includes/Frontend/Views/booking-form.php');
  const packages = read('includes/Frontend/Views/packages-list.php');
  const css = read('assets/css/frontend.css');
  for (const view of [booking, packages]) {
    assert.match(view, /sltr-package-card-media<\?php echo \$[a-z_]+ \? '' : ' is-empty';/);
    assert.match(view, /sltr-package-card-meta-row/);
    assert.match(view, /sltr-package-card-promo-row/);
    assert.match(view, /sltr-package-card-price-row/);
    assert.match(view, /sltr-package-card-link-row/);
    assert.match(view, /sltr-package-card-actions[\s\S]*?sltr-select-button[\s\S]*?sltr-package-info-button/);
    assert.ok(view.indexOf('sltr-package-card-price-row') < view.indexOf('sltr-package-card-promo-row'));
  }
  assert.match(css, /Canonical package-card row grid/);
  assert.match(css, /grid-auto-rows: 1fr/);
  assert.match(css, /\.sltr-package\.sltr-package-card \{[\s\S]*?height: 100%/);
  assert.match(css, /\.sltr-package-card-promo-row\.is-empty \{\s*display: none;/);
  assert.match(css, /\.sltr-package-card-promo-row \{[^}]*min-height: 0;/);
  assert.match(css, /\.sltr-package-card-link-row \{ min-height: 2\.7em/);
  assert.match(css, /\.sltr-package-card-actions \.sltr-package-info-button/);
  assert.match(css, /gap: 18px;[\s\S]*?min-height: 46px;[\s\S]*?transform: translateX\(4px\);/);
});

test('Solo images request full screen and autoplay video has no custom sound button', () => {
  const shortcode = read('includes/Frontend/Shortcodes/BookingShortcode.php');
  const cards = read('assets/js/frontend-package-cards.js');
  const bookingJs = read('assets/js/frontend-booking-form.js');
  assert.match(shortcode, /sltr-media-fullscreen-icon/);
  assert.doesNotMatch(shortcode, /sltr-media-zoom-icon|Turn on sound|data-sltr-video-unmute/);
  assert.match(cards, /requestFullscreen/);
  assert.match(cards, /fullscreenchange/);
  assert.match(cards, /document\.exitFullscreen/);
  assert.doesNotMatch(bookingJs, /data-sltr-video-unmute/);
});
