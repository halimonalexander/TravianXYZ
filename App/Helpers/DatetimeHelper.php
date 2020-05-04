<?php

namespace App\Helpers;

class DatetimeHelper
{
    public static function currentTime($date = null): string
    {
        if ($date === null) {
            $date = time();
        }
        
        return date("H:i:s", $date);
    }
    
    public static function secondsToTime(int $seconds)
    {
        return sprintf(
            '%02d:%02d:%d',
            $seconds / 3600,
            ($seconds - floor($seconds / 3600) * 3600) / 60,
            $seconds % 60
        );
    }
}
