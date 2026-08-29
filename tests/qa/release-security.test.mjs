import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (p) => fs.readFileSync(new URL(`../../${p}`, import.meta.url), 'utf8');

test('rate limiter fails closed on database errors', () => {
  const source = read('includes/Infrastructure/Http/RateLimiter.php');
  assert.match(source, /return self::fail_closed\('increment'/);
  assert.match(source, /return self::fail_closed\('read'/);
  assert.match(source, /return PHP_INT_MAX;/);
});

test('public visitor analytics is rate limited and restricted to same-site URLs', () => {
  const source = read('includes/Frontend/Controllers/VisitorAnalyticsController.php');
  assert.match(source, /RateLimiter::increment\('visitor_analytics_ip'/);
  assert.match(source, /RateLimiter::increment\('visitor_analytics_session'/);
  assert.match(source, /self::RATE_LIMIT_PER_IP/);
  assert.match(source, /self::RATE_LIMIT_PER_SESSION/);
  assert.match(source, /wp_send_json_error\([^;]+, 429\)/s);
  assert.match(source, /private function is_same_site_url/);
  assert.match(source, /hash_equals\(\$home_host, \$host\)/);
});

test('plaintext secret migration never writes when encryption is unavailable', () => {
  const source = read('includes/Infrastructure/Repositories/SettingsRepository.php');
  const guard = source.indexOf('SecretStore::encryption_available()');
  const decrypted = source.indexOf('$decrypted = SecretStore::decrypt_settings($settings)', guard);
  const encrypted = source.indexOf('$encrypted = SecretStore::encrypt_settings($decrypted)', decrypted);
  const write = source.indexOf('update_option(self::OPTION_NAME, $encrypted, false)', encrypted);
  assert.ok(guard >= 0 && decrypted > guard && encrypted > decrypted && write > encrypted);
  assert.match(source, /!SecretStore::is_encrypted\(\$encrypted\[\$key\]\)/);
  assert.match(source, /finally\s*\{\s*\$migrating = false;/s);
});

test('license secrets rotate legacy ciphertext to current encryption', () => {
  const source = read('includes/Application/Services/LicenseService.php');
  assert.match(source, /SecretStore::is_current_encrypted\(\$stored_key\)/);
  assert.match(source, /\$plain_key = SecretStore::decrypt_string\(\$stored_key\)/);
  assert.match(source, /\$rotated_key = SecretStore::encrypt_string\(\$plain_key\)/);
  assert.match(source, /SecretStore::is_current_encrypted\(\$rotated_key\)/);
  assert.match(source, /update_option\(self::OPTION_NAME, \$stored, false\)/);
  assert.match(source, /\$data\['license_key'\] = SecretStore::decrypt_string\(\$stored_key\)/);
});
test('visitor analytics emits one final event per page view', () => {
  const client = read('assets/js/frontend-analytics.js');
  assert.match(client, /if \(sent\) \{ return; \}/);
  assert.match(client, /sent = true;/);
  assert.match(client, /final_event: 1/);
  assert.doesNotMatch(client, /send\(false\)|setTimeout\(function \(\) \{ send/);
  const service = read('includes/Application/Services/VisitorAnalyticsService.php');
  assert.match(service, /'exited' => \$final_event/);
});

test('visitor analytics separates anonymous basic collection from consent-gated session analytics', () => {
  const service = read('includes/Application/Services/VisitorAnalyticsService.php');
  const client = read('assets/js/frontend-analytics.js');
  const settings = read('includes/Infrastructure/Repositories/SettingsRepository.php');
  assert.match(service, /privacy_visitor_analytics_enabled/);
  assert.match(service, /privacy_visitor_session_analytics_enabled/);
  assert.match(service, /slotera_visitor_analytics_consent_granted/);
  assert.match(service, /'ip_prefix_hash' => ''/);
  assert.match(service, /private function strip_url_query/);
  assert.match(service, /private function sanitize_referrer/);
  assert.match(client, /if \(!sessionAnalytics\) \{ return ''; \}/);
  assert.match(client, /window\.sessionStorage\.getItem/);
  assert.match(client, /cleanPageUrl\(\)/);
  assert.match(settings, /'privacy_visitor_session_analytics_enabled' => 0/);
  const plugin = read('includes/Core/Plugin.php');
  assert.match(plugin, /\$visitor_analytics->is_collection_allowed\(\)/);
  assert.match(plugin, /visitor_analytics_session_enabled/);
  const privacy = read('includes/Application/Services/PrivacyService.php');
  assert.match(privacy, /Database::visitor_events_table\(\)/);
  assert.match(privacy, /privacy_visitor_analytics_retention_days/);
  assert.match(privacy, /RETENTION_MAX_BATCHES/);
});

test('anonymous visitor analytics does not persist query strings or count blank sessions', () => {
  const service = read('includes/Application/Services/VisitorAnalyticsService.php');
  const report = read('includes/Application/Services/AnalyticsService.php');
  assert.doesNotMatch(service, /anonymized_ip_prefix/);
  assert.doesNotMatch(service, /ClientIpResolver/);
  assert.match(report, /COUNT\(DISTINCT NULLIF\(session_hash,''\)\) AS sessions/);
});

test('social login rejects unverified Google and Apple email claims', () => {
  const service = read('includes/Application/Services/SocialLoginService.php');
  assert.match(service, /private function is_verified_email_claim/);
  assert.match(service, /\['email_verified'\] \?\? null/);
  assert.match(service, /sltr_social_login_email_unverified/);
  assert.match(service, /return in_array\(strtolower\(trim\(\$value\)\), \['true', '1'\], true\)/);
  const controller = read('includes/Frontend/Controllers/AccountController.php');
  assert.match(controller, /\(\$profile\['email_verified'\] \?\? false\) !== true/);
});

test('booking notifications run only after successful payment initialization', () => {
  const base = read('includes/Application/Services/BookingModes/AbstractBookingModeHandler.php');
  assert.match(base, /protected function notify_after_successful_creation/);
  assert.match(base, /notifications->booking_created/);
  for (const file of ['FixedBookingModeHandler.php', 'SimpleBookingModeHandler.php', 'DateRangeInventoryBookingModeHandler.php']) {
    const source = read(`includes/Application/Services/BookingModes/${file}`);
    const payment = source.lastIndexOf('payment_service->create_for_booking');
    const notify = source.lastIndexOf('$this->notify_after_successful_creation');
    assert.ok(payment >= 0 && notify > payment, `${file}: notification must follow payment initialization`);
    assert.doesNotMatch(source, /notifications->booking_created/);
    assert.match(source, /'defer_notifications' => true/);
  }
  const payment = read('includes/Application/Services/PaymentService.php');
  assert.match(payment, /empty\(\$context\['defer_notifications'\]\)/);
});

test('external HTTPS delivery fails closed without cURL DNS pinning', () => {
  const guard = read('includes/Application/Security/UrlGuard.php');
  assert.match(guard, /private static function curl_pinning_available/);
  assert.match(guard, /!function_exists\('curl_init'\)/);
  assert.match(guard, /!defined\('CURLOPT_RESOLVE'\)/);
  assert.match(guard, /sltr_secure_http_transport_unavailable/);
  assert.match(guard, /curl_setopt\(\$handle, CURLOPT_RESOLVE/);
  assert.match(guard, /throw new \\RuntimeException/);
  assert.doesNotMatch(guard, /@curl_setopt/);
});

test('release metadata has one version source and reproducible provenance', () => {
  const manifest = JSON.parse(read('release-manifest.json'));
  const plugin = read('slotera-booking.php');
  const build = read('includes/build.php');
  const pkg = JSON.parse(read('package.json'));
  const provenance = JSON.parse(read('build-provenance.json'));
  assert.match(plugin, new RegExp(`Version: ${manifest.version.replaceAll('.', '\\.')}`));
  assert.match(build, new RegExp(`SLTR_BUILD_VERSION', '${manifest.version.replaceAll('.', '\\.')}`));
  assert.equal(pkg.version, manifest.version);
  assert.equal(provenance.version, manifest.version);
  assert.equal(provenance.schema, 'slotera-build-provenance/v3');
  assert.ok(Array.isArray(provenance.transformation_chain));
  assert.ok(provenance.transformation_chain.length >= 6);
  assert.ok(provenance.build.command);
  assert.ok(Object.hasOwn(provenance.vcs, 'commit'));
  assert.ok(Object.hasOwn(provenance.vcs, 'tag'));
  assert.ok(provenance.hashes.release_tree_sha256);
  assert.equal(manifest.schema, 'slotera-release-manifest/v2');

  if (provenance.vcs?.state === 'git-clean') {
    assert.equal(provenance.candidate, manifest.candidate);
    assert.equal(provenance.builder.version, manifest.builder.version);
    assert.equal(provenance.source?.type, 'git');
    assert.equal(provenance.source?.commit, provenance.vcs.commit);
    assert.equal(provenance.source?.tag, provenance.vcs.tag);
    assert.equal(provenance.vcs?.dirty, false);
  } else {
    assert.equal(provenance.source?.sha256, manifest.lineage?.previous_source?.sha256);
    assert.equal(provenance.source?.tree_sha256, manifest.lineage?.previous_source?.tree_sha256);
  }
});

test('release provenance is covered by an external signing workflow', () => {
  const manifest = JSON.parse(read('release-manifest.json'));
  const checksums = read('checksums.sha256');
  const builder = read('tools/build-release.ps1');
  const signer = read('tools/release-attestation.mjs');
  assert.match(checksums, /  build-provenance\.json$/m);
  assert.equal(manifest.signing.algorithm, 'RSA-PSS-SHA256');
  assert.match(manifest.signing.key_id, /^sha256:[a-f0-9]{64}$/);
  assert.match(builder, /release-attestation\.mjs'\) sign/);
  assert.match(signer, /RSA_PKCS1_PSS_PADDING/);
  assert.match(signer, /Archive hash does not match attestation/);
  assert.match(signer, /Attested material does not match signed ZIP/);
  assert.match(signer, /Extracted tree differs from signed ZIP/);
});

test('X-Forwarded-For is resolved from the trusted edge backwards', () => {
  const source = read('includes/Infrastructure/Http/ClientIpResolver.php');
  assert.match(source, /for \(\$index = count\(\$chain\) - 1; \$index >= 0; --\$index\)/);
  assert.match(source, /!self::is_trusted_proxy\(\$chain\[\$index\]\)/);
});

test('internal request id does not consume client supplied header', () => {
  const source = read('includes/Application/Services/ObservabilityLogger.php');
  assert.doesNotMatch(source, /HTTP_X_REQUEST_ID|\$_SERVER\[['"]HTTP_X_REQUEST_ID/);
  assert.match(source, /self::generate_request_id\(\)/);
});


test('marketing campaigns share one queue control and history model', () => {
  const view = read('includes/Admin/Views/campaign-history-table.php');
  for (const action of ['sltr_process_marketing_queue_now', 'sltr_pause_marketing_campaign', 'sltr_resume_marketing_campaign', 'sltr_stop_marketing_campaign', 'sltr_retry_failed_marketing_campaign']) {
    assert.match(view, new RegExp(action));
  }
  const controller = read('includes/Admin/Controllers/MarketingController.php');
  assert.match(controller, /process_campaign_queue\(\$id\)/);
  assert.match(controller, /campaign_return_url/);
  assert.match(controller, /campaign_history_url/);
  const logs = read('includes/Infrastructure/Repositories/MarketingLogRepository.php');
  assert.match(logs, /delete_for_campaign/);
  assert.match(logs, /campaign_source/);
});

test('packaged PHP QA tools are CLI-only', () => {
  for (const file of ['qa.php', 'qa-customers-filter-ui.php', 'qa-tools-layout.php']) {
    const source = read(`tools/${file}`);
    assert.match(source, /PHP_SAPI !== 'cli'/, `${file} must reject direct HTTP execution`);
  }
});

test('payment administration uses the dedicated payment capability', () => {
  const controller = read('includes/Admin/Controllers/PaymentsController.php');
  const provider = read('includes/Admin/AdminServiceProvider.php');
  assert.match(controller, /Capabilities::MANAGE_PAYMENTS/);
  assert.doesNotMatch(controller, /Capabilities::MANAGE_SETTINGS/);
  assert.match(provider, /'slotera-payments'.*Capabilities::MANAGE_PAYMENTS/s);
});

test('magic-link request redirect does not expose customer email', () => {
  const controller = read('includes/Frontend/Controllers/AccountController.php');
  const start = controller.indexOf('public function request_magic_link');
  const end = controller.indexOf('public function', start + 1);
  const method = controller.slice(start, end);
  assert.doesNotMatch(method, /sltr_email/);
});

test('release build delegates deterministic archive creation to the canonical cross-platform builder', () => {
  const signedBuilder = read('tools/build-release.ps1');
  const archiveBuilder = read('tools/build-rc.py');
  const metadata = read('tools/release-metadata.mjs');
  assert.match(signedBuilder, /SOURCE_DATE_EPOCH/);
  assert.match(signedBuilder, /build-rc\.mjs/);
  assert.doesNotMatch(signedBuilder, /CreateEntry\(/);
  assert.match(archiveBuilder, /files\.sort\(key=lambda item: item\[0\]\)/);
  assert.match(archiveBuilder, /nested ZIP is not allowed in release source tree/);
  assert.match(archiveBuilder, /path\.suffix\.lower\(\) == '\.zip'/);
  assert.match(archiveBuilder, /write_zip\(output, entries, dt\)/);
  assert.match(read('tools/deterministic_zip.py'), /method=8/);
  assert.match(metadata, /SOURCE_DATE_EPOCH/);
  assert.match(metadata, /utf8PathCompare/);
});
