export function normalizeUpdateUri(raw) {
  const value = String(raw || '').trim();
  if (value === '') return '';

  let parsed;
  try {
    parsed = new URL(value);
  } catch {
    throw new Error('SLTR_UPDATE_URI must be a valid absolute HTTPS URL');
  }
  if (parsed.protocol !== 'https:' || !parsed.hostname) {
    throw new Error('SLTR_UPDATE_URI must use HTTPS and include a hostname');
  }
  if (parsed.username || parsed.password) {
    throw new Error('SLTR_UPDATE_URI must not contain URL credentials');
  }
  if (parsed.hash) {
    throw new Error('SLTR_UPDATE_URI must not contain a fragment');
  }
  return parsed.href.replace(/'/g, '%27');
}

export function applyUpdateUri(source, raw) {
  const uri = normalizeUpdateUri(raw);
  let next = String(source).replace(/^ \* Update URI:\s*.*\r?\n/m, '');
  next = next.replace(
    /define\('SLTR_UPDATE_URI',\s*'[^']*'\);/,
    `define('SLTR_UPDATE_URI', '${uri}');`,
  );
  if (!/define\('SLTR_UPDATE_URI'/.test(next)) {
    throw new Error('SLTR_UPDATE_URI constant target not found');
  }
  if (uri !== '') {
    const versionLine = /^ \* Version:\s*[^\r\n]+\r?$/m;
    if (!versionLine.test(next)) throw new Error('Plugin Version header not found for Update URI injection');
    next = next.replace(versionLine, (line) => `${line}\n * Update URI: ${uri}`);
  }
  return { source: next, uri };
}
