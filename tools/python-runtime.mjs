import { spawnSync } from 'node:child_process';

function candidateList(env = process.env) {
  const candidates = [];
  if (env.PYTHON) candidates.push({ command: env.PYTHON, prefix: [], label: 'PYTHON' });
  if (process.platform === 'win32') candidates.push({ command: 'py', prefix: ['-3'], label: 'py -3' });
  candidates.push({ command: 'python3', prefix: [], label: 'python3' });
  candidates.push({ command: 'python', prefix: [], label: 'python' });
  return candidates;
}

export function resolvePython({ env = process.env } = {}) {
  const diagnostics = [];
  for (const candidate of candidateList(env)) {
    const probe = spawnSync(candidate.command, [...candidate.prefix, '--version'], {
      encoding: 'utf8',
      env,
      windowsHide: true,
    });
    if (probe.error) {
      diagnostics.push(`${candidate.label}: ${probe.error.code || probe.error.message}`);
      continue;
    }
    if (probe.status !== 0) {
      diagnostics.push(`${candidate.label}: exit ${probe.status}: ${(probe.stderr || probe.stdout || '').trim()}`);
      continue;
    }
    const versionText = `${probe.stdout || ''} ${probe.stderr || ''}`.trim();
    const match = versionText.match(/Python\s+(\d+)\.(\d+)(?:\.(\d+))?/i);
    if (!match) {
      diagnostics.push(`${candidate.label}: unrecognized version output: ${versionText}`);
      continue;
    }
    const major = Number(match[1]);
    const minor = Number(match[2]);
    if (major < 3 || (major === 3 && minor < 9)) {
      diagnostics.push(`${candidate.label}: Python ${major}.${minor} is older than required 3.9`);
      continue;
    }
    return { ...candidate, version: versionText };
  }
  throw new Error(`CPython 3.9+ not found. Tried ${candidateList(env).map((v) => v.label).join(', ')}. ${diagnostics.join(' | ')}`);
}
