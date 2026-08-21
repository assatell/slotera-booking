import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('privacy-first visitor analytics is enabled by default while session analytics remains opt-in', () => {
  const repo = read('includes/Infrastructure/Repositories/SettingsRepository.php');
  const view = read('includes/Admin/Views/settings/privacy.php');
  assert.match(repo, /'privacy_visitor_analytics_enabled'\s*=>\s*1/);
  assert.match(repo, /'privacy_visitor_session_analytics_enabled'\s*=>\s*0/);
  assert.match(view, /privacy_visitor_analytics_enabled'\]\s*\?\?\s*1/);
});

test('frontend gateway picker exposes only online checkout aggregators', () => {
  const view = read('includes/Frontend/Views/booking-form.php');
  assert.match(view, /array_flip\(\['stripe', 'apple_pay', 'google_pay', 'paypal', 'mollie'\]\)/);
  assert.match(view, /<option value="booking_only">/);
  assert.doesNotMatch(view, /array_flip\([^\n]*'manual'/);
});
