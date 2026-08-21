<?php if (!defined('ABSPATH')) { exit; }
$totals = $report['totals'] ?? [];
$period = (string) ($report['period'] ?? '30');
$money = static function ($value): string {
    return function_exists('number_format_i18n') ? number_format_i18n((float) $value, 2) : number_format((float) $value, 2);
};
$render_table_empty = static function (string $message, int $columns): void {
    echo '<tr><td class="sltr-table-empty" colspan="' . esc_attr((string) $columns) . '"><div class="sltr-empty-state sltr-empty-state--compact"><span class="sltr-empty-state__title">' . esc_html($message) . '</span></div></td></tr>';
};
?>
<div class="wrap sltr-admin-wrap sltr-analytics-page sltr-pro-feature-page sltr-full-width-admin sltr-page-stack">
    <header class="sltr-page-header">
        <div class="sltr-page-header__content">
            <h1 class="sltr-page-header__title"><?php esc_html_e('Analytics', 'slotera-booking'); ?></h1>
        </div>
        <form method="get" class="sltr-page-header__actions sltr-filter-form">
            <input type="hidden" name="page" value="slotera-analytics">
            <label for="sltr-analytics-period"><?php esc_html_e('Period', 'slotera-booking'); ?></label>
            <select id="sltr-analytics-period" name="period">
                <?php foreach (['7' => __('Last 7 days', 'slotera-booking'), '30' => __('Last 30 days', 'slotera-booking'), '90' => __('Last 90 days', 'slotera-booking'), '365' => __('Last 365 days', 'slotera-booking'), 'all' => __('All time', 'slotera-booking')] as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($period, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button"><?php esc_html_e('Apply', 'slotera-booking'); ?></button>
        </form>
    </header>

    <div class="sltr-stat-grid">
        <?php $cards = [
            __('Booking Count', 'slotera-booking') => (int) ($totals['booking_count'] ?? 0),
            __('Successful', 'slotera-booking') => (int) ($totals['successful_bookings'] ?? 0),
            __('Success rate', 'slotera-booking') => (float) ($totals['success_rate'] ?? 0) . '%',
            __('Expected Revenue', 'slotera-booking') => $money($totals['expected_revenue'] ?? 0),
            __('Confirmed Revenue', 'slotera-booking') => $money($totals['confirmed_revenue'] ?? 0),
            __('Average Booking Value', 'slotera-booking') => $money($totals['average_booking_value'] ?? 0),
        ]; foreach ($cards as $label => $value) : ?>
            <article class="sltr-stat-card"><span class="sltr-stat-card__label"><?php echo esc_html((string) $label); ?></span><strong class="sltr-stat-card__value"><?php echo esc_html((string) $value); ?></strong></article>
        <?php endforeach; ?>
    </div>

    <div class="sltr-component-grid sltr-component-grid--2">
        <section class="sltr-panel sltr-panel--flush">
            <h2><?php esc_html_e('Booking funnel', 'slotera-booking'); ?></h2>
            <div class="sltr-panel__body sltr-chart-list">
                <?php foreach (($report['funnel'] ?? []) as $row) : $rate = max(0, min(100, (float) ($row['rate'] ?? 0))); ?>
                    <div class="sltr-chart-row"><div class="sltr-chart-row__label"><span><?php echo esc_html((string) ($row['label'] ?? '')); ?></span><strong><?php echo esc_html((string) ($row['value'] ?? 0)); ?> · <?php echo esc_html((string) $rate); ?>%</strong></div><progress max="100" value="<?php echo esc_attr((string) $rate); ?>"><?php echo esc_html((string) $rate); ?>%</progress></div>
                <?php endforeach; ?>
                <p class="description"><?php esc_html_e('This first production version measures server-side booking attempts and successful bookings. Page-view and step-level tracking can be added next.', 'slotera-booking'); ?></p>
            </div>
        </section>
        <section class="sltr-panel sltr-panel--flush">
            <h2><?php esc_html_e('Booking weekdays', 'slotera-booking'); ?></h2>
            <div class="sltr-panel__body sltr-chart-list">
                <?php foreach (($report['weekday'] ?? []) as $row) : $max = max(1, (int) ($totals['total_bookings'] ?? 1)); $rate = round(((int) ($row['bookings'] ?? 0) / $max) * 100, 1); ?>
                    <div class="sltr-chart-row"><div class="sltr-chart-row__label"><span><?php echo esc_html((string) ($row['label'] ?? '')); ?></span><strong><?php echo esc_html((string) ($row['bookings'] ?? 0)); ?></strong></div><progress max="100" value="<?php echo esc_attr((string) $rate); ?>"><?php echo esc_html((string) $rate); ?>%</progress></div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="sltr-component-grid sltr-component-grid--2">
        <?php foreach ([['title' => __('Top services', 'slotera-booking'), 'rows' => $report['services'] ?? [], 'kind' => 'service'], ['title' => __('Sources', 'slotera-booking'), 'rows' => $report['sources'] ?? [], 'kind' => 'source']] as $dataset) : ?>
            <section class="sltr-panel sltr-panel--flush">
                <h2><?php echo esc_html($dataset['title']); ?></h2>
                <div class="sltr-responsive-table-wrapper" tabindex="0" role="region" aria-label="<?php echo esc_attr($dataset['title']); ?>">
                    <table class="widefat striped sltr-responsive-table"><thead><tr><th><?php echo esc_html($dataset['kind'] === 'service' ? __('Service', 'slotera-booking') : __('Source', 'slotera-booking')); ?></th><th><?php esc_html_e('Bookings', 'slotera-booking'); ?></th><th><?php esc_html_e('Revenue', 'slotera-booking'); ?></th></tr></thead><tbody>
                        <?php foreach ($dataset['rows'] as $row) : ?><tr><td><?php echo esc_html((string) ($row[$dataset['kind'] === 'service' ? 'title' : 'source'] ?? '')); ?></td><td><?php echo esc_html((string) ($row['successful'] ?? 0)); ?></td><td><?php echo esc_html($money($row['revenue'] ?? 0)); ?></td></tr><?php endforeach; ?>
                        <?php if (empty($dataset['rows'])) { $render_table_empty(__('No data for this period yet.', 'slotera-booking'), 3); } ?>
                    </tbody></table>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <?php $visitor = $report['visitor_insights'] ?? []; $visitor_totals = is_array($visitor) ? ($visitor['totals'] ?? []) : []; ?>
    <section class="sltr-panel sltr-panel--flush">
        <div class="sltr-panel__header"><div><h2 class="sltr-panel__title"><?php esc_html_e('Visitor Insights', 'slotera-booking'); ?></h2><p class="sltr-panel__description"><?php esc_html_e('Privacy-safe page analytics. Slotera stores an anonymized IP prefix hash and aggregated city hints only; raw IP addresses are not stored.', 'slotera-booking'); ?></p></div></div>
        <div class="sltr-panel__body">
            <div class="sltr-stat-grid sltr-stat-grid--nested">
                <?php $visitor_cards = [__('Page views', 'slotera-booking') => (int) ($visitor_totals['page_views'] ?? 0), __('Sessions', 'slotera-booking') => (int) ($visitor_totals['sessions'] ?? 0), __('Service views without booking', 'slotera-booking') => (int) ($visitor_totals['service_views_without_booking'] ?? 0), __('Average time on page', 'slotera-booking') => round((float) ($visitor_totals['average_time_on_page'] ?? 0)) . 's', __('Bounce rate', 'slotera-booking') => (float) ($visitor_totals['bounce_rate'] ?? 0) . '%', __('Exit signals', 'slotera-booking') => (int) ($visitor_totals['exit_signals'] ?? 0)]; foreach ($visitor_cards as $label => $value) : ?>
                    <article class="sltr-stat-card"><span class="sltr-stat-card__label"><?php echo esc_html((string) $label); ?></span><strong class="sltr-stat-card__value"><?php echo esc_html((string) $value); ?></strong></article>
                <?php endforeach; ?>
            </div>
            <div class="sltr-component-grid sltr-component-grid--3">
                <?php $visitor_tables = [['title' => __('Most viewed pages', 'slotera-booking'), 'rows' => $visitor['top_pages'] ?? [], 'type' => 'pages'], ['title' => __('Short-view pages', 'slotera-booking'), 'rows' => $visitor['short_pages'] ?? [], 'type' => 'pages'], ['title' => __('Cities', 'slotera-booking'), 'rows' => $visitor['cities'] ?? [], 'type' => 'cities']]; foreach ($visitor_tables as $table) : ?>
                    <section class="sltr-subsection"><h3><?php echo esc_html($table['title']); ?></h3><div class="sltr-responsive-table-wrapper" tabindex="0" role="region" aria-label="<?php echo esc_attr($table['title']); ?>"><table class="widefat striped sltr-responsive-table"><thead><tr><?php if ($table['type'] === 'pages') : ?><th><?php esc_html_e('Page', 'slotera-booking'); ?></th><th><?php esc_html_e('Views', 'slotera-booking'); ?></th><th><?php esc_html_e('Avg. time', 'slotera-booking'); ?></th><?php else : ?><th><?php esc_html_e('City', 'slotera-booking'); ?></th><th><?php esc_html_e('Visits', 'slotera-booking'); ?></th><th><?php esc_html_e('Bookings', 'slotera-booking'); ?></th><?php endif; ?></tr></thead><tbody>
                        <?php foreach ($table['rows'] as $row) : ?><tr><?php if ($table['type'] === 'pages') : ?><td><a href="<?php echo esc_url((string) ($row['page_url'] ?? '')); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html((string) ($row['page_title'] ?? $row['page_url'] ?? '')); ?></a></td><td><?php echo esc_html((string) ($row['views'] ?? 0)); ?></td><td><?php echo esc_html((string) round((float) ($row['avg_duration_seconds'] ?? 0))); ?>s</td><?php else : ?><td><?php echo esc_html(trim((string) ($row['city'] ?? __('Unknown city', 'slotera-booking')) . (!empty($row['country']) ? ', ' . (string) $row['country'] : ''))); ?></td><td><?php echo esc_html((string) ($row['visits'] ?? 0)); ?></td><td><?php echo esc_html((string) ($row['bookings'] ?? 0)); ?></td><?php endif; ?></tr><?php endforeach; ?>
                        <?php if (empty($table['rows'])) { $render_table_empty(__('No visitor data for this period yet.', 'slotera-booking'), 3); } ?>
                    </tbody></table></div></section>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>
