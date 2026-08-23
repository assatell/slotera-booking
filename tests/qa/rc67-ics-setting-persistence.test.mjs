import fs from 'node:fs';
import test from 'node:test';
import assert from 'node:assert/strict';

const source = fs.readFileSync(
  'includes/Infrastructure/Repositories/SettingsRepository.php',
  'utf8'
);

test('RC67 ICS calendar invite setting is persisted as a boolean setting', () => {
  assert.match(
    source,
    /in_array\(\$key,\s*\[[^\]]*'email_attach_ics_invites'[^\]]*\],\s*true\)/
  );

  assert.match(
    source,
    /\$clean\[\$key\]\s*=\s*!empty\(\$value\)\s*\?\s*1\s*:\s*0;/
  );
});
