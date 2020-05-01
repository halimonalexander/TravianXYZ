<?php

spl_autoload_register(function ($class) {
    $basedir = __DIR__  . '/';
    $classFilename = str_replace('\\', '/', $class) . '.php';
    
    $fullPath = $basedir . $classFilename;
    
    if (file_exists($fullPath)) {
        include_once($fullPath);
    }
});
