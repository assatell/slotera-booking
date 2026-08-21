import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (path) => fs.readFileSync(new URL('../../' + path, import.meta.url), 'utf8');
const packageController = read('includes/Admin/Controllers/PackageController.php');
const packageRepo = read('includes/Infrastructure/Repositories/PackageRepository.php');
const promotions = read('includes/Application/Services/PromotionCampaignService.php');

test('locked package slug never blocks later Booking blocks saves', () => {
  assert.match(packageController, /if \(\$saved_slug !== ''\) \{[\s\S]*?\$requested_slug = \$saved_slug;/);
  assert.doesNotMatch(packageController, /sltr_error=slug_locked/);
  assert.match(packageRepo, /\$normalized\['slug'\] = \$old_slug;/);
});

test('Promotions CTA falls back from solo page to configured Booking page and never to home', () => {
  assert.match(promotions, /solo_page_enabled[\s\S]*?get_permalink\(\$page_id\)/);
  assert.match(promotions, /get_page_url\('booking'\)/);
  assert.match(promotions, /if \(\$url === ''\) \{ return ''; \}/);
  assert.match(promotions, /'sltr_package_id'\s*=>/);
  assert.match(promotions, /'sltr_step'\s*=>\s*'calendar'/);
  assert.match(promotions, /#sltr-booking/);
  assert.doesNotMatch(promotions, /\$url = home_url\('\/'\)/);
});

test('Promotions omits the CTA when neither solo nor Booking page exists', () => {
  assert.match(promotions, /\$url = trim\(\(string\) \(\$offer\['url'\] \?\? ''\)\)/);
  assert.match(promotions, /if \(\$url !== ''\) \{[\s\S]*?\$cta = '<p/);
  assert.match(promotions, /\. \$cta\s*\n\s*\. '<\/div>'/);
});
