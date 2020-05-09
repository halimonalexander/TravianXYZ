<?php

use HalimonAlexander\{
    PDODecorator\DSN,
    PDODecorator\PDODecorator,
    Registry\Registry
};
use App\Helpers\ResponseHelper;
use GameEngine\{
    Alliance,
    Automation,
    Battle,
    Building,
    Form,
    Logging,
    Mailer,
    Market,
    Message,
    Multisort,
    MyGenerator,
    Database\MysqliModel,
    Profile,
    Ranking,
    Session,
    Technology,
    Units,
    Village,
};

ob_start(); // Enesure, that no more header already been sent error not showing up again
mb_internal_encoding("UTF-8"); // Add for utf8 varriables.

require_once 'vendor/autoload.php';
require_once 'autoloader.php';

if (!file_exists('GameEngine/config.php') &&
    !file_exists('../GameEngine/config.php') &&
    !file_exists('../../GameEngine/config.php') &&
    !file_exists('../../config.php')
) {
    ResponseHelper::redirect('install/');
}

// data
require_once "GameEngine/Data/buidata.php";
require_once "GameEngine/Data/cp.php";
require_once "GameEngine/Data/cel.php";
require_once "GameEngine/Data/resdata.php";
require_once "GameEngine/Data/unitdata.php";
require_once "GameEngine/Data/hero_full.php";

// constants
require_once "GameEngine/config.php";
require_once "GameEngine/Lang/" . LANG . ".php";

// classes
$registry = Registry::getInstance();

$mailer = new Mailer();
$generator = new MyGenerator();
$multisort = new Multisort();
$database = new MysqliModel();

// Protection is not a class, but depends on database. And as most of classes have business logic in constructor,
// so we should call it asap. For now let it bee so
require_once "GameEngine/Protection.php";

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

$logging = new Logging($database);
$registry->set('logging', $logging);

$session = new Session($database, $generator, $logging, $allowIndexPage ?? false);
$registry->set('session', $session);

$form = new Form(); // depends on session is started
$registry->set('form', $form);

//
$battle = new Battle($database, $form);
$registry->set('battle', $battle);

$message = new Message($database, $session);
$registry->set('message', $message);

$profile = new Profile($database, $form, $session);
$registry->set('profile', $profile);

$ranking = new Ranking($database, $multisort, $session);
$registry->set('ranking', $ranking);

$alliance = new Alliance($database, $form, $session);
$registry->set('alliance', $alliance);

if (isset($loadVillage)) {
    $technology = new Technology($database, $generator, $logging, $session);
    $registry->set('technology', $technology);
    
    $village = new Village($database, $logging, $session, $technology);
    $registry->set('village', $village);
    
    $ranking->setVillage($village);
    $alliance->setVillage($village);
    $technology->setVillage($village);
    
    //
    $building = new Building($database, $generator, $logging, $session, $technology, $village);
    $registry->set('building', $building);
    
    $market = new Market($building, $database, $generator, $logging, $multisort, $session, $village);
    $registry->set('market', $market);
    
    $units = new Units($database, $form, $generator, $session, $village);
    $registry->set('units', $units);
}

if (isset($loadVillage) || isset($loadAutomation)) {
    $automation = new Automation\Automation($battle, $database, $form, $generator, $ranking, $session, $technology, $units, $village);
    $registry->set('automation', $automation);
}
