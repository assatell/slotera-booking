<?php

declare(strict_types=1);

namespace Slotera\Application\Services\Translations;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminTranslationStrings
{
    public static function strings(): array
    {
        return array (
  'admin.dashboard.today_bookings' => 
  array (
    'group' => 'admin',
    'label' => 'Today bookings',
    'default' => 'Today bookings',
  ),
  'admin.dashboard.upcoming' => 
  array (
    'group' => 'admin',
    'label' => 'Upcoming',
    'default' => 'Upcoming',
  ),
  'admin.dashboard.pending_payments' => 
  array (
    'group' => 'admin',
    'label' => 'Pending payments',
    'default' => 'Pending payments',
  ),
  'admin.dashboard.cancelled' => 
  array (
    'group' => 'admin',
    'label' => 'Cancelled',
    'default' => 'Cancelled',
  ),
  'admin.dashboard.revenue' => 
  array (
    'group' => 'admin',
    'label' => 'Revenue',
    'default' => 'Revenue',
  ),
  'admin.dashboard.title' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera Dashboard',
    'default' => 'Slotera Dashboard',
  ),
  'admin.dashboard.upcoming_bookings' => 
  array (
    'group' => 'admin',
    'label' => 'Upcoming bookings',
    'default' => 'Upcoming bookings',
  ),
  'admin.dashboard.no_upcoming_bookings' => 
  array (
    'group' => 'admin',
    'label' => 'No upcoming bookings.',
    'default' => 'No upcoming bookings.',
  ),
  'admin.dashboard.no_matching_bookings' => 
  array (
    'group' => 'admin',
    'label' => 'No matching bookings.',
    'default' => 'No matching bookings.',
  ),
  'admin.dashboard.recent_activity' => 
  array (
    'group' => 'admin',
    'label' => 'Recent activity',
    'default' => 'Recent activity',
  ),
  'admin.dashboard.no_recent_activity' => 
  array (
    'group' => 'admin',
    'label' => 'No recent activity.',
    'default' => 'No recent activity.',
  ),
  'admin.common.client' => 
  array (
    'group' => 'admin',
    'label' => 'Client',
    'default' => 'Client',
  ),
  'admin.common.date' => 
  array (
    'group' => 'admin',
    'label' => 'Date',
    'default' => 'Date',
  ),
  'admin.common.time' => 
  array (
    'group' => 'admin',
    'label' => 'Time',
    'default' => 'Time',
  ),
  'admin.common.status' => 
  array (
    'group' => 'admin',
    'label' => 'Status',
    'default' => 'Status',
  ),
  'admin.package.edit_package' => 
  array (
    'group' => 'admin',
    'label' => 'Edit package',
    'default' => 'Edit package',
  ),
  'admin.package.new_package' => 
  array (
    'group' => 'admin',
    'label' => 'New package',
    'default' => 'New package',
  ),
  'admin.package.setup_guide' => 
  array (
    'group' => 'admin',
    'label' => 'Package setup guide',
    'default' => 'Package setup guide',
  ),
  'admin.package.setup_guide_text' => 
  array (
    'group' => 'admin',
    'label' => 'Start with the public package details, choose one booking block, then configu...',
    'default' => 'Start with the public package details, choose one booking block, then configure availability, pricing, payment policy and buffers. Only the active booking block is used on the frontend.',
    'textarea' => true,
  ),
  'admin.package.title' => 
  array (
    'group' => 'admin',
    'label' => 'Title',
    'default' => 'Title',
  ),
  'admin.package.slug' => 
  array (
    'group' => 'admin',
    'label' => 'Slug',
    'default' => 'Slug',
  ),
  'admin.package.auto_generated_from_title' => 
  array (
    'group' => 'admin',
    'label' => 'auto-generated from title',
    'default' => 'auto-generated from title',
  ),
  'admin.package.solo_page_layout' => 
  array (
    'group' => 'admin',
    'label' => 'Solo page layout',
    'default' => 'Solo page layout',
  ),
  'admin.package.description' => 
  array (
    'group' => 'admin',
    'label' => 'Description',
    'default' => 'Description',
  ),
  'admin.package.solo_right_content_help' => 
  array (
    'group' => 'admin',
    'label' => 'This field controls only the right 2/3 column of the solo package page. Leave...',
    'default' => 'This field controls only the right 2/3 column of the solo package page. Leave it empty to automatically show configured right-side blocks, or insert shortcodes to control the order.',
    'textarea' => true,
  ),
  'admin.invalid_booking' => 
  array (
    'group' => 'admin',
    'label' => 'Invalid booking.',
    'default' => 'Invalid booking.',
  ),
  'admin.you_do_not_have_permission_to_manage_booking_form_settings' => 
  array (
    'group' => 'admin',
    'label' => 'You do not have permission to manage booking form settings.',
    'default' => 'You do not have permission to manage booking form settings.',
  ),
  'admin.external_url_was_rejected' => 
  array (
    'group' => 'admin',
    'label' => 'External URL was rejected.',
    'default' => 'External URL was rejected.',
  ),
  'admin.invalid_webhook_url' => 
  array (
    'group' => 'admin',
    'label' => 'Invalid webhook URL.',
    'default' => 'Invalid webhook URL.',
  ),
  'admin.you_do_not_have_permission_to_do_this' => 
  array (
    'group' => 'admin',
    'label' => 'You do not have permission to do this.',
    'default' => 'You do not have permission to do this.',
  ),
  'admin.security_check_failed' => 
  array (
    'group' => 'admin',
    'label' => 'Security check failed.',
    'default' => 'Security check failed.',
  ),
  'admin.no_csv_file_uploaded' => 
  array (
    'group' => 'admin',
    'label' => 'No CSV file uploaded.',
    'default' => 'No CSV file uploaded.',
  ),
  'admin.safe_import_validation_finished_review_the_result_and_import_valid_row' => 
  array (
    'group' => 'admin',
    'label' => 'Safe import validation finished. Review the result and import valid rows only.',
    'default' => 'Safe import validation finished. Review the result and import valid rows only.',
  ),
  'admin.safe_import_validation_finished_no_rows_can_be_imported' => 
  array (
    'group' => 'admin',
    'label' => 'Safe import validation finished. No rows can be imported.',
    'default' => 'Safe import validation finished. No rows can be imported.',
  ),
  'admin.import_session_expired_please_upload_and_validate_the_csv_again' => 
  array (
    'group' => 'admin',
    'label' => 'Import session expired. Please upload and validate the CSV again.',
    'default' => 'Import session expired. Please upload and validate the CSV again.',
  ),
  'admin.database_insert_failed' => 
  array (
    'group' => 'admin',
    'label' => 'Database insert failed.',
    'default' => 'Database insert failed.',
  ),
  'admin.safe_csv_import_finished' => 
  array (
    'group' => 'admin',
    'label' => 'Safe CSV import finished.',
    'default' => 'Safe CSV import finished.',
  ),
  'admin.could_not_read_uploaded_csv' => 
  array (
    'group' => 'admin',
    'label' => 'Could not read uploaded CSV.',
    'default' => 'Could not read uploaded CSV.',
  ),
  'admin.csv_header_row_is_missing' => 
  array (
    'group' => 'admin',
    'label' => 'CSV header row is missing.',
    'default' => 'CSV header row is missing.',
  ),
  'admin.import_limit_is_1000_rows_per_upload' => 
  array (
    'group' => 'admin',
    'label' => 'Import limit is 1000 rows per upload.',
    'default' => 'Import limit is 1000 rows per upload.',
  ),
  'admin.csv_row_has_more_fields_than_the_header_row' => 
  array (
    'group' => 'admin',
    'label' => 'CSV row has more fields than the header row.',
    'default' => 'CSV row has more fields than the header row.',
  ),
  'admin.csv_field_is_too_large' => 
  array (
    'group' => 'admin',
    'label' => 'CSV field is too large.',
    'default' => 'CSV field is too large.',
  ),
  'admin.package_not_found' => 
  array (
    'group' => 'admin',
    'label' => 'Package not found.',
    'default' => 'Package not found.',
  ),
  'admin.package_not_found_or_inactive' => 
  array (
    'group' => 'admin',
    'label' => 'Package not found or inactive.',
    'default' => 'Package not found or inactive.',
  ),
  'admin.invalid_email' => 
  array (
    'group' => 'admin',
    'label' => 'Invalid email.',
    'default' => 'Invalid email.',
  ),
  'admin.customer_name_is_required' => 
  array (
    'group' => 'admin',
    'label' => 'Customer name is required.',
    'default' => 'Customer name is required.',
  ),
  'admin.invalid_booking_date' => 
  array (
    'group' => 'admin',
    'label' => 'Invalid booking date.',
    'default' => 'Invalid booking date.',
  ),
  'admin.past_dates_cannot_be_imported_by_the_safe_importer' => 
  array (
    'group' => 'admin',
    'label' => 'Past dates cannot be imported by the safe importer.',
    'default' => 'Past dates cannot be imported by the safe importer.',
  ),
  'admin.invalid_time_format' => 
  array (
    'group' => 'admin',
    'label' => 'Invalid time format.',
    'default' => 'Invalid time format.',
  ),
  'admin.booking_status_cannot_be_imported_by_the_safe_importer' => 
  array (
    'group' => 'admin',
    'label' => 'Booking status cannot be imported by the safe importer.',
    'default' => 'Booking status cannot be imported by the safe importer.',
  ),
  'admin.payment_status_cannot_be_imported_paid_refunded_failed_states_must_com' => 
  array (
    'group' => 'admin',
    'label' => 'Payment status cannot be imported. Paid/refunded/failed states must come from...',
    'default' => 'Payment status cannot be imported. Paid/refunded/failed states must come from payments or manual admin confirmation.',
    'textarea' => true,
  ),
  'admin.payment_fields_cannot_be_imported_by_the_safe_importer' => 
  array (
    'group' => 'admin',
    'label' => 'Payment fields cannot be imported by the safe importer.',
    'default' => 'Payment fields cannot be imported by the safe importer.',
  ),
  'admin.invalid_date_range' => 
  array (
    'group' => 'admin',
    'label' => 'Invalid date range.',
    'default' => 'Invalid date range.',
  ),
  'admin.room_unit_is_required_for_date_range_inventory_imports' => 
  array (
    'group' => 'admin',
    'label' => 'Room/unit is required for date range inventory imports.',
    'default' => 'Room/unit is required for date range inventory imports.',
  ),
  'admin.slot_is_unavailable' => 
  array (
    'group' => 'admin',
    'label' => 'Slot is unavailable.',
    'default' => 'Slot is unavailable.',
  ),
  'admin.package_capacity_is_full' => 
  array (
    'group' => 'admin',
    'label' => 'Package capacity is full.',
    'default' => 'Package capacity is full.',
  ),
  'admin.start_and_end_time_are_required' => 
  array (
    'group' => 'admin',
    'label' => 'Start and end time are required.',
    'default' => 'Start and end time are required.',
  ),
  'admin.duplicate_booking' => 
  array (
    'group' => 'admin',
    'label' => 'Duplicate booking.',
    'default' => 'Duplicate booking.',
  ),
  'admin.csv_upload_failed_please_try_again' => 
  array (
    'group' => 'admin',
    'label' => 'CSV upload failed. Please try again.',
    'default' => 'CSV upload failed. Please try again.',
  ),
  'admin.csv_file_must_be_smaller_than_2_mb' => 
  array (
    'group' => 'admin',
    'label' => 'CSV file must be smaller than 2 MB.',
    'default' => 'CSV file must be smaller than 2 MB.',
  ),
  'admin.only_csv_files_are_allowed' => 
  array (
    'group' => 'admin',
    'label' => 'Only .csv files are allowed.',
    'default' => 'Only .csv files are allowed.',
  ),
  'admin.uploaded_file_is_not_a_valid_csv_file' => 
  array (
    'group' => 'admin',
    'label' => 'Uploaded file is not a valid CSV file.',
    'default' => 'Uploaded file is not a valid CSV file.',
  ),
  'admin.uploaded_csv_appears_to_be_invalid' => 
  array (
    'group' => 'admin',
    'label' => 'Uploaded CSV appears to be invalid.',
    'default' => 'Uploaded CSV appears to be invalid.',
  ),
  'admin.csv_contains_duplicate_column_headers' => 
  array (
    'group' => 'admin',
    'label' => 'CSV contains duplicate column headers.',
    'default' => 'CSV contains duplicate column headers.',
  ),
  'admin.csv_contains_an_unsupported_column_value' => 
  array (
    'group' => 'admin',
    'label' => 'CSV contains an unsupported column: %s',
    'default' => 'CSV contains an unsupported column: %s',
  ),
  'admin.csv_is_missing_required_column_value' => 
  array (
    'group' => 'admin',
    'label' => 'CSV is missing required column: %s',
    'default' => 'CSV is missing required column: %s',
  ),
  'admin.you_do_not_have_permission_to_view_this_page' => 
  array (
    'group' => 'admin',
    'label' => 'You do not have permission to view this page.',
    'default' => 'You do not have permission to view this page.',
  ),
  'admin.you_do_not_have_permission_to_access_this_page' => 
  array (
    'group' => 'admin',
    'label' => 'You do not have permission to access this page.',
    'default' => 'You do not have permission to access this page.',
  ),
  'admin.customers' => 
  array (
    'group' => 'admin',
    'label' => 'Customers',
    'default' => 'Customers',
  ),
  'admin.customers_are_grouped_from_booking_records_use_slotera_account_for_fro' => 
  array (
    'group' => 'admin',
    'label' => 'Customers are grouped from booking records. Use [slotera_account] for fronten...',
    'default' => 'Customers are grouped from booking records. Use [slotera_account] for frontend booking history and self-service actions.',
    'textarea' => true,
  ),
  'admin.search_customers' => 
  array (
    'group' => 'admin',
    'label' => 'Search customers',
    'default' => 'Search customers',
  ),
  'admin.name_email_or_phone' => 
  array (
    'group' => 'admin',
    'label' => 'Name, email or phone',
    'default' => 'Name, email or phone',
  ),
  'admin.customer' => 
  array (
    'group' => 'admin',
    'label' => 'Customer',
    'default' => 'Customer',
  ),
  'admin.email' => 
  array (
    'group' => 'admin',
    'label' => 'Email',
    'default' => 'Email',
  ),
  'admin.phone' => 
  array (
    'group' => 'admin',
    'label' => 'Phone',
    'default' => 'Phone',
  ),
  'admin.bookings' => 
  array (
    'group' => 'admin',
    'label' => 'Bookings',
    'default' => 'Bookings',
  ),
  'admin.active' => 
  array (
    'group' => 'admin',
    'label' => 'Active',
    'default' => 'Active',
  ),
  'admin.last_booking' => 
  array (
    'group' => 'admin',
    'label' => 'Last booking',
    'default' => 'Last booking',
  ),
  'admin.no_customers_found_yet' => 
  array (
    'group' => 'admin',
    'label' => 'No customers found yet.',
    'default' => 'No customers found yet.',
  ),
  'admin.filter' => 
  array (
    'group' => 'admin',
    'label' => 'Filter',
    'default' => 'Filter',
  ),
  'admin.payments' => 
  array (
    'group' => 'admin',
    'label' => 'Payments',
    'default' => 'Payments',
  ),
  'admin.payment_setup_guide' => 
  array (
    'group' => 'admin',
    'label' => 'Payment setup guide',
    'default' => 'Payment setup guide',
  ),
  'admin.enable_the_checkout_methods_customers_can_use_online_gateways_stay_hid' => 
  array (
    'group' => 'admin',
    'label' => 'Enable the checkout methods customers can use. Online gateways stay hidden un...',
    'default' => 'Enable the checkout methods customers can use. Online gateways stay hidden until credentials and webhook verification are complete; manual/custom methods can keep bookings pending until you confirm payment.',
    'textarea' => true,
  ),
  'admin.open_payment_diagnostics' => 
  array (
    'group' => 'admin',
    'label' => 'Open Payment Diagnostics',
    'default' => 'Open Payment Diagnostics',
  ),
  'admin.send_pdf_invoices_to_customers' => 
  array (
    'group' => 'admin',
    'label' => 'Send PDF invoices to customers',
    'default' => 'Send PDF invoices to customers',
  ),
  'admin.send_pdf_invoices_to_customers_and_show_invoice_downloads_in_the_clien' => 
  array (
    'group' => 'admin',
    'label' => 'Send PDF invoices to customers and show invoice downloads in the client account',
    'default' => 'Send PDF invoices to customers and show invoice downloads in the client account',
  ),
  'admin.automatically_generate_and_send_a_pdf_invoice_to_the_customer_after_bo' => 
  array (
    'group' => 'admin',
    'label' => 'Automatically generate and send a PDF invoice to the customer after booking.',
    'default' => 'Automatically generate and send a PDF invoice to the customer after booking.',
  ),
  'admin.custom_payment_methods' => 
  array (
    'group' => 'admin',
    'label' => 'Custom payment methods',
    'default' => 'Custom payment methods',
  ),
  'admin.add_bank_links_or_other_manual_external_payment_options_customers_will' => 
  array (
    'group' => 'admin',
    'label' => 'Add bank links or other manual/external payment options. Customers will see e...',
    'default' => 'Add bank links or other manual/external payment options. Customers will see enabled methods in checkout and can choose them as a payment option.',
    'textarea' => true,
  ),
  'admin.bookings_can_stay_pending_until_you_manually_confirm_payment_or_be_con' => 
  array (
    'group' => 'admin',
    'label' => 'Bookings can stay pending until you manually confirm payment, or be confirmed...',
    'default' => 'Bookings can stay pending until you manually confirm payment, or be confirmed immediately when this custom method is selected.',
    'textarea' => true,
  ),
  'admin.enable_this_custom_method' => 
  array (
    'group' => 'admin',
    'label' => 'Enable this custom method',
    'default' => 'Enable this custom method',
  ),
  'admin.disabled_methods_remain_saved_but_are_hidden_from_checkout' => 
  array (
    'group' => 'admin',
    'label' => 'Disabled methods remain saved but are hidden from checkout.',
    'default' => 'Disabled methods remain saved but are hidden from checkout.',
  ),
  'admin.customer_facing_title' => 
  array (
    'group' => 'admin',
    'label' => 'Customer-facing title',
    'default' => 'Customer-facing title',
  ),
  'admin.example_pay_through_mybank' => 
  array (
    'group' => 'admin',
    'label' => 'Example: Pay through MyBank',
    'default' => 'Example: Pay through MyBank',
  ),
  'admin.this_is_the_name_customers_see_during_checkout' => 
  array (
    'group' => 'admin',
    'label' => 'This is the name customers see during checkout.',
    'default' => 'This is the name customers see during checkout.',
  ),
  'admin.payment_link' => 
  array (
    'group' => 'admin',
    'label' => 'Payment link',
    'default' => 'Payment link',
  ),
  'admin.customers_are_sent_to_this_external_page_after_choosing_the_custom_pay' => 
  array (
    'group' => 'admin',
    'label' => 'Customers are sent to this external page after choosing the custom payment me...',
    'default' => 'Customers are sent to this external page after choosing the custom payment method.',
  ),
  'admin.instructions_shown_to_customer' => 
  array (
    'group' => 'admin',
    'label' => 'Instructions shown to customer',
    'default' => 'Instructions shown to customer',
  ),
  'admin.explain_what_the_customer_should_do_after_opening_the_payment_link' => 
  array (
    'group' => 'admin',
    'label' => 'Explain what the customer should do after opening the payment link.',
    'default' => 'Explain what the customer should do after opening the payment link.',
  ),
  'admin.after_booking' => 
  array (
    'group' => 'admin',
    'label' => 'After booking',
    'default' => 'After booking',
  ),
  'admin.custom_methods_now_create_confirmed_bookings_and_track_the_unpaid_pend' => 
  array (
    'group' => 'admin',
    'label' => 'Custom methods now create confirmed bookings and track the unpaid/pending pay...',
    'default' => 'Custom methods now create confirmed bookings and track the unpaid/pending payment separately.',
    'textarea' => true,
  ),
  'admin.use_confirmed_only_for_payment_methods_you_trust_without_automatic_ver' => 
  array (
    'group' => 'admin',
    'label' => 'Use confirmed only for payment methods you trust without automatic verificati...',
    'default' => 'Use confirmed only for payment methods you trust without automatic verification, such as cash, internal payment links, or trusted offline flows.',
    'textarea' => true,
  ),
  'admin.remove' => 
  array (
    'group' => 'admin',
    'label' => 'Remove',
    'default' => 'Remove',
  ),
  'admin.security' => 
  array (
    'group' => 'admin',
    'label' => 'Security',
    'default' => 'Security',
  ),
  'admin.security_setup_guide' => 
  array (
    'group' => 'admin',
    'label' => 'Security setup guide',
    'default' => 'Security setup guide',
  ),
  'admin.these_settings_protect_public_booking_and_availability_endpoints_from_' => 
  array (
    'group' => 'admin',
    'label' => 'These settings protect public booking and availability endpoints from spam, s...',
    'default' => 'These settings protect public booking and availability endpoints from spam, scraping and spoofed IP headers. Keep defaults enabled unless you understand the trade-off.',
    'textarea' => true,
  ),
  'admin.security_settings_saved' => 
  array (
    'group' => 'admin',
    'label' => 'Security settings saved.',
    'default' => 'Security settings saved.',
  ),
  'admin.anti_spam_defaults' => 
  array (
    'group' => 'admin',
    'label' => 'Anti-spam defaults',
    'default' => 'Anti-spam defaults',
  ),
  'admin.default_protection_uses_honeypot_and_a_4_second_minimum_form_time_trus' => 
  array (
    'group' => 'admin',
    'label' => 'Default protection uses Honeypot and a 4 second minimum form time. Trusted IP...',
    'default' => 'Default protection uses Honeypot and a 4 second minimum form time. Trusted IPs bypass anti-spam checks.',
    'textarea' => true,
  ),
  'admin.honeypot' => 
  array (
    'group' => 'admin',
    'label' => 'Honeypot',
    'default' => 'Honeypot',
  ),
  'admin.enable_hidden_honeypot_field' => 
  array (
    'group' => 'admin',
    'label' => 'Enable hidden honeypot field',
    'default' => 'Enable hidden honeypot field',
  ),
  'admin.public_rest_booking' => 
  array (
    'group' => 'admin',
    'label' => 'Public REST booking',
    'default' => 'Public REST booking',
  ),
  'admin.allow_unauthenticated_booking_creation_through_rest_api' => 
  array (
    'group' => 'admin',
    'label' => 'Allow unauthenticated booking creation through REST API',
    'default' => 'Allow unauthenticated booking creation through REST API',
  ),
  'admin.keep_this_off_unless_the_site_frontend_needs_post_slotera_v1_bookings_' => 
  array (
    'group' => 'admin',
    'label' => 'Keep this off unless the site frontend needs POST /slotera/v1/bookings. When ...',
    'default' => 'Keep this off unless the site frontend needs POST /slotera/v1/bookings. When enabled, non-admin requests require a valid same-site REST nonce; nonce-less external booking creation is blocked.',
    'textarea' => true,
  ),
  'admin.minimum_form_time' => 
  array (
    'group' => 'admin',
    'label' => 'Minimum form time',
    'default' => 'Minimum form time',
  ),
  'admin.seconds' => 
  array (
    'group' => 'admin',
    'label' => 'seconds',
    'default' => 'seconds',
  ),
  'admin.bookings_submitted_faster_than_this_are_blocked_set_0_to_disable_this_' => 
  array (
    'group' => 'admin',
    'label' => 'Bookings submitted faster than this are blocked. Set 0 to disable this check.',
    'default' => 'Bookings submitted faster than this are blocked. Set 0 to disable this check.',
  ),
  'admin.rate_limit_by_ip' => 
  array (
    'group' => 'admin',
    'label' => 'Rate limit by IP',
    'default' => 'Rate limit by IP',
  ),
  'admin.enable_ip_rate_limit' => 
  array (
    'group' => 'admin',
    'label' => 'Enable IP rate limit',
    'default' => 'Enable IP rate limit',
  ),
  'admin.attempts_per_window' => 
  array (
    'group' => 'admin',
    'label' => 'attempts per window',
    'default' => 'attempts per window',
  ),
  'admin.availability_rest_rate_limit' => 
  array (
    'group' => 'admin',
    'label' => 'Availability REST rate limit',
    'default' => 'Availability REST rate limit',
  ),
  'admin.always_enabled_for_anonymous_public_requests' => 
  array (
    'group' => 'admin',
    'label' => 'Always enabled for anonymous public requests',
    'default' => 'Always enabled for anonymous public requests',
  ),
  'admin.requests_per_window' => 
  array (
    'group' => 'admin',
    'label' => 'requests per window',
    'default' => 'requests per window',
  ),
  'admin.protects_get_post_slotera_v1_availability_from_package_date_scanning_a' => 
  array (
    'group' => 'admin',
    'label' => 'Protects GET/POST /slotera/v1/availability from package/date scanning and scr...',
    'default' => 'Protects GET/POST /slotera/v1/availability from package/date scanning and scraping. Admins, valid same-site REST nonce requests, and configured trusted IPs bypass this limit.',
    'textarea' => true,
  ),
  'admin.rate_limit_by_email' => 
  array (
    'group' => 'admin',
    'label' => 'Rate limit by email',
    'default' => 'Rate limit by email',
  ),
  'admin.enable_email_rate_limit' => 
  array (
    'group' => 'admin',
    'label' => 'Enable email rate limit',
    'default' => 'Enable email rate limit',
  ),
  'admin.rate_limit_window' => 
  array (
    'group' => 'admin',
    'label' => 'Rate limit window',
    'default' => 'Rate limit window',
  ),
  'admin.min' => 
  array (
    'group' => 'admin',
    'label' => 'min',
    'default' => 'min',
  ),
  'admin.trusted_ips' => 
  array (
    'group' => 'admin',
    'label' => 'Trusted IPs',
    'default' => 'Trusted IPs',
  ),
  'admin.one_ip_or_cidr_range_per_line_these_ips_bypass_honeypot_minimum_time_c' => 
  array (
    'group' => 'admin',
    'label' => 'One IP or CIDR range per line. These IPs bypass honeypot, minimum time, captc...',
    'default' => 'One IP or CIDR range per line. These IPs bypass honeypot, minimum time, captcha and rate limits.',
    'textarea' => true,
  ),
  'admin.trusted_proxies' => 
  array (
    'group' => 'admin',
    'label' => 'Trusted proxies',
    'default' => 'Trusted proxies',
  ),
  'admin.one_reverse_proxy_load_balancer_cdn_or_ingress_ip_cidr_per_line_sloter' => 
  array (
    'group' => 'admin',
    'label' => 'One reverse proxy, load balancer, CDN or ingress IP/CIDR per line. Slotera tr...',
    'default' => 'One reverse proxy, load balancer, CDN or ingress IP/CIDR per line. Slotera trusts X-Forwarded-For, X-Real-IP and CF-Connecting-IP only when REMOTE_ADDR matches this list. Leave empty to ignore spoofable proxy headers except localhost.',
    'textarea' => true,
  ),
  'admin.captcha_provider' => 
  array (
    'group' => 'admin',
    'label' => 'Captcha provider',
    'default' => 'Captcha provider',
  ),
  'admin.provider' => 
  array (
    'group' => 'admin',
    'label' => 'Provider',
    'default' => 'Provider',
  ),
  'admin.none' => 
  array (
    'group' => 'admin',
    'label' => 'None',
    'default' => 'None',
  ),
  'admin.cloudflare_turnstile' => 
  array (
    'group' => 'admin',
    'label' => 'Cloudflare Turnstile',
    'default' => 'Cloudflare Turnstile',
  ),
  'admin.google_recaptcha_v2_checkbox' => 
  array (
    'group' => 'admin',
    'label' => 'Google reCAPTCHA v2 Checkbox',
    'default' => 'Google reCAPTCHA v2 Checkbox',
  ),
  'admin.site_key' => 
  array (
    'group' => 'admin',
    'label' => 'Site key',
    'default' => 'Site key',
  ),
  'admin.secret_key' => 
  array (
    'group' => 'admin',
    'label' => 'Secret key',
    'default' => 'Secret key',
  ),
  'admin.google_recaptcha' => 
  array (
    'group' => 'admin',
    'label' => 'Google reCAPTCHA',
    'default' => 'Google reCAPTCHA',
  ),
  'admin.save_security_settings' => 
  array (
    'group' => 'admin',
    'label' => 'Save security settings',
    'default' => 'Save security settings',
  ),
  'admin.tools' => 
  array (
    'group' => 'admin',
    'label' => 'Tools',
    'default' => 'Tools',
  ),
  'admin.maintenance_and_diagnostics' => 
  array (
    'group' => 'admin',
    'label' => 'Maintenance and diagnostics',
    'default' => 'Maintenance and diagnostics',
  ),
  'admin.use_these_tools_for_safe_cleanup_reconciliation_data_rebuilds_csv_tran' => 
  array (
    'group' => 'admin',
    'label' => 'Use these tools for safe cleanup, reconciliation, data rebuilds, CSV transfer...',
    'default' => 'Use these tools for safe cleanup, reconciliation, data rebuilds, CSV transfer, webhook testing and system checks. Destructive or risky actions validate data before changing bookings.',
    'textarea' => true,
  ),
  'admin.maintenance_utilities_for_slotera_booking_use_these_tools_carefully_on' => 
  array (
    'group' => 'admin',
    'label' => 'Maintenance utilities for Slotera Booking. Use these tools carefully on produ...',
    'default' => 'Maintenance utilities for Slotera Booking. Use these tools carefully on production sites.',
  ),
  'admin.done' => 
  array (
    'group' => 'admin',
    'label' => 'Done.',
    'default' => 'Done.',
  ),
  'admin.1_maintenance' => 
  array (
    'group' => 'admin',
    'label' => '1. Maintenance',
    'default' => '1. Maintenance',
  ),
  'admin.run_cleanup_jobs_and_prune_old_operational_logs' => 
  array (
    'group' => 'admin',
    'label' => 'Run cleanup jobs and prune old operational logs.',
    'default' => 'Run cleanup jobs and prune old operational logs.',
  ),
  'admin.run_cleanup_now' => 
  array (
    'group' => 'admin',
    'label' => 'Run cleanup now',
    'default' => 'Run cleanup now',
  ),
  'admin.cancels_abandoned_online_payment_holds_according_to_payments_settings' => 
  array (
    'group' => 'admin',
    'label' => 'Cancels abandoned online payment holds according to Payments settings.',
    'default' => 'Cancels abandoned online payment holds according to Payments settings.',
  ),
  'admin.delete_successful_permanent_logs_older_than' => 
  array (
    'group' => 'admin',
    'label' => 'Delete successful/permanent logs older than',
    'default' => 'Delete successful/permanent logs older than',
  ),
  'admin.days' => 
  array (
    'group' => 'admin',
    'label' => 'days',
    'default' => 'days',
  ),
  'admin.prune_logs' => 
  array (
    'group' => 'admin',
    'label' => 'Prune logs',
    'default' => 'Prune logs',
  ),
  'admin.2_reconciliation' => 
  array (
    'group' => 'admin',
    'label' => '2. Reconciliation',
    'default' => '2. Reconciliation',
  ),
  'admin.re_check_pending_online_payments_with_supported_gateways' => 
  array (
    'group' => 'admin',
    'label' => 'Re-check pending online payments with supported gateways.',
    'default' => 'Re-check pending online payments with supported gateways.',
  ),
  'admin.re_run_payment_reconciliation' => 
  array (
    'group' => 'admin',
    'label' => 'Re-run payment reconciliation',
    'default' => 'Re-run payment reconciliation',
  ),
  'admin.manual_bank_transfer_and_custom_payment_methods_are_not_auto_confirmed' => 
  array (
    'group' => 'admin',
    'label' => 'Manual, bank transfer, and custom payment methods are not auto-confirmed by t...',
    'default' => 'Manual, bank transfer, and custom payment methods are not auto-confirmed by this tool.',
  ),
  'admin.3_rebuild_data' => 
  array (
    'group' => 'admin',
    'label' => '3. Rebuild data',
    'default' => '3. Rebuild data',
  ),
  'admin.repair_derived_booking_data_after_imports_migrations_or_manual_databas' => 
  array (
    'group' => 'admin',
    'label' => 'Repair derived booking data after imports, migrations, or manual database cha...',
    'default' => 'Repair derived booking data after imports, migrations, or manual database changes.',
  ),
  'admin.batch_size' => 
  array (
    'group' => 'admin',
    'label' => 'Batch size',
    'default' => 'Batch size',
  ),
  'admin.rebuild_active_slot_hashes' => 
  array (
    'group' => 'admin',
    'label' => 'Rebuild active slot hashes',
    'default' => 'Rebuild active slot hashes',
  ),
  'admin.runs_a_safe_batch_repeat_if_you_have_a_very_large_bookings_table' => 
  array (
    'group' => 'admin',
    'label' => 'Runs a safe batch. Repeat if you have a very large bookings table.',
    'default' => 'Runs a safe batch. Repeat if you have a very large bookings table.',
  ),
  'admin.4_export_import' => 
  array (
    'group' => 'admin',
    'label' => '4. Export / Import',
    'default' => '4. Export / Import',
  ),
  'admin.export_bookings_csv' => 
  array (
    'group' => 'admin',
    'label' => 'Export bookings CSV',
    'default' => 'Export bookings CSV',
  ),
  'admin.any' => 
  array (
    'group' => 'admin',
    'label' => 'Any',
    'default' => 'Any',
  ),
  'admin.from' => 
  array (
    'group' => 'admin',
    'label' => 'From',
    'default' => 'From',
  ),
  'admin.to' => 
  array (
    'group' => 'admin',
    'label' => 'To',
    'default' => 'To',
  ),
  'admin.download_csv' => 
  array (
    'group' => 'admin',
    'label' => 'Download CSV',
    'default' => 'Download CSV',
  ),
  'admin.safe_bookings_csv_import' => 
  array (
    'group' => 'admin',
    'label' => 'Safe bookings CSV import',
    'default' => 'Safe bookings CSV import',
  ),
  'admin.validate_csv' => 
  array (
    'group' => 'admin',
    'label' => 'Validate CSV',
    'default' => 'Validate CSV',
  ),
  'admin.safe_import_always_validates_first_it_never_imports_paid_refunded_paym' => 
  array (
    'group' => 'admin',
    'label' => 'Safe import always validates first. It never imports paid/refunded payment st...',
    'default' => 'Safe import always validates first. It never imports paid/refunded payment states, never updates existing bookings, never bypasses availability, and rejects duplicates with a reason.',
    'textarea' => true,
  ),
  'admin.required_columns_package_id_customer_name_customer_email_booking_date_' => 
  array (
    'group' => 'admin',
    'label' => 'Required columns: package_id, customer_name, customer_email, booking_date, st...',
    'default' => 'Required columns: package_id, customer_name, customer_email, booking_date, start_time, end_time. Date range inventory also requires end_date and resource_id.',
    'textarea' => true,
  ),
  'admin.5_debug_tools' => 
  array (
    'group' => 'admin',
    'label' => '5. Debug tools',
    'default' => '5. Debug tools',
  ),
  'admin.enable_debug_mode' => 
  array (
    'group' => 'admin',
    'label' => 'Enable debug mode',
    'default' => 'Enable debug mode',
  ),
  'admin.store_raw_webhook_payloads_where_available' => 
  array (
    'group' => 'admin',
    'label' => 'Store raw webhook payloads where available',
    'default' => 'Store raw webhook payloads where available',
  ),
  'admin.save_debug_settings' => 
  array (
    'group' => 'admin',
    'label' => 'Save debug settings',
    'default' => 'Save debug settings',
  ),
  'admin.send_test_webhook_event' => 
  array (
    'group' => 'admin',
    'label' => 'Send test webhook event',
    'default' => 'Send test webhook event',
  ),
  'admin.queues_a_tools_test_event_for_active_outgoing_webhook_endpoints' => 
  array (
    'group' => 'admin',
    'label' => 'Queues a tools.test event for active outgoing webhook endpoints.',
    'default' => 'Queues a tools.test event for active outgoing webhook endpoints.',
  ),
  'admin.5_system_status' => 
  array (
    'group' => 'admin',
    'label' => '5. System status',
    'default' => '5. System status',
  ),
  'admin.slotera_version' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera version',
    'default' => 'Slotera version',
  ),
  'admin.wordpress' => 
  array (
    'group' => 'admin',
    'label' => 'WordPress',
    'default' => 'WordPress',
  ),
  'admin.php' => 
  array (
    'group' => 'admin',
    'label' => 'PHP',
    'default' => 'PHP',
  ),
  'admin.database' => 
  array (
    'group' => 'admin',
    'label' => 'Database',
    'default' => 'Database',
  ),
  'admin.wp_cron_disabled' => 
  array (
    'group' => 'admin',
    'label' => 'WP-Cron disabled',
    'default' => 'WP-Cron disabled',
  ),
  'admin.yes' => 
  array (
    'group' => 'admin',
    'label' => 'Yes',
    'default' => 'Yes',
  ),
  'admin.no' => 
  array (
    'group' => 'admin',
    'label' => 'No',
    'default' => 'No',
  ),
  'admin.tables' => 
  array (
    'group' => 'admin',
    'label' => 'Tables',
    'default' => 'Tables',
  ),
  'admin.ok' => 
  array (
    'group' => 'admin',
    'label' => 'OK',
    'default' => 'OK',
  ),
  'admin.missing' => 
  array (
    'group' => 'admin',
    'label' => 'Missing',
    'default' => 'Missing',
  ),
  'admin.rows' => 
  array (
    'group' => 'admin',
    'label' => 'rows',
    'default' => 'rows',
  ),
  'admin.cron' => 
  array (
    'group' => 'admin',
    'label' => 'Cron',
    'default' => 'Cron',
  ),
  'admin.not_scheduled' => 
  array (
    'group' => 'admin',
    'label' => 'Not scheduled',
    'default' => 'Not scheduled',
  ),
  'admin.import_valid_rows_only' => 
  array (
    'group' => 'admin',
    'label' => 'Import valid rows only',
    'default' => 'Import valid rows only',
  ),
  'admin.validation_is_repeated_before_insert_so_rows_can_still_be_rejected_if_' => 
  array (
    'group' => 'admin',
    'label' => 'Validation is repeated before insert, so rows can still be rejected if availa...',
    'default' => 'Validation is repeated before insert, so rows can still be rejected if availability changed.',
    'textarea' => true,
  ),
  'admin.row_value' => 
  array (
    'group' => 'admin',
    'label' => 'Row %d',
    'default' => 'Row %d',
  ),
  'admin.row' => 
  array (
    'group' => 'admin',
    'label' => 'Row',
    'default' => 'Row',
  ),
  'admin.outgoing_webhooks' => 
  array (
    'group' => 'admin',
    'label' => 'Outgoing Webhooks',
    'default' => 'Outgoing Webhooks',
  ),
  'admin.webhook_setup_guide' => 
  array (
    'group' => 'admin',
    'label' => 'Webhook setup guide',
    'default' => 'Webhook setup guide',
  ),
  'admin.send_signed_event_notifications_to_external_systems_through_a_webhook_' => 
  array (
    'group' => 'admin',
    'label' => 'Send signed event notifications to external systems through a webhook URL. Co...',
    'default' => 'Send signed event notifications to external systems through a webhook URL. Compatible with Zapier, Make, CRMs, and custom endpoints.',
    'textarea' => true,
  ),
  'admin.edit_endpoint' => 
  array (
    'group' => 'admin',
    'label' => 'Edit endpoint',
    'default' => 'Edit endpoint',
  ),
  'admin.add_endpoint' => 
  array (
    'group' => 'admin',
    'label' => 'Add endpoint',
    'default' => 'Add endpoint',
  ),
  'admin.name' => 
  array (
    'group' => 'admin',
    'label' => 'Name',
    'default' => 'Name',
  ),
  'admin.internal_label_only_customers_never_see_this_name' => 
  array (
    'group' => 'admin',
    'label' => 'Internal label only. Customers never see this name.',
    'default' => 'Internal label only. Customers never see this name.',
  ),
  'admin.webhook_url' => 
  array (
    'group' => 'admin',
    'label' => 'Webhook URL',
    'default' => 'Webhook URL',
  ),
  'admin.use_an_https_endpoint_that_can_respond_with_a_2xx_status_code' => 
  array (
    'group' => 'admin',
    'label' => 'Use an HTTPS endpoint that can respond with a 2xx status code.',
    'default' => 'Use an HTTPS endpoint that can respond with a 2xx status code.',
  ),
  'admin.signing_secret' => 
  array (
    'group' => 'admin',
    'label' => 'Signing secret',
    'default' => 'Signing secret',
  ),
  'admin.generated_automatically_if_empty' => 
  array (
    'group' => 'admin',
    'label' => 'Generated automatically if empty',
    'default' => 'Generated automatically if empty',
  ),
  'admin.slotera_signs_each_request_with_hmac_sha256_in_the_x_slotera_signature' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera signs each request with HMAC-SHA256 in the X-Slotera-Signature header.',
    'default' => 'Slotera signs each request with HMAC-SHA256 in the X-Slotera-Signature header.',
  ),
  'admin.events' => 
  array (
    'group' => 'admin',
    'label' => 'Events',
    'default' => 'Events',
  ),
  'admin.all_events' => 
  array (
    'group' => 'admin',
    'label' => 'All events',
    'default' => 'All events',
  ),
  'admin.choose_all_events_for_automation_platforms_or_select_only_the_events_t' => 
  array (
    'group' => 'admin',
    'label' => 'Choose all events for automation platforms, or select only the events this en...',
    'default' => 'Choose all events for automation platforms, or select only the events this endpoint needs.',
  ),
  'admin.enabled' => 
  array (
    'group' => 'admin',
    'label' => 'Enabled',
    'default' => 'Enabled',
  ),
  'admin.save_webhook_endpoint' => 
  array (
    'group' => 'admin',
    'label' => 'Save webhook endpoint',
    'default' => 'Save webhook endpoint',
  ),
  'admin.webhook_endpoints' => 
  array (
    'group' => 'admin',
    'label' => 'Webhook endpoints',
    'default' => 'Webhook endpoints',
  ),
  'admin.url' => 
  array (
    'group' => 'admin',
    'label' => 'URL',
    'default' => 'URL',
  ),
  'admin.actions' => 
  array (
    'group' => 'admin',
    'label' => 'Actions',
    'default' => 'Actions',
  ),
  'admin.no_webhook_endpoints_yet' => 
  array (
    'group' => 'admin',
    'label' => 'No webhook endpoints yet.',
    'default' => 'No webhook endpoints yet.',
  ),
  'admin.disabled' => 
  array (
    'group' => 'admin',
    'label' => 'Disabled',
    'default' => 'Disabled',
  ),
  'admin.edit' => 
  array (
    'group' => 'admin',
    'label' => 'Edit',
    'default' => 'Edit',
  ),
  'admin.disable' => 
  array (
    'group' => 'admin',
    'label' => 'Disable',
    'default' => 'Disable',
  ),
  'admin.enable' => 
  array (
    'group' => 'admin',
    'label' => 'Enable',
    'default' => 'Enable',
  ),
  'admin.delete_this_webhook_endpoint' => 
  array (
    'group' => 'admin',
    'label' => 'Delete this webhook endpoint?',
    'default' => 'Delete this webhook endpoint?',
  ),
  'admin.delete' => 
  array (
    'group' => 'admin',
    'label' => 'Delete',
    'default' => 'Delete',
  ),
  'admin.delivery_log' => 
  array (
    'group' => 'admin',
    'label' => 'Delivery log',
    'default' => 'Delivery log',
  ),
  'admin.created' => 
  array (
    'group' => 'admin',
    'label' => 'Created',
    'default' => 'Created',
  ),
  'admin.event' => 
  array (
    'group' => 'admin',
    'label' => 'Event',
    'default' => 'Event',
  ),
  'admin.http' => 
  array (
    'group' => 'admin',
    'label' => 'HTTP',
    'default' => 'HTTP',
  ),
  'admin.attempts' => 
  array (
    'group' => 'admin',
    'label' => 'Attempts',
    'default' => 'Attempts',
  ),
  'admin.error' => 
  array (
    'group' => 'admin',
    'label' => 'Error',
    'default' => 'Error',
  ),
  'admin.no_webhook_deliveries_yet' => 
  array (
    'group' => 'admin',
    'label' => 'No webhook deliveries yet.',
    'default' => 'No webhook deliveries yet.',
  ),
  'admin.retry' => 
  array (
    'group' => 'admin',
    'label' => 'Retry',
    'default' => 'Retry',
  ),
  'admin.webhook_endpoint_saved' => 
  array (
    'group' => 'admin',
    'label' => 'Webhook endpoint saved.',
    'default' => 'Webhook endpoint saved.',
  ),
  'admin.webhook_endpoint_deleted' => 
  array (
    'group' => 'admin',
    'label' => 'Webhook endpoint deleted.',
    'default' => 'Webhook endpoint deleted.',
  ),
  'admin.webhook_delivery_retried' => 
  array (
    'group' => 'admin',
    'label' => 'Webhook delivery retried.',
    'default' => 'Webhook delivery retried.',
  ),
  'admin.please_enter_a_valid_webhook_url' => 
  array (
    'group' => 'admin',
    'label' => 'Please enter a valid webhook URL.',
    'default' => 'Please enter a valid webhook URL.',
  ),
  'admin.booking_form' => 
  array (
    'group' => 'admin',
    'label' => 'Booking Form',
    'default' => 'Booking Form',
  ),
  'admin.booking_form_settings_saved' => 
  array (
    'group' => 'admin',
    'label' => 'Booking form settings saved.',
    'default' => 'Booking form settings saved.',
  ),
  'admin.choose_which_customer_fields_appear_in_the_public_booking_form_email_i' => 
  array (
    'group' => 'admin',
    'label' => 'Choose which customer fields appear in the public booking form. Email is alwa...',
    'default' => 'Choose which customer fields appear in the public booking form. Email is always enabled and required.',
    'textarea' => true,
  ),
  'admin.field' => 
  array (
    'group' => 'admin',
    'label' => 'Field',
    'default' => 'Field',
  ),
  'admin.show' => 
  array (
    'group' => 'admin',
    'label' => 'Show',
    'default' => 'Show',
  ),
  'admin.required' => 
  array (
    'group' => 'admin',
    'label' => 'Required',
    'default' => 'Required',
  ),
  'admin.type' => 
  array (
    'group' => 'admin',
    'label' => 'Type',
    'default' => 'Type',
  ),
  'admin.large_wishes_additional_notes_field_shown_after_customer_details' => 
  array (
    'group' => 'admin',
    'label' => 'Large wishes / additional notes field shown after customer details.',
    'default' => 'Large wishes / additional notes field shown after customer details.',
  ),
  'admin.show_field' => 
  array (
    'group' => 'admin',
    'label' => 'Show field',
    'default' => 'Show field',
  ),
  'admin.save_booking_form' => 
  array (
    'group' => 'admin',
    'label' => 'Save booking form',
    'default' => 'Save booking form',
  ),
  'admin.search' => 
  array (
    'group' => 'admin',
    'label' => 'Search...',
    'default' => 'Search...',
  ),
  'admin.apply' => 
  array (
    'group' => 'admin',
    'label' => 'Apply',
    'default' => 'Apply',
  ),
  'admin.reset' => 
  array (
    'group' => 'admin',
    'label' => 'Reset',
    'default' => 'Reset',
  ),
  'admin.package' => 
  array (
    'group' => 'admin',
    'label' => 'Package',
    'default' => 'Package',
  ),
  'admin.payment' => 
  array (
    'group' => 'admin',
    'label' => 'Payment',
    'default' => 'Payment',
  ),
  'admin.view' => 
  array (
    'group' => 'admin',
    'label' => 'View',
    'default' => 'View',
  ),
  'admin.no_bookings_found' => 
  array (
    'group' => 'admin',
    'label' => 'No bookings found.',
    'default' => 'No bookings found.',
  ),
  'admin.categories' => 
  array (
    'group' => 'admin',
    'label' => 'Categories',
    'default' => 'Categories',
  ),
  'admin.add_new' => 
  array (
    'group' => 'admin',
    'label' => 'Add New',
    'default' => 'Add New',
  ),
  'admin.category_updated' => 
  array (
    'group' => 'admin',
    'label' => 'Category updated.',
    'default' => 'Category updated.',
  ),
  'admin.order' => 
  array (
    'group' => 'admin',
    'label' => 'Order',
    'default' => 'Order',
  ),
  'admin.delete_this_category_packages_in_this_category_will_be_moved_to_no_cat' => 
  array (
    'group' => 'admin',
    'label' => 'Delete this category? Packages in this category will be moved to no category.',
    'default' => 'Delete this category? Packages in this category will be moved to no category.',
  ),
  'admin.edit_page' => 
  array (
    'group' => 'admin',
    'label' => 'Edit page',
    'default' => 'Edit page',
  ),
  'admin.open_page' => 
  array (
    'group' => 'admin',
    'label' => 'Open page',
    'default' => 'Open page',
  ),
  'admin.no_categories_found' => 
  array (
    'group' => 'admin',
    'label' => 'No categories found.',
    'default' => 'No categories found.',
  ),
  'admin.edit_category' => 
  array (
    'group' => 'admin',
    'label' => 'Edit category',
    'default' => 'Edit category',
  ),
  'admin.new_category' => 
  array (
    'group' => 'admin',
    'label' => 'New category',
    'default' => 'New category',
  ),
  'admin.category_setup_guide' => 
  array (
    'group' => 'admin',
    'label' => 'Category setup guide',
    'default' => 'Category setup guide',
  ),
  'admin.categories_group_packages_on_public_package_lists_and_category_shortco' => 
  array (
    'group' => 'admin',
    'label' => 'Categories group packages on public package lists and category shortcodes. In...',
    'default' => 'Categories group packages on public package lists and category shortcodes. Inactive categories are hidden from customers.',
    'textarea' => true,
  ),
  'admin.customer_facing_category_name_shown_in_admin_lists_and_public_category' => 
  array (
    'group' => 'admin',
    'label' => 'Customer-facing category name shown in admin lists and public category pages.',
    'default' => 'Customer-facing category name shown in admin lists and public category pages.',
  ),
  'admin.used_in_the_category_url_leave_empty_to_generate_it_from_the_category_' => 
  array (
    'group' => 'admin',
    'label' => 'Used in the category URL. Leave empty to generate it from the category name.',
    'default' => 'Used in the category URL. Leave empty to generate it from the category name.',
  ),
  'admin.optional_text_shown_wherever_the_theme_or_shortcode_displays_category_' => 
  array (
    'group' => 'admin',
    'label' => 'Optional text shown wherever the theme or shortcode displays category descrip...',
    'default' => 'Optional text shown wherever the theme or shortcode displays category descriptions.',
  ),
  'admin.sort_order' => 
  array (
    'group' => 'admin',
    'label' => 'Sort order',
    'default' => 'Sort order',
  ),
  'admin.lower_numbers_appear_first_in_category_lists' => 
  array (
    'group' => 'admin',
    'label' => 'Lower numbers appear first in category lists.',
    'default' => 'Lower numbers appear first in category lists.',
  ),
  'admin.inactive_categories_remain_saved_but_are_not_shown_to_customers' => 
  array (
    'group' => 'admin',
    'label' => 'Inactive categories remain saved but are not shown to customers.',
    'default' => 'Inactive categories remain saved but are not shown to customers.',
  ),
  'admin.save_category' => 
  array (
    'group' => 'admin',
    'label' => 'Save category',
    'default' => 'Save category',
  ),
  'admin.back' => 
  array (
    'group' => 'admin',
    'label' => 'Back',
    'default' => 'Back',
  ),
  'admin.packages' => 
  array (
    'group' => 'admin',
    'label' => 'Packages',
    'default' => 'Packages',
  ),
  'admin.package_has_existing_bookings_so_it_was_deactivated_instead_of_permane' => 
  array (
    'group' => 'admin',
    'label' => 'Package has existing bookings, so it was deactivated instead of permanently d...',
    'default' => 'Package has existing bookings, so it was deactivated instead of permanently deleted. Booking history remains intact.',
    'textarea' => true,
  ),
  'admin.package_deleted' => 
  array (
    'group' => 'admin',
    'label' => 'Package deleted.',
    'default' => 'Package deleted.',
  ),
  'admin.package_updated' => 
  array (
    'group' => 'admin',
    'label' => 'Package updated.',
    'default' => 'Package updated.',
  ),
  'admin.category' => 
  array (
    'group' => 'admin',
    'label' => 'Category',
    'default' => 'Category',
  ),
  'admin.duration' => 
  array (
    'group' => 'admin',
    'label' => 'Duration',
    'default' => 'Duration',
  ),
  'admin.price' => 
  array (
    'group' => 'admin',
    'label' => 'Price',
    'default' => 'Price',
  ),
  'admin.discount' => 
  array (
    'group' => 'admin',
    'label' => 'Discount',
    'default' => 'Discount',
  ),
  'admin.mode' => 
  array (
    'group' => 'admin',
    'label' => 'Mode',
    'default' => 'Mode',
  ),
  'admin.capacity' => 
  array (
    'group' => 'admin',
    'label' => 'Capacity',
    'default' => 'Capacity',
  ),
  'admin.hours' => 
  array (
    'group' => 'admin',
    'label' => 'Hours',
    'default' => 'Hours',
  ),
  'admin.popular' => 
  array (
    'group' => 'admin',
    'label' => 'Popular',
    'default' => 'Popular',
  ),
  'admin.delete_this_package_packages_with_bookings_will_be_deactivated_instead' => 
  array (
    'group' => 'admin',
    'label' => 'Delete this package? Packages with bookings will be deactivated instead to pr...',
    'default' => 'Delete this package? Packages with bookings will be deactivated instead to preserve booking history.',
    'textarea' => true,
  ),
  'admin.no_packages_found' => 
  array (
    'group' => 'admin',
    'label' => 'No packages found.',
    'default' => 'No packages found.',
  ),
  'admin.settings' => 
  array (
    'group' => 'admin',
    'label' => 'Settings',
    'default' => 'Settings',
  ),
  'admin.saved' => 
  array (
    'group' => 'admin',
    'label' => 'Saved.',
    'default' => 'Saved.',
  ),
  'admin.shortcodes' => 
  array (
    'group' => 'admin',
    'label' => 'Shortcodes',
    'default' => 'Shortcodes',
  ),
  'admin.use_these_shortcodes_to_place_slotera_booking_elements_on_wordpress_pa' => 
  array (
    'group' => 'admin',
    'label' => 'Use these shortcodes to place Slotera booking elements on WordPress pages.',
    'default' => 'Use these shortcodes to place Slotera booking elements on WordPress pages.',
  ),
  'admin.purpose' => 
  array (
    'group' => 'admin',
    'label' => 'Purpose',
    'default' => 'Purpose',
  ),
  'admin.shortcode' => 
  array (
    'group' => 'admin',
    'label' => 'Shortcode',
    'default' => 'Shortcode',
  ),
  'admin.booking_pages' => 
  array (
    'group' => 'admin',
    'label' => 'Booking pages',
    'default' => 'Booking pages',
  ),
  'admin.choose_the_wordpress_pages_used_by_slotera_booking_for_booking_package_lists_t' => 
  array (
    'group' => 'admin',
    'label' => 'Choose the WordPress pages used by Slotera Booking for booking, package lists...',
    'default' => 'Choose the WordPress pages used by Slotera Booking for booking, package lists, categories, checkout, thank-you screen, login and client account.',
    'textarea' => true,
  ),
  'admin.booking_page' => 
  array (
    'group' => 'admin',
    'label' => 'Booking page',
    'default' => 'Booking page',
  ),
  'admin.select_page' => 
  array (
    'group' => 'admin',
    'label' => '— Select page —',
    'default' => '— Select page —',
  ),
  'admin.packages_page' => 
  array (
    'group' => 'admin',
    'label' => 'Packages page',
    'default' => 'Packages page',
  ),
  'admin.categories_page' => 
  array (
    'group' => 'admin',
    'label' => 'Categories page',
    'default' => 'Categories page',
  ),
  'admin.thank_you_page' => 
  array (
    'group' => 'admin',
    'label' => 'Thank you page',
    'default' => 'Thank you page',
  ),
  'admin.client_login_page' => 
  array (
    'group' => 'admin',
    'label' => 'Client login page',
    'default' => 'Client login page',
  ),
  'admin.client_account_page' => 
  array (
    'group' => 'admin',
    'label' => 'Client account page',
    'default' => 'Client account page',
  ),
  'admin.save_booking_pages' => 
  array (
    'group' => 'admin',
    'label' => 'Save booking pages',
    'default' => 'Save booking pages',
  ),
  'admin.package_card_layout' => 
  array (
    'group' => 'admin',
    'label' => 'Package card layout',
    'default' => 'Package card layout',
  ),
  'admin.control_how_many_package_cards_are_shown_in_one_row_on_different_scree' => 
  array (
    'group' => 'admin',
    'label' => 'Control how many package cards are shown in one row on different screen sizes.',
    'default' => 'Control how many package cards are shown in one row on different screen sizes.',
  ),
  'admin.desktop_columns' => 
  array (
    'group' => 'admin',
    'label' => 'Desktop columns',
    'default' => 'Desktop columns',
  ),
  'admin.default_3' => 
  array (
    'group' => 'admin',
    'label' => 'Default: 3',
    'default' => 'Default: 3',
  ),
  'admin.tablet_columns' => 
  array (
    'group' => 'admin',
    'label' => 'Tablet columns',
    'default' => 'Tablet columns',
  ),
  'admin.default_2' => 
  array (
    'group' => 'admin',
    'label' => 'Default: 2',
    'default' => 'Default: 2',
  ),
  'admin.mobile_columns' => 
  array (
    'group' => 'admin',
    'label' => 'Mobile columns',
    'default' => 'Mobile columns',
  ),
  'admin.default_1' => 
  array (
    'group' => 'admin',
    'label' => 'Default: 1',
    'default' => 'Default: 1',
  ),
  'admin.select_time_layout' => 
  array (
    'group' => 'admin',
    'label' => 'Select time layout',
    'default' => 'Select time layout',
  ),
  'admin.list' => 
  array (
    'group' => 'admin',
    'label' => 'List',
    'default' => 'List',
  ),
  'admin.grid' => 
  array (
    'group' => 'admin',
    'label' => 'Grid',
    'default' => 'Grid',
  ),
  'admin.default_list' => 
  array (
    'group' => 'admin',
    'label' => 'Default: list.',
    'default' => 'Default: list.',
  ),
  'admin.save_layout_settings' => 
  array (
    'group' => 'admin',
    'label' => 'Save layout settings',
    'default' => 'Save layout settings',
  ),
  'admin.appearance' => 
  array (
    'group' => 'admin',
    'label' => 'Appearance',
    'default' => 'Appearance',
  ),
  'admin.choose_a_preset_theme_or_select_custom_to_use_your_own_colors_for_the_' => 
  array (
    'group' => 'admin',
    'label' => 'Choose a preset theme or select Custom to use your own colors for the form, c...',
    'default' => 'Choose a preset theme or select Custom to use your own colors for the form, cards, prices and badges.',
    'textarea' => true,
  ),
  'admin.preset_theme' => 
  array (
    'group' => 'admin',
    'label' => 'Preset theme',
    'default' => 'Preset theme',
  ),
  'admin.theme_preview' => 
  array (
    'group' => 'admin',
    'label' => 'Theme preview',
    'default' => 'Theme preview',
  ),
  'admin.sample_package' => 
  array (
    'group' => 'admin',
    'label' => 'Sample package',
    'default' => 'Sample package',
  ),
  'admin.this_preview_uses_the_same_colors_as_the_frontend_package_cards' => 
  array (
    'group' => 'admin',
    'label' => 'This preview uses the same colors as the frontend package cards.',
    'default' => 'This preview uses the same colors as the frontend package cards.',
  ),
  'admin.book_now' => 
  array (
    'group' => 'admin',
    'label' => 'Book now',
    'default' => 'Book now',
  ),
  'admin.preview_updates_instantly_while_you_edit_preset_themes_fill_the_color_' => 
  array (
    'group' => 'admin',
    'label' => 'Preview updates instantly while you edit. Preset themes fill the color fields...',
    'default' => 'Preview updates instantly while you edit. Preset themes fill the color fields; Custom lets you adjust every color manually.',
    'textarea' => true,
  ),
  'admin.form_background' => 
  array (
    'group' => 'admin',
    'label' => 'Form background',
    'default' => 'Form background',
  ),
  'admin.form_text' => 
  array (
    'group' => 'admin',
    'label' => 'Form text',
    'default' => 'Form text',
  ),
  'admin.card_background' => 
  array (
    'group' => 'admin',
    'label' => 'Card background',
    'default' => 'Card background',
  ),
  'admin.card_border' => 
  array (
    'group' => 'admin',
    'label' => 'Card border',
    'default' => 'Card border',
  ),
  'admin.primary_color' => 
  array (
    'group' => 'admin',
    'label' => 'Primary color',
    'default' => 'Primary color',
  ),
  'admin.primary_text' => 
  array (
    'group' => 'admin',
    'label' => 'Primary text',
    'default' => 'Primary text',
  ),
  'admin.muted_text' => 
  array (
    'group' => 'admin',
    'label' => 'Muted text',
    'default' => 'Muted text',
  ),
  'admin.old_price_color' => 
  array (
    'group' => 'admin',
    'label' => 'Old price color',
    'default' => 'Old price color',
  ),
  'admin.new_price_color' => 
  array (
    'group' => 'admin',
    'label' => 'New price color',
    'default' => 'New price color',
  ),
  'admin.discount_badge_background' => 
  array (
    'group' => 'admin',
    'label' => 'Discount badge background',
    'default' => 'Discount badge background',
  ),
  'admin.discount_badge_text' => 
  array (
    'group' => 'admin',
    'label' => 'Discount badge text',
    'default' => 'Discount badge text',
  ),
  'admin.tooltip_icon_color' => 
  array (
    'group' => 'admin',
    'label' => 'Tooltip icon color',
    'default' => 'Tooltip icon color',
  ),
  'admin.tooltip_background' => 
  array (
    'group' => 'admin',
    'label' => 'Tooltip background',
    'default' => 'Tooltip background',
  ),
  'admin.tooltip_text' => 
  array (
    'group' => 'admin',
    'label' => 'Tooltip text',
    'default' => 'Tooltip text',
  ),
  'admin.calendar_background' => 
  array (
    'group' => 'admin',
    'label' => 'Calendar background',
    'default' => 'Calendar background',
  ),
  'admin.calendar_text' => 
  array (
    'group' => 'admin',
    'label' => 'Calendar text',
    'default' => 'Calendar text',
  ),
  'admin.calendar_border' => 
  array (
    'group' => 'admin',
    'label' => 'Calendar border',
    'default' => 'Calendar border',
  ),
  'admin.calendar_day_background' => 
  array (
    'group' => 'admin',
    'label' => 'Calendar day background',
    'default' => 'Calendar day background',
  ),
  'admin.blocked_date_background' => 
  array (
    'group' => 'admin',
    'label' => 'Blocked date background',
    'default' => 'Blocked date background',
  ),
  'admin.blocked_date_text_lock' => 
  array (
    'group' => 'admin',
    'label' => 'Blocked date text / lock',
    'default' => 'Blocked date text / lock',
  ),
  'admin.tooltip_size_ratio' => 
  array (
    'group' => 'admin',
    'label' => 'Tooltip size ratio',
    'default' => 'Tooltip size ratio',
  ),
  'admin.controls_the_size_of_the_tooltip_information_icon_example_1_15_115' => 
  array (
    'group' => 'admin',
    'label' => 'Controls the size of the tooltip information icon. Example: 1.15 = 115%.',
    'default' => 'Controls the size of the tooltip information icon. Example: 1.15 = 115%.',
  ),
  'admin.text_size_inside_the_tooltip' => 
  array (
    'group' => 'admin',
    'label' => 'Text size inside the tooltip',
    'default' => 'Text size inside the tooltip',
  ),
  'admin.old_price_style' => 
  array (
    'group' => 'admin',
    'label' => 'Old price style',
    'default' => 'Old price style',
  ),
  'admin.line_through' => 
  array (
    'group' => 'admin',
    'label' => 'Line through',
    'default' => 'Line through',
  ),
  'admin.old_price_size_ratio' => 
  array (
    'group' => 'admin',
    'label' => 'Old price size ratio',
    'default' => 'Old price size ratio',
  ),
  'admin.example_0_85_means_the_old_price_is_85_of_the_new_price_size' => 
  array (
    'group' => 'admin',
    'label' => 'Example: 0.85 means the old price is 85% of the new price size.',
    'default' => 'Example: 0.85 means the old price is 85% of the new price size.',
  ),
  'admin.save_appearance_settings' => 
  array (
    'group' => 'admin',
    'label' => 'Save appearance settings',
    'default' => 'Save appearance settings',
  ),
  'admin.privacy_retention_and_uninstall' => 
  array (
    'group' => 'admin',
    'label' => 'Privacy, retention and uninstall',
    'default' => 'Privacy, retention and uninstall',
  ),
  'admin.control_how_long_slotera_keeps_personal_data_in_operational_records_lo' => 
  array (
    'group' => 'admin',
    'label' => 'Control how long Slotera keeps personal data in operational records, logs, em...',
    'default' => 'Control how long Slotera keeps personal data in operational records, logs, email queues and webhook payloads.',
    'textarea' => true,
  ),
  'admin.remove_data_on_uninstall' => 
  array (
    'group' => 'admin',
    'label' => 'Remove data on uninstall',
    'default' => 'Remove data on uninstall',
  ),
  'admin.delete_slotera_tables_options_and_scheduled_jobs_when_the_plugin_is_un' => 
  array (
    'group' => 'admin',
    'label' => 'Delete Slotera tables, options and scheduled jobs when the plugin is uninstal...',
    'default' => 'Delete Slotera tables, options and scheduled jobs when the plugin is uninstalled.',
  ),
  'admin.keep_disabled_unless_the_site_owner_explicitly_wants_a_full_data_wipe_' => 
  array (
    'group' => 'admin',
    'label' => 'Keep disabled unless the site owner explicitly wants a full data wipe. Deacti...',
    'default' => 'Keep disabled unless the site owner explicitly wants a full data wipe. Deactivation never removes data.',
    'textarea' => true,
  ),
  'admin.anonymize_completed_cancelled_bookings_after' => 
  array (
    'group' => 'admin',
    'label' => 'Anonymize completed/cancelled bookings after',
    'default' => 'Anonymize completed/cancelled bookings after',
  ),
  'admin.0_disables_automatic_anonymization_future_active_bookings_are_not_anon' => 
  array (
    'group' => 'admin',
    'label' => '0 disables automatic anonymization. Future/active bookings are not anonymized.',
    'default' => '0 disables automatic anonymization. Future/active bookings are not anonymized.',
  ),
  'admin.activity_log_retention' => 
  array (
    'group' => 'admin',
    'label' => 'Activity log retention',
    'default' => 'Activity log retention',
  ),
  'admin.booking_history_retention' => 
  array (
    'group' => 'admin',
    'label' => 'Booking history retention',
    'default' => 'Booking history retention',
  ),
  'admin.email_queue_retention' => 
  array (
    'group' => 'admin',
    'label' => 'Email queue retention',
    'default' => 'Email queue retention',
  ),
  'admin.marketing_log_retention' => 
  array (
    'group' => 'admin',
    'label' => 'Marketing log retention',
    'default' => 'Marketing log retention',
  ),
  'admin.incoming_webhook_payload_retention' => 
  array (
    'group' => 'admin',
    'label' => 'Incoming webhook payload retention',
    'default' => 'Incoming webhook payload retention',
  ),
  'admin.outgoing_webhook_delivery_retention' => 
  array (
    'group' => 'admin',
    'label' => 'Outgoing webhook delivery retention',
    'default' => 'Outgoing webhook delivery retention',
  ),
  'admin.save_privacy_settings' => 
  array (
    'group' => 'admin',
    'label' => 'Save privacy settings',
    'default' => 'Save privacy settings',
  ),
  'admin.global_working_hours' => 
  array (
    'group' => 'admin',
    'label' => 'Global working hours',
    'default' => 'Global working hours',
  ),
  'admin.day' => 
  array (
    'group' => 'admin',
    'label' => 'Day',
    'default' => 'Day',
  ),
  'admin.start' => 
  array (
    'group' => 'admin',
    'label' => 'Start',
    'default' => 'Start',
  ),
  'admin.end' => 
  array (
    'group' => 'admin',
    'label' => 'End',
    'default' => 'End',
  ),
  'admin.save_working_hours' => 
  array (
    'group' => 'admin',
    'label' => 'Save working hours',
    'default' => 'Save working hours',
  ),
  'admin.used_for_the_public_package_page_url_if_another_package_already_uses_t' => 
  array (
    'group' => 'admin',
    'label' => 'Used for the public package page URL. If another package already uses this sl...',
    'default' => 'Used for the public package page URL. If another package already uses this slug, Slotera will append a number automatically.',
    'textarea' => true,
  ),
  'admin.more_info_link' => 
  array (
    'group' => 'admin',
    'label' => 'More info link',
    'default' => 'More info link',
  ),
  'admin.show_more_info_link_on_package_cards' => 
  array (
    'group' => 'admin',
    'label' => 'Show "More info" link on package cards',
    'default' => 'Show "More info" link on package cards',
  ),
  'admin.when_enabled_the_package_card_shows_a_more_info_link_to_the_solo_packa' => 
  array (
    'group' => 'admin',
    'label' => 'When enabled, the package card shows a More info link to the solo package pag...',
    'default' => 'When enabled, the package card shows a More info link to the solo package page above the Select button.',
    'textarea' => true,
  ),
  'admin.classic_left_card_right_content' => 
  array (
    'group' => 'admin',
    'label' => 'Classic: left card + right content',
    'default' => 'Classic: left card + right content',
  ),
  'admin.stacked_card_above_content' => 
  array (
    'group' => 'admin',
    'label' => 'Stacked: card above content',
    'default' => 'Stacked: card above content',
  ),
  'admin.controls_the_layout_of_the_solo_package_page' => 
  array (
    'group' => 'admin',
    'label' => 'Controls the layout of the solo package page.',
    'default' => 'Controls the layout of the solo package page.',
  ),
  'admin.no_category' => 
  array (
    'group' => 'admin',
    'label' => 'No category',
    'default' => 'No category',
  ),
  'admin.shown_as_a_popup_inside_the_solo_package_card_it_is_not_shown_on_the_p' => 
  array (
    'group' => 'admin',
    'label' => 'Shown as a popup inside the solo package card. It is not shown on the package...',
    'default' => 'Shown as a popup inside the solo package card. It is not shown on the package list.',
  ),
  'admin.solo_page_right_content' => 
  array (
    'group' => 'admin',
    'label' => 'Solo page right content',
    'default' => 'Solo page right content',
  ),
  'admin.right_column_quick_inserts' => 
  array (
    'group' => 'admin',
    'label' => 'Right column quick inserts',
    'default' => 'Right column quick inserts',
  ),
  'admin.insert_slider' => 
  array (
    'group' => 'admin',
    'label' => 'Insert slider',
    'default' => 'Insert slider',
  ),
  'admin.insert_gallery' => 
  array (
    'group' => 'admin',
    'label' => 'Insert image',
    'default' => 'Insert image',
  ),
  'admin.insert_title_text_block' => 
  array (
    'group' => 'admin',
    'label' => 'Insert title/text block',
    'default' => 'Insert title/text block',
  ),
  'admin.use_default_media_layout' => 
  array (
    'group' => 'admin',
    'label' => 'Use default media layout',
    'default' => 'Use default media layout',
  ),
  'admin.add_text_html_or_an_elementor_template_shortcode_here_leave_empty_to_a' => 
  array (
    'group' => 'admin',
    'label' => 'Add text, HTML, or an Elementor/template shortcode here. Leave empty to auto-...',
    'default' => 'Add text, HTML, or an Elementor/template shortcode here. Leave empty to auto-show configured title/text, slider and image blocks.',
    'textarea' => true,
  ),
  'admin.elementor_global_usage_1_s_2_s_3_s' => 
  array (
    'group' => 'admin',
    'label' => 'Elementor/global usage: %1$s, %2$s, %3$s',
    'default' => 'Elementor/global usage: %1$s, %2$s, %3$s',
  ),
  'admin.solo_page_down_content' => 
  array (
    'group' => 'admin',
    'label' => 'Solo page down content',
    'default' => 'Solo page down content',
  ),
  'admin.down_content_quick_inserts' => 
  array (
    'group' => 'admin',
    'label' => 'Down content quick inserts',
    'default' => 'Down content quick inserts',
  ),
  'admin.add_text_html_elementor_template_shortcode_or_any_content_that_should_' => 
  array (
    'group' => 'admin',
    'label' => 'Add text, HTML, Elementor/template shortcode, or any content that should appe...',
    'default' => 'Add text, HTML, Elementor/template shortcode, or any content that should appear under both solo page columns.',
    'textarea' => true,
  ),
  'admin.this_content_appears_in_a_full_width_container_below_the_left_package_' => 
  array (
    'group' => 'admin',
    'label' => 'This content appears in a full-width container below the left package card an...',
    'default' => 'This content appears in a full-width container below the left package card and the right content area.',
    'textarea' => true,
  ),
  'admin.solo_media_slider' => 
  array (
    'group' => 'admin',
    'label' => 'Solo media slider',
    'default' => 'Solo media slider',
  ),
  'admin.select_slider_images' => 
  array (
    'group' => 'admin',
    'label' => 'Select slider images',
    'default' => 'Select slider images',
  ),
  'admin.clear' => 
  array (
    'group' => 'admin',
    'label' => 'Clear',
    'default' => 'Clear',
  ),
  'admin.shortcode_slotera_package_slider_use_it_in_solo_page_right_content_or_' => 
  array (
    'group' => 'admin',
    'label' => 'Shortcode: [slotera_package_slider]. Use it in Solo page right content or Ele...',
    'default' => 'Shortcode: [slotera_package_slider]. Use it in Solo page right content or Elementor shortcode widget. Supports JPG, PNG, GIF and WebP from the WordPress Media Library. Recommended: 1600×900 px, under 1 MB per image.',
    'textarea' => true,
  ),
  'admin.autoplay_speed' => 
  array (
    'group' => 'admin',
    'label' => 'Autoplay speed',
    'default' => 'Autoplay speed',
  ),
  'admin.slider_pauses_on_hover_clicking_an_image_opens_a_larger_preview' => 
  array (
    'group' => 'admin',
    'label' => 'Slider pauses on hover. Clicking an image opens a larger preview.',
    'default' => 'Slider pauses on hover. Clicking an image opens a larger preview.',
  ),
  'admin.solo_image_gallery' => 
  array (
    'group' => 'admin',
    'label' => 'Solo page image',
    'default' => 'Solo page image',
  ),
  'admin.select_gallery_images' => 
  array (
    'group' => 'admin',
    'label' => 'Select image',
    'default' => 'Select image',
  ),
  'admin.gallery_layout' => 
  array (
    'group' => 'admin',
    'label' => 'Image layout',
    'default' => 'Image layout',
  ),
  'admin.grid_two_rows' => 
  array (
    'group' => 'admin',
    'label' => 'Grid / two rows',
    'default' => 'Grid / two rows',
  ),
  'admin.horizontal_list' => 
  array (
    'group' => 'admin',
    'label' => 'Horizontal list',
    'default' => 'Horizontal list',
  ),
  'admin.solo_title_text_block' => 
  array (
    'group' => 'admin',
    'label' => 'Solo title/text block',
    'default' => 'Solo title/text block',
  ),
  'admin.text' => 
  array (
    'group' => 'admin',
    'label' => 'Text',
    'default' => 'Text',
  ),
  'admin.font_family' => 
  array (
    'group' => 'admin',
    'label' => 'Font family',
    'default' => 'Font family',
  ),
  'admin.font_size' => 
  array (
    'group' => 'admin',
    'label' => 'Font size',
    'default' => 'Font size',
  ),
  'admin.shortcode_slotera_package_text_block_use_this_when_the_right_2_3_colum' => 
  array (
    'group' => 'admin',
    'label' => 'Shortcode: [slotera_package_text_block]. Use this when the right 2/3 column n...',
    'default' => 'Shortcode: [slotera_package_text_block]. Use this when the right 2/3 column needs an editable heading and text block.',
    'textarea' => true,
  ),
  'admin.description_typography' => 
  array (
    'group' => 'admin',
    'label' => 'Description typography',
    'default' => 'Description typography',
  ),
  'admin.applies_only_to_the_solo_package_page_description_leave_font_family_em' => 
  array (
    'group' => 'admin',
    'label' => 'Applies only to the solo package page description. Leave font family empty to...',
    'default' => 'Applies only to the solo package page description. Leave font family empty to inherit the site font.',
    'textarea' => true,
  ),
  'admin.info_tooltip' => 
  array (
    'group' => 'admin',
    'label' => 'Info tooltip',
    'default' => 'Info tooltip',
  ),
  'admin.shown_only_as_the_i_tooltip_on_package_list_cards_not_shown_on_the_sol' => 
  array (
    'group' => 'admin',
    'label' => 'Shown only as the i tooltip on package list cards. Not shown on the solo pack...',
    'default' => 'Shown only as the i tooltip on package list cards. Not shown on the solo package page.',
  ),
  'admin.booking_blocks' => 
  array (
    'group' => 'admin',
    'label' => 'Booking blocks',
    'default' => 'Booking blocks',
  ),
  'admin.only_one_booking_block_can_be_active_for_a_package_settings_are_isolat' => 
  array (
    'group' => 'admin',
    'label' => 'Only one booking block can be active for a package. Settings are isolated per...',
    'default' => 'Only one booking block can be active for a package. Settings are isolated per block; only the active block is used for availability, pricing and payment.',
    'textarea' => true,
  ),
  'admin.buffers' => 
  array (
    'group' => 'admin',
    'label' => 'Buffers',
    'default' => 'Buffers',
  ),
  'admin.before' => 
  array (
    'group' => 'admin',
    'label' => 'Before',
    'default' => 'Before',
  ),
  'admin.after' => 
  array (
    'group' => 'admin',
    'label' => 'After',
    'default' => 'After',
  ),
  'admin.buffers_are_package_wide_and_applied_only_by_booking_modes_that_use_ti' => 
  array (
    'group' => 'admin',
    'label' => 'Buffers are package-wide and applied only by booking modes that use time slots.',
    'default' => 'Buffers are package-wide and applied only by booking modes that use time slots.',
  ),
  'admin.working_hours_mode' => 
  array (
    'group' => 'admin',
    'label' => 'Availability schedule',
    'default' => 'Availability schedule',
  ),
  'admin.use_global_working_hours' => 
  array (
    'group' => 'admin',
    'label' => 'Use global working hours',
    'default' => 'Use global working hours',
  ),
  'admin.custom_for_this_package' => 
  array (
    'group' => 'admin',
    'label' => 'Custom schedule for this package',
    'default' => 'Custom schedule for this package',
  ),
  'admin.open_24_7' => 
  array (
    'group' => 'admin',
    'label' => 'Open 24/7',
    'default' => 'Open 24/7',
  ),
  'admin.when_enabled_this_package_is_available_every_day_from_00_00_to_23_59_a' => 
  array (
    'group' => 'admin',
    'label' => 'When enabled, this package is available every day from 00:00 to 23:59 and man...',
    'default' => 'When enabled, this package is available every day from 00:00 to 23:59 and manual rows below are ignored.',
    'textarea' => true,
  ),
  'admin.package_working_hours' => 
  array (
    'group' => 'admin',
    'label' => 'Package working hours',
    'default' => 'Package working hours',
  ),
  'admin.used_only_when_working_hours_mode_is_custom_and_open_24_7_is_disabled' => 
  array (
    'group' => 'admin',
    'label' => 'Used only when Availability schedule is Custom and Open 24/7 is disabled.',
    'default' => 'Used only when Availability schedule is Custom and Open 24/7 is disabled.',
  ),
  'admin.save_package' => 
  array (
    'group' => 'admin',
    'label' => 'Save package',
    'default' => 'Save package',
  ),
  'admin.coupons' => 
  array (
    'group' => 'admin',
    'label' => 'Coupons',
    'default' => 'Coupons',
  ),
  'admin.coupon_saved' => 
  array (
    'group' => 'admin',
    'label' => 'Coupon saved.',
    'default' => 'Coupon saved.',
  ),
  'admin.code' => 
  array (
    'group' => 'admin',
    'label' => 'Code',
    'default' => 'Code',
  ),
  'admin.used' => 
  array (
    'group' => 'admin',
    'label' => 'Used',
    'default' => 'Used',
  ),
  'admin.expires' => 
  array (
    'group' => 'admin',
    'label' => 'Expires',
    'default' => 'Expires',
  ),
  'admin.delete_this_coupon' => 
  array (
    'group' => 'admin',
    'label' => 'Delete this coupon?',
    'default' => 'Delete this coupon?',
  ),
  'admin.no_coupons_yet' => 
  array (
    'group' => 'admin',
    'label' => 'No coupons yet.',
    'default' => 'No coupons yet.',
  ),
  'admin.edit_coupon' => 
  array (
    'group' => 'admin',
    'label' => 'Edit Coupon',
    'default' => 'Edit Coupon',
  ),
  'admin.add_coupon' => 
  array (
    'group' => 'admin',
    'label' => 'Add Coupon',
    'default' => 'Add Coupon',
  ),
  'admin.coupon_setup_guide' => 
  array (
    'group' => 'admin',
    'label' => 'Coupon setup guide',
    'default' => 'Coupon setup guide',
  ),
  'admin.coupons_are_applied_before_payment_use_limits_and_package_restrictions' => 
  array (
    'group' => 'admin',
    'label' => 'Coupons are applied before payment. Use limits and package restrictions to co...',
    'default' => 'Coupons are applied before payment. Use limits and package restrictions to control who can use a discount.',
    'textarea' => true,
  ),
  'admin.example_welcome10_codes_are_stored_uppercase' => 
  array (
    'group' => 'admin',
    'label' => 'Example: WELCOME10. Codes are stored uppercase.',
    'default' => 'Example: WELCOME10. Codes are stored uppercase.',
  ),
  'admin.percent' => 
  array (
    'group' => 'admin',
    'label' => 'Percent',
    'default' => 'Percent',
  ),
  'admin.fixed_amount' => 
  array (
    'group' => 'admin',
    'label' => 'Fixed amount',
    'default' => 'Fixed amount',
  ),
  'admin.percent_discounts_use_values_like_10_for_10_fixed_discounts_use_the_st' => 
  array (
    'group' => 'admin',
    'label' => 'Percent discounts use values like 10 for 10%. Fixed discounts use the store c...',
    'default' => 'Percent discounts use values like 10 for 10%. Fixed discounts use the store currency.',
  ),
  'admin.minimum_amount' => 
  array (
    'group' => 'admin',
    'label' => 'Minimum amount',
    'default' => 'Minimum amount',
  ),
  'admin.set_0_to_allow_the_coupon_for_any_booking_total' => 
  array (
    'group' => 'admin',
    'label' => 'Set 0 to allow the coupon for any booking total.',
    'default' => 'Set 0 to allow the coupon for any booking total.',
  ),
  'admin.usage_limit' => 
  array (
    'group' => 'admin',
    'label' => 'Usage limit',
    'default' => 'Usage limit',
  ),
  'admin.0_means_unlimited' => 
  array (
    'group' => 'admin',
    'label' => '0 means unlimited.',
    'default' => '0 means unlimited.',
  ),
  'admin.usage_per_email' => 
  array (
    'group' => 'admin',
    'label' => 'Usage per email',
    'default' => 'Usage per email',
  ),
  'admin.expires_at' => 
  array (
    'group' => 'admin',
    'label' => 'Expires at',
    'default' => 'Expires at',
  ),
  'admin.leave_empty_if_the_coupon_should_not_expire_automatically' => 
  array (
    'group' => 'admin',
    'label' => 'Leave empty if the coupon should not expire automatically.',
    'default' => 'Leave empty if the coupon should not expire automatically.',
  ),
  'admin.leave_all_unchecked_to_allow_this_coupon_for_all_packages' => 
  array (
    'group' => 'admin',
    'label' => 'Leave all unchecked to allow this coupon for all packages.',
    'default' => 'Leave all unchecked to allow this coupon for all packages.',
  ),
  'admin.coupon_is_active' => 
  array (
    'group' => 'admin',
    'label' => 'Coupon is active',
    'default' => 'Coupon is active',
  ),
  'admin.save_coupon' => 
  array (
    'group' => 'admin',
    'label' => 'Save Coupon',
    'default' => 'Save Coupon',
  ),
  'admin.no_coupon_used' => 
  array (
    'group' => 'admin',
    'label' => 'No coupon used',
    'default' => 'No coupon used',
  ),
  'admin.marketing_emails' => 
  array (
    'group' => 'admin',
    'label' => 'Marketing Emails',
    'default' => 'Marketing Emails',
  ),
  'admin.create_campaign' => 
  array (
    'group' => 'admin',
    'label' => 'Create campaign',
    'default' => 'Create campaign',
  ),
  'admin.marketing_is_paused_because_the_license_period_has_expired_bookings_co' => 
  array (
    'group' => 'admin',
    'label' => 'Marketing is paused because the license period has expired. Bookings continue...',
    'default' => 'Marketing is paused because the license period has expired. Bookings continue to work. Activate the yearly license to resume campaigns and automations.',
    'textarea' => true,
  ),
  'admin.open_license' => 
  array (
    'group' => 'admin',
    'label' => 'Open License',
    'default' => 'Open License',
  ),
  'admin.limited_grace_period_basic_manual_campaigns_remain_available_automatio' => 
  array (
    'group' => 'admin',
    'label' => 'Limited grace period: basic manual campaigns remain available. Automations, a...',
    'default' => 'Limited grace period: basic manual campaigns remain available. Automations, advanced filters, personal coupons and queue tuning are locked until license activation.',
    'textarea' => true,
  ),
  'admin.activate_license' => 
  array (
    'group' => 'admin',
    'label' => 'Activate license',
    'default' => 'Activate license',
  ),
  'admin.campaign_deleted' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign deleted.',
    'default' => 'Campaign deleted.',
  ),
  'admin.come_back_automation_settings_saved' => 
  array (
    'group' => 'admin',
    'label' => 'Come back automation settings saved.',
    'default' => 'Come back automation settings saved.',
  ),
  'admin.come_back_automation_processed_queued_1_d_skipped_2_d' => 
  array (
    'group' => 'admin',
    'label' => 'Come back automation processed. Queued: %1$d, Skipped: %2$d.',
    'default' => 'Come back automation processed. Queued: %1$d, Skipped: %2$d.',
  ),
  'admin.open_created_campaign' => 
  array (
    'group' => 'admin',
    'label' => 'Open created campaign',
    'default' => 'Open created campaign',
  ),
  'admin.after_booking_automation_settings_saved' => 
  array (
    'group' => 'admin',
    'label' => 'After booking automation settings saved.',
    'default' => 'After booking automation settings saved.',
  ),
  'admin.after_booking_automation_processed_queued_1_d_skipped_2_d' => 
  array (
    'group' => 'admin',
    'label' => 'After booking automation processed. Queued: %1$d, Skipped: %2$d.',
    'default' => 'After booking automation processed. Queued: %1$d, Skipped: %2$d.',
  ),
  'admin.come_back_automation' => 
  array (
    'group' => 'admin',
    'label' => 'Come back automation',
    'default' => 'Come back automation',
  ),
  'admin.automatically_queues_a_promo_email_for_customers_who_have_not_booked_f' => 
  array (
    'group' => 'admin',
    'label' => 'Automatically queues a promo email for customers who have not booked for X da...',
    'default' => 'Automatically queues a promo email for customers who have not booked for X days. Emails are sent through the existing marketing queue, so batch limits and retries still apply.',
    'textarea' => true,
  ),
  'admin.enable_come_back_automation' => 
  array (
    'group' => 'admin',
    'label' => 'Enable Come back automation',
    'default' => 'Enable Come back automation',
  ),
  'admin.inactive_for' => 
  array (
    'group' => 'admin',
    'label' => 'Inactive for',
    'default' => 'Inactive for',
  ),
  'admin.example_30_means_customers_whose_last_completed_booking_was_more_than_' => 
  array (
    'group' => 'admin',
    'label' => 'Example: 30 means customers whose last completed booking was more than 30 day...',
    'default' => 'Example: 30 means customers whose last completed booking was more than 30 days ago.',
  ),
  'admin.do_not_repeat_for' => 
  array (
    'group' => 'admin',
    'label' => 'Do not repeat for',
    'default' => 'Do not repeat for',
  ),
  'admin.prevents_the_same_customer_from_receiving_this_automation_too_often_re' => 
  array (
    'group' => 'admin',
    'label' => 'Prevents the same customer from receiving this automation too often. Recommen...',
    'default' => 'Prevents the same customer from receiving this automation too often. Recommended: 60–120 days.',
    'textarea' => true,
  ),
  'admin.template' => 
  array (
    'group' => 'admin',
    'label' => 'Template',
    'default' => 'Template',
  ),
  'admin.coupon' => 
  array (
    'group' => 'admin',
    'label' => 'Coupon',
    'default' => 'Coupon',
  ),
  'admin.no_coupon' => 
  array (
    'group' => 'admin',
    'label' => 'No coupon',
    'default' => 'No coupon',
  ),
  'admin.generate_unique_coupon_per_recipient' => 
  array (
    'group' => 'admin',
    'label' => 'Generate unique coupon per recipient',
    'default' => 'Generate unique coupon per recipient',
  ),
  'admin.subject_override' => 
  array (
    'group' => 'admin',
    'label' => 'Subject override',
    'default' => 'Subject override',
  ),
  'admin.message' => 
  array (
    'group' => 'admin',
    'label' => 'Message',
    'default' => 'Message',
  ),
  'admin.we_miss_you' => 
  array (
    'group' => 'admin',
    'label' => 'We miss you 👋',
    'default' => 'We miss you 👋',
  ),
  'admin.cta' => 
  array (
    'group' => 'admin',
    'label' => 'CTA',
    'default' => 'CTA',
  ),
  'admin.show_cta_button' => 
  array (
    'group' => 'admin',
    'label' => 'Show CTA button',
    'default' => 'Show CTA button',
  ),
  'admin.package_solo_page' => 
  array (
    'group' => 'admin',
    'label' => 'Package solo page',
    'default' => 'Package solo page',
  ),
  'admin.custom_url' => 
  array (
    'group' => 'admin',
    'label' => 'Custom URL',
    'default' => 'Custom URL',
  ),
  'admin.schedule' => 
  array (
    'group' => 'admin',
    'label' => 'Schedule',
    'default' => 'Schedule',
  ),
  'admin.automation_check_runs_hourly_next_check_value_last_run_value' => 
  array (
    'group' => 'admin',
    'label' => 'Automation check runs hourly. Next check: %s. Last run: %s.',
    'default' => 'Automation check runs hourly. Next check: %s. Last run: %s.',
  ),
  'admin.not_scheduled_2' => 
  array (
    'group' => 'admin',
    'label' => 'not scheduled',
    'default' => 'not scheduled',
  ),
  'admin.save_automation' => 
  array (
    'group' => 'admin',
    'label' => 'Save automation',
    'default' => 'Save automation',
  ),
  'admin.run_come_back_automation_now_this_will_create_a_campaign_and_queue_mat' => 
  array (
    'group' => 'admin',
    'label' => 'Run Come back automation now? This will create a campaign and queue matching ...',
    'default' => 'Run Come back automation now? This will create a campaign and queue matching customers.',
  ),
  'admin.run_automation_now' => 
  array (
    'group' => 'admin',
    'label' => 'Run automation now',
    'default' => 'Run automation now',
  ),
  'admin.after_booking_automation' => 
  array (
    'group' => 'admin',
    'label' => 'After booking automation',
    'default' => 'After booking automation',
  ),
  'admin.automatically_queues_a_promo_follow_up_after_a_completed_booking_good_' => 
  array (
    'group' => 'admin',
    'label' => 'Automatically queues a promo follow-up after a completed booking. Good for th...',
    'default' => 'Automatically queues a promo follow-up after a completed booking. Good for thank-you offers, repeat booking discounts and loyalty messages. Emails are sent through the existing marketing queue.',
    'textarea' => true,
  ),
  'admin.enable_after_booking_automation' => 
  array (
    'group' => 'admin',
    'label' => 'Enable After booking automation',
    'default' => 'Enable After booking automation',
  ),
  'admin.send_after' => 
  array (
    'group' => 'admin',
    'label' => 'Send after',
    'default' => 'Send after',
  ),
  'admin.days_after_completed_booking' => 
  array (
    'group' => 'admin',
    'label' => 'days after completed booking',
    'default' => 'days after completed booking',
  ),
  'admin.example_3_means_customers_whose_booking_was_completed_3_days_ago_will_' => 
  array (
    'group' => 'admin',
    'label' => 'Example: 3 means customers whose booking was completed 3 days ago will receiv...',
    'default' => 'Example: 3 means customers whose booking was completed 3 days ago will receive the follow-up.',
    'textarea' => true,
  ),
  'admin.prevents_the_same_customer_from_receiving_this_follow_up_too_often_rec' => 
  array (
    'group' => 'admin',
    'label' => 'Prevents the same customer from receiving this follow-up too often. Recommend...',
    'default' => 'Prevents the same customer from receiving this follow-up too often. Recommended: 14–45 days.',
    'textarea' => true,
  ),
  'admin.thank_you_book_again_with_a_special_offer' => 
  array (
    'group' => 'admin',
    'label' => 'Thank you — book again with a special offer',
    'default' => 'Thank you — book again with a special offer',
  ),
  'admin.thank_you_for_your_booking' => 
  array (
    'group' => 'admin',
    'label' => 'Thank you for your booking ✨',
    'default' => 'Thank you for your booking ✨',
  ),
  'admin.we_hope_everything_went_well_here_is_a_small_offer_for_your_next_booki' => 
  array (
    'group' => 'admin',
    'label' => 'We hope everything went well. Here is a small offer for your next booking.',
    'default' => 'We hope everything went well. Here is a small offer for your next booking.',
  ),
  'admin.save_after_booking_automation' => 
  array (
    'group' => 'admin',
    'label' => 'Save after booking automation',
    'default' => 'Save after booking automation',
  ),
  'admin.run_after_booking_automation_now_this_will_create_a_campaign_and_queue' => 
  array (
    'group' => 'admin',
    'label' => 'Run After booking automation now? This will create a campaign and queue match...',
    'default' => 'Run After booking automation now? This will create a campaign and queue matching customers.',
    'textarea' => true,
  ),
  'admin.run_after_booking_automation_now' => 
  array (
    'group' => 'admin',
    'label' => 'Run after booking automation now',
    'default' => 'Run after booking automation now',
  ),
  'admin.audience' => 
  array (
    'group' => 'admin',
    'label' => 'Audience',
    'default' => 'Audience',
  ),
  'admin.progress' => 
  array (
    'group' => 'admin',
    'label' => 'Progress',
    'default' => 'Progress',
  ),
  'admin.1_d_sent_2_d_3_d_failed_4_d' => 
  array (
    'group' => 'admin',
    'label' => '%1$d%% · sent %2$d/%3$d · failed %4$d',
    'default' => '%1$d%% · sent %2$d/%3$d · failed %4$d',
  ),
  'admin.delete_this_campaign' => 
  array (
    'group' => 'admin',
    'label' => 'Delete this campaign?',
    'default' => 'Delete this campaign?',
  ),
  'admin.no_campaigns_yet' => 
  array (
    'group' => 'admin',
    'label' => 'No campaigns yet.',
    'default' => 'No campaigns yet.',
  ),
  'admin.awaiting_online_payment' => 
  array (
    'group' => 'admin',
    'label' => 'Awaiting online payment',
    'default' => 'Awaiting online payment',
  ),
  'admin.confirmed' => 
  array (
    'group' => 'admin',
    'label' => 'Confirmed',
    'default' => 'Confirmed',
  ),
  'admin.completed' => 
  array (
    'group' => 'admin',
    'label' => 'Completed',
    'default' => 'Completed',
  ),
  'admin.unpaid' => 
  array (
    'group' => 'admin',
    'label' => 'Unpaid',
    'default' => 'Unpaid',
  ),
  'admin.pending' => 
  array (
    'group' => 'admin',
    'label' => 'Pending',
    'default' => 'Pending',
  ),
  'admin.paid' => 
  array (
    'group' => 'admin',
    'label' => 'Paid',
    'default' => 'Paid',
  ),
  'admin.failed' => 
  array (
    'group' => 'admin',
    'label' => 'Failed',
    'default' => 'Failed',
  ),
  'admin.refunded' => 
  array (
    'group' => 'admin',
    'label' => 'Refunded',
    'default' => 'Refunded',
  ),
  'admin.edit_marketing_campaign' => 
  array (
    'group' => 'admin',
    'label' => 'Edit marketing campaign',
    'default' => 'Edit marketing campaign',
  ),
  'admin.create_marketing_campaign' => 
  array (
    'group' => 'admin',
    'label' => 'Create marketing campaign',
    'default' => 'Create marketing campaign',
  ),
  'admin.campaign_saved' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign saved.',
    'default' => 'Campaign saved.',
  ),
  'admin.marketing_queue_settings_saved' => 
  array (
    'group' => 'admin',
    'label' => 'Marketing queue settings saved.',
    'default' => 'Marketing queue settings saved.',
  ),
  'admin.test_email_sent' => 
  array (
    'group' => 'admin',
    'label' => 'Test email sent.',
    'default' => 'Test email sent.',
  ),
  'admin.test_email_failed' => 
  array (
    'group' => 'admin',
    'label' => 'Test email failed.',
    'default' => 'Test email failed.',
  ),
  'admin.campaign_queued_queued_value_skipped_value' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign queued. Queued: %d, Skipped: %d.',
    'default' => 'Campaign queued. Queued: %d, Skipped: %d.',
  ),
  'admin.queue_processed_sent_value_failed_value' => 
  array (
    'group' => 'admin',
    'label' => 'Queue processed. Sent: %d, Failed: %d.',
    'default' => 'Queue processed. Sent: %d, Failed: %d.',
  ),
  'admin.campaign_paused' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign paused.',
    'default' => 'Campaign paused.',
  ),
  'admin.campaign_resumed' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign resumed.',
    'default' => 'Campaign resumed.',
  ),
  'admin.campaign_stopped' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign stopped.',
    'default' => 'Campaign stopped.',
  ),
  'admin.failed_emails_returned_to_queue_value' => 
  array (
    'group' => 'admin',
    'label' => 'Failed emails returned to queue: %d.',
    'default' => 'Failed emails returned to queue: %d.',
  ),
  'admin.marketing_is_paused_because_the_license_period_has_expired_bookings_an' => 
  array (
    'group' => 'admin',
    'label' => 'Marketing is paused because the license period has expired. Bookings and syst...',
    'default' => 'Marketing is paused because the license period has expired. Bookings and system emails continue to work.',
    'textarea' => true,
  ),
  'admin.limited_grace_period_basic_manual_campaigns_are_available_but_advanced' => 
  array (
    'group' => 'admin',
    'label' => 'Limited grace period: basic manual campaigns are available, but advanced filt...',
    'default' => 'Limited grace period: basic manual campaigns are available, but advanced filters, personal coupons, automations, and queue tuning are locked. Queue processing is capped at 5 emails per batch.',
    'textarea' => true,
  ),
  'admin.campaign_name' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign name',
    'default' => 'Campaign name',
  ),
  'admin.email_template' => 
  array (
    'group' => 'admin',
    'label' => 'Email template',
    'default' => 'Email template',
  ),
  'admin.choose_marketing_promo_for_promotional_campaigns_it_supports_headline_' => 
  array (
    'group' => 'admin',
    'label' => 'Choose “Marketing — Promo” for promotional campaigns. It supports {headline},...',
    'default' => 'Choose “Marketing — Promo” for promotional campaigns. It supports {headline}, {message}, {submessage}, {coupon_code} and {cta_button}.',
    'textarea' => true,
  ),
  'admin.leave_empty_to_use_the_selected_template_subject' => 
  array (
    'group' => 'admin',
    'label' => 'Leave empty to use the selected template subject.',
    'default' => 'Leave empty to use the selected template subject.',
  ),
  'admin.marketing_content' => 
  array (
    'group' => 'admin',
    'label' => 'Marketing content',
    'default' => 'Marketing content',
  ),
  'admin.these_fields_are_used_by_the_marketing_promo_template_and_available_as' => 
  array (
    'group' => 'admin',
    'label' => 'These fields are used by the Marketing — Promo template and available as {hea...',
    'default' => 'These fields are used by the Marketing — Promo template and available as {headline}, {message}, and {submessage}.',
    'textarea' => true,
  ),
  'admin.headline' => 
  array (
    'group' => 'admin',
    'label' => 'Headline',
    'default' => 'Headline',
  ),
  'admin.it_has_been_a_while_since_your_last_booking_here_is_a_special_offer_fo' => 
  array (
    'group' => 'admin',
    'label' => 'It has been a while since your last booking. Here is a special offer for your...',
    'default' => 'It has been a while since your last booking. Here is a special offer for your next visit.',
  ),
  'admin.submessage' => 
  array (
    'group' => 'admin',
    'label' => 'Submessage',
    'default' => 'Submessage',
  ),
  'admin.use_your_code_before_it_expires_and_book_your_next_appointment_when_it' => 
  array (
    'group' => 'admin',
    'label' => 'Use your code before it expires and book your next appointment when it suits ...',
    'default' => 'Use your code before it expires and book your next appointment when it suits you.',
  ),
  'admin.all_customers' => 
  array (
    'group' => 'admin',
    'label' => 'All customers',
    'default' => 'All customers',
  ),
  'admin.customers_by_package' => 
  array (
    'group' => 'admin',
    'label' => 'Customers by package',
    'default' => 'Customers by package',
  ),
  'admin.customers_with_completed_bookings' => 
  array (
    'group' => 'admin',
    'label' => 'Customers with completed bookings',
    'default' => 'Customers with completed bookings',
  ),
  'admin.inactive_customers_30_days' => 
  array (
    'group' => 'admin',
    'label' => 'Inactive customers — 30 days',
    'default' => 'Inactive customers — 30 days',
  ),
  'admin.advanced_filters' => 
  array (
    'group' => 'admin',
    'label' => 'Advanced filters',
    'default' => 'Advanced filters',
  ),
  'admin.locked' => 
  array (
    'group' => 'admin',
    'label' => 'locked',
    'default' => 'locked',
  ),
  'admin.current_matching_audience_value_unique_emails' => 
  array (
    'group' => 'admin',
    'label' => 'Current matching audience: %d unique emails.',
    'default' => 'Current matching audience: %d unique emails.',
  ),
  'admin.advanced_audience_filters' => 
  array (
    'group' => 'admin',
    'label' => 'Advanced audience filters',
    'default' => 'Advanced audience filters',
  ),
  'admin.booking_statuses' => 
  array (
    'group' => 'admin',
    'label' => 'Booking statuses',
    'default' => 'Booking statuses',
  ),
  'admin.leave_empty_to_include_every_booking_status_these_filters_are_used_whe' => 
  array (
    'group' => 'admin',
    'label' => 'Leave empty to include every booking status. These filters are used when Audi...',
    'default' => 'Leave empty to include every booking status. These filters are used when Audience is set to Advanced filters.',
    'textarea' => true,
  ),
  'admin.payment_statuses' => 
  array (
    'group' => 'admin',
    'label' => 'Payment statuses',
    'default' => 'Payment statuses',
  ),
  'admin.leave_empty_to_include_every_payment_status' => 
  array (
    'group' => 'admin',
    'label' => 'Leave empty to include every payment status.',
    'default' => 'Leave empty to include every payment status.',
  ),
  'admin.any_time' => 
  array (
    'group' => 'admin',
    'label' => 'Any time',
    'default' => 'Any time',
  ),
  'admin.within_the_last_x_days' => 
  array (
    'group' => 'admin',
    'label' => 'Within the last X days',
    'default' => 'Within the last X days',
  ),
  'admin.older_than_x_days' => 
  array (
    'group' => 'admin',
    'label' => 'Older than X days',
    'default' => 'Older than X days',
  ),
  'admin.booking_count' => 
  array (
    'group' => 'admin',
    'label' => 'Booking count',
    'default' => 'Booking count',
  ),
  'admin.min_2' => 
  array (
    'group' => 'admin',
    'label' => 'Min',
    'default' => 'Min',
  ),
  'admin.max' => 
  array (
    'group' => 'admin',
    'label' => 'Max',
    'default' => 'Max',
  ),
  'admin.use_0_for_no_limit' => 
  array (
    'group' => 'admin',
    'label' => 'Use 0 for no limit.',
    'default' => 'Use 0 for no limit.',
  ),
  'admin.total_spent' => 
  array (
    'group' => 'admin',
    'label' => 'Total spent',
    'default' => 'Total spent',
  ),
  'admin.coupon_behavior' => 
  array (
    'group' => 'admin',
    'label' => 'Coupon behavior',
    'default' => 'Coupon behavior',
  ),
  'admin.any_customer' => 
  array (
    'group' => 'admin',
    'label' => 'Any customer',
    'default' => 'Any customer',
  ),
  'admin.used_any_coupon_before' => 
  array (
    'group' => 'admin',
    'label' => 'Used any coupon before',
    'default' => 'Used any coupon before',
  ),
  'admin.never_used_a_coupon' => 
  array (
    'group' => 'admin',
    'label' => 'Never used a coupon',
    'default' => 'Never used a coupon',
  ),
  'admin.used_selected_coupon_before' => 
  array (
    'group' => 'admin',
    'label' => 'Used selected coupon before',
    'default' => 'Used selected coupon before',
  ),
  'admin.for_used_selected_coupon_before_choose_the_coupon_in_attach_coupon_bel' => 
  array (
    'group' => 'admin',
    'label' => 'For “Used selected coupon before”, choose the coupon in Promotion below.',
    'default' => 'For “Used selected coupon before”, choose the coupon in Promotion below.',
  ),
  'admin.package_filter' => 
  array (
    'group' => 'admin',
    'label' => 'Package filter',
    'default' => 'Package filter',
  ),
  'admin.any_package' => 
  array (
    'group' => 'admin',
    'label' => 'Any package',
    'default' => 'Any package',
  ),
  'admin.attach_coupon' => 
  array (
    'group' => 'admin',
    'label' => 'Promotion',
    'default' => 'Promotion',
  ),
  'admin.no_promotion_information_campaign' => 
  array (
    'group' => 'admin',
    'label' => 'No promotion (information campaign)',
    'default' => 'No promotion (information campaign)',
  ),
  'admin.available_in_templates_as_coupon_code_coupon_discount_coupon_expires' => 
  array (
    'group' => 'admin',
    'label' => 'Select a coupon to create a promotional campaign with a discount, or choose “No promotion (information campaign)” to send an informational email without a coupon. Coupon details are available in templates as {coupon_code}, {coupon_discount}, and {coupon_expires}.',
    'default' => 'Select a coupon to create a promotional campaign with a discount, or choose “No promotion (information campaign)” to send an informational email without a coupon. Coupon details are available in templates as {coupon_code}, {coupon_discount}, and {coupon_expires}.',
  ),
  'admin.personal_coupons' => 
  array (
    'group' => 'admin',
    'label' => 'Personal coupons',
    'default' => 'Personal coupons',
  ),
  'admin.when_enabled_slotera_creates_a_one_use_personal_coupon_for_each_queued' => 
  array (
    'group' => 'admin',
    'label' => 'When enabled, Slotera creates a one-use personal coupon for each queued recip...',
    'default' => 'When enabled, Slotera creates a one-use personal coupon for each queued recipient using the selected coupon as a template. {coupon_code} becomes unique in every email.',
    'textarea' => true,
  ),
  'admin.locked_during_the_limited_grace_period_regular_attached_coupons_still_' => 
  array (
    'group' => 'admin',
    'label' => 'Locked during the limited grace period. Regular attached coupons still work.',
    'default' => 'Locked during the limited grace period. Regular attached coupons still work.',
  ),
  'admin.cta_button' => 
  array (
    'group' => 'admin',
    'label' => 'CTA button',
    'default' => 'CTA button',
  ),
  'admin.add_book_now_cta_button_to_this_campaign_email' => 
  array (
    'group' => 'admin',
    'label' => 'Add “Book now” CTA button to this campaign email',
    'default' => 'Add “Book now” CTA button to this campaign email',
  ),
  'admin.use_cta_button_booking_url_package_url_or_cta_url_inside_html_template' => 
  array (
    'group' => 'admin',
    'label' => 'Use {cta_button}, {booking_url}, {package_url}, or {cta_url} inside HTML temp...',
    'default' => 'Use {cta_button}, {booking_url}, {package_url}, or {cta_url} inside HTML templates. If {cta_button} is not present, Slotera appends the button below the email content.',
    'textarea' => true,
  ),
  'admin.cta_label' => 
  array (
    'group' => 'admin',
    'label' => 'CTA label',
    'default' => 'CTA label',
  ),
  'admin.cta_link_target' => 
  array (
    'group' => 'admin',
    'label' => 'CTA link target',
    'default' => 'CTA link target',
  ),
  'admin.package_page_works_best_when_the_campaign_is_filtered_by_one_package_o' => 
  array (
    'group' => 'admin',
    'label' => 'Package page works best when the campaign is filtered by one package. Otherwi...',
    'default' => 'Package page works best when the campaign is filtered by one package. Otherwise Slotera falls back to the booking page.',
    'textarea' => true,
  ),
  'admin.custom_cta_url' => 
  array (
    'group' => 'admin',
    'label' => 'Custom CTA URL',
    'default' => 'Custom CTA URL',
  ),
  'admin.campaign_status' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign status',
    'default' => 'Campaign status',
  ),
  'admin.queue_progress' => 
  array (
    'group' => 'admin',
    'label' => 'Queue progress',
    'default' => 'Queue progress',
  ),
  'admin.campaign_progress' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign progress',
    'default' => 'Campaign progress',
  ),
  'admin.done_1_d_2_d_pending_3_d_sending_4_d_sent_5_d_failed_6_d_skipped_7_d' => 
  array (
    'group' => 'admin',
    'label' => 'Done: %1$d / %2$d · Pending: %3$d · Sending: %4$d · Sent: %5$d · Failed: %6$d...',
    'default' => 'Done: %1$d / %2$d · Pending: %3$d · Sending: %4$d · Sent: %5$d · Failed: %6$d · Skipped: %7$d',
    'textarea' => true,
  ),
  'admin.last_activity_value' => 
  array (
    'group' => 'admin',
    'label' => 'Last activity: %s',
    'default' => 'Last activity: %s',
  ),
  'admin.never' => 
  array (
    'group' => 'admin',
    'label' => 'never',
    'default' => 'never',
  ),
  'admin.next_cron_run_value' => 
  array (
    'group' => 'admin',
    'label' => 'Next cron run: %s',
    'default' => 'Next cron run: %s',
  ),
  'admin.save_campaign' => 
  array (
    'group' => 'admin',
    'label' => 'Save campaign',
    'default' => 'Save campaign',
  ),
  'admin.marketing_queue_settings' => 
  array (
    'group' => 'admin',
    'label' => 'Marketing queue settings',
    'default' => 'Marketing queue settings',
  ),
  'admin.emails_per_batch' => 
  array (
    'group' => 'admin',
    'label' => 'Emails per batch',
    'default' => 'Emails per batch',
  ),
  'admin.recommended_shared_hosting_5_10_good_vps_15_25_dedicated_server_smtp_2' => 
  array (
    'group' => 'admin',
    'label' => 'Recommended: shared hosting 5–10, good VPS 15–25, dedicated/server SMTP 25–50...',
    'default' => 'Recommended: shared hosting 5–10, good VPS 15–25, dedicated/server SMTP 25–50. If emails fail or the site becomes slow, reduce this value. Default: 10.',
    'textarea' => true,
  ),
  'admin.cron_interval' => 
  array (
    'group' => 'admin',
    'label' => 'Cron interval',
    'default' => 'Cron interval',
  ),
  'admin.value_minutes' => 
  array (
    'group' => 'admin',
    'label' => '%d minutes',
    'default' => '%d minutes',
  ),
  'admin.default_5_minutes_wordpress_cron_runs_when_the_site_receives_visits_so' => 
  array (
    'group' => 'admin',
    'label' => 'Default: 5 minutes. WordPress cron runs when the site receives visits, so low...',
    'default' => 'Default: 5 minutes. WordPress cron runs when the site receives visits, so low-traffic sites may process later.',
    'textarea' => true,
  ),
  'admin.max_attempts' => 
  array (
    'group' => 'admin',
    'label' => 'Max attempts',
    'default' => 'Max attempts',
  ),
  'admin.how_many_times_slotera_should_retry_a_failed_marketing_email_before_le' => 
  array (
    'group' => 'admin',
    'label' => 'How many times Slotera should retry a failed marketing email before leaving i...',
    'default' => 'How many times Slotera should retry a failed marketing email before leaving it failed.',
  ),
  'admin.save_marketing_queue_settings' => 
  array (
    'group' => 'admin',
    'label' => 'Save marketing queue settings',
    'default' => 'Save marketing queue settings',
  ),
  'admin.queue_settings_are_locked_in_the_limited_grace_period_slotera_uses_a_s' => 
  array (
    'group' => 'admin',
    'label' => 'Queue settings are locked in the limited grace period. Slotera uses a safe fi...',
    'default' => 'Queue settings are locked in the limited grace period. Slotera uses a safe fixed batch of 5 emails.',
    'textarea' => true,
  ),
  'admin.preview_and_test' => 
  array (
    'group' => 'admin',
    'label' => 'Preview and test',
    'default' => 'Preview and test',
  ),
  'admin.preview_opens_in_a_popup_and_uses_real_matching_customer_data_when_the' => 
  array (
    'group' => 'admin',
    'label' => 'Preview opens in a popup and uses real matching customer data when the email ...',
    'default' => 'Preview opens in a popup and uses real matching customer data when the email exists in this campaign audience. It does not create queue records, logs, or real unique coupons.',
    'textarea' => true,
  ),
  'admin.preview_test_as_email' => 
  array (
    'group' => 'admin',
    'label' => 'Preview / test as email',
    'default' => 'Preview / test as email',
  ),
  'admin.preview_in_popup' => 
  array (
    'group' => 'admin',
    'label' => 'Preview in popup',
    'default' => 'Preview in popup',
  ),
  'admin.send_test_email_with_preview_data' => 
  array (
    'group' => 'admin',
    'label' => 'Send test email with preview data',
    'default' => 'Send test email with preview data',
  ),
  'admin.campaign_preview' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign preview',
    'default' => 'Campaign preview',
  ),
  'admin.close_preview' => 
  array (
    'group' => 'admin',
    'label' => 'Close preview',
    'default' => 'Close preview',
  ),
  'admin.campaign_queue_controls' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign queue controls',
    'default' => 'Campaign queue controls',
  ),
  'admin.send_creates_a_queue_wp_cron_then_sends_a_safe_number_of_emails_per_ba' => 
  array (
    'group' => 'admin',
    'label' => 'Send creates a queue. WP-Cron then sends a safe number of emails per batch us...',
    'default' => 'Send creates a queue. WP-Cron then sends a safe number of emails per batch using the settings above.',
    'textarea' => true,
  ),
  'admin.queue_this_campaign_for_the_selected_audience' => 
  array (
    'group' => 'admin',
    'label' => 'Queue this campaign for the selected audience?',
    'default' => 'Queue this campaign for the selected audience?',
  ),
  'admin.queue_campaign' => 
  array (
    'group' => 'admin',
    'label' => 'Queue campaign',
    'default' => 'Queue campaign',
  ),
  'admin.process_queue_now' => 
  array (
    'group' => 'admin',
    'label' => 'Process queue now',
    'default' => 'Process queue now',
  ),
  'admin.retry_failed' => 
  array (
    'group' => 'admin',
    'label' => 'Retry failed',
    'default' => 'Retry failed',
  ),
  'admin.pause' => 
  array (
    'group' => 'admin',
    'label' => 'Pause',
    'default' => 'Pause',
  ),
  'admin.resume' => 
  array (
    'group' => 'admin',
    'label' => 'Resume',
    'default' => 'Resume',
  ),
  'admin.stop_this_campaign_pending_emails_will_not_be_sent_unless_you_resume_i' => 
  array (
    'group' => 'admin',
    'label' => 'Stop this campaign? Pending emails will not be sent unless you resume it later.',
    'default' => 'Stop this campaign? Pending emails will not be sent unless you resume it later.',
  ),
  'admin.stop' => 
  array (
    'group' => 'admin',
    'label' => 'Stop',
    'default' => 'Stop',
  ),
  'admin.sending_log' => 
  array (
    'group' => 'admin',
    'label' => 'Sending log',
    'default' => 'Sending log',
  ),
  'admin.sent_value_failed_value_sending_value_pending_value_skipped_value' => 
  array (
    'group' => 'admin',
    'label' => 'Sent: %d · Failed: %d · Sending: %d · Pending: %d · Skipped: %d',
    'default' => 'Sent: %d · Failed: %d · Sending: %d · Pending: %d · Skipped: %d',
  ),
  'admin.sent_at' => 
  array (
    'group' => 'admin',
    'label' => 'Sent at',
    'default' => 'Sent at',
  ),
  'admin.last_try' => 
  array (
    'group' => 'admin',
    'label' => 'Last try',
    'default' => 'Last try',
  ),
  'admin.no_sending_log_yet' => 
  array (
    'group' => 'admin',
    'label' => 'No sending log yet.',
    'default' => 'No sending log yet.',
  ),
  'admin.slotera_license' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera License',
    'default' => 'Slotera License',
  ),
  'admin.license_activated' => 
  array (
    'group' => 'admin',
    'label' => 'License activated.',
    'default' => 'License activated.',
  ),
  'admin.please_enter_a_license_key' => 
  array (
    'group' => 'admin',
    'label' => 'Please enter a license key.',
    'default' => 'Please enter a license key.',
  ),
  'admin.license_deactivated' => 
  array (
    'group' => 'admin',
    'label' => 'License deactivated.',
    'default' => 'License deactivated.',
  ),
  'admin.local_license_fields_refreshed_this_is_a_preparation_step_no_external_' => 
  array (
    'group' => 'admin',
    'label' => 'Local license fields refreshed. This is a preparation step; no external licen...',
    'default' => 'Local license fields refreshed. This is a preparation step; no external license server is connected yet.',
    'textarea' => true,
  ),
  'admin.current_status' => 
  array (
    'group' => 'admin',
    'label' => 'Current status',
    'default' => 'Current status',
  ),
  'admin.full_trial' => 
  array (
    'group' => 'admin',
    'label' => 'Full trial',
    'default' => 'Full trial',
  ),
  'admin.30_days' => 
  array (
    'group' => 'admin',
    'label' => '30 days',
    'default' => '30 days',
  ),
  'admin.grace_period' => 
  array (
    'group' => 'admin',
    'label' => 'Grace period',
    'default' => 'Grace period',
  ),
  'admin.14_days_with_limited_features' => 
  array (
    'group' => 'admin',
    'label' => '14 days with limited features',
    'default' => '14 days with limited features',
  ),
  'admin.trial_started' => 
  array (
    'group' => 'admin',
    'label' => 'Trial started',
    'default' => 'Trial started',
  ),
  'admin.full_trial_ends' => 
  array (
    'group' => 'admin',
    'label' => 'Full trial ends',
    'default' => 'Full trial ends',
  ),
  'admin.grace_ends' => 
  array (
    'group' => 'admin',
    'label' => 'Grace ends',
    'default' => 'Grace ends',
  ),
  'admin.marketing_campaigns' => 
  array (
    'group' => 'admin',
    'label' => 'Marketing campaigns',
    'default' => 'Marketing campaigns',
  ),
  'admin.limited_basic_campaigns' => 
  array (
    'group' => 'admin',
    'label' => 'Limited basic campaigns',
    'default' => 'Limited basic campaigns',
  ),
  'admin.available' => 
  array (
    'group' => 'admin',
    'label' => 'Available',
    'default' => 'Available',
  ),
  'admin.paused' => 
  array (
    'group' => 'admin',
    'label' => 'Paused',
    'default' => 'Paused',
  ),
  'admin.locked_2' => 
  array (
    'group' => 'admin',
    'label' => 'Locked',
    'default' => 'Locked',
  ),
  'admin.queue_tuning' => 
  array (
    'group' => 'admin',
    'label' => 'Queue tuning',
    'default' => 'Queue tuning',
  ),
  'admin.locked_fixed_to_5_emails_per_batch' => 
  array (
    'group' => 'admin',
    'label' => 'Locked; fixed to 5 emails per batch',
    'default' => 'Locked; fixed to 5 emails per batch',
  ),
  'admin.automations' => 
  array (
    'group' => 'admin',
    'label' => 'Automations',
    'default' => 'Automations',
  ),
  'admin.always_available_existing_client_booking_flow_is_not_broken_by_trial_e' => 
  array (
    'group' => 'admin',
    'label' => 'Always available. Existing client booking flow is not broken by trial expirat...',
    'default' => 'Always available. Existing client booking flow is not broken by trial expiration.',
  ),
  'admin.prepared_license_fields' => 
  array (
    'group' => 'admin',
    'label' => 'Prepared license fields',
    'default' => 'Prepared license fields',
  ),
  'admin.these_fields_prepare_slotera_for_a_future_sales_website_and_license_se' => 
  array (
    'group' => 'admin',
    'label' => 'These fields prepare Slotera for a future sales website and license server. F...',
    'default' => 'These fields prepare Slotera for a future sales website and license server. For now they are stored locally only.',
    'textarea' => true,
  ),
  'admin.license_key' => 
  array (
    'group' => 'admin',
    'label' => 'License key',
    'default' => 'License key',
  ),
  'admin.not_entered' => 
  array (
    'group' => 'admin',
    'label' => 'Not entered',
    'default' => 'Not entered',
  ),
  'admin.licensed_domain' => 
  array (
    'group' => 'admin',
    'label' => 'Licensed domain',
    'default' => 'Licensed domain',
  ),
  'admin.license_status' => 
  array (
    'group' => 'admin',
    'label' => 'License status',
    'default' => 'License status',
  ),
  'admin.not_set_yet' => 
  array (
    'group' => 'admin',
    'label' => 'Not set yet',
    'default' => 'Not set yet',
  ),
  'admin.last_checked' => 
  array (
    'group' => 'admin',
    'label' => 'Last checked',
    'default' => 'Last checked',
  ),
  'admin.not_checked_yet' => 
  array (
    'group' => 'admin',
    'label' => 'Not checked yet',
    'default' => 'Not checked yet',
  ),
  'admin.last_check_result' => 
  array (
    'group' => 'admin',
    'label' => 'Last check result',
    'default' => 'Last check result',
  ),
  'admin.refresh_local_license_fields' => 
  array (
    'group' => 'admin',
    'label' => 'Refresh local license fields',
    'default' => 'Refresh local license fields',
  ),
  'admin.activate_yearly_license' => 
  array (
    'group' => 'admin',
    'label' => 'Activate yearly license',
    'default' => 'Activate yearly license',
  ),
  'admin.slotera_uses_one_simple_yearly_license_in_this_local_version_entering_' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera uses one simple yearly license. In this local version, entering any n...',
    'default' => 'Slotera uses one simple yearly license. In this local version, entering any non-empty key activates the license for one year and binds it to this domain locally. Later this can be connected to your payment/license server.',
    'textarea' => true,
  ),
  'admin.deactivate_license' => 
  array (
    'group' => 'admin',
    'label' => 'Deactivate license',
    'default' => 'Deactivate license',
  ),
  'admin.warning' => 
  array (
    'group' => 'admin',
    'label' => 'Warning',
    'default' => 'Warning',
  ),
  'admin.critical' => 
  array (
    'group' => 'admin',
    'label' => 'Critical',
    'default' => 'Critical',
  ),
  'admin.info' => 
  array (
    'group' => 'admin',
    'label' => 'Info',
    'default' => 'Info',
  ),
  'admin.slotera_diagnostics' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera Diagnostics',
    'default' => 'Slotera Diagnostics',
  ),
  'admin.health_checks_for_production_readiness_system_database_pages_booking_s' => 
  array (
    'group' => 'admin',
    'label' => 'Health checks for production readiness: system, database, pages, booking setu...',
    'default' => 'Health checks for production readiness: system, database, pages, booking setup, payments, webhooks and privacy.',
    'textarea' => true,
  ),
  'admin.refresh_checks' => 
  array (
    'group' => 'admin',
    'label' => 'Refresh checks',
    'default' => 'Refresh checks',
  ),
  'admin.open_onboarding' => 
  array (
    'group' => 'admin',
    'label' => 'Open onboarding',
    'default' => 'Open onboarding',
  ),
  'admin.check' => 
  array (
    'group' => 'admin',
    'label' => 'Check',
    'default' => 'Check',
  ),
  'admin.details' => 
  array (
    'group' => 'admin',
    'label' => 'Details',
    'default' => 'Details',
  ),
  'admin.recommendation' => 
  array (
    'group' => 'admin',
    'label' => 'Recommendation',
    'default' => 'Recommendation',
  ),
  'admin.welcome_to_slotera' => 
  array (
    'group' => 'admin',
    'label' => 'Welcome to Slotera',
    'default' => 'Welcome to Slotera',
  ),
  'admin.use_this_setup_checklist_to_create_required_pages_configure_booking_ba' => 
  array (
    'group' => 'admin',
    'label' => 'Use this setup checklist to create required pages, configure booking basics a...',
    'default' => 'Use this setup checklist to create required pages, configure booking basics and verify your installation before going live.',
    'textarea' => true,
  ),
  'admin.onboarding_step_saved' => 
  array (
    'group' => 'admin',
    'label' => 'Onboarding step saved.',
    'default' => 'Onboarding step saved.',
  ),
  'admin.warnings' => 
  array (
    'group' => 'admin',
    'label' => 'Warnings',
    'default' => 'Warnings',
  ),
  'admin.ready_checks' => 
  array (
    'group' => 'admin',
    'label' => 'Ready checks',
    'default' => 'Ready checks',
  ),
  'admin.1_create_required_pages' => 
  array (
    'group' => 'admin',
    'label' => '1. Create required pages',
    'default' => '1. Create required pages',
  ),
  'admin.slotera_needs_pages_for_packages_booking_thank_you_login_and_customer_' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera needs pages for packages, booking, thank-you, login and customer acco...',
    'default' => 'Slotera needs pages for packages, booking, thank-you, login and customer account. This action creates missing pages and binds them automatically.',
    'textarea' => true,
  ),
  'admin.create_bind_missing_pages' => 
  array (
    'group' => 'admin',
    'label' => 'Create / bind missing pages',
    'default' => 'Create / bind missing pages',
  ),
  'admin.2_configure_booking_basics' => 
  array (
    'group' => 'admin',
    'label' => '2. Configure booking basics',
    'default' => '2. Configure booking basics',
  ),
  'admin.currency' => 
  array (
    'group' => 'admin',
    'label' => 'Currency',
    'default' => 'Currency',
  ),
  'admin.checkout_mode' => 
  array (
    'group' => 'admin',
    'label' => 'Checkout mode',
    'default' => 'Checkout mode',
  ),
  'admin.admin_notification_email' => 
  array (
    'group' => 'admin',
    'label' => 'Admin notification email',
    'default' => 'Admin notification email',
  ),
  'admin.allow_pay_on_arrival' => 
  array (
    'group' => 'admin',
    'label' => 'Allow pay on arrival',
    'default' => 'Allow pay on arrival',
  ),
  'admin.enable_email_notifications' => 
  array (
    'group' => 'admin',
    'label' => 'Enable email notifications',
    'default' => 'Enable email notifications',
  ),
  'admin.save_basics' => 
  array (
    'group' => 'admin',
    'label' => 'Save basics',
    'default' => 'Save basics',
  ),
  'admin.3_next_setup_steps' => 
  array (
    'group' => 'admin',
    'label' => '3. Next setup steps',
    'default' => '3. Next setup steps',
  ),
  'admin.create_your_first_package' => 
  array (
    'group' => 'admin',
    'label' => 'Create your first package',
    'default' => 'Create your first package',
  ),
  'admin.configure_working_hours' => 
  array (
    'group' => 'admin',
    'label' => 'Configure working hours',
    'default' => 'Configure working hours',
  ),
  'admin.configure_payments_if_needed' => 
  array (
    'group' => 'admin',
    'label' => 'Configure payments if needed',
    'default' => 'Configure payments if needed',
  ),
  'admin.run_diagnostics' => 
  array (
    'group' => 'admin',
    'label' => 'Run diagnostics',
    'default' => 'Run diagnostics',
  ),
  'admin.mark_setup_complete' => 
  array (
    'group' => 'admin',
    'label' => 'Mark setup complete',
    'default' => 'Mark setup complete',
  ),
  'admin.skip_for_now' => 
  array (
    'group' => 'admin',
    'label' => 'Skip for now',
    'default' => 'Skip for now',
  ),
  'admin.remove_image' => 
  array (
    'group' => 'admin',
    'label' => 'Remove image',
    'default' => 'Remove image',
  ),
  'admin.translations' => 
  array (
    'group' => 'admin',
    'label' => 'Translations',
    'default' => 'Translations',
  ),
  'admin.translations_saved' => 
  array (
    'group' => 'admin',
    'label' => 'Translations saved.',
    'default' => 'Translations saved.',
  ),
  'admin.language' => 
  array (
    'group' => 'admin',
    'label' => 'Language',
    'default' => 'Language',
  ),
  'admin.change_language' => 
  array (
    'group' => 'admin',
    'label' => 'Change language',
    'default' => 'Change language',
  ),
  'admin.key' => 
  array (
    'group' => 'admin',
    'label' => 'Key',
    'default' => 'Key',
  ),
  'admin.english_default' => 
  array (
    'group' => 'admin',
    'label' => 'English default',
    'default' => 'English default',
  ),
  'admin.save_translations' => 
  array (
    'group' => 'admin',
    'label' => 'Save translations',
    'default' => 'Save translations',
  ),
  'admin.show_low_availability_notices' => 
  array (
    'group' => 'admin',
    'label' => 'Show low availability notices',
    'default' => 'Show low availability notices',
  ),
  'admin.shows_messages_like_only_1_spot_left_during_booking' => 
  array (
    'group' => 'admin',
    'label' => 'Shows messages like “Only 1 spot left” during booking.',
    'default' => 'Shows messages like “Only 1 spot left” during booking.',
  ),
  'admin.low_availability_threshold' => 
  array (
    'group' => 'admin',
    'label' => 'Low availability threshold',
    'default' => 'Low availability threshold',
  ),
  'admin.show_the_notice_when_remaining_capacity_is_at_or_below_this_number' => 
  array (
    'group' => 'admin',
    'label' => 'Show the notice when remaining capacity is at or below this number.',
    'default' => 'Show the notice when remaining capacity is at or below this number.',
  ),
  'admin.night' => 
  array (
    'group' => 'admin',
    'label' => 'night',
    'default' => 'night',
  ),
  'admin.day_2' => 
  array (
    'group' => 'admin',
    'label' => 'day',
    'default' => 'day',
  ),
  'admin.hour' => 
  array (
    'group' => 'admin',
    'label' => 'hour',
    'default' => 'hour',
  ),
  'admin.price_on_request' => 
  array (
    'group' => 'admin',
    'label' => 'Price on request',
    'default' => 'Price on request',
  ),
  'admin.from_value' => 
  array (
    'group' => 'admin',
    'label' => 'From %s',
    'default' => 'From %s',
  ),
  'admin.per_event' => 
  array (
    'group' => 'admin',
    'label' => 'per event',
    'default' => 'per event',
  ),
  'admin.add_event_price' => 
  array (
    'group' => 'admin',
    'label' => 'Add event price',
    'default' => 'Add event price',
  ),
  'admin.set_a_price_or_switch_price_display_to_price_on_request' => 
  array (
    'group' => 'admin',
    'label' => 'Set a price, or switch Price display to Price on request.',
    'default' => 'Set a price, or switch Price display to Price on request.',
  ),
  'admin.set_a_fixed_slot_duration' => 
  array (
    'group' => 'admin',
    'label' => 'Set a fixed slot duration.',
    'default' => 'Set a fixed slot duration.',
  ),
  'admin.set_a_slot_price' => 
  array (
    'group' => 'admin',
    'label' => 'Set a slot price.',
    'default' => 'Set a slot price.',
  ),
  'admin.set_a_duration_step' => 
  array (
    'group' => 'admin',
    'label' => 'Set a duration step.',
    'default' => 'Set a duration step.',
  ),
  'admin.set_an_hourly_price' => 
  array (
    'group' => 'admin',
    'label' => 'Set an hourly price.',
    'default' => 'Set an hourly price.',
  ),
  'admin.add_at_least_one_event_with_start_date_end_date_and_seats' => 
  array (
    'group' => 'admin',
    'label' => 'Add at least one event with start date, end date and seats.',
    'default' => 'Add at least one event with start date, end date and seats.',
  ),
  'admin.add_at_least_one_active_room_unit_with_capacity' => 
  array (
    'group' => 'admin',
    'label' => 'Add at least one active room/unit with capacity.',
    'default' => 'Add at least one active room/unit with capacity.',
  ),
  'admin.set_the_base_date_range_price' => 
  array (
    'group' => 'admin',
    'label' => 'Set the base date-range price.',
    'default' => 'Set the base date-range price.',
  ),
  'admin.frontend_card_preview' => 
  array (
    'group' => 'admin',
    'label' => 'Frontend card preview',
    'default' => 'Frontend card preview',
  ),
  'admin.package_title' => 
  array (
    'group' => 'admin',
    'label' => 'Package title',
    'default' => 'Package title',
  ),
  'admin.this_block_has_the_minimum_settings_needed_to_publish' => 
  array (
    'group' => 'admin',
    'label' => 'This block has the minimum settings needed to publish.',
    'default' => 'This block has the minimum settings needed to publish.',
  ),
  'admin.before_publishing' => 
  array (
    'group' => 'admin',
    'label' => 'Before publishing:',
    'default' => 'Before publishing:',
  ),
  'admin.dynamic_pricing_taxes_vat' => 
  array (
    'group' => 'admin',
    'label' => 'Dynamic pricing & Taxes / VAT',
    'default' => 'Dynamic pricing & Taxes / VAT',
  ),
  'admin.dynamic_pricing' => 
  array (
    'group' => 'admin',
    'label' => 'Dynamic pricing',
    'default' => 'Dynamic pricing',
  ),
  'admin.enable_dynamic_pricing' => 
  array (
    'group' => 'admin',
    'label' => 'Enable dynamic pricing',
    'default' => 'Enable dynamic pricing',
  ),
  'admin.shows_a_customer_facing_offer_and_reduces_the_package_price_before_cou' => 
  array (
    'group' => 'admin',
    'label' => 'Shows a customer-facing offer and reduces the package price before coupons an...',
    'default' => 'Shows a customer-facing offer and reduces the package price before coupons and tax.',
  ),
  'admin.weekend_offer_discount' => 
  array (
    'group' => 'admin',
    'label' => 'Weekend offer discount',
    'default' => 'Weekend offer discount',
  ),
  'admin.seasonal_offer_period' => 
  array (
    'group' => 'admin',
    'label' => 'Seasonal offer period',
    'default' => 'Seasonal offer period',
  ),
  'admin.taxes_vat' => 
  array (
    'group' => 'admin',
    'label' => 'Taxes / VAT',
    'default' => 'Taxes / VAT',
  ),
  'admin.enable_tax_vat_calculation' => 
  array (
    'group' => 'admin',
    'label' => 'Enable tax/VAT calculation',
    'default' => 'Enable tax/VAT calculation',
  ),
  'admin.label' => 
  array (
    'group' => 'admin',
    'label' => 'Label',
    'default' => 'Label',
  ),
  'admin.rate' => 
  array (
    'group' => 'admin',
    'label' => 'Rate',
    'default' => 'Rate',
  ),
  'admin.added_on_top' => 
  array (
    'group' => 'admin',
    'label' => 'Added on top',
    'default' => 'Added on top',
  ),
  'admin.included_in_price' => 
  array (
    'group' => 'admin',
    'label' => 'Included in price',
    'default' => 'Included in price',
  ),
  'admin.checkout_options' => 
  array (
    'group' => 'admin',
    'label' => 'Checkout options',
    'default' => 'Checkout options',
  ),
  'admin.pay_on_arrival_booking_only' => 
  array (
    'group' => 'admin',
    'label' => 'Pay on arrival / booking only',
    'default' => 'Pay on arrival / booking only',
  ),
  'admin.full_payment' => 
  array (
    'group' => 'admin',
    'label' => 'Full payment',
    'default' => 'Full payment',
  ),
  'admin.prepayment_deposit' => 
  array (
    'group' => 'admin',
    'label' => 'Prepayment / deposit',
    'default' => 'Prepayment / deposit',
  ),
  'admin.you_can_enable_multiple_checkout_options_the_customer_chooses_one_opti' => 
  array (
    'group' => 'admin',
    'label' => 'You can enable multiple checkout options. The customer chooses one option dur...',
    'default' => 'You can enable multiple checkout options. The customer chooses one option during booking.',
  ),
  'admin.pay_on_arrival_booking_status' => 
  array (
    'group' => 'admin',
    'label' => 'Pay on arrival booking status',
    'default' => 'Pay on arrival booking status',
  ),
  'admin.confirm_immediately' => 
  array (
    'group' => 'admin',
    'label' => 'Confirm immediately',
    'default' => 'Confirm immediately',
  ),
  'admin.booking_status_stays_confirmed_unpaid_pending_payment_status_tracks_wh' => 
  array (
    'group' => 'admin',
    'label' => 'Booking status stays confirmed; unpaid/pending payment status tracks whether ...',
    'default' => 'Booking status stays confirmed; unpaid/pending payment status tracks whether money still needs to be collected.',
    'textarea' => true,
  ),
  'admin.deposit' => 
  array (
    'group' => 'admin',
    'label' => 'Deposit',
    'default' => 'Deposit',
  ),
  'admin.fixed' => 
  array (
    'group' => 'admin',
    'label' => 'Fixed',
    'default' => 'Fixed',
  ),
  'admin.simple_booking' => 
  array (
    'group' => 'admin',
    'label' => 'Booking Request',
    'default' => 'Booking Request',
  ),
  'admin.no_date_no_time_just_a_direct_booking_request' => 
  array (
    'group' => 'admin',
    'label' => 'No date, no time — just a direct booking/request.',
    'default' => 'No date, no time — just a direct booking/request.',
  ),
  'admin.use_this_for_services_where_the_business_confirms_details_later_such_a' => 
  array (
    'group' => 'admin',
    'label' => 'Use this for services where the business confirms details later, such as cate...',
    'default' => 'Use this for services where the business confirms details later, such as catering, custom rentals, shows or quote requests.',
    'textarea' => true,
  ),
  'admin.simple_booking_setup' => 
  array (
    'group' => 'admin',
    'label' => 'Booking Request setup',
    'default' => 'Booking Request setup',
  ),
  'admin.use_when_customers_should_book_or_request_without_choosing_a_date_or_t' => 
  array (
    'group' => 'admin',
    'label' => 'Use when customers should book or request without choosing a date or time.',
    'default' => 'Use when customers should book or request without choosing a date or time.',
  ),
  'admin.price_display' => 
  array (
    'group' => 'admin',
    'label' => 'Price display',
    'default' => 'Price display',
  ),
  'admin.fixed_price' => 
  array (
    'group' => 'admin',
    'label' => 'Fixed price',
    'default' => 'Fixed price',
  ),
  'admin.from_price' => 
  array (
    'group' => 'admin',
    'label' => 'From price',
    'default' => 'From price',
  ),
  'admin.base_price' => 
  array (
    'group' => 'admin',
    'label' => 'Base price',
    'default' => 'Base price',
  ),
  'admin.the_default_amount_shown_before_coupons_tax_deposits_or_extra_services' => 
  array (
    'group' => 'admin',
    'label' => 'The default amount shown before coupons, tax, deposits or extra services are ...',
    'default' => 'The default amount shown before coupons, tax, deposits or extra services are applied.',
  ),
  'admin.unlimited' => 
  array (
    'group' => 'admin',
    'label' => 'Unlimited',
    'default' => 'Unlimited',
  ),
  'admin.limited_total_quantity' => 
  array (
    'group' => 'admin',
    'label' => 'Limited total quantity',
    'default' => 'Limited total quantity',
  ),
  'admin.maximum_number_of_bookings_allowed_at_the_same_time_for_this_package' => 
  array (
    'group' => 'admin',
    'label' => 'Maximum number of bookings allowed at the same time for this package.',
    'default' => 'Maximum number of bookings allowed at the same time for this package.',
  ),
  'admin.confirm_immediately_when_no_online_payment_is_required' => 
  array (
    'group' => 'admin',
    'label' => 'Confirm immediately when no online payment is required',
    'default' => 'Confirm immediately when no online payment is required',
  ),
  'admin.included_services' => 
  array (
    'group' => 'admin',
    'label' => 'Included services',
    'default' => 'Included services',
  ),
  'admin.extra_services' => 
  array (
    'group' => 'admin',
    'label' => 'Extra services',
    'default' => 'Extra services',
  ),
  'admin.optional_add_ons_shown_during_booking_disable_an_item_to_keep_it_saved' => 
  array (
    'group' => 'admin',
    'label' => 'Optional add-ons shown during booking. Disable an item to keep it saved witho...',
    'default' => 'Optional add-ons shown during booking. Disable an item to keep it saved without offering it to customers.',
    'textarea' => true,
  ),
  'admin.price_type' => 
  array (
    'group' => 'admin',
    'label' => 'Price type',
    'default' => 'Price type',
  ),
  'admin.extra_option' => 
  array (
    'group' => 'admin',
    'label' => 'Extra option',
    'default' => 'Extra option',
  ),
  'admin.once' => 
  array (
    'group' => 'admin',
    'label' => 'Once',
    'default' => 'Once',
  ),
  'admin.per_guest' => 
  array (
    'group' => 'admin',
    'label' => 'Per guest',
    'default' => 'Per guest',
  ),
  'admin.advanced_json_import_export' => 
  array (
    'group' => 'admin',
    'label' => 'Advanced JSON import/export',
    'default' => 'Advanced JSON import/export',
  ),
  'admin.campaign_note' => 
  array (
    'group' => 'admin',
    'label' => 'Campaign note',
    'default' => 'Campaign note',
  ),
  'admin.optional_public_urgency_note_shown_on_frontend_e_g_offer_ends_soon' => 
  array (
    'group' => 'admin',
    'label' => 'Optional public urgency note shown on frontend, e.g. Offer ends soon.',
    'default' => 'Optional public urgency note shown on frontend, e.g. Offer ends soon.',
  ),
  'admin.fixed_time_slot_bookings_with_a_fixed_duration_and_capacity_per_slot' => 
  array (
    'group' => 'admin',
    'label' => 'Fixed time-slot bookings with a fixed duration and capacity per slot.',
    'default' => 'Fixed time-slot bookings with a fixed duration and capacity per slot.',
  ),
  'admin.fixed_slot_setup' => 
  array (
    'group' => 'admin',
    'label' => 'Fixed slot setup',
    'default' => 'Fixed slot setup',
  ),
  'admin.use_when_customers_choose_one_predefined_slot_with_a_fixed_duration' => 
  array (
    'group' => 'admin',
    'label' => 'Use when customers choose one predefined slot with a fixed duration.',
    'default' => 'Use when customers choose one predefined slot with a fixed duration.',
  ),
  'admin.show_duration_on_frontend' => 
  array (
    'group' => 'admin',
    'label' => 'Show duration on frontend',
    'default' => 'Show duration on frontend',
  ),
  'admin.applies_to_package_cards_solo_page_and_booking_form_summary' => 
  array (
    'group' => 'admin',
    'label' => 'Applies to package cards, solo page and booking form summary.',
    'default' => 'Applies to package cards, solo page and booking form summary.',
  ),
  'admin.max_bookings_per_slot' => 
  array (
    'group' => 'admin',
    'label' => 'Max bookings per slot',
    'default' => 'Max bookings per slot',
  ),
  'admin.flexible' => 
  array (
    'group' => 'admin',
    'label' => 'Flexible',
    'default' => 'Flexible',
  ),
  'admin.customer_chooses_a_start_time_while_duration_step_capacity_are_control' => 
  array (
    'group' => 'admin',
    'label' => 'Customer chooses a start time, while duration/step/capacity are controlled by...',
    'default' => 'Customer chooses a start time, while duration/step/capacity are controlled by this block.',
  ),
  'admin.flexible_time_setup' => 
  array (
    'group' => 'admin',
    'label' => 'Flexible time setup',
    'default' => 'Flexible time setup',
  ),
  'admin.use_when_customers_choose_a_start_time_while_this_block_controls_durat' => 
  array (
    'group' => 'admin',
    'label' => 'Use when customers choose a start time while this block controls duration, st...',
    'default' => 'Use when customers choose a start time while this block controls duration, step and capacity.',
    'textarea' => true,
  ),
  'admin.slot_step' => 
  array (
    'group' => 'admin',
    'label' => 'Slot step',
    'default' => 'Slot step',
  ),
  'admin.date_range_inventory' => 
  array (
    'group' => 'admin',
    'label' => 'Date range inventory',
    'default' => 'Date range inventory',
  ),
  'admin.date_based_bookings_with_rooms_units_check_in_check_out_included_servi' => 
  array (
    'group' => 'admin',
    'label' => 'Date-based bookings with rooms/units, check-in/check-out, included services a...',
    'default' => 'Date-based bookings with rooms/units, check-in/check-out, included services and extras.',
  ),
  'admin.date_range_inventory_setup' => 
  array (
    'group' => 'admin',
    'label' => 'Date range inventory setup',
    'default' => 'Date range inventory setup',
  ),
  'admin.use_for_nights_days_hours_rooms_units_or_admin_scheduled_tours_events' => 
  array (
    'group' => 'admin',
    'label' => 'Use for nights/days/hours, rooms/units, or admin-scheduled tours/events.',
    'default' => 'Use for nights/days/hours, rooms/units, or admin-scheduled tours/events.',
  ),
  'admin.date_range_inventory_type' => 
  array (
    'group' => 'admin',
    'label' => 'Date range inventory type',
    'default' => 'Date range inventory type',
  ),
  'admin.customer_chooses_dates' => 
  array (
    'group' => 'admin',
    'label' => 'Customer chooses dates',
    'default' => 'Customer chooses dates',
  ),
  'admin.admin_scheduled_events_departures' => 
  array (
    'group' => 'admin',
    'label' => 'Admin scheduled events / departures',
    'default' => 'Admin scheduled events / departures',
  ),
  'admin.use_scheduled_events_when_the_admin_decides_exact_start_end_dates_and_' => 
  array (
    'group' => 'admin',
    'label' => 'Use scheduled events when the admin decides exact start/end dates and availab...',
    'default' => 'Use scheduled events when the admin decides exact start/end dates and available seats.',
  ),
  'admin.price_unit' => 
  array (
    'group' => 'admin',
    'label' => 'Price unit',
    'default' => 'Price unit',
  ),
  'admin.fixed_for_range' => 
  array (
    'group' => 'admin',
    'label' => 'Fixed for range',
    'default' => 'Fixed for range',
  ),
  'admin.per_day' => 
  array (
    'group' => 'admin',
    'label' => 'Per day',
    'default' => 'Per day',
  ),
  'admin.per_night' => 
  array (
    'group' => 'admin',
    'label' => 'Per night',
    'default' => 'Per night',
  ),
  'admin.per_hour' => 
  array (
    'group' => 'admin',
    'label' => 'Per hour',
    'default' => 'Per hour',
  ),
  'admin.hourly_price' => 
  array (
    'group' => 'admin',
    'label' => 'Hourly price',
    'default' => 'Hourly price',
  ),
  'admin.check_in' => 
  array (
    'group' => 'admin',
    'label' => 'Check-in',
    'default' => 'Check-in',
  ),
  'admin.check_out' => 
  array (
    'group' => 'admin',
    'label' => 'Check-out',
    'default' => 'Check-out',
  ),
  'admin.min_nights' => 
  array (
    'group' => 'admin',
    'label' => 'Min nights',
    'default' => 'Min nights',
  ),
  'admin.max_nights' => 
  array (
    'group' => 'admin',
    'label' => 'Max nights',
    'default' => 'Max nights',
  ),
  'admin.rooms_units' => 
  array (
    'group' => 'admin',
    'label' => 'Rooms / units',
    'default' => 'Rooms / units',
  ),
  'admin.add_rooms_vehicles_apartments_or_other_bookable_units_disable_a_unit_t' => 
  array (
    'group' => 'admin',
    'label' => 'Add rooms, vehicles, apartments or other bookable units. Disable a unit to ke...',
    'default' => 'Add rooms, vehicles, apartments or other bookable units. Disable a unit to keep it saved without making it bookable.',
    'textarea' => true,
  ),
  'admin.capacity_maximum_number_of_bookings_allowed_at_the_same_time_for_this_' => 
  array (
    'group' => 'admin',
    'label' => 'Capacity: Maximum number of bookings allowed at the same time for this unit.',
    'default' => 'Capacity: Maximum number of bookings allowed at the same time for this unit.',
  ),
  'admin.room_1' => 
  array (
    'group' => 'admin',
    'label' => 'Room 1',
    'default' => 'Room 1',
  ),
  'admin.capacity_maximum_number_of_bookings_allowed_at_the_same_time_for_this__2' => 
  array (
    'group' => 'admin',
    'label' => 'Capacity. Maximum number of bookings allowed at the same time for this unit.',
    'default' => 'Capacity. Maximum number of bookings allowed at the same time for this unit.',
  ),
  'admin.add_room_unit' => 
  array (
    'group' => 'admin',
    'label' => 'Add room / unit',
    'default' => 'Add room / unit',
  ),
  'admin.date_inventory_overrides' => 
  array (
    'group' => 'admin',
    'label' => 'Date inventory overrides',
    'default' => 'Date inventory overrides',
  ),
  'admin.optional_date_periods_for_closing_inventory_changing_capacity_or_overr' => 
  array (
    'group' => 'admin',
    'label' => 'Optional date periods for closing inventory, changing capacity or overriding ...',
    'default' => 'Optional date periods for closing inventory, changing capacity or overriding price.',
  ),
  'admin.start_date' => 
  array (
    'group' => 'admin',
    'label' => 'Start date',
    'default' => 'Start date',
  ),
  'admin.end_date' => 
  array (
    'group' => 'admin',
    'label' => 'End date',
    'default' => 'End date',
  ),
  'admin.available_quantity' => 
  array (
    'group' => 'admin',
    'label' => 'Available quantity',
    'default' => 'Available quantity',
  ),
  'admin.price_override' => 
  array (
    'group' => 'admin',
    'label' => 'Price override',
    'default' => 'Price override',
  ),
  'admin.closed' => 
  array (
    'group' => 'admin',
    'label' => 'Closed',
    'default' => 'Closed',
  ),
  'admin.add_date_override' => 
  array (
    'group' => 'admin',
    'label' => 'Add date override',
    'default' => 'Add date override',
  ),
  'admin.create_ready_made_packages_departures_customers_see_the_start_end_date' => 
  array (
    'group' => 'admin',
    'label' => 'Create ready-made packages/departures. Customers see the start/end date, pric...',
    'default' => 'Create ready-made packages/departures. Customers see the start/end date, price and seats left, then go straight to details.',
    'textarea' => true,
  ),
  'admin.start_date_time' => 
  array (
    'group' => 'admin',
    'label' => 'Start date/time',
    'default' => 'Start date/time',
  ),
  'admin.end_date_time' => 
  array (
    'group' => 'admin',
    'label' => 'End date/time',
    'default' => 'End date/time',
  ),
  'admin.use_time' => 
  array (
    'group' => 'admin',
    'label' => 'Use time',
    'default' => 'Use time',
  ),
  'admin.free_seats' => 
  array (
    'group' => 'admin',
    'label' => 'Free seats',
    'default' => 'Free seats',
  ),
  'admin.group_tour' => 
  array (
    'group' => 'admin',
    'label' => 'Group tour',
    'default' => 'Group tour',
  ),
  'admin.add_event' => 
  array (
    'group' => 'admin',
    'label' => 'Add event',
    'default' => 'Add event',
  ),
  'admin.airport_transfer' => 
  array (
    'group' => 'admin',
    'label' => 'Airport transfer',
    'default' => 'Airport transfer',
  ),
  'admin.add_extra_service' => 
  array (
    'group' => 'admin',
    'label' => 'Add extra service',
    'default' => 'Add extra service',
  ),
  'admin.price_type_once_per_day_per_night_per_hour_or_per_guest' => 
  array (
    'group' => 'admin',
    'label' => 'price_type: once, per_day, per_night, per_hour or per_guest.',
    'default' => 'price_type: once, per_day, per_night, per_hour or per_guest.',
  ),
  'admin.online_payment_is_not_available_please_contact_the_site_owner' => 
  array (
    'group' => 'admin',
    'label' => 'Online payment is not available. Please contact the site owner.',
    'default' => 'Online payment is not available. Please contact the site owner.',
  ),
  'admin.existing_payment_session_reused' => 
  array (
    'group' => 'admin',
    'label' => 'Existing payment session reused.',
    'default' => 'Existing payment session reused.',
  ),
  'admin.payment_creation_is_already_in_progress_please_try_again_in_a_moment' => 
  array (
    'group' => 'admin',
    'label' => 'Payment creation is already in progress. Please try again in a moment.',
    'default' => 'Payment creation is already in progress. Please try again in a moment.',
  ),
  'admin.custom_payment' => 
  array (
    'group' => 'admin',
    'label' => 'Custom payment',
    'default' => 'Custom payment',
  ),
  'admin.please_complete_payment_using_value' => 
  array (
    'group' => 'admin',
    'label' => 'Please complete payment using %s.',
    'default' => 'Please complete payment using %s.',
  ),
  'admin.your_name' => 
  array (
    'group' => 'admin',
    'label' => 'Your name',
    'default' => 'Your name',
  ),
  'admin.you_example_com' => 
  array (
    'group' => 'admin',
    'label' => 'you@example.com',
    'default' => 'you@example.com',
  ),
  'admin.phone_number' => 
  array (
    'group' => 'admin',
    'label' => 'Phone number',
    'default' => 'Phone number',
  ),
  'admin.city' => 
  array (
    'group' => 'admin',
    'label' => 'City',
    'default' => 'City',
  ),
  'admin.state_county' => 
  array (
    'group' => 'admin',
    'label' => 'State / County',
    'default' => 'State / County',
  ),
  'admin.state_county_or_region' => 
  array (
    'group' => 'admin',
    'label' => 'State, county or region',
    'default' => 'State, county or region',
  ),
  'admin.address' => 
  array (
    'group' => 'admin',
    'label' => 'Address',
    'default' => 'Address',
  ),
  'admin.street_address' => 
  array (
    'group' => 'admin',
    'label' => 'Street address',
    'default' => 'Street address',
  ),
  'admin.company' => 
  array (
    'group' => 'admin',
    'label' => 'Company',
    'default' => 'Company',
  ),
  'admin.company_name' => 
  array (
    'group' => 'admin',
    'label' => 'Company name',
    'default' => 'Company name',
  ),
  'admin.additional_notes_wishes' => 
  array (
    'group' => 'admin',
    'label' => 'Additional notes / wishes',
    'default' => 'Additional notes / wishes',
  ),
  'admin.tell_us_about_any_wishes_or_special_requests' => 
  array (
    'group' => 'admin',
    'label' => 'Tell us about any wishes or special requests...',
    'default' => 'Tell us about any wishes or special requests...',
  ),
  'admin.invalid_or_expired_cancellation_link' => 
  array (
    'group' => 'admin',
    'label' => 'Invalid or expired cancellation link.',
    'default' => 'Invalid or expired cancellation link.',
  ),
  'admin.completed_bookings_cannot_be_cancelled' => 
  array (
    'group' => 'admin',
    'label' => 'Completed bookings cannot be cancelled.',
    'default' => 'Completed bookings cannot be cancelled.',
  ),
  'admin.invalid_or_expired_reschedule_link' => 
  array (
    'group' => 'admin',
    'label' => 'Invalid or expired reschedule link.',
    'default' => 'Invalid or expired reschedule link.',
  ),
  'admin.cancelled_bookings_cannot_be_rescheduled' => 
  array (
    'group' => 'admin',
    'label' => 'Cancelled bookings cannot be rescheduled.',
    'default' => 'Cancelled bookings cannot be rescheduled.',
  ),
  'admin.completed_bookings_cannot_be_rescheduled' => 
  array (
    'group' => 'admin',
    'label' => 'Completed bookings cannot be rescheduled.',
    'default' => 'Completed bookings cannot be rescheduled.',
  ),
  'admin.this_booking_has_already_been_rescheduled_from_the_client_account' => 
  array (
    'group' => 'admin',
    'label' => 'This booking has already been rescheduled from the client account.',
    'default' => 'This booking has already been rescheduled from the client account.',
  ),
  'admin.invalid_reschedule_date_or_time' => 
  array (
    'group' => 'admin',
    'label' => 'Invalid reschedule date or time.',
    'default' => 'Invalid reschedule date or time.',
  ),
  'admin.bookings_cannot_be_rescheduled_to_past_dates_or_times' => 
  array (
    'group' => 'admin',
    'label' => 'Bookings cannot be rescheduled to past dates or times.',
    'default' => 'Bookings cannot be rescheduled to past dates or times.',
  ),
  'admin.selected_time_slot_is_no_longer_available' => 
  array (
    'group' => 'admin',
    'label' => 'Selected time slot is no longer available.',
    'default' => 'Selected time slot is no longer available.',
  ),
  'admin.booking_could_not_be_rescheduled' => 
  array (
    'group' => 'admin',
    'label' => 'Booking could not be rescheduled.',
    'default' => 'Booking could not be rescheduled.',
  ),
  'admin.this_time_slot_is_being_booked_right_now_please_try_again_in_a_moment' => 
  array (
    'group' => 'admin',
    'label' => 'This time slot is being booked right now. Please try again in a moment.',
    'default' => 'This time slot is being booked right now. Please try again in a moment.',
  ),
  'admin.please_enter_a_valid_email_address' => 
  array (
    'group' => 'admin',
    'label' => 'Please enter a valid email address.',
    'default' => 'Please enter a valid email address.',
  ),
  'admin.please_complete_all_required_fields' => 
  array (
    'group' => 'admin',
    'label' => 'Please complete all required fields.',
    'default' => 'Please complete all required fields.',
  ),
  'admin.please_choose_a_payment_method' => 
  array (
    'group' => 'admin',
    'label' => 'Please choose a payment method.',
    'default' => 'Please choose a payment method.',
  ),
  'admin.selected_payment_method_does_not_exist' => 
  array (
    'group' => 'admin',
    'label' => 'Selected payment method does not exist.',
    'default' => 'Selected payment method does not exist.',
  ),
  'admin.booking_not_found' => 
  array (
    'group' => 'admin',
    'label' => 'Booking not found.',
    'default' => 'Booking not found.',
  ),
  'admin.you_do_not_have_permission_to_perform_this_action' => 
  array (
    'group' => 'admin',
    'label' => 'You do not have permission to perform this action.',
    'default' => 'You do not have permission to perform this action.',
  ),
  'admin.booking_request_could_not_be_processed' => 
  array (
    'group' => 'admin',
    'label' => 'Booking request could not be processed.',
    'default' => 'Booking request could not be processed.',
  ),
  'admin.please_wait_a_moment_before_submitting_the_booking_form' => 
  array (
    'group' => 'admin',
    'label' => 'Please wait a moment before submitting the booking form.',
    'default' => 'Please wait a moment before submitting the booking form.',
  ),
  'admin.too_many_booking_attempts_please_try_again_later' => 
  array (
    'group' => 'admin',
    'label' => 'Too many booking attempts. Please try again later.',
    'default' => 'Too many booking attempts. Please try again later.',
  ),
  'admin.please_complete_the_security_challenge' => 
  array (
    'group' => 'admin',
    'label' => 'Please complete the security challenge.',
    'default' => 'Please complete the security challenge.',
  ),
  'admin.security_challenge_could_not_be_verified_please_try_again' => 
  array (
    'group' => 'admin',
    'label' => 'Security challenge could not be verified. Please try again.',
    'default' => 'Security challenge could not be verified. Please try again.',
  ),
  'admin.security_challenge_failed_please_try_again' => 
  array (
    'group' => 'admin',
    'label' => 'Security challenge failed. Please try again.',
    'default' => 'Security challenge failed. Please try again.',
  ),
  'admin.coupon_code_is_not_valid' => 
  array (
    'group' => 'admin',
    'label' => 'Coupon code is not valid.',
    'default' => 'Coupon code is not valid.',
  ),
  'admin.coupon_code_has_expired' => 
  array (
    'group' => 'admin',
    'label' => 'Coupon code has expired.',
    'default' => 'Coupon code has expired.',
  ),
  'admin.coupon_usage_limit_has_been_reached' => 
  array (
    'group' => 'admin',
    'label' => 'Coupon usage limit has been reached.',
    'default' => 'Coupon usage limit has been reached.',
  ),
  'admin.coupon_is_not_available_for_this_package' => 
  array (
    'group' => 'admin',
    'label' => 'Coupon is not available for this package.',
    'default' => 'Coupon is not available for this package.',
  ),
  'admin.coupon_minimum_amount_has_not_been_reached' => 
  array (
    'group' => 'admin',
    'label' => 'Coupon minimum amount has not been reached.',
    'default' => 'Coupon minimum amount has not been reached.',
  ),
  'admin.this_coupon_has_already_been_used_by_this_email' => 
  array (
    'group' => 'admin',
    'label' => 'This coupon has already been used by this email.',
    'default' => 'This coupon has already been used by this email.',
  ),
  'admin.active_license' => 
  array (
    'group' => 'admin',
    'label' => 'Active license',
    'default' => 'Active license',
  ),
  'admin.slotera_license_is_active' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera license is active.',
    'default' => 'Slotera license is active.',
  ),
  'admin.selected_event_is_not_available' => 
  array (
    'group' => 'admin',
    'label' => 'Selected event is not available.',
    'default' => 'Selected event is not available.',
  ),
  'admin.scheduled_event' => 
  array (
    'group' => 'admin',
    'label' => 'Scheduled event',
    'default' => 'Scheduled event',
  ),
  'admin.default_unit' => 
  array (
    'group' => 'admin',
    'label' => 'Default unit',
    'default' => 'Default unit',
  ),
  'admin.please_choose_valid_check_in_and_check_out_dates' => 
  array (
    'group' => 'admin',
    'label' => 'Please choose valid check-in and check-out dates.',
    'default' => 'Please choose valid check-in and check-out dates.',
  ),
  'admin.please_choose_a_future_date_range' => 
  array (
    'group' => 'admin',
    'label' => 'Please choose a future date range.',
    'default' => 'Please choose a future date range.',
  ),
  'admin.stay_length_must_be_between_1_d_and_2_d_nights' => 
  array (
    'group' => 'admin',
    'label' => 'Stay length must be between %1$d and %2$d nights.',
    'default' => 'Stay length must be between %1$d and %2$d nights.',
  ),
  'admin.selected_room_is_not_available' => 
  array (
    'group' => 'admin',
    'label' => 'Selected room is not available.',
    'default' => 'Selected room is not available.',
  ),
  'admin.selected_payment_option_is_not_available_for_this_package' => 
  array (
    'group' => 'admin',
    'label' => 'Selected payment option is not available for this package.',
    'default' => 'Selected payment option is not available for this package.',
  ),
  'admin.pay_in_full' => 
  array (
    'group' => 'admin',
    'label' => 'Pay in full',
    'default' => 'Pay in full',
  ),
  'admin.prepay_deposit' => 
  array (
    'group' => 'admin',
    'label' => 'Prepay / deposit',
    'default' => 'Prepay / deposit',
  ),
  'admin.pay_on_arrival' => 
  array (
    'group' => 'admin',
    'label' => 'Pay on arrival',
    'default' => 'Pay on arrival',
  ),
  'admin.day_3' => 
  array (
    'group' => 'admin',
    'label' => ' / day',
    'default' => ' / day',
  ),
  'admin.night_2' => 
  array (
    'group' => 'admin',
    'label' => ' / night',
    'default' => ' / night',
  ),
  'admin.hour_2' => 
  array (
    'group' => 'admin',
    'label' => ' / hour',
    'default' => ' / hour',
  ),
  'admin.weekend_offer' => 
  array (
    'group' => 'admin',
    'label' => 'Weekend offer',
    'default' => 'Weekend offer',
  ),
  'admin.seasonal_offer' => 
  array (
    'group' => 'admin',
    'label' => 'Seasonal offer',
    'default' => 'Seasonal offer',
  ),
  'admin.special_offer' => 
  array (
    'group' => 'admin',
    'label' => 'Special offer',
    'default' => 'Special offer',
  ),
  'admin.value_your_client_account_login_link' => 
  array (
    'group' => 'admin',
    'label' => '[%s] Your client account login link',
    'default' => '[%s] Your client account login link',
  ),
  'admin.use_the_button_below_to_open_your_booking_account_this_link_expires_in' => 
  array (
    'group' => 'admin',
    'label' => 'Use the button below to open your booking account. This link expires in 30 mi...',
    'default' => 'Use the button below to open your booking account. This link expires in 30 minutes.',
  ),
  'admin.open_my_bookings' => 
  array (
    'group' => 'admin',
    'label' => 'Open my bookings',
    'default' => 'Open my bookings',
  ),
  'admin.if_you_did_not_request_this_email_you_can_ignore_it' => 
  array (
    'group' => 'admin',
    'label' => 'If you did not request this email, you can ignore it.',
    'default' => 'If you did not request this email, you can ignore it.',
  ),
  'admin.every_five_minutes' => 
  array (
    'group' => 'admin',
    'label' => 'Every five minutes',
    'default' => 'Every five minutes',
  ),
  'admin.webhook_endpoint_is_disabled_or_missing' => 
  array (
    'group' => 'admin',
    'label' => 'Webhook endpoint is disabled or missing.',
    'default' => 'Webhook endpoint is disabled or missing.',
  ),
  'admin.webhook_endpoint_url_is_not_allowed' => 
  array (
    'group' => 'admin',
    'label' => 'Webhook endpoint URL is not allowed.',
    'default' => 'Webhook endpoint URL is not allowed.',
  ),
  'admin.http_value_returned_by_webhook_endpoint' => 
  array (
    'group' => 'admin',
    'label' => 'HTTP %d returned by webhook endpoint.',
    'default' => 'HTTP %d returned by webhook endpoint.',
  ),
  'admin.slotera_booking' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera Booking',
    'default' => 'Slotera Booking',
  ),
  'admin.slotera_bookings' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera bookings',
    'default' => 'Slotera bookings',
  ),
  'admin.booking_id' => 
  array (
    'group' => 'admin',
    'label' => 'Booking ID',
    'default' => 'Booking ID',
  ),
  'admin.customer_name' => 
  array (
    'group' => 'admin',
    'label' => 'Customer name',
    'default' => 'Customer name',
  ),
  'admin.booking_date' => 
  array (
    'group' => 'admin',
    'label' => 'Booking date',
    'default' => 'Booking date',
  ),
  'admin.start_time' => 
  array (
    'group' => 'admin',
    'label' => 'Start time',
    'default' => 'Start time',
  ),
  'admin.end_time' => 
  array (
    'group' => 'admin',
    'label' => 'End time',
    'default' => 'End time',
  ),
  'admin.booking_status' => 
  array (
    'group' => 'admin',
    'label' => 'Booking status',
    'default' => 'Booking status',
  ),
  'admin.payment_status' => 
  array (
    'group' => 'admin',
    'label' => 'Payment status',
    'default' => 'Payment status',
  ),
  'admin.total_amount' => 
  array (
    'group' => 'admin',
    'label' => 'Total amount',
    'default' => 'Total amount',
  ),
  'admin.notes' => 
  array (
    'group' => 'admin',
    'label' => 'Notes',
    'default' => 'Notes',
  ),
  'admin.created_at' => 
  array (
    'group' => 'admin',
    'label' => 'Created at',
    'default' => 'Created at',
  ),
  'admin.deleted_customer' => 
  array (
    'group' => 'admin',
    'label' => 'Deleted customer',
    'default' => 'Deleted customer',
  ),
  'admin.slotera_booking_records_were_anonymized_and_retained_for_operational_a' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera booking records were anonymized and retained for operational/accounti...',
    'default' => 'Slotera booking records were anonymized and retained for operational/accounting integrity.',
  ),
  'admin.selected_package_is_not_available' => 
  array (
    'group' => 'admin',
    'label' => 'Selected package is not available.',
    'default' => 'Selected package is not available.',
  ),
  'admin.bookings_cannot_be_created_for_past_dates_or_times' => 
  array (
    'group' => 'admin',
    'label' => 'Bookings cannot be created for past dates or times.',
    'default' => 'Bookings cannot be created for past dates or times.',
  ),
  'admin.booking_could_not_be_created' => 
  array (
    'group' => 'admin',
    'label' => 'Booking could not be created.',
    'default' => 'Booking could not be created.',
  ),
  'admin.payment_could_not_be_started_please_try_again_or_choose_another_paymen' => 
  array (
    'group' => 'admin',
    'label' => 'Payment could not be started. Please try again or choose another payment meth...',
    'default' => 'Payment could not be started. Please try again or choose another payment method.',
  ),
  'admin.this_package_is_no_longer_available' => 
  array (
    'group' => 'admin',
    'label' => 'This package is no longer available.',
    'default' => 'This package is no longer available.',
  ),
  'admin.selected_event_is_no_longer_available' => 
  array (
    'group' => 'admin',
    'label' => 'Selected event is no longer available.',
    'default' => 'Selected event is no longer available.',
  ),
  'admin.selected_room_is_no_longer_available_for_these_dates' => 
  array (
    'group' => 'admin',
    'label' => 'Selected room is no longer available for these dates.',
    'default' => 'Selected room is no longer available for these dates.',
  ),
  'admin.cancelled_or_completed_bookings_cannot_be_confirmed_manually' => 
  array (
    'group' => 'admin',
    'label' => 'Cancelled or completed bookings cannot be confirmed manually.',
    'default' => 'Cancelled or completed bookings cannot be confirmed manually.',
  ),
  'admin.cancelled_bookings_cannot_be_completed' => 
  array (
    'group' => 'admin',
    'label' => 'Cancelled bookings cannot be completed.',
    'default' => 'Cancelled bookings cannot be completed.',
  ),
  'admin.admin_ui' => 
  array (
    'group' => 'admin',
    'label' => 'Admin UI',
    'default' => 'Admin UI',
  ),
  'admin.frontend_ui' => 
  array (
    'group' => 'admin',
    'label' => 'Frontend UI',
    'default' => 'Frontend UI',
  ),
  'admin.emails' => 
  array (
    'group' => 'admin',
    'label' => 'Emails',
    'default' => 'Emails',
  ),
  'admin.url_is_required' => 
  array (
    'group' => 'admin',
    'label' => 'URL is required.',
    'default' => 'URL is required.',
  ),
  'admin.enter_a_valid_absolute_url' => 
  array (
    'group' => 'admin',
    'label' => 'Enter a valid absolute URL.',
    'default' => 'Enter a valid absolute URL.',
  ),
  'admin.only_https_urls_are_allowed' => 
  array (
    'group' => 'admin',
    'label' => 'Only HTTPS URLs are allowed.',
    'default' => 'Only HTTPS URLs are allowed.',
  ),
  'admin.urls_with_embedded_credentials_are_not_allowed' => 
  array (
    'group' => 'admin',
    'label' => 'URLs with embedded credentials are not allowed.',
    'default' => 'URLs with embedded credentials are not allowed.',
  ),
  'admin.url_host_is_missing' => 
  array (
    'group' => 'admin',
    'label' => 'URL host is missing.',
    'default' => 'URL host is missing.',
  ),
  'admin.local_private_reserved_and_internal_hosts_are_not_allowed' => 
  array (
    'group' => 'admin',
    'label' => 'Local, private, reserved and internal hosts are not allowed.',
    'default' => 'Local, private, reserved and internal hosts are not allowed.',
  ),
  'admin.url_host_could_not_be_resolved' => 
  array (
    'group' => 'admin',
    'label' => 'URL host could not be resolved.',
    'default' => 'URL host could not be resolved.',
  ),
  'admin.url_resolves_to_a_local_private_reserved_or_internal_address' => 
  array (
    'group' => 'admin',
    'label' => 'URL resolves to a local, private, reserved or internal address.',
    'default' => 'URL resolves to a local, private, reserved or internal address.',
  ),
  'admin.url_was_rejected_by_site_policy' => 
  array (
    'group' => 'admin',
    'label' => 'URL was rejected by site policy.',
    'default' => 'URL was rejected by site policy.',
  ),
  'admin.plugin_activation_failed' => 
  array (
    'group' => 'admin',
    'label' => 'Plugin activation failed',
    'default' => 'Plugin activation failed',
  ),
  'admin.slotera_setup_is_not_completed_yet' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera setup is not completed yet.',
    'default' => 'Slotera setup is not completed yet.',
  ),
  'admin.license_settings' => 
  array (
    'group' => 'admin',
    'label' => 'License settings',
    'default' => 'License settings',
  ),
  'admin.slotera_booking_database_upgrade_is_running' => 
  array (
    'group' => 'admin',
    'label' => 'Slotera Booking database upgrade is running.',
    'default' => 'Slotera Booking database upgrade is running.',
  ),
  'admin.booking_slot_hashes_are_being_rebuilt_in_small_batches_to_avoid_lockin' => 
  array (
    'group' => 'admin',
    'label' => 'Booking slot hashes are being rebuilt in small batches to avoid locking large...',
    'default' => 'Booking slot hashes are being rebuilt in small batches to avoid locking large tables.',
  ),
  'admin.processed_rows_value' => 
  array (
    'group' => 'admin',
    'label' => 'Processed rows: %d.',
    'default' => 'Processed rows: %d.',
  ),
  'admin.run_next_batch_now' => 
  array (
    'group' => 'admin',
    'label' => 'Run next batch now',
    'default' => 'Run next batch now',
  ),
  'admin.select_images' => 
  array (
    'group' => 'admin',
    'label' => 'Select images',
    'default' => 'Select images',
  ),
  'admin.add_selected_images' => 
  array (
    'group' => 'admin',
    'label' => 'Add selected images',
    'default' => 'Add selected images',
  ),
);
    }
}
