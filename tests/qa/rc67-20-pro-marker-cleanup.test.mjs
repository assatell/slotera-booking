import test from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";
import path from "node:path";

const root = process.cwd();

function read(rel) {
  return fs.readFileSync(path.join(root, rel), "utf8");
}

test("RC67.20 removes visible PRO product markers", () => {
  const proFeatures = read("includes/Admin/Views/pro-features.php");
  const sharedNetwork = read("includes/Admin/Views/shared-database-network.php");
  const registry = read("includes/Application/Services/ProFeatureRegistry.php");

  assert.doesNotMatch(proFeatures, /No PRO features are available/);
  assert.match(proFeatures, /No additional features are available/);

  assert.doesNotMatch(
    sharedNetwork,
    /Slotera\s*>\s*PRO\s*>\s*Shared Database Network/
  );
  assert.match(
    sharedNetwork,
    /Slotera\s*>\s*Shared Database Network/
  );

  assert.doesNotMatch(
    registry,
    /Agency\/client branding options for Pro installations/
  );
  assert.match(
    registry,
    /Agency\/client branding options: product name/
  );
});

test("RC67.20 keeps Powered by Slotera attribution more visible", () => {
  const css = read("assets/css/frontend.css");
  const bookingForm = read("includes/Frontend/Views/booking-form.php");

  assert.match(
    css,
    /\.sltr-platform-attribution\s*\{[^}]*font-size:14px;/
  );

  assert.match(
    bookingForm,
    /Powered by <strong>Slotera<\/strong>/
  );
});