<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\SEOService;
use Slotera\Application\Services\RobotsTxtService;
use Slotera\Core\Capabilities;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\CategoryRepository;
use Slotera\Infrastructure\Repositories\LocationRepository;
use Slotera\Core\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class SeoController
{
    private RequestValidator $request;
    private SettingsRepository $settings;
    private SEOService $seo;

    public function __construct(?RequestValidator $request = null, ?SettingsRepository $settings = null, ?SEOService $seo = null)
    {
        $this->request = $request ?? new RequestValidator();
        $this->settings = $settings ?? new SettingsRepository();
        $this->seo = $seo ?? new SEOService();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_seo_center_settings', [$this, 'save_center_settings']);
        add_action('admin_post_sltr_create_robots_txt', [$this, 'create_robots_txt']);
        add_action('admin_post_sltr_save_wp_page_seo', [$this, 'save_wp_page_seo']);
        add_action('admin_post_sltr_save_slotera_package_seo', [$this, 'save_package_seo']);
        add_action('admin_post_sltr_save_slotera_category_seo', [$this, 'save_category_seo']);
        add_action('admin_post_sltr_save_slotera_local_seo', [$this, 'save_local_seo']);
        add_action('admin_post_sltr_save_seo_templates', [$this, 'save_templates']);
        add_action('admin_post_sltr_bulk_apply_seo_metadata', [$this, 'bulk_apply_metadata']);
    }

    public function save_center_settings(): void
    {
        $this->verify('sltr_save_seo_center_settings');

        $external_active = count($this->seo->detected_external_plugins()) > 0;
        $wp_pages_enabled = $external_active ? 0 : $this->request->post_bool('seo_wp_pages_enabled');

        $this->settings->update([
            'seo_wp_pages_enabled' => $wp_pages_enabled,
            'seo_sitemap_enabled' => $this->request->post_bool('seo_sitemap_enabled'),
            'seo_sitemap_include_packages' => $this->request->post_bool('seo_sitemap_include_packages'),
            'seo_sitemap_include_categories' => $this->request->post_bool('seo_sitemap_include_categories'),
            'seo_sitemap_include_locations' => $this->request->post_bool('seo_sitemap_include_locations'),
            'seo_sitemap_include_other_pages' => $this->request->post_bool('seo_sitemap_include_other_pages'),
            'seo_sitemap_include_posts' => $this->request->post_bool('seo_sitemap_include_posts'),
            'seo_robots_smart_builder_enabled' => $this->request->post_bool('seo_robots_smart_builder_enabled'),
            'seo_robots_block_wp_search' => $this->request->post_bool('seo_robots_block_wp_search'),
            'seo_robots_block_slotera_technical' => $this->request->post_bool('seo_robots_block_slotera_technical'),
            'seo_robots_block_tracking_params' => $this->request->post_bool('seo_robots_block_tracking_params'),
            'seo_robots_block_attachment_pages' => $this->request->post_bool('seo_robots_block_attachment_pages'),
            'seo_robots_add_sitemap' => $this->request->post_bool('seo_robots_add_sitemap'),
            'seo_robots_custom_rules' => $this->request->post_textarea('seo_robots_custom_rules'),
            'seo_meta_output_mode' => $this->request->post_key('seo_meta_output_mode', 'auto'),
            'seo_default_robots' => $this->request->post_text('seo_default_robots', 'index,follow'),
            'seo_noindex_empty_categories' => $this->request->post_bool('seo_noindex_empty_categories'),
            'seo_noindex_inactive_items' => $this->request->post_bool('seo_noindex_inactive_items'),
            'seo_title_format' => $this->request->post_text('seo_title_format', '{title} | {site}'),
            'seo_breadcrumbs_enabled' => $this->request->post_bool('seo_breadcrumbs_enabled'),
            'seo_breadcrumbs_show_packages' => $this->request->post_bool('seo_breadcrumbs_show_packages'),
            'seo_breadcrumbs_show_categories' => $this->request->post_bool('seo_breadcrumbs_show_categories'),
            'seo_breadcrumbs_show_local' => $this->request->post_bool('seo_breadcrumbs_show_local'),
        ]);

        $this->redirect('settings');
    }



    public function create_robots_txt(): void
    {
        $this->verify('sltr_create_robots_txt');

        $robots = new RobotsTxtService();
        $exists = $robots->exists();
        $overwrite_confirmed = $this->request->post_bool('confirm_overwrite');
        if ($exists && !$overwrite_confirmed) {
            $this->redirect('settings', 'robots_overwrite_required');
        }

        $mode = $this->request->post_key('robots_mode', 'default');
        $content = $mode === 'custom'
            ? $this->request->post_textarea('robots_custom_content')
            : $robots->default_content();

        if (trim($content) === '') {
            $this->redirect('settings', 'robots_empty');
        }

        if ($exists && !$robots->backup_existing()) {
            $this->redirect('settings', 'robots_backup_failed');
        }

        if (!$robots->write($content)) {
            $this->redirect('settings', 'robots_write_failed');
        }

        $this->redirect('settings', $exists ? 'robots_overwritten' : 'robots_created');
    }

    public function save_templates(): void
    {
        $this->verify('sltr_save_seo_templates');

        $this->settings->update([
            'seo_template_package_title' => substr($this->request->post_text('seo_template_package_title'), 0, 255),
            'seo_template_package_description' => substr($this->request->post_textarea('seo_template_package_description'), 0, 320),
            'seo_template_category_title' => substr($this->request->post_text('seo_template_category_title'), 0, 255),
            'seo_template_category_description' => substr($this->request->post_textarea('seo_template_category_description'), 0, 320),
            'seo_template_location_title' => substr($this->request->post_text('seo_template_location_title'), 0, 255),
            'seo_template_location_description' => substr($this->request->post_textarea('seo_template_location_description'), 0, 320),
            'seo_template_local_package_title' => substr($this->request->post_text('seo_template_local_package_title'), 0, 255),
            'seo_template_local_package_description' => substr($this->request->post_textarea('seo_template_local_package_description'), 0, 320),
        ]);

        $this->redirect('templates', 'templates_saved');
    }

    public function bulk_apply_metadata(): void
    {
        $this->verify('sltr_bulk_apply_seo_metadata');

        $target = $this->request->post_key('bulk_target', 'packages');
        $mode = $this->request->post_key('bulk_mode', 'empty_only');
        $include_descriptions = $this->request->post_bool('bulk_include_descriptions');
        $include_titles = $this->request->post_bool('bulk_include_titles');

        if (!$include_titles && !$include_descriptions) {
            $this->redirect('templates', 'bulk_nothing_selected');
        }

        if (!in_array($target, ['packages', 'categories'], true) || !in_array($mode, ['empty_only', 'overwrite'], true)) {
            $this->redirect('templates', 'bulk_invalid');
        }

        $updated = $target === 'categories'
            ? $this->bulk_apply_category_metadata($mode, $include_titles, $include_descriptions)
            : $this->bulk_apply_package_metadata($mode, $include_titles, $include_descriptions);

        $this->redirect('templates', 'bulk_updated_' . $updated);
    }

    private function bulk_apply_package_metadata(string $mode, bool $titles, bool $descriptions): int
    {
        $service = new \Slotera\Application\Services\SEOTemplateService();
        $settings = $this->settings->all();
        $repo = new PackageRepository();
        $updated = 0;
        foreach ($repo->get_all() as $package) {
            $data = [];
            $context = [
                'package_name' => (string) ($package['title'] ?? ''),
                'site_name' => get_bloginfo('name'),
            ];
            if ($titles && ($mode === 'overwrite' || trim((string) ($package['seo_title'] ?? '')) === '')) {
                $title = $service->render((string) ($settings['seo_template_package_title'] ?? ''), $context);
                if ($title !== '') { $data['seo_title'] = substr($title, 0, 255); }
            }
            if ($descriptions && ($mode === 'overwrite' || trim((string) ($package['seo_description'] ?? '')) === '')) {
                $description = $service->render((string) ($settings['seo_template_package_description'] ?? ''), $context);
                if ($description !== '') { $data['seo_description'] = substr($description, 0, 320); }
            }
            if (!empty($data) && $repo->update((int) ($package['id'] ?? 0), $data)) { $updated++; }
        }
        return $updated;
    }

    private function bulk_apply_category_metadata(string $mode, bool $titles, bool $descriptions): int
    {
        $service = new \Slotera\Application\Services\SEOTemplateService();
        $settings = $this->settings->all();
        $repo = new CategoryRepository();
        $updated = 0;
        foreach ($repo->get_all() as $category) {
            $data = [];
            $context = [
                'category_name' => (string) ($category['name'] ?? ''),
                'site_name' => get_bloginfo('name'),
            ];
            if ($titles && ($mode === 'overwrite' || trim((string) ($category['seo_title'] ?? '')) === '')) {
                $title = $service->render((string) ($settings['seo_template_category_title'] ?? ''), $context);
                if ($title !== '') { $data['seo_title'] = substr($title, 0, 255); }
            }
            if ($descriptions && ($mode === 'overwrite' || trim((string) ($category['seo_description'] ?? '')) === '')) {
                $description = $service->render((string) ($settings['seo_template_category_description'] ?? ''), $context);
                if ($description !== '') { $data['seo_description'] = substr($description, 0, 320); }
            }
            if (!empty($data) && $repo->update((int) ($category['id'] ?? 0), $data)) { $updated++; }
        }
        return $updated;
    }

    public function save_package_seo(): void
    {
        $this->verify('sltr_save_slotera_package_seo');
        $id = $this->request->post_int('id');
        if ($id <= 0) {
            $this->redirect('individual', 'invalid_package');
        }

        (new PackageRepository())->update($id, $this->collect_slotera_seo_data());
        $this->redirect('individual', 'package_seo_saved');
    }

    public function save_category_seo(): void
    {
        $this->verify('sltr_save_slotera_category_seo');
        $id = $this->request->post_int('id');
        if ($id <= 0) {
            $this->redirect('individual', 'invalid_category');
        }

        (new CategoryRepository())->update($id, $this->collect_slotera_seo_data());
        $this->redirect('individual', 'category_seo_saved');
    }


    public function save_local_seo(): void
    {
        $this->verify('sltr_save_slotera_local_seo');

        $package_id = $this->request->post_int('package_id');
        $location_id = $this->request->post_int('location_id');
        if ($package_id <= 0 || $location_id <= 0) {
            $this->redirect('individual', 'invalid_local_seo');
        }

        $faq = $this->collect_local_faq_overrides();
        global $wpdb;
        $relations = Database::package_locations_table();
        $updated = $wpdb->update($relations, [
            'intro_override' => wp_kses_post($this->request->post_html('local_intro_override')),
            'faq_override_json' => (new LocationRepository())->encode_faq($faq),
        ], [
            'package_id' => $package_id,
            'location_id' => $location_id,
        ], ['%s', '%s'], ['%d', '%d']);

        if ($updated === false) {
            $this->redirect('individual', 'local_seo_save_failed');
        }

        $this->redirect('individual', 'local_seo_saved');
    }

    /**
     * Collect the full Slotera SEO payload from the central SEO Settings screen.
     * This is the only writable source for package/category SEO.
     *
     * @return array<string, mixed>
     */
    private function collect_slotera_seo_data(): array
    {
        return [
            'seo_title' => substr($this->request->post_text('seo_title'), 0, 255),
            'seo_site_title_position' => in_array($this->request->post_key('seo_site_title_position', 'right'), ['left', 'right'], true) ? $this->request->post_key('seo_site_title_position', 'right') : 'right',
            'seo_description' => substr($this->request->post_textarea('seo_description'), 0, 320),
            'seo_og_title' => substr($this->request->post_text('seo_og_title'), 0, 255),
            'seo_og_description' => substr($this->request->post_textarea('seo_og_description'), 0, 320),
            'seo_og_image' => esc_url_raw($this->request->post_text('seo_og_image')),
            'seo_canonical' => esc_url_raw($this->request->post_text('seo_canonical')),
            'seo_redirect_301' => esc_url_raw($this->request->post_text('seo_redirect_301')),
            'seo_robots' => $this->normalize_robots($this->request->post_text('seo_robots', 'index,follow')),
            'seo_i18n_json' => $this->collect_multilingual_seo(),
        ];
    }


    /** @return array<int, array{question:string, answer:string}> */
    private function collect_local_faq_overrides(): array
    {
        $raw = isset($_POST['local_faq_override']) && is_array($_POST['local_faq_override']) ? wp_unslash($_POST['local_faq_override']) : [];
        $collected = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $question = sanitize_text_field((string) ($item['question'] ?? ''));
            $answer = wp_kses_post((string) ($item['answer'] ?? ''));
            if ($question === '' && trim(wp_strip_all_tags($answer)) === '') {
                continue;
            }
            $collected[] = ['question' => $question, 'answer' => $answer];
        }
        return $collected;
    }

    private function normalize_robots(string $robots): string
    {
        $allowed = ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'];
        return in_array($robots, $allowed, true) ? $robots : 'index,follow';
    }

    private function collect_multilingual_seo(): string
    {
        $raw = isset($_POST['seo_i18n']) && is_array($_POST['seo_i18n']) ? wp_unslash($_POST['seo_i18n']) : [];
        $clean = [];

        foreach ($raw as $locale => $fields) {
            $locale = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $locale);
            if ($locale === '' || !is_array($fields)) {
                continue;
            }

            $entry = [
                'seo_title' => sanitize_text_field((string) ($fields['seo_title'] ?? '')),
                'seo_description' => sanitize_textarea_field((string) ($fields['seo_description'] ?? '')),
                'seo_og_title' => sanitize_text_field((string) ($fields['seo_og_title'] ?? '')),
                'seo_og_description' => sanitize_textarea_field((string) ($fields['seo_og_description'] ?? '')),
                'seo_og_image' => esc_url_raw((string) ($fields['seo_og_image'] ?? '')),
                'seo_canonical' => esc_url_raw((string) ($fields['seo_canonical'] ?? '')),
            ];

            $entry = array_filter($entry, static fn($value) => is_string($value) && trim($value) !== '');
            if (!empty($entry)) {
                $clean[$locale] = $entry;
            }
        }

        return empty($clean) ? '' : (string) wp_json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function save_wp_page_seo(): void
    {
        $this->verify('sltr_save_wp_page_seo');

        $post_id = $this->request->post_int('post_id');
        $post = $post_id > 0 ? get_post($post_id) : null;
        if (!$post || $post->post_type !== 'page') {
            $this->redirect('individual', 'invalid_page');
        }

        if (count($this->seo->detected_external_plugins()) > 0) {
            update_post_meta($post_id, '_sltr_wp_page_seo_enabled', 0);
            $this->redirect('individual', 'blocked_external_seo');
        }

        update_post_meta($post_id, '_sltr_wp_page_seo_enabled', $this->request->post_bool('enabled') ? 1 : 0);
        update_post_meta($post_id, '_sltr_wp_page_seo_title', substr($this->request->post_text('seo_title'), 0, 255));
        update_post_meta($post_id, '_sltr_wp_page_seo_description', substr($this->request->post_textarea('seo_description'), 0, 320));
        update_post_meta($post_id, '_sltr_wp_page_seo_og_title', substr($this->request->post_text('seo_og_title'), 0, 255));
        update_post_meta($post_id, '_sltr_wp_page_seo_og_description', substr($this->request->post_textarea('seo_og_description'), 0, 320));
        update_post_meta($post_id, '_sltr_wp_page_seo_og_image', esc_url_raw($this->request->post_text('seo_og_image')));
        update_post_meta($post_id, '_sltr_wp_page_seo_noindex', $this->request->post_bool('seo_noindex') ? 1 : 0);
        update_post_meta($post_id, '_sltr_wp_page_seo_redirect_301', esc_url_raw($this->request->post_text('seo_redirect_301')));
        update_post_meta($post_id, '_sltr_wp_page_seo_canonical', esc_url_raw($this->request->post_text('seo_canonical')));

        $this->redirect('individual', 'saved');
    }

    private function verify(string $action): void
    {
        $this->request->require_admin(Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce($action);
    }

    private function redirect(string $tab, string $message = 'saved'): void
    {
        wp_safe_redirect(add_query_arg([
            'page' => 'slotera-settings', 'section' => 'seo',
            'tab' => $tab,
            'sltr_message' => $message,
        ], admin_url('admin.php')));
        exit;
    }
}
