import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('privacy erasure revokes consent and retains suppression', () => {
  const privacy = read('includes/Application/Services/PrivacyService.php');
  assert.match(privacy, /has_consent\(\$email\).*revoke\(\$email\)/s);
  assert.match(privacy, /suppress\(\$email, 'privacy_erasure'\)/);
  assert.doesNotMatch(privacy, /delete_record\(\$email\)/);
});

test('privacy export and erasure cover payment transactions and invoices', () => {
  const privacy = read('includes/Application/Services/PrivacyService.php');
  assert.match(privacy, /payment_transactions_table\(\)/);
  assert.match(privacy, /payment_invoices_table\(\)/);
  assert.match(privacy, /deleted\+transaction-/);
  assert.match(privacy, /deleted\+invoice-/);
  assert.match(privacy, /'metadata_json' => ''/);
});

test('magic-link credentials are exchanged before frontend rendering', () => {
  const controller = read('includes/Frontend/Controllers/AccountController.php');
  const service = read('includes/Application/Services/AccountMagicLinkService.php');
  assert.match(controller, /begin_magic_confirmation\(\$email, \$expires, \$token\)/);
  assert.match(controller, /wp_safe_redirect\(\$this->accounts->account_url\(\), 303\)/);
  assert.doesNotMatch(controller, /name="sltr_email"/);
  assert.doesNotMatch(controller, /name="sltr_expires"/);
  assert.doesNotMatch(controller, /name="sltr_token"/);
  assert.match(service, /CONFIRM_COOKIE/);
  assert.match(service, /'httponly' => true/);
  assert.match(service, /'samesite' => 'Lax'/);
});

test('temporary mail attachments fail closed outside the web root', () => {
  const attachments = read('includes/Application/Services/SecureAttachmentFileService.php');
  assert.match(attachments, /sys_get_temp_dir\(\)/);
  assert.match(attachments, /is_public_path/);
  assert.match(attachments, /must never\s*\n\s*\/\/ fall back to wp-content\/uploads/);
  assert.doesNotMatch(attachments, /write_protection_files/);
});
