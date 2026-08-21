import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('Solo contact block stores address plus an external Google Maps page link', () => {
  const view = read('includes/Admin/Views/package-form/sections/solo-content.php');
  assert.match(view, /id="sltr-contact-address"/);
  assert.match(view, /Google Maps link/);
  assert.match(view, /https:\/\/maps\.app\.goo\.gl\/\.\.\.\.\.\.\.\.\.\.\.\./);
  assert.match(view, /does not embed Google Maps/);
});

test('Solo contact block has a short social platform dropdown and URL rows', () => {
  const view = read('includes/Admin/Views/package-form/sections/solo-content.php');
  for (const platform of ['Instagram','Facebook','LinkedIn','X (Twitter)','YouTube','TikTok']) {
    assert.ok(view.includes(platform), `missing ${platform}`);
  }
  assert.match(view, /sltr-add-contact-social/);
  assert.match(view, /sltr-contact-social-platform/);
  assert.match(view, /sltr-contact-social-url/);
});

test('contact JSON sanitizer preserves old contact rows and allowlists social links', () => {
  const repo = read('includes/Infrastructure/Repositories/PackageRepository.php');
  assert.match(repo, /\$social_platforms = \['instagram', 'facebook', 'linkedin', 'x', 'youtube', 'tiktok'\]/);
  assert.match(repo, /esc_url_raw\(\(string\) \(\$row\['url'\]/);
  assert.match(repo, /\['type' => 'address'/);
  assert.match(repo, /\['type' => 'contact'/);
  assert.match(repo, /\['type' => 'social'/);
});

test('frontend renders social links separately and safely', () => {
  const view = read('includes/Frontend/Views/package-detail.php');
  assert.match(view, /sltr-package-contact-socials/);
  assert.match(view, /target="_blank" rel="noopener noreferrer"/);
  assert.match(view, /\$sltr_social_labels/);
  assert.match(view, /sltr-package-contact-address/);
  assert.match(view, /Open in Google Maps/);
  assert.doesNotMatch(view, /<iframe src=/);
  const addressAt = view.indexOf('sltr-package-contact-address');
  const mapsAt = view.indexOf('data-sltr-google-maps-popup');
  const contactsAt = view.indexOf('<?php if ($sltr_contact_details) : ?>');
  const socialsAt = view.indexOf('<?php if ($sltr_contact_socials) : ?>');
  assert.ok(addressAt >= 0 && mapsAt > addressAt && contactsAt > mapsAt && socialsAt > contactsAt);

});
