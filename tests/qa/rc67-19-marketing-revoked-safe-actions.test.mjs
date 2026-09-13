import test from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";
import path from "node:path";

const root = process.cwd();

function read(rel) {
  return fs.readFileSync(path.join(root, rel), "utf8");
}

test("RC67.19 keeps Marketing pages visible while revoked", () => {
  const source = read("includes/Admin/Pages/MarketingPage.php");

  assert.match(source, /\$sltr_marketing_locked\s*=/);
  assert.doesNotMatch(
    source,
    /if\s*\(\s*!\$license_policy->allows\([^)]*MARKETING[^)]*\)\s*\)\s*\{[\s\S]{0,1500}\breturn\s*;/,
    "MarketingPage must not return early just because Marketing is locked"
  );
});

test("RC67.19 blocks direct coupon create/edit routes while revoked", () => {
  const source = read("includes/Admin/Pages/CouponsPage.php");

  assert.match(
    source,
    /\$sltr_marketing_locked\s*&&\s*in_array\(\$action,\s*\['new',\s*'edit'\],\s*true\)/
  );
});

test("RC67.19 coupon list stays visible but creation and sending are locked", () => {
  const source = read("includes/Admin/Views/coupons-list.php");

  assert.match(source, /empty\(\$sltr_marketing_locked\)/);
  assert.match(source, /Create campaign/);
  assert.match(source, /Send now/);

  assert.match(source, /Delete campaign/);
  assert.match(source, /Delete coupon/);
});

test("RC67.19 campaign history keeps cleanup actions but hides restart actions", () => {
  const source = read("includes/Admin/Views/campaign-history-table.php");

  assert.match(
    source,
    /elseif\s*\(\s*empty\(\$sltr_marketing_locked\)\s*\)\s*:\s*\?>[\s\S]*?sltr_run_marketing_automation/
  );

  assert.match(
    source,
    /empty\(\$sltr_marketing_locked\)[\s\S]*?sltr_process_marketing_queue_now/
  );

  assert.match(
    source,
    /empty\(\$sltr_marketing_locked\)[\s\S]*?sltr_resume_marketing_campaign/
  );

  assert.match(
    source,
    /empty\(\$sltr_marketing_locked\)[\s\S]*?sltr_retry_failed_marketing_campaign/
  );

  assert.match(source, /sltr_stop_marketing_automation/);
  assert.match(source, /sltr_pause_marketing_campaign/);
  assert.match(source, /sltr_stop_marketing_campaign/);
  assert.match(source, /sltr_delete_marketing_campaign/);
});

test("RC67.19 automation editor is read-only while Marketing is locked", () => {
  const source = read("includes/Admin/Views/marketing-automation-editor.php");

  assert.match(
    source,
    /<fieldset\s+<\?php disabled\(!empty\(\$sltr_marketing_locked\)\); \?>/
  );

  assert.match(
    source,
    /if\s*\(\s*empty\(\$sltr_marketing_locked\)\s*\)\s*:\s*\?>[\s\S]*?Preview and test/
  );

  assert.match(source, /sltr_send_marketing_automation_test/);
  assert.match(source, /sltr_run_marketing_automation/);
});

test("RC67.19 Promotions remains visible but write/send actions are locked", () => {
  const source = read("includes/Admin/Views/marketing-promotions.php");

  assert.match(
    source,
    /<fieldset\s+<\?php disabled\(!empty\(\$sltr_marketing_locked\)\); \?>/
  );

  assert.match(
    source,
    /if\s*\(\s*empty\(\$sltr_marketing_locked\)\s*\)\s*:\s*\?>[\s\S]*?Save promotion settings/
  );

  assert.match(
    source,
    /if\s*\(\s*empty\(\$sltr_marketing_locked\)\s*\)\s*:\s*\?>[\s\S]*?sltr_send_promotion_test/
  );

  assert.match(
    source,
    /if\s*\(\s*empty\(\$sltr_marketing_locked\)\s*\)\s*:\s*\?>[\s\S]*?sltr_send_promotion_now/
  );

  assert.match(source, /Promotion email preview/);
});

test("RC67.19 backend blocks dangerous Marketing actions but leaves cleanup actions unblocked", () => {
  const source = read("includes/Application/Services/LicenseFeatureEnforcer.php");

  const blocked = [
    "sltr_save_coupon",
    "sltr_save_marketing_campaign",
    "sltr_send_marketing_campaign",
    "sltr_process_marketing_queue_now",
    "sltr_resume_marketing_campaign",
    "sltr_retry_failed_marketing_campaign",
    "sltr_run_marketing_automation",
    "sltr_save_promotion_digest",
    "sltr_send_promotion_test",
    "sltr_send_promotion_now",
  ];

  for (const action of blocked) {
    assert.match(source, new RegExp(`'${action}'\\s*=>\\s*LicenseFeaturePolicy::MARKETING`));
  }

  const cleanup = [
    "sltr_stop_marketing_automation",
    "sltr_pause_marketing_campaign",
    "sltr_stop_marketing_campaign",
    "sltr_delete_marketing_campaign",
    "sltr_delete_coupon",
  ];

  for (const action of cleanup) {
    assert.doesNotMatch(
      source,
      new RegExp(`'${action}'\\s*=>\\s*LicenseFeaturePolicy::MARKETING`),
      `${action} must remain available for safe cleanup`
    );
  }
});

test("RC67.19 Coupon Campaigns uses the shared locked history table", () => {
  const source = read("includes/Admin/Views/coupon-campaigns-list.php");

  assert.match(source, /\$sltr_history_context\s*=\s*'coupon'/);
  assert.match(
    source,
    /require\s+SLTR_PLUGIN_DIR\s*\.\s*'includes\/Admin\/Views\/campaign-history-table\.php'/
  );
});