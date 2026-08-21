import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';

const pluginRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const ATTESTATION_SCHEMA = 'slotera-release-attestation/v1';
const REQUIRED_MATERIALS = ['build-provenance.json', 'checksums.sha256', 'release-manifest.json'];
const ARCHIVE_ROOT = 'slotera-booking/';
const sha256 = (data) => crypto.createHash('sha256').update(data).digest('hex');
const read = (file) => fs.readFileSync(file);
const writeExclusive = (file, data, mode) => fs.writeFileSync(file, data, { flag: 'wx', mode });
const publicFingerprint = (publicKey) => sha256(publicKey.export({ type: 'spki', format: 'der' }));
const generatedAtUtc = () => {
  const raw = String(process.env.SOURCE_DATE_EPOCH || '').trim();
  if (raw !== '' && !/^\d+$/.test(raw)) throw new Error('SOURCE_DATE_EPOCH must be an integer Unix timestamp');
  return (raw !== '' ? new Date(Number(raw) * 1000) : new Date()).toISOString().replace(/\.\d{3}Z$/, 'Z');
};
const isSha256 = (value) => typeof value === 'string' && /^[0-9a-f]{64}$/.test(value);

function assertSafeRelative(name) {
  if (typeof name !== 'string' || !name || name.includes('\\') || name.startsWith('/') || name.split('/').some((part) => part === '' || part === '.' || part === '..')) {
    throw new Error(`Unsafe archive/material path: ${String(name)}`);
  }
}

function validateAttestation(attestation, archivePath) {
  if (!attestation || typeof attestation !== 'object' || Array.isArray(attestation)) throw new Error('Attestation must be an object');
  if (attestation.schema !== ATTESTATION_SCHEMA) throw new Error(`Unsupported attestation schema: ${String(attestation.schema)}`);
  if (typeof attestation.generated_at_utc !== 'string' || Number.isNaN(Date.parse(attestation.generated_at_utc))) throw new Error('Attestation generated_at_utc is missing or invalid');
  if (!attestation.subject || typeof attestation.subject !== 'object') throw new Error('Attestation subject is required');
  if (attestation.subject.name !== path.basename(archivePath)) throw new Error('Attestation subject name does not match archive filename');
  if (!isSha256(attestation.subject.sha256)) throw new Error('Attestation subject SHA-256 is missing or invalid');
  if (!attestation.signature || typeof attestation.signature !== 'object') throw new Error('Attestation signature metadata is required');
  if (attestation.signature.algorithm !== 'RSA-PSS-SHA256') throw new Error('Unsupported attestation signature algorithm');
  if (attestation.signature.salt_length !== 32) throw new Error('Attestation RSA-PSS salt length must be 32');
  if (typeof attestation.signature.key_id !== 'string' || !/^sha256:[0-9a-f]{64}$/.test(attestation.signature.key_id)) throw new Error('Attestation signing key ID is missing or invalid');
  if (!Array.isArray(attestation.materials) || attestation.materials.length !== REQUIRED_MATERIALS.length) throw new Error('Attestation must contain the complete required materials allowlist');
  const materialNames = attestation.materials.map((m) => m?.name).sort();
  if (JSON.stringify(materialNames) !== JSON.stringify([...REQUIRED_MATERIALS].sort())) throw new Error('Attestation materials allowlist is incomplete or contains unexpected entries');
  for (const material of attestation.materials) {
    assertSafeRelative(material.name);
    if (!isSha256(material.sha256)) throw new Error(`Attested material SHA-256 is invalid: ${material.name}`);
  }
}

function keygen(privatePath, publicPath) {
  const { privateKey, publicKey } = crypto.generateKeyPairSync('rsa', { modulusLength: 3072, publicExponent: 0x10001 });
  writeExclusive(privatePath, privateKey.export({ type: 'pkcs8', format: 'pem' }), 0o600);
  writeExclusive(publicPath, publicKey.export({ type: 'spki', format: 'pem' }), 0o644);
  console.log(`KEY_ID=sha256:${publicFingerprint(publicKey)}`);
}

