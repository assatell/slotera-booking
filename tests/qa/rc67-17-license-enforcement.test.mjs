import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');

test('RC67.17 central license policy covers licensed feature families', () => {
  const policy = read('includes/Application/Services/LicenseFeaturePolicy.php');
  for (const token of ['MARKETING', 'PAYMENTS', 'SHARED_NETWORK', 'ANALYTICS', 'WHITE_LABEL']) {
    assert.match(policy, new RegExp(`public const ${token}`));
  }
  assert.ok(policy.includes("['active', 'trial', 'grace']"));
});

test('RC67.17 protected admin actions cannot bypass UI gating', () => {
  const enforcer = read('includes/Application/Services/LicenseFeatureEnforcer.php');
  for (const action of [
    'sltr_save_coupon',
    'sltr_save_marketing_campaign',
    'sltr_send_marketing_campaign',
    'sltr_run_marketing_automation',
    'sltr_save_promotion_digest',
    'sltr_send_promotion_now',
    'sltr_save_payment_settings',
    'sltr_save_white_label_settings',
  ]) {
    assert.match(enforcer, new RegExp(action));
  }
  assert.match(enforcer, /sltr_create_shared_tables/);
  assert.ok(enforcer.includes('remove_all_actions(MarketingEmailService::CRON_HOOK)'));
  assert.ok(enforcer.includes('remove_all_actions(MarketingAutomationService::CRON_HOOK)'));
  assert.ok(enforcer.includes('remove_all_actions(PromotionCampaignService::CRON_HOOK)'));
});

test('RC67.17 locked Payments fall back to booking-only and expose no gateways', () => {
  const policy = read('includes/Application/Services/PaymentPolicyService.php');
  const methods = read('includes/Application/Services/PaymentMethodService.php');
  assert.match(policy, /LicenseFeaturePolicy::PAYMENTS/);
  assert.match(policy, /CHOICE_PAY_LATER/);
  assert.match(methods, /LicenseFeaturePolicy::PAYMENTS/);
  assert.ok(methods.includes('return [];'));
});

test('RC67.17 licensed admin pages enforce the central policy', () => {
  const expected = new Map([
    ['includes/Admin/Pages/MarketingPage.php', 'MARKETING'],
    ['includes/Admin/Pages/PaymentsPage.php', 'PAYMENTS'],
    ['includes/Admin/Pages/AnalyticsPage.php', 'ANALYTICS'],
    ['includes/Admin/Pages/SharedDatabaseNetworkPage.php', 'SHARED_NETWORK'],
    ['includes/Admin/Pages/WhiteLabelPage.php', 'WHITE_LABEL'],
  ]);
  for (const [file, feature] of expected) {
    const source = read(file);
    assert.match(source, new RegExp(`LicenseFeaturePolicy::${feature}`), file);
  }
});

test('RC67.17 signed revoke/expire disables active White Label and Promotion sends', () => {
  const whiteLabel = read('includes/Application/Services/WhiteLabelService.php');
  const promotion = read('includes/Application/Services/PromotionCampaignService.php');
  assert.match(whiteLabel, /LicenseFeaturePolicy::WHITE_LABEL/);
  assert.match(promotion, /LicenseFeaturePolicy::MARKETING/);
  assert.match(promotion, /reason' => 'license_limited'/);
});
const { test: safetyTest } = await import('node:test');
const { default: safetyAssert } = await import('node:assert/strict');
const { readFileSync: safetyReadFileSync } = await import('node:fs');

safetyTest('RC67.17 revoked license preserves safe stop and cleanup actions', () => {
  const source = safetyReadFileSync(
    new URL('../../includes/Application/Services/LicenseFeatureEnforcer.php', import.meta.url),
    'utf8'
  );

  // Operations that create, send, restart or modify licensed marketing remain blocked.
  safetyAssert.ok(source.includes("'sltr_save_coupon' => LicenseFeaturePolicy::MARKETING"));
  safetyAssert.ok(source.includes("'sltr_save_marketing_campaign' => LicenseFeaturePolicy::MARKETING"));
  safetyAssert.ok(source.includes("'sltr_send_marketing_campaign' => LicenseFeaturePolicy::MARKETING"));
  safetyAssert.ok(source.includes("'sltr_resume_marketing_campaign' => LicenseFeaturePolicy::MARKETING"));
  safetyAssert.ok(source.includes("'sltr_retry_failed_marketing_campaign' => LicenseFeaturePolicy::MARKETING"));
  safetyAssert.ok(source.includes("'sltr_run_marketing_automation' => LicenseFeaturePolicy::MARKETING"));

  // Safe shutdown / cleanup must remain available even after authoritative revocation.
  safetyAssert.ok(!source.includes("'sltr_delete_coupon' => LicenseFeaturePolicy::MARKETING"));
  safetyAssert.ok(!source.includes("'sltr_delete_marketing_campaign' => LicenseFeaturePolicy::MARKETING"));
  safetyAssert.ok(!source.includes("'sltr_pause_marketing_campaign' => LicenseFeaturePolicy::MARKETING"));
  safetyAssert.ok(!source.includes("'sltr_stop_marketing_campaign' => LicenseFeaturePolicy::MARKETING"));
  safetyAssert.ok(!source.includes("'sltr_stop_marketing_automation' => LicenseFeaturePolicy::MARKETING"));
});
