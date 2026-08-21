import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const read = (p) => fs.readFileSync(path.join(root, p), 'utf8');

test('RC66 observability stores request path only, never full query strings', () => {
  const source = read('includes/Application/Services/ObservabilityLogger.php');
  assert.match(source, /'uri'\s*=>\s*self::request_path\(\)/);
  assert.match(source, /wp_parse_url\(\$request_uri, PHP_URL_PATH\)/);
  assert.doesNotMatch(source, /'uri'\s*=>\s*DataRedactor::text\(\(string\) \(\$_SERVER\['REQUEST_URI'\]/);
});

test('RC66 booking CSV export uses an explicit allowlist and excludes sensitive internals', () => {
  const source = read('includes/Admin/Controllers/ToolsController.php');
  assert.match(source, /private const BOOKING_EXPORT_COLUMNS = \[/);
  assert.doesNotMatch(source, /DESCRIBE ['"]?\s*\.\s*Database::bookings_table/);
  assert.match(source, /SELECT \{\$select\} FROM \{\$table\}/);
  for (const sensitive of ['payment_redirect_url', 'external_payment_id', 'payment_policy_snapshot_json', 'cancellation_token', 'reschedule_token', 'active_slot_hash', 'coupon_usage_recorded']) {
    const allowlist = source.match(/private const BOOKING_EXPORT_COLUMNS = \[([\s\S]*?)\n    \];/);
    assert.ok(allowlist, 'export allowlist missing');
    assert.ok(!allowlist[1].includes(`'${sensitive}'`), `${sensitive} must not be exported`);
  }
});

test('RC66 Facebook OAuth keeps secret, code and access token out of request URLs', () => {
  const source = read('includes/Application/Services/SocialLoginService.php');
  const start = source.indexOf('private function facebook_user_from_code');
  const end = source.indexOf('private function apple_user_from_code', start);
  const method = source.slice(start, end > start ? end : undefined);
  assert.match(method, /wp_remote_post\(\(string\) self::PROVIDER_CONFIG\['facebook'\]\['token_url'\]/);
  assert.match(method, /'body'\s*=>\s*\[[\s\S]*'client_secret'[\s\S]*'code'/);
  assert.doesNotMatch(method, /add_query_arg\([\s\S]*'client_secret'/);
  assert.doesNotMatch(method, /add_query_arg\([\s\S]*'access_token'/);
  assert.match(method, /'Authorization'\s*=>\s*'Bearer '\s*\.\s*\$access_token/);
});
