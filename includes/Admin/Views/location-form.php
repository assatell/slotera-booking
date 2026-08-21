<?php
if (!defined('ABSPATH')) { exit; }
$location_id = (int) ($location['id'] ?? 0);
$is_active = (int) ($location['is_active'] ?? 1);
$sltr_location_faq = json_decode((string) ($location['faq_json'] ?? ''), true);
if (!is_array($sltr_location_faq) || empty($sltr_location_faq)) {
    $sltr_location_faq = [['question' => '', 'answer' => '']];
}
?>
<div class="wrap sltr-admin">
    <?php $sltr_error = (new \Slotera\Application\Services\RequestValidator())->get_key('sltr_error'); ?>
    <?php if ($sltr_error === 'save_failed') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Location could not be saved. Please review the fields and try again.', 'slotera-booking'); ?></p></div>
    <?php elseif ($sltr_error === 'slug_exists') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('This slug is already in use. Please choose another one.', 'slotera-booking'); ?></p></div>
    <?php elseif ($sltr_error === 'slug_locked') : ?>
        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('This operation is not possible. The slug is locked after the first save.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>
    <h1><?php echo $location_id ? esc_html__('Edit location', 'slotera-booking') : esc_html__('New location', 'slotera-booking'); ?></h1>
    <div class="sltr-admin-help-card">
        <strong><?php esc_html_e('Location setup guide', 'slotera-booking'); ?></strong>
        <p><?php esc_html_e('Use locations for cities or service areas such as Tallinn, Tartu or Riga. Packages can be assigned to multiple locations.', 'slotera-booking'); ?></p>
        <?php
        $sltr_location_hints = [];
        if ($location_id > 0) {
            if (trim(wp_strip_all_tags((string) ($location['intro_content'] ?? ''))) === '') { $sltr_location_hints[] = __('Local intro is empty; city landing pages will use fallback text.', 'slotera-booking'); }
            if (empty($sltr_location_faq) || (count($sltr_location_faq) === 1 && trim((string) ($sltr_location_faq[0]['question'] ?? '')) === '')) { $sltr_location_hints[] = __('Local FAQ is empty; FAQ block and FAQ schema will be skipped.', 'slotera-booking'); }
        }
        ?>
        <?php if (!empty($sltr_location_hints)) : ?>
            <div class="notice notice-info inline sltr-seo-validation-hints" style="margin:10px 0 0;padding:8px 12px;">
                <p><strong><?php esc_html_e('Validation hints', 'slotera-booking'); ?></strong></p>
                <ul style="margin:0 0 0 18px;list-style:disc;">
                    <?php foreach ($sltr_location_hints as $sltr_hint) : ?><li><?php echo esc_html($sltr_hint); ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="sltr_save_location">
        <input type="hidden" name="id" value="<?php echo esc_attr((string) $location_id); ?>">
        <?php wp_nonce_field('sltr_save_location'); ?>
        <table class="form-table" role="presentation">
            <tr><th><label for="sltr-location-name"><?php esc_html_e('Name', 'slotera-booking'); ?></label></th><td><input required id="sltr-location-name" class="regular-text" name="name" value="<?php echo esc_attr((string) ($location['name'] ?? '')); ?>"><p class="description"><?php esc_html_e('Customer-facing city or service area name.', 'slotera-booking'); ?></p></td></tr>
            <tr><th><label for="sltr-location-slug"><?php esc_html_e('Slug', 'slotera-booking'); ?></label></th><td><input id="sltr-location-slug" class="regular-text sltr-slug-field" name="slug" data-sltr-slug-source="#sltr-location-name" value="<?php echo esc_attr((string) ($location['slug'] ?? '')); ?>" <?php echo $location_id > 0 && trim((string) ($location['slug'] ?? '')) !== '' ? 'readonly aria-readonly="true"' : ''; ?>><?php if ($location_id > 0 && trim((string) ($location['slug'] ?? '')) !== '') : ?><button type="button" class="button" disabled><?php esc_html_e('Generated', 'slotera-booking'); ?></button><?php else : ?><button type="button" class="button sltr-generate-slug" data-sltr-no-processing="1" data-target="#sltr-location-slug" data-source="#sltr-location-name"><?php esc_html_e('Generate slug', 'slotera-booking'); ?></button><?php endif; ?><p class="description"><?php esc_html_e('The slug must be unique. After the first save it is locked and cannot be changed.', 'slotera-booking'); ?></p><p class="description"><strong><?php esc_html_e('Slug preview:', 'slotera-booking'); ?></strong> <code class="sltr-slug-preview" data-source="#sltr-location-slug"></code></p></td></tr>
            <tr><th><label for="sltr-location-description"><?php esc_html_e('Description', 'slotera-booking'); ?></label></th><td><textarea id="sltr-location-description" class="large-text" rows="4" name="description"><?php echo esc_textarea((string) ($location['description'] ?? '')); ?></textarea><p class="description"><?php esc_html_e('Short internal note or public description. Used as fallback only if Intro is empty.', 'slotera-booking'); ?></p></td></tr>
            <tr><th><label for="sltr-location-intro"><?php esc_html_e('Local intro', 'slotera-booking'); ?></label></th><td><textarea id="sltr-location-intro" class="large-text" rows="6" name="intro_content"><?php echo esc_textarea((string) ($location['intro_content'] ?? ($location['description'] ?? ''))); ?></textarea><p class="description"><?php esc_html_e('Optional general intro for this city/service area. Package-specific local pages use this when their override is empty.', 'slotera-booking'); ?></p></td></tr>
            <tr><th><?php esc_html_e('Local FAQ', 'slotera-booking'); ?></th><td>
                <div class="sltr-faq-list">
                    <?php foreach ($sltr_location_faq as $sltr_faq_index => $sltr_faq_item) : ?>
                        <div class="sltr-faq-item" style="margin:0 0 12px;padding:12px;border:1px solid #dcdcde;background:#fff;">
                            <input class="large-text" name="faq[<?php echo esc_attr((string) $sltr_faq_index); ?>][question]" placeholder="<?php esc_attr_e('Question', 'slotera-booking'); ?>" value="<?php echo esc_attr((string) ($sltr_faq_item['question'] ?? '')); ?>"><br><br>
                            <textarea class="large-text" rows="3" name="faq[<?php echo esc_attr((string) $sltr_faq_index); ?>][answer]" placeholder="<?php esc_attr_e('Answer', 'slotera-booking'); ?>"><?php echo esc_textarea((string) ($sltr_faq_item['answer'] ?? '')); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="description"><?php esc_html_e('Optional. If empty, no FAQ block or FAQ schema is rendered.', 'slotera-booking'); ?></p>
            </td></tr>
            <tr><th><label for="sltr-location-sort-order"><?php esc_html_e('Sort order', 'slotera-booking'); ?></label></th><td><input id="sltr-location-sort-order" type="number" name="sort_order" value="<?php echo esc_attr((string) ($location['sort_order'] ?? 0)); ?>"></td></tr>
            <tr><th><?php esc_html_e('Status', 'slotera-booking'); ?></th><td><strong><?php echo $is_active ? esc_html__('Active', 'slotera-booking') : esc_html__('Draft', 'slotera-booking'); ?></strong><p class="description"><?php esc_html_e('Change status from the Locations list using Deactivate or Restore.', 'slotera-booking'); ?></p></td></tr>
        </table>
        <p><button class="button button-primary"><?php esc_html_e('Save location', 'slotera-booking'); ?></button> <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=slotera-locations')); ?>"><?php esc_html_e('Back', 'slotera-booking'); ?></a></p>
    </form>
</div>
