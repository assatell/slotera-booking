<?php
declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\MarketingCampaignRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) { exit; }

final class PromotionCampaignService
{
    public const CRON_HOOK = 'sltr_process_promotion_digest';

    private SettingsRepository $settings;
    private PackageRepository $packages;
    private MarketingCampaignRepository $campaigns;
    private MarketingEmailService $marketing;

    public function __construct(?SettingsRepository $settings = null, ?PackageRepository $packages = null, ?MarketingCampaignRepository $campaigns = null, ?MarketingEmailService $marketing = null)
    {
        $this->settings = $settings ?: new SettingsRepository();
        $this->packages = $packages ?: new PackageRepository();
        $this->campaigns = $campaigns ?: new MarketingCampaignRepository();
        $this->marketing = $marketing ?: new MarketingEmailService();
    }

    public function register_hooks(): void
    {
        add_action(self::CRON_HOOK, [$this, 'process_scheduled']);
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 300, 'hourly', self::CRON_HOOK);
        }
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public function settings(): array
    {
        return [
            'enabled' => (int) $this->settings->get('promotion_digest_enabled', 0),
            'frequency' => sanitize_key((string) $this->settings->get('promotion_digest_frequency', 'manual')),
            'subject' => (string) $this->settings->get('promotion_digest_subject', 'Special offers'),
            'intro' => (string) $this->settings->get('promotion_digest_intro', 'See our current special offers.'),
            'closing' => (string) $this->settings->get('promotion_digest_closing', ''),
            'button_label' => (string) $this->settings->get('promotion_digest_button_label', 'Book now'),
            'fallback_image_id' => absint($this->settings->get('promotion_digest_fallback_image_id', 0)),
            'test_email' => sanitize_email((string) $this->settings->get('promotion_digest_test_email', get_option('admin_email'))),
            'last_run' => (string) $this->settings->get('promotion_digest_last_run', ''),
            'last_result' => (string) $this->settings->get('promotion_digest_last_result', ''),
        ];
    }

    public function save_settings(array $input): void
    {
        $frequency = sanitize_key((string) ($input['promotion_digest_frequency'] ?? 'manual'));
        if (!in_array($frequency, ['manual', 'weekly', 'biweekly', 'monthly'], true)) { $frequency = 'manual'; }
        $this->settings->update([
            'promotion_digest_enabled' => !empty($input['promotion_digest_enabled']) && $frequency !== 'manual' ? 1 : 0,
            'promotion_digest_frequency' => $frequency,
            'promotion_digest_subject' => sanitize_text_field((string) ($input['promotion_digest_subject'] ?? 'Special offers')),
            'promotion_digest_intro' => wp_kses_post((string) ($input['promotion_digest_intro'] ?? '')),
            'promotion_digest_closing' => wp_kses_post((string) ($input['promotion_digest_closing'] ?? '')),
            'promotion_digest_button_label' => sanitize_text_field((string) ($input['promotion_digest_button_label'] ?? 'Book now')),
            'promotion_digest_fallback_image_id' => absint($input['promotion_digest_fallback_image_id'] ?? 0),
            'promotion_digest_test_email' => sanitize_email((string) ($input['promotion_digest_test_email'] ?? '')),
        ]);
    }

    public function active_offers(?string $reference_date = null): array
    {
        $reference_date = $reference_date ?: wp_date('Y-m-d', current_time('timestamp'));
        $offers = [];
        foreach ($this->packages->get_active(500, 0) as $package) {
            $offer = $this->package_offer($package, $reference_date);
            if ($offer !== null) { $offers[] = $offer; }
        }
        usort($offers, static function (array $a, array $b): int {
            $ai = empty($a['image_url']) ? 1 : 0;
            $bi = empty($b['image_url']) ? 1 : 0;
            return $ai <=> $bi ?: strcasecmp((string) $a['title'], (string) $b['title']);
        });
        return $offers;
    }

    public function eligible_recipient_count(): int
    {
        return count($this->marketing->audience_for_campaign(['audience_type' => 'all']));
    }

    public function preview(string $email = '', array $input = []): array
    {
        return $this->marketing->preview_message_for_campaign($this->campaign_payload(null, $input), $email) ?: ['subject' => '', 'body' => ''];
    }

    public function send_test(string $email, array $input = []): bool
    {
        return $this->marketing->send_test_for_campaign($this->campaign_payload(null, $input), $email);
    }

    public function send_now(string $reason = 'manual', array $input = []): array
    {
        $offers = $this->active_offers();
        if ($offers === []) {
            $this->record_run('Skipped — no active offers');
            return ['queued' => 0, 'skipped' => 0, 'reason' => 'no_active_offers'];
        }
        $payload = $this->campaign_payload($offers, $input);
        $payload['name'] = sprintf('Promotion digest — %s', wp_date('Y-m-d H:i'));
        $id = $this->campaigns->create($payload);
        if ($id <= 0) {
            $this->record_run('Failed — campaign could not be created');
            return ['queued' => 0, 'skipped' => 0, 'reason' => 'campaign_create_failed'];
        }
        $result = $this->marketing->queue_campaign($id, ['automation' => $reason === 'scheduled' ? 'promotion_digest' : '']);
        $queued = (int) ($result['queued'] ?? 0);
        $this->record_run($queued > 0 ? 'Queued ' . $queued . ' recipient(s)' : 'Skipped — no eligible recipients');
        return array_merge($result, ['campaign_id' => $id]);
    }

    public function process_scheduled(): array
    {
        $cfg = $this->settings();
        if ((int) $cfg['enabled'] !== 1 || $cfg['frequency'] === 'manual') { return ['reason' => 'disabled']; }
        $now = current_datetime();
        if ((int) $now->format('N') !== 5) { return ['reason' => 'not_friday']; }
        if (!$this->is_due($cfg['frequency'], (string) $cfg['last_run'], $now)) { return ['reason' => 'not_due']; }
        return $this->send_now('scheduled');
    }

    public function next_run_label(): string
    {
        $cfg = $this->settings();
        if ((int) $cfg['enabled'] !== 1 || $cfg['frequency'] === 'manual') { return 'Manual'; }
        $now = current_datetime();
        $days = (5 - (int) $now->format('N') + 7) % 7;
        if ($days === 0 && !$this->is_due($cfg['frequency'], (string) $cfg['last_run'], $now)) { $days = 7; }
        $candidate = $now->modify('+' . $days . ' days')->setTime(9, 0);
        if ($cfg['frequency'] === 'biweekly' && (string) $cfg['last_run'] !== '') {
            $last = strtotime((string) $cfg['last_run']);
            if ($last && $candidate->getTimestamp() < $last + 13 * DAY_IN_SECONDS) { $candidate = $candidate->modify('+7 days'); }
        }
        if ($cfg['frequency'] === 'monthly' && (string) $cfg['last_run'] !== '') {
            $last = strtotime((string) $cfg['last_run']);
            if ($last && $candidate->format('Y-m') === wp_date('Y-m', $last)) { $candidate = $candidate->modify('+7 days'); }
        }
        return wp_date('Y-m-d H:i', $candidate->getTimestamp());
    }

    private function is_due(string $frequency, string $last_run, \DateTimeImmutable $now): bool
    {
        if ($last_run === '') { return true; }
        $last = strtotime($last_run);
        if (!$last) { return true; }
        if ($frequency === 'weekly') { return $now->getTimestamp() >= $last + 6 * DAY_IN_SECONDS; }
        if ($frequency === 'biweekly') { return $now->getTimestamp() >= $last + 13 * DAY_IN_SECONDS; }
        if ($frequency === 'monthly') { return $now->format('Y-m') !== wp_date('Y-m', $last); }
        return false;
    }

    private function record_run(string $result): void
    {
        $this->settings->update([
            'promotion_digest_last_run' => current_time('mysql'),
            'promotion_digest_last_result' => sanitize_text_field($result),
        ]);
    }

    private function campaign_payload(?array $offers = null, array $input = []): array
    {
        $cfg = $this->settings();
        if ($input !== []) {
            $cfg = $this->settings_from_input($input, $cfg);
        }
        $offers = $offers ?? $this->active_offers();
        return [
            'name' => 'Promotion digest preview',
            'template_key' => 'marketing_promo',
            'subject_override' => $cfg['subject'] !== '' ? $cfg['subject'] : 'Special offers',
            'audience_type' => 'all',
            'package_id' => 0,
            'coupon_id' => 0,
            'generate_unique_coupons' => 0,
            'cta_enabled' => 0,
            'cta_label' => '',
            'cta_url_type' => 'booking',
            'cta_custom_url' => '',
            'marketing_headline' => $cfg['subject'],
            'marketing_message' => $this->render_offer_html($offers, $cfg),
            'marketing_submessage' => '',
            'source' => 'promotion_digest',
            'status' => 'draft',
        ];
    }

    private function settings_from_input(array $input, array $saved): array
    {
        $frequency = sanitize_key((string) ($input['promotion_digest_frequency'] ?? $saved['frequency'] ?? 'manual'));
        if (!in_array($frequency, ['manual', 'weekly', 'biweekly', 'monthly'], true)) {
            $frequency = 'manual';
        }

        return array_merge($saved, [
            'enabled' => !empty($input['promotion_digest_enabled']) && $frequency !== 'manual' ? 1 : 0,
            'frequency' => $frequency,
            'subject' => sanitize_text_field((string) ($input['promotion_digest_subject'] ?? $saved['subject'] ?? 'Special offers')),
            'intro' => wp_kses_post((string) ($input['promotion_digest_intro'] ?? $saved['intro'] ?? '')),
            'closing' => wp_kses_post((string) ($input['promotion_digest_closing'] ?? $saved['closing'] ?? '')),
            'button_label' => sanitize_text_field((string) ($input['promotion_digest_button_label'] ?? $saved['button_label'] ?? 'Book now')),
            'fallback_image_id' => absint($input['promotion_digest_fallback_image_id'] ?? $saved['fallback_image_id'] ?? 0),
            'test_email' => sanitize_email((string) ($input['promotion_digest_test_email'] ?? $saved['test_email'] ?? '')),
        ]);
    }

    private function package_offer(array $package, string $date): ?array
    {
        $base = max(0, (float) ($package['price'] ?? 0));
        if ($base <= 0) { return null; }
        $dynamic = (new PricingAdjustmentService())->active_config($package);
        $current = $base;
        $types = [];
        $validity = [];
        $meta_lines = [];

        $weekend = !empty($dynamic['dynamic_pricing_enabled']) ? max(0, min(100, abs((float) ($dynamic['dynamic_weekend_percent'] ?? 0)))) : 0.0;
        if ($weekend > 0) {
            $current = round(max(0, $current - ($current * $weekend / 100)), 2);
            $weekend_line = $this->translated_offer_label('Weekend offer') . ' -' . $this->percent($weekend) . '%';
            $types[] = $weekend_line;
            $meta_lines[] = $weekend_line;
        }

        $season = !empty($dynamic['dynamic_pricing_enabled']) ? max(0, min(100, abs((float) ($dynamic['dynamic_season_percent'] ?? 0)))) : 0.0;
        $start = sanitize_text_field((string) ($dynamic['dynamic_season_start'] ?? ''));
        $end = sanitize_text_field((string) ($dynamic['dynamic_season_end'] ?? ''));
        if ($season > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) && $date >= min($start, $end) && $date <= max($start, $end)) {
            $current = round(max(0, $current - ($current * $season / 100)), 2);
            $season_end = max($start, $end);
            $season_line = $this->translated_offer_label('Seasonal offer') . ' -' . $this->percent($season) . '% · ' . $season_end;
            $types[] = $this->translated_offer_label('Seasonal offer') . ' -' . $this->percent($season) . '%';
            $validity[] = $season_end;
            $meta_lines[] = $season_line;
        }

        $discount_type = sanitize_key((string) ($package['discount_type'] ?? 'none'));
        $discount_value = max(0, (float) ($package['discount_value'] ?? 0));
        if ($discount_type === 'percent' && $discount_value > 0) {
            $current = round(max(0, $current - ($current * min(100, $discount_value) / 100)), 2);
            $discount_line = $this->translated_offer_label('Standard discount') . ' -' . $this->percent($discount_value) . '%';
            $types[] = $discount_line;
            $validity[] = 'Current offer';
            $meta_lines[] = $discount_line;
        } elseif ($discount_type === 'fixed' && $discount_value > 0) {
            $current = round(max(0, $current - $discount_value), 2);
            $discount_line = $this->translated_offer_label('Standard discount');
            $types[] = $discount_line;
            $validity[] = 'Current offer';
            $meta_lines[] = $discount_line;
        }

        if ($types === [] || $current >= $base) { return null; }

        $booking_image_id = absint($package['booking_card_image_id'] ?? 0);
        $package_image_id = absint($package['card_image_id'] ?? 0);
        $image_id = $booking_image_id > 0 ? $booking_image_id : $package_image_id;
        $image_source = $booking_image_id > 0 ? 'Slotera Booking page image' : ($package_image_id > 0 ? 'Package page image' : '');
        $image_url = $image_id > 0 ? (string) (wp_get_attachment_image_url($image_id, 'large') ?: wp_get_attachment_url($image_id)) : '';

        return [
            'id' => absint($package['id'] ?? 0),
            'title' => sanitize_text_field((string) ($package['title'] ?? '')),
            'old_price' => $base,
            'new_price' => $current,
            'offer_label' => implode(' · ', array_unique($types)),
            'validity' => implode(' · ', array_unique($validity)),
            'meta_lines' => array_values(array_unique($meta_lines)),
            'image_id' => $image_id,
            'image_url' => esc_url_raw($image_url),
            'image_source' => $image_source,
            'url' => $this->package_booking_url($package),
        ];
    }

    private function render_offer_html(array $offers, array $cfg): string
    {
        if ($offers === []) { return '<p>No active offers.</p>'; }
        $html = '';
        if (trim((string) $cfg['intro']) !== '') { $html .= '<div style="margin:0 0 22px;">' . wp_kses_post(wpautop((string) $cfg['intro'])) . '</div>'; }
        $without = [];
        foreach ($offers as $offer) {
            if (empty($offer['image_url'])) { $without[] = $offer; continue; }
            $html .= $this->offer_card_html($offer, (string) $cfg['button_label']);
        }
        if ($without !== []) {
            $fallback_id = absint($cfg['fallback_image_id'] ?? 0);
            $fallback = $fallback_id > 0 ? (string) (wp_get_attachment_image_url($fallback_id, 'large') ?: wp_get_attachment_url($fallback_id)) : '';
            $html .= '<div style="margin:28px 0;padding:18px;border:1px solid #dbe3ef;border-radius:16px;">';
            if ($fallback !== '') { $html .= '<img src="' . esc_url($this->email_safe_image_url($fallback)) . '" alt="" style="display:block;width:100%;height:auto;border-radius:12px;margin:0 0 16px;">'; }
            $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0">';
            foreach ($without as $offer) {
                $html .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e5e7eb;">'
                    . '<strong>' . esc_html((string) $offer['title']) . '</strong><br>'
                    . '<span style="text-decoration:line-through;color:#64748b;">' . esc_html($this->money((float) $offer['old_price'])) . '</span> '
                    . '<strong>' . esc_html($this->money((float) $offer['new_price'])) . '</strong><br>'
                    . '<span style="font-size:12px;color:#64748b;">' . $this->offer_meta_html($offer) . '</span>'
                    . '</td></tr>';
            }
            $html .= '</table></div>';
        }
        if (trim((string) $cfg['closing']) !== '') { $html .= '<div style="margin:22px 0 0;">' . wp_kses_post(wpautop((string) $cfg['closing'])) . '</div>'; }
        return $html;
    }

    private function offer_card_html(array $offer, string $button_label): string
    {
        $cta = '';
        $url = trim((string) ($offer['url'] ?? ''));
        if ($url !== '') {
            $cta = '<p style="margin:0;"><a href="' . esc_url($url) . '" style="display:inline-block;padding:11px 18px;border-radius:999px;background:#2563eb;color:#fff;text-decoration:none;font-weight:700;white-space:nowrap;">' . esc_html($button_label !== '' ? $button_label : \sltr_t('Book now', 'emails', EmailTemplateRegistry::runtime_locale())) . '</a></p>';
        }

        return '<div style="margin:0 0 28px;padding:18px;border:1px solid #dbe3ef;border-radius:16px;">'
            . '<img src="' . esc_url($this->email_safe_image_url((string) $offer['image_url'])) . '" alt="' . esc_attr((string) $offer['title']) . '" style="display:block;width:100%;height:auto;border-radius:12px;margin:0 0 14px;">'
            . '<h2 style="margin:0 0 8px;font-size:22px;">' . esc_html((string) $offer['title']) . '</h2>'
            . '<p style="margin:0 0 6px;"><span style="text-decoration:line-through;color:#64748b;">' . esc_html($this->money((float) $offer['old_price'])) . '</span> <strong style="font-size:20px;">' . esc_html($this->money((float) $offer['new_price'])) . '</strong></p>'
            . '<div style="margin:0 0 14px;color:#64748b;font-size:13px;line-height:1.5;">' . $this->offer_meta_html($offer) . '</div>'
            . $cta
            . '</div>';
    }

    private function offer_meta_lines(array $offer): array
    {
        $lines = array_values(array_filter(array_map('trim', (array) ($offer['meta_lines'] ?? [])), static fn(string $value): bool => $value !== ''));
        if ($lines !== []) { return $lines; }
        $parts = array_values(array_filter([
            trim((string) ($offer['offer_label'] ?? '')),
            trim((string) ($offer['validity'] ?? '')),
        ], static fn(string $value): bool => $value !== ''));
        return $parts;
    }

    private function offer_meta_html(array $offer): string
    {
        return implode('<br>', array_map('esc_html', $this->offer_meta_lines($offer)));
    }

    private function email_safe_image_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') { return ''; }
        $encoded = preg_replace_callback('/[^\x00-\x7F]/u', static fn(array $match): string => rawurlencode($match[0]), $url);
        return is_string($encoded) ? $encoded : $url;
    }

    private function translated_offer_label(string $default): string
    {
        $locale = EmailTemplateRegistry::runtime_locale();
        return function_exists('sltr_t') ? \sltr_t($default, 'frontend', $locale) : $default;
    }

    private function package_booking_url(array $package): string
    {
        $page_id = absint($package['page_id'] ?? 0);
        if (!empty($package['solo_page_enabled']) && $page_id > 0) {
            $url = get_permalink($page_id);
            if (is_string($url) && trim($url) !== '') { return $url; }
        }

        $url = trim((string) $this->settings->get_page_url('booking'));
        if ($url === '') { return ''; }

        $url = add_query_arg([
            'sltr_package_id' => absint($package['id'] ?? 0),
            'sltr_step' => 'calendar',
        ], $url);
        if (strpos($url, '#') === false) { $url .= '#sltr-booking'; }
        return $url;
    }

    private function money(float $amount): string
    {
        $currency = strtoupper((string) $this->settings->get('payment_currency', 'EUR'));
        $decimals = max(0, min(4, absint($this->settings->get('payment_decimals', 2))));
        return number_format_i18n($amount, $decimals) . ' ' . $currency;
    }

    private function percent(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
