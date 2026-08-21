#!/usr/bin/env node
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { resolvePython } from './python-runtime.mjs';

const here = path.dirname(fileURLToPath(import.meta.url));
const python = resolvePython();
const script = path.join(here, 'build-rc.py');
const run = spawnSync(python.command, [...python.prefix, script, ...process.argv.slice(2)], {
  stdio: 'inherit',
  env: process.env,
  windowsHide: true,
});
if (run.error) {
  console.error(`Failed to launch ${python.label}: ${run.error.code || run.error.message}`);
  process.exit(1);
}
process.exit(run.status ?? 1);
