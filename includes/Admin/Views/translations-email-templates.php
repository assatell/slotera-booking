<?php if (!defined('ABSPATH')) { exit; } ?>
    <section class="sltr-panel" style="margin:16px 0;padding:14px 16px;background:#fff;border:1px solid #dcdcde;border-radius:6px;max-width:980px;">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <?php wp_nonce_field('sltr_save_email_template_locale'); ?>
            <input type="hidden" name="action" value="sltr_save_email_template_locale">
            <label for="sltr-email-context-locale"><strong><?php esc_html_e('Default language for this section', 'slotera-booking'); ?></strong></label>
            <select id="sltr-email-context-locale" name="locale">
                <?php foreach (($email_languages ?? []) as $code => $label) : ?>
                    <option value="<?php echo esc_attr((string) $code); ?>" <?php selected(($email_default_locale ?? 'en_US'), (string) $code); ?>><?php echo esc_html((string) $label . ' (' . (string) $code . ')'); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button"><?php esc_html_e('Save default language', 'slotera-booking'); ?></button>
            <span class="description"><?php esc_html_e('Email templates use this language as the default editing/sending context. The available languages match the Frontend UI language list.', 'slotera-booking'); ?></span>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <?php wp_nonce_field('sltr_reset_all_email_templates'); ?>
            <input type="hidden" name="action" value="sltr_reset_all_email_templates">
            <button type="submit" class="button button-secondary" data-sltr-confirm="<?php echo esc_attr(__('Reset all email templates for the current language? This will overwrite custom email template text.', 'slotera-booking')); ?>" data-sltr-confirm-title="<?php esc_attr_e('Confirm action', 'slotera-booking'); ?>" data-sltr-confirm-button="<?php esc_attr_e('Confirm', 'slotera-booking'); ?>"><?php esc_html_e('Reset all email templates for current language', 'slotera-booking'); ?></button>
            <span class="description"><?php esc_html_e('Use this after changing the default language when you want saved templates to be replaced by that language\'s defaults.', 'slotera-booking'); ?></span>
        </form>
    </section>

    <section id="sltr-email-templates" class="sltr-panel" style="margin:16px 0; scroll-margin-top:48px;">
        <h2><?php esc_html_e('Email templates', 'slotera-booking'); ?></h2>
        <p><?php esc_html_e('Edit subjects and message templates for each email scenario.', 'slotera-booking'); ?></p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Scenario', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Description', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Recipient', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Enabled', 'slotera-booking'); ?></th>
                    <th><?php esc_html_e('Actions', 'slotera-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scenarios as $key => $scenario) : ?>
                    <?php $enabled = (int) ($settings['email_template_' . $key . '_enabled'] ?? 1); ?>
                    <tr>
                        <td><strong><?php echo esc_html($scenario['title']); ?></strong></td>
                        <td><?php echo esc_html($scenario['description']); ?></td>
                        <td><?php echo esc_html(ucfirst((string) ($scenario['recipient'] ?? 'customer'))); ?></td>
                        <td><?php echo $enabled ? esc_html__('Yes', 'slotera-booking') : esc_html__('No', 'slotera-booking'); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=slotera-translations&group=email_templates&scenario=' . $key)); ?>">
                                <?php esc_html_e('Edit template', 'slotera-booking'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
