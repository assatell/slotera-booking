import test from 'node:test';
import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (name) => fs.readFileSync(path.join(root, name), 'utf8');

test('signed updates use the distinct RC67 release key', () => {
  const source = read('includes/Application/Services/UpdateEnvelopeVerifier.php');
  const pem = source.match(/-----BEGIN PUBLIC KEY-----[\s\S]+?-----END PUBLIC KEY-----/)?.[0];
  const der = crypto.createPublicKey(pem).export({ type: 'spki', format: 'der' });
  assert.equal(`sha256:${crypto.createHash('sha256').update(der).digest('hex')}`, 'sha256:c3272d14be785233f096bd11ef478a85b12b889bb3e7b7930993c440b1275603');
  assert.doesNotMatch(source, /ecadf72a744b506b/);
});

test('update checks never read or send the license key', () => {
  const source = read('includes/Application/Services/UpdateService.php');
  assert.doesNotMatch(source, /license_key|LicenseService|sltr_license/);
  assert.match(source, /'plugin' => 'slotera-booking'/);
});

test('installer verifies SHA-256 and deletes a mismatched archive', () => {
  const source = read('includes/Application/Services/UpdateService.php');
  assert.match(source, /hash_file\('sha256', \$file\)/);
  assert.match(source, /hash_equals\(\(string\) \$payload\['package_sha256'\]/);
  assert.match(source, /wp_delete_file\(\$file\)/);
  assert.match(source, /sltr_update_hash_mismatch/);
});

test('release tool signs update metadata with the offline release key', () => {
  const source = read('tools/update-envelope.mjs');
  assert.match(source, /RSA_PKCS1_PADDING/);
  assert.match(source, /manifest\.signing\?\.key_id/);
  assert.match(source, /package_sha256/);
  assert.doesNotMatch(source, /license/i);
});
