<?php

namespace App\Helpers;

class TraceHelper
{
    public static function getTimer()
    {
        return microtime(true);
    }
    
    public static function getDiff(float $start)
    {
        return self::getTimer() - $start;
    }
    
    public static function getDiffInSeconds(float $start)
    {
        return number_format(self::getDiff($start) * 100, 1);
    }
}
