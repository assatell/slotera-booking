import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '../..');
const plugin = readFileSync(resolve(root, 'includes/Core/Plugin.php'), 'utf8');
const account = readFileSync(resolve(root, 'includes/Frontend/Controllers/AccountController.php'), 'utf8');
const bookingShortcode = readFileSync(resolve(root, 'includes/Frontend/Shortcodes/BookingShortcode.php'), 'utf8');

test('RC65.1.2 keeps normal wp-admin lazy while selectively restoring frontend admin-post handlers', () => {
  assert.match(plugin, /current_frontend_admin_post_action\(\)/);
  assert.match(plugin, /register_frontend_account = \$register_frontend \|\| in_array\(\$admin_post_action/);
  assert.match(plugin, /register_frontend_booking_shortcode = \$register_frontend \|\| \$admin_post_action === 'sltr_contact_form_submit'/);
  assert.match(plugin, /frontend_account[\s\S]*\$register_frontend_account/);
  assert.match(plugin, /frontend_booking_shortcode[\s\S]*\$register_frontend_booking_shortcode/);
  assert.match(plugin, /if \(function_exists\('is_admin'\) && is_admin\(\)\) \{[\s\S]*wp_doing_ajax/);
});

test('RC65.1.2 whitelist covers every frontend admin-post hook currently registered by Slotera', () => {
  const expected = [
    'sltr_request_magic_link',
    'sltr_consume_magic_link',
    'sltr_account_logout',
    'sltr_account_cancel_booking',
    'sltr_account_reschedule_booking',
    'sltr_account_invoice_pdf',
    'sltr_contact_form_submit',
  ];
  for (const action of expected) assert.match(plugin, new RegExp(`'${action}'`));
  for (const action of expected.slice(0, 6)) assert.match(account, new RegExp(`admin_post(?:_nopriv)?_${action}`));
  assert.match(bookingShortcode, /admin_post(?:_nopriv)?_sltr_contact_form_submit/);
});
