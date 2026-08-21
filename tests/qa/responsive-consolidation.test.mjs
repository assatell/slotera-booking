import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync('assets/css/frontend.css', 'utf8');

test('responsive ownership avoids known duplicate mobile override chains', () => {
  assert.doesNotMatch(css, /v1\.0\.1019 Solo content row-only composition/);
  assert.doesNotMatch(css, /v1\.0\.1020 Solo composer mobile stacking/);
  assert.match(css, /\/\* Canonical Solo content composition\. \*\/[\s\S]*?@media \(max-width: 767px\) \{[\s\S]*?\.sltr-package-landing-bottom-content \{[\s\S]*?overflow-x: auto;[\s\S]*?\.sltr-package-landing-top-content,[\s\S]*?\.sltr-package-landing-down-content \{[\s\S]*?flex-direction: column !important;/);

  assert.equal((css.match(/\.sltr-packages-page \{\n\s*margin-top: 20px;/g) || []).length, 0, 'obsolete Packages mobile margin layer must stay removed');
  assert.match(css, /\/\* Canonical Packages mobile containment\. \*\/[\s\S]*?@media \(max-width: 640px\) \{[\s\S]*?\.sltr-packages-page \{[\s\S]*?margin: 18px auto !important;/);

  assert.equal((css.match(/\.sltr-step\[data-step="4"\] \.sltr-booking-summary \{\n\s*grid-template-columns: 1fr;/g) || []).length, 0, 'Step 4 must inherit the shared mobile summary rule');
});

test('responsive slice 2 keeps one canonical mobile layer for public shells and Solo composition', () => {
  assert.equal((css.match(/@media \(max-width: 767px\)/g) || []).length, 1, 'Solo responsive rules should share one <=767px layer');
  assert.doesNotMatch(css, /@media \(max-width: 640px\) \{[\s\S]{0,180}\.sltr-checkout \{\s*padding: 18px;/, 'dead checkout mobile padding override must stay removed');
  assert.match(css, /@media \(max-width: 640px\) \{[\s\S]*?\.sltr-checkout,[\s\S]*?\.sltr-thank-you-card,[\s\S]*?\.sltr-checkout-panel \{\s*border-radius: 20px;/);
  assert.match(css, /@media \(max-width: 767px\) \{[\s\S]*?\.sltr-package-landing-bottom-content \{[\s\S]*?\.sltr-package-contact-block \{\s*grid-template-columns: 1fr !important;/);
});

test('responsive slice 3 groups legacy gallery and checkout option mobile ownership', () => {
  assert.match(css, /@media \(max-width: 640px\) \{[\s\S]*?\.sltr-package-gallery-count-3,[\s\S]*?\.sltr-package-gallery-horizontal \{\s*flex-direction: column;/);
  assert.match(css, /@media \(max-width: 640px\) \{[\s\S]*?\.sltr-conversion-option \{\s*grid-template-columns: 1fr;[\s\S]*?\.sltr-payment-option-card \{\s*grid-template-columns: auto 1fr;/);
  assert.equal((css.match(/@media \(max-width: 640px\)/g) || []).length, 9, 'slice 4 plus Phase 20 legacy package-detail removal leave nine <=640px wrappers');
});
