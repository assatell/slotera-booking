<?php if (!defined('ABSPATH')) { exit; }
$notice = isset($_GET['sltr_login_notice']) ? sanitize_key((string) wp_unslash($_GET['sltr_login_notice'])) : '';
$social_error = isset($_GET['sltr_social_error']) ? sanitize_key((string) wp_unslash($_GET['sltr_social_error'])) : '';
$prefill_email = isset($_GET['sltr_email']) ? sanitize_email(rawurldecode((string) wp_unslash($_GET['sltr_email']))) : '';
$messages = [
    'sent' => sltr_account_t('if_bookings_exist_for_this_email_we_sent_a_login_link_please_check_you'),
    'invalid_email' => sltr_t('Please enter a valid email address.'),
    'rate_limited' => sltr_t('Too many login link requests. Please try again later.'),
    'mail_failed' => sltr_t('We could not send the login email. Please contact the site administrator or check email delivery settings.'),
    'logged_out' => sltr_t('You have been logged out.'),
    'social_not_configured' => sltr_t('This social login provider is not available yet.'),
    'social_cancelled' => sltr_t('Social login was cancelled.'),
    'social_failed' => sltr_t('Social login failed. Please try again or use email login.'),
    'social_email_not_found' => sltr_t('This Google email is valid, but no Slotera booking account exists for it yet. Use the same email that was used for booking, or sign in by email.'),
];
$social_error_messages = [
    'sltr_social_login_invalid_state' => sltr_t('Diagnostic: OAuth state is invalid or expired. Try again from the login button; if it repeats, cache/security plugins may be blocking transients or cookies.'),
    'sltr_social_login_disabled' => sltr_t('Diagnostic: this social login provider is disabled or missing credentials in Slotera settings.'),
    'sltr_social_login_token_failed' => sltr_t('Diagnostic: Google returned a token exchange error. Check Client Secret and Authorized redirect URI in Google Cloud.'),
    'sltr_social_login_profile_failed' => sltr_t('Diagnostic: Google login succeeded, but Slotera could not read the Google profile. Check server outbound HTTPS requests.'),
    'sltr_social_login_email_missing' => sltr_t('Diagnostic: the social provider did not return an email address.'),
    'sltr_social_login_email_not_found' => sltr_t('Diagnostic: the social email was verified, but it does not match any existing Slotera booking customer email.'),
];
?>
<div class="sltr-login sltr-account-auth sltr-theme-<?php echo esc_attr($theme ?? 'light'); ?>" style="<?php echo esc_attr($style_vars ?? ''); ?>">
    <div class="sltr-account-panel">
    <h2><?php echo esc_html(sltr_t('Client login')); ?></h2>
    <?php if (!empty($is_logged_in)) : ?>
        <p><?php echo esc_html(sltr_t('You are already signed in to your client account.')); ?></p>
        <p><a class="sltr-button" href="<?php echo esc_url($account_url ?? home_url('/')); ?>"><?php echo esc_html(sltr_account_t('open_my_bookings')); ?></a></p>
    <?php else : ?>
        <p><?php echo esc_html(sltr_t('Enter the email you used for booking and we will send you a secure login link.')); ?></p>
        <?php if ($notice && isset($messages[$notice])) : ?>
            <div class="sltr-message <?php echo $notice === 'sent' ? 'is-success' : 'is-error'; ?>"><?php echo esc_html($messages[$notice]); ?></div>
        <?php endif; ?>
        <?php if ($social_error && isset($social_error_messages[$social_error])) : ?>
            <div class="sltr-message is-error sltr-social-login-diagnostic"><?php echo esc_html($social_error_messages[$social_error]); ?></div>
        <?php elseif ($social_error) : ?>
            <div class="sltr-message is-error sltr-social-login-diagnostic"><?php echo esc_html(sprintf(sltr_t('Diagnostic code: %s'), $social_error)); ?></div>
        <?php endif; ?>

        <?php
        $sltr_social_login = class_exists('Slotera\Application\Services\SocialLoginService') ? new \Slotera\Application\Services\SocialLoginService() : null;
        $sltr_social_providers = $sltr_social_login ? $sltr_social_login->enabled_providers() : [];
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-login-form">
            <input type="hidden" name="action" value="sltr_request_magic_link">
            <?php wp_nonce_field('sltr_request_magic_link'); ?>
            <label>
                <span><?php echo esc_html(sltr_t('Email')); ?></span>
                <input type="email" name="email" required autocomplete="email" placeholder="<?php echo esc_attr(sltr_t('you@example.com')); ?>" value="<?php echo esc_attr($prefill_email); ?>">
            </label>
            <button type="submit" class="sltr-button"><?php echo $notice === 'sent' ? esc_html(sltr_account_t('resend_login_link')) : esc_html(sltr_t('Send login link')); ?></button>
        </form>
        <?php if ($notice === 'sent') : ?>
            <p class="sltr-account-muted"><?php echo esc_html(sltr_t('Did not receive it? Check spam first, then use the resend button above. For security, requests are rate-limited.')); ?></p>
        <?php else : ?>
            <p class="sltr-account-muted"><?php echo esc_html(sltr_account_t('the_login_link_expires_in_30_minutes_your_account_session_stays_active')); ?></p>
        <?php endif; ?>
        <?php if (!empty($sltr_social_providers)) : ?>
            <div class="sltr-social-login-options sltr-social-login-options--after-email" aria-label="<?php echo esc_attr(sltr_t('Social login options')); ?>">
                <div class="sltr-login-divider"><span><?php echo esc_html(sltr__('frontend.or_continue_with')); ?></span></div>
                <?php foreach ($sltr_social_providers as $sltr_provider) :
                    $sltr_provider_label = sltr__('frontend.continue_with_' . sanitize_key($sltr_provider));
                    $sltr_provider_url = $sltr_social_login->start_url($sltr_provider);
                ?>
                    <p><a class="sltr-button sltr-social-login-button sltr-social-login-button--<?php echo esc_attr($sltr_provider); ?>" href="<?php echo esc_url($sltr_provider_url); ?>"><?php echo esc_html($sltr_provider_label); ?></a></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</div>