function signRelease(archivePath, privatePath, attestationPath, signaturePath, publicPath) {
  for (const output of [attestationPath, signaturePath, publicPath]) {
    if (fs.existsSync(output)) throw new Error(`Output already exists: ${output}`);
  }
  const privateKey = crypto.createPrivateKey(read(privatePath));
  const publicKey = crypto.createPublicKey(privateKey);
  const manifest = JSON.parse(read(path.join(pluginRoot, 'release-manifest.json')));
  const keyId = `sha256:${publicFingerprint(publicKey)}`;
  if (manifest.signing?.key_id !== keyId) throw new Error(`Signing key does not match release manifest: ${keyId}`);
  const materials = REQUIRED_MATERIALS.map((name) => ({ name, sha256: sha256(read(path.join(pluginRoot, name))) }));
  const candidate = String(manifest.candidate || '');
  if (candidate && !path.basename(archivePath).toLowerCase().includes(candidate.toLowerCase())) throw new Error(`Archive filename does not contain manifest candidate ${candidate}`);
  const attestation = {
    schema: ATTESTATION_SCHEMA,
    generated_at_utc: generatedAtUtc(),
    subject: { name: path.basename(archivePath), sha256: sha256(read(archivePath)) },
    materials,
    signature: { algorithm: 'RSA-PSS-SHA256', key_id: keyId, salt_length: 32 },
  };
  const encoded = Buffer.from(`${JSON.stringify(attestation, null, 2)}\n`, 'utf8');
  const signature = crypto.sign('sha256', encoded, {
    key: privateKey,
    padding: crypto.constants.RSA_PKCS1_PSS_PADDING,
    saltLength: 32,
  });
  writeExclusive(attestationPath, encoded, 0o644);
  writeExclusive(signaturePath, signature, 0o644);
  writeExclusive(publicPath, publicKey.export({ type: 'spki', format: 'pem' }), 0o644);
  console.log(`KEY_ID=${keyId}`);
}

function readCanonicalZipEntries(archivePath) {
  const data = read(archivePath);
  const entries = new Map();
  let offset = 0;
  while (offset + 4 <= data.length) {
    const sig = data.readUInt32LE(offset);
    if (sig === 0x02014b50 || sig === 0x06054b50) break;
    if (sig !== 0x04034b50) throw new Error(`Unexpected ZIP record at offset ${offset}`);
    if (offset + 30 > data.length) throw new Error('Truncated ZIP local header');
    const flags = data.readUInt16LE(offset + 6);
    const method = data.readUInt16LE(offset + 8);
    const compressedSize = data.readUInt32LE(offset + 18);
    const uncompressedSize = data.readUInt32LE(offset + 22);
    const nameLength = data.readUInt16LE(offset + 26);
    const extraLength = data.readUInt16LE(offset + 28);
    if ((flags & 0x0008) !== 0) throw new Error('ZIP data descriptors are not allowed in canonical releases');
    if (![0, 8].includes(method)) throw new Error(`Unsupported canonical ZIP compression method: ${method}`);
    const nameStart = offset + 30;
    const nameEnd = nameStart + nameLength;
    const contentStart = nameEnd + extraLength;
    const contentEnd = contentStart + compressedSize;
    if (contentEnd > data.length) throw new Error('Truncated ZIP entry');
    const archiveName = data.subarray(nameStart, nameEnd).toString('utf8');
    if (!archiveName.startsWith(ARCHIVE_ROOT)) throw new Error(`Unexpected archive root: ${archiveName}`);
    const relative = archiveName.slice(ARCHIVE_ROOT.length);
    assertSafeRelative(relative);
    if (entries.has(relative)) throw new Error(`Duplicate ZIP entry: ${relative}`);
    const compressed = data.subarray(contentStart, contentEnd);
    const content = method === 0 ? compressed : zlib.inflateRawSync(compressed);
    if (content.length !== uncompressedSize) throw new Error(`ZIP entry size mismatch: ${relative}`);
    entries.set(relative, { sha256: sha256(content), size: uncompressedSize });
    offset = contentEnd;
  }
  if (entries.size === 0) throw new Error('No files found in release ZIP');
  return entries;
}

