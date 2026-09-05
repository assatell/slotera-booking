import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const manifest = JSON.parse(fs.readFileSync(new URL('../../release-manifest.json', import.meta.url), 'utf8'));
const builder = fs.readFileSync(new URL('../../tools/build-rc.py', import.meta.url), 'utf8');
const metadata = fs.readFileSync(new URL('../../tools/release-metadata.mjs', import.meta.url), 'utf8');
const plugin = fs.readFileSync(new URL('../../slotera-booking.php', import.meta.url), 'utf8');
const attributes = fs.readFileSync(new URL('../../.gitattributes', import.meta.url), 'utf8');
const robots = fs.readFileSync(new URL('../../includes/Application/Services/RobotsTxtService.php', import.meta.url), 'utf8');

test('release payload excludes source QA and build tooling', () => {
  const excluded = new Set(manifest.archive.exclude);
  for (const item of ['tests/', 'tools/', 'QA.md', 'composer.json', 'package.json', 'pnpm-lock.yaml', '.gitattributes', '.gitignore']) {
    assert.ok(excluded.has(item), `archive exclusion missing: ${item}`);
  }
  assert.match(builder, /is_excluded\(rel, exclusions\)/);
  assert.match(metadata, /isArchiveExcluded\(relative\)/);
  assert.ok(builder.includes(".removeprefix('./')"));
  assert.ok(!builder.includes(".lstrip('./')"));
  assert.match(builder, /TEXT_FILENAMES = \{[^}]*'\.gitattributes'[^}]*'\.gitignore'[^}]*\}/s);
});

test('release content is canonicalized independently of checkout line endings', () => {
  assert.match(builder, /def canonical_file_bytes\(path: pathlib\.Path\) -> bytes/);
  assert.match(builder, /replace\(b'\\r\\n', b'\\n'\)\.replace\(b'\\r', b'\\n'\)/);
  assert.match(metadata, /replace\(\/\\r\\n\?\/g, '\\n'\)/);
  for (const extension of ['php', 'js', 'mjs', 'json', 'css', 'md', 'po', 'py']) {
    assert.match(attributes, new RegExp(`\\*\\.${extension} text eol=lf`));
  }
  assert.match(robots, /str_replace\(\["\\r\\n", "\\r"\], "\\n", \$content\)/);
  assert.doesNotMatch(robots.replace(/\r\n/g, '\n'), /\r/);
});

test('first-party Update URI is explicit and HTTPS-bound', () => {
  assert.equal(manifest.update_uri, 'https://getslotera.com/?plugin=slotera-booking');
  assert.match(plugin, /\* Update URI: https:\/\/getslotera\.com\/\?plugin=slotera-booking/);
  assert.match(plugin, /define\('SLTR_UPDATE_URI', 'https:\/\/getslotera\.com\/\?plugin=slotera-booking'\);/);
  assert.match(metadata, /process\.env\.SLTR_UPDATE_URI \|\| manifest\.update_uri/);
});

