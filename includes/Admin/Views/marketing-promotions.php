<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
wp_enqueue_media();
$sltr_get = wp_unslash($_GET);
$promotion_service = $promotion_service ?? new \Slotera\Application\Services\PromotionCampaignService();
$promotion_settings = $promotion_settings ?? $promotion_service->settings();
$promotion_offers = $promotion_offers ?? $promotion_service->active_offers();
$promotion_recipients = $promotion_recipients ?? $promotion_service->eligible_recipient_count();
$with_images = array_values(array_filter($promotion_offers, static fn(array $o): bool => !empty($o['image_url'])));
$without_images = array_values(array_filter($promotion_offers, static fn(array $o): bool => empty($o['image_url'])));
$fallback_id = absint($promotion_settings['fallback_image_id'] ?? 0);
$fallback_url = $fallback_id > 0 ? (string) (wp_get_attachment_image_url($fallback_id, 'medium') ?: wp_get_attachment_url($fallback_id)) : '';
?>
<div class="wrap sltr-admin-wrap sltr-marketing-page sltr-full-width-admin sltr-page-stack">
    <?php $sltr_marketing_section = 'promotions'; require SLTR_PLUGIN_DIR . 'includes/Admin/Views/marketing-shell-tabs.php'; ?>
    <?php if (!empty($sltr_get['promotion_saved'])) : ?><div class="notice notice-success"><p>Promotion settings saved.</p></div><?php endif; ?>
    <?php if (!empty($sltr_get['promotion_test_sent'])) : ?><div class="notice notice-success"><p>Test promotion email sent.</p></div><?php endif; ?>
    <?php if (!empty($sltr_get['promotion_test_failed'])) : ?><div class="notice notice-error"><p>Test promotion email could not be sent.</p></div><?php endif; ?>
    <?php if (isset($sltr_get['promotion_queued'])) : ?><div class="notice notice-success"><p>Promotion campaign queued for <?php echo esc_html((string) absint($sltr_get['promotion_queued'])); ?> recipient(s).</p></div><?php endif; ?>

    <h2>Promotions</h2>
    <p>Active offers are collected automatically from package Discount, Weekend offer discount % and Seasonal offer period settings. Automatic sends run on Friday so the latest weekend offers are included.</p>

    <form id="sltr-promotion-settings-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sltr-card" style="padding:20px;max-width:1100px;">
        <input type="hidden" name="action" value="sltr_save_promotion_digest">
        <?php wp_nonce_field('sltr_save_promotion_digest'); ?>
        <h3>Schedule</h3>
        <p><label><strong>Frequency</strong><br>
            <select name="promotion_digest_frequency">
                <?php foreach (['manual'=>'Manual','weekly'=>'Once a week','biweekly'=>'Once every 2 weeks','monthly'=>'Once a month'] as $value=>$label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected((string) $promotion_settings['frequency'], $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label></p>
        <p><label><input type="checkbox" name="promotion_digest_enabled" value="1" <?php checked((int) $promotion_settings['enabled'], 1); ?>> Enable automatic Friday sending</label></p>
        <p><strong>Last run:</strong> <?php echo esc_html((string) ($promotion_settings['last_run'] ?: '—')); ?> &nbsp; <strong>Next:</strong> <?php echo esc_html($promotion_service->next_run_label()); ?> &nbsp; <strong>Last result:</strong> <?php echo esc_html((string) ($promotion_settings['last_result'] ?: '—')); ?></p>

        <h3>Email content</h3>
        <p><label><strong>Subject</strong><br><input type="text" class="regular-text" style="width:100%;max-width:720px" name="promotion_digest_subject" value="<?php echo esc_attr((string) $promotion_settings['subject']); ?>"></label></p>
        <p><label><strong>Intro text</strong><br><textarea name="promotion_digest_intro" rows="4" style="width:100%;max-width:720px"><?php echo esc_textarea((string) $promotion_settings['intro']); ?></textarea></label></p>
        <p><label><strong>Button text</strong><br><input type="text" class="regular-text" name="promotion_digest_button_label" value="<?php echo esc_attr((string) $promotion_settings['button_label']); ?>"></label></p>
        <p><label><strong>Closing text</strong><br><textarea name="promotion_digest_closing" rows="3" style="width:100%;max-width:720px"><?php echo esc_textarea((string) $promotion_settings['closing']); ?></textarea></label></p>

        <h3>Images</h3>
        <p>Packages use <strong>Slotera Booking page image</strong> first and <strong>Package page image</strong> second. Packages with neither image use the one common image selected below.</p>
        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
            <img id="sltr-promotion-fallback-preview" src="<?php echo esc_url($fallback_url); ?>" alt="" style="<?php echo $fallback_url === '' ? 'display:none;' : ''; ?>width:180px;height:auto;border-radius:10px;">
            <input type="hidden" id="sltr-promotion-fallback-image" name="promotion_digest_fallback_image_id" value="<?php echo esc_attr((string) $fallback_id); ?>">
            <button type="button" class="button" id="sltr-promotion-choose-image">Choose common image from Media Library</button>
            <button type="button" class="button" id="sltr-promotion-clear-image">Clear</button>
        </div>

        <h3>Active offers (<?php echo esc_html((string) count($promotion_offers)); ?>)</h3>
        <p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=slotera-marketing&sltr_marketing_section=promotions')); ?>">Refresh active offers</a></p>
        <?php if ($promotion_offers === []) : ?><p><em>No active promotional offers right now.</em></p><?php endif; ?>
        <?php foreach ($with_images as $offer) : ?>
            <div style="display:grid;grid-template-columns:140px 1fr;gap:16px;padding:14px 0;border-top:1px solid #dcdcde;max-width:900px;">
                <img src="<?php echo esc_url((string) $offer['image_url']); ?>" alt="" style="width:140px;height:95px;object-fit:cover;border-radius:8px;">
                <div><strong><?php echo esc_html((string) $offer['title']); ?></strong><br>
                    <span><?php echo esc_html(number_format_i18n((float) $offer['old_price'], 2)); ?> → <strong><?php echo esc_html(number_format_i18n((float) $offer['new_price'], 2)); ?></strong></span><br>
                    <small><?php echo esc_html((string) $offer['offer_label']); ?> · <?php echo esc_html((string) $offer['validity']); ?> · Image: <?php echo esc_html((string) $offer['image_source']); ?></small>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($without_images !== []) : ?>
            <h4>Packages without images</h4>
            <p>These packages will be grouped under the one common image above.</p>
            <ul>
                <?php foreach ($without_images as $offer) : ?><li><strong><?php echo esc_html((string) $offer['title']); ?></strong> — <?php echo esc_html(number_format_i18n((float) $offer['old_price'], 2)); ?> → <?php echo esc_html(number_format_i18n((float) $offer['new_price'], 2)); ?> — <?php echo esc_html((string) $offer['validity']); ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p><strong>Eligible marketing recipients:</strong> <?php echo esc_html((string) $promotion_recipients); ?></p>
        <p><button type="submit" class="button button-primary">Save promotion settings</button></p>
    </form>

    <div class="sltr-card" style="padding:20px;max-width:1100px;margin-top:20px;">
        <h3>Preview email</h3>
        <?php $promotion_preview = $promotion_service->preview((string) ($promotion_settings['test_email'] ?? '')); ?>
        <p><strong>Subject:</strong> <?php echo esc_html((string) ($promotion_preview['subject'] ?? '')); ?></p>
        <iframe title="Promotion email preview" style="width:100%;height:720px;border:1px solid #ccd0d4;background:#fff" srcdoc="<?php echo esc_attr((string) ($promotion_preview['body'] ?? '')); ?>"></iframe>
        <form id="sltr-promotion-test-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:16px;display:flex;gap:8px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="action" value="sltr_send_promotion_test"><?php wp_nonce_field('sltr_send_promotion_test'); ?>
            <label><strong>Test email</strong><br><input type="email" name="promotion_test_email" value="<?php echo esc_attr((string) $promotion_settings['test_email']); ?>" required></label>
            <button type="submit" class="button">Send test email</button>
        </form>
        <form id="sltr-promotion-send-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
            <input type="hidden" name="action" value="sltr_send_promotion_now"><?php wp_nonce_field('sltr_send_promotion_now'); ?>
            <button type="submit" class="button button-primary" <?php disabled($promotion_offers === []); ?>>Send now</button>
        </form>
    </div>
</div>
<script>
(function(){
  var choose=document.getElementById('sltr-promotion-choose-image'), clear=document.getElementById('sltr-promotion-clear-image'), input=document.getElementById('sltr-promotion-fallback-image'), preview=document.getElementById('sltr-promotion-fallback-preview');
  if(!choose || !window.wp || !wp.media) return;
  choose.addEventListener('click',function(){ var frame=wp.media({title:'Choose common promotion image',multiple:false,library:{type:'image'}}); frame.on('select',function(){var a=frame.state().get('selection').first().toJSON(); input.value=a.id||''; preview.src=(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url); preview.style.display='block';}); frame.open(); });
  clear.addEventListener('click',function(){ input.value=''; preview.src=''; preview.style.display='none'; });

  var settingsForm=document.getElementById('sltr-promotion-settings-form');
  function syncCurrentSettings(targetForm){
    if(!settingsForm || !targetForm) return;
    var names=['promotion_digest_frequency','promotion_digest_enabled','promotion_digest_subject','promotion_digest_intro','promotion_digest_button_label','promotion_digest_closing','promotion_digest_fallback_image_id'];
    names.forEach(function(name){
      targetForm.querySelectorAll('input[data-sltr-promotion-copy="'+name+'"]').forEach(function(node){node.remove();});
      var source=settingsForm.elements[name];
      if(!source) return;
      var value=(source.type==='checkbox')?(source.checked?'1':'0'):source.value;
      var hidden=document.createElement('input');
      hidden.type='hidden';
      hidden.name=name;
      hidden.value=value;
      hidden.setAttribute('data-sltr-promotion-copy',name);
      targetForm.appendChild(hidden);
    });
  }
  var sendForm=document.getElementById('sltr-promotion-send-form');
  var testForm=document.getElementById('sltr-promotion-test-form');
  if(sendForm) sendForm.addEventListener('submit',function(){syncCurrentSettings(sendForm);});
  if(testForm) testForm.addEventListener('submit',function(){syncCurrentSettings(testForm);});
})();
</script>