function listExtractedFiles(root) {
  const files = new Map();
  const walk = (directory) => {
    for (const item of fs.readdirSync(directory, { withFileTypes: true })) {
      const absolute = path.join(directory, item.name);
      if (item.isSymbolicLink()) throw new Error(`Symlink is not allowed in extracted release tree: ${absolute}`);
      if (item.isDirectory()) walk(absolute);
      else if (item.isFile()) {
        const relative = path.relative(root, absolute).replaceAll('\\', '/');
        assertSafeRelative(relative);
        files.set(relative, { sha256: sha256(read(absolute)), size: fs.statSync(absolute).size });
      } else throw new Error(`Unsupported filesystem entry in extracted release tree: ${absolute}`);
    }
  };
  walk(root);
  return files;
}

function verifyExtractedTreeAgainstArchive(archivePath, extractedRoot) {
  const archiveEntries = readCanonicalZipEntries(archivePath);
  const extractedEntries = listExtractedFiles(path.resolve(extractedRoot));
  if (archiveEntries.size !== extractedEntries.size) throw new Error(`Extracted tree file count differs from signed ZIP: ${extractedEntries.size} != ${archiveEntries.size}`);
  for (const [name, expected] of archiveEntries) {
    const actual = extractedEntries.get(name);
    if (!actual) throw new Error(`Extracted tree is missing signed file: ${name}`);
    if (actual.size !== expected.size || actual.sha256 !== expected.sha256) throw new Error(`Extracted tree differs from signed ZIP: ${name}`);
  }
  for (const name of extractedEntries.keys()) if (!archiveEntries.has(name)) throw new Error(`Extracted tree contains unsigned extra file: ${name}`);
}

function verifyRelease(archivePath, attestationPath, signaturePath, publicPath, extractedRoot = '') {
  const encoded = read(attestationPath);
  let attestation;
  try { attestation = JSON.parse(encoded); } catch { throw new Error('Attestation JSON is invalid'); }
  validateAttestation(attestation, archivePath);
  const publicKey = crypto.createPublicKey(read(publicPath));
  const keyId = `sha256:${publicFingerprint(publicKey)}`;
  if (attestation.signature.key_id !== keyId) throw new Error('Public key fingerprint does not match attestation');
  if (attestation.subject.sha256 !== sha256(read(archivePath))) throw new Error('Archive hash does not match attestation');
  const valid = crypto.verify('sha256', encoded, {
    key: publicKey,
    padding: crypto.constants.RSA_PKCS1_PSS_PADDING,
    saltLength: 32,
  }, read(signaturePath));
  if (!valid) throw new Error('Detached release signature is invalid');
  const manifestMaterial = attestation.materials.find((m) => m.name === 'release-manifest.json');
  if (!manifestMaterial) throw new Error('Attested release manifest material is missing');
  const zipEntries = readCanonicalZipEntries(archivePath);
  for (const material of attestation.materials) {
    const entry = zipEntries.get(material.name);
    if (!entry || entry.sha256 !== material.sha256) throw new Error(`Attested material does not match signed ZIP: ${material.name}`);
  }
  const manifestEntry = zipEntries.get('release-manifest.json');
  if (!manifestEntry || manifestEntry.sha256 !== manifestMaterial.sha256) throw new Error('Signed release manifest is not bound to the archive');
  if (extractedRoot) verifyExtractedTreeAgainstArchive(archivePath, extractedRoot);
  console.log(`Verified signed release: ${attestation.subject.name}`);
  console.log(`KEY_ID=${keyId}`);
}

const [command, ...args] = process.argv.slice(2);
if (command === 'keygen' && args.length === 2) keygen(...args);
else if (command === 'sign' && args.length === 5) signRelease(...args);
else if (command === 'verify' && (args.length === 4 || args.length === 5)) verifyRelease(...args);
else throw new Error('Usage: keygen <private.pem> <public.pem> | sign <archive> <private.pem> <attestation.json> <signature.sig> <public.pem> | verify <archive> <attestation.json> <signature.sig> <public.pem> [extracted-plugin-root]');
