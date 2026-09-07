#!/usr/bin/env node
import childProcess from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { resolvePhpExecutable } from './php-runtime.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const mode = process.argv[2] || 'pretag';
if (!['pretag', 'tag'].includes(mode)) throw new Error('Usage: node tools/release-gate.mjs [pretag|tag]');
const manifest = JSON.parse(fs.readFileSync(path.join(root, 'release-manifest.json'), 'utf8'));
const expectedTag = String(manifest.source?.tag || '');
if (!expectedTag) throw new Error('release-manifest.json source.tag is required');

const run = (command, args, options = {}) => {
  const result = childProcess.spawnSync(command, args, {
    cwd: root,
    encoding: 'utf8',
    stdio: 'inherit',
    windowsHide: true,
    ...options,
  });
  if (result.error) throw result.error;
  if (result.status !== 0) throw new Error(`${command} ${args.join(' ')} failed with exit ${result.status}`);
};
const gitOutput = (args) => childProcess.execFileSync('git', args, { cwd: root, encoding: 'utf8' }).trim();
const assertClean = (stage) => {
  const status = gitOutput(['status', '--porcelain']);
  if (status !== '') throw new Error(`${stage}: release gate requires a clean Git tree\n${status}`);
  run('git', ['diff', '--exit-code']);
  run('git', ['diff', '--cached', '--exit-code']);
};

assertClean('before QA');
if (mode === 'tag') {
  const actualTag = gitOutput(['describe', '--tags', '--exact-match', 'HEAD']);
  if (actualTag !== expectedTag) throw new Error(`release gate requires exact tag ${expectedTag}; got ${actualTag || '<none>'}`);
}

const metadataEnv = { ...process.env };
if (mode === 'tag') {
  metadataEnv.SLTR_VCS_REQUIRED = '1';
  metadataEnv.SLTR_VCS_TAG = expectedTag;
}
run(process.execPath, ['tools/release-metadata.mjs', 'verify'], { env: metadataEnv });
run(resolvePhpExecutable(), ['tools/qa.php', 'qa']);
const tests = fs.readdirSync(path.join(root, 'tests', 'qa'))
  .filter((name) => name.endsWith('.test.mjs'))
  .sort()
  .map((name) => path.join('tests', 'qa', name));
run(process.execPath, ['--test', ...tests]);
assertClean('after QA');
console.log(`Release gate passed (${mode}): ${manifest.version} ${manifest.candidate}${mode === 'tag' ? ` ${expectedTag}` : ''}`);
