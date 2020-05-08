<?php

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

if (!file_exists('GameEngine/config.php') &&
    !file_exists('../GameEngine/config.php') &&
    !file_exists('../../GameEngine/config.php') &&
    !file_exists('../../config.php')
) {
    // ResponseHelper::redirect('install/');
    header('Location: install/');
    exit;
}

// loader
require_once "GameEngine/Data/buidata.php";
require_once "GameEngine/Data/cp.php";
require_once "GameEngine/Data/cel.php";
require_once "GameEngine/Data/resdata.php";
require_once "GameEngine/Data/unitdata.php";
require_once "GameEngine/Data/hero_full.php";

require_once "GameEngine/config.php";
require_once "GameEngine/Lang/" . LANG . ".php";

// classes
require_once 'vendor/autoload.php';
require_once 'autoloader.php';

$mailer = new Mailer();
$generator = new MyGenerator();
$multisort = new Multisort();

$database = new MysqliModel();

// Protection is not a class, but depends on database. And as most of classes have business logic in constructor,
// so we should call it asap. For now let it bee so
require_once "GameEngine/Protection.php";

$logging = new Logging($database);
$session = new Session($database, $generator, $logging);
$form = new Form(); // depends on session is started

$battle = new Battle($database, $form);
$message = new Message($database, $session);
$profile = new Profile($database, $form, $session);
$ranking = new Ranking($database, $multisort, $session);
$alliance = new Alliance($database, $form, $session);

if (isset($loadVillage)) {
    $technology = new Technology($database, $generator, $logging, $session);
    $village = new Village($database, $logging, $session, $technology);
    $ranking->setVillage($village);
    $alliance->setVillage($village);
    $technology->setVillage($village);
    
    $building = new Building($database, $generator, $logging, $session, $technology, $village);
    $market = new Market($building, $database, $generator, $logging, $multisort, $session, $village);
    $units = new Units($database, $form, $generator, $session, $village);
}

if (isset($loadVillage) || isset($loadAutomation)) {
    $automation = new Automation\Automation($battle, $database, $form, $generator, $ranking, $session, $technology, $units, $village);
}