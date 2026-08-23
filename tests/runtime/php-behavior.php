<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('ABSPATH', __DIR__ . '/../../');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('COOKIEPATH', '/');

$GLOBALS['sltr_test_transients'] = [];
$GLOBALS['sltr_test_options'] = [];

final class WP_Error {
    public function __construct(public string $code = '', public string $message = '') {}
}
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function __(string $value): string { return $value; }
function sanitize_key(string $value): string { return preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)) ?: ''; }
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function sanitize_email(string $value): string { return strtolower(trim($value)); }
function is_email(string $value): bool { return filter_var($value, FILTER_VALIDATE_EMAIL) !== false; }
function wp_unslash($value) { return $value; }
function absint($value): int { return abs((int) $value); }
function wp_salt(string $scheme = 'auth'): string { return 'runtime-test-salt|' . $scheme; }
function wp_generate_password(int $length): string { return str_repeat('s', $length); }
function is_ssl(): bool { return false; }
function home_url(string $path = ''): string { return 'https://example.test' . ($path !== '' ? '/' . ltrim($path, '/') : ''); }
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
function wp_timezone(): DateTimeZone { return new DateTimeZone('UTC'); }
function set_transient(string $key, $value, int $ttl): bool { $GLOBALS['sltr_test_transients'][$key] = $value; return true; }
function get_transient(string $key) { return $GLOBALS['sltr_test_transients'][$key] ?? false; }
function delete_transient(string $key): bool { unset($GLOBALS['sltr_test_transients'][$key]); return true; }
function add_option(string $key, $value, string $deprecated = '', $autoload = 'yes'): bool {
    if (array_key_exists($key, $GLOBALS['sltr_test_options'])) return false;
    $GLOBALS['sltr_test_options'][$key] = $value;
    return true;
}
function delete_option(string $key): bool { $exists = array_key_exists($key, $GLOBALS['sltr_test_options']); unset($GLOBALS['sltr_test_options'][$key]); return $exists; }
function get_option(string $key, $default = false) { return $GLOBALS['sltr_test_options'][$key] ?? $default; }
function update_option(string $key, $value, $autoload = null): bool { $GLOBALS['sltr_test_options'][$key] = $value; return true; }
function current_time(string $format) { return $format === 'mysql' ? '2026-08-13 18:12:09' : time(); }

function assert_runtime(bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
}
function private_method(object $object, string $method): ReflectionMethod {
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);
    return $reflection;
}

require_once ABSPATH . 'includes/Application/Services/SocialLoginService.php';
require_once ABSPATH . 'includes/Domain/Availability/AvailabilityService.php';
require_once ABSPATH . 'includes/Application/Services/DateRangeInventoryService.php';
require_once ABSPATH . 'includes/Application/Services/BookingAccessTokenService.php';
require_once ABSPATH . 'includes/Application/Services/MarketingConsentService.php';
require_once ABSPATH . 'includes/Application/Security/SecretStore.php';

if (\Slotera\Application\Security\SecretStore::encryption_available()) {
    $secretPlain = 'runtime-secret';
    $secretEncrypted = \Slotera\Application\Security\SecretStore::encrypt_string($secretPlain);

    assert_runtime(
        str_starts_with($secretEncrypted, 'sltr_secret:v3:'),
        'SecretStore must use v3 for new ciphertext.'
    );

    assert_runtime(
        \Slotera\Application\Security\SecretStore::decrypt_string($secretEncrypted) === $secretPlain,
        'SecretStore v3 ciphertext must round-trip.'
    );

    $legacyKey = hash('sha256', 'slotera-secret-store-fallback', true);
    $legacyNonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $legacyCiphertext = sodium_crypto_secretbox('legacy-runtime-secret', $legacyNonce, $legacyKey);
    $legacyStored = 'sltr_secret:v2:' . base64_encode($legacyNonce . $legacyCiphertext);

    assert_runtime(
        \Slotera\Application\Security\SecretStore::decrypt_string($legacyStored) === 'legacy-runtime-secret',
        'SecretStore must retain v2 fixed-fallback decryption compatibility.'
    );
}

