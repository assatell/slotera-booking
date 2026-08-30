import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (name) => fs.readFileSync(path.join(root, name), 'utf8');

test('RC67.4 contact email does not collect or expose raw network identifiers', () => {
  const shortcode = read('includes/Frontend/Shortcodes/BookingShortcode.php');
  const registry = read('includes/Application/Services/EmailTemplateRegistry.php');
  const translations = read('includes/Application/Services/EmailTemplateTranslationData.php');

  for (const source of [shortcode, registry, translations]) {
    assert.doesNotMatch(source, /contact_user_ip/);
    assert.doesNotMatch(source, /contact_user_agent/);
  }

  assert.doesNotMatch(shortcode, /HTTP_CF_CONNECTING_IP/);
  assert.doesNotMatch(shortcode, /HTTP_X_FORWARDED_FOR/);
  assert.doesNotMatch(shortcode, /HTTP_USER_AGENT/);
});
