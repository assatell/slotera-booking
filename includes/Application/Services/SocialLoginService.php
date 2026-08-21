<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\SettingsRepository;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

final class SocialLoginService
{
    /**
     * OAuth providers supported by Slotera. Endpoints are fixed in code on purpose:
     * admin settings may only supply credentials, never authorization/token hosts.
     */
    private const PROVIDER_CONFIG = [
        'google' => [
            'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'auth_host' => 'accounts.google.com',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'profile_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scopes' => 'openid email profile',
        ],
        'facebook' => [
            'auth_url' => 'https://www.facebook.com/v19.0/dialog/oauth',
            'auth_host' => 'www.facebook.com',
            'token_url' => 'https://graph.facebook.com/v19.0/oauth/access_token',
            'profile_url' => 'https://graph.facebook.com/me',
            'scopes' => 'email,public_profile',
        ],
        'apple' => [
            'auth_url' => 'https://appleid.apple.com/auth/authorize',
            'auth_host' => 'appleid.apple.com',
            'token_url' => 'https://appleid.apple.com/auth/token',
            'scopes' => 'name email',
        ],
    ];

    private SettingsRepository $settings;

    public function __construct(?SettingsRepository $settings = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
    }

    public function is_provider_enabled(string $provider): bool
    {
        $provider = sanitize_key($provider);
        if (!array_key_exists($provider, self::PROVIDER_CONFIG)) {
            return false;
        }

        if ((int) $this->settings->get('social_login_' . $provider . '_enabled', 0) !== 1 || $this->client_id($provider) === '') {
            return false;
        }

        if ($provider === 'apple') {
            return trim((string) $this->settings->get('social_login_apple_team_id', '')) !== ''
                && trim((string) $this->settings->get('social_login_apple_key_id', '')) !== ''
                && trim((string) $this->settings->get('social_login_apple_private_key', '')) !== '';
        }

        return $this->client_secret($provider) !== '';
    }

    /** @return array<int,string> */
    public function enabled_providers(): array
    {
        $providers = [];
        foreach (array_keys(self::PROVIDER_CONFIG) as $provider) {
            if ($this->is_provider_enabled($provider)) {
                $providers[] = $provider;
            }
        }
        return $providers;
    }

    public function callback_url(string $provider): string
    {
        return $this->frontend_oauth_url($provider, 'callback');
    }

    public function start_url(string $provider): string
    {
        return $this->frontend_oauth_url($provider, 'start');
    }

    private function frontend_oauth_url(string $provider, string $step): string
    {
        $provider = sanitize_key($provider);
        $step = sanitize_key($step);
        if (!array_key_exists($provider, self::PROVIDER_CONFIG) || !in_array($step, ['start', 'callback'], true)) {
            return home_url('/');
        }

        return home_url(user_trailingslashit('slotera-social-login/' . $provider . '/' . $step));
    }

    private function frontend_login_url(): string
    {
        $accounts = new AccountMagicLinkService(null, $this->settings);
        return $accounts->login_url();
    }

    public function authorization_url(string $provider): string
    {
        $provider = sanitize_key($provider);
        if (!$this->is_provider_enabled($provider)) {
            return '';
        }

        $state = $this->create_state($provider);
        $redirect_uri = $this->callback_url($provider);

        $config = self::PROVIDER_CONFIG[$provider] ?? null;
        if (!is_array($config)) {
            return '';
        }

        $args = [
            'client_id' => $this->client_id($provider),
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => (string) ($config['scopes'] ?? ''),
            'state' => $state,
        ];

        if ($provider === 'google') {
            $args['access_type'] = 'online';
            $args['prompt'] = 'select_account';
        }

        $url = add_query_arg($args, (string) $config['auth_url']);
        return $this->is_authorization_url_allowed($provider, $url) ? $url : '';
    }

