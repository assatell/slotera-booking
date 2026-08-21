import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (name) => fs.readFileSync(path.join(root, name), 'utf8');

test('RC66.6 public REST booking authenticates each request object only once', () => {
  const source = read('includes/Frontend/Controllers/RestApiController.php');
  assert.match(source, /private array \$validated_rest_booking_requests = \[\];/);
  assert.match(source, /\$this->validated_rest_booking_requests\[spl_object_id\(\$request\)\] = true;/);
  assert.match(source, /if \(empty\(\$this->validated_rest_booking_requests\[\$request_id\]\)\) \{[\s\S]*?validate_rest_booking_auth\(\$request, \$settings\)[\s\S]*?\$this->validated_rest_booking_requests\[\$request_id\] = true;/);
  assert.match(source, /if \(!\$this->reserve_rest_hmac_nonce\([\s\S]*?sltr_rest_hmac_replay/);
});

test('RC66.6 basic analytics honors the require-consent setting at runtime', () => {
  const service = path.join(root, 'includes/Application/Services/VisitorAnalyticsService.php').replaceAll('\\', '\\\\');
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'sltr-analytics-consent-'));
  const script = path.join(temp, 'runtime.php');
  fs.writeFileSync(script, `<?php
namespace Slotera\\Infrastructure\\Repositories {
    final class SettingsRepository {
        public static array $settings = [];
        public function all(): array { return self::$settings; }
    }
}
namespace {
    define('ABSPATH', __DIR__ . '/');
    $GLOBALS['sltr_consent'] = false;
    function apply_filters($name, $default, ...$args) { return $name === 'slotera_visitor_analytics_consent_granted' ? (bool) $GLOBALS['sltr_consent'] : $default; }
    require '${service}';
    $svc = new \\Slotera\\Application\\Services\\VisitorAnalyticsService();
    \\Slotera\\Infrastructure\\Repositories\\SettingsRepository::$settings = [
      'privacy_visitor_analytics_enabled' => 1,
      'privacy_visitor_analytics_require_consent' => 1,
      'privacy_visitor_session_analytics_enabled' => 0,
    ];
    echo $svc->is_collection_allowed() ? '1' : '0';
    $GLOBALS['sltr_consent'] = true;
    echo $svc->is_collection_allowed() ? '1' : '0';
    \\Slotera\\Infrastructure\\Repositories\\SettingsRepository::$settings['privacy_visitor_analytics_require_consent'] = 0;
    $GLOBALS['sltr_consent'] = false;
    echo $svc->is_collection_allowed() ? '1' : '0';
}
`);
  try {
    const run = spawnSync('php', [script], { encoding: 'utf8' });
    assert.equal(run.status, 0, run.stderr || run.stdout);
    assert.equal(run.stderr, '');
    assert.equal(run.stdout, '011');
  } finally {
    fs.rmSync(temp, { recursive: true, force: true });
  }
});

test('RC66.6 booking access cookies remain Secure when canonical site URL is HTTPS behind a proxy', () => {
  const source = read('includes/Application/Services/BookingAccessTokenService.php');
  assert.match(source, /'secure' => \$this->should_secure_cookie\(\)/);
  assert.match(source, /private function should_secure_cookie\(\): bool/);
  assert.match(source, /if \(is_ssl\(\)\) \{[\s\S]*?return true;/);
  assert.match(source, /wp_parse_url\(home_url\('\/'\), PHP_URL_SCHEME\)[\s\S]*?=== 'https'/);
  assert.match(source, /'httponly' => true/);
  assert.match(source, /'samesite' => 'Lax'/);
});

test('RC66.6 every admin PHP view fails closed on direct web execution', () => {
  const viewsRoot = path.join(root, 'includes/Admin/Views');
  const stack = [viewsRoot];
  const missing = [];
  while (stack.length) {
    const dir = stack.pop();
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
      const full = path.join(dir, entry.name);
      if (entry.isDirectory()) stack.push(full);
      else if (entry.isFile() && entry.name.endsWith('.php')) {
        const source = fs.readFileSync(full, 'utf8');
        if (!/defined\(['"]ABSPATH['"]\)/.test(source)) missing.push(path.relative(root, full));
      }
    }
  }
  assert.deepEqual(missing, []);
});

test('RC66.6 licensing is explicitly a non-enforcing development placeholder', () => {
  const service = read('includes/Application/Services/LicenseService.php');
  const view = read('includes/Admin/Views/license.php');
  assert.match(service, /'state' => 'development_placeholder'/);
  assert.match(service, /Licensing: development placeholder \/ enforcement disabled/);
  assert.match(service, /'license_status' => 'development_placeholder'/);
  assert.match(service, /development_placeholder_key_saved/);
  assert.match(service, /development_placeholder_key_cleared/);
  assert.match(view, /Licensing: development placeholder \/ enforcement disabled/);
  assert.match(view, /does not validate or enforce licenses/);
  assert.doesNotMatch(view, /entering any non-empty key activates the license for one year/);
});
