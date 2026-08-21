<?php
if (!defined('ABSPATH')) { exit; }
if (sltr_view_file_exists($sltr_view = __DIR__ . '/package-form/setup.php')) { require $sltr_view; }
?>
<div class="wrap sltr-admin">
    <?php $sltr_error = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_error'); ?>
    <?php if ($sltr_error === 'save_failed') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Package could not be saved. Please review the fields and try again.', 'slotera-booking'); ?></p></div>
    <?php elseif ($sltr_error === 'slug_exists') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('This slug is already in use. Please choose another one.', 'slotera-booking'); ?></p></div>
    <?php elseif ($sltr_error === 'slug_locked') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('This operation is not possible. The slug is locked after the first save.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>
    <h1><?php echo esc_html($id ? sltr__('admin.package.edit_package') : sltr__('admin.package.new_package')); ?></h1>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" autocomplete="off">
        <input type="hidden" name="action" value="sltr_save_package">
        <input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>">
        <?php wp_nonce_field('sltr_save_package'); ?>
        <input type="hidden" name="sltr_package_compact_state" value="">
        <input type="hidden" name="sltr_confirm_immediately_simple" value="<?php echo esc_attr((string) ((int) $sltr_mode_value('simple', 'confirm_immediately', 0))); ?>">
        <input type="hidden" name="sltr_simple_price_mode" value="<?php echo esc_attr((string) $sltr_mode_value('simple', 'price_mode', 'fixed')); ?>">
        <input type="hidden" name="confirm_immediately_simple" value="<?php echo esc_attr((string) ((int) $sltr_mode_value('simple', 'confirm_immediately', 0))); ?>">
        <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/package-form/state-script.php')) { require_once $sltr_view; sltr_render_package_form_state_script(); } ?>

        <section id="sltr-package-settings" class="sltr-settings-card sltr-package-settings-block">
            <div class="sltr-settings-form">
                <h2><?php esc_html_e('Package settings', 'slotera-booking'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/package-form/sections/events.php')) { require $sltr_view; } ?>
                        <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/package-form/sections/identity.php')) { require $sltr_view; } ?>
                        <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/package-form/sections/status.php')) { require $sltr_view; } ?>
                    </tbody>
                </table>
                <p class="sltr-package-section-actions"><button type="submit" class="button button-primary" name="sltr_save_section" value="package-settings"><?php esc_html_e('Save', 'slotera-booking'); ?></button></p>
            </div>
        </section>

        <?php $sltr_solo_enabled = (int) ($package['solo_page_enabled'] ?? 1); ?>
        <div class="sltr-package-solo-toggle">
            <label for="sltr-package-solo-enabled"><strong><?php esc_html_e('Solo page', 'slotera-booking'); ?></strong></label>
            <select id="sltr-package-solo-enabled" name="solo_page_enabled">
                <option value="1" <?php selected($sltr_solo_enabled, 1); ?>><?php esc_html_e('Show solo page', 'slotera-booking'); ?></option>
                <option value="0" <?php selected($sltr_solo_enabled, 0); ?>><?php esc_html_e('Do not show solo page', 'slotera-booking'); ?></option>
            </select>
        </div>

        <section id="sltr-solo-page-settings" class="sltr-settings-card sltr-solo-settings-block">
            <div class="sltr-settings-form">
            <h2><?php esc_html_e('Solo page settings', 'slotera-booking'); ?></h2>
            <table class="form-table" role="presentation"><tbody>
                <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/package-form/sections/solo-content.php')) { require $sltr_view; } ?>
            </tbody></table>
                <p class="sltr-package-section-actions"><button type="submit" class="button button-primary" name="sltr_save_section" value="solo-page-settings"><?php esc_html_e('Save', 'slotera-booking'); ?></button></p>
            </div>
        </section>
        <section id="sltr-booking-blocks" class="sltr-settings-card sltr-booking-settings-block">
            <div class="sltr-settings-form">
            <h2><?php esc_html_e('Booking blocks', 'slotera-booking'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody id="sltr-standard-booking-settings" <?php echo $sltr_is_event_package ? 'style="display:none;"' : ''; ?>>
                    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/package-form/sections/booking-blocks-row.php')) { require $sltr_view; } ?>
                    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/package-form/sections/availability.php')) { require $sltr_view; } ?>
                </tbody>
                <tbody id="sltr-scheduled-event-settings" <?php echo !$sltr_is_event_package ? 'style="display:none;"' : ''; ?>>
                    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/package-form/sections/scheduled-event.php')) { require $sltr_view; } ?>
                </tbody>
            </table>
            </div>
        </section>
<script>(function(){var s=document.getElementById('sltr-package-solo-enabled');var more=document.querySelector('input[name="show_more_info"]');function sync(fromChange){var on=!s||s.value==='1';document.querySelectorAll('.sltr-solo-page-setting').forEach(function(r){r.style.display=on?'':'none';});if(more){more.disabled=!on;if(!on){more.checked=false;}else if(fromChange){more.checked=true;}}}if(s){s.addEventListener('change',function(){sync(true);});sync(false);}})();</script>

        <p>
            <button class="button button-primary"><?php esc_html_e('Save package', 'slotera-booking'); ?></button>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=slotera-packages')); ?>"><?php esc_html_e('Back', 'slotera-booking'); ?></a>
        </p>
    </form>
</div>
