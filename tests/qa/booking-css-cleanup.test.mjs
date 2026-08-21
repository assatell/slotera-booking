import fs from 'node:fs';
import assert from 'node:assert/strict';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');
const bookingView = fs.readFileSync(new URL('../../includes/Frontend/Views/booking-form.php', import.meta.url), 'utf8');

assert.ok(!bookingView.includes('<style>'), 'booking-form.php must not reintroduce a frontend CSS override block');
assert.ok(css.includes('.sltr-payment-method-card {'), 'payment method picker styles must live in frontend.css');
assert.ok(css.includes('background: var(--sltr-card-bg, #f8fafc);'), 'booking summary/feedback surfaces must inherit Appearance card background');
assert.ok(css.includes('border: 1px solid var(--sltr-card-border, #e2e8f0);'), 'booking summary must inherit Appearance border');
assert.ok(css.includes('color: var(--sltr-form-text, #0f172a);'), 'booking details text must inherit Appearance text token');
assert.ok(!css.includes('v1.0.998 booking details summary'), 'folded v1.0.998 booking summary patch must not return');
console.log('booking CSS cleanup regression guard: ok');
