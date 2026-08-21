<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="sltr-contact-form-wrap" style="<?php echo esc_attr($style); ?>">
    <form class="sltr-contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"<?php echo $security_captcha_provider === 'recaptcha_v3' ? ' data-sltr-recaptcha-v3-action="slotera_contact"' : ''; ?> novalidate>
        <input type="hidden" name="action" value="sltr_contact_form_submit">
        <?php wp_nonce_field('sltr_contact_form_submit'); ?>
        <input type="hidden" name="sltr_contact_locale" value="<?php echo esc_attr($contact_locale ?? 'en_US'); ?>">

        <?php if ($message !== '') : ?>
            <div class="sltr-contact-message <?php echo esc_attr($message_class); ?>" role="status"><?php echo esc_html($message); ?></div>
        <?php endif; ?>

        <label class="sltr-contact-field">
            <span><?php echo esc_html($contact_labels['name'] ?? 'Name'); ?> <em>*</em></span>
            <input type="text" name="sltr_contact_name" autocomplete="name" required>
        </label>

        <label class="sltr-contact-field">
            <span><?php echo esc_html($contact_labels['email'] ?? 'Email'); ?> <em>*</em></span>
            <input type="email" name="sltr_contact_email" autocomplete="email" required>
        </label>

        <label class="sltr-contact-field">
            <span><?php echo esc_html($contact_labels['phone'] ?? 'Phone'); ?></span>
            <input type="tel" name="sltr_contact_phone" autocomplete="tel">
        </label>

        <label class="sltr-contact-field">
            <span><?php echo esc_html($contact_labels['subject'] ?? 'Message Subject'); ?></span>
            <input type="text" name="sltr_contact_subject" autocomplete="off">
        </label>

        <label class="sltr-contact-field">
            <span><?php echo esc_html($contact_labels['message'] ?? 'Message'); ?> <em>*</em></span>
            <textarea name="sltr_contact_message" rows="6" required></textarea>
        </label>

        <?php if (!empty($settings['security_honeypot_enabled'])) : ?>
            <div class="sltr-honeypot" aria-hidden="true">
                <label for="sltr-contact-company-website"><?php echo esc_html($contact_labels['company'] ?? 'Company website'); ?></label>
                <input type="text" id="sltr-contact-company-website" name="company_website" value="" tabindex="-1" autocomplete="off">
            </div>
        <?php endif; ?>
        <input type="hidden" name="form_started_at" value="<?php echo esc_attr((string) time()); ?>">

        <?php if ($security_captcha_provider === 'turnstile' && $turnstile_site_key !== '') : ?>
            <div class="sltr-captcha sltr-turnstile"><div class="cf-turnstile" data-sitekey="<?php echo esc_attr($turnstile_site_key); ?>"></div></div>
        <?php elseif ($security_captcha_provider === 'recaptcha' && $recaptcha_site_key !== '') : ?>
            <div class="sltr-captcha sltr-recaptcha"><div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>"></div></div>
        <?php elseif ($security_captcha_provider === 'recaptcha_v3' && $recaptcha_site_key !== '') : ?>
            <input type="hidden" name="g_recaptcha_response" value="" data-sltr-recaptcha-v3-token>
        <?php endif; ?>

        <button type="submit" class="sltr-contact-submit"><?php echo esc_html($contact_labels['submit'] ?? 'Send Message'); ?></button>
    </form>
</div>
