import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';

const [, , archivePath, privateKeyPath, packageUrl, outputPath] = process.argv;
if (!archivePath || !privateKeyPath || !packageUrl || !outputPath) {
  throw new Error('Usage: node tools/update-envelope.mjs ARCHIVE PRIVATE_KEY PACKAGE_URL OUTPUT');
}
if (fs.existsSync(outputPath)) throw new Error('Output already exists');
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
  issued_at: new Date().toISOString(),
  source_commit: String(manifest.source?.commit || ''),
  source_tag: String(manifest.source?.tag || ''),
};
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
