<?php
declare(strict_types=1);
namespace Slotera\Infrastructure\Repositories;
use Slotera\Core\Database;
if (!defined('ABSPATH')) { exit; }
final class WorkingHoursRepository {
    public function get_for_scope(string $scope_type='global',int $scope_id=0): array { global $wpdb; $t=Database::working_hours_table(); return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t} WHERE scope_type=%s AND scope_id=%d ORDER BY weekday ASC,start_time ASC",sanitize_key($scope_type),$scope_id),ARRAY_A) ?: []; }
    public function get_all_global(): array { $rows=$this->get_for_scope('global',0); $out=[]; foreach($rows as $r){$out[(int)$r['weekday']]=$r;} return $out; }
    public function get_for_weekday(string $scope_type,int $scope_id,int $weekday): array { global $wpdb; $t=Database::working_hours_table(); return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t} WHERE scope_type=%s AND scope_id=%d AND weekday=%d AND is_enabled=1 ORDER BY start_time ASC",sanitize_key($scope_type),$scope_id,$weekday),ARRAY_A) ?: []; }
    public function replace_scope_hours(string $scope_type,int $scope_id,array $hours): bool { global $wpdb; $t=Database::working_hours_table(); $scope_type=sanitize_key($scope_type); $scope_id=max(0,$scope_id); $wpdb->delete($t,['scope_type'=>$scope_type,'scope_id'=>$scope_id],['%s','%d']); foreach($hours as $weekday=>$row){$now=current_time('mysql'); $wpdb->insert($t,['scope_type'=>$scope_type,'scope_id'=>$scope_id,'weekday'=>max(1,min(7,(int)$weekday)),'start_time'=>$this->time((string)($row['start_time']??'09:00')),'end_time'=>$this->time((string)($row['end_time']??'17:00')),'is_enabled'=>!empty($row['is_enabled'])?1:0,'created_at'=>$now,'updated_at'=>$now]);} do_action('sltr_data_changed', 'working_hours_updated', ['package_id' => $scope_type === 'package' ? $scope_id : 0]); return true; }
    public function replace_global(array $hours): bool { return $this->replace_scope_hours('global',0,$hours); }
    private function time(string $time): string { $time=trim($time); if(preg_match('/^\d{2}:\d{2}$/',$time)){return $time.':00';} if(preg_match('/^\d{2}:\d{2}:\d{2}$/',$time)){return $time;} return '09:00:00'; }
}
