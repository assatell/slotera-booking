<?php

declare(strict_types=1);

namespace Slotera\Admin\Controllers;

use Slotera\Application\Services\RequestValidator;
use Slotera\Application\Services\BusinessValidator;
use Slotera\Infrastructure\Repositories\EventRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class EventController
{
    private EventRepository $repo;
    private RequestValidator $request;

    public function __construct(?EventRepository $repo = null, ?RequestValidator $request = null)
    {
        $this->repo = $repo ?: new EventRepository();
        $this->request = $request ?: new RequestValidator();
    }

    public function register(): void
    {
        add_action('admin_post_sltr_save_event', [$this, 'save']);
        add_action('admin_post_sltr_delete_event', [$this, 'delete']);
    }

    public function save(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);
        $this->request->verify_admin_nonce('sltr_save_event');

        $package_id = $this->request->post_int('return_package_id');
        $package = $package_id > 0 ? (new PackageRepository())->get_by_id($package_id) : null;
        $package_title = is_array($package) ? (string) ($package['title'] ?? '') : '';

        $payment_options = isset($_POST['payment_options']) && is_array($_POST['payment_options']) ? array_map('sanitize_key', wp_unslash($_POST['payment_options'])) : [];
        $payment_options = array_values(array_intersect($payment_options, ['booking_only', 'deposit_payment', 'full_payment']));
        $has_booking = in_array('booking_only', $payment_options, true);
        $has_deposit = in_array('deposit_payment', $payment_options, true);
        $has_full = in_array('full_payment', $payment_options, true);
        $event_payment_policy = $has_booking && $has_deposit && $has_full ? 'all_options'
            : ($has_full && $has_deposit ? 'full_or_deposit'
            : ($has_booking && $has_full ? 'booking_or_full'
            : ($has_booking && $has_deposit ? 'booking_or_deposit'
            : ($has_full ? 'full_payment' : ($has_deposit ? 'deposit_payment' : 'booking_only')))));

        $data = [
            'package_id' => $package_id,
            'title' => $package_title,
            'event_date' => BusinessValidator::date_or_today($this->request->post_text('event_date', current_time('Y-m-d'))),
            'end_date' => BusinessValidator::date_or_today($this->request->post_text('end_date', current_time('Y-m-d'))),
            'use_time' => $this->request->post_bool('use_time'),
            'start_time' => BusinessValidator::time($this->request->post_text('start_time', '09:00'), '09:00'),
            'end_time' => BusinessValidator::time($this->request->post_text('end_time', '10:00'), '10:00'),
            'timezone' => $this->request->post_text('timezone', wp_timezone_string()),
            'capacity' => BusinessValidator::capacity($this->request->post_int('capacity', 1)),
            'price_override' => (string) BusinessValidator::money($this->request->post_text('price_override', '0')),
            'discount_type' => $this->request->post_key('discount_type', 'none'),
            'discount_value' => (string) BusinessValidator::money($this->request->post_text('discount_value', '0')),
            'allow_coupons' => $this->request->post_bool('allow_coupons'),
            'payment_policy' => $event_payment_policy,
            'deposit_type' => $this->request->post_key('deposit_type', 'percent'),
            'deposit_value' => (string) BusinessValidator::money($this->request->post_text('deposit_value', '30')),
            'location' => $this->request->post_text('location'),
            'status' => $this->request->post_key('status', 'scheduled'),
            'reminder_profile' => $this->request->post_key('reminder_profile', 'default'),
            'automation_profile' => $this->request->post_key('automation_profile', 'default'),
            'is_active' => $this->request->post_bool('is_active'),
        ];

        if ($data['end_date'] < $data['event_date']) { $data['end_date'] = $data['event_date']; }
        if ($data['use_time'] && $data['end_date'] === $data['event_date'] && $data['end_time'] <= $data['start_time']) {
            $data['end_time'] = BusinessValidator::time(date('H:i', strtotime($data['start_time'] . ' +1 hour')), '10:00');
        }
        $id = $this->request->post_int('id');
        if ($id <= 0 && $package_id > 0) {
            $existing = $this->repo->get_first_for_package($package_id);
            $id = is_array($existing) ? (int) ($existing['id'] ?? 0) : 0;
        }
        $existing_event = $id > 0 ? $this->repo->get_by_id($id) : null;
        $data['booked_count'] = is_array($existing_event)
            ? min(max(0, (int) ($existing_event['booked_count'] ?? 0)), (int) $data['capacity'])
            : 0;

        $saved = false;
        if ($id > 0) {
            $saved = $this->repo->update($id, $data);
        } else {
            $id = $this->repo->create($data);
            $saved = $id > 0;
        }

        $return_package_id = $package_id;
        if (!$saved) {
            global $wpdb;
            if (function_exists('error_log')) {
                error_log('Slotera event save failed: ' . (string) ($wpdb->last_error ?? 'unknown database error'));
            }
            $error_url = admin_url('admin.php?page=slotera-events&action=' . ($id > 0 ? 'edit&id=' . $id : 'new') . '&package_id=' . $return_package_id . '&return_package_id=' . $return_package_id . '&sltr_message=event_save_failed');
            wp_safe_redirect($error_url);
            exit;
        }

        do_action('sltr_data_changed', 'event_saved', ['event_id' => $id, 'package_id' => $return_package_id]);
        $redirect = $return_package_id > 0 ? admin_url('admin.php?page=slotera-packages&sltr_message=event_saved') : admin_url('admin.php?page=slotera-events');
        wp_safe_redirect($redirect);
        exit;
    }

    public function delete(): void
    {
        $this->request->require_admin(\Slotera\Core\Capabilities::MANAGE_PACKAGES);
        $id = $this->request->get_int('id');
        if ($id <= 0) {
            wp_safe_redirect(admin_url('admin.php?page=slotera-packages'));
            exit;
        }
        $this->request->verify_admin_nonce('sltr_delete_event_' . $id);
        $this->repo->delete($id);
        $return_package_id = $this->request->get_int('return_package_id');
        $redirect = $return_package_id > 0 ? admin_url('admin.php?page=slotera-events&package_id=' . $return_package_id . '&return_package_id=' . $return_package_id . '&sltr_message=event_deleted') : admin_url('admin.php?page=slotera-events');
        wp_safe_redirect($redirect);
        exit;
    }
}
