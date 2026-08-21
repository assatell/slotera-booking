<?php if (!defined('ABSPATH')) { exit; } ?>
    <section id="sltr-social-login-settings" class="sltr-panel sltr-settings-section" style="margin: 16px 0;">
        <h2><?php esc_html_e('Client social login', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Allow clients to sign in to their Slotera booking account with Google, Facebook or Apple. Social login does not use the WordPress login screen and does not replace magic link login.', 'slotera-booking'); ?></p>


        <?php $sltr_last_social_error = get_transient('sltr_last_social_login_error'); ?>
        <?php if (is_array($sltr_last_social_error) && !empty($sltr_last_social_error['reason'])) : ?>
            <div class="notice notice-warning inline">
                <p><strong><?php esc_html_e('Last social login diagnostic', 'slotera-booking'); ?>:</strong>
                    <code><?php echo esc_html((string) ($sltr_last_social_error['reason'] ?? '')); ?></code>
                    <?php if (!empty($sltr_last_social_error['provider'])) : ?>
                        — <?php echo esc_html((string) $sltr_last_social_error['provider']); ?>
                    <?php endif; ?>
                    <?php if (!empty($sltr_last_social_error['http_code'])) : ?>
                        — HTTP <?php echo esc_html((string) $sltr_last_social_error['http_code']); ?>
                    <?php endif; ?>
                </p>
                <?php if (!empty($sltr_last_social_error['provider_error'])) : ?>
                    <p><?php esc_html_e('Provider error', 'slotera-booking'); ?>: <code><?php echo esc_html((string) $sltr_last_social_error['provider_error']); ?></code></p>
                <?php endif; ?>
                <?php if (!empty($sltr_last_social_error['provider_error_description'])) : ?>
                    <p><?php esc_html_e('Provider details', 'slotera-booking'); ?>: <code><?php echo esc_html((string) $sltr_last_social_error['provider_error_description']); ?></code></p>
                <?php endif; ?>
                <?php if (!empty($sltr_last_social_error['redirect_uri'])) : ?>
                    <p><?php esc_html_e('Redirect URI used', 'slotera-booking'); ?>: <code style="user-select:all;"><?php echo esc_html((string) $sltr_last_social_error['redirect_uri']); ?></code></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_save_social_login_settings">
            <input type="hidden" name="return_to" value="sltr-social-login-settings">
            <input type="hidden" name="settings_section" value="advanced">
            <?php wp_nonce_field('sltr_save_social_login_settings'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Google login', 'slotera-booking'); ?></th>
                        <td>
                            <label><input type="checkbox" name="social_login_google_enabled" value="1" <?php checked(!empty($settings['social_login_google_enabled'])); ?>> <?php esc_html_e('Enable Google login', 'slotera-booking'); ?></label>
                            <p><label><?php esc_html_e('Google Client ID', 'slotera-booking'); ?><br><input type="text" class="regular-text" name="social_login_google_client_id" value="<?php echo esc_attr((string) ($settings['social_login_google_client_id'] ?? '')); ?>" autocomplete="off"></label></p>
                            <p><label><?php esc_html_e('Google Client Secret', 'slotera-booking'); ?><br><input type="password" class="regular-text" name="social_login_google_client_secret" value="" autocomplete="new-password" placeholder="<?php echo esc_attr(!empty($settings['social_login_google_client_secret']) ? __('Secret saved; leave blank to keep', 'slotera-booking') : __('Enter client secret', 'slotera-booking')); ?>"></label><?php if (!empty($settings['social_login_google_client_secret'])) : ?><br><label><input type="checkbox" name="social_login_google_client_secret_clear" value="1"> <?php esc_html_e('Clear saved Google client secret', 'slotera-booking'); ?></label><?php endif; ?></p>
                            <p class="description"><?php esc_html_e('Frontend authorized redirect URI:', 'slotera-booking'); ?> <code style="user-select:all;"><?php echo esc_html($sltr_social_google_callback); ?></code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Facebook login', 'slotera-booking'); ?></th>
                        <td>
                            <label><input type="checkbox" name="social_login_facebook_enabled" value="1" <?php checked(!empty($settings['social_login_facebook_enabled'])); ?>> <?php esc_html_e('Enable Facebook login', 'slotera-booking'); ?></label>
                            <p><label><?php esc_html_e('Facebook App ID', 'slotera-booking'); ?><br><input type="text" class="regular-text" name="social_login_facebook_client_id" value="<?php echo esc_attr((string) ($settings['social_login_facebook_client_id'] ?? '')); ?>" autocomplete="off"></label></p>
                            <p><label><?php esc_html_e('Facebook App Secret', 'slotera-booking'); ?><br><input type="password" class="regular-text" name="social_login_facebook_client_secret" value="" autocomplete="new-password" placeholder="<?php echo esc_attr(!empty($settings['social_login_facebook_client_secret']) ? __('Secret saved; leave blank to keep', 'slotera-booking') : __('Enter app secret', 'slotera-booking')); ?>"></label><?php if (!empty($settings['social_login_facebook_client_secret'])) : ?><br><label><input type="checkbox" name="social_login_facebook_client_secret_clear" value="1"> <?php esc_html_e('Clear saved Facebook app secret', 'slotera-booking'); ?></label><?php endif; ?></p>
                            <p class="description"><?php esc_html_e('Frontend OAuth Redirect URI:', 'slotera-booking'); ?> <code style="user-select:all;"><?php echo esc_html($sltr_social_facebook_callback); ?></code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Apple login', 'slotera-booking'); ?></th>
                        <td>
                            <label><input type="checkbox" name="social_login_apple_enabled" value="1" <?php checked(!empty($settings['social_login_apple_enabled'])); ?>> <?php esc_html_e('Enable Apple login', 'slotera-booking'); ?></label>
                            <p><label><?php esc_html_e('Apple Services ID', 'slotera-booking'); ?><br><input type="text" class="regular-text" name="social_login_apple_client_id" value="<?php echo esc_attr((string) ($settings['social_login_apple_client_id'] ?? '')); ?>" autocomplete="off"></label></p>
                            <p><label><?php esc_html_e('Apple Team ID', 'slotera-booking'); ?><br><input type="text" class="regular-text" name="social_login_apple_team_id" value="<?php echo esc_attr((string) ($settings['social_login_apple_team_id'] ?? '')); ?>" autocomplete="off"></label></p>
                            <p><label><?php esc_html_e('Apple Key ID', 'slotera-booking'); ?><br><input type="text" class="regular-text" name="social_login_apple_key_id" value="<?php echo esc_attr((string) ($settings['social_login_apple_key_id'] ?? '')); ?>" autocomplete="off"></label></p>
                            <p><label><?php esc_html_e('Apple private key (.p8)', 'slotera-booking'); ?><br><textarea class="large-text code" rows="6" name="social_login_apple_private_key" autocomplete="off" placeholder="<?php echo esc_attr(!empty($settings['social_login_apple_private_key']) ? __('Private key saved; leave blank to keep', 'slotera-booking') : __('Paste private key', 'slotera-booking')); ?>"></textarea></label><?php if (!empty($settings['social_login_apple_private_key'])) : ?><br><label><input type="checkbox" name="social_login_apple_private_key_clear" value="1"> <?php esc_html_e('Clear saved Apple private key', 'slotera-booking'); ?></label><?php endif; ?></p>
                            <p class="description"><?php esc_html_e('Frontend OAuth Redirect URI:', 'slotera-booking'); ?> <code style="user-select:all;"><?php echo esc_html($sltr_social_apple_callback); ?></code></p>
                            <p class="description"><?php esc_html_e('Use an Apple Services ID with Sign in with Apple enabled. The private key is used only to generate the Apple client secret server-side.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p><button class="button button-primary"><?php esc_html_e('Save social login settings', 'slotera-booking'); ?></button></p>
        </form>
    </section>

