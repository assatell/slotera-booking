import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const js = fs.readFileSync(new URL('../../assets/js/frontend-booking-form.js', import.meta.url), 'utf8');
const view = fs.readFileSync(new URL('../../includes/Frontend/Views/booking-form.php', import.meta.url), 'utf8');

test('Booking Request hides irrelevant Back to time action', () => {
  assert.match(view, /id=\"sltr-details-back\"[^>]*data-back=\"3\"/);
  assert.match(js, /detailsBackButton\.style\.display\s*=\s*selectedPackageMeta\.mode === 'simple' \? 'none' : ''/);
  assert.match(js, /if \(selectedPackageMeta\.mode === 'simple'\)[\s\S]*showStep\(4\)/);
});
