<?php
if (!defined('ABSPATH')) { exit; }

$sltr_seo_plugins_blocking = !empty($detected_seo_plugins);
$sltr_tab_url = static function (string $target): string {
    return esc_url(add_query_arg(['page' => 'slotera-settings', 'section' => 'seo', 'tab' => $target], admin_url('admin.php')));
};
$sltr_seo_mode = (string) ($settings['seo_meta_output_mode'] ?? 'auto');
$sltr_wp_enabled = !empty($settings['seo_wp_pages_enabled']) && !$sltr_seo_plugins_blocking;
$sltr_sitemap_url = home_url('/slotera-sitemap.xml');


if (sltr_view_file_exists($sltr_view = __DIR__ . '/seo-settings/helpers.php')) { require $sltr_view; }
?>
<?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/seo-settings/header.php')) { require $sltr_view; } ?>
<?php if ($tab === 'settings') : ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/seo-settings/global-settings-tab.php')) { require $sltr_view; } ?>
<?php elseif ($tab === 'individual') : ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/seo-settings/individual-tab.php')) { require $sltr_view; } ?>
<?php elseif ($tab === 'templates') : ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/seo-settings/templates-tab.php')) { require $sltr_view; } ?>
<?php else : ?>
    <?php if (sltr_view_file_exists($sltr_view = __DIR__ . '/seo-settings/conflict-checks-tab.php')) { require $sltr_view; } ?>
<?php endif; ?>
</div>
