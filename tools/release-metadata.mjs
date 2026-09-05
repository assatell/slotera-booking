import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import childProcess from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { applyUpdateUri } from './update-uri.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const manifestPath = path.join(root, 'release-manifest.json');
const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
const version = String(manifest.version || '');
const builderVersion = String(manifest.builder?.version || '');
const packageLabel = manifest.channel === 'release-candidate' ? 'Release candidate package' : 'Production package';
if (!/^\d+\.\d+\.\d+$/.test(version)) throw new Error('release-manifest.json has an invalid version');
if (!/^\d+\.\d+\.\d+$/.test(builderVersion)) throw new Error('release-manifest.json has an invalid builder version');
if (manifest.schema !== 'slotera-release-manifest/v2') throw new Error('Unsupported release manifest schema');

const utf8 = (file) => fs.readFileSync(path.join(root, file), 'utf8');
const write = (file, value) => fs.writeFileSync(path.join(root, file), value.replace(/\r\n/g, '\n'), 'utf8');
const sha256 = (data) => crypto.createHash('sha256').update(data).digest('hex');
const generated = new Set(['build-provenance.json', 'checksums.sha256']);
const textExtensions = new Set(['.css', '.html', '.ini', '.js', '.json', '.md', '.mjs', '.php', '.po', '.pot', '.sql', '.svg', '.txt', '.xml', '.yaml', '.yml']);
const textFilenames = new Set(['CHANGELOG', 'LICENSE', 'README']);
const archiveExclusions = (manifest.archive?.exclude || []).map((item) => String(item).replaceAll('\\', '/').replace(/^\.\//, ''));
const vcsPolicy = manifest.source?.type === 'git' ? manifest.source : null;
const vcsRequired = vcsPolicy?.required === true || process.env.SLTR_VCS_REQUIRED === '1';
const expectedVcsTag = String(vcsPolicy?.tag || process.env.SLTR_VCS_TAG || '').trim();
const normalizedRelativePath = (absolute) => path.relative(root, absolute).replaceAll('\\', '/');
const utf8PathCompare = (a, b) => Buffer.compare(Buffer.from(a, 'utf8'), Buffer.from(b, 'utf8'));
const isArchiveExcluded = (relative) => archiveExclusions.some((item) => item.endsWith('/') ? relative.startsWith(item) : relative === item);
const canonicalFileData = (absolute) => {
  const data = fs.readFileSync(absolute);
  const extension = path.extname(absolute).toLowerCase();
  if (!textExtensions.has(extension) && !textFilenames.has(path.basename(absolute))) return data;
  return Buffer.from(data.toString('utf8').replace(/\r\n?/g, '\n'), 'utf8');
};

function listFiles({ includeGenerated = false } = {}) {
  const found = [];
  const walk = (directory) => {
    for (const item of fs.readdirSync(directory, { withFileTypes: true })) {
      if (item.name === '.git' || item.name === 'node_modules') continue;
      const absolute = path.join(directory, item.name);
      if (item.isDirectory()) walk(absolute);
      else {
        const relative = normalizedRelativePath(absolute);
        if (isArchiveExcluded(relative)) continue;
        if (includeGenerated || !generated.has(item.name)) found.push(absolute);
      }
    }
  };
  walk(root);
  return found.sort((a, b) => utf8PathCompare(normalizedRelativePath(a), normalizedRelativePath(b)));
}

function fileRecords({ includeGenerated = false } = {}) {
  return listFiles({ includeGenerated }).map((absolute) => {
    const data = canonicalFileData(absolute);
    return { path: normalizedRelativePath(absolute), sha256: sha256(data), size: data.length };
  });
}

function treeHash({ includeGenerated = false } = {}) {
  const canonical = fileRecords({ includeGenerated })
    .map((entry) => `${entry.path}\0${entry.sha256}\0${entry.size}\n`)
    .join('');
  return sha256(Buffer.from(canonical, 'utf8'));
}

function git(args) {
  try {
    return childProcess.execFileSync('git', args, { cwd: root, encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();
  } catch {
    return '';
  }
}

function detectVcs() {
  const capturedState = String(process.env.SLTR_VCS_STATE || '').trim();
  if (capturedState !== '') {
    if (!['source-archive', 'git-clean', 'git-dirty'].includes(capturedState)) throw new Error(`Invalid captured VCS state: ${capturedState}`);
    if (capturedState === 'source-archive') {
      if (vcsRequired) throw new Error('VCS-bound build cannot use source-archive state');
      return { commit: null, tag: null, dirty: null, state: 'source-archive', note: 'No Git metadata is present in the release source tree.' };
    }

    const commit = String(process.env.SLTR_VCS_COMMIT || '').trim();
    const tag = String(process.env.SLTR_VCS_TAG || '').trim() || null;
    const dirtyRaw = String(process.env.SLTR_VCS_DIRTY || '').trim();

    if (!/^[0-9a-f]{40}$/.test(commit)) throw new Error('Captured VCS commit must be a full 40-character lowercase Git SHA-1');
    if (!['0', '1'].includes(dirtyRaw)) throw new Error('Captured VCS dirty state must be 0 or 1');

    const dirty = dirtyRaw === '1';
    if ((capturedState === 'git-dirty') !== dirty) throw new Error('Captured VCS state/dirty mismatch');

    if (vcsRequired) {
      if (dirty) throw new Error('VCS-bound build requires a clean captured tree');
      if (!expectedVcsTag || tag !== expectedVcsTag) throw new Error(`VCS-bound build requires exact tag ${expectedVcsTag || '(missing)'}`);
    }

    return { commit, tag, dirty, state: capturedState, capture: 'pre-metadata' };
  }

  const commit = git(['rev-parse', 'HEAD']);
  if (!commit) {
    if (vcsRequired) throw new Error('VCS-bound build requires Git metadata and a real commit/tag');
    return { commit: null, tag: null, dirty: null, state: 'source-archive', note: 'No Git metadata is present in the release source tree.' };
  }
  const tag = git(['describe', '--tags', '--exact-match', 'HEAD']) || null;
  const dirty = git(['status', '--porcelain']) !== '';
  if (vcsRequired) {
    if (dirty) throw new Error('VCS-bound build requires a clean Git working tree');
    if (!expectedVcsTag || tag !== expectedVcsTag) throw new Error(`VCS-bound build requires exact tag ${expectedVcsTag || '(missing)'}`);
  }
  return { commit, tag, dirty, state: dirty ? 'git-dirty' : 'git-clean' };
}

function runtimeInfo() {
  return {
    reproducibility: 'observed host runtime intentionally excluded from the in-archive provenance so identical source + SOURCE_DATE_EPOCH builds remain byte-for-byte reproducible',
    contract: manifest.builder.runtime,
  };
}

function buildCommand() {
  return process.env.SLTR_BUILD_COMMAND || `node tools/release-metadata.mjs ${process.argv[2] || 'verify'}`;
}

function generatedAtUtc() {
  const raw = String(process.env.SOURCE_DATE_EPOCH || '').trim();
  if (raw !== '') {
    if (!/^\d+$/.test(raw)) throw new Error('SOURCE_DATE_EPOCH must be an integer Unix timestamp');
    return new Date(Number(raw) * 1000).toISOString().replace(/\.\d{3}Z$/, 'Z');
  }
  return new Date().toISOString().replace(/\.\d{3}Z$/, 'Z');
}

function syncVersions() {
  const before = treeHash();
  const targets = [];

  const updateUriState = applyUpdateUri(utf8('slotera-booking.php'), process.env.SLTR_UPDATE_URI || manifest.update_uri || '');
  write('slotera-booking.php', updateUriState.source);
  targets.push(updateUriState.uri === '' ? 'slotera-booking.php:update-uri-ready-unbound' : 'slotera-booking.php:update-uri-bound');
  const syncText = (file, replacements) => {
    let source = utf8(file);
    for (const [pattern, replacement, label] of replacements) {
      if (!pattern.test(source)) throw new Error(`Version target not found: ${label}`);
      source = source.replace(pattern, replacement);
      targets.push(label);
    }
    write(file, source);
  };

  syncText('slotera-booking.php', [
    [/(^ \* Version:\s*)\d+\.\d+\.\d+/m, `$1${version}`, 'slotera-booking.php:plugin-header'],
    [/define\('SLTR_VERSION',\s*'\d+\.\d+\.\d+'\);/, `define('SLTR_VERSION', '${version}');`, 'slotera-booking.php:SLTR_VERSION'],
  ]);
  syncText('includes/build.php', [
    [/define\('SLTR_BUILD_VERSION',\s*'\d+\.\d+\.\d+'\);/, `define('SLTR_BUILD_VERSION', '${version}');`, 'includes/build.php:SLTR_BUILD_VERSION'],
    [/define\('SLTR_BUILD_ID',\s*'\d+\.\d+\.\d+'\);/, `define('SLTR_BUILD_ID', '${version}');`, 'includes/build.php:SLTR_BUILD_ID'],
  ]);

  const pkg = JSON.parse(utf8('package.json'));
  pkg.version = version;
  write('package.json', `${JSON.stringify(pkg, null, 2)}\n`);
  targets.push('package.json:version');

  const sbom = JSON.parse(utf8('sbom.cdx.json'));
  if (!sbom?.metadata?.component) throw new Error('sbom.cdx.json is missing metadata.component');
  sbom.metadata.component.version = version;
  const candidateProperty = Array.isArray(sbom.metadata.component.properties)
    ? sbom.metadata.component.properties.find((property) => property?.name === 'slotera:release-candidate')
    : null;
  if (!candidateProperty) throw new Error('sbom.cdx.json is missing slotera:release-candidate');
  candidateProperty.value = String(manifest.candidate || '');
  write('sbom.cdx.json', `${JSON.stringify(sbom, null, 2)}\n`);
  targets.push('sbom.cdx.json:component-version');
  targets.push('sbom.cdx.json:release-candidate');

  syncText('README.md', [
    [/(?:Production|Release candidate) package for Slotera Booking v\d+\.\d+\.\d+\./, `${packageLabel} for Slotera Booking v${version}.`, 'README.md:package-version'],
  ]);

  const after = treeHash();
  return { before, after, targets };
}

function generateMetadata(syncState = null) {
  const releaseFiles = fileRecords();
  const releaseTreeSha256 = treeHash();
  const manifestSha256 = sha256(fs.readFileSync(manifestPath));
  const vcs = detectVcs();
  const lineageSource = manifest.lineage?.previous_source || {};
  const previousSource = {
    artifact: lineageSource.artifact || null,
    sha256: lineageSource.sha256 || null,
    attestation_schema: lineageSource.attestation_schema || null,
    attestation_key_id: lineageSource.attestation_key_id || null,
    tree_sha256: lineageSource.tree_sha256 || null,
  };

  const source = vcs.state === 'git-clean'
    ? {
        type: 'git',
        repository: vcsPolicy?.repository || null,
        commit: vcs.commit,
        tag: vcs.tag,
        dirty: vcs.dirty,
      }
    : {
        type: 'archive',
        ...previousSource,
      };
  const canonicalBuildCommand = buildCommand();
  const sync = syncState || { before: releaseTreeSha256, after: releaseTreeSha256, targets: [] };

  const provenance = {
    schema: 'slotera-build-provenance/v3',
    version,
    candidate: manifest.candidate || null,
    channel: manifest.channel || 'production',
    generated_at_utc: generatedAtUtc(),
    timestamp_semantics: 'generated_at_utc is the reproducible SOURCE_DATE_EPOCH selected for this build, not a VCS commit time',
    release_manifest_sha256: manifestSha256,
    builder: {
      name: manifest.builder.name,
      version: builderVersion,
      declared_runtime: manifest.builder.runtime,
      runtime: runtimeInfo(),
    },
    vcs,
    source,
    lineage: {
      previous_source: previousSource,
    },
    build: {
      command: canonicalBuildCommand,
      metadata_command: 'node tools/release-metadata.mjs prepare',
      archive_root: manifest.archive?.root || 'slotera-booking/',
      output: process.env.SLTR_BUILD_OUTPUT || `slotera-booking-${version}.zip`,
      source_date_epoch_utc: generatedAtUtc(),
    },
    hashes: {
      source_artifact_sha256: source.type === 'archive' ? source.sha256 : null,
      release_manifest_sha256: manifestSha256,
      pre_version_sync_tree_sha256: sync.before,
      release_tree_sha256: releaseTreeSha256,
    },
    transformation_chain: [
      {
        order: 1,
        name: 'source-material-verification',
        input_artifact: previousSource.artifact,
        input_sha256: previousSource.sha256,
        attestation_schema: previousSource.attestation_schema,
        attestation_key_id: previousSource.attestation_key_id,
      },
      {
        order: 2,
        name: 'source-to-release-working-tree',
        input_tree_sha256: previousSource.tree_sha256,
        output_tree_sha256: sync.before,
        changes_manifest_sha256: manifestSha256,
        latest_change: Array.isArray(manifest.release_changes) && manifest.release_changes.length ? manifest.release_changes.at(-1) : null,
        note: 'The complete ordered release change history is bound by release_manifest_sha256; only the latest transformation label is repeated here to avoid duplicating the signed manifest.'
      },
      {
        order: 3,
        name: 'manifest-version-sync',
        version,
        input_tree_sha256: sync.before,
        output_tree_sha256: sync.after,
        generated_targets: sync.targets,
      },
      {
        order: 4,
        name: 'release-tree-snapshot',
        output_tree_sha256: releaseTreeSha256,
        file_count: releaseFiles.length,
      },
      {
        order: 5,
        name: 'metadata-and-checksum-generation',
        inputs: ['release-manifest.json', 'release-tree-snapshot'],
        outputs: ['build-provenance.json', 'checksums.sha256'],
        note: 'Generated metadata is authenticated by checksums and, for official releases, by the detached release attestation.',
      },
      {
        order: 6,
        name: 'archive-packaging',
        command: canonicalBuildCommand,
        output: process.env.SLTR_BUILD_OUTPUT || `slotera-booking-${version}.zip`,
        signing_policy: {
          algorithm: manifest.signing?.algorithm || null,
          key_id: manifest.signing?.key_id || null,
          status: process.env.SLTR_SIGNING_STATUS || 'not-performed-in-this-build',
        },
        note: 'Official releases are signed after packaging. The final archive SHA-256 is recorded outside the ZIP in the detached attestation to avoid self-reference.',
      },
    ],
    file_count: releaseFiles.length,
    tree_manifest: { path: 'checksums.sha256', semantics: 'canonical UTF-8 path ordered SHA-256 manifest; its digest is bound by the detached release attestation' },
  };

  const provenanceJson = `${JSON.stringify(provenance, null, 2)}\n`;
  write('build-provenance.json', provenanceJson);
  const checksummedFiles = [...releaseFiles, {
    path: 'build-provenance.json',
    sha256: sha256(Buffer.from(provenanceJson)),
    size: Buffer.byteLength(provenanceJson),
  }].sort((a, b) => utf8PathCompare(a.path, b.path));
  write('checksums.sha256', `${checksummedFiles.map((entry) => `${entry.sha256}  ${entry.path}`).join('\n')}\n`);
}

function verify() {
  const checks = [
    [/^ \* Version: (\d+\.\d+\.\d+)$/m, utf8('slotera-booking.php'), 'plugin header'],
    [/define\('SLTR_VERSION', '(\d+\.\d+\.\d+)'\);/, utf8('slotera-booking.php'), 'SLTR_VERSION'],
    [/define\('SLTR_BUILD_VERSION', '(\d+\.\d+\.\d+)'\);/, utf8('includes/build.php'), 'SLTR_BUILD_VERSION'],
    [/define\('SLTR_BUILD_ID', '(\d+\.\d+\.\d+)'\);/, utf8('includes/build.php'), 'SLTR_BUILD_ID'],
    [new RegExp(`^${packageLabel.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')} for Slotera Booking v(\\d+\\.\\d+\\.\\d+)\\.$`, 'm'), utf8('README.md'), 'README'],
  ];
  for (const [pattern, source, label] of checks) {
    const match = source.match(pattern);
    if (!match || match[1] !== version) throw new Error(`${label} does not match release manifest ${version}`);
  }
  if (JSON.parse(utf8('package.json')).version !== version) throw new Error('package.json does not match release manifest');
  const candidate = String(manifest.candidate || '');
  if (!/^RC\d+(?:\.\d+)*$/.test(candidate)) throw new Error('release manifest candidate is missing or invalid');
  const firstChangelog = utf8('CHANGELOG.md').match(/^##\s+\d+\.\d+\.\d+\s+(RC\d+(?:\.\d+)*)\b/m);
  if (!firstChangelog || firstChangelog[1] !== candidate) throw new Error(`CHANGELOG top candidate does not match release manifest ${candidate}`);

  const provenance = JSON.parse(utf8('build-provenance.json'));
  if (provenance.schema !== 'slotera-build-provenance/v3') throw new Error('Unexpected provenance schema');
  if (provenance.version !== version || provenance.candidate !== manifest.candidate || provenance.release_manifest_sha256 !== sha256(fs.readFileSync(manifestPath))) throw new Error('provenance does not match release manifest');
  if (provenance.builder?.version !== builderVersion) throw new Error('provenance builder version mismatch');
  if (!Object.hasOwn(provenance.vcs || {}, 'commit') || !Object.hasOwn(provenance.vcs || {}, 'tag')) throw new Error('provenance must contain commit/tag fields');

  if (provenance.vcs?.state === 'git-clean') {
    if (!/^[0-9a-f]{40}$/.test(String(provenance.vcs.commit || ''))) throw new Error('git-clean provenance must contain a full commit hash');
    if (provenance.vcs.dirty !== false) throw new Error('git-clean provenance must record dirty=false');
  }

  if (vcsRequired) {
    if (provenance.vcs?.state !== 'git-clean') throw new Error('VCS-bound provenance must be git-clean');
    if (!expectedVcsTag || provenance.vcs?.tag !== expectedVcsTag) throw new Error('VCS-bound provenance tag mismatch');
    if (provenance.source?.type !== 'git') throw new Error('VCS-bound provenance must use Git as the direct source');
    if (provenance.source?.repository !== vcsPolicy?.repository) throw new Error('VCS-bound provenance repository mismatch');
  }

  if (provenance.source?.type === 'archive') {
    if (vcsRequired) throw new Error('Required VCS provenance cannot fall back to an archive source');
    if (provenance.source?.sha256 !== manifest.lineage?.previous_source?.sha256) throw new Error('provenance lineage source hash mismatch');
  } else if (provenance.source?.type === 'git') {
    if (provenance.source?.commit !== provenance.vcs?.commit) throw new Error('Git source commit does not match VCS provenance');
    if (provenance.source?.tag !== provenance.vcs?.tag) throw new Error('Git source tag does not match VCS provenance');
  } else {
    throw new Error('Unsupported provenance source type');
  }
  if (!Array.isArray(provenance.transformation_chain) || provenance.transformation_chain.length < 6) throw new Error('provenance transformation chain is incomplete');
  if (!provenance.build?.command) throw new Error('provenance exact build command is missing');

  const checksumLines = utf8('checksums.sha256').trim().split(/\r?\n/);
  const checksumEntries = checksumLines.map((line) => {
    const match = line.match(/^([0-9a-f]{64})  (.+)$/);
    if (!match) throw new Error(`Invalid checksums.sha256 line: ${line}`);
    return { sha256: match[1], path: match[2] };
  });
  for (const entry of checksumEntries) {
    const absolute = path.join(root, entry.path);
    if (!fs.existsSync(absolute) || sha256(canonicalFileData(absolute)) !== entry.sha256) throw new Error(`checksum mismatch: ${entry.path}`);
  }
  if (provenance.file_count !== checksumEntries.length - 1) throw new Error('provenance file_count does not match checksum tree manifest');
  const provenanceChecksum = checksumLines.find((line) => line.endsWith('  build-provenance.json'));
  if (!provenanceChecksum || !provenanceChecksum.startsWith(`${sha256(fs.readFileSync(path.join(root, 'build-provenance.json')))}  `)) throw new Error('checksums.sha256 does not authenticate build-provenance.json');
  console.log(`Verified release ${version} ${manifest.candidate}: ${provenance.file_count} files; builder ${builderVersion}`);
}

const command = process.argv[2] || 'verify';
if (command === 'sync') syncVersions();
else if (command === 'generate') generateMetadata();
else if (command === 'prepare') { const sync = syncVersions(); generateMetadata(sync); verify(); }
else if (command === 'verify') verify();
else if (command === 'tree-hash') console.log(treeHash());
else throw new Error(`Unknown command: ${command}`);
