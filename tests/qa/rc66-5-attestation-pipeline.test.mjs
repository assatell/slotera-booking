import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { resolvePython } from '../../tools/python-runtime.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const sha256 = (data) => crypto.createHash('sha256').update(data).digest('hex');
const canonicalText = (data) => Buffer.from(data.toString('utf8').replace(/\r\n?/g, '\n'), 'utf8');
const readText = (name) => fs.readFileSync(path.join(root, name), 'utf8');

function copyTree(src, dst) {
  fs.cpSync(src, dst, { recursive: true, filter: (p) => !p.includes(`${path.sep}node_modules${path.sep}`) && !p.includes(`${path.sep}.git${path.sep}`) });
}

test('release metadata binds current candidate identity and changelog top entry', () => {
  const manifest = JSON.parse(readText('release-manifest.json'));
  const provenance = JSON.parse(readText('build-provenance.json'));
  const top = readText('CHANGELOG.md').match(/^##\s+\d+\.\d+\.\d+\s+(RC\d+(?:\.\d+)*)\b/m);
  assert.match(manifest.candidate, /^RC\d+(?:\.\d+)*$/);
  assert.equal(top?.[1], manifest.candidate);
  assert.match(manifest.release_notes, new RegExp(manifest.candidate.replace('.', '\\.')));

  assert.equal(provenance.version, manifest.version);
  assert.match(provenance.candidate, /^RC\d+(?:\.\d+)*$/);
  assert.equal(provenance.schema, 'slotera-build-provenance/v3');

  if (provenance.vcs?.state === 'git-clean') {
    assert.equal(provenance.candidate, manifest.candidate);
    assert.equal(provenance.builder.version, manifest.builder.version);
  } else {
    assert.equal(provenance.source?.sha256, manifest.lineage?.previous_source?.sha256);
    assert.equal(provenance.source?.tree_sha256, manifest.lineage?.previous_source?.tree_sha256);
  }
});

test('release attestation verifier is fail-closed and compares extracted tree to signed ZIP', () => {
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'sltr-attestation-'));
  try {
    const tree = path.join(temp, 'tree');
    copyTree(root, tree);
    const manifest = JSON.parse(readText('release-manifest.json'));
    for (const args of [['init'], ['config','user.email','qa@example.invalid'], ['config','user.name','Slotera QA'], ['add','.'], ['commit','-m','qa fixture'], ['tag', manifest.source.tag]]) {
      const git = spawnSync('git', args, { cwd: tree, encoding: 'utf8' });
      assert.equal(git.status, 0, git.stderr || git.stdout);
    }
    const archive = path.join(temp, `slotera-booking-${manifest.version}-${manifest.candidate.toLowerCase()}.zip`);
    const build = spawnSync(process.execPath, ['tools/build-rc.mjs', '--output', archive, '--source-date-epoch', '1786884060'], { cwd: tree, encoding: 'utf8' });
    assert.equal(build.status, 0, build.stderr || build.stdout);

    const { privateKey, publicKey } = crypto.generateKeyPairSync('rsa', { modulusLength: 2048 });
    const publicDer = publicKey.export({ type: 'spki', format: 'der' });
    const keyId = `sha256:${sha256(publicDer)}`;
    const materialNames = ['build-provenance.json', 'checksums.sha256', 'release-manifest.json'];
    const attestation = {
      schema: 'slotera-release-attestation/v1',
      generated_at_utc: '2026-08-16T15:21:00Z',
      subject: { name: path.basename(archive), sha256: sha256(fs.readFileSync(archive)) },
      materials: materialNames.map((name) => ({ name, sha256: sha256(canonicalText(fs.readFileSync(path.join(tree, name)))) })),
      signature: { algorithm: 'RSA-PSS-SHA256', key_id: keyId, salt_length: 32 },
    };
    const encoded = Buffer.from(`${JSON.stringify(attestation, null, 2)}\n`);
    const signature = crypto.sign('sha256', encoded, { key: privateKey, padding: crypto.constants.RSA_PKCS1_PSS_PADDING, saltLength: 32 });
    const attPath = path.join(temp, 'att.json');
    const sigPath = path.join(temp, 'att.sig');
    const pubPath = path.join(temp, 'public.pem');
    fs.writeFileSync(attPath, encoded);
    fs.writeFileSync(sigPath, signature);
    fs.writeFileSync(pubPath, publicKey.export({ type: 'spki', format: 'pem' }));

    const extracted = path.join(temp, 'extracted');
    const python = resolvePython();
    const extraction = spawnSync(python.command, [...python.prefix, '-c', 'import pathlib,sys,zipfile; zipfile.ZipFile(sys.argv[1]).extractall(sys.argv[2])', archive, extracted], { encoding: 'utf8' });
    assert.equal(extraction.status, 0, extraction.stderr || extraction.stdout);
    const extractedPlugin = path.join(extracted, 'slotera-booking');
    const ok = spawnSync(process.execPath, ['tools/release-attestation.mjs', 'verify', archive, attPath, sigPath, pubPath, extractedPlugin], { cwd: tree, encoding: 'utf8' });
    assert.equal(ok.status, 0, ok.stderr || ok.stdout);

    fs.appendFileSync(path.join(extractedPlugin, 'README.md'), '\nCONTROLLED TAMPER\n');
    const tampered = spawnSync(process.execPath, ['tools/release-attestation.mjs', 'verify', archive, attPath, sigPath, pubPath, extractedPlugin], { cwd: tree, encoding: 'utf8' });
    assert.notEqual(tampered.status, 0, 'tampered extracted tree must fail verification even after local metadata regeneration');
    assert.match(tampered.stderr, /differs from signed ZIP|file count differs|unsigned extra file/);

    const incomplete = { ...attestation };
    delete incomplete.materials;
    const badEncoded = Buffer.from(`${JSON.stringify(incomplete, null, 2)}\n`);
    fs.writeFileSync(attPath, badEncoded);
    fs.writeFileSync(sigPath, crypto.sign('sha256', badEncoded, { key: privateKey, padding: crypto.constants.RSA_PKCS1_PSS_PADDING, saltLength: 32 }));
    const failClosed = spawnSync(process.execPath, ['tools/release-attestation.mjs', 'verify', archive, attPath, sigPath, pubPath], { cwd: tree, encoding: 'utf8' });
    assert.notEqual(failClosed.status, 0);
    assert.match(failClosed.stderr, /complete required materials allowlist/);
  } finally {
    fs.rmSync(temp, { recursive: true, force: true });
  }
});

test('deterministic builder keeps strict ZIP size below 8 MiB', () => {
  const builder = readText('tools/build-rc.py');
  assert.match(builder, /max_size = 8 \* 1024 \* 1024/);
  assert.match(builder, /release ZIP exceeds strict <8 MiB gate/);
  assert.match(builder, /write_zip\(output, entries, dt\)/);
  const metadata = readText('tools/release-metadata.mjs');
  assert.doesNotMatch(metadata, /files:\s*releaseFiles/);
  assert.match(metadata, /tree_manifest/);
});
