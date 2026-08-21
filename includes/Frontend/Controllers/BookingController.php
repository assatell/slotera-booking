<?php

declare(strict_types=1);

namespace Slotera\Frontend\Controllers;

use Slotera\Application\Services\BookingLifecycleService;
use Slotera\Application\Services\BookingService;
use Slotera\Application\Services\BookingSpamProtectionService;
use Slotera\Application\Services\CouponService;
use Slotera\Domain\Availability\AvailabilityService;
use Slotera\Infrastructure\Repositories\BookingRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;
use Slotera\Application\Services\PaymentService;
use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\PerformanceProfiler;
use Slotera\Application\Services\PublicBookingActionSecurityService;
use Slotera\Application\Services\AccountMagicLinkService;
use Slotera\Application\Services\TranslationService;
use Slotera\Application\Services\MarketingConsentService;
use Slotera\Application\Services\PublicBookingRequestNormalizer;
use Slotera\Application\Services\SimpleBookingQuoteService;

if (!defined('ABSPATH')) { exit; }

final class BookingController
{
    private RequestValidator $request;
    private BookingSpamProtectionService $spam;
    private BookingService $bookings;
    private PaymentService $payments;
    private BookingLifecycleService $lifecycle;
    private PublicBookingActionSecurityService $public_actions;

    public function __construct(?RequestValidator $request = null, ?BookingSpamProtectionService $spam = null, ?BookingService $bookings = null, ?PaymentService $payments = null, ?BookingLifecycleService $lifecycle = null, ?PublicBookingActionSecurityService $public_actions = null)
    {
        $this->request = $request ?? new RequestValidator();
        $this->spam = $spam ?? new BookingSpamProtectionService();
        $this->bookings = $bookings ?? new BookingService();
        $this->payments = $payments ?? new PaymentService();
        $this->lifecycle = $lifecycle ?? new BookingLifecycleService();
        $this->public_actions = $public_actions ?? new PublicBookingActionSecurityService();
    }

    public function register(): void
    {
        add_action('wp_ajax_sltr_create_booking', [$this, 'create']);
        add_action('wp_ajax_nopriv_sltr_create_booking', [$this, 'create']);
        add_action('wp_ajax_sltr_validate_coupon', [$this, 'validate_coupon']);
        add_action('wp_ajax_nopriv_sltr_validate_coupon', [$this, 'validate_coupon']);
        add_action('wp_ajax_sltr_quote_simple_booking', [$this, 'quote_simple_booking']);
        add_action('wp_ajax_nopriv_sltr_quote_simple_booking', [$this, 'quote_simple_booking']);
        add_action('template_redirect', [$this, 'handle_public_token_action']);
    }

    public function create(): void
    {
        $this->request->verify_ajax_nonce('sltr_frontend_booking');
        if ((string) (new SettingsRepository())->get('booking_availability_status', 'available') === 'paused') {
            status_header(503);
            wp_send_json_error(sltr_t('Booking is temporarily unavailable. Please try again later.'), 503);
        }
        $booking_data = $this->booking_data_from_request();

        $spam_check = $this->spam->validate_frontend_submission($booking_data);
        if (is_wp_error($spam_check)) {
            wp_send_json_error($spam_check->get_error_message());
        }

        $result = PerformanceProfiler::time('ajax.booking.create_booking', function () use ($booking_data) {
            return $this->bookings->create_booking($booking_data);
        });
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        $booking_id = is_array($result) ? (int) ($result['booking_id'] ?? 0) : (int) $result;
        $payment_result = is_array($result) ? ($result['payment_result'] ?? null) : null;
        if (!empty($booking_data['marketing_consent'])) {
            (new MarketingConsentService())->grant((string) ($booking_data['customer_email'] ?? ''), 'booking_form');
        }
        $redirect_url = $this->payments->get_booking_redirect_url($booking_id, is_array($payment_result) ? $payment_result : null);

        wp_send_json_success(['booking_id' => $booking_id, 'payment' => $payment_result, 'redirect_url' => esc_url_raw($redirect_url)]);
    }