    public function is_authorization_url_allowed(string $provider, string $url): bool
    {
        $provider = sanitize_key($provider);
        $config = self::PROVIDER_CONFIG[$provider] ?? null;
        if (!is_array($config) || $url === '') {
            return false;
        }

        $parts = wp_parse_url($url);
        $expected = wp_parse_url((string) $config['auth_url']);
        if (!is_array($parts) || !is_array($expected)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $expected_scheme = strtolower((string) ($expected['scheme'] ?? ''));
        $expected_host = strtolower((string) ($config['auth_host'] ?? $expected['host'] ?? ''));
        $expected_path = (string) ($expected['path'] ?? '');

        return $scheme === 'https'
            && $scheme === $expected_scheme
            && $host === $expected_host
            && $path === $expected_path;
    }

    /**
     * @return array{email:string,name:string,id:string,provider:string,email_verified:bool}|WP_Error
     */
    public function user_from_callback(string $provider, string $code, string $state)
    {
        $provider = sanitize_key($provider);
        $code = sanitize_text_field($code);
        $state = sanitize_text_field($state);

        if (!array_key_exists($provider, self::PROVIDER_CONFIG) || $code === '' || !$this->consume_state($provider, $state)) {
            return new WP_Error('sltr_social_login_invalid_state', __('Social login state is invalid or expired. Please try again.', 'slotera-booking'));
        }

        if (!$this->is_provider_enabled($provider)) {
            return new WP_Error('sltr_social_login_disabled', __('This social login provider is not configured.', 'slotera-booking'));
        }

        if ($provider === 'google') {
            return $this->google_user_from_code($code);
        }

        if ($provider === 'facebook') {
            return $this->facebook_user_from_code($code);
        }

        return $this->apple_user_from_code($code);
    }

    /**
     * @param mixed $response
     * @return array<string,string|int>
     */
    private function oauth_response_diagnostics($response, string $provider, string $redirect_uri): array
    {
        if (is_wp_error($response)) {
            return [
                'wp_error_code' => $response->get_error_code(),
                'wp_error_message' => $response->get_error_message(),
                'redirect_uri' => $redirect_uri,
            ];
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $data = [
            'http_code' => (int) wp_remote_retrieve_response_code($response),
            'redirect_uri' => $redirect_uri,
        ];
        if (is_array($body)) {
            if (isset($body['error']) && is_scalar($body['error'])) {
                $data['provider_error'] = sanitize_text_field((string) $body['error']);
            }
            if (isset($body['error_description']) && is_scalar($body['error_description'])) {
                $data['provider_error_description'] = sanitize_text_field((string) $body['error_description']);
            }
            if (isset($body['error_message']) && is_scalar($body['error_message'])) {
                $data['provider_error_description'] = sanitize_text_field((string) $body['error_message']);
            }
        }

        $data['provider'] = sanitize_key($provider);
        return $data;
    }

    private function google_user_from_code(string $code)
    {
        $token = wp_remote_post((string) self::PROVIDER_CONFIG['google']['token_url'], [
            'timeout' => 15,
            'body' => [
                'code' => $code,
                'client_id' => $this->client_id('google'),
                'client_secret' => $this->client_secret('google'),
                'redirect_uri' => $this->callback_url('google'),
                'grant_type' => 'authorization_code',
            ],
        ]);

        if (is_wp_error($token)) {
            return new WP_Error('sltr_social_login_token_failed', __('Could not complete Google login. Please try again.', 'slotera-booking'), $this->oauth_response_diagnostics($token, 'google', $this->callback_url('google')));
        }

        $token_body = json_decode((string) wp_remote_retrieve_body($token), true);
        $access_token = is_array($token_body) ? (string) ($token_body['access_token'] ?? '') : '';
        if ($access_token === '') {
            return new WP_Error('sltr_social_login_token_failed', __('Could not complete Google login. Please try again.', 'slotera-booking'), $this->oauth_response_diagnostics($token, 'google', $this->callback_url('google')));
        }

        $profile = wp_remote_get((string) self::PROVIDER_CONFIG['google']['profile_url'], [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $access_token],
        ]);

        if (is_wp_error($profile)) {
            return new WP_Error('sltr_social_login_profile_failed', __('Could not read Google account profile.', 'slotera-booking'), $this->oauth_response_diagnostics($profile, 'google', $this->callback_url('google')));
        }

        $data = json_decode((string) wp_remote_retrieve_body($profile), true);
        if (!is_array($data)) {
            return new WP_Error('sltr_social_login_profile_failed', __('Could not read Google account profile.', 'slotera-booking'), $this->oauth_response_diagnostics($profile, 'google', $this->callback_url('google')));
        }

        $email = sanitize_email((string) ($data['email'] ?? ''));
        if ($email === '' || !is_email($email)) {
            return new WP_Error('sltr_social_login_email_missing', __('Your Google account did not provide an email address.', 'slotera-booking'), ['redirect_uri' => $this->callback_url('google')]);
        }
        if (!$this->is_verified_email_claim($data['email_verified'] ?? null)) {
            return new WP_Error('sltr_social_login_email_unverified', __('Your Google email address is not verified. Please use email login.', 'slotera-booking'), ['redirect_uri' => $this->callback_url('google')]);
        }

        return [
            'provider' => 'google',
            'id' => sanitize_text_field((string) ($data['sub'] ?? '')),
            'email' => $email,
            'name' => sanitize_text_field((string) ($data['name'] ?? '')),
            'email_verified' => true,
        ];
    }

    private function facebook_user_from_code(string $code)
    {
        $token = wp_remote_post((string) self::PROVIDER_CONFIG['facebook']['token_url'], [
            'timeout' => 15,
            'body' => [
                'client_id' => $this->client_id('facebook'),
                'client_secret' => $this->client_secret('facebook'),
                'redirect_uri' => $this->callback_url('facebook'),
                'code' => $code,
            ],
        ]);
        if (is_wp_error($token)) {
            return $token;
        }

        $token_body = json_decode((string) wp_remote_retrieve_body($token), true);
        $access_token = is_array($token_body) ? (string) ($token_body['access_token'] ?? '') : '';
        if ($access_token === '') {
            return new WP_Error('sltr_social_login_token_failed', __('Could not complete Facebook login. Please try again.', 'slotera-booking'));
        }

        $profile_url = add_query_arg([
            'fields' => 'id,name,email',
        ], (string) self::PROVIDER_CONFIG['facebook']['profile_url']);

        $profile = wp_remote_get($profile_url, [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $access_token],
        ]);
        if (is_wp_error($profile)) {
            return $profile;
        }

        $data = json_decode((string) wp_remote_retrieve_body($profile), true);
        if (!is_array($data)) {
            return new WP_Error('sltr_social_login_profile_failed', __('Could not read Facebook account profile.', 'slotera-booking'));
        }

        $email = sanitize_email((string) ($data['email'] ?? ''));
        if ($email === '' || !is_email($email)) {
            return new WP_Error('sltr_social_login_email_missing', __('Your Facebook account did not provide an email address. Please use email login.', 'slotera-booking'));
        }

        return [
            'provider' => 'facebook',
            'id' => sanitize_text_field((string) ($data['id'] ?? '')),
            'email' => $email,
            'name' => sanitize_text_field((string) ($data['name'] ?? '')),
            // Meta Graph does not expose an email_verified claim. The email field
            // is trusted only because it was returned by the fixed HTTPS /me
            // endpoint for this app-scoped access token with the email permission.
            'email_verified' => true,
        ];
    }


