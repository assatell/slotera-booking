<?php
if (!defined('ABSPATH')) { exit; }

$sltr_contact_social_labels = [
    'instagram' => 'Instagram',
    'facebook' => 'Facebook',
    'linkedin' => 'LinkedIn',
    'x' => 'X (Twitter)',
    'youtube' => 'YouTube',
    'tiktok' => 'TikTok',
];
?>
<div class="sltr-contact-page-block sltr-package-contact-block" style="<?php echo esc_attr($style); ?>">
    <div class="sltr-package-contact-form">
        <?php echo $contact_form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered Slotera form view ?>
    </div>
    <aside class="sltr-package-contact-aside">
        <img class="sltr-package-contact-image" src="<?php echo esc_url($contact_page_image_url); ?>" alt="" loading="lazy">

        <?php if ($contact_page_address !== '') : ?>
            <div class="sltr-package-contact-details sltr-package-contact-address">
                <div class="sltr-package-contact-detail"><strong><?php esc_html_e('Address', 'slotera-booking'); ?></strong><span><?php echo esc_html($contact_page_address); ?></span></div>
            </div>
        <?php endif; ?>

        <?php if ($contact_page_map_url !== '') : ?>
            <p class="sltr-package-contact-map-link"><a href="<?php echo $contact_page_map_url; ?>" target="_blank" rel="noopener noreferrer" data-sltr-google-maps-popup>Google Maps</a></p>
        <?php endif; ?>

        <?php if ($contact_page_details) : ?>
            <div class="sltr-package-contact-details">
                <?php foreach ($contact_page_details as $detail) : if (!is_array($detail)) continue; ?>
                    <div class="sltr-package-contact-detail"><strong><?php echo esc_html((string) ($detail['label'] ?? '')); ?></strong><span><?php echo esc_html((string) ($detail['value'] ?? '')); ?></span></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($contact_page_socials) : ?>
            <div class="sltr-package-contact-details sltr-package-contact-socials" aria-label="<?php esc_attr_e('Social links', 'slotera-booking'); ?>">
                <?php foreach ($contact_page_socials as $social) : if (!is_array($social)) continue; ?>
                    <?php
                    $sltr_contact_social_platform = sanitize_key((string) ($social['platform'] ?? ''));
                    $sltr_contact_social_url = esc_url((string) ($social['url'] ?? ''));
                    if ($sltr_contact_social_url === '' || !isset($sltr_contact_social_labels[$sltr_contact_social_platform])) { continue; }
                    ?>
                    <a class="sltr-package-social-link" href="<?php echo $sltr_contact_social_url; ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($sltr_contact_social_labels[$sltr_contact_social_platform]); ?>">
                        <?php if ($sltr_contact_social_platform === 'instagram') : ?>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/></svg>
                        <?php elseif ($sltr_contact_social_platform === 'facebook') : ?>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3c-3.3 0-5 2-5 5v3H6v4h3v8h4v-8h3.4l.6-4h-4V9c0-.7.3-1 1-1z" fill="currentColor"/></svg>
                        <?php elseif ($sltr_contact_social_platform === 'linkedin') : ?>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="9" width="4" height="12" fill="currentColor"/><circle cx="5" cy="5" r="2" fill="currentColor"/><path d="M10 9h4v1.7c1-1.3 2.4-2 4-2 3 0 4 2 4 5.6V21h-4v-6c0-1.8-.5-3-2-3-1.7 0-2 1.4-2 3v6h-4z" fill="currentColor"/></svg>
                        <?php elseif ($sltr_contact_social_platform === 'x') : ?>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4l14 16M19 4L5 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                        <?php elseif ($sltr_contact_social_platform === 'youtube') : ?>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10 9l6 3-6 3z" fill="currentColor"/></svg>
                        <?php else : ?>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3v12.2a4.2 4.2 0 1 1-3-4V7.5c2.7 0 5-1.4 6-3.5 1 2.6 2.5 4 5 4v3.5c-1.7 0-3.2-.5-4.5-1.4V15a7.5 7.5 0 1 1-7.5-7.5V11a4 4 0 1 0 4 4V3z" fill="currentColor"/></svg>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</div>