$socialClass = new ReflectionClass(\Slotera\Application\Services\SocialLoginService::class);
$social = $socialClass->newInstanceWithoutConstructor();
$createState = private_method($social, 'create_state');
$consumeState = private_method($social, 'consume_state');
$state = $createState->invoke($social, 'google');
$_COOKIE['sltr_oauth_state_google'] = 'attacker-browser-cookie';
assert_runtime($consumeState->invoke($social, 'google', $state) === false, 'OAuth state must be bound to the initiating browser.');
$state = $createState->invoke($social, 'google');
assert_runtime($consumeState->invoke($social, 'google', $state) === true, 'Valid OAuth state and browser cookie must be accepted once.');
assert_runtime($consumeState->invoke($social, 'google', $state) === false, 'OAuth state must be one-time.');

$availabilityClass = new ReflectionClass(\Slotera\Domain\Availability\AvailabilityService::class);
$availability = $availabilityClass->newInstanceWithoutConstructor();
$bufferCheck = private_method($availability, 'meets_same_day_before_buffer');
$fixedNow = new DateTimeImmutable('2026-08-13 23:00:00', new DateTimeZone('UTC'));
assert_runtime($bufferCheck->invoke($availability, '2026-08-14', '00:30:00', 120, $fixedNow) === false, 'A preparation buffer crossing midnight must block the slot.');
assert_runtime($bufferCheck->invoke($availability, '2026-08-14', '02:30:00', 120, $fixedNow) === true, 'A future preparation window must remain bookable.');

$rangeClass = new ReflectionClass(\Slotera\Application\Services\DateRangeInventoryService::class);
$range = $rangeClass->newInstanceWithoutConstructor();
$package = [
    'price' => 100,
    'price_unit' => 'per_night',
    'min_nights' => 1,
    'max_nights' => 30,
    'inventory_units_json' => json_encode([['id' => 7, 'name' => 'Room', 'capacity' => 1, 'price' => 100, 'active' => 1]]),
];
$quote = $range->quote($package, 7, '2099-08-14', '2099-08-16', []);
assert_runtime(is_array($quote) && $quote['total_amount'] === 200.0, 'Date-range quote must calculate deterministic totals.');
assert_runtime($quote['allow_coupons'] === 1 && $quote['discount_type'] === 'none' && $quote['discount_value'] === 0.0, 'Ordinary date-range quote must not read scheduled-event variables.');

$access = new \Slotera\Application\Services\BookingAccessTokenService();
$secureCookie = private_method($access, 'should_secure_cookie');
assert_runtime($secureCookie->invoke($access) === true, 'Booking access cookie must remain Secure when canonical home URL is HTTPS behind a TLS proxy.');
$booking = ['id' => 42, 'cancellation_token' => 'cancel', 'reschedule_token' => 'move', 'created_at' => '2026-08-13 20:00:00'];
$code = $access->issue_code($booking);
assert_runtime($code !== '' && $access->exchange_code($booking, $code) === true, 'Booking access code must exchange successfully once.');
assert_runtime($access->exchange_code($booking, $code) === false, 'Booking access code must reject a second exchange.');

$consent = new \Slotera\Application\Services\MarketingConsentService();
assert_runtime($consent->has_consent('customer@example.com') === false, 'Marketing must default to no consent.');
assert_runtime($consent->grant('customer@example.com', 'booking_form') === true, 'Explicit marketing consent must be recordable.');
$consentRecord = $consent->record('customer@example.com');
assert_runtime($consent->has_consent('customer@example.com') === true && ($consentRecord['source'] ?? '') === 'booking_form' && !empty($consentRecord['granted_at']), 'Marketing consent must retain timestamp and source evidence.');
assert_runtime($consent->revoke('customer@example.com') === true && $consent->has_consent('customer@example.com') === false, 'Marketing consent must be revocable.');

fwrite(STDOUT, "OK: runtime behavior tests passed\n");
