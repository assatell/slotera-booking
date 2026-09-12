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

test('update metadata is fresh, monotonic and replay-resistant', () => {
  const verifier = read('includes/Application/Services/UpdateEnvelopeVerifier.php');
  const service = read('includes/Application/Services/UpdateService.php');
  assert.match(verifier, /\$payload\['sequence'\] < 1/);
  assert.match(verifier, /\$expires <= \$now/);
  assert.match(verifier, /7 \* DAY_IN_SECONDS/);
  assert.match(service, /ACCEPTANCE_OPTION/);
  assert.match(service, /\$sequence < \$lastSequence/);
  assert.match(service, /hash_equals\(\$lastFingerprint, \$fingerprint\)/);
  assert.match(service, /\['envelope' => \$envelope\]/);
});

test('signed rollback is restricted to the exact installed source version', () => {
  const verifier = read('includes/Application/Services/UpdateEnvelopeVerifier.php');
  const service = read('includes/Application/Services/UpdateService.php');
  assert.match(verifier, /slotera-update-rollback\/v1/);
  assert.match(verifier, /version_compare\(\(string\) \$payload\['version'\], \(string\) \$rollback\['from_version'\], '>='\)/);
  assert.match(service, /hash_equals\(\(string\) \(\$rollback\['from_version'\] \?\? ''\), SLTR_VERSION\)/);
  assert.match(service, /version_compare\(\$target, SLTR_VERSION, '<'\)/);
});

test('release tool signs freshness, rollback and optional key rotation with the offline release key', () => {
  const source = read('tools/update-envelope.mjs');
  assert.match(source, /RSA_PKCS1_PADDING/);
  assert.match(source, /manifest\.signing\?\.key_id/);
  assert.match(source, /package_sha256/);
  assert.match(source, /sequence/);
  assert.match(source, /expires_at/);
  assert.match(source, /slotera-update-rollback\/v1/);
  assert.match(source, /slotera-next-signing-key\/v1/);
  assert.doesNotMatch(source, /license/i);
});
