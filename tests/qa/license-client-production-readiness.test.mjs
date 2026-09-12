import test from 'node:test';
import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (name) => fs.readFileSync(path.join(root, name), 'utf8');

test('embedded license public key matches the deployed Zone key ID', () => {
  const source = read('includes/Application/Services/LicenseCertificateVerifier.php');
  const pem = source.match(/-----BEGIN PUBLIC KEY-----[\s\S]+?-----END PUBLIC KEY-----/)?.[0];
  assert.ok(pem);
  const der = crypto.createPublicKey(pem).export({ type: 'spki', format: 'der' });
  assert.equal(
    `sha256:${crypto.createHash('sha256').update(der).digest('hex')}`,
    'sha256:ecadf72a744b506b38c64b3898df0d5d97a7e15fcf46ba356c083f2d9583917c',
  );
});

test('license transport is key-scoped and update checks cannot inherit a license key', () => {
  const service = read('includes/Application/Services/LicenseService.php');
  assert.match(service, /if \(\$key !== ''\) \{ \$body\['license_key'\] = \$key; \}/);
  assert.doesNotMatch(read('slotera-booking.php'), /license_key/);
});

test('deactivation is local and automatic refresh is daily', () => {
  const service = read('includes/Application/Services/LicenseService.php');
  assert.match(service, /wp_schedule_event\(time\(\) \+ HOUR_IN_SECONDS, 'daily', self::CRON_HOOK\)/);
  assert.match(service, /local_key_removed/);
  assert.doesNotMatch(service.match(/public function deactivate_license\(\): void[\s\S]*?public function prepared_license_fields/)?.[0] || '', /remote_/);
});
