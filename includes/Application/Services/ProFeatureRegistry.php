<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class ProFeatureRegistry
{
    /** @return array<string,array<string,mixed>> */
    public function features(): array
    {
        return [
            'marketing' => [
                'title' => __('Marketing', 'slotera-booking'),
                'status' => 'active',
                'menu_slug' => 'slotera-marketing',
                'description' => __('Production-ready customer segments, queued campaigns, test sends, follow-up automations, opt-out handling and marketing logs.', 'slotera-booking'),
                'items' => [
                    __('Manual email campaigns', 'slotera-booking'),
                    __('Audience filters and customer segments', 'slotera-booking'),
                    __('Come-back and after-booking automations', 'slotera-booking'),
                    __('Preview, test sending, safe queue processing and retries', 'slotera-booking'),
                    __('One-click unsubscribe suppression for marketing recipients', 'slotera-booking'),
                ],
            ],
            'shared_database_network' => [
                'title' => __('Shared Database Network', 'slotera-booking'),
                'status' => Database::uses_shared_tables() ? 'active' : 'available',
                'menu_slug' => 'slotera-shared-network',
                'description' => __('Connect several independent WordPress/SEO sites to one shared Slotera booking database.', 'slotera-booking'),
                'items' => [
                    __('Shared packages, customers, bookings and availability', 'slotera-booking'),
                    __('Independent WordPress content and SEO per domain', 'slotera-booking'),
                    __('Health checks for shared tables and prefixes', 'slotera-booking'),
                    __('Safe opt-in through wp-config.php', 'slotera-booking'),
                ],
            ],
            'payments' => [
                'title' => __('Payments', 'slotera-booking'),
                'status' => 'active',
                'menu_slug' => 'slotera-payments',
                'description' => __('Configure currencies, checkout options and offline payment methods for bookings, full payments and deposits.', 'slotera-booking'),
                'items' => [
                    __('Pay on arrival and bank transfer methods', 'slotera-booking'),
                    __('Full payment and deposit options per package', 'slotera-booking'),
                    __('Custom offline payment methods', 'slotera-booking'),
                    __('Manual paid/unpaid confirmation from bookings', 'slotera-booking'),
                ],
            ],
            'analytics' => [
                'title' => __('Analytics', 'slotera-booking'),
                'status' => 'active',
                'menu_slug' => 'slotera-analytics',
                'description' => __('Production booking analytics with funnel, revenue, service performance, source and weekday reports.', 'slotera-booking'),
                'items' => [
                    __('Booking funnel and conversion overview', 'slotera-booking'),
                    __('Expected revenue, paid revenue and average order value', 'slotera-booking'),
                    __('Top services and source performance', 'slotera-booking'),
                    __('Booking weekday distribution', 'slotera-booking'),
                ],
            ],
            'white_label' => [
                'title' => __('White Label', 'slotera-booking'),
                'status' => 'active',
                'menu_slug' => 'slotera-white-label',
                'description' => __('Agency/client branding options for Pro installations: product name, admin logo, footer text and plugin-list branding.', 'slotera-booking'),
                'items' => [
                    __('Custom brand and product names', 'slotera-booking'),
                    __('Admin logo URL and footer text', 'slotera-booking'),
                    __('Plugin list metadata/description override', 'slotera-booking'),
                    __('Optional Slotera vendor branding suppression', 'slotera-booking'),
                ],
            ],
        ];
    }
}
