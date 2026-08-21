<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\TranslationRegistry;
use Slotera\Application\Services\TranslationService;

if (!defined('ABSPATH')) {
    exit;
}

final class TranslationController
{
    private TranslationService $translations;
    private RequestValidator $request;

    public function __construct(?TranslationService $translations = null, ?RequestValidator $request = null)
    {
        $this->translations = $translations ?? new TranslationService();
        $this->request = $request ?? new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_translations', [$this, 'save']);
        add_action('admin_post_sltr_save_translation_locale', [$this, 'save_locale_setting']);
    }

    public function save(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce('sltr_save_translations');

        $locale = $this->request->post_text('locale', 'en_US');
        $group = $this->request->post_key('group', 'frontend');
        $values = $this->request->post_array('translations');
        $this->translations->save_locale($group, $locale, is_array($values) ? $values : []);
        $search = $this->request->post_text('translation_search', '');
        $section = $this->request->post_key('translation_section', 'all');
        $workspace_filter = $this->request->post_key('workspace_filter', 'all');
        if (!in_array($workspace_filter, ['all', 'missing', 'translated', 'duplicates', 'quality', 'overrides', 'unsaved'], true)) {
            $workspace_filter = 'all';
        }

        $redirect_args = [
            'page' => 'slotera-translations',
            'locale' => rawurlencode($locale),
            'group' => $group,
            'updated' => '1',
            'translation_section' => $section,
            'workspace_filter' => $workspace_filter === 'unsaved' ? 'overrides' : $workspace_filter,
        ];
        if ($search !== '') {
            $redirect_args['translation_search'] = $search;
        }

        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')) . '#sltr-translation-workspace');
        exit;
    }

    public function save_locale_setting(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_SETTINGS);
        $this->request->verify_admin_nonce('sltr_save_translation_locale');

        $group = $this->request->post_key('group', 'frontend');
        $locale = $this->request->post_text('locale', TranslationRegistry::default_locale());
        $this->translations->save_group_locale($group, $locale);

        wp_safe_redirect(add_query_arg([
            'page' => 'slotera-translations',
            'group' => $group,
            'locale' => rawurlencode($locale),
            'locale_saved' => '1',
        ], admin_url('admin.php')));
        exit;
    }


}