test('fresh cross-platform worktrees produce the same install ZIP', { timeout: 60000 }, () => {
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'sltr-rc6710-eol-'));
  const git = (cwd, args, env) => {
    const result = spawnSync('git', args, { cwd, encoding: 'utf8', env });
    assert.equal(result.status, 0, result.stderr || result.stdout);
  };
  const sha256 = (file) => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');

  try {
    const source = path.join(temp, 'source');
    const lf = path.join(temp, 'lf');
    const crlf = path.join(temp, 'crlf');
    fs.cpSync(root, source, {
      recursive: true,
      filter: (item) => !item.includes(`${path.sep}.git${path.sep}`) && !item.includes(`${path.sep}node_modules${path.sep}`),
    });

    const fixedEnv = { ...process.env, GIT_AUTHOR_DATE: '2026-09-05T12:00:00Z', GIT_COMMITTER_DATE: '2026-09-05T12:00:00Z' };
    git(source, ['init'], fixedEnv);
    git(source, ['config', 'user.email', 'qa@example.invalid'], fixedEnv);
    git(source, ['config', 'user.name', 'Slotera QA'], fixedEnv);
    git(source, ['add', '.'], fixedEnv);
    git(source, ['commit', '-m', 'cross-EOL fixture'], fixedEnv);
    git(source, ['tag', manifest.source.tag], fixedEnv);
    git(temp, ['-c', 'core.autocrlf=false', 'clone', source, lf], fixedEnv);
    git(temp, ['-c', 'core.autocrlf=true', 'clone', source, crlf], fixedEnv);

    for (const tree of [lf, crlf]) {
      const status = spawnSync('git', ['status', '--porcelain'], { cwd: tree, encoding: 'utf8' });
      assert.equal(status.stdout, '', 'fixture must be Git-clean before the build');
    }
    assert.equal(
      spawnSync('git', ['rev-parse', 'HEAD'], { cwd: lf, encoding: 'utf8' }).stdout,
      spawnSync('git', ['rev-parse', 'HEAD'], { cwd: crlf, encoding: 'utf8' }).stdout,
      'fixtures must use the same exact source commit',
    );

    const outputName = `slotera-booking-${manifest.version}-${manifest.candidate.toLowerCase()}.zip`;
    const outLf = path.join(temp, 'out-lf', outputName);
    const outCrlf = path.join(temp, 'out-crlf', outputName);
    fs.mkdirSync(path.dirname(outLf));
    fs.mkdirSync(path.dirname(outCrlf));
    for (const [tree, output] of [[lf, outLf], [crlf, outCrlf]]) {
      const build = spawnSync(process.execPath, ['tools/build-rc.mjs', '--output', output, '--source-date-epoch', '1788619200'], { cwd: tree, encoding: 'utf8' });
      assert.equal(build.status, 0, build.stderr || build.stdout);
    }
    assert.equal(sha256(outLf), sha256(outCrlf));
    const listing = spawnSync('python3', ['-c', "import sys,zipfile; print('\\n'.join(zipfile.ZipFile(sys.argv[1]).namelist()))", outLf], { encoding: 'utf8', env: { ...process.env, PYTHONDONTWRITEBYTECODE: '1' } });
    assert.equal(listing.status, 0, listing.stderr || listing.stdout);
    assert.doesNotMatch(listing.stdout, /^slotera-booking\/(?:tests|tools)\//m);
    assert.doesNotMatch(listing.stdout, /^slotera-booking\/(?:QA\.md|composer\.json|package\.json|pnpm-lock\.yaml)$/m);
    assert.doesNotMatch(listing.stdout, /^slotera-booking\/\.gitattributes$/m);
    assert.doesNotMatch(listing.stdout, /^slotera-booking\/\.gitignore$/m);
  } finally {
    fs.rmSync(temp, { recursive: true, force: true });
  }
});

test('canonical file reader collapses LF, CRLF and CR to identical bytes', () => {
  const script = [
    'import importlib.util,pathlib,sys,tempfile',
    "sys.path.insert(0, str(pathlib.Path('tools').resolve()))",
    "spec=importlib.util.spec_from_file_location('slotera_build_rc','tools/build-rc.py')",
    'module=importlib.util.module_from_spec(spec)',
    'spec.loader.exec_module(module)',
    'root=pathlib.Path(tempfile.mkdtemp())',
    "a=root/'a.php'; b=root/'b.php'; c=root/'c.php'",
    "a.write_bytes(b'one\\ntwo\\n')",
    "b.write_bytes(b'one\\r\\ntwo\\r\\n')",
    "c.write_bytes(b'one\\rtwo\\r')",
    'assert module.canonical_file_bytes(a) == module.canonical_file_bytes(b) == module.canonical_file_bytes(c)',
  ].join(';');
  const result = spawnSync('python3', ['-c', script], { cwd: root, encoding: 'utf8', env: { ...process.env, PYTHONDONTWRITEBYTECODE: '1' } });
  assert.equal(result.status, 0, result.stderr || result.stdout);
});

test('Node QA resolves configured PHP instead of hardcoding a PATH command', () => {
  const qaRoot = path.join(root, 'tests', 'qa');
  for (const name of fs.readdirSync(qaRoot).filter((item) => item.endsWith('.test.mjs'))) {
    const source = fs.readFileSync(path.join(qaRoot, name), 'utf8');
    assert.doesNotMatch(source, /(?:spawnSync|execFileSync)\(['"]php['"]/, name);
  }
  const resolver = fs.readFileSync(path.join(root, 'tools', 'php-runtime.mjs'), 'utf8');
  assert.match(resolver, /process\.env\.PHP_BINARY \|\| process\.env\.PHP/);
});

test('signed material hashes use the same canonical bytes as the ZIP', () => {
  const signer = fs.readFileSync(path.join(root, 'tools', 'release-attestation.mjs'), 'utf8');
  assert.match(signer, /const canonicalText = .*replace\(\/\\r\\n\?\/g, '\\n'\)/);
  assert.match(signer, /const zipEntries = readCanonicalZipEntries\(archivePath\)/);
  assert.match(signer, /if \(entry\.sha256 !== sourceHash\)/);
  assert.match(signer, /return \{ name, sha256: entry\.sha256 \}/);
});
