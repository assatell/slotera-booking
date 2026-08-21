import fs from 'node:fs';
import assert from 'node:assert/strict';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../..');
const form = fs.readFileSync(path.join(root, 'includes/Admin/Views/marketing-form.php'), 'utf8');
const qa = fs.readFileSync(path.join(root, 'tools/qa.php'), 'utf8');

for (const dead of ['queue-settings.php', 'preview-test.php', 'queue-controls-log.php']) {
  assert.equal(fs.existsSync(path.join(root, 'includes/Admin/Views/marketing-form', dead)), false);
  assert.equal(form.includes(dead), false);
  assert.equal(qa.includes(dead), true);
}
console.log('dead coupon campaign views stay removed: ok');
