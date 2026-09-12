import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';

const [, , archivePath, privateKeyPath, packageUrl, outputPath, sequenceArg, rollbackFrom = '', nextPublicKeyPath = '', nextNotBefore = '', retireCurrentAfter = ''] = process.argv;
if (!archivePath || !privateKeyPath || !packageUrl || !outputPath || !sequenceArg) {
  throw new Error('Usage: node tools/update-envelope.mjs ARCHIVE PRIVATE_KEY PACKAGE_URL OUTPUT SEQUENCE [ROLLBACK_FROM] [NEXT_PUBLIC_KEY] [NEXT_NOT_BEFORE] [RETIRE_CURRENT_AFTER]');
}
if (fs.existsSync(outputPath)) throw new Error('Output already exists');
const sequence = Number(sequenceArg);
if (!Number.isSafeInteger(sequence) || sequence < 1) throw new Error('SEQUENCE must be a positive integer');
const manifest = JSON.parse(fs.readFileSync('release-manifest.json', 'utf8'));
const privateKey = crypto.createPrivateKey(fs.readFileSync(privateKeyPath));
const publicKey = crypto.createPublicKey(privateKey);
const der = publicKey.export({ type: 'spki', format: 'der' });
const keyId = `sha256:${crypto.createHash('sha256').update(der).digest('hex')}`;
if (keyId !== manifest.signing?.key_id) throw new Error('Release signing key does not match release-manifest.json');
const parsed = new URL(packageUrl);
const host = parsed.hostname.toLowerCase();
if (parsed.protocol !== 'https:' || (host !== 'getslotera.com' && !host.endsWith('.getslotera.com'))) throw new Error('Package URL must use a getslotera.com HTTPS host');
if (path.basename(parsed.pathname) !== path.basename(archivePath)) throw new Error('Package URL filename must match the archive');
const issued = new Date();
const payload = {
  schema: 'slotera-update/v1',
  plugin: 'slotera-booking',
  channel: 'stable',
  version: manifest.version,
  package_url: packageUrl,
  package_sha256: crypto.createHash('sha256').update(fs.readFileSync(archivePath)).digest('hex'),
  requires_wp: '6.0',
  requires_php: '8.0',
  tested_wp: '',
  sequence,
  issued_at: issued.toISOString(),
  expires_at: new Date(issued.getTime() + 24 * 60 * 60 * 1000).toISOString(),
  source_commit: String(manifest.source?.commit || ''),
  source_tag: String(manifest.source?.tag || ''),
};
if (rollbackFrom) {
  if (!/^\d+\.\d+\.\d+$/.test(rollbackFrom) || !/^\d+\.\d+\.\d+$/.test(payload.version)) throw new Error('Rollback versions must be semantic versions');
  const tuple = (v) => v.split('.').map(Number);
  const cmp = (a, b) => { const aa = tuple(a); const bb = tuple(b); for (let i = 0; i < 3; i += 1) { if (aa[i] !== bb[i]) return aa[i] - bb[i]; } return 0; };
  if (cmp(payload.version, rollbackFrom) >= 0) throw new Error('Rollback target must be lower than ROLLBACK_FROM');
  payload.rollback = { schema: 'slotera-update-rollback/v1', from_version: rollbackFrom, reason: 'Emergency signed rollback' };
}
if (nextPublicKeyPath) {
  if (!nextNotBefore || !retireCurrentAfter) throw new Error('Key rotation requires NEXT_NOT_BEFORE and RETIRE_CURRENT_AFTER');
  const nextPem = fs.readFileSync(nextPublicKeyPath, 'utf8').trim();
  const nextKey = crypto.createPublicKey(nextPem);
  const nextDer = nextKey.export({ type: 'spki', format: 'der' });
  const nextKeyId = `sha256:${crypto.createHash('sha256').update(nextDer).digest('hex')}`;
  const notBefore = new Date(nextNotBefore);
  const retireAfter = new Date(retireCurrentAfter);
  if (!Number.isFinite(notBefore.getTime()) || !Number.isFinite(retireAfter.getTime()) || retireAfter <= notBefore || retireAfter <= issued) {
    throw new Error('Invalid key rotation window');
  }
  payload.next_key = {
    schema: 'slotera-next-signing-key/v1',
    purpose: 'update',
    signed_by: keyId,
    key_id: nextKeyId,
    public_key: `${nextPem}\n`,
    not_before: notBefore.toISOString(),
    retire_current_after: retireAfter.toISOString(),
  };
}
const bytes = Buffer.from(JSON.stringify(payload), 'utf8');
const signature = crypto.sign('sha256', bytes, { key: privateKey, padding: crypto.constants.RSA_PKCS1_PADDING });
const envelope = {
  schema: 'slotera-signed-update-envelope/v1',
  key_id: keyId,
  algorithm: 'RSA-SHA256',
  payload: bytes.toString('base64'),
  signature: signature.toString('base64'),
};
fs.writeFileSync(outputPath, `${JSON.stringify(envelope, null, 2)}\n`, { flag: 'wx', mode: 0o644 });
