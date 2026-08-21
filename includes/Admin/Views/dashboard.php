<?php
if (!defined('ABSPATH')) {
    exit;
}

$overview = $data['overview'] ?? [];
$cards = [
    'today_bookings' => sltr__('admin.dashboard.today_bookings'),
    'upcoming_bookings' => sltr__('admin.dashboard.upcoming'),
    'pending_payments' => sltr__('admin.dashboard.pending_payments'),
    'cancelled_bookings' => sltr__('admin.dashboard.cancelled'),
    'paid_revenue' => sltr__('admin.dashboard.revenue'),
];
$sections = $data['dashboard_sections'] ?? [];
$active_card = 'upcoming_bookings';
$sltr_settings_repo = new \Slotera\Infrastructure\Repositories\SettingsRepository();
$sltr_settings = $sltr_settings_repo->all();
$sltr_currency = \Slotera\Application\Services\CurrencyService::normalize((string) ($sltr_settings['payment_currency'] ?? 'EUR'));
$sltr_currency_position = \Slotera\Application\Services\CurrencyService::normalize_position((string) ($sltr_settings['payment_currency_position'] ?? 'right'));
$sltr_money = static function ($amount) use ($sltr_currency, $sltr_currency_position): string {
    return \Slotera\Application\Services\CurrencyService::format((float) $amount, $sltr_currency, $sltr_currency_position);
};
?>
<div class="wrap sltr-admin">
    <header class="sltr-page-header"><div class="sltr-page-header__content"><h1 class="sltr-page-header__title"><?php sltr_esc_html_e('admin.dashboard.title'); ?></h1></div></header>

    <div class="sltr-cards sltr-dashboard-filter-cards" role="tablist" aria-label="<?php echo esc_attr(sltr__('admin.dashboard.title')); ?>">
        <?php foreach ($cards as $key => $label) : ?>
            <button type="button" class="sltr-stat-card sltr-card sltr-dashboard-filter-card <?php echo $key === $active_card ? 'is-active' : ''; ?>" data-sltr-dashboard-filter="<?php echo esc_attr($key); ?>" role="tab" aria-selected="<?php echo $key === $active_card ? 'true' : 'false'; ?>" aria-controls="sltr-dashboard-section-<?php echo esc_attr($key); ?>">
                <span><?php echo esc_html($label); ?></span>
                <strong><?php echo esc_html((string) ($overview[$key] ?? 0)); ?></strong>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="sltr-grid">
        <section class="sltr-panel sltr-dashboard-dynamic-panel">
            <?php foreach ($cards as $key => $label) :
                $section = $sections[$key] ?? ['title' => $label, 'empty' => sltr__('admin.dashboard.no_matching_bookings'), 'rows' => []];
                $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
                ?>
                <div id="sltr-dashboard-section-<?php echo esc_attr($key); ?>" class="sltr-dashboard-section <?php echo $key === $active_card ? 'is-active' : ''; ?>" data-sltr-dashboard-section="<?php echo esc_attr($key); ?>" <?php echo $key === $active_card ? '' : 'hidden'; ?>>
                    <h2><?php echo esc_html((string) ($section['title'] ?? $label)); ?></h2>

                    <?php if (($section['type'] ?? '') === 'revenue') : ?>
                        <p class="sltr-dashboard-revenue-total"><strong><?php echo esc_html($sltr_money((float) ($section['total'] ?? 0))); ?></strong></p>
                        <?php if (!empty($rows)) : ?>
                            <table class="widefat striped">
                                <thead>
                                    <tr>
                                        <th><?php sltr_esc_html_e('admin.common.client'); ?></th>
                                        <th><?php sltr_esc_html_e('admin.common.date'); ?></th>
                                        <th><?php esc_html_e('Amount', 'slotera-booking'); ?></th>
                                        <th><?php esc_html_e('Payment', 'slotera-booking'); ?></th>
                                        <th><?php esc_html_e('Method', 'slotera-booking'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $booking) :
                                        $amount = (float) ($booking['paid_amount'] ?? 0);
                                        if ($amount <= 0) { $amount = (float) ($booking['total_amount'] ?? 0); }
                                        ?>
                                        <tr>
                                            <td><?php echo esc_html((string) ($booking['customer_name'] ?? '')); ?></td>
                                            <td><?php echo esc_html((string) ($booking['booking_date'] ?? '')); ?></td>
                                            <td><?php echo esc_html($sltr_money($amount)); ?></td>
                                            <td><?php echo esc_html((string) ($booking['payment_status'] ?? '')); ?></td>
                                            <td><?php echo esc_html((string) ($booking['payment_gateway'] ?? '')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <div class="sltr-empty-state"><span><?php echo esc_html((string) ($section['empty'] ?? sltr__('admin.dashboard.no_matching_bookings'))); ?></span></div>
                        <?php endif; ?>
                    <?php elseif (!empty($rows)) : ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php sltr_esc_html_e('admin.common.client'); ?></th>
                                    <th><?php sltr_esc_html_e('admin.common.date'); ?></th>
                                    <th><?php sltr_esc_html_e('admin.common.time'); ?></th>
                                    <th><?php sltr_esc_html_e('admin.common.status'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $booking) : ?>
                                    <tr>
                                        <td><?php echo esc_html((string) ($booking['customer_name'] ?? '')); ?></td>
                                        <td><?php echo esc_html((string) ($booking['booking_date'] ?? '')); ?></td>
                                        <td><?php echo esc_html(trim((string) ($booking['start_time'] ?? '') . ' - ' . (string) ($booking['end_time'] ?? ''), ' -')); ?></td>
                                        <td><?php echo esc_html((string) ($booking['status'] ?? '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <div class="sltr-empty-state"><span><?php echo esc_html((string) ($section['empty'] ?? sltr__('admin.dashboard.no_matching_bookings'))); ?></span></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="sltr-panel">
            <h2><?php sltr_esc_html_e('admin.dashboard.recent_activity'); ?></h2>

            <?php if (!empty($data['recent_activity'])) : ?>
                <ul class="sltr-list">
                    <?php foreach ($data['recent_activity'] as $log) : ?>
                        <li>
                            <strong><?php echo esc_html((string) ($log['message'] ?? '')); ?></strong><br>
                            <small><?php echo esc_html((string) ($log['event'] ?? '') . ' · ' . (string) ($log['created_at'] ?? '')); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <div class="sltr-empty-state"><span><?php sltr_esc_html_e('admin.dashboard.no_recent_activity'); ?></span></div>
            <?php endif; ?>
        </section>
    </div>
</div>
