<?php

declare(strict_types=1);

namespace Slotera\Core\Migrations;

use Slotera\Core\Database;

if (!defined('ABSPATH')) { exit; }

final class Version_1_0_963 implements MigrationInterface
{
    public static function apply(): void
    {
        global $wpdb;
        $campaigns = Database::marketing_campaigns_table();
        $logs = Database::marketing_logs_table();

        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$campaigns}", 0) ?: [];
        if (!in_array('source', $columns, true)) {
            $wpdb->query("ALTER TABLE {$campaigns} ADD source VARCHAR(20) NOT NULL DEFAULT 'coupon' AFTER marketing_submessage, ADD KEY source (source)");
        }
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$campaigns}", 0) ?: [];
        if (!in_array('automation_type', $columns, true)) {
            $wpdb->query("ALTER TABLE {$campaigns} ADD automation_type VARCHAR(30) NOT NULL DEFAULT '' AFTER source, ADD KEY automation_type (automation_type)");
        }

        $wpdb->query("UPDATE {$campaigns} c SET c.source='automation' WHERE EXISTS (SELECT 1 FROM {$logs} l WHERE l.campaign_id=c.id AND l.payload_json LIKE '%\"automation\":%')");
        $wpdb->query($wpdb->prepare("UPDATE {$campaigns} SET source='automation', automation_type='come_back' WHERE name LIKE %s", 'Come back automation —%'));
        $wpdb->query($wpdb->prepare("UPDATE {$campaigns} SET source='automation', automation_type='after_booking' WHERE name LIKE %s", 'After booking automation —%'));
        $wpdb->query("UPDATE {$campaigns} c INNER JOIN {$logs} l ON l.campaign_id=c.id SET c.automation_type='come_back' WHERE c.source='automation' AND c.automation_type='' AND l.payload_json LIKE '%\"automation\":\"come_back\"%'");
        $wpdb->query("UPDATE {$campaigns} c INNER JOIN {$logs} l ON l.campaign_id=c.id SET c.automation_type='after_booking' WHERE c.source='automation' AND c.automation_type='' AND l.payload_json LIKE '%\"automation\":\"after_booking\"%'");
    }
}
