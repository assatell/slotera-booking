import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const form = fs.readFileSync(new URL('../../includes/Admin/Views/package-form.php', import.meta.url), 'utf8');
const solo = fs.readFileSync(new URL('../../includes/Admin/Views/package-form/sections/solo-content.php', import.meta.url), 'utf8');
const js = fs.readFileSync(new URL('../../assets/js/admin-package-editor.js', import.meta.url), 'utf8');
const repo = fs.readFileSync(new URL('../../includes/Infrastructure/Repositories/PackageRepository.php', import.meta.url), 'utf8');
const shortcode = fs.readFileSync(new URL('../../includes/Frontend/Shortcodes/BookingShortcode.php', import.meta.url), 'utf8');
const controller = fs.readFileSync(new URL('../../includes/Admin/Controllers/PackageController.php', import.meta.url), 'utf8');
const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');
const adminCss = fs.readFileSync(new URL('../../assets/css/admin.css', import.meta.url), 'utf8');

test('New/Edit Package has three top-level sections and Save actions in first two', () => {
  assert.match(form, /id="sltr-package-settings"[\s\S]*?<h2><\?php esc_html_e\('Package settings'/);
  assert.match(form, /id="sltr-solo-page-settings"[\s\S]*?<h2><\?php esc_html_e\('Solo page settings'/);
  assert.match(form, /id="sltr-booking-blocks"[\s\S]*?<h2><\?php esc_html_e\('Booking blocks'/);
  assert.match(form, /name="sltr_save_section" value="package-settings"/);
  assert.match(form, /name="sltr_save_section" value="solo-page-settings"/);
  const packageStart = form.indexOf('id="sltr-package-settings"');
  const packageEnd = form.indexOf('</section>', packageStart);
  const toggleAt = form.indexOf('class="sltr-package-solo-toggle"');
  const soloStart = form.indexOf('id="sltr-solo-page-settings"');
  assert.ok(packageStart >= 0 && packageEnd > packageStart);
  assert.ok(toggleAt > packageEnd && toggleAt < soloStart);
  assert.equal(form.slice(packageStart, packageEnd).includes('id="sltr-package-solo-enabled"'), false);
  assert.match(form, /id="sltr-package-settings" class="sltr-settings-card sltr-package-settings-block"/);
  assert.match(form, /id="sltr-solo-page-settings" class="sltr-settings-card sltr-solo-settings-block"/);
  assert.match(form, /id="sltr-booking-blocks" class="sltr-settings-card sltr-booking-settings-block"/);
  assert.doesNotMatch(form, /sltr-package-settings-block" style=/);
  assert.match(adminCss, /\.sltr-package-settings-block,[\s\S]*?background:\s*#fff;[\s\S]*?border:\s*1px solid #dcdcde;[\s\S]*?border-radius:\s*8px;/);
  assert.doesNotMatch(solo, /id="sltr-package-solo-enabled"/);
  assert.match(controller, /\['package-settings', 'solo-page-settings'\][\s\S]*?#sltr-/);
});

test('Solo page right content can select image slider or WordPress Media Library video', () => {
  assert.match(solo, /sltr-activate-package-media[\s\S]*?Insert image\/slider/);
  assert.match(solo, /sltr-activate-package-video[\s\S]*?Insert video/);
  assert.match(solo, /id="sltr-package-media-video-id"/);
  assert.match(js, /library:\s*\{\s*type:\s*'video'\s*\}/);
  assert.match(js, /allowedVideoMimes = \['video\/mp4', 'video\/webm', 'video\/ogg'\]/);
  assert.match(solo, /Supported video formats: MP4, WebM and Ogg/);
  assert.match(solo, /QuickTime\/MOV files are not supported/);
  assert.match(js, /video_id:\s*mediaVideoId/);
  assert.match(repo, /'type' => \$type,[\s\S]*?'video_id' => \$video_id/);
});

test('Package media shortcode renders selected video safely with native controls', () => {
  assert.match(shortcode, /\$media_type === 'video'/);
  assert.match(shortcode, /\['video\/mp4', 'video\/webm', 'video\/ogg'\]/);
  assert.match(shortcode, /<video controls playsinline preload="metadata"[\s\S]*?autoplay muted/);
  assert.match(shortcode, /esc_url\(\$video_url\)/);
  assert.match(css, /\.sltr-package-media-video video\s*\{[\s\S]*?width:\s*100%;[\s\S]*?aspect-ratio:\s*16 \/ 9;/);
});
