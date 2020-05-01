<?php

namespace App\Helpers;

class ResponseHelper
{
    public static function redirect(string $location, int $redirectCode = 302)
    {
        header("Location: " . $location, true, $redirectCode);
        exit;
    }
    
    public static function redirectToRoute(string $route)
    {
    
    }
}