    private function apple_user_from_code(string $code)
    {
        $client_secret = $this->apple_client_secret();
        if ($client_secret === '') {
            return new WP_Error('sltr_social_login_apple_secret_failed', __('Could not prepare Apple login. Please check Apple Sign In settings.', 'slotera-booking'));
        }

        $token = wp_remote_post((string) self::PROVIDER_CONFIG['apple']['token_url'], [
            'timeout' => 15,
            'body' => [
                'code' => $code,
                'client_id' => $this->client_id('apple'),
                'client_secret' => $client_secret,
                'redirect_uri' => $this->callback_url('apple'),
                'grant_type' => 'authorization_code',
            ],
        ]);

        if (is_wp_error($token)) {
            return $token;
        }

        $token_body = json_decode((string) wp_remote_retrieve_body($token), true);
        $id_token = is_array($token_body) ? (string) ($token_body['id_token'] ?? '') : '';
        if ($id_token === '') {
            return new WP_Error('sltr_social_login_token_failed', __('Could not complete Apple login. Please try again.', 'slotera-booking'));
        }

        $data = $this->verify_apple_id_token($id_token);
        if (!is_array($data)) {
            return new WP_Error('sltr_social_login_profile_failed', __('Could not verify Apple account profile.', 'slotera-booking'));
        }

        if ((string) ($data['iss'] ?? '') !== 'https://appleid.apple.com' || (string) ($data['aud'] ?? '') !== $this->client_id('apple')) {
            return new WP_Error('sltr_social_login_profile_failed', __('Could not verify Apple account profile.', 'slotera-booking'));
        }

        $exp = isset($data['exp']) ? (int) $data['exp'] : 0;
        if ($exp > 0 && $exp < time()) {
            return new WP_Error('sltr_social_login_profile_failed', __('Apple login response has expired. Please try again.', 'slotera-booking'));
        }

        $email = sanitize_email((string) ($data['email'] ?? ''));
        if ($email === '' || !is_email($email)) {
            return new WP_Error('sltr_social_login_email_missing', __('Your Apple account did not provide an email address. Please use email login.', 'slotera-booking'));
        }
        if (!$this->is_verified_email_claim($data['email_verified'] ?? null)) {
            return new WP_Error('sltr_social_login_email_unverified', __('Your Apple email address is not verified. Please use email login.', 'slotera-booking'));
        }

        $name = sanitize_text_field((string) ($data['name'] ?? ''));
        if ($name === '') {
            $name = sanitize_text_field((string) preg_replace('/@.*/', '', $email));
        }

        return [
            'provider' => 'apple',
            'id' => sanitize_text_field((string) ($data['sub'] ?? '')),
            'email' => $email,
            'name' => $name,
            'email_verified' => true,
        ];
    }

