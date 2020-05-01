<?php

use HalimonAlexander\{
    PDODecorator\DSN,
    PDODecorator\PDODecorator,
    Registry\Registry
};

require_once './vendor/autoload.php';
require_once './autoloader.php';

DSN::set([
    "driver"   => 'mysql',
    "host"     => \SQL_SERVER,
    "username" => \SQL_USER,
    "password" => \SQL_PASS,
    "database" => \SQL_DB,
]);
$db = new PDODecorator();

(Registry::getInstance())
    ->set('db', $db)
    ->set('tablePrefix', \TB_PREFIX);
