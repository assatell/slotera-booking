import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const service = fs.readFileSync('includes/Application/Services/PromotionCampaignService.php', 'utf8');
const page = fs.readFileSync('includes/Admin/Views/marketing-promotions.php', 'utf8');
const controller = fs.readFileSync('includes/Admin/Controllers/MarketingController.php', 'utf8');
const marketing = fs.readFileSync('includes/Application/Services/MarketingEmailService.php', 'utf8');
const repo = fs.readFileSync('includes/Infrastructure/Repositories/MarketingCampaignRepository.php', 'utf8');
const cron = fs.readFileSync('includes/Application/Services/CronScheduleRegistry.php', 'utf8');

test('RC66.12 promotion digest collects all supported package offer types', () => {
  assert.match(service, /discount_type/);
  assert.match(service, /dynamic_weekend_percent/);
  assert.match(service, /dynamic_season_percent/);
  assert.match(service, /dynamic_season_start/);
  assert.match(service, /dynamic_season_end/);
  assert.match(service, /get_active\(500, 0\)/);
});

test('RC66.12 promotion images use booking image then package image and one common fallback', () => {
  const bookingPos = service.indexOf("booking_card_image_id");
  const packagePos = service.indexOf("card_image_id");
  assert.ok(bookingPos >= 0 && packagePos > bookingPos);
  assert.match(service, /promotion_digest_fallback_image_id/);
  assert.match(page, /Choose common image from Media Library/);
  assert.match(page, /Packages without images/);
  assert.doesNotMatch(page, /Custom marketing image/i);
});

test('RC66.12 promotion schedule is Friday based with manual weekly biweekly monthly modes', () => {
  for (const mode of ['manual', 'weekly', 'biweekly', 'monthly']) assert.ok(service.includes(`'${mode}'`));
  assert.match(service, /format\('N'\) !== 5/);
  assert.match(page, /Once a week/);
  assert.match(page, /Once every 2 weeks/);
  assert.match(page, /Once a month/);
  assert.match(service, /no_active_offers/);
  assert.match(cron, /PromotionCampaignService::CRON_HOOK/);
});

test('RC66.12 preview test send and real send share promotion digest renderer and marketing queue', () => {
  assert.match(page, /Preview email/);
  assert.match(page, /Send test email/);
  assert.match(page, /Send now/);
  assert.match(controller, /sltr_send_promotion_test/);
  assert.match(controller, /sltr_send_promotion_now/);
  assert.match(service, /preview_message_for_campaign/);
  assert.match(service, /send_test_for_campaign/);
  assert.match(service, /queue_campaign/);
  assert.match(marketing, /source.*promotion_digest/s);
  assert.match(repo, /promotion_digest/);
});
