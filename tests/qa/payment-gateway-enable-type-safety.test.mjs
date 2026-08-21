import test from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";

const controller = fs.readFileSync(new URL("../../includes/Admin/Controllers/PaymentsController.php", import.meta.url), "utf8");
const validator = fs.readFileSync(new URL("../../includes/Application/Services/RequestValidator.php", import.meta.url), "utf8");

test("gateway enable helper accepts RequestValidator post_bool integer values", () => {
  assert.match(validator, /function\s+post_bool\(string \$key\): int/);
  assert.match(controller, /function\s+with_gateway_enabled\(array \$gateways, string \$gateway, bool\|int \$enabled\): array/);
  assert.match(controller, /\$enabled\s*=\s*\(bool\)\s*\$enabled;/);
});

test("Stripe and PayPal gateway saves pass checkbox values through the type-safe helper", () => {
  assert.match(controller, /with_gateway_enabled\([\s\S]*?['"]stripe['"][\s\S]*?post_bool\(['"]payment_stripe_enabled['"]\)/);
  assert.match(controller, /with_gateway_enabled\([\s\S]*?['"]paypal['"][\s\S]*?post_bool\(['"]payment_paypal_enabled['"]\)/);
});
