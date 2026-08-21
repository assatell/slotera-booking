<?php
if (!defined('ABSPATH')) { exit; }
$sltr_get = wp_unslash($_GET);
?>
<div class="wrap sltr-admin sltr-translations-page">
    <h1><?php esc_html_e('Translations', 'slotera-booking'); ?></h1>

    <?php
    $dashboard_sections = ['frontend_ui' => 'Frontend', 'email_settings' => 'Settings', 'email_templates' => 'Templates'];
    $dashboard_expected_items = ['frontend_ui' => 346, 'email_settings' => 151, 'email_templates' => 109];
    $dashboard_locale_aliases = ['et' => 'et_EE', 'fi' => 'fi_FI', 'el' => 'el_GR', 'hr' => 'hr_HR', 'lv' => 'lv_LV', 'nb_NO' => 'no_NO', 'nb' => 'no_NO'];
    $dashboard_reports = [];
    foreach ($dashboard_sections as $dashboard_section_key => $dashboard_section_label) {
        foreach ((array) ($translation_report[$dashboard_section_key]['locale_reports'] ?? []) as $dashboard_raw_locale => $dashboard_locale_report) {
            $dashboard_locale = $dashboard_locale_aliases[(string) $dashboard_raw_locale] ?? (string) $dashboard_raw_locale;
            $dashboard_reports[$dashboard_locale][$dashboard_section_key] = (array) $dashboard_locale_report;
        }
    }
    $dashboard_locales = array_keys($dashboard_reports);
    sort($dashboard_locales);
    $dashboard_freeze_service = new \Slotera\Application\Services\TranslationFreezeService();
    $dashboard_frozen_locales = $dashboard_freeze_service->frozenLocales();
    $dashboard_baseline_file = SLTR_PLUGIN_DIR . 'includes/Application/config/translation-freeze.php';
    if (!is_file($dashboard_baseline_file)) {
        $dashboard_baseline_file = SLTR_PLUGIN_DIR . 'includes/config/translation-freeze.php';
    }
    $dashboard_baseline_updated = is_file($dashboard_baseline_file) ? gmdate('Y-m-d H:i', (int) filemtime($dashboard_baseline_file)) . ' UTC' : '—';
    ?>
    <section class="sltr-panel sltr-localization-dashboard">
        <div class="sltr-scanner-header">
            <div>
                <h2>Localization Dashboard</h2>
                <p class="description">Runtime readiness is shown separately from the complete gettext catalog backlog.</p>
            </div>
            <span class="sltr-badge sltr-badge-info">Scanner <?php echo esc_html(\Slotera\Application\Services\TranslationQualityScanner::VERSION); ?></span>
        </div>
        <div class="sltr-runtime-coverage">
            <strong>Runtime Coverage</strong>
            <span>Frontend UI 346</span>
            <span>Email Settings 151</span>
            <span>Email Templates 109</span>
            <span>Total 606 runtime items per locale</span>
        </div>
        <p class="description sltr-confidence-method"><strong>Confidence formula:</strong> starts from runtime Coverage, then subtracts active quality penalties: 2 points per critical issue, 0.25 per warning, 0.25 per unresolved duplicate-context review, 2 points when Freeze is invalid, and 20 points for a data mismatch. The score is advisory; <strong>Ready</strong> remains the release-blocking decision.</p>
        <div class="sltr-dashboard-table-wrap">
            <table class="widefat striped sltr-dashboard-table">
                <thead><tr><th>Locale</th><th>Coverage</th><th><abbr title="Confidence is an advisory score: runtime coverage minus active quality penalties (critical issues, warnings, duplicate-context reviews, Freeze state, and data mismatch). Ready remains the release-blocking status.">Confidence</abbr></th><th>Translated</th><th>Missing</th><th>Frontend UI</th><th>Email Settings</th><th>Email Templates</th><th>Warnings</th><th>Freeze</th><th>Scanner</th><th>Baseline</th><th>Baseline updated</th><th>Ready</th><th>Ready reason</th></tr></thead>
                <tbody>
                <?php foreach ($dashboard_locales as $dashboard_locale) :
                    $dashboard_source = 0; $dashboard_translated = 0; $dashboard_missing = 0; $dashboard_warnings = 0; $dashboard_notices = 0; $dashboard_context_reviews = 0; $dashboard_critical = 0; $dashboard_all_sections = true; $dashboard_section_progress = [];
                    foreach ($dashboard_sections as $dashboard_section_key => $dashboard_section_label) {
                        $dashboard_locale_report = (array) ($dashboard_reports[$dashboard_locale][$dashboard_section_key] ?? []);
                        $dashboard_section_source = (int) ($dashboard_expected_items[$dashboard_section_key] ?? 0);
                        $dashboard_source += $dashboard_section_source;
                        if ($dashboard_locale_report === []) { $dashboard_all_sections = false; $dashboard_section_progress[$dashboard_section_key] = '0/' . $dashboard_section_source; continue; }
                        $dashboard_section_translated = min($dashboard_section_source, max(0, (int) ($dashboard_locale_report['totals']['translated_strings'] ?? 0)));
                        $dashboard_translated += $dashboard_section_translated;
                        $dashboard_missing += max(0, $dashboard_section_source - $dashboard_section_translated);
                        $dashboard_warnings += (int) ($dashboard_locale_report['severity']['warning'] ?? 0);
                        $dashboard_notices += (int) ($dashboard_locale_report['severity']['notice'] ?? 0);
                        $dashboard_context_reviews += (int) ($dashboard_locale_report['issues']['duplicate_english_msgid'] ?? 0);
                        $dashboard_critical += (int) ($dashboard_locale_report['severity']['critical'] ?? 0);
                        $dashboard_section_progress[$dashboard_section_key] = $dashboard_section_translated . '/' . $dashboard_section_source;
                    }
                    // Runtime coverage and Missing must always be derived from the same 606-item scope.
                    // Scanner issue counts may include section/matrix diagnostics and therefore are not a safe
                    // source for the dashboard's arithmetic Missing column.
                    $dashboard_missing = max(0, $dashboard_source - $dashboard_translated);
                    $dashboard_coverage = $dashboard_source > 0 ? round(($dashboard_translated / $dashboard_source) * 100, 1) : 0;
                    $dashboard_data_consistent = ($dashboard_translated + $dashboard_missing) === $dashboard_source;
                    $dashboard_is_frozen = in_array($dashboard_locale, $dashboard_frozen_locales, true);
                    $dashboard_freeze_ok = $dashboard_is_frozen;
                    foreach (['frontend', 'emails', 'email_templates'] as $dashboard_freeze_section) {
                        $dashboard_freeze_result = $dashboard_freeze_service->verify($dashboard_freeze_section, $dashboard_locale);
                        if (!($dashboard_freeze_result['frozen'] ?? false) || !($dashboard_freeze_result['valid'] ?? false)) { $dashboard_freeze_ok = false; }
                    }
                    $dashboard_ready = $dashboard_all_sections && $dashboard_data_consistent && $dashboard_coverage >= 100 && $dashboard_missing === 0 && $dashboard_critical === 0 && $dashboard_warnings === 0 && $dashboard_freeze_ok;
                    $dashboard_scanner_ok = $dashboard_data_consistent && $dashboard_critical === 0 && $dashboard_warnings === 0;
                    // Confidence is informative and does not replace the release-blocking Ready status.
                    // Missing runtime content is already represented by Coverage, so issue penalties must not
                    // introduce a hidden baseline deduction. A clean, frozen, 100%-covered locale is invariantly 100%.
                    $dashboard_confidence_penalty = ($dashboard_critical * 2.0) + ($dashboard_warnings * 0.25) + ($dashboard_context_reviews * 0.25);
                    if (!$dashboard_freeze_ok) { $dashboard_confidence_penalty += 2.0; }
                    if (!$dashboard_data_consistent) { $dashboard_confidence_penalty += 20.0; }
                    $dashboard_confidence = max(0.0, min(100.0, $dashboard_coverage - $dashboard_confidence_penalty));
                    if ($dashboard_ready) { $dashboard_confidence = 100.0; }
                    $dashboard_ready_reasons = [];
                    if (!$dashboard_is_frozen) { $dashboard_ready_reasons[] = 'Not frozen'; } elseif (!$dashboard_freeze_ok) { $dashboard_ready_reasons[] = 'Freeze drift'; }
                    if (!$dashboard_data_consistent) { $dashboard_ready_reasons[] = 'Data mismatch'; }
                    if (!$dashboard_all_sections) { $dashboard_ready_reasons[] = 'Incomplete section data'; }
                    if ($dashboard_missing > 0) { $dashboard_ready_reasons[] = $dashboard_missing . ' missing'; }
                    if ($dashboard_critical > 0) { $dashboard_ready_reasons[] = $dashboard_critical . ' critical'; }
                    if ($dashboard_warnings > 0) { $dashboard_ready_reasons[] = $dashboard_warnings . ' ' . ($dashboard_warnings === 1 ? 'warning' : 'warnings'); }
                    $dashboard_ready_reason = $dashboard_ready ? 'Ready' : implode(', ', $dashboard_ready_reasons);
                    $dashboard_warning_url = add_query_arg(['page' => 'slotera-translations', 'group' => $active_group, 'scanner_locale' => $dashboard_locale], admin_url('admin.php')) . '#sltr-scanner-issues';
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($dashboard_locale); ?></strong></td>
                        <td><?php echo esc_html(number_format_i18n($dashboard_coverage, 1)); ?>%</td>
                        <td><strong title="<?php echo esc_attr(sprintf('Coverage %.1f%% − penalties %.1f = Confidence %.1f%%. Ready is evaluated separately.', $dashboard_coverage, $dashboard_confidence_penalty, $dashboard_confidence)); ?>"><?php echo esc_html(number_format_i18n($dashboard_confidence, 1)); ?>%</strong></td>
                        <td><strong><?php echo esc_html($dashboard_translated . ' / ' . $dashboard_source); ?></strong></td>
                        <td><?php echo esc_html((string) $dashboard_missing); ?></td>
                        <td><?php echo esc_html($dashboard_section_progress['frontend_ui'] ?? '0/0'); ?></td>
                        <td><?php echo esc_html($dashboard_section_progress['email_settings'] ?? '0/0'); ?></td>
                        <td><?php echo esc_html($dashboard_section_progress['email_templates'] ?? '0/0'); ?></td>
                        <td><?php if ($dashboard_warnings > 0) : ?><a class="sltr-dashboard-warning-link" href="<?php echo esc_url($dashboard_warning_url); ?>" aria-label="<?php echo esc_attr('View ' . $dashboard_warnings . ' warnings for ' . $dashboard_locale); ?>"><?php echo esc_html((string) $dashboard_warnings); ?></a><?php else : ?>0<?php endif; ?></td>
                        <td><span class="sltr-badge sltr-badge-<?php echo esc_attr($dashboard_freeze_ok ? 'ok' : 'warning'); ?>"><?php echo esc_html($dashboard_freeze_ok ? 'Frozen' : 'Not frozen'); ?></span></td>
                        <td><span class="sltr-badge sltr-badge-<?php echo esc_attr($dashboard_scanner_ok ? 'ok' : 'warning'); ?>"><?php echo esc_html(!$dashboard_data_consistent ? 'Data mismatch' : ($dashboard_scanner_ok ? 'PASS' : 'Review')); ?></span></td>
                        <td><?php echo esc_html($dashboard_freeze_ok ? 'Valid' : 'Pending'); ?></td>
                        <td><?php echo esc_html($dashboard_baseline_updated); ?></td>
                        <td><span class="sltr-badge sltr-badge-<?php echo esc_attr($dashboard_ready ? 'ok' : 'warning'); ?>"><?php echo esc_html($dashboard_ready ? 'Yes' : 'No'); ?></span></td>
                        <td class="sltr-dashboard-ready-reason"><?php echo esc_html($dashboard_ready_reason); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php
    $dashboard_ready_count = 0;
    $dashboard_total_translated = 0;
    $dashboard_total_runtime = count($dashboard_locales) * array_sum($dashboard_expected_items);
    $dashboard_total_warnings = 0;
    foreach ($dashboard_locales as $dashboard_summary_locale) {
        $summary_ready = true;
        foreach ($dashboard_sections as $dashboard_summary_section => $_label) {
            $summary_report = (array) ($dashboard_reports[$dashboard_summary_locale][$dashboard_summary_section] ?? []);
            $summary_expected = (int) $dashboard_expected_items[$dashboard_summary_section];
            $summary_translated = min($summary_expected, max(0, (int) ($summary_report['totals']['translated_strings'] ?? 0)));
            $dashboard_total_translated += $summary_translated;
            $dashboard_total_warnings += (int) ($summary_report['severity']['warning'] ?? 0);
            if ($summary_translated !== $summary_expected || (int) ($summary_report['severity']['critical'] ?? 0) > 0 || (int) ($summary_report['severity']['warning'] ?? 0) > 0) { $summary_ready = false; }
        }
        if (!in_array($dashboard_summary_locale, $dashboard_frozen_locales, true)) { $summary_ready = false; }
        if ($summary_ready) { $dashboard_ready_count++; }
    }
    ?>
    <section class="sltr-panel sltr-regression-summary">
        <h2>Localization Release Snapshot</h2>
        <p class="description">Current runtime localization snapshot for this release. Translation catalogs and freeze hashes were not intentionally changed in 1.0.674.</p>
        <div class="sltr-regression-grid">
            <span><strong><?php echo esc_html((string) $dashboard_ready_count); ?></strong> ready locales</span>
            <span><strong><?php echo esc_html($dashboard_total_translated . ' / ' . $dashboard_total_runtime); ?></strong> runtime items translated</span>
            <span><strong><?php echo esc_html((string) ($dashboard_total_runtime - $dashboard_total_translated)); ?></strong> runtime items missing</span>
            <span><strong><?php echo esc_html((string) $dashboard_total_warnings); ?></strong> warnings</span>
            <span><strong>0</strong> intentional translation removals</span>
            <span><strong>Scanner <?php echo esc_html(\Slotera\Application\Services\TranslationQualityScanner::VERSION); ?></strong> stabilization schema</span>
        </div>
    </section>

    <section id="sltr-scanner-issues" class="sltr-panel sltr-health-panel sltr-scanner-panel">
        <div class="sltr-scanner-header">
            <div>
                <h2>Translation Quality Scanner</h2>
                <p class="description">Release readiness for active locales. Catalog backlog is tracked separately and does not block the active site language. <span class="sltr-scanner-schema">Scanner schema <?php echo esc_html(\Slotera\Application\Services\TranslationQualityScanner::VERSION); ?> · Plugin <?php echo esc_html(SLTR_VERSION); ?></span></p>
            </div>
            <div class="sltr-scanner-actions">
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=slotera-translations&group=' . $active_group)); ?>">Rescan</a>
                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=slotera-translations&group=' . $active_group . '&sltr_translation_export=json'), 'sltr_translation_export')); ?>">Export JSON</a>
                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=slotera-translations&group=' . $active_group . '&sltr_translation_export=csv'), 'sltr_translation_export')); ?>">Export CSV</a>
            </div>
        </div>

        <?php
        $translation_area_labels = ['frontend_ui' => 'Frontend UI', 'email_settings' => 'Email Settings', 'email_templates' => 'Email Templates'];
        $scanner_status_labels = ['ready' => 'Ready', 'in_progress' => 'In progress', 'needs_attention' => 'Needs attention', 'blocked' => 'Blocked'];
        $scanner_locale_filter = sanitize_text_field((string) ($sltr_get['scanner_locale'] ?? ''));
        if ($scanner_locale_filter !== '') {
            echo '<p class="sltr-scanner-filter"><strong>Issue filter:</strong> ' . esc_html($scanner_locale_filter) . ' <a href="' . esc_url(admin_url('admin.php?page=slotera-translations&group=' . $active_group) . '#sltr-scanner-issues') . '">Clear</a></p>';
        }
        ?>
        <div class="sltr-scanner-grid">
            <?php foreach (($translation_report ?? []) as $area => $report) :
                $scanner_status = (string) ($report['status'] ?? 'in_progress');
                $badge_status = $scanner_status === 'blocked' ? 'critical' : (in_array($scanner_status, ['in_progress', 'needs_attention'], true) ? 'warning' : 'ok');
                $issues = (array) ($report['issues'] ?? []);
                $critical = (int) ($report['critical'] ?? ($report['severity']['critical'] ?? 0));
                $warnings = (int) ($report['warnings'] ?? ($report['severity']['warning'] ?? 0));
                $notices = (int) ($report['notices'] ?? ($report['severity']['notice'] ?? 0));
                $quality_lint_count = (int) ($issues['quality_lint'] ?? 0);
                $ux_linguistic_count = (int) ($issues['ux_linguistic'] ?? 0);
                $freeze_drift_count = (int) ($issues['freeze_drift'] ?? 0);
                $catalog_backlog = (int) ($report['catalog']['backlog_issues'] ?? 0);
                $active_locales = (array) ($report['active_locales'] ?? []);
                $active_details = (array) ($report['active_details'] ?? $report['details'] ?? []);
                if ($scanner_locale_filter !== '') {
                    $active_details = array_values(array_filter($active_details, static function (array $detail) use ($scanner_locale_filter, $dashboard_locale_aliases): bool {
                        $detail_locale = (string) ($detail['locale'] ?? '');
                        $detail_locale = $dashboard_locale_aliases[$detail_locale] ?? $detail_locale;
                        return $detail_locale === $scanner_locale_filter;
                    }));
                }
                $priority_rows = array_values(array_filter($active_details, static fn(array $detail): bool => in_array((string) ($detail['severity'] ?? ''), ['critical', 'warning'], true)));
                $notice_rows = array_values(array_filter($active_details, static fn(array $detail): bool => (string) ($detail['severity'] ?? '') === 'notice'));
                $rows = array_slice(array_merge($priority_rows, array_slice($notice_rows, 0, 20)), 0, 200);
            ?>
                <article class="sltr-scanner-card sltr-scanner-card-<?php echo esc_attr($badge_status); ?>">
                    <div class="sltr-scanner-card-head">
                        <h3><?php echo esc_html($translation_area_labels[(string) $area] ?? ucwords(str_replace('_', ' ', (string) $area))); ?></h3>
                        <span class="sltr-badge sltr-badge-<?php echo esc_attr($badge_status); ?>"><?php echo esc_html($scanner_status_labels[$scanner_status] ?? ucfirst(str_replace('_', ' ', $scanner_status))); ?></span>
                    </div>
                    <div class="sltr-scanner-score-row">
                        <div><strong><?php echo esc_html((string) ($report['translation_completion'] ?? 100)); ?>%</strong><span>Completion</span></div>
                        <div><strong><?php echo esc_html((string) ($report['quality'] ?? 100)); ?>%</strong><span>Quality</span></div>
                        <div><strong><?php echo esc_html((string) ($report['release_readiness'] ?? 100)); ?>%</strong><span>Readiness</span></div>
                    </div>
                    <div class="sltr-scanner-meta">
                        <span><?php echo esc_html(sprintf('%d items', (int) ($report['items'] ?? 0))); ?></span>
                        <span><?php echo esc_html($active_locales === [] ? 'English source active' : implode(', ', $active_locales)); ?></span>
                    </div>
                    <div class="sltr-scanner-issue-row">
                        <span class="sltr-scanner-count is-critical"><strong><?php echo esc_html((string) $critical); ?></strong> critical</span>
                        <span class="sltr-scanner-count is-warning"><strong><?php echo esc_html((string) $warnings); ?></strong> warnings</span>
                        <span class="sltr-scanner-count is-notice"><strong><?php echo esc_html((string) $notices); ?></strong> notices</span>
                        <span class="sltr-scanner-count is-quality"><strong><?php echo esc_html((string) $quality_lint_count); ?></strong> quality lint</span>
                        <span class="sltr-scanner-count is-quality"><strong><?php echo esc_html((string) $ux_linguistic_count); ?></strong> UX/linguistic</span>
                        <span class="sltr-scanner-count is-critical"><strong><?php echo esc_html((string) $freeze_drift_count); ?></strong> freeze drift</span>
                    </div>
                    <p class="sltr-scanner-backlog">Catalog backlog: <strong><?php echo esc_html(number_format_i18n($catalog_backlog)); ?></strong> untranslated gettext entries <span class="sltr-badge sltr-badge-info">Non-blocking</span></p>

                    <details class="sltr-scanner-details">
                        <summary><?php echo esc_html(sprintf(_n('View %d active issue', 'View %d active issues', count($active_details), 'slotera-booking'), count($active_details))); ?></summary>
                        <div class="sltr-scanner-detail-table-wrap">
                            <table class="widefat striped sltr-scanner-detail-table">
                                <thead><tr><th>Locale</th><th>Key</th><th>Severity</th><th>Issue</th><th>Value</th><th>Suggestion</th></tr></thead>
                                <tbody>
                                <?php if ($rows === []) : ?><tr><td colspan="6">No active issues found.</td></tr><?php endif; ?>
                                <?php foreach ($rows as $detail) : ?>
                                    <tr>
                                        <td><?php echo esc_html((string) ($detail['locale'] ?? '')); ?></td>
                                        <td><code><?php echo esc_html((string) ($detail['key'] ?? '')); ?></code></td>
                                        <td><span class="sltr-badge sltr-badge-<?php echo esc_attr((string) (($detail['severity'] ?? '') === 'notice' ? 'info' : ($detail['severity'] ?? 'info'))); ?>"><?php echo esc_html((string) ($detail['severity'] ?? '')); ?></span></td>
                                        <td><?php echo esc_html((string) ($detail['issue'] ?? '')); ?></td>
                                        <td class="sltr-scanner-value"><?php echo esc_html((string) ($detail['value'] ?? '')); ?></td>
                                        <td class="sltr-scanner-suggestion"><?php echo esc_html((string) ($detail['context']['suggestion'] ?? '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($notice_rows) > 20) : ?><tr><td colspan="6"><em><?php echo esc_html(sprintf('%d additional notices remain available in JSON/CSV export.', count($notice_rows) - 20)); ?></em></td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="sltr-i18n-info-box" style="margin:16px 0;padding:14px 16px;background:#f0f6fc;border-left:4px solid #2271b1;border-radius:6px;max-width:980px;">
        <p style="margin:0;font-size:14px;line-height:1.55;">Frontend UI and Email settings remain fully localized and can be reviewed or edited here. Slotera Admin UI is English-only and is no longer part of the translation editor.</p>
    </div>

    <?php if (!empty($sltr_get['updated'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Translations saved.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($sltr_get['locale_saved'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Default language for this section saved.', 'slotera-booking'); ?></p></div>
    <?php endif; ?>
    <div class="notice notice-info inline"><p><?php echo esc_html('Translation registry keys remain frozen for release safety. You can still edit translation values manually: change a field, save it, and Slotera stores it as a protected manual override until you clear it or reset it to the bundled text.'); ?></p></div>

    <h2 id="sltr-translation-workspace" class="nav-tab-wrapper" style="scroll-margin-top:32px;">
        <?php foreach ($groups as $group_key => $group_label) : ?>
            <?php $url = add_query_arg(['page' => 'slotera-translations', 'group' => $group_key], admin_url('admin.php')) . '#sltr-translation-workspace'; ?>
            <a class="nav-tab <?php echo $active_group === $group_key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url($url); ?>"><?php echo esc_html($group_label); ?></a>
        <?php endforeach; ?>
    </h2>

    <?php if ($active_group === 'email_templates') : ?>
        <?php require SLTR_PLUGIN_DIR . 'includes/Admin/Views/translations-email-templates.php'; ?>
    <?php else : ?>

    <div class="sltr-translation-context-box" style="margin:16px 0;padding:14px 16px;background:#fff;border:1px solid #dcdcde;border-radius:6px;max-width:980px;">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <?php wp_nonce_field('sltr_save_translation_locale'); ?>
            <input type="hidden" name="action" value="sltr_save_translation_locale">
            <input type="hidden" name="group" value="<?php echo esc_attr($active_group); ?>">
            <label for="sltr-context-locale"><strong><?php esc_html_e('Default language for this section', 'slotera-booking'); ?></strong></label>
            <select id="sltr-context-locale" name="locale">
                <?php foreach ($languages as $code => $label) : ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected(($context_locales[$active_group] ?? 'en_US'), $code); ?>><?php echo esc_html($label . ' (' . $code . ')'); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button"><?php esc_html_e('Save default language', 'slotera-booking'); ?></button>
            <span class="description"><?php esc_html_e('Frontend UI and Emails follow the WordPress site locale by default. You can override each section here.', 'slotera-booking'); ?></span>
        </form>
    </div>


    <form id="sltr-translation-editor" method="get" action="<?php echo esc_url(admin_url('admin.php') . '#sltr-translation-editor'); ?>" class="sltr-language-switcher" style="margin:16px 0;display:flex;gap:10px;align-items:center;flex-wrap:wrap;scroll-margin-top:32px;">
        <input type="hidden" name="page" value="slotera-translations">
        <input type="hidden" name="group" value="<?php echo esc_attr($active_group); ?>">
        <label for="sltr-translation-locale"><strong><?php esc_html_e('Edit translations for language', 'slotera-booking'); ?></strong></label>
        <select id="sltr-translation-locale" name="locale" onchange="this.form.submit()">
            <?php foreach ($languages as $code => $label) : ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($locale, $code); ?>><?php echo esc_html($label . ' (' . $code . ')'); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="sltr-translation-section"><strong><?php esc_html_e('Group', 'slotera-booking'); ?></strong></label>
        <select id="sltr-translation-section" name="translation_section" onchange="this.form.submit()">
            <?php foreach ($translation_sections as $section_key => $section_label) : ?>
                <option value="<?php echo esc_attr($section_key); ?>" <?php selected($active_section, $section_key); ?>><?php echo esc_html($section_label); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="sltr-translation-search" class="screen-reader-text"><?php esc_html_e('Search translations', 'slotera-booking'); ?></label>
        <input id="sltr-translation-search" type="search" name="translation_search" class="regular-text" value="<?php echo esc_attr($search ?? ''); ?>" placeholder="<?php esc_attr_e('Search word or phrase', 'slotera-booking'); ?>">
        <button type="submit" class="button"><?php esc_html_e('Search', 'slotera-booking'); ?></button>
        <?php if (!empty($search)) : ?>
            <a class="button" href="<?php echo esc_url(add_query_arg(['page' => 'slotera-translations', 'group' => $active_group, 'locale' => $locale, 'translation_section' => $active_section], admin_url('admin.php')) . '#sltr-translation-editor'); ?>"><?php esc_html_e('Clear', 'slotera-booking'); ?></a>
            <span class="description"><?php echo esc_html(sprintf(__('Found %1$d of %2$d strings.', 'slotera-booking'), count($strings), $section_total ?? count($strings))); ?></span>
        <?php endif; ?>
        <noscript><button class="button"><?php esc_html_e('Change language', 'slotera-booking'); ?></button></noscript>
    </form>


    <div class="sltr-translation-tools" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin:16px 0;max-width:1180px;">
        <div class="postbox" style="margin:0;">
            <div class="postbox-header"><h2><?php esc_html_e('Duplicate strings', 'slotera-booking'); ?></h2></div>
            <div class="inside">
                <p><?php echo esc_html(sprintf(__('Detected %d duplicate English source strings across the translation registry.', 'slotera-booking'), count($duplicate_defaults ?? []))); ?></p>
                <?php if (!empty($duplicate_defaults)) : ?>
                    <details>
                        <summary><?php esc_html_e('Show duplicates', 'slotera-booking'); ?></summary>
                        <ul style="max-height:180px;overflow:auto;margin-top:8px;">
                            <?php $shown = 0; foreach ($duplicate_defaults as $duplicate_text => $duplicate_list) : if ($shown++ >= 20) { break; } ?>
                                <li><code><?php echo esc_html($duplicate_text); ?></code> — <?php echo esc_html(count($duplicate_list)); ?> <?php esc_html_e('keys', 'slotera-booking'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
                <p class="description"><?php esc_html_e('Rows with duplicate English source strings are marked in the table, so you can keep wording consistent.', 'slotera-booking'); ?></p>
            </div>
        </div>
    </div>

    <?php
    $workspace_total = count($strings);
    $workspace_translated = 0;
    $workspace_missing = 0;
    $workspace_duplicates = 0;
    $workspace_quality = count($quality_lint_results ?? []);
    $workspace_overrides = 0;
    foreach ($strings as $workspace_key => $workspace_meta) {
        $workspace_value = trim((string) ($values[$workspace_key] ?? ''));
        if ($workspace_value === '') {
            $workspace_missing++;
        } else {
            $workspace_translated++;
        }
        if (!empty($duplicate_keys[$workspace_key])) {
            $workspace_duplicates++;
        }
        if (!empty(($manual_override_keys ?? [])[$workspace_key])) {
            $workspace_overrides++;
        }
    }
    $workspace_progress = $workspace_total > 0 ? round(($workspace_translated / $workspace_total) * 100, 1) : 100;
    ?>
    <section id="sltr-translation-workspace" class="sltr-translation-workspace" data-sltr-translation-workspace data-initial-filter="<?php echo esc_attr($workspace_filter ?? 'all'); ?>">
        <div class="sltr-workspace-summary">
            <div>
                <h2><?php esc_html_e('Translation workspace', 'slotera-booking'); ?></h2>
                <p class="description"><?php echo esc_html(sprintf(__('%1$s · %2$d strings in this view', 'slotera-booking'), $languages[$locale], $workspace_total)); ?></p>
            </div>
            <div class="sltr-workspace-progress" aria-label="<?php esc_attr_e('Translation progress', 'slotera-booking'); ?>">
                <strong><?php echo esc_html((string) $workspace_progress); ?>%</strong>
                <span><i style="width:<?php echo esc_attr((string) $workspace_progress); ?>%"></i></span>
            </div>
        </div>
        <div class="sltr-workspace-toolbar" role="toolbar" aria-label="<?php esc_attr_e('Translation filters', 'slotera-booking'); ?>">
            <button type="button" class="button<?php echo ($workspace_filter ?? 'all') === 'all' ? ' is-active' : ''; ?>" data-sltr-filter="all"><?php echo esc_html(sprintf(__('All (%d)', 'slotera-booking'), $workspace_total)); ?></button>
            <button type="button" class="button<?php echo ($workspace_filter ?? 'all') === 'missing' ? ' is-active' : ''; ?>" data-sltr-filter="missing"<?php echo $workspace_missing === 0 ? ' disabled aria-disabled="true" title="' . esc_attr__('No missing translations', 'slotera-booking') . '"' : ''; ?>><?php echo esc_html(sprintf(__('Missing (%d)', 'slotera-booking'), $workspace_missing)); ?></button>
            <button type="button" class="button<?php echo ($workspace_filter ?? 'all') === 'translated' ? ' is-active' : ''; ?>" data-sltr-filter="translated"<?php echo $workspace_translated === 0 ? ' disabled aria-disabled="true" title="' . esc_attr__('No translated strings', 'slotera-booking') . '"' : ''; ?>><?php echo esc_html(sprintf(__('Translated (%d)', 'slotera-booking'), $workspace_translated)); ?></button>
            <button type="button" class="button<?php echo ($workspace_filter ?? 'all') === 'duplicates' ? ' is-active' : ''; ?>" data-sltr-filter="duplicates"<?php echo $workspace_duplicates === 0 ? ' disabled aria-disabled="true" title="' . esc_attr__('No duplicate source strings', 'slotera-booking') . '"' : ''; ?>><?php echo esc_html(sprintf(__('Duplicates (%d)', 'slotera-booking'), $workspace_duplicates)); ?></button>
            <button type="button" class="button sltr-quality-filter<?php echo ($workspace_filter ?? 'all') === 'quality' ? ' is-active' : ''; ?>" data-sltr-filter="quality"<?php echo $workspace_quality === 0 ? ' disabled aria-disabled="true" title="' . esc_attr__('No quality issues', 'slotera-booking') . '"' : ''; ?>><?php echo esc_html(sprintf(__('Quality (%d)', 'slotera-booking'), $workspace_quality)); ?></button>
            <button type="button" class="button<?php echo ($workspace_filter ?? 'all') === 'overrides' ? ' is-active' : ''; ?>" data-sltr-filter="overrides"<?php echo $workspace_overrides === 0 ? ' disabled aria-disabled="true" title="' . esc_attr__('No translation overrides', 'slotera-booking') . '"' : ''; ?>><?php echo esc_html(sprintf(__('Overrides (%d)', 'slotera-booking'), $workspace_overrides)); ?></button>
            <button type="button" class="button<?php echo ($workspace_filter ?? 'all') === 'unsaved' ? ' is-active' : ''; ?>" data-sltr-filter="unsaved" disabled aria-disabled="true" title="<?php esc_attr_e('No unsaved changes', 'slotera-booking'); ?>"><?php esc_html_e('Unsaved (0)', 'slotera-booking'); ?></button>
            <span class="sltr-workspace-visible" data-sltr-visible-count><?php echo esc_html(sprintf(__('%d visible', 'slotera-booking'), $workspace_total)); ?></span>
            <span class="sltr-workspace-unsaved" data-sltr-unsaved hidden><?php esc_html_e('Unsaved changes', 'slotera-booking'); ?></span>
        </div>
    </section>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-sltr-translation-form>
        <?php wp_nonce_field('sltr_save_translations'); ?>
        <input type="hidden" name="action" value="sltr_save_translations">
        <input type="hidden" name="locale" value="<?php echo esc_attr($locale); ?>">
        <input type="hidden" name="group" value="<?php echo esc_attr($active_group); ?>">
        <input type="hidden" name="translation_section" value="<?php echo esc_attr($active_section); ?>">
        <input type="hidden" name="workspace_filter" value="<?php echo esc_attr($workspace_filter ?? 'all'); ?>" data-sltr-workspace-filter>
        <?php if (!empty($search)) : ?>
            <input type="hidden" name="translation_search" value="<?php echo esc_attr($search); ?>">
        <?php endif; ?>

        <table class="widefat striped sltr-translations-table">
            <thead>
                <tr>
                    <th style="width:28%;"><?php esc_html_e('Key', 'slotera-booking'); ?></th>
                    <th style="width:36%;"><?php esc_html_e('English default', 'slotera-booking'); ?></th>
                    <th style="width:36%;"><?php echo esc_html($languages[$locale]); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($strings)) : ?>
                    <tr>
                        <td colspan="3"><?php esc_html_e('No translations found for this search.', 'slotera-booking'); ?></td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($strings as $key => $meta) :
                    $current_value = (string) ($values[$key] ?? '');
                    $is_missing = trim($current_value) === '';
                    $is_duplicate = !empty($duplicate_keys[$key]);
                    $quality_issue = (array) (($quality_lint_results ?? [])[$key] ?? []);
                    $has_quality_issue = $quality_issue !== [];
                    $is_manual_override = !empty(($manual_override_keys ?? [])[$key]);
                ?>
                    <tr data-sltr-translation-row data-missing="<?php echo $is_missing ? '1' : '0'; ?>" data-duplicate="<?php echo $is_duplicate ? '1' : '0'; ?>" data-quality="<?php echo $has_quality_issue ? '1' : '0'; ?>" data-override="<?php echo $is_manual_override ? '1' : '0'; ?>">
                        <td><code><?php echo esc_html($key); ?></code><?php if ($is_duplicate) : ?><br><span class="description sltr-duplicate-label"><?php echo esc_html(sprintf(__('Duplicate source (%d keys)', 'slotera-booking'), (int) $duplicate_keys[$key]['count'])); ?></span><?php endif; ?><br><span class="description"><?php echo esc_html($meta['label'] ?? ''); ?></span></td>
                        <td class="sltr-source-cell"><?php echo !empty($meta['textarea']) ? '<textarea readonly rows="3" class="large-text" data-sltr-source>' . esc_textarea((string) $meta['default']) . '</textarea>' : '<input type="text" readonly class="regular-text" data-sltr-source value="' . esc_attr((string) $meta['default']) . '">'; ?><button type="button" class="button-link sltr-copy-source" data-sltr-copy-source><?php esc_html_e('Copy source to translation', 'slotera-booking'); ?></button></td>
                        <td>
                            <?php if (!empty($meta['textarea'])) : ?>
                                <textarea rows="3" class="large-text" data-sltr-translation-key="<?php echo esc_attr($key); ?>" name="translations[<?php echo esc_attr($key); ?>]"><?php echo esc_textarea($current_value); ?></textarea>
                            <?php else : ?>
                                <input type="text" class="regular-text" data-sltr-translation-key="<?php echo esc_attr($key); ?>" name="translations[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($current_value); ?>">
                            <?php endif; ?>
                            <?php if ($has_quality_issue) : ?>
                                <div class="sltr-quality-hint">
                                    <strong><?php esc_html_e('Quality warning', 'slotera-booking'); ?></strong>
                                    <span><?php echo esc_html((string) ($quality_issue['message'] ?? '')); ?></span>
                                    <?php if (!empty($quality_issue['suggestion'])) : ?>
                                        <div class="sltr-quality-suggestion">
                                            <em><?php echo esc_html(sprintf(__('Suggestion: %s', 'slotera-booking'), (string) $quality_issue['suggestion'])); ?></em>
                                            <button type="button" class="button button-small sltr-apply-suggestion" data-sltr-apply-suggestion data-suggestion="<?php echo esc_attr((string) $quality_issue['suggestion']); ?>"><?php esc_html_e('Apply', 'slotera-booking'); ?></button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="submit sltr-translation-submit"><span data-sltr-save-state></span><button type="submit" class="button button-primary"><?php esc_html_e('Save translations', 'slotera-booking'); ?></button></p>
    </form>
    <?php endif; ?>
</div>