    public function quote_simple_booking(): void
    {
        $this->request->verify_ajax_nonce('sltr_frontend_booking');
        $package_id = $this->request->post_int('package_id');
        $package = (new PackageRepository())->get_by_id($package_id);
        if (!$package || (int) ($package['is_active'] ?? 0) !== 1 || sanitize_key((string) ($package['booking_mode'] ?? '')) !== 'simple') {
            wp_send_json_error(sltr_t('Selected package is not available.'));
        }
        $extra_ids_raw = isset($_POST['extra_ids']) ? wp_unslash((string) $_POST['extra_ids']) : '';
        $extra_ids = array_values(array_unique(array_filter(array_map('absint', preg_split('/[\s,]+/', $extra_ids_raw) ?: []))));
        $coupon_code = $this->request->post_text('coupon_code');
        $email = $this->request->post_email('email');
        $quote = (new SimpleBookingQuoteService())->quote($package, $extra_ids, $coupon_code, $email);
        if (is_wp_error($quote)) { wp_send_json_error($quote->get_error_message()); }
        $quote['valid'] = $coupon_code === '' ? true : ((string) ($quote['coupon_code'] ?? '') !== '');
        $quote['code'] = (string) ($quote['coupon_code'] ?? '');
        $quote['base_amount'] = number_format((float) ($quote['pricing_base_amount'] ?? 0), 2, '.', '');
        $quote['discount_amount'] = number_format((float) ($quote['coupon_discount_amount'] ?? 0), 2, '.', '');
        $quote['final_amount'] = number_format((float) ($quote['final_amount'] ?? 0), 2, '.', '');
        wp_send_json_success($quote);
    }

    public function validate_coupon(): void
    {
        $this->request->verify_ajax_nonce('sltr_frontend_booking');
        $package_id = $this->request->post_int('package_id');
        $code = $this->request->post_text('coupon_code');
        $email = $this->request->post_email('email');
        $this->enforce_coupon_rate_limit($email);
        $package = (new PackageRepository())->get_by_id($package_id);
        if (!$package || (int) ($package['is_active'] ?? 0) !== 1) {
            wp_send_json_error(sltr_t('Selected package is not available.'));
        }
        $booking_days = max(1, min(3650, $this->request->post_int('booking_days', 1)));
        if ($booking_days > 1) {
            $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
            $is_fixed_full_day = sanitize_key((string) ($package['booking_mode'] ?? '')) === 'fixed'
                && is_array($configs)
                && !empty($configs['fixed']['full_day_booking']);
            if ($is_fixed_full_day) {
                $package['price'] = max(0, (float) ($package['price'] ?? 0)) * $booking_days;
            }
        }
        $result = PerformanceProfiler::time('ajax.booking.validate_coupon', function () use ($code, $package, $email) {
            return (new CouponService())->validate_and_calculate($code, $package, $email);
        });
        if (is_wp_error($result)) { wp_send_json_error($result->get_error_message()); }
        $coupon = is_array($result) ? ($result['coupon'] ?? []) : [];
        wp_send_json_success([
            'valid' => !empty($result['valid']),
            'code' => (string) ($coupon['code'] ?? ''),
            'base_amount' => number_format((float) ($result['base_amount'] ?? 0), 2),
            'discount_amount' => number_format((float) ($result['discount_amount'] ?? 0), 2),
            'final_amount' => number_format((float) ($result['final_amount'] ?? 0), 2),
        ]);
    }


    private function enforce_coupon_rate_limit(string $email): void
    {
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            return;
        }

        $settings = (new SettingsRepository())->all();
        $window = max(1, (int) ($settings['security_rate_limit_window_minutes'] ?? 15)) * MINUTE_IN_SECONDS;
        $ip = ClientIpResolver::get_client_ip();

        if (!empty($settings['security_rate_limit_ip_enabled']) && $ip !== '') {
            $limit = max(1, (int) ($settings['security_rate_limit_ip_attempts'] ?? 10));
            $attempts = RateLimiter::increment('coupon_validation', 'ip_' . md5($ip), $window);
            if ($attempts > $limit) {
                wp_send_json_error(sltr_t('Too many coupon validation attempts. Please try again later.'), 429);
            }
        }

