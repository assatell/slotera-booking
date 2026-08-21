<?php

declare(strict_types=1);

namespace Slotera\Admin\Pages;

use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class SecurityPage
{
    public function render(bool $embedded = false): void
    {
        if (!current_user_can(\Slotera\Core\Capabilities::MANAGE_SETTINGS)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'slotera-booking'));
        }

        $settings = (new SettingsRepository())->all();
        $provider = (string) ($settings['security_captcha_provider'] ?? 'none');
        $rate_limit_window_minutes = max(0, (int) ($settings['security_rate_limit_window_minutes'] ?? 15));
        $sltr_rate_h = intdiv($rate_limit_window_minutes, 60);
        $sltr_rate_m = $rate_limit_window_minutes % 60;
        ?>
        <?php if (!$embedded) : ?><div class="wrap sltr-admin-page sltr-security-page"><h1><?php esc_html_e('Security', 'slotera-booking'); ?></h1><?php else : ?><div class="sltr-admin-page sltr-security-page"><h2><?php esc_html_e('Access & Protection', 'slotera-booking'); ?></h2><?php endif; ?>
            <div class="sltr-admin-help-card">
                <strong><?php esc_html_e('Security setup guide', 'slotera-booking'); ?></strong>
                <p><?php esc_html_e('These settings protect public booking and availability endpoints from spam, scraping and spoofed IP headers. Keep defaults enabled unless you understand the trade-off.', 'slotera-booking'); ?></p>
            </div>

            <?php if (isset($_GET['sltr_message']) && sanitize_key((string) wp_unslash($_GET['sltr_message'])) === 'saved') : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Security settings saved.', 'slotera-booking'); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-card">
                <?php wp_nonce_field('sltr_save_security_settings', 'sltr_nonce'); ?>
                <input type="hidden" name="action" value="sltr_save_security_settings">

                <h2><?php esc_html_e('Anti-spam defaults', 'slotera-booking'); ?></h2>
                <p class="description"><?php esc_html_e('Default protection uses Honeypot and a 4 second minimum form time. Trusted IPs bypass anti-spam checks.', 'slotera-booking'); ?></p>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Honeypot', 'slotera-booking'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="security_honeypot_enabled" value="1" <?php checked(!empty($settings['security_honeypot_enabled'])); ?>>
                                    <?php esc_html_e('Enable hidden honeypot field', 'slotera-booking'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Public REST booking', 'slotera-booking'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="security_public_rest_booking_enabled" value="1" <?php checked(!empty($settings['security_public_rest_booking_enabled'])); ?>>
                                    <?php esc_html_e('Allow booking creation through REST API', 'slotera-booking'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Keep this off for MVP unless the site frontend or a trusted headless integration needs POST /slotera/v1/bookings.', 'slotera-booking'); ?></p>
                                <label style="display:block;margin-top:8px">
                                    <input type="checkbox" name="security_public_rest_booking_security_reviewed" value="1" <?php checked(!empty($settings['security_public_rest_booking_security_reviewed'])); ?>>
                                    <?php esc_html_e('I have completed the REST booking security review for this deployment', 'slotera-booking'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Required before public REST booking can register or accept requests. Review must cover same-site REST nonce flow, HMAC clients, replay protection, rate limits and logging.', 'slotera-booking'); ?></p>

                                <?php $sltr_rest_booking_auth_mode = (string) ($settings['security_public_rest_booking_auth_mode'] ?? 'site_form'); ?>
                                <fieldset style="margin-top:10px">
                                    <legend class="screen-reader-text"><?php esc_html_e('Public REST booking authentication mode', 'slotera-booking'); ?></legend>
                                    <label>
                                        <input type="radio" name="security_public_rest_booking_auth_mode" value="site_form" <?php checked($sltr_rest_booking_auth_mode, 'site_form'); ?>>
                                        <?php esc_html_e('Site-form only: require WordPress REST nonce', 'slotera-booking'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="security_public_rest_booking_auth_mode" value="hmac" <?php checked($sltr_rest_booking_auth_mode, 'hmac'); ?>>
                                        <?php esc_html_e('Headless/server-to-server: require API key + HMAC signature', 'slotera-booking'); ?>
                                    </label>
                                </fieldset>
                                <p class="description"><?php esc_html_e('Site-form mode is for same-site WordPress forms. HMAC mode is for trusted external/headless clients and never accepts nonce-only public booking requests.', 'slotera-booking'); ?></p>

                                <p>
                                    <label for="security_public_rest_booking_api_key"><strong><?php esc_html_e('REST booking API key ID', 'slotera-booking'); ?></strong></label><br>
                                    <input type="password" id="security_public_rest_booking_api_key" name="security_public_rest_booking_api_key" value="" class="regular-text code" autocomplete="new-password" placeholder="<?php echo esc_attr(!empty($settings['security_public_rest_booking_api_key']) ? __('API key saved; leave blank to keep', 'slotera-booking') : __('Enter API key ID', 'slotera-booking')); ?>">
                                    <?php if (!empty($settings['security_public_rest_booking_api_key'])) : ?>
                                        <br><label><input type="checkbox" name="security_public_rest_booking_api_key_clear" value="1"> <?php esc_html_e('Clear saved REST booking API key', 'slotera-booking'); ?></label>
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <label for="security_public_rest_booking_hmac_secret"><strong><?php esc_html_e('REST booking HMAC secret', 'slotera-booking'); ?></strong></label><br>
                                    <input type="password" id="security_public_rest_booking_hmac_secret" name="security_public_rest_booking_hmac_secret" value="" class="regular-text code" autocomplete="new-password" placeholder="<?php echo esc_attr(!empty($settings['security_public_rest_booking_hmac_secret']) ? __('Secret saved; leave blank to keep', 'slotera-booking') : __('Enter secret to enable HMAC mode', 'slotera-booking')); ?>">
                                    <?php if (!empty($settings['security_public_rest_booking_hmac_secret'])) : ?>
                                        <br><label><input type="checkbox" name="security_public_rest_booking_hmac_secret_clear" value="1"> <?php esc_html_e('Clear saved HMAC secret', 'slotera-booking'); ?></label>
                                    <?php endif; ?>
                                </p>
                                <p class="description"><?php esc_html_e('HMAC clients must send X-Slotera-Api-Key, X-Slotera-Timestamp, X-Slotera-Nonce and X-Slotera-Signature. Signature payload: METHOD, route, timestamp, nonce and raw request body joined with newlines.', 'slotera-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="security_min_submit_seconds"><?php esc_html_e('Minimum form time', 'slotera-booking'); ?></label></th>
                            <td>
                                <input type="number" min="0" max="60" id="security_min_submit_seconds" name="security_min_submit_seconds" value="<?php echo esc_attr((string) ($settings['security_min_submit_seconds'] ?? 4)); ?>" class="small-text"> <?php esc_html_e('seconds', 'slotera-booking'); ?>
                                <p class="description"><?php esc_html_e('Bookings submitted faster than this are blocked. Set 0 to disable this check.', 'slotera-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Rate limit by IP', 'slotera-booking'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="security_rate_limit_ip_enabled" value="1" <?php checked(!empty($settings['security_rate_limit_ip_enabled'])); ?>>
                                    <?php esc_html_e('Enable IP rate limit', 'slotera-booking'); ?>
                                </label><br>
                                <input type="number" min="1" max="1000" name="security_rate_limit_ip_attempts" value="<?php echo esc_attr((string) ($settings['security_rate_limit_ip_attempts'] ?? 30)); ?>" class="small-text">
                                <?php esc_html_e('attempts per window', 'slotera-booking'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Availability REST rate limit', 'slotera-booking'); ?></th>
                            <td>
                                <strong><?php esc_html_e('Always enabled for anonymous public requests', 'slotera-booking'); ?></strong><br>
                                <input type="hidden" name="security_availability_rate_limit_enabled" value="1">
                                <input type="number" min="1" max="5000" name="security_availability_rate_limit_attempts" value="<?php echo esc_attr((string) ($settings['security_availability_rate_limit_attempts'] ?? 120)); ?>" class="small-text">
                                <?php esc_html_e('requests per window', 'slotera-booking'); ?>
                                <p class="description"><?php esc_html_e('Protects GET/POST /slotera/v1/availability from package/date scanning and scraping. Admins, valid same-site REST nonce requests, and configured trusted IPs bypass this limit.', 'slotera-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Rate limit by email', 'slotera-booking'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="security_rate_limit_email_enabled" value="1" <?php checked(!empty($settings['security_rate_limit_email_enabled'])); ?>>
                                    <?php esc_html_e('Enable email rate limit', 'slotera-booking'); ?>
                                </label><br>
                                <input type="number" min="1" max="1000" name="security_rate_limit_email_attempts" value="<?php echo esc_attr((string) ($settings['security_rate_limit_email_attempts'] ?? 10)); ?>" class="small-text">
                                <?php esc_html_e('attempts per window', 'slotera-booking'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="security_rate_limit_window_minutes"><?php esc_html_e('Rate limit window', 'slotera-booking'); ?></label></th>
                            <td>
                                <input type="number" min="0" max="24" name="security_rate_limit_window_hours" value="<?php echo esc_attr((string) $sltr_rate_h); ?>" class="small-text"> <?php esc_html_e('h', 'slotera-booking'); ?>
                                <input type="number" min="0" max="59" name="security_rate_limit_window_mins" value="<?php echo esc_attr((string) $sltr_rate_m); ?>" class="small-text"> <?php esc_html_e('min', 'slotera-booking'); ?>
                                <input type="hidden" id="security_rate_limit_window_minutes" name="security_rate_limit_window_minutes" value="<?php echo esc_attr((string) $rate_limit_window_minutes); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="security_trusted_ips"><?php esc_html_e('Trusted IPs', 'slotera-booking'); ?></label></th>
                            <td>
                                <textarea id="security_trusted_ips" name="security_trusted_ips" rows="5" class="large-text code" placeholder="203.0.113.10&#10;198.51.100.0/24"><?php echo esc_textarea((string) ($settings['security_trusted_ips'] ?? '')); ?></textarea>
                                <p class="description"><?php esc_html_e('One IP or CIDR range per line. These IPs bypass honeypot, minimum time, captcha and rate limits.', 'slotera-booking'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="security_trusted_proxies"><?php esc_html_e('Trusted proxies', 'slotera-booking'); ?></label></th>
                            <td>
                                <textarea id="security_trusted_proxies" name="security_trusted_proxies" rows="5" class="large-text code" placeholder="10.0.0.10&#10;172.16.0.0/12"><?php echo esc_textarea((string) ($settings['security_trusted_proxies'] ?? '')); ?></textarea>
                                <p class="description"><?php esc_html_e('One reverse proxy, load balancer, CDN or ingress IP/CIDR per line. Slotera trusts X-Forwarded-For, X-Real-IP and CF-Connecting-IP only when REMOTE_ADDR matches this list. Leave empty to ignore spoofable proxy headers except localhost.', 'slotera-booking'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Captcha provider', 'slotera-booking'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="security_captcha_provider"><?php esc_html_e('Provider', 'slotera-booking'); ?></label></th>
                            <td>
                                <select id="security_captcha_provider" name="security_captcha_provider">
                                    <option value="none" <?php selected($provider, 'none'); ?>><?php esc_html_e('None', 'slotera-booking'); ?></option>
                                    <option value="turnstile" <?php selected($provider, 'turnstile'); ?>><?php esc_html_e('Cloudflare Turnstile', 'slotera-booking'); ?></option>
                                    <option value="recaptcha" <?php selected($provider, 'recaptcha'); ?>><?php esc_html_e('Google reCAPTCHA v2 Checkbox', 'slotera-booking'); ?></option>
                                    <option value="recaptcha_v3" <?php selected($provider, 'recaptcha_v3'); ?>><?php esc_html_e('Google reCAPTCHA v3', 'slotera-booking'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Cloudflare Turnstile', 'slotera-booking'); ?></th>
                            <td>
                                <label><?php esc_html_e('Site key', 'slotera-booking'); ?><br>
                                    <input type="text" name="security_turnstile_site_key" value="<?php echo esc_attr((string) ($settings['security_turnstile_site_key'] ?? '')); ?>" class="regular-text code">
                                </label><br><br>
                                <label><?php esc_html_e('Secret key', 'slotera-booking'); ?><br>
                                    <input type="password" name="security_turnstile_secret_key" value="" class="regular-text code" autocomplete="new-password" placeholder="<?php echo esc_attr(!empty($settings['security_turnstile_secret_key']) ? __('Secret saved; leave blank to keep', 'slotera-booking') : __('Enter secret key', 'slotera-booking')); ?>">
                                    <?php if (!empty($settings['security_turnstile_secret_key'])) : ?><br><label><input type="checkbox" name="security_turnstile_secret_key_clear" value="1"> <?php esc_html_e('Clear saved Turnstile secret key', 'slotera-booking'); ?></label><?php endif; ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Google reCAPTCHA', 'slotera-booking'); ?></th>
                            <td>
                                <label><?php esc_html_e('Site key', 'slotera-booking'); ?><br>
                                    <input type="text" name="security_recaptcha_site_key" value="<?php echo esc_attr((string) ($settings['security_recaptcha_site_key'] ?? '')); ?>" class="regular-text code">
                                </label><br><br>
                                <label><?php esc_html_e('Secret key', 'slotera-booking'); ?><br>
                                    <input type="password" name="security_recaptcha_secret_key" value="" class="regular-text code" autocomplete="new-password" placeholder="<?php echo esc_attr(!empty($settings['security_recaptcha_secret_key']) ? __('Secret saved; leave blank to keep', 'slotera-booking') : __('Enter secret key', 'slotera-booking')); ?>">
                                    <?php if (!empty($settings['security_recaptcha_secret_key'])) : ?><br><label><input type="checkbox" name="security_recaptcha_secret_key_clear" value="1"> <?php esc_html_e('Clear saved reCAPTCHA secret key', 'slotera-booking'); ?></label><?php endif; ?>
                                </label>
                                <br><br>
                                <label><?php esc_html_e('reCAPTCHA v3 minimum score', 'slotera-booking'); ?><br>
                                    <input type="number" name="security_recaptcha_v3_threshold" value="<?php echo esc_attr((string) ($settings['security_recaptcha_v3_threshold'] ?? '0.5')); ?>" min="0" max="1" step="0.1" class="small-text">
                                </label>
                                <p class="description"><?php esc_html_e('Used only with reCAPTCHA v3. Start with 0.5 and adjust after reviewing real traffic in the reCAPTCHA console.', 'slotera-booking'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Save security settings', 'slotera-booking')); ?>
            </form>
        </div>
        <?php
    }
}
