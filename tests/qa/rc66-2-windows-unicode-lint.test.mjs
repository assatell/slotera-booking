import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const qa = fs.readFileSync(path.join(root, 'tools/qa.php'), 'utf8');

test('RC66.2 lint parses PHP in-process and does not spawn PHP_BINARY', () => {
  assert.match(qa, /token_get_all\(\$source, TOKEN_PARSE\)/);
  assert.match(qa, /catch \(ParseError \$error\)/);
  assert.doesNotMatch(qa, /proc_open\(/);
  assert.doesNotMatch(qa, /PHP_BINARY/);
});

test('RC66.2 lint remains path-transparent for Unicode source paths', () => {
  assert.match(qa, /file_get_contents\(\$file\)/);
  assert.doesNotMatch(qa, /escapeshellarg\(/);
  assert.doesNotMatch(qa, /cmd\.exe|powershell|COMSPEC/i);
});