        if (!empty($settings['security_rate_limit_email_enabled']) && $email !== '') {
            $limit = max(1, (int) ($settings['security_rate_limit_email_attempts'] ?? 5));
            $attempts = RateLimiter::increment('coupon_validation', 'email_' . md5(strtolower($email)), $window);
            if ($attempts > $limit) {
                wp_send_json_error(sltr_t('Too many coupon validation attempts. Please try again later.'), 429);
            }
        }
    }

    public function handle_public_token_action(): void
    {
        $action = $this->request->get_key('sltr_action', '');
        if ($action === '') { return; }
        $token = $this->request->get_text('sltr_token');
        if ($token === '') { return; }

        if ($action === 'cancel_booking') {
            $limited = $this->public_actions->enforce_rate_limit(PublicBookingActionSecurityService::ACTION_CANCEL, $token);
            if (is_wp_error($limited)) {
                $this->render_token_page(sltr_t('Too many attempts'), $limited->get_error_message(), 429);
                exit;
            }
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
                $this->render_cancel_confirmation($token);
                exit;
            }

            $posted_token = isset($_POST['sltr_token']) ? sanitize_text_field(wp_unslash((string) $_POST['sltr_token'])) : '';
            $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash((string) $_POST['_wpnonce'])) : '';
            if ($posted_token === '' || !hash_equals($token, $posted_token) || !$this->public_actions->verify_nonce($nonce, PublicBookingActionSecurityService::ACTION_CANCEL, $token)) {
                $this->render_booking_action_result('invalid_cancel_request');
                exit;
            }

            $result = $this->lifecycle->cancel_by_token($token);
            if (is_wp_error($result)) {
                $this->render_booking_action_result('cancel_failed', $result->get_error_message());
                exit;
            }

            $this->render_booking_action_result('cancelled');
            exit;
        }

        if ($action === 'reschedule_booking') {
            $limited = $this->public_actions->enforce_rate_limit(PublicBookingActionSecurityService::ACTION_RESCHEDULE, $token);
            if (is_wp_error($limited)) {
                $this->render_token_page(sltr_t('Too many attempts'), $limited->get_error_message(), 429);
                exit;
            }
            $this->handle_reschedule_action($token);
            exit;
        }
    }


    private function render_cancel_confirmation(string $token): void
    {
        $booking = (new BookingRepository())->get_by_cancellation_token($token);
        if (!$booking || $this->public_actions->is_token_expired($booking)) {
            $this->render_token_page(sltr_t('Invalid cancellation link'), sltr_t('This cancellation link is invalid or has expired.'));
            return;
        }

        $this->send_public_token_page_headers(200);
        $locale = $this->public_frontend_locale();
        $display_start_time_only = $this->display_start_time_only_for_booking($booking);
        $is_request = sanitize_key((string) ($booking['booking_mode'] ?? $booking['pricing_mode'] ?? '')) === 'simple';
        $request_package_title = '';
        if ($is_request) {
            $request_package = (new PackageRepository())->get_by_id(absint($booking['package_id'] ?? 0));
            $request_package_title = is_array($request_package) ? (string) ($request_package['title'] ?? '') : '';
        }
        $request_ui = $is_request ? \Slotera\Application\Services\Translations\BookingRequestTranslations::ui($locale) : [];
        $this->render_public_token_view('cancel-confirmation', [
            'title' => sltr_t('Confirm cancellation', 'frontend', $locale),
            'heading' => $is_request ? ($request_ui['cancel_heading'] ?? 'Cancel booking request?') : sltr_t('Cancel booking?', 'frontend', $locale),
            'message' => $is_request ? ($request_ui['cancel_message'] ?? 'Please confirm that you want to cancel this booking request.') : sltr_t('Please confirm that you want to cancel this booking. This action will notify the site owner and customer if email notifications are enabled.', 'frontend', $locale),
            'summary' => $is_request
                ? sprintf("%s
%s", sprintf((string) ($request_ui['request_label'] ?? 'Request #%d'), (int) ($booking['id'] ?? 0)), sprintf((string) ($request_ui['package_label'] ?? 'Package: %s'), ($request_package_title !== '' ? $request_package_title : ('#' . (int) ($booking['package_id'] ?? 0)))))
                : $this->booking_summary($booking, $display_start_time_only, $locale),
            'cancel_label' => $is_request ? ($request_ui['cancel_request'] ?? 'Cancel request') : sltr_t('Yes, cancel booking', 'frontend', $locale),
            'keep_label' => $is_request ? ($request_ui['keep_request'] ?? 'Keep request') : sltr_t('Keep booking', 'frontend', $locale),
            'locale' => $locale,
            'html_lang' => str_replace('_', '-', $locale),
            'action_url' => $this->public_action_context_url($this->public_actions->cancel_url($token)),
            'token' => $token,
            'nonce_field' => $this->public_actions->nonce_field(PublicBookingActionSecurityService::ACTION_CANCEL, $token),
            'rest_url' => esc_url_raw(rest_url('slotera/v1/bookings/cancel')),
            'rest_nonce' => $this->public_actions->create_nonce(PublicBookingActionSecurityService::ACTION_CANCEL, $token),
            'home_url' => $this->public_action_return_url(),
            'return_context' => $this->public_action_return_context(),
        ]);
    }

    private function handle_reschedule_action(string $token): void
    {
        $repo = new BookingRepository();
        $booking = $repo->get_by_reschedule_token($token);
        if (!$booking || $this->public_actions->is_token_expired($booking)) {
            $this->render_token_page(sltr_t('Invalid reschedule link'), sltr_t('This reschedule link is invalid or has expired.'));
            return;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->process_reschedule_submission($token);
            return;
        }

        $locale = $this->public_frontend_locale();
        if ($this->is_fixed_full_day_booking($booking)) {
            $available_dates = $this->available_full_day_reschedule_dates($booking, 366);
            $this->render_reschedule_form($token, $booking, '', [], '', $locale, $available_dates);
            return;
        }
        $selected_date_input = isset($_GET['sltr_date']) ? sanitize_text_field(wp_unslash((string) $_GET['sltr_date'])) : '';
        $selected_date = $this->normalize_public_date_input($selected_date_input, $locale);
        $slots = [];
        $error = '';
        if ($selected_date !== '') {
            if (!$this->is_valid_date($selected_date)) {
                $error = sltr_t('Please choose a valid date.', 'frontend', $locale);
                $selected_date = '';
            } else {
                $slots = (new AvailabilityService())->get_available_slots_for_package_date(
                    absint($booking['package_id'] ?? 0),
                    $selected_date,
                    absint($booking['resource_id'] ?? 0),
                    absint($booking['staff_id'] ?? 0),
                    absint($booking['id'] ?? 0)
                );
                if (empty($slots)) {
                    $error = sltr_t('No available slots were found for this date. Please choose another date.', 'frontend', $locale);
                }
            }
        }

        $this->render_reschedule_form($token, $booking, $selected_date, $slots, $error, $locale);
    }

    private function process_reschedule_submission(string $token): void
    {
        $existing_booking = (new BookingRepository())->get_by_reschedule_token($token);
        $display_start_time_only = is_array($existing_booking) && $this->display_start_time_only_for_booking($existing_booking);
        $full_day_booking = is_array($existing_booking) && $this->is_fixed_full_day_booking($existing_booking);
        $posted_token = isset($_POST['sltr_token']) ? sanitize_text_field(wp_unslash((string) $_POST['sltr_token'])) : '';
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash((string) $_POST['_wpnonce'])) : '';
        $date = isset($_POST['sltr_date']) ? sanitize_text_field(wp_unslash((string) $_POST['sltr_date'])) : '';
        $slot = isset($_POST['sltr_slot']) ? sanitize_text_field(wp_unslash((string) $_POST['sltr_slot'])) : '';

        if ($posted_token === '' || !hash_equals($token, $posted_token) || !$this->public_actions->verify_nonce($nonce, PublicBookingActionSecurityService::ACTION_RESCHEDULE, $token)) {
            $this->render_booking_action_result('invalid_reschedule_request');
            exit;
        }

        [$start, $end] = $full_day_booking
            ? ['00:00:00', '00:00:00']
            : array_pad(explode('|', $slot, 2), 2, '');
        $result = $this->lifecycle->reschedule_by_token($token, $date, $start, $end);
        if (is_wp_error($result)) {
            $booking = (new BookingRepository())->get_by_reschedule_token($token);
            $slots = $this->is_valid_date($date) && $booking ? (new AvailabilityService())->get_available_slots_for_package_date(
                absint($booking['package_id'] ?? 0),
                $date,
                absint($booking['resource_id'] ?? 0),
                absint($booking['staff_id'] ?? 0),
                absint($booking['id'] ?? 0)
            ) : [];
            $available_dates = $full_day_booking && $booking ? $this->available_full_day_reschedule_dates($booking, 366) : [];
            $this->render_reschedule_form($token, $booking ?: [], $date, $slots, $result->get_error_message(), null, $available_dates);
            return;
        }

        $this->render_booking_action_result('rescheduled', '', [
            'date' => $this->format_date($date),
            'time' => $full_day_booking ? '' : $this->format_time_range($start, $end, $display_start_time_only),
        ]);
        exit;
    }

    private function render_reschedule_form(string $token, array $booking, string $selected_date, array $slots, string $error = '', ?string $locale = null, array $available_dates = []): void
    {
        $this->send_public_token_page_headers(200);
        $locale = $locale ?: $this->public_frontend_locale();
        $display_start_time_only = $this->display_start_time_only_for_booking($booking);
        $this->render_public_token_view('reschedule-form', [
            'title' => sltr_t('Reschedule booking', 'frontend', $locale),
            'heading' => sltr_t('Reschedule booking', 'frontend', $locale),
            'base_url' => $this->public_action_context_url($this->public_actions->reschedule_url($token)),
            'home_url' => $this->public_action_return_url(),
            'return_context' => $this->public_action_return_context(),
            'token' => $token,
            'min_date' => (new \DateTimeImmutable('today', wp_timezone()))->format('Y-m-d'),
            'summary' => $this->booking_summary($booking, $display_start_time_only, $locale),
            'selected_date' => $selected_date,
            'selected_date_display' => $this->format_public_date_input($selected_date, $locale),
            'date_placeholder' => $this->date_input_placeholder($locale),
                'date_order' => $this->date_input_order($locale),
            'locale' => $locale,
            'html_lang' => str_replace('_', '-', $locale),
            'slots' => $slots,
            'full_day_booking' => $this->is_fixed_full_day_booking($booking),
            'available_dates' => $available_dates,
            'display_start_time_only' => $display_start_time_only,
            'error' => $error,
            'nonce_field' => $this->public_actions->nonce_field(PublicBookingActionSecurityService::ACTION_RESCHEDULE, $token),
            'rest_url' => esc_url_raw(rest_url('slotera/v1/bookings/reschedule')),
            'rest_nonce' => $this->public_actions->create_nonce(PublicBookingActionSecurityService::ACTION_RESCHEDULE, $token),
        ]);
    }

    private function render_booking_action_result(string $status, string $detail = '', array $context = []): void
    {
        $site_name = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        if ($site_name === '') {
            $site_name = sltr_t('site');
        }

        $title = sltr_t('Booking updated');
        $heading = sltr_t('Booking updated');
        $message = sltr_t('Your booking request has been processed.');
        $status_code = 200;

        if ($status === 'cancelled') {
            $title = sltr_t('Booking cancelled');
            $heading = sltr_t('Booking cancelled');
            $message = sltr_t('Your booking has been cancelled. A confirmation email has been sent if email notifications are enabled.');
        } elseif ($status === 'rescheduled') {
            $title = sltr_t('Booking rescheduled');
            $heading = sltr_t('Booking rescheduled');
            $message = sltr_t('Your booking has been rescheduled. A confirmation email has been sent if email notifications are enabled.');
            $date = trim((string) ($context['date'] ?? ''));
            $time = trim((string) ($context['time'] ?? ''));
            if ($date !== '' || $time !== '') {
                $message .= ' ' . sprintf(
                    /* translators: 1: booking date, 2: booking time range */
                    sltr_t('New booking time: %1$s %2$s'),
                    $date,
                    $time
                );
            }
        } elseif ($status === 'invalid_cancel_request') {
            $title = sltr_t('Cancellation could not be confirmed');
            $heading = sltr_t('Cancellation could not be confirmed');
            $message = sltr_t('The cancellation request is invalid or expired. Please open the cancellation link from your latest email and try again.');
            $status_code = 400;
        } elseif ($status === 'invalid_reschedule_request') {
            $title = sltr_t('Reschedule could not be confirmed');
            $heading = sltr_t('Reschedule could not be confirmed');
            $message = sltr_t('The reschedule request is invalid or expired. Please open the reschedule link from your latest email and try again.');
            $status_code = 400;
        } elseif ($status === 'cancel_failed') {
            $title = sltr_t('Cancellation failed');
            $heading = sltr_t('Cancellation failed');
            $message = $detail !== '' ? $detail : sltr_t('We could not cancel this booking. Please try again or contact the site owner.');
            $status_code = 400;
        }

        $this->send_public_token_page_headers($status_code);
        $this->render_public_token_view('message', [
            'title' => $title,
            'heading' => $heading,
            'message' => $message,
            'home_url' => $this->booking_action_destination_url($status),
            'button_label' => $this->booking_action_button_label($status, $site_name),
        ]);
    }



    private function booking_action_destination_url(string $status): string
    {
        if ($this->public_action_return_context() === 'account' || in_array($status, ['cancelled', 'rescheduled'], true)) {
            $account_url = (new AccountMagicLinkService())->account_url();
            if (is_string($account_url) && $account_url !== '') {
                return $account_url;
            }
        }

        return home_url('/');
    }

    private function booking_action_button_label(string $status, string $site_name): string
    {
        if ($this->public_action_return_context() === 'account' || in_array($status, ['cancelled', 'rescheduled'], true)) {
            return sltr_t('Open client account');
        }

        return sprintf(
            /* translators: %s: site name */
            sltr_t('Go to %s'),
            $site_name
        );
    }

    private function public_action_return_context(): string
    {
        $value = isset($_REQUEST['sltr_return']) ? sanitize_key((string) wp_unslash($_REQUEST['sltr_return'])) : '';
        return $value === 'account' ? 'account' : '';
    }

    private function public_action_return_url(): string
    {
        if ($this->public_action_return_context() === 'account') {
            $account_url = (new AccountMagicLinkService())->account_url();
            if (is_string($account_url) && $account_url !== '') {
                return $account_url;
            }
        }

        return home_url('/');
    }

    private function public_action_context_url(string $url): string
    {
        return $this->public_action_return_context() === 'account'
            ? add_query_arg('sltr_return', 'account', $url)
            : $url;
    }

    private function render_token_page(string $title, string $message, int $status_code = 404): void
    {
        $this->send_public_token_page_headers($status_code);
        $this->render_public_token_view('message', [
            'title' => $title,
            'heading' => $title,
            'message' => $message,
            'home_url' => $this->public_action_return_url(),
            'button_label' => $this->public_action_return_context() === 'account' ? sltr_t('Open client account') : '',
        ]);
    }

    private function render_public_token_view(string $template, array $data): void
    {
        $template = sanitize_key($template);
        $view = SLTR_PLUGIN_DIR . 'includes/Frontend/Views/public-token/' . $template . '.php';
        if (!is_readable($view)) {
            $view = SLTR_PLUGIN_DIR . 'includes/Frontend/Views/public-token/message.php';
            $data = [
                'title' => sltr_t('Booking link unavailable'),
                'heading' => sltr_t('Booking link unavailable'),
                'message' => sltr_t('This booking action page is not available.'),
                'home_url' => home_url('/'),
            ];
        }

        extract($data, EXTR_SKIP);
        include $view;
    }

    private function send_public_token_page_headers(int $status_code): void
    {
        status_header($status_code);
        nocache_headers();

        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow, noarchive', false);
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; script-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
    }

    private function is_fixed_full_day_booking(array $booking): bool
    {
        $package_id = absint($booking['package_id'] ?? 0);
        $package = $package_id > 0 ? (new PackageRepository())->get_by_id($package_id) : null;
        if (!is_array($package) || sanitize_key((string) ($package['booking_mode'] ?? '')) !== 'fixed') {
            return false;
        }
        $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        return is_array($configs) && !empty($configs['fixed']['full_day_booking']);
    }

    private function available_full_day_reschedule_dates(array $booking, int $lookahead_days = 366): array
    {
        $package_id = absint($booking['package_id'] ?? 0);
        $start_date = sanitize_text_field((string) ($booking['booking_date'] ?? ''));
        $end_date = sanitize_text_field((string) ($booking['end_date'] ?? ''));
        if ($package_id <= 0 || !$this->is_valid_date($start_date) || !$this->is_valid_date($end_date) || $end_date <= $start_date) {
            return [];
        }
        try {
            $timezone = wp_timezone();
            $duration_days = max(1, (int) (new \DateTimeImmutable($start_date, $timezone))->diff(new \DateTimeImmutable($end_date, $timezone))->days);
            $cursor = new \DateTimeImmutable('tomorrow', $timezone);
        } catch (\Throwable $e) {
            return [];
        }

        $availability = new AvailabilityService();
        $available = [];
        $limit = max(1, min(730, $lookahead_days));
        for ($i = 0; $i < $limit; $i++) {
            $candidate = $cursor->modify('+' . $i . ' days');
            $candidate_date = $candidate->format('Y-m-d');
            $candidate_end = $candidate->modify('+' . $duration_days . ' days')->format('Y-m-d');
            if (empty($availability->get_available_slots_for_package_date(
                $package_id,
                $candidate_date,
                absint($booking['resource_id'] ?? 0),
                absint($booking['staff_id'] ?? 0),
                absint($booking['id'] ?? 0)
            ))) {
                continue;
            }
            if ($availability->timed_range_is_available(
                $package_id,
                $candidate_date,
                '00:00:00',
                $candidate_end,
                '00:00:00',
                absint($booking['resource_id'] ?? 0),
                absint($booking['staff_id'] ?? 0),
                absint($booking['id'] ?? 0)
            )) {
                $available[] = $candidate_date;
            }
        }
        return $available;
    }

    private function booking_summary(array $booking, bool $display_start_time_only = false, ?string $locale = null): string
    {
        $locale = $locale ?: $this->public_frontend_locale();
        $package = (new PackageRepository())->get_by_id(absint($booking['package_id'] ?? 0));
        $display = function_exists('sltr_booking_display_data')
            ? sltr_booking_display_data($booking, is_array($package) ? $package : [])
            : ['date' => $this->format_date((string) ($booking['booking_date'] ?? ''), $locale), 'time' => ''];

        $date = (string) ($display['date'] ?? '');
        $time = (string) ($display['time'] ?? '');
        if ($date === '' && $time === '') { return ''; }
        if ($time === '') { return $date; }

        // Keep the legacy three-part translation key discoverable for release
        // compatibility; scheduled events now use the normalized two-part summary.
        // sltr__('frontend.current_booking_1_s_2_s_3_s', $locale)
        return sprintf(
            /* translators: 1: booking date or date range, 2: booking time or time range */
            sltr__('frontend.current_booking_1_s_2_s', $locale),
            $date,
            $time
        );
    }

    private function format_time_range(string $start, string $end, bool $display_start_time_only = false): string
    {
        if ($display_start_time_only) {
            return $this->format_time($start);
        }

        return $this->format_time($start) . '–' . $this->format_time($end);
    }

    private function display_start_time_only_for_booking(array $booking): bool
    {
        $package_id = absint($booking['package_id'] ?? 0);
        if ($package_id < 1) {
            return false;
        }

        $package = (new PackageRepository())->get_by_id($package_id);
        if (!is_array($package)) {
            return false;
        }

        $mode = sanitize_key((string) ($package['booking_mode'] ?? ''));
        if ($mode !== 'flex') {
            return false;
        }

        $configs = json_decode((string) ($package['mode_configs_json'] ?? ''), true);
        return is_array($configs)
            && isset($configs['flex'])
            && is_array($configs['flex'])
            && !empty($configs['flex']['display_start_time_only']);
    }

    private function format_date(string $date, ?string $locale = null): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        try {
            $dt = new \DateTimeImmutable($date, function_exists('wp_timezone') ? wp_timezone() : null);
            $locale = $locale ?: $this->public_frontend_locale();
            if (class_exists('IntlDateFormatter')) {
                $formatter = new \IntlDateFormatter(
                    str_replace('_', '-', $locale),
                    \IntlDateFormatter::LONG,
                    \IntlDateFormatter::NONE,
                    function_exists('wp_timezone_string') ? wp_timezone_string() : null
                );
                $formatted = $formatter->format($dt);
                if (is_string($formatted) && $formatted !== '') {
                    return $formatted;
                }
            }
            return $this->format_public_date_input($dt->format('Y-m-d'), $locale);
        } catch (\Throwable $e) {
            return $date;
        }
    }

    private function public_frontend_locale(): string
    {
        return (new TranslationService())->locale_for_group('frontend');
    }

    private function date_input_order(string $locale): string
    {
        $locale = strtolower(str_replace('-', '_', $locale));
        if (in_array($locale, ['en_us'], true)) { return 'mdy'; }
        return 'dmy';
    }

    private function date_input_placeholder(string $locale): string
    {
        $order = $this->date_input_order($locale);
        if ($order === 'mdy') { return 'mm/dd/yyyy'; }
        if ($order === 'ymd') { return 'yyyy/mm/dd'; }
        $locale = strtolower(str_replace('-', '_', $locale));
        return match ($locale) {
            'de_de' => 'TT.MM.JJJJ',
            'et', 'et_ee' => 'pp.kk.aaaa',
            'fr_fr' => 'jj/mm/aaaa',
            'it_it' => 'gg/mm/aaaa',
            'es_es', 'pt_br', 'pt_pt' => 'dd/mm/aaaa',
            'da_dk', 'sv_se' => 'dd/mm/åååå',
            'nl_nl' => 'dd-mm-jjjj',
            'fi', 'fi_fi' => 'pp.kk.vvvv',
            'ru_ru' => 'дд.мм.гггг',
            default => 'dd/mm/yyyy',
        };
    }

    private function format_public_date_input(string $date, string $locale): string
    {
        if (!preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})$/', $date, $m)) { return $date; }
        $order = $this->date_input_order($locale);
        if ($order === 'mdy') { return $m[2] . '/' . $m[3] . '/' . $m[1]; }
        if ($order === 'ymd') { return $m[1] . '/' . $m[2] . '/' . $m[3]; }
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }

    private function normalize_public_date_input(string $value, string $locale): string
    {
        $value = trim($value);
        if ($value === '') { return ''; }
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $value)) { return $value; }
        if (!preg_match('/^(\\d{1,4})[.\\/-](\\d{1,2})[.\\/-](\\d{1,4})$/', $value, $m)) { return $value; }
        $order = $this->date_input_order($locale);
        if ($order === 'mdy') { [$month, $day, $year] = [(int)$m[1], (int)$m[2], (int)$m[3]]; }
        elseif ($order === 'ymd') { [$year, $month, $day] = [(int)$m[1], (int)$m[2], (int)$m[3]]; }
        else { [$day, $month, $year] = [(int)$m[1], (int)$m[2], (int)$m[3]]; }
        if ($year < 100) { $year += 2000; }
        if (!checkdate($month, $day, $year)) { return $value; }
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function format_time(string $time): string
    {
        return preg_match('/^\d{2}:\d{2}/', $time) ? substr($time, 0, 5) : $time;
    }

    private function is_valid_date(string $date): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date;
    }

    private function booking_data_from_request(): array
    {
        $payload = isset($_POST) && is_array($_POST) ? wp_unslash($_POST) : [];
        return PublicBookingRequestNormalizer::normalize($payload, 'frontend');
    }}
