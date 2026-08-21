import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (p) => fs.readFileSync(p, 'utf8');
const service = read('includes/Application/Services/PromotionCampaignService.php');
const controller = read('includes/Admin/Controllers/MarketingController.php');
const view = read('includes/Admin/Views/marketing-promotions.php');
const menu = read('includes/Admin/AdminServiceProvider.php');
const settings = read('includes/Infrastructure/Repositories/SettingsRepository.php');
const calendar = read('includes/Application/Services/CalendarInviteService.php');
const emailView = read('includes/Admin/Views/settings/email.php');
const booking = read('includes/Frontend/Views/booking-form.php');
const css = read('assets/css/frontend.css');

test('RC66.12.5 manual Promotions send/test use current unsaved form state', () => {
  assert.match(service, /send_now\(string \$reason = 'manual', array \$input = \[\]\)/);
  assert.match(service, /settings_from_input\(array \$input, array \$saved\)/);
  assert.match(controller, /send_now\('manual', wp_unslash\(\$_POST\)\)/);
  assert.match(controller, /send_test\(\$email, wp_unslash\(\$_POST\)\)/);
  assert.match(view, /id="sltr-promotion-settings-form"/);
  assert.match(view, /syncCurrentSettings/);
  assert.match(view, /promotion_digest_fallback_image_id/);
});

test('RC66.12.5 removes PRO labels from the Slotera admin menu while licensing is deferred', () => {
  assert.doesNotMatch(menu, /sltr-menu-pro-badge/);
  assert.doesNotMatch(menu, /Slotera PRO Features/);
  assert.match(menu, /'Payments', 'Payments'/);
  assert.match(menu, /'Analytics', 'Analytics'/);
  assert.match(menu, /'Marketing Emails', 'Marketing Emails'/);
});

test('RC66.12.5 calendar invite attachments default to off without overriding saved settings', () => {
  assert.match(settings, /'email_attach_ics_invites' => 0/);
  assert.match(calendar, /get\('email_attach_ics_invites', 0\) === 1/);
  assert.match(emailView, /\$settings\['email_attach_ics_invites'\] \?\? 0/);
});

test('RC66.12.5 marketing consent is an unselected compact card in booking details', () => {
  assert.match(booking, /class="sltr-marketing-consent-card"/);
  assert.match(booking, /class="sltr-marketing-consent-text"/);
  assert.match(booking, /id="sltr-marketing-consent" value="1">/);
  assert.doesNotMatch(booking, /id="sltr-marketing-consent"[^>]*checked/);
  assert.match(css, /sltr-marketing-consent-card/);
  assert.match(css, /font-size:\.9em/);
});
