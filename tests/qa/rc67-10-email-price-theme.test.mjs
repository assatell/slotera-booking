import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const service = fs.readFileSync(new URL('../../includes/Application/Services/EmailReminderService.php', import.meta.url), 'utf8');
const controller = fs.readFileSync(new URL('../../includes/Admin/Controllers/EmailController.php', import.meta.url), 'utf8');

test('email price summary uses the selected Slotera appearance palette', () => {
  const start = service.indexOf("$colors = $this->email_theme_colors();", service.indexOf('private function booking_price_placeholders'));
  assert.ok(start >= 0, 'price summary does not resolve email theme colors');
  const block = service.slice(start, service.indexOf("'price_summary' => $summary", start));
  for (const token of ["$colors['card_bg']", "$colors['card_border']", "$colors['text']", "$colors['muted']"]) {
    assert.ok(block.includes(token), `price summary missing ${token}`);
  }
  assert.doesNotMatch(block, /bgcolor="*#ffffff/);
  assert.doesNotMatch(block, /background:#ffffff/);
});

test('email preview price summary uses the same appearance palette', () => {
  const start = controller.indexOf("$colors = $this->email_theme_colors();", controller.indexOf('private function sample_price_summary'));
  assert.ok(start >= 0, 'price preview does not resolve email theme colors');
  const block = controller.slice(start, controller.indexOf('private function verify', start));
  for (const token of ["$colors['card_bg']", "$colors['card_border']", "$colors['text']", "$colors['muted']"]) {
    assert.ok(block.includes(token), `price preview missing ${token}`);
  }
  assert.doesNotMatch(block, /background:#ffffff/);
  assert.doesNotMatch(block, /color:#64748b/);
});
