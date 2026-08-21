<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
$closures = isset($settings['business_closures']) && is_array($settings['business_closures']) ? $settings['business_closures'] : [];
$closures[] = ['start_date' => '', 'end_date' => '', 'reason' => 'holiday', 'note' => ''];
?>
<section id="sltr-closures" class="sltr-panel sltr-settings-section" style="margin:16px 0;">
    <h2><?php esc_html_e('Days off / Closures', 'slotera-booking'); ?></h2>
    <p class="description"><?php esc_html_e('Close booking for holidays, inventory, maintenance or other exceptional dates. Closures override global and package working hours, including Open 24/7.', 'slotera-booking'); ?></p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_save_closures">
        <input type="hidden" name="return_to" value="sltr-closures">
        <?php wp_nonce_field('sltr_save_closures'); ?>
        <table class="widefat striped" id="sltr-closures-table">
            <thead><tr>
                <th><?php esc_html_e('From', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('To', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Reason', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Note', 'slotera-booking'); ?></th>
                <th><?php esc_html_e('Remove', 'slotera-booking'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($closures as $index => $closure) : ?>
                <tr class="sltr-closure-row">
                    <td><input type="date" name="closures[<?php echo (int) $index; ?>][start_date]" value="<?php echo esc_attr((string) ($closure['start_date'] ?? '')); ?>"></td>
                    <td><input type="date" name="closures[<?php echo (int) $index; ?>][end_date]" value="<?php echo esc_attr((string) ($closure['end_date'] ?? '')); ?>"></td>
                    <td><select name="closures[<?php echo (int) $index; ?>][reason]">
                        <?php foreach (['holiday' => __('Holiday', 'slotera-booking'), 'inventory' => __('Inventory', 'slotera-booking'), 'maintenance' => __('Maintenance', 'slotera-booking'), 'private_event' => __('Private event', 'slotera-booking'), 'other' => __('Other', 'slotera-booking')] as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($closure['reason'] ?? 'other'), $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select></td>
                    <td><input type="text" maxlength="191" class="regular-text" name="closures[<?php echo (int) $index; ?>][note]" value="<?php echo esc_attr((string) ($closure['note'] ?? '')); ?>" placeholder="<?php echo esc_attr__('Optional internal note', 'slotera-booking'); ?>"></td>
                    <td><button type="button" class="button-link-delete sltr-remove-closure"><?php esc_html_e('Remove', 'slotera-booking'); ?></button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p><button type="button" class="button" id="sltr-add-closure"><?php esc_html_e('Add closure', 'slotera-booking'); ?></button> <button class="button button-primary"><?php esc_html_e('Save closures', 'slotera-booking'); ?></button></p>
    </form>
    <script>
    (function(){
        var table=document.getElementById('sltr-closures-table'); var add=document.getElementById('sltr-add-closure'); if(!table||!add)return;
        function renumber(){table.querySelectorAll('.sltr-closure-row').forEach(function(row,i){row.querySelectorAll('[name]').forEach(function(el){el.name=el.name.replace(/closures\[\d+\]/,'closures['+i+']');});});}
        table.addEventListener('click',function(e){if(!e.target.classList.contains('sltr-remove-closure'))return; var row=e.target.closest('tr'); if(row){row.remove();renumber();}});
        add.addEventListener('click',function(){var rows=table.querySelectorAll('.sltr-closure-row'); var source=rows[rows.length-1]; if(!source)return; var row=source.cloneNode(true); row.querySelectorAll('input').forEach(function(el){el.value='';}); var sel=row.querySelector('select'); if(sel)sel.value='holiday'; table.querySelector('tbody').appendChild(row);renumber();});
    })();
    </script>
</section>
