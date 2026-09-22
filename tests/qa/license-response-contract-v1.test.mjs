import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(
  path.dirname(fileURLToPath(import.meta.url)),
  '../..'
);

const verifier = fs.readFileSync(
  path.join(
    root,
    'includes/Application/Services/LicenseCertificateVerifier.php'
  ),
  'utf8'
);

test('license verifier consumes the backend v1 signed envelope contract', () => {
  assert.match(verifier, /slotera-license-envelope-v1/);
  assert.match(verifier, /payload_base64/);
  assert.match(verifier, /signature_base64/);
  assert.match(verifier, /slotera-license-response-v1\\n/);

  assert.doesNotMatch(
    verifier,
    /slotera-signed-envelope\/v1/
  );
});

test('license verifier consumes the backend v1 state payload contract', () => {
  assert.match(verifier, /slotera-license-state-v1/);
  assert.match(verifier, /license_public_id/);
  assert.match(verifier, /root_host/);
  assert.match(verifier, /state_version/);

  assert.doesNotMatch(verifier, /licensed_root/);
  assert.doesNotMatch(verifier, /trial_started_at/);
  assert.doesNotMatch(
    verifier,
    /slotera-license-certificate\/v1/
  );
});

test('license verifier accepts only backend lifecycle states and plans', () => {
  assert.match(
    verifier,
    /'active'[\s\S]*'trial'[\s\S]*'expired'[\s\S]*'suspended'[\s\S]*'revoked'/
  );

  assert.match(
    verifier,
    /'monthly'[\s\S]*'yearly'[\s\S]*'lifetime'[\s\S]*'trial'/
  );

  assert.doesNotMatch(
    verifier,
    /\['active', 'trial', 'grace'/
  );
});

test('license verifier keeps fingerprint key IDs bound across envelope and payload', () => {
  assert.match(
    verifier,
    /SigningKeyRing\(\).*resolve/s
  );

  assert.match(
    verifier,
    /payload\['key_id'\]/
  );

  assert.match(
    verifier,
    /envelope\['key_id'\]/
  );
});
test('issued_at validates freshness but does not order license lifecycle state', () => {
  assert.match(
    verifier,
    /microtime\(true\)\s*\+\s*300/
  );

  assert.doesNotMatch(
    verifier,
    /\$issued\s*<\s*\$lastIssued/
  );

  assert.doesNotMatch(
    verifier,
    /\$lastIssuedAt/
  );
});