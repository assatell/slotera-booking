import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(
  path.dirname(fileURLToPath(import.meta.url)),
  '../..'
);

const service = fs.readFileSync(
  path.join(
    root,
    'includes/Application/Services/LicenseService.php'
  ),
  'utf8'
);

test('license keys use the backend opaque sltr_ token format', () => {
  assert.match(
    service,
    /\/\^sltr_\[A-Za-z0-9_-\]\+\$\/D/
  );

  assert.doesNotMatch(
    service,
    /SLTR-\[A-F0-9\]/
  );

  assert.match(
    service,
    /strlen\(\$key\)\s*>=\s*32/
  );

  assert.match(
    service,
    /strlen\(\$key\)\s*<=\s*128/
  );
});

test('activate refresh and trial use operation-specific backend endpoints', () => {
  assert.match(
    service,
    /sltr_license_api_operation_url\(\$operation\)/
  );

  assert.doesNotMatch(
    service,
    /wp_safe_remote_post\(sltr_license_api_url\(\)/
  );

  assert.match(
    service,
    /request_and_store\('activate', \$key\)/
  );

  assert.match(
    service,
    /request_and_store\('refresh', \$key\)/
  );

  assert.match(
    service,
    /request_and_store\('trial', ''\)/
  );
});

test('license requests match the backend exact JSON contract', () => {
  assert.match(
    service,
    /\$body\s*=\s*\[\s*'root_host'\s*=>\s*\$this->current_domain\(\),?\s*\]/
  );

  assert.match(
    service,
    /\$body\['license_key'\] = \$key/
  );

  assert.doesNotMatch(
    service,
    /slotera-license-request\/v1/
  );

  assert.doesNotMatch(
    service,
    /'plugin' => 'slotera-booking'/
  );

  assert.doesNotMatch(
    service,
    /'operation' => \$operation/
  );

  assert.doesNotMatch(
    service,
    /'site_url' => home_url/
  );
});

test('license state stores and enforces monotonic state_version', () => {
  assert.match(
    service,
    /'license_state_version' => 0/
  );

  assert.match(
    service,
    /\$incomingVersion\s*=\s*\(int\)\s*\$payload\['state_version'\]/
  );

  assert.match(
    service,
    /\$previousVersion\s*=\s*\(int\)\s*\(\s*\$data\['license_state_version'\]\s*\?\?\s*0\s*\)/
  );

  assert.match(
    service,
    /\$incomingVersion\s*<\s*\$previousVersion/
  );

  assert.match(
    service,
    /\$data\['license_state_version'\]\s*=\s*\$incomingVersion/
  );
});

test('service stores the new backend payload field names', () => {
  assert.match(
    service,
    /\$payload\s*\[\s*'root_host'\s*\]/
  );

  assert.match(
    service,
    /\$payload\s*\[\s*'license_public_id'\s*\]/
  );

  assert.doesNotMatch(
    service,
    /\$payload\['licensed_root'\]/
  );

  assert.doesNotMatch(
    service,
    /\$payload\['license_id'\]/
  );

  assert.doesNotMatch(
    service,
    /\$payload\['trial_started_at'\]/
  );
});

test('license transport failure remains fail-open', () => {
  assert.match(
    service,
    /Fail open: retain the last valid signed certificate and state indefinitely/
  );

  const failedCheck = service.match(
    /private function record_failed_check[\s\S]*?private function store/
  )?.[0] || '';

  assert.doesNotMatch(
    failedCheck,
    /license_status.*unverified/
  );

  assert.doesNotMatch(
    failedCheck,
    /certificate_envelope.*\[\]/
  );
});
test('trial response unwraps the issued key and signed envelope', () => {
  assert.match(
    service,
    /\$operation === 'trial'/
  );

  assert.match(
    service,
    /\$responseData\['license_key'\]/
  );

  assert.match(
    service,
    /\$responseData\['envelope'\]/
  );

  assert.match(
    service,
    /\$trialKey/
  );

  assert.match(
    service,
    /SecretStore::encrypt_string\(\$key\)/
  );
});

test('equal state_version is accepted only for the same signed lifecycle state', () => {
  assert.match(
    service,
    /\$incomingVersion\s*===\s*\$previousVersion/
  );

  assert.match(
    service,
    /same_state_version_is_consistent/
  );

  const consistency = service.match(
    /private function same_state_version_is_consistent[\s\S]*?private function/
  )?.[0] || '';

  assert.match(consistency, /license_public_id/);
  assert.match(consistency, /root_host/);
  assert.match(consistency, /license_status/);
  assert.match(consistency, /license_plan/);
  assert.match(consistency, /license_expires_at/);
});

test('trial-issued license key is validated before storage', () => {
  assert.match(
    service,
    /private function is_valid_license_key/
  );

  assert.match(
    service,
    /\$trialKey[\s\S]*is_valid_license_key\(\$trialKey\)/
  );

  assert.match(
    service,
    /is_valid_license_key\(\$key\)/
  );
});