<?php

namespace App\Helpers;

class DatetimeHelper
{
    public static function currentTime(): string
    {
        return date("H:i:s");
    }
}
