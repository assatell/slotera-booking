<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;
use Slotera\Core\Migrator;

if (!defined('ABSPATH')) {
    exit;
}

final class Version_1_0_433 implements MigrationInterface
{
    public static function apply(): void
    {
        self::add_booking_indexes();
        self::add_log_indexes();
        self::add_availability_indexes();
    }

    private static function add_booking_indexes(): void
    {
        $bookings = Database::bookings_table();

        Migrator::maybe_add_index($bookings, 'sltr_booking_date_status_time', 'KEY sltr_booking_date_status_time (booking_date,status,start_time)');
        Migrator::maybe_add_index($bookings, 'sltr_booking_status_date_time', 'KEY sltr_booking_status_date_time (status,booking_date,start_time)');
        Migrator::maybe_add_index($bookings, 'sltr_booking_package_inventory_day', 'KEY sltr_booking_package_inventory_day (package_id,resource_id,staff_id,booking_date,status)');
        Migrator::maybe_add_index($bookings, 'sltr_booking_resource_day_time', 'KEY sltr_booking_resource_day_time (resource_id,booking_date,start_time,end_time,status)');
        Migrator::maybe_add_index($bookings, 'sltr_booking_staff_day_time', 'KEY sltr_booking_staff_day_time (staff_id,booking_date,start_time,end_time,status)');
        Migrator::maybe_add_index($bookings, 'sltr_booking_customer_date', 'KEY sltr_booking_customer_date (customer_email,booking_date)');
        Migrator::maybe_add_index($bookings, 'sltr_booking_completed_marketing', 'KEY sltr_booking_completed_marketing (status,completed_at,booking_date)');
    }

    private static function add_log_indexes(): void
    {
        Migrator::maybe_add_index(Database::activity_log_table(), 'sltr_activity_object_created', 'KEY sltr_activity_object_created (object_type,object_id,created_at)');
        Migrator::maybe_add_index(Database::activity_log_table(), 'sltr_activity_event_created', 'KEY sltr_activity_event_created (event,created_at)');
        Migrator::maybe_add_index(Database::activity_log_table(), 'sltr_activity_status_created', 'KEY sltr_activity_status_created (status,created_at)');

        Migrator::maybe_add_index(Database::booking_history_table(), 'sltr_history_booking_created', 'KEY sltr_history_booking_created (booking_id,created_at)');
        Migrator::maybe_add_index(Database::booking_history_table(), 'sltr_history_event_created', 'KEY sltr_history_event_created (event,created_at)');
    }

    private static function add_availability_indexes(): void
    {
        Migrator::maybe_add_index(Database::working_hours_table(), 'sltr_hours_scope_weekday_enabled', 'KEY sltr_hours_scope_weekday_enabled (scope_type,scope_id,weekday,is_enabled)');
        Migrator::maybe_add_index(Database::events_table(), 'sltr_event_package_date_active', 'KEY sltr_event_package_date_active (package_id,event_date,is_active,status)');
        Migrator::maybe_add_index(Database::coupons_table(), 'sltr_coupon_active_expires', 'KEY sltr_coupon_active_expires (is_active,expires_at)');
    }
}
