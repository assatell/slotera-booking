<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="wrap sltr-admin-wrap sltr-pro-feature-page sltr-full-width-admin sltr-page-stack">
    <?php if (empty($features)) : ?>
        <div class="sltr-empty-state"><h2 class="sltr-empty-state__title"><?php esc_html_e('No PRO features are available', 'slotera-booking'); ?></h2><p><?php esc_html_e('Features will appear here when modules are enabled.', 'slotera-booking'); ?></p></div>
    <?php else : ?>
        <div class="sltr-component-grid sltr-component-grid--3">
            <?php foreach ($features as $key => $feature) : $status = (string) ($feature['status'] ?? 'available'); $slug = (string) ($feature['menu_slug'] ?? ''); $url = $slug !== '' ? admin_url('admin.php?page=' . $slug) : ''; $label = $status === 'active' ? __('Active', 'slotera-booking') : ($status === 'testable' ? __('Testable', 'slotera-booking') : ($status === 'soon' ? __('Coming soon', 'slotera-booking') : ($status === 'later' ? __('Later', 'slotera-booking') : __('Available', 'slotera-booking')))); ?>
                <article class="sltr-panel sltr-feature-panel"><div class="sltr-panel__header"><h2 class="sltr-panel__title"><?php echo esc_html((string) ($feature['title'] ?? $key)); ?></h2><span class="sltr-status-badge sltr-status-badge--<?php echo esc_attr(sanitize_html_class($status)); ?>"><?php echo esc_html($label); ?></span></div><div class="sltr-panel__body"><p><?php echo esc_html((string) ($feature['description'] ?? '')); ?></p><?php if (!empty($feature['items']) && is_array($feature['items'])) : ?><ul class="sltr-feature-list"><?php foreach ($feature['items'] as $item) : ?><li><?php echo esc_html((string) $item); ?></li><?php endforeach; ?></ul><?php endif; ?></div><footer class="sltr-panel__footer"><?php if ($url !== '') : ?><a class="button button-primary" href="<?php echo esc_url($url); ?>"><?php esc_html_e('Open', 'slotera-booking'); ?></a><?php else : ?><button class="button" disabled><?php esc_html_e('Not available yet', 'slotera-booking'); ?></button><?php endif; ?></footer></article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
