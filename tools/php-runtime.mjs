export function resolvePhpExecutable() {
  const configured = String(process.env.PHP_BINARY || process.env.PHP || '').trim();
  return configured || 'php';
}
