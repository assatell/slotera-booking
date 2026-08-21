import fs from 'node:fs';
import assert from 'node:assert/strict';

const read = (path) => fs.readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');
const styles = read('includes/Frontend/Views/public-token/_styles.php');
for (const view of ['reschedule-form.php', 'cancel-confirmation.php', 'message.php']) {
  const source = read(`includes/Frontend/Views/public-token/${view}`);
  assert.ok(source.includes("include __DIR__ . '/_styles.php'"), `${view} must use shared Appearance-aware public action styles`);
}
assert.ok(styles.includes("appearance_theme"), 'public action styles must resolve the active Appearance theme');
assert.ok(styles.includes("'muted' => 'muted_text_color'"), 'Custom Appearance muted text must map to the canonical token');
assert.ok(styles.includes('--sltr-muted:'), 'public action styles must expose --sltr-muted');
assert.ok(!styles.includes('--sltr-muted-text'), 'legacy muted token must not appear in public action styles');
assert.ok(styles.includes('background:var(--sltr-form-bg)'), 'public action page background must inherit Appearance');
console.log('public action Appearance regression guard: ok');
