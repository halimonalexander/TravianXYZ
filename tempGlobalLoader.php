<?php

use HalimonAlexander\{
    PDODecorator\DSN,
    PDODecorator\PDODecorator,
    Registry\Registry
};

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/autoloader.php';

$registry = Registry::getInstance();

if (!$registry->has('db')) {
    DSN::set([
        "driver"   => 'mysql',
        "host"     => \SQL_SERVER,
        "username" => \SQL_USER,
        "password" => \SQL_PASS,
        "database" => \SQL_DB,
    ]);
    
    $registry
        ->set('db', new PDODecorator())
        ->set('tablePrefix', \TB_PREFIX);
}