    /** @param mixed $value */
    private function is_verified_email_claim($value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['true', '1'], true);
    }

    private function create_state(string $provider): string
    {
        $provider = sanitize_key($provider);
        $state = wp_generate_password(32, false, false);
        $key = $this->state_key($provider, $state);
        $hash = $this->state_cookie_hash($provider, $state);

        // Primary store: WordPress transient.
        // Store the browser binding server-side as well. A state value that is
        // valid globally must never be sufficient to authenticate a callback
        // arriving in another browser (login-CSRF / session swapping).
        set_transient($key, $hash, 10 * MINUTE_IN_SECONDS);

        // Fallback store: short-lived HttpOnly cookie. Some hosts/security/cache plugins
        // aggressively drop transients between the OAuth start and callback requests.
        // A SameSite=Lax cookie survives the Google top-level redirect without exposing
        // the raw state value to JavaScript.
        $this->set_state_cookie($provider, $hash, time() + (10 * MINUTE_IN_SECONDS));

        return $state;
    }

    private function consume_state(string $provider, string $state): bool
    {
        $provider = sanitize_key($provider);
        if ($state === '') {
            return false;
        }

        $key = $this->state_key($provider, $state);
        $stored_hash = get_transient($key);
        delete_transient($key);

        $cookie_name = $this->state_cookie_name($provider);
        $cookie_hash = isset($_COOKIE[$cookie_name]) ? sanitize_text_field((string) wp_unslash($_COOKIE[$cookie_name])) : '';
        $expected_hash = $this->state_cookie_hash($provider, $state);
        $server_ok = is_string($stored_hash)
            && $stored_hash !== ''
            && hash_equals($expected_hash, $stored_hash);
        $cookie_ok = $cookie_hash !== '' && hash_equals($expected_hash, $cookie_hash);
        $this->set_state_cookie($provider, '', time() - HOUR_IN_SECONDS);

        return $server_ok && $cookie_ok;
    }

    private function state_key(string $provider, string $state): string
    {
        return 'sltr_oauth_state_' . hash_hmac('sha256', sanitize_key($provider) . '|' . $state, wp_salt('auth'));
    }

    private function state_cookie_name(string $provider): string
    {
        return 'sltr_oauth_state_' . sanitize_key($provider);
    }

    private function state_cookie_hash(string $provider, string $state): string
    {
        return hash_hmac('sha256', sanitize_key($provider) . '|' . $state, wp_salt('secure_auth'));
    }

    private function set_state_cookie(string $provider, string $value, int $expires): void
    {
        $name = $this->state_cookie_name($provider);
        $path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
        $secure = is_ssl();

        if (PHP_VERSION_ID >= 70300) {
            setcookie($name, $value, [
                'expires' => $expires,
                'path' => $path,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie($name, $value, $expires, $path . '; SameSite=Lax', '', $secure, true);
        }

        if ($expires < time()) {
            unset($_COOKIE[$name]);
        } else {
            $_COOKIE[$name] = $value;
        }
    }


    private function apple_client_secret(): string
    {
        $team_id = trim((string) $this->settings->get('social_login_apple_team_id', ''));
        $key_id = trim((string) $this->settings->get('social_login_apple_key_id', ''));
        $private_key = trim((string) $this->settings->get('social_login_apple_private_key', ''));
        $client_id = $this->client_id('apple');

        if ($team_id === '' || $key_id === '' || $private_key === '' || $client_id === '') {
            return '';
        }

        $now = time();
        $header = ['alg' => 'ES256', 'kid' => $key_id];
        $claims = [
            'iss' => $team_id,
            'iat' => $now,
            'exp' => $now + 20 * MINUTE_IN_SECONDS,
            'aud' => 'https://appleid.apple.com',
            'sub' => $client_id,
        ];

        $segments = [
            $this->base64url_encode((string) wp_json_encode($header)),
            $this->base64url_encode((string) wp_json_encode($claims)),
        ];
        $payload = implode('.', $segments);

        if (!function_exists('openssl_sign')) {
            return '';
        }
        $signature = '';
        $ok = openssl_sign($payload, $signature, $private_key, OPENSSL_ALGO_SHA256);
        if (!$ok || $signature === '') {
            return '';
        }

        $raw_signature = $this->ecdsa_der_to_raw($signature, 64);
        if ($raw_signature === '') {
            return '';
        }

        $segments[] = $this->base64url_encode($raw_signature);
        return implode('.', $segments);
    }


    /** @return array<string,mixed>|null */
    private function verify_apple_id_token(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        $header = json_decode($this->base64url_decode($parts[0]), true);
        $payload = json_decode($this->base64url_decode($parts[1]), true);
        if (!is_array($header) || !is_array($payload)) {
            return null;
        }

        $kid = sanitize_text_field((string) ($header['kid'] ?? ''));
        $alg = strtoupper(sanitize_text_field((string) ($header['alg'] ?? '')));
        if ($kid === '' || $alg !== 'RS256' || !function_exists('openssl_verify')) {
            return null;
        }

        $pem = $this->apple_public_key_pem($kid);
        if ($pem === '') {
            return null;
        }

        $signature = $this->base64url_decode($parts[2]);
        if ($signature === '') {
            return null;
        }

        $verified = openssl_verify($parts[0] . '.' . $parts[1], $signature, $pem, OPENSSL_ALGO_SHA256);
        return $verified === 1 ? $payload : null;
    }

    private function apple_public_key_pem(string $kid): string
    {
        $keys = $this->apple_jwks();
        foreach ($keys as $key) {
            if (!is_array($key)) {
                continue;
            }
            if ((string) ($key['kid'] ?? '') !== $kid || (string) ($key['kty'] ?? '') !== 'RSA') {
                continue;
            }
            if (strtoupper((string) ($key['alg'] ?? 'RS256')) !== 'RS256') {
                continue;
            }
            $n = $this->base64url_decode((string) ($key['n'] ?? ''));
            $e = $this->base64url_decode((string) ($key['e'] ?? ''));
            if ($n === '' || $e === '') {
                continue;
            }
            return $this->rsa_public_key_pem($n, $e);
        }
        return '';
    }

    /** @return array<int,array<string,mixed>> */
    private function apple_jwks(): array
    {
        $cached = get_transient('sltr_apple_jwks');
        if (is_array($cached)) {
            return $cached;
        }

        $response = wp_remote_get('https://appleid.apple.com/auth/keys', ['timeout' => 10]);
        if (is_wp_error($response)) {
            return [];
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $keys = is_array($body) && isset($body['keys']) && is_array($body['keys']) ? $body['keys'] : [];
        if ($keys !== []) {
            set_transient('sltr_apple_jwks', $keys, 12 * HOUR_IN_SECONDS);
        }
        return $keys;
    }

    private function rsa_public_key_pem(string $modulus, string $exponent): string
    {
        $modulus = $this->asn1_unsigned_integer($modulus);
        $exponent = $this->asn1_unsigned_integer($exponent);
        $rsa_public_key = $this->asn1_sequence($modulus . $exponent);
        $algorithm = $this->asn1_sequence($this->asn1_object_identifier('1.2.840.113549.1.1.1') . "\x05\x00");
        $spki = $this->asn1_sequence($algorithm . "\x03" . $this->asn1_length(strlen($rsa_public_key) + 1) . "\x00" . $rsa_public_key);

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private function asn1_unsigned_integer(string $value): string
    {
        $value = ltrim($value, "\x00");
        if ($value === '') {
            $value = "\x00";
        }
        if ((ord($value[0]) & 0x80) !== 0) {
            $value = "\x00" . $value;
        }
        return "\x02" . $this->asn1_length(strlen($value)) . $value;
    }

    private function asn1_sequence(string $value): string
    {
        return "\x30" . $this->asn1_length(strlen($value)) . $value;
    }

    private function asn1_object_identifier(string $oid): string
    {
        $parts = array_map('intval', explode('.', $oid));
        if (count($parts) < 2) {
            return '';
        }
        $body = chr((40 * $parts[0]) + $parts[1]);
        for ($i = 2; $i < count($parts); $i++) {
            $body .= $this->asn1_base128($parts[$i]);
        }
        return "\x06" . $this->asn1_length(strlen($body)) . $body;
    }

    private function asn1_base128(int $value): string
    {
        $result = chr($value & 0x7f);
        $value >>= 7;
        while ($value > 0) {
            $result = chr(($value & 0x7f) | 0x80) . $result;
            $value >>= 7;
        }
        return $result;
    }

    private function asn1_length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    /** @return array<string,mixed>|null */
    private function decode_jwt_payload(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            return null;
        }

        $payload = $this->base64url_decode($parts[1]);
        $data = json_decode($payload, true);
        return is_array($data) ? $data : null;
    }

    private function base64url_encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64url_decode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }
        return (string) base64_decode(strtr($value, '-_', '+/'));
    }

    private function ecdsa_der_to_raw(string $der, int $part_length): string
    {
        $offset = 0;
        if (ord($der[$offset] ?? "\0") !== 0x30) {
            return '';
        }
        $offset++;
        $this->read_der_length($der, $offset);
        if (ord($der[$offset] ?? "\0") !== 0x02) {
            return '';
        }
        $offset++;
        $r_length = $this->read_der_length($der, $offset);
        $r = substr($der, $offset, $r_length);
        $offset += $r_length;
        if (ord($der[$offset] ?? "\0") !== 0x02) {
            return '';
        }
        $offset++;
        $s_length = $this->read_der_length($der, $offset);
        $s = substr($der, $offset, $s_length);

        $half = (int) ($part_length / 2);
        $r = str_pad(ltrim($r, "\x00"), $half, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), $half, "\x00", STR_PAD_LEFT);

        if (strlen($r) !== $half || strlen($s) !== $half) {
            return '';
        }

        return $r . $s;
    }

    private function read_der_length(string $der, int &$offset): int
    {
        $length = ord($der[$offset] ?? "\0");
        $offset++;
        if ($length < 0x80) {
            return $length;
        }
        $bytes = $length & 0x7f;
        $length = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $length = ($length << 8) | ord($der[$offset] ?? "\0");
            $offset++;
        }
        return $length;
    }

    private function client_id(string $provider): string
    {
        return trim((string) $this->settings->get('social_login_' . sanitize_key($provider) . '_client_id', ''));
    }

    private function client_secret(string $provider): string
    {
        return trim((string) $this->settings->get('social_login_' . sanitize_key($provider) . '_client_secret', ''));
    }
}
