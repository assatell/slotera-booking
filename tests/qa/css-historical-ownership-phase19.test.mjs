import test from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";

const css = fs.readFileSync(new URL("../../assets/css/frontend.css", import.meta.url), "utf8");

test("legacy package patch labels are retired from canonical frontend CSS", () => {
  for (const version of ["816", "817", "818", "821", "852", "1021", "1027"]) {
    assert.ok(!css.includes(`v1.0.${version}`), `legacy v1.0.${version} label returned`);
  }
});

test("booking card link row no longer carries the obsolete 80 percent patch", () => {
  assert.ok(!css.includes("v1.0.852 booking card link row uses the exact booking button width"));
  assert.match(css, /#sltr-booking \.sltr-step\[data-step="1"\] \.sltr-package-card-link-row \{[\s\S]*?width: 100% !important;/);
  assert.match(css, /#sltr-booking \.sltr-step\[data-step="1"\] \.sltr-package-card-link-row \.sltr-package-info-button \{[\s\S]*?--sltr-tooltip-size-ratio/);
});
