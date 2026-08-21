<?php if (!defined('ABSPATH')) { exit; } ?>
    <section id="sltr-social-share" class="sltr-panel sltr-settings-section" style="margin: 16px 0;">
        <h2><?php esc_html_e('Social Share Settings', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Show icon-only share buttons under the package card on single package pages.', 'slotera-booking'); ?></p>

        <?php
        $social_share_networks = array_filter(array_map('trim', explode(',', (string) ($settings['social_share_networks'] ?? 'facebook,x,whatsapp,telegram,linkedin,line,kakaotalk,viber,copy'))));
        $social_share_options = [
            'facebook' => ['label' => 'Facebook', 'note' => ''],
            'x' => ['label' => 'X / Twitter', 'note' => ''],
            'whatsapp' => ['label' => 'WhatsApp', 'note' => __('Only at mobile', 'slotera-booking')],
            'telegram' => ['label' => 'Telegram', 'note' => __('Only at mobile', 'slotera-booking')],
            'linkedin' => ['label' => 'LinkedIn', 'note' => ''],
            'line' => ['label' => 'LINE', 'note' => __('Popular in Japan, Taiwan and Thailand', 'slotera-booking')],
            'kakaotalk' => ['label' => 'KakaoTalk', 'note' => __('Mobile only; popular in Korea', 'slotera-booking')],
            'viber' => ['label' => 'Viber', 'note' => __('Mobile only', 'slotera-booking')],
            'copy' => ['label' => __('Copy link', 'slotera-booking'), 'note' => __('Recommended fallback', 'slotera-booking')],
        ];
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="sltr_save_social_share_settings">
            <input type="hidden" name="return_to" value="sltr-social-share">
            <input type="hidden" name="settings_section" value="advanced">
            <?php wp_nonce_field('sltr_save_social_share_settings'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable share buttons', 'slotera-booking'); ?></th>
                        <td>
                            <label><input type="checkbox" name="social_share_enabled" value="1" <?php checked((int) ($settings['social_share_enabled'] ?? 1), 1); ?>> <?php esc_html_e('Show social share block on single package pages.', 'slotera-booking'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Networks', 'slotera-booking'); ?></th>
                        <td>
                            <?php foreach ($social_share_options as $network_key => $network) : ?>
                                <label style="display:block;margin:0 0 8px;">
                                    <input type="checkbox" name="social_share_networks[]" value="<?php echo esc_attr($network_key); ?>" <?php checked(in_array($network_key, $social_share_networks, true)); ?>>
                                    <?php echo esc_html($network['label']); ?>
                                    <?php if ((string) $network['note'] !== '') : ?><span class="description">— <?php echo esc_html((string) $network['note']); ?></span><?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('WhatsApp, Telegram, KakaoTalk and Viber are hidden on desktop screens.', 'slotera-booking'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p><button class="button button-primary"><?php esc_html_e('Save social share settings', 'slotera-booking'); ?></button></p>
        </form>
    </section>
