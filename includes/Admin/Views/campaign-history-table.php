<?php
if (!defined('ABSPATH')) { exit; }
$sltr_history_context = isset($sltr_history_context) && $sltr_history_context === 'automation' ? 'automation' : 'coupon';
$sltr_history_title = $sltr_history_context === 'automation' ? __('Automation campaigns', 'slotera-booking') : __('Coupon Campaigns', 'slotera-booking');
$sltr_history_label = $sltr_history_context === 'automation' ? __('Automation campaign history', 'slotera-booking') : __('Coupon campaign history', 'slotera-booking');
?>
<section class="sltr-panel sltr-panel--flush">
    <h2><?php echo esc_html($sltr_history_title); ?></h2>
    <div class="sltr-responsive-table-wrapper" tabindex="0" role="region" aria-label="<?php echo esc_attr($sltr_history_label); ?>">
        <table class="widefat striped sltr-responsive-table">
            <thead><tr>
                <th><?php esc_html_e('Name', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Type', 'slotera-booking'); ?></th>
                <?php if ($sltr_history_context === 'coupon') : ?><th><?php esc_html_e('Coupon', 'slotera-booking'); ?></th><?php endif; ?>
                <th><?php esc_html_e('Status', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Progress', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Last activity', 'slotera-booking'); ?></th>
                <?php if ($sltr_history_context === 'automation') : ?><th><?php esc_html_e('Next run', 'slotera-booking'); ?></th><?php endif; ?>
                <th><?php esc_html_e('Created', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Actions', 'slotera-booking'); ?></th>
            </tr></thead>
            <tbody>
            <?php if (!empty($campaigns)) : foreach ($campaigns as $campaign) :
                $id = (int) ($campaign['id'] ?? 0);
                $status = sanitize_key((string) ($campaign['status'] ?? 'draft'));
                $s = is_array($campaign['stats'] ?? null) ? $campaign['stats'] : ['percent'=>0,'sent'=>0,'total'=>0,'failed'=>0,'last_activity'=>''];
                $active = in_array($status, ['queued', 'sending'], true);
                $paused = $status === 'paused';
                $cancelled = $status === 'stopped';
                $failed = (int) ($s['failed'] ?? 0);
                $automation_type = sanitize_key((string) ($campaign['automation_type'] ?? ''));
                $automation_enabled = (int) ($campaign['automation_enabled'] ?? 0) === 1;
                $automation_next_run = (int) ($campaign['automation_next_run'] ?? 0);
                $automation_rule_days = (int) ($campaign['automation_rule_days'] ?? 0);
                $automation_seconds = $automation_next_run > 0 ? max(0, $automation_next_run - time()) : 0;
                if ($automation_seconds < HOUR_IN_SECONDS) {
                    $automation_countdown = sprintf(__('%d min', 'slotera-booking'), max(1, (int) ceil($automation_seconds / MINUTE_IN_SECONDS)));
                } elseif ($automation_seconds < DAY_IN_SECONDS) {
                    $automation_countdown = sprintf(__('%d h', 'slotera-booking'), max(1, (int) ceil($automation_seconds / HOUR_IN_SECONDS)));
                } else {
                    $automation_countdown = sprintf(__('%d d', 'slotera-booking'), max(1, (int) ceil($automation_seconds / DAY_IN_SECONDS)));
                }
            ?>
                <tr>
                    <td><strong><?php echo esc_html((string) ($campaign['name'] ?? '')); ?></strong></td>
                    <td><?php if ($sltr_history_context === 'automation') { echo esc_html($automation_type === 'after_booking' ? __('After booking', 'slotera-booking') : __('Come back', 'slotera-booking')); } else { echo esc_html__('Coupon campaign', 'slotera-booking'); } ?></td>
                    <?php if ($sltr_history_context === 'coupon') : ?><td><?php $coupon_id = (int) ($campaign['coupon_id'] ?? 0); echo $coupon_id > 0 ? esc_html((string) ($coupon_codes[$coupon_id] ?? ('#' . $coupon_id))) : esc_html__('None', 'slotera-booking'); ?></td><?php endif; ?>
                    <td>
                        <?php if ($sltr_history_context === 'automation') : ?>
                            <span class="sltr-status-badge sltr-status-badge--<?php echo esc_attr($automation_enabled ? 'active' : 'stopped'); ?>"><?php echo esc_html($automation_enabled ? __('Active', 'slotera-booking') : __('Stopped', 'slotera-booking')); ?></span>
                        <?php else : ?>
                            <span class="sltr-status-badge sltr-status-badge--<?php echo esc_attr(sanitize_html_class($status)); ?>"><?php echo esc_html($status === 'stopped' ? __('cancelled', 'slotera-booking') : $status); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><progress class="sltr-progress" max="100" value="<?php echo esc_attr((string) (int) ($s['percent'] ?? 0)); ?>"></progress><small><?php printf(esc_html__('%1$d%% · sent %2$d/%3$d · failed %4$d', 'slotera-booking'), (int) ($s['percent'] ?? 0), (int) ($s['sent'] ?? 0), (int) ($s['total'] ?? 0), $failed); ?></small></td>
                    <td><?php echo esc_html((string) ($s['last_activity'] ?? '')); ?></td>
                    <?php if ($sltr_history_context === 'automation') : ?><td>
                        <?php if (!$automation_enabled) : ?>
                            <strong><?php esc_html_e('Stopped', 'slotera-booking'); ?></strong>
                        <?php elseif ($automation_next_run > 0) : ?>
                            <strong><?php printf(esc_html__('in %s', 'slotera-booking'), esc_html($automation_countdown)); ?></strong><br>
                            <small><?php echo esc_html(wp_date('Y-m-d H:i', $automation_next_run)); ?> · <?php echo esc_html($automation_type === 'after_booking' ? sprintf(__('send %d day(s) after booking', 'slotera-booking'), $automation_rule_days) : sprintf(__('after %d inactive day(s)', 'slotera-booking'), $automation_rule_days)); ?></small>
                        <?php else : ?>
                            <strong><?php esc_html_e('Not scheduled', 'slotera-booking'); ?></strong>
                        <?php endif; ?>
                    </td><?php endif; ?>
                    <td><?php echo esc_html((string) ($campaign['created_at'] ?? '')); ?></td>
                    <td>
                        <div class="sltr-form-actions sltr-form-actions--compact">
                            <?php if ($sltr_history_context === 'automation') : ?>
                                <?php if ($automation_enabled) : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="sltr_stop_marketing_automation">
                                        <input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>">
                                        <?php wp_nonce_field('sltr_marketing_automation_toggle_' . $id); ?>
                                        <button class="button button-small" type="submit"><?php esc_html_e('Stop', 'slotera-booking'); ?></button>
                                    </form>
                                <?php else : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="sltr_run_marketing_automation">
                                        <input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>">
                                        <?php wp_nonce_field('sltr_marketing_automation_toggle_' . $id); ?>
                                        <button class="button button-small button-primary" type="submit"><?php esc_html_e('Run', 'slotera-booking'); ?></button>
                                    </form>
                                <?php endif; ?>
                            <?php else : ?>
                                <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_coupon_tab=campaigns&action=edit&id=' . $id)); ?>"><?php esc_html_e('View', 'slotera-booking'); ?></a>
                                <?php if ($active) : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="sltr_process_marketing_queue_now"><input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>"><input type="hidden" name="return_history" value="1"><?php wp_nonce_field('sltr_process_marketing_queue_now_' . $id); ?><button class="button button-small" type="submit"><?php esc_html_e('Run batch now', 'slotera-booking'); ?></button></form>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="sltr_pause_marketing_campaign"><input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>"><input type="hidden" name="return_history" value="1"><?php wp_nonce_field('sltr_marketing_status_' . $id); ?><button class="button button-small" type="submit"><?php esc_html_e('Pause', 'slotera-booking'); ?></button></form>
                                <?php elseif ($paused) : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="sltr_resume_marketing_campaign"><input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>"><input type="hidden" name="return_history" value="1"><?php wp_nonce_field('sltr_marketing_status_' . $id); ?><button class="button button-small" type="submit"><?php esc_html_e('Resume', 'slotera-booking'); ?></button></form>
                                <?php endif; ?>
                                <?php if (($active || $paused) && !$cancelled) : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-sltr-confirm="<?php echo esc_attr(__('Cancel this campaign? Pending emails will remain in history but will not be sent.', 'slotera-booking')); ?>" data-sltr-confirm-title="<?php esc_attr_e('Confirm action', 'slotera-booking'); ?>" data-sltr-confirm-button="<?php esc_attr_e('Confirm', 'slotera-booking'); ?>"><input type="hidden" name="action" value="sltr_stop_marketing_campaign"><input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>"><input type="hidden" name="return_history" value="1"><?php wp_nonce_field('sltr_marketing_status_' . $id); ?><button class="button button-small" type="submit"><?php esc_html_e('Cancel', 'slotera-booking'); ?></button></form>
                                <?php endif; ?>
                                <?php if ($failed > 0 && !$active && !$paused && !$cancelled) : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="sltr_retry_failed_marketing_campaign"><input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>"><input type="hidden" name="return_history" value="1"><?php wp_nonce_field('sltr_retry_failed_marketing_campaign_' . $id); ?><button class="button button-small" type="submit"><?php esc_html_e('Retry failed', 'slotera-booking'); ?></button></form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-sltr-confirm="<?php echo esc_attr($sltr_history_context === 'automation' ? __('Delete this automation and its campaign history?', 'slotera-booking') : __('Delete this campaign and its sending history?', 'slotera-booking')); ?>" data-sltr-confirm-title="<?php esc_attr_e('Confirm action', 'slotera-booking'); ?>" data-sltr-confirm-button="<?php esc_attr_e('Confirm', 'slotera-booking'); ?>">
                                <input type="hidden" name="action" value="sltr_delete_marketing_campaign">
                                <input type="hidden" name="id" value="<?php echo esc_attr((string) $id); ?>">
                                <?php if ($sltr_history_context === 'coupon') : ?><input type="hidden" name="return_coupons" value="1"><?php endif; ?>
                                <?php wp_nonce_field('sltr_delete_marketing_campaign_' . $id); ?>
                                <button class="button button-small submitdelete" type="submit"><?php esc_html_e('Delete', 'slotera-booking'); ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; else : ?>
                <tr><td class="sltr-table-empty" colspan="<?php echo esc_attr($sltr_history_context === 'coupon' ? '8' : '8'); ?>"><div class="sltr-empty-state sltr-empty-state--compact"><?php esc_html_e('No campaigns yet.', 'slotera-booking'); ?></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
