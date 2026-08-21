import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync('assets/css/frontend.css', 'utf8');
const accountController = fs.readFileSync('includes/Frontend/Controllers/AccountController.php', 'utf8');
const frontendPhp = fs.readdirSync('includes/Frontend', { recursive: true, withFileTypes: true })
  .filter((entry) => entry.isFile() && entry.name.endsWith('.php'))
  .map((entry) => fs.readFileSync(`${entry.parentPath}/${entry.name}`, 'utf8'))
  .join('\n');

test('final CSS debt thresholds do not regress', () => {
  assert.ok(css.split(/\r?\n/).length <= 4530, 'frontend.css line count exceeded final audited ceiling');
  assert.ok((css.match(/!important/g) || []).length <= 409, 'frontend.css !important count exceeded final audited ceiling');
  assert.equal((css.match(/v\d+\.\d+(?:\.\d+)?/g) || []).length, 0, 'version-labelled CSS ownership must remain zero');
  assert.ok((css.match(/^\s*@media\b/gm) || []).length <= 27, 'responsive media-query layer count exceeded final audited ceiling');
  assert.doesNotMatch(css, /--sltr-muted-text\b/);
});

test('frontend inline presentation debt stays within audited boundary', () => {
  assert.ok((frontendPhp.match(/<style\b/gi) || []).length <= 1, 'only the public-token Appearance style generator may remain');
  assert.ok((frontendPhp.match(/\bstyle\s*=\s*"/gi) || []).length <= 43, 'frontend inline style attribute count regressed');
  assert.ok((frontendPhp.match(/!important/g) || []).length <= 3, 'frontend PHP inline !important count regressed');
});

test('magic-link confirmation uses shared Appearance tokens instead of hardcoded light UI', () => {
  const start = accountController.indexOf('private function render_magic_link_confirmation');
  const end = accountController.indexOf('public function request_magic_link', start);
  assert.ok(start >= 0 && end > start);
  const confirmation = accountController.slice(start, end);

  assert.match(confirmation, /Views\/public-token\/_styles\.php/);
  assert.match(confirmation, /class="sltr-confirm sltr-account-auth"/);
  assert.match(confirmation, /<button type="submit" class="button">/);
  assert.doesNotMatch(confirmation, /\bstyle\s*=/i);
  assert.doesNotMatch(confirmation, /#(?:e5e7eb|2563eb|fff(?:fff)?)/i);
});
