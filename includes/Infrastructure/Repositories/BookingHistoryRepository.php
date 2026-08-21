<?php
declare(strict_types=1);
namespace Slotera\Infrastructure\Repositories;
use Slotera\Core\Database;
use Slotera\Application\Security\DataRedactor;
if (!defined('ABSPATH')) { exit; }
final class BookingHistoryRepository {
    public function create(array $d): int {
        global $wpdb; $t=Database::booking_history_table();
        $ok=$wpdb->insert($t,[
            'booking_id'=>(int)($d['booking_id']??0),
            'event'=>sanitize_key((string)($d['event']??'updated')),
            'old_status'=>isset($d['old_status'])?sanitize_key((string)$d['old_status']):null,
            'new_status'=>isset($d['new_status'])?sanitize_key((string)$d['new_status']):null,
            'old_payment_status'=>isset($d['old_payment_status'])?sanitize_key((string)$d['old_payment_status']):null,
            'new_payment_status'=>isset($d['new_payment_status'])?sanitize_key((string)$d['new_payment_status']):null,
            'actor_type'=>sanitize_key((string)($d['actor_type']??(is_user_logged_in()?'user':'system'))),
            'actor_id'=>(int)($d['actor_id']??get_current_user_id()),
            'message'=>sanitize_text_field(DataRedactor::text((string)($d['message']??''))),
            'payload_json'=>wp_json_encode(DataRedactor::payload($d['payload']??[]), JSON_UNESCAPED_UNICODE),
            'created_at'=>current_time('mysql'),
        ]);
        return $ok?(int)$wpdb->insert_id:0;
    }
    public function get_by_booking(int $booking_id, int $limit=50): array {
        global $wpdb; $t=Database::booking_history_table();
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t} WHERE booking_id=%d ORDER BY created_at DESC,id DESC LIMIT %d",$booking_id,$limit),ARRAY_A) ?: [];
    }

    public function count_event(int $booking_id, string $event): int {
        global $wpdb; $t=Database::booking_history_table();
        $event = sanitize_key($event);
        if ($booking_id <= 0 || $event === '') { return 0; }
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE booking_id=%d AND event=%s", $booking_id, $event));
    }
}

