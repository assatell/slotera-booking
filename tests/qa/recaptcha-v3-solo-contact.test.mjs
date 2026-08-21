import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('Access & Protection exposes reCAPTCHA v3 without replacing v2', () => {
  const page = read('includes/Admin/Pages/SecurityPage.php');
  const settings = read('includes/Infrastructure/Repositories/SettingsRepository.php');
  assert.match(page, /Google reCAPTCHA v2 Checkbox/);
  assert.match(page, /Google reCAPTCHA v3/);
  assert.match(page, /security_recaptcha_v3_threshold/);
  assert.match(settings, /recaptcha_v3/);
  assert.match(settings, /security_recaptcha_v3_threshold/);
});

test('reCAPTCHA v3 verifies success, action and score server-side', () => {
  const spam = read('includes/Application/Services/BookingSpamProtectionService.php');
  assert.match(spam, /verify_recaptcha_v3/);
  assert.match(spam, /slotera_contact/);
  assert.match(spam, /slotera_booking/);
  assert.match(spam, /\$body\['action'\]/);
  assert.match(spam, /\$body\['score'\]/);
  assert.match(spam, /\$score < \$threshold/);
  assert.match(spam, /https:\/\/www\.google\.com\/recaptcha\/api\/siteverify/);
});

test('nested Solo contact shortcode preserves CAPTCHA attributes through public-content sanitization', () => {
  const shortcode = read('includes/Frontend/Shortcodes/BookingShortcode.php');
  const contact = read('includes/Frontend/Views/contact-form.php');
  const sanitizer = read('includes/Core/HtmlSanitizer.php');
  assert.match(shortcode, /enqueue_contact_captcha_assets/);
  assert.match(shortcode, /sltr-recaptcha-v3-api/);
  assert.match(shortcode, /sltr-frontend-recaptcha-v3/);
  assert.match(contact, /data-sltr-recaptcha-v3-action="slotera_contact"/);
  assert.match(contact, /data-sltr-recaptcha-v3-token/);
  assert.match(contact, /class="g-recaptcha" data-sitekey=/);
  assert.match(contact, /class="cf-turnstile" data-sitekey=/);
  assert.match(sanitizer, /'data-sitekey' => true/);
  assert.match(sanitizer, /'data-sltr-recaptcha-v3-action' => true/);
  assert.match(sanitizer, /'data-sltr-recaptcha-v3-token' => true/);
});

test('booking generates a fresh v3 token for the booking action', () => {
  const js = read('assets/js/frontend-booking-form.js');
  const plugin = read('includes/Core/Plugin.php');
  assert.match(js, /sltrGetRecaptchaV3Token\('slotera_booking'\)/);
  assert.match(js, /recaptchaV3Response/);
  assert.match(plugin, /captcha_provider/);
  assert.match(plugin, /sltr-frontend-recaptcha-v3/);
});

test('Solo contact uses an external Google Maps link and never renders a map iframe', () => {
  const admin = read('includes/Admin/Views/package-form/sections/solo-content.php');
  const repo = read('includes/Infrastructure/Repositories/PackageRepository.php');
  const frontend = read('includes/Frontend/Views/package-detail.php');
  assert.match(admin, /Google Maps link/);
  assert.match(admin, /normal Google Maps page\/share link/);
  assert.match(repo, /sanitize_google_maps_link/);
  assert.match(frontend, /data-sltr-google-maps-popup/);
  assert.doesNotMatch(frontend, /sltr-package-contact-map"><iframe/);
});
