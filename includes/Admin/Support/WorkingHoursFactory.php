<?php

declare(strict_types=1);

namespace Slotera\Admin\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class WorkingHoursFactory
{
    /**
     * @return array<int,array{is_enabled:int,start_time:string,end_time:string}>
     */
    public static function open_247_hours(): array
    {
        $hours = [];
        for ($day = 1; $day <= 7; $day++) {
            $hours[$day] = [
                'is_enabled' => 1,
                'start_time' => '00:00',
                'end_time' => '23:59',
            ];
        }

        return $hours;
    }
}
